<?php


/**
 * Service
 */

class Core_Payment_Service extends Core_Data_Storage {

	protected $_table='p_orders';
	protected $_fields=array( 'id', 'user_id', 'package_id', 'amount', 'flg_confirm','added' );
	protected $_data=null;

	private $_adapter=null;
	private $_params=null;

	private $_withUsers=false;
	private $_onlyConfirmed=false;
	private $_onlyNoConfirmed=false;
	private $_withPackage=false;
	private $_withPackageId=false;
	private $_withUserId=false;
	private $_withCredits=false;
	private $_logger=false;
	const TYPE_PAYMENT=0,TYPE_REFUND=1;
	const PAYMENT_TYPE_CLICK2SELL=1,PAYMENT_TYPE_JVZOO=2;

	public function __construct(){
		$this->setLogger();
	}

	private function setLogger() {
		$writer=new Zend_Log_Writer_Stream( Zend_Registry::get('config')->path->absolute->logfiles.'Core_Payment_Service.log' );
		$writer->setFormatter( new Zend_Log_Formatter_Simple("%timestamp% %priorityName% (%priority%): %message%\r\n") );
		$this->_logger=new Zend_Log( $writer );
	}

	public function withUsers(){
		$this->_withUsers=true;
		return $this;
	}

	public function onlyConfirmed(){
		$this->_onlyConfirmed=true;
		return $this;
	}

	public function onlyNoConfirmed(){
		$this->_onlyNoConfirmed=true;
		return $this;
	}

	public function withPackage(){
		$this->_withPackage=true;
		return $this;
	}

	public function withPackageId( $_arrIds ){
		if( !empty($_arrIds) ){
			$this->_withPackageId=$_arrIds;
		}
		return $this;
	}

	public function withUserId( $_arrIds ){
		if( !empty($_arrIds) ){
			$this->_withUserId=$_arrIds;
		}
		return $this;
	}

	public function withCredits(){
		$this->_withCredits=true;
		return $this;
	}

	protected function init(){
		parent::init();
		$this->_withUsers=false;
		$this->_onlyConfirmed=false;
		$this->_onlyNoConfirmed=false;
		$this->_withCredits=false;
		$this->_withPackage=false;
		$this->_withPackageId=false;
		$this->_withUserId=false;
	}

	protected function assemblyQuery(){
		parent::assemblyQuery();
		if( $this->_withUsers ){
			$this->_crawler->set_from('LEFT JOIN u_users u ON u.id=d.user_id');
		}
		if( $this->_onlyConfirmed ){
			$this->_crawler->set_where('d.flg_confirm=1');
		}
		if( $this->_onlyNoConfirmed ){
			$this->_crawler->set_where('d.flg_confirm=0');
		}
		if( $this->_withCredits ){
			$this->_crawler->set_from('LEFT JOIN p_package p ON p.id=d.package_id');
			$this->_crawler->set_where('p.flg_type=1');
		}
		if( $this->_withPackage ){
			$this->_crawler->set_from('LEFT JOIN p_package p ON p.id=d.package_id');
			$this->_crawler->set_where('p.flg_type=0' );
		}
		if( $this->_withPackageId ){
			$this->_crawler->set_where('d.package_id IN ('. Core_Sql::fixInjection($this->_withPackageId) .')');
		}
		if( $this->_withUserId ){
			$this->_crawler->set_where('d.user_id IN ('. Core_Sql::fixInjection($this->_withUserId) .')');
		}
	}

	public function setParams( $_params ){
		$this->_params=$_params;
		return $this;
	}

	public function setAdapter( $_adapter ){
		$_className='Core_Payment_Adapter_'.$_adapter;
		if( empty($_adapter) || !class_exists( $_className ) ){
			$this->_logger->err('Can\'t finde adapter:'.$_adapter);
			return false;
		}
		$this->_adapter=new $_className();
		return $this;
	}
	public function run(){
		$this->_logger->info('run()-> : '.serialize($this->_params));
		if( empty($this->_params) || !$this->_adapter->setData( $this->_params ) ){
			$this->_logger->err('Empty _params: '.serialize($this->_params));
			return false;
		}
		$this->_data=$this->_adapter->getData();
		switch( $this->_data->filtered['transaction_type'] ){
			case self::TYPE_REFUND:
				$this->refund();
				break;
			case self::TYPE_PAYMENT:
			default:
				$this->payment();
				break;
		}
	}

	public static function redirect(){
		$_params=Core_Payment_Encode::decode($_GET['params']);
		Core_Users::getInstance()->setById($_params['user_id']);
		$_package=new Core_Payment_Package();
		$_package->withHided()->onlyOne()->withIds( $_params['package_id'] )->getList($arrPak);
		if(empty($arrPak)){
			die('Can\'t find package.');
		}
		if( Core_Payment_Package::isFree($arrPak) ){
			$_free=new Project_Users_Free();
			$_free->setPackage($arrPak['id'])->setUser( Core_Users::$info['id'] )->add();
			header('Location: '.Core_Module_Router::$domain.'/?reload=1');
			return;
		}
		$_payment=new self();
		$_payment->setEntered(array('package_id'=>$arrPak['id'],'amount'=>$arrPak['cost'],'flg_confirm'=>0))->set();
		header('Location: '.$arrPak['click2sell_redirect_url']);
	}

	protected function beforeSet(){
		if ( !$this->_data->setFilter( array( 'trim', 'clear' ) )->setChecker( array(
			'package_id' => empty( $this->_data->filtered['package_id'] ),
//			'amount' => empty( $this->_data->filtered['amount'] )
		))->check() ){
			$this->_data->getErrors( $this->_errors );
			$this->_logger->err('Required filds empty: '.serialize($this->_errors) );
			return false;
		}
		return true;
	}

	private function refund(){
		$_package=new Core_Payment_Package();
		if( !$_package->withHided()->onlyOne()->withIds( $this->_data->filtered['package_id'] )->getList( $_arrPack )->checkEmpty() ){
			$this->_logger->err('REFUND: Can\'t find package: '.$this->_data->filtered['package_id']);
			return false;
		}
		if( empty(Core_Users::$info['id']) ){
			$this->_logger->err('REFUND: Can\'t find user: '.serialize($this->_data->filtered));
			return false;
		}
		$_subscr=new Core_Payment_Subscription();
		if( !$_subscr->onlyOne()->withPackage( $_arrPack['id'] )->onlyOwner()->getList( $_arrSubsc )->checkEmpty() ){
			$this->_logger->err('REFUND: Can\'t find subscribe: '.serialize($this->_data->filtered));
			return false;
		}
		$_subscr->withIds( $_arrSubsc['id'] )->del();
		$_purse=new Core_Payment_Purse();

		$_group=new Core_Acs_Groups();
		$_group->withIds( Core_Users::$info['id'] )->getGroupByUserId( $_currentUsersGroups );
		unset($_currentUsersGroups[$_arrPack['group_id']]);
		$_group->withIds( Core_Users::$info['id'] )->setGroupByIds( $_currentUsersGroups );
		if( Zend_Registry::isRegistered( 'translate' ) ){
			$_purse->setMessage(Zend_Registry::get( 'translate' )->_('Successfully removed the package '.$_arrPack['title'].'. ').sprintf( Zend_Registry::get( 'translate' )->plural('%d credit have been removal from account balance.','%d credits have been removal from account balance.',$_arrPack['credits'] ),$_arrPack['credits']) );
		} else {
			$_purse->setMessage( 'Successfully removed the package '.$_arrPack['title'].'. '.sprintf( (($_arrPack['credits']==1)?'%d credit have been removal from account balance.':'%d credits have been removal from account balance.'),$_arrPack['credits']) );
		}
		$this->_logger->info('REFUND: End transaction: '.serialize($this->_data->filtered) );
		$_purse
			->setAmount( $_arrPack['credits'] )
			->setUserId( Core_Users::$info['id'])
			->expenditure();
	}

	private function payment(){
		$_package=new Core_Payment_Package();
		if( !$_package->withHided()->onlyOne()->withIds( $this->_data->filtered['package_id'] )->getList( $_arrPack )->checkEmpty() ){
			$this->_logger->err('Can\'t find package: '.$this->_data->filtered['package_id']);
			return false;
		}
		if( empty(Core_Users::$info['id']) ){
			$this->_logger->err('Can\'t find user: '.serialize($this->_data->filtered));
			return false;
		}
		$_purse=new Core_Payment_Purse();
		$_purse
			->setAmount( $_arrPack['credits'] )
			->setUserId( Core_Users::$info['id']);
		if( $_arrPack['flg_type']!=1 ){
			// обновляем группы пользователя
			$_group=new Core_Acs_Groups();
			$_subscr=new Core_Payment_Subscription();
			$_group->withIds( Core_Users::$info['id'] )->getGroupByUserId( $_currentUsersGroups );
			$_group->withIds( $_arrPack['group_id'] )->onlyOne()->getList( $_tmpArr );
			if( $_tmpArr['sys_name']=='Unlimited' ){ // Если пользователь купил Unlimited, возможно у него есть CNM1.0 ее удаляем. - Обновление CNM1.0 до Unlimited
				foreach( $_currentUsersGroups as $_k=>$_v ){
					if( $_v=='CNM1.0' ){
						$_package->withHided()->onlyOne()->withGroupId($_k)->getList( $_arrTmp1 );
						unset($_currentUsersGroups[$_k]);
						if( $_subscr->onlyOne()->onlyOwner()->withPackage( $_arrTmp1['id'] )->getList( $_arrSub )->checkEmpty() ){
							$_subscr->withIds( $_arrSub['id'] )->del();
						}
					}
				}
			}
			$_currentUsersGroups[$_arrPack['group_id']]=true;
			$_group->withIds( Core_Users::$info['id'] )->setGroupByIds( $_currentUsersGroups );
			if( !$_subscr->onlyOne()->withPackage( $_arrPack['id'] )->onlyOwner()->getList( $_arrSubsc )->checkEmpty() ){
				$cycles_remain=($_arrPack['cycles']<=0)?0:$_arrPack['cycles']-1;
				$_expiry=time()+Core_Payment_Package::getLengthInSeconds( $_arrPack );
				if( $_expiry>2147483647 ){
					$_expiry=2147483647; // MAX 2038-01-19 03:14:07 !!TODO переделать поле INT в котором хранится эта дата на DATETIME
				}
				$_subscr->check( $_arrPack )->setEntered(array(
					'package_id'=>$_arrPack['id'],
					'user_id'=>Core_Users::$info['id'],
					'flg_lifetime'=>($cycles_remain==0&&$_arrPack['cycles']>0)?1:0,
					'cycles_remain'=>$cycles_remain,
					'expiry'=>$_expiry,
					'flg_auto'=>$this->_data->filtered['flg_auto'],
					'transaction_id'=>$this->_data->filtered['transaction_id'],
					'payment_type'=>$this->_data->filtered['payment_type'],
					'counter'=>1
				))->set();
				if( Zend_Registry::isRegistered( 'translate' ) ){
					$_purse->setMessage(Zend_Registry::get( 'translate' )->_('Successfully added the package '.$_arrPack['title'].'. ').sprintf( Zend_Registry::get( 'translate' )->plural('%d credit have been added to account balance.','%d credits have been added to account balance.',$_arrPack['credits'] ),$_arrPack['credits']) );
				} else {
					$_purse->setMessage( 'Successfully added the package '.$_arrPack['title'].'. '.sprintf( (($_arrPack['credits']==1)?'%d credit have been added to account balance.':'%d credits have been added to account balance.'),$_arrPack['credits']) );
				}
				if( Core_Users::$info['flg_confirm']==1 ){
					$this->notification($_arrPack);
				}
			} else {
				if( (!empty($_arrPack['recurring_cost'])||!empty($_arrPack['recurring_credits']))&&$_arrSubsc['counter']>0 ){
					$_purse->setAmount( $_arrPack['recurring_credits'] );
					$_arrPack['credits']=$_arrPack['recurring_credits'];
					$_arrPack['cost']=$_arrPack['recurring_cost'];
				}
				$cycles_remain=($_arrSubsc['cycles_remain']<=0)?0:$_arrSubsc['cycles_remain']-1;
				$_expiry=time()+Core_Payment_Package::getLengthInSeconds( $_arrPack );
				if( $_expiry>2147483647 ){
					$_expiry=2147483647; // MAX 2038-01-19 03:14:07 !!TODO переделать поле INT в котором хранится эта дата на DATETIME
				}
				$_subscr->setEntered(array(
					'id'=>$_arrSubsc['id'],
					'package_id'=>$_arrPack['id'],
					'user_id'=>Core_Users::$info['id'],
					'flg_lifetime'=>($cycles_remain==0&&$_arrPack['cycles']>0)?1:0,
					'cycles_remain'=>$cycles_remain,
					'expiry'=>$_expiry,
					'transaction_id'=>$this->_data->filtered['transaction_id'],
					'payment_type'=>$this->_data->filtered['payment_type'],
					'counter'=>$_arrSubsc['counter']+1
				))->set();
				if( Zend_Registry::isRegistered( 'translate' ) ){
					$_purse->setMessage(Zend_Registry::get( 'translate' )->_('Successfully updated the package '.$_arrPack['title'].'. ').sprintf( Zend_Registry::get( 'translate' )->plural('%d credit have been added to account balance.','%d credits have been added to account balance.',$_arrPack['credits'] ),$_arrPack['credits']) );
				} else {
					$_purse->setMessage( 'Successfully updated the package '.$_arrPack['title'].'. '.sprintf( (($_arrPack['credits']==1)?'%d credit have been added to account balance.':'%d credits have been added to account balance.'),$_arrPack['credits']) );
				}
			}
		} else {
			if( Zend_Registry::isRegistered( 'translate' ) ){
				$_purse->setMessage(sprintf( Zend_Registry::get( 'translate' )->plural('%d credit have been added to account balance.','%d credits have been added to account balance.',$_arrPack['credits'] ),$_arrPack['credits']) );
			} else {
				$_purse->setMessage(sprintf( (($_arrPack['credits']==1)?'%d credit have been added to account balance.':'%d credits have been added to account balance.'),$_arrPack['credits']) );
			}
		}
		$_reggi=new Project_Users_Registration();
		if( Core_Users::$info['flg_confirm']==0&&!$_reggi->setEntered( array('user'=>Core_Users::$info), 'user')->complete() ){
			$this->_logger->err('Can\'t complete registration');
			return false;
		}
		$this->_logger->info('End transaction: '.serialize($this->_data->filtered) );
		Project_Statistics_Package::add( $_arrPack['id'],Project_Statistics_Package::TYPE_SALE );
		if( $_purse->receipts() ){
			$this->onlyOne()->withUserId( Core_Users::$info['id'] )->withPackageId( $_arrPack['id'] )->getList( $arrOrder );
			$this->setEntered(array(
				'package_id'=>$_arrPack['id'],
				'amount'=>$_arrPack['cost'],
				'flg_confirm'=>1,
				'id'=>(!empty($arrOrder['id'])?$arrOrder['id']:'')
			))->set();
		}
		Core_Users::getInstance()->reload();
	}

	private function notification($arrPack){
		Core_Users::$info['arrPkg']=$arrPack;
		// отправляем пароль пользователю
		$_mailer=new Core_Mailer();
		if ( !$_mailer
			->setVariables( Core_Users::$info )
			->setTemplate( 'api_registration_complete' )
			->setSubject( 'Your Account Has Been Created' )
			->setPeopleTo( array( 'email'=>Core_Users::$info['email'], 'name'=>Core_Users::$info['nickname'] ) )
			->setPeopleFrom( array( 'name' => Zend_Registry::get('config')->engine->project_sysemail->name, 'email' => Zend_Registry::get('config')->engine->project_sysemail->email ) )
			->sendOneToMany() ) {
			return $this->setHeadError( 'email don\'t send' );
		}
	}

	public function addBonus( $packageIds, $_intCredits ){
		$_intCredits=intval($_intCredits);
		if(empty($packageIds)||empty($_intCredits)){
			return false;
		}
		$_subscr=new Core_Payment_Subscription();
		$_subscr->withPackage( $packageIds )->getList( $arrSubscr );
		if(empty($arrSubscr)){
			return false;
		}
		foreach( $arrSubscr as $_subscr ){
			Core_Users::getInstance()->withCashe()->setById( $_subscr['user_id'] );
			$_purse=new Core_Payment_Purse();
			$_purse
				->setAmount( $_intCredits )
				->setUserId( $_subscr['user_id'] )
				->setMessage(sprintf( (($_intCredits==1)?'%d credit have been added to account balance.':'%d credits have been added to account balance.'),$_intCredits) )
				->receipts();
			Core_Users::getInstance()->retrieveFromCashe();
		}
		return true;
	}
}
?>