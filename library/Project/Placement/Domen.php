<?php

/**
 * Проверка поокупка доменов.
 */
class Project_Placement_Domen implements Core_Payment_Buns_Interface {

	/**
	 * Core Data object
	 * @var Core_Data object
	 */
	private $_data=false;

	private $_errors=false;

	private $_years=false;
	public  $expiry=false;
	private $_logger=false;

	private $_domenPrice=false;
	private $_hostingPrice=false;

	public function __construct(){
		$_buns=new Core_Payment_Buns();
		if( !$_buns->onlyOne()->withSysName('Project_Placement_Domen')->getList( $_arrResDomain ) ){
			throw new Exception('Can\'t find Buns with name Project_Placement_Domen');
		}
		if( !$_buns->onlyOne()->withSysName('Project_Placement_Hosting')->getList( $_arrResHosting ) ){
			throw new Exception('Can\'t find Buns with name Project_Placement_Hosting');
		}
		$this->expiry=Core_Payment_Buns::getLength( $_arrResDomain );
		$this->_years=$this->expiry/365/24/60/60;// seconds to years
		$this->_domenPrice=$_arrResDomain['credits'];
		$this->_hostingPrice=$_arrResHosting['credits'];
	}

	/**
	 * @param array $arrCurrentBunSettings
	 * @return mixed
	 */
	public function checkExpired( $arrCurrentBunSettings=array() ){
		$this->setLogger();
		$this->_logger->info('Start DOMAIN checkExpired');
		$_buns=new Core_Payment_Buns();
		$_buns->onlyOne()->withSysName('Project_Placement_Hosting')->getList( $_arrHosting );
		$this->notification( $arrCurrentBunSettings );
		$_purse=new Core_Payment_Purse();
		$_placement=new Project_Placement();
		$this->_logger->info('Start auto renew');
		if( !$_placement->withType( array(Project_Placement::LOCAL_HOSTING_DOMEN) )->onlyAuto()->withoutLoss()->getList( $arrRes )->checkEmpty() ){
			$this->_logger->err('can\'t find expired hosting..');
			return;
		}
		$_namecheap=new Project_Placement_Domen_Namecheap();
		foreach( $arrRes as $_item ){
			Core_Users::getInstance()->setById($_item['user_id']);
			usleep(10);
			$this->_logger->info('Start renew for '.$_item['domain_http']);
			$_domainInfo=false;
			$_return=$_namecheap->setEntered( array('DomainName'=>$_item['domain_http']) )->getInfo( $_domainInfo );
			if( empty( $_domainInfo) || $_domainInfo['Status'] == 'Locked' || $_domainInfo['IsOwner'] == false ){
				$this->_logger->err('namecheap error: '. join('<br/> -',Core_Data_Errors::getInstance()->getErrorsFlow()) );
				$this->_logger->err('namecheap data: '. @serialize( $_domainInfo ) );
				$this->_logger->err('namecheap return: '. @serialize( $_return ) );
				$this->_logger->err('namecheap get error: '. @serialize( $_namecheap->getErrors() ) );
				Core_Users::logout();
				continue;
			}else{
				$this->_logger->err('namecheap getInfo log: '.serialize( $_domainInfo ) );
			}
			// у домена истек срок или скоро истечет
			if( $_domainInfo['Status'] == 'Expired' || ( $_domainInfo['Status'] == 'Ok' && strtotime( $_domainInfo['DomainDetails']['ExpiredDate'] ) <= (time()+Project_Placement::$_time) ) ){
				// не продлеваем домен если у пользователя нет средств на хостинг+домен и при этом оплата хостинга просрочена
				if( $_item['expiry_hosting']<(time()-Project_Placement::$_time)&&(Core_Payment_Purse::getAmount()<($arrCurrentBunSettings['credits']+$_arrHosting['credits'])) ){
					$this->_logger->err('user not have credits for renew honsting+domain..');
					Core_Users::logout();
					continue;
				}
				if( Core_Payment_Purse::getAmount() < $arrCurrentBunSettings['credits'] ){
					$this->_logger->err('user not have credits for renew..');
					Core_Users::logout();
					continue;
				}
				$this->_logger->info('renew domain: '.$_item['domain_http']);
				// если отработало за 1 день до окончания срока действия
				if( !$_namecheap->setEntered( array('DomainName'=>$_item['domain_http'],'Years'=>$this->_years) )->renew( $res ) ){
					//если пустое в $res то высылать админам сообщение - деньги кончились на namecheap
					$this->_logger->err( 'namecheap renew log: '.serialize( $res ) );
					$this->_logger->err( 'renew is blocked!' );
					// если отработало после окончания срока действия
					if( !$_namecheap->setEntered( array('DomainName'=>$_item['domain_http']) )->reactivate( $res ) ){
						$this->_logger->err('namecheap reactivate log: '.serialize( $res ) );
						$this->_logger->err('namecheap error: '. join('<br/> -',Core_Data_Errors::getInstance()->getErrorsFlow()) );
						Core_Users::logout();
						continue;
					}
				}
				$this->_logger->err('namecheap successful renew/reactivate log: '.serialize( $res ) );
				$_item['expiry_domain']=time()+Core_Payment_Buns::getLength( $arrCurrentBunSettings );
			}elseif( $_domainInfo['Status'] == 'Ok' ){ // срок у домена продлен, но почему-то не обновлен в базе
				$this->_logger->err('update user domain from namecheap data: '.$_item['domain_http'] );
				$_item['expiry_domain']=strtotime( $_domainInfo['DomainDetails']['ExpiredDate'] );
			}
			$this->_logger->err('get credits '.$arrCurrentBunSettings['credits'].' for domain '.$_item['domain_http'].' update' );
			$_placement->setEntered( $_item )->set();
			if( Core_Payment_Purse::getAmount() < $arrCurrentBunSettings['credits'] ){
				$this->_logger->err('user not have credits for renew, but renewed domain');
				Core_Users::logout();
				continue;
			}
			$_purse
				->setAmount( $arrCurrentBunSettings['credits'] ) // Сумма кредитов за домен
				->setUserId( $_item['user_id'] )
				->setType( Core_Payment_Purse::TYPE_INTERNAL )
				->setMessage( $arrCurrentBunSettings['description'] .' domain:'.$_item['domain_http'] )
				->expenditure();
			Core_Users::logout();
		}
		$this->_logger->info('End DOMAIN checkExpired');
	}

	/**
	 * Отправка пользователям сообщений если до наступления
	 *  срока оплаты остался месяц,неделя,день
	 * @static
	 */
	private function notification( $arrCurrentBunSettings ){
		$this->_logger->info('Start notification DOMAINS');
		$_placement=new Project_Placement();
		if( $_placement
				->withOrder('d.user_id--dn')
				->withExpiryDomain( date('Y-m-d', time()+(60*60*24*30)) )
				->withUsersExplode()
				->withSendedDomain( Project_Placement::SENDED_MONTH )
				->getList( $arrMonth )->checkEmpty() ){
			Project_Placement_Notification::domainExpired( $arrMonth, $arrCurrentBunSettings );
			$this->_logger->info('Sended mail to user for Month expired');
		}
		if( $_placement
				->withOrder('d.user_id--dn')
				->withExpiryDomain( date('Y-m-d', time()+(60*60*24*7)) )
				->withUsersExplode()
				->withSendedDomain( Project_Placement::SENDED_WEEK )
				->getList( $arrWeek )->checkEmpty() ){
			Project_Placement_Notification::domainExpired( $arrWeek, $arrCurrentBunSettings );
			$this->_logger->info('Sended mail to user for Week expired');
		}
		if( $_placement
				->withOrder('d.user_id--dn')
				->withExpiryDomain( date('Y-m-d', time()+(60*60*24)) )
				->withUsersExplode()
				->withSendedDomain( Project_Placement::SENDED_DAY )
				->getList( $arrDay )->checkEmpty() ){
			Project_Placement_Notification::domainExpired( $arrDay, $arrCurrentBunSettings );
			$this->_logger->info('Sended mail to user for Day expired');
		}
		$this->_logger->info('End notification DOMAINS');
	}

	public function setHostingInfo( $_arr ){
		$this->_data=new Core_Data( $_arr );
		return $this;
	}

	public static function phoneFormat( $strPhone ){
		$strPhone = preg_replace("@[^0-9]@si",'',$strPhone);
	    $intCode = substr($strPhone, 0,3);
		$intNumber = substr($strPhone, 3,-1);
		$strPhone = "+".$intCode.".".$intNumber;
	    return $strPhone;
	}
	
	// reactivate local domain
	// ->setHostingInfo( new Project_Placement()->setEntered()->_data )->reactivate()
	public function reactivate(){
		if( !Core_Data_Errors::getInstance()->setData( $this->_data->setFilter( array('clear','trim') ) )->setValidators(array(
			'domain_http'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' )
		))->isValid() ){
			return Core_Data_Errors::getInstance()->setError('Can\'t find reactivate domain..');
		}
		if( $this->_data->filtered['expiry_domain']+25*24*60*60 <= time() ){
			return Core_Data_Errors::getInstance()->setError('End of reactivate domain time..');
		}
		// проверка стоимости
		if( Core_Payment_Purse::getAmount() < ($this->_domenPrice+$this->_hostingPrice) ){
			return Core_Data_Errors::getInstance()->setError('You have no credits for reactivate domain');
		}
		$_namecheap=new Project_Placement_Domen_Namecheap();
		if( !$_namecheap->setEntered( array('DomainName'=>$this->_data->filtered['domain_http'],'Years'=>$this->_years) )->reactivate( $response ) ){
			return Core_Data_Errors::getInstance()->setError('You have namecheap error for reactivate domain');
		}
		$_purse=new Core_Payment_Purse();
		$_purse
			->setAmount( $this->_domenPrice ) // Сумма кредитов за домен
			->setUserId( Core_Users::$info['id'] )
			->setType( Core_Payment_Purse::TYPE_INTERNAL )
			->setMessage('Domain was renewed successfully domain:'.$this->_data->filtered['domain_http'])
			->expenditure();
		return true;
	}
	
	// renew local domain
	// ->setHostingInfo( new Project_Placement()->setEntered()->_data )->renew()
	public function renew(){
		if( !Core_Data_Errors::getInstance()->setData( $this->_data->setFilter( array('clear','trim') ) )->setValidators(array(
			'domain_http'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' )
		))->isValid() ){
			return Core_Data_Errors::getInstance()->setError('Can\'t find renew domain');
		}
		if( $this->_data->filtered['expiry_domain'] < time() ){
			return Core_Data_Errors::getInstance()->setError('End of renew domain time');
		}
		// проверка стоимости
		if( Core_Payment_Purse::getAmount() < ($this->_domenPrice+$this->_hostingPrice) ){
			return Core_Data_Errors::getInstance()->setError('You have no credits for renew domain');
		}
		$_namecheap=new Project_Placement_Domen_Namecheap();
		if( !$_namecheap->setEntered( array('DomainName'=>$this->_data->filtered['domain_http'],'Years'=>$this->_years) )->renew( $response ) ){
			return Core_Data_Errors::getInstance()->setError('You have namecheap error for renew domain');
		}
		$_purse=new Core_Payment_Purse();
		$_purse
			->setAmount( $this->_domenPrice ) // Сумма кредитов за домен
			->setUserId( Core_Users::$info['id'] )
			->setType( Core_Payment_Purse::TYPE_INTERNAL )
			->setMessage('Domain was renewed successfully domain:'.$this->_data->filtered['domain_http'])
			->expenditure();
		return true;
	}
	
	// create local domain
	public function create(){
		if( !Core_Data_Errors::getInstance()->setData( $this->_data->setFilter( array('clear','trim') ) )->setValidators(array(
			'domain_http'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' )
		))->isValid() ){
			return false;
		}
		$_tmp=explode('.',$this->_data->filtered['domain_http']);
		$_arrLD['TLD']=$_tmp[1];
		$_arrLD['SLD']=$_tmp[0];
		$_users=new Project_Users_Management();
		$_users->withIds(Core_Users::$info['id'])->onlyOne()->getList( $_arrUser );
		if( Core_Payment_Purse::getAmount() < ($this->_domenPrice+$this->_hostingPrice) ){
			$_name=Core_Users::$info['nickname'];
			if( empty( $_name ) && ( !empty(Core_Users::$info['buyer_name']) || !empty(Core_Users::$info['buyer_surname']) ) ){
				$_name=(( !empty(Core_Users::$info['buyer_name']) )?Core_Users::$info['buyer_name'].' ':'').( !empty(Core_Users::$info['buyer_surname']) )?Core_Users::$info['buyer_surname'].' ':'';
			}
			if( empty( $_name ) ){
				$_name=Core_Users::$info['email'];
			}
			return Core_Data_Errors::getInstance()->setError('Dear '.$_name.'! You have no credits for buying a domain. You need '.($this->_domenPrice+$this->_hostingPrice).'. You have '.Core_Payment_Purse::getAmount().' credits in your account.');
		}
		$_namecheap=new Project_Placement_Domen_Namecheap();
		$_arrUser['buyer_phone']=self::phoneFormat($_arrUser['buyer_phone']);
		if( !$_namecheap->setEntered(array(
			'DomainName'=>$this->_data->filtered['domain_http'],
			'Years'=>$this->_years,
			'RegistrantFirstName'		=>$_arrUser['buyer_name'],
			'RegistrantLastName'		=>$_arrUser['buyer_surname'],
			'RegistrantAddress1'		=>$_arrUser['buyer_address'],
			'RegistrantCity'			=>$_arrUser['buyer_city'],
			'RegistrantStateProvince'	=>$_arrUser['buyer_province'],
			'RegistrantPostalCode'		=>$_arrUser['buyer_zip'],
			'RegistrantCountry'			=>$_arrUser['buyer_country'],
			'RegistrantPhone'			=>$_arrUser['buyer_phone'],
			'RegistrantEmailAddress'	=> 'imssarl@gmail.com', //$_arrUser['email'],
			'TechFirstName'				=>$_arrUser['buyer_name'],
			'TechLastName'				=>$_arrUser['buyer_surname'],
			'TechAddress1'				=>$_arrUser['buyer_address'],
			'TechCity'					=>$_arrUser['buyer_city'],
			'TechStateProvince'			=>$_arrUser['buyer_province'],
			'TechPostalCode'			=>$_arrUser['buyer_zip'],
			'TechCountry'				=>$_arrUser['buyer_country'],
			'TechPhone'					=>$_arrUser['buyer_phone'],
			'TechEmailAddress'			=> 'imssarl@gmail.com', //$_arrUser['email'],
			'AdminFirstName'			=>$_arrUser['buyer_name'],
			'AdminLastName'				=>$_arrUser['buyer_surname'],
			'AdminAddress1'				=>$_arrUser['buyer_address'],
			'AdminCity'					=>$_arrUser['buyer_city'],
			'AdminStateProvince'		=>$_arrUser['buyer_province'],
			'AdminPostalCode'			=>$_arrUser['buyer_zip'],
			'AdminCountry'				=>$_arrUser['buyer_country'],
			'AdminPhone'				=>$_arrUser['buyer_phone'],
			'AdminEmailAddress'			=> 'imssarl@gmail.com', //$_arrUser['email'],
			'AuxBillingFirstName'		=>$_arrUser['buyer_name'],
			'AuxBillingLastName'		=>$_arrUser['buyer_surname'],
			'AuxBillingAddress1'		=>$_arrUser['buyer_address'],
			'AuxBillingCity'			=>$_arrUser['buyer_city'],
			'AuxBillingStateProvince'	=>$_arrUser['buyer_province'],
			'AuxBillingPostalCode'		=>$_arrUser['buyer_zip'],
			'AuxBillingCountry'			=>$_arrUser['buyer_country'],
			'AuxBillingPhone'			=>$_arrUser['buyer_phone'],
			'AuxBillingEmailAddress'	=> 'imssarl@gmail.com', //$_arrUser['email'],
			'Nameservers'				=>Project_Placement_Domen_Availability::$_NS1.','.Project_Placement_Domen_Availability::$_NS2
		))->create( $response ) ){
			return false;
		}
		$_purse=new Core_Payment_Purse();
		$_purse	->setAmount( $this->_domenPrice ) // Сумма кредитов за домен
				->setUserId( Core_Users::$info['id'] )
				->setType( Core_Payment_Purse::TYPE_INTERNAL )
				->setMessage('Domain was purchased successfully domain:'.$this->_data->filtered['domain_http'])
				->expenditure();
		return true;
	}

	/**
	 * Check domain of availability or not.
	 * @param $_strDomain
	 * @return bool
	 */
	public function check( $strDomain, $intType ){
		if( empty( $strDomain ) ){
			return Core_Data_Errors::getInstance()->setError('Domain name is empty');
		}
		if( !Project_Placement::prepareDomainName( $strDomain, $intType ) ){
			return Core_Data_Errors::getInstance()->setError('Domain name is not correct');
		}
		$_placement=new Project_Placement();
		if( $_placement->withDomain( $strDomain )->onlyOne()->getList( $tmpRes )->checkEmpty() ){
			return Core_Data_Errors::getInstance()->setError('This domain has already exist');
		}
		if( $intType == Project_Placement::LOCAL_HOSTING ){ // домены пользователей не проверяем через namecheap 16.09.2019
			return false;
		}
		if( $intType == Project_Placement::LOCAL_HOSTING_SUBDOMEN ){
			return true;
		}
		if( $intType == Project_Placement::IFUNELS_HOSTING ){
			return false;
		}
		if( @$_SERVER['HTTP_HOST'] == 'cnm.local' ){
			return true;
		}
		$_checker=new Project_Placement_Domen_Availability();
		return $_checker->checkAvailability( $strDomain );

	}

	public function getErrors(){
		return Core_Data_Errors::getInstance()->getErrors();
	}

	private function setLogger() {
		$writer=new Zend_Log_Writer_Stream( 'php://output' );
		$writer->setFormatter( new Zend_Log_Formatter_Simple("%timestamp% %priorityName% (%priority%): %message%<br/>\r\n") );
		$this->_logger=new Zend_Log( $writer );
	}
}
?>