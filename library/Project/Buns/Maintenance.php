<?php
class Project_Buns_Maintenance implements Core_Payment_Buns_Interface {

	private $_arrSettings=array();
	private $_logger=false;
	
	public function checkExpired( $arrCurrentBunSettings=array() ){
		$this->setLogger();
		$this->_arrSettings=$arrCurrentBunSettings;
		$this->_logger->info('Start Maintenance checkExpired');
		if( $this->_arrSettings['flg_length'] == Core_Payment_Buns::LENGTH_DAY || $this->_arrSettings['flg_length'] == Core_Payment_Buns::ITEMS
			&& date('H',time())==1&&date('H.d',$this->_arrSettings['edited'])!=date('H.d',time())
		){ // каждый первый час дня
			$this->_logger->info('every day get credits');
			$this->maintenance(); // обрабатываются пользователи
			$_buns=new Core_Payment_Buns();
			$this->_arrSettings['edited']=time();
			$_buns->setEntered( $this->_arrSettings )->set();
		}elseif( ( $this->_arrSettings['flg_length'] == Core_Payment_Buns::LENGTH_MONTH || $this->_arrSettings['flg_length'] == Core_Payment_Buns::LENGTH_YEAR )
			&& date('d',time())==1 && date('d.m',$this->_arrSettings['edited'])!=date('d.m',time())
		){ // каждое 1-е число. для flg_length 1 & 2
			$this->_logger->info('1 day. get credits.');
			$this->maintenance(); // обрабатываются пользователи
			$_buns=new Core_Payment_Buns();
			$this->_arrSettings['edited']=time();
			$_buns->setEntered( $this->_arrSettings )->set();
		} else {
			$this->_logger->info('debtors. get credits.');
			$this->debtors();
		}
		$this->_logger->info('End Maintenance checkExpired');
	}

	/**
	 * Запускается 1-го числа каждого месяца.
	 * Снимает с пользователей Х кредитов.
	 * Уменьшает/Увеличивает expiry на месяц или год
	 * @param $arrSettings
	 */
	private function maintenance(){
		$_users=new Project_Users_Management();
		$_users->onlyConfirm()->onlyMaintenance()->onlyActive()->withGroups(array(Core_Acs::$maintenance))->getList( $arrRes ); // получаем список пользователей
		// ----------------------------------------------------------------------------------------
		$_arrUserIds=array();
		foreach( $arrRes as $_user ){
			$_arrUserIds[]=$_user['id'];
		}
		$_groups=new Core_Acs_Groups();
		$_groups->withIds( $_arrUserIds )->getGroupsByUserIds( $_arrGroups );
		$_buns=new Core_Payment_Buns();
		$_buns->withSysName('Project_Placement_Hosting')->onlyOne()->getList( $arrHosting );
		foreach( $_arrGroups as $_group ){
			foreach( $arrRes as &$_user ){
				if( $_user['id'] == $_group['user_id'] ){
					$_user['groups'][$_group['group_id']]=$_group['sys_name'];
					$_user['_minBalance']=0; // Core_Payment_Purse::$_minBalance
					if( $_group['sys_name'] == 'Blog Fusion CSPP' && $arrProfile['domains_parked']<50 ){
						$_user['_minBalance']=$arrHosting['credits']*(50-$arrProfile['domains_parked']);
					}
					if( $_group['sys_name'] == 'Blog Fusion CSP' && $arrProfile['domains_parked']<5 ){
						$_user['_minBalance']=$arrHosting['credits']*(5-$arrProfile['domains_parked']);
					}
					$_user['_getAmount']=($_user['amount']>=$_user['_minBalance'])?($_user['amount']-$_user['_minBalance']):0;
					continue;
				}
			}
		}
		// ---------------------------------------------------------------------------------------- */
		// flg_expire == 0 - т.е. это те которые или оплатили и им нужно снять период или должны оплатить за текущий
		foreach( $arrRes as $_removeId=>$_user ){
			if( $_user['expiry'] > 0 ){ // оплатили за несколько периодов вперед
				$_user['expiry']=$_user['expiry']-1; // снимаем один период
				$_users->withIds( $_user['id'] )->setExpiry($_user['expiry'],0); // обновляем данные, без задолженностей
				unset( $arrRes[$_removeId] );
			}
		}
		foreach( $arrRes as $_user ){  // пробежка по пользователям
//			Core_Users::getInstance()->setById($_user['id']);
			usleep(10);
			if( $_user['expiry'] == 0 ){ // закончилась оплата
				$_groups=new Core_Acs_Groups();
				if( $_user['_getAmount']<$this->_arrSettings['credits'] ){
					// средств у пользователя не достаточно
					$_user['expiry']=$_user['expiry']+1; // добавляем пользователю период
					$_users->withIds( $_user['id'] )->setExpiry($_user['expiry'],1); // устанавливаем что у него есть задолженность
					$_groups->withIds($_user['id'])->removeGroupByName(Core_Acs::$maintenance); // у пользователя удаляется группа, чтобы небыло доступа к функционалу
					if( $_user['expiry']!=$_user['flg_sended']){ // если ему не высылали сообщения за эту проверку то делаем это
						$this->notifications( $_user );
						$this->_logger->info('Send notifications for user '.$_user['id'] );
						$_users->withIds($_user['id'] )->setSended($_user['expiry']);
					}
				}else{
					// средств у пользователя достаточно
					$_purse=new Core_Payment_Purse();
					$_purse
						->setUserId( $_user['id'] )
						->setAmount( $this->_arrSettings['credits'] )
						->setMessage($this->_arrSettings['description'].' '.$this->_arrSettings['credits'].' credits')
						->setType( Core_Payment_Purse::TYPE_INTERNAL )
						->expenditure();
					$this->_logger->info('Purse user '.$_user['id'] );
					if( $this->_arrSettings['flg_length'] == Core_Payment_Buns::LENGTH_MONTH
						|| $this->_arrSettings['flg_length'] == Core_Payment_Buns::LENGTH_DAY
						|| $this->_arrSettings['flg_length'] == Core_Payment_Buns::ITEMS
					){
						$_expiry=1;
					}elseif( $this->_arrSettings['flg_length'] == Core_Payment_Buns::LENGTH_YEAR ){
						$_expiry=12;
					}
					$_users->withIds( $_user['id'] )->setExpiry($_expiry,0);
					$_users->withIds($_user['id'] )->setSended(0);
					$_groups->withIds( $_user['id'] )->addGroupByName( Core_Acs::$maintenance );
				}
			}
	//		Core_Users::logout();
		}
	}

	private function debtors(){
		
		$start = microtime(true);
		
		$time = microtime(true) - $start;
		
		
		
		$_users=new Project_Users_Management();
		if( !$_users->onlyMaintenance()->onlyActive()->onlyExpiry()->getList( $arrRes )->checkEmpty() ){
			$this->_logger->info('Can\'t finde debtors'); // оптимистично
			return false;
		}
		// ---------------------------------------------------------------------------------------- */
		$_arrUserIds=array();
		foreach( $arrRes as $_user ){
			$_arrUserIds[]=$_user['id'];
		}
		$_groups=new Core_Acs_Groups();
		$_groups->withIds( $_arrUserIds )->getGroupsByUserIds( $_arrGroups );
		$_buns=new Core_Payment_Buns();
		$_buns->withSysName('Project_Placement_Hosting')->onlyOne()->getList( $arrHosting );
		foreach( $_arrGroups as $_group ){
			foreach( $arrRes as &$_user ){
				if( $_user['id'] == $_group['user_id'] ){
					$_user['groups'][$_group['group_id']]=$_group['sys_name'];
					$_user['_minBalance']=0; // Core_Payment_Purse::$_minBalance
					if( $_group['sys_name'] == 'Blog Fusion CSPP' && $arrProfile['domains_parked']<50 ){
						$_user['_minBalance']=$arrHosting['credits']*(50-$arrProfile['domains_parked']);
					}
					if( $_group['sys_name'] == 'Blog Fusion CSP' && $arrProfile['domains_parked']<5 ){
						$_user['_minBalance']=$arrHosting['credits']*(5-$arrProfile['domains_parked']);
					}
					$_user['_getAmount']=($_user['amount']>=$_user['_minBalance'])?($_user['amount']-$_user['_minBalance']):0;
					continue;
				}
			}
		}
		foreach( $arrRes as $_user ){
	//		Core_Users::getInstance()->setById( $_user['id'] );
			if( $_user['expiry']==$_user['flg_sended'] && $_user['_getAmount']<($this->_arrSettings['credits']*$_user['expiry']) ){
				continue;
			}
		//	if( Core_Users::$info['id']!=$_user['id'] ){ 
		//		$this->_logger->info('Can not set debtor '.$_user['id'].' to get maintenance');
		//		continue;
		//	}
			// ---------------------------------------------------------------------------------------- */
			usleep(10); // возможно в связи с репликацией не успевает отработать предыдущий метод, в итоге проблема с проверкой суммы на счету
			if( $_user['_getAmount']<=($this->_arrSettings['credits']*$_user['expiry']) ){
				$_groups->withIds($_user['id'])->removeGroupByName(Core_Acs::$maintenance);
				if( $_user['expiry']!=$_user['flg_sended'] ){
					$this->_logger->info('Send notifications for debtor '.$_user['id'] );
					$this->notifications( $_user );
					$_users->withIds($_user['id'] )->setSended($_user['expiry']);
				}
			//	Core_Users::logout();
				continue;
			}
			$this->_logger->info('Purse debtor '.$_user['id'] );
			$_purse=new Core_Payment_Purse();
			$_purse
				->setUserId( $_user['id'] )
				->setAmount( ($this->_arrSettings['credits']*$_user['expiry']) )
				->setMessage( $this->_arrSettings['description'].' '.($this->_arrSettings['credits']*$_user['expiry']).' credits')
				->setType( Core_Payment_Purse::TYPE_INTERNAL )
				->expenditure();
			$_users->withIds( $_user['id'] )->setExpiry(0,0);
			$_users->withIds($_user['id'] )->setSended(0);
			$_groups->withIds( $_user['id'] )->addGroupByName(Core_Acs::$maintenance);
			//Core_Users::logout();
		}
	}

	private function notifications($arrProfile){
		if( $this->_arrSettings['flg_length'] == Core_Payment_Buns::LENGTH_DAY
			|| $this->_arrSettings['flg_length'] == Core_Payment_Buns::ITEMS
		){
			$_expiryPoints=30;
		}elseif( $this->_arrSettings['flg_length'] == Core_Payment_Buns::LENGTH_YEAR
			|| $this->_arrSettings['flg_length'] == Core_Payment_Buns::LENGTH_MONTH
		){
			$_expiryPoints=1;
		}
		if( $arrProfile['flg_expire']==0&&$arrProfile['expiry']==0 ){ // У пользователя нет кредитов, будем отключать.
			$this->maintenanceExpiry( $arrProfile );
		}elseif( $arrProfile['flg_expire']==1&&$arrProfile['expiry']==$_expiryPoints ){// не оплатил за месяц
			// ---
		}elseif( $arrProfile['flg_expire']==1&&$arrProfile['expiry']==$_expiryPoints*2 ){// не оплатил за 2 месяца
			$this->maintenanceExpiry2Month( $arrProfile );
			$_users=new Project_Users_Management();
			$_users->withIds( $arrProfile['id'] )->setMaintenance( false );
		}
	}

	private function maintenanceExpiry( $arrProfile ){
		$this->_logger->info('send message to userID:'.$arrProfile['id'].': Important: We could not process the '.$this->_arrSettings['credits'].' credits support and maintenance fee');
		$_mailer=new Core_Mailer();
		$_mailer->setVariables( array(
					'user'=>$arrProfile,
					'settings'=>$this->_arrSettings,
				) )
				->setTemplate( 'maintenance_expiry' )
				->setSubject( 'Important: We could not process the '.$this->_arrSettings['credits'].' credits support and maintenance fee' )
				->setPeopleTo( array( 'email'=>$arrProfile['email'], 'name'=>$arrProfile['buyer_name'] ) )
				->setPeopleFrom( array( 'name' => Zend_Registry::get('config')->engine->project_sysemail->name, 'email' => Zend_Registry::get('config')->engine->project_sysemail->email ) )
				->sendOneToMany();
	}

	private function maintenanceExpiry2Month( $arrProfile ){ return true;
		$this->_logger->info('send message to userID:'.$arrProfile['id'].': Important: 2 moth');
		$_mailer=new Core_Mailer();
		$_mailer->setVariables( array(
					'user'=>$arrProfile,
					'settings'=>$this->_arrSettings,
				) )
				->setTemplate( 'maintenance_expiry_2month' )
				->setSubject( 'Important: We could not process the '.$this->_arrSettings['credits'].' credits support and maintenance fee' )
				->setPeopleTo( array( 'email'=>$arrProfile['email'], 'name'=>$arrProfile['buyer_name'] ) )
				->setPeopleFrom( array( 'name' => Zend_Registry::get('config')->engine->project_sysemail->name, 'email' => Zend_Registry::get('config')->engine->project_sysemail->email ) )
				->sendOneToMany();
	}


	private function setLogger() {
		$writer=new Zend_Log_Writer_Stream( 'php://output' );
		$writer->setFormatter( new Zend_Log_Formatter_Simple("%timestamp% %priorityName% (%priority%): %message%<br/>\r\n") );
		$this->_logger=new Zend_Log( $writer );
	}

}
?>