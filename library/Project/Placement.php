<?php

class Project_Placement extends Core_Data_Storage {

	/**
	 * Время на которое смещается оплата хостинга и домена (сек.)
	 * 60*60*24
	 * @var int
	 */
	public static $_time=86400;

	protected $_table='site_placement';

	protected $_fields=array( 'id','user_id', 'flg_passive', 'flg_type','flg_auto', 'flg_checked','flg_sended_hosting','flg_sended_domain', 'domain_http','db_host',
		'db_name','db_username','db_password','domain_ftp', 'username', 'password', 'expiry_hosting', 'expiry_domain','checked', 'added' );

	/**
	 * Типы размещения сайтов:
	 * 0 - хостинг удаленный;
	 * 1 - хостинг локальный, домен пользователя;
	 * 2 - хостинг локальный и домен наш;
	 * 3 - хостинг локальный поддомен;
	 */
	const REMOTE_HOSTING=0, LOCAL_HOSTING=1, LOCAL_HOSTING_DOMEN=2, IFUNELS_HOSTING=3, LOCAL_HOSTING_SUBDOMEN=4;

	/**
	 * Флаг показывающий за какой период еще не было отправлено письмо
	 * 0 - за месяц
	 * 1 - за неделю
	 * 2 - за день
	 */
	const SENDED_MONTH=0,SENDED_WEEK=1,SENDED_DAY=2, DELETE_MONTH=3, DELETE_WEEK=4, DELETE_DAY=5;

	/**
	 * Ограничение количества сайтов для локального домена.
	 */
	const MAX_SITES_FOR_DOMAIN=20;

	private $_withType=false;
	private $_onlyChecked=false;
	private $_onlyNoChecked=false;
	private $_withOptgroup=false;
	private $_limit=false;
	private $_onlyExpiredHosting=false;
	private $_onlyNotExpiredHosting=false;
	private $_onlyLimitedHosting=false;
	private $_withoutLoss=false;
	private $_onlyExpiredDomain=false;
	private $_onlyNotExpiredDomain=false;
	private $_onlyNotVerified=false;
	private $_withDomain=false;
	private $_withExpiryDomain=false;
	private $_withExpiryHosting=false;
	private $_withUsersExplode=false;
	private $_withSendedDomain=false;
	private $_withSendedHosting=false;
	private $_sendMessageToUser=true;
	private $_onlyAuto=false;
	private $_withPeriod=false;
	private $_likeDomainName=false;
	private $_withUserId=false;
	private $_onlyCountSites=false;
	private $_place=array();
	private static $_valideTld=array( 'com', 'info', 'org', 'net', 'biz', 'us', 'online' );

	/**
	 * Prepare domain name.
	 * @static
	 * @param $strName
	 * @return bool
	 */
	public static function prepareDomainName( &$strName, $_type ){
		$strName=trim($strName);
		if( stripos($strName,'http://')===false&&stripos($strName,'https://')===false ){
			$strName='http://'.$strName;
		}
		if( stripos($strName,'www.')!==false ){
			$strName=str_replace('www.','',$strName);
		}
		$_tmp=parse_url( $strName );
		$strName=$_tmp['host'];
		preg_match('/^(?<name>[\da-z\.-]+)\.(?<tld>[a-z\.]{2,24})$/si',$strName,$_matches);
		if( empty($_matches) ){
			return false;
		}
		if($_type==self::LOCAL_HOSTING_DOMEN&&!in_array($_matches['tld'],self::$_valideTld)){
			return false;
		}
		$_validate=new Zend_Validate_Hostname();
		return $_validate->isValid( $strName );
	}
	
	public function changeType(){
		if( empty($this->_withIds) ){
			return false;
		}
		$_intId=$this->_withIds;
		$this->onlyOwner()->onlyOne()->getList( $arrRes );
		unset($arrRes['user_id']);
		unset($arrRes['id']);
		$arrRes['flg_type']=self::LOCAL_HOSTING;
		$arrRes['flg_checked']=0;
		$arrRes['instruction_send']=true;
		$arrRes['domain_http']=$arrRes['domain_ftp'];
		if( $this->setEntered( $arrRes )->set() ){
			return $this->withIds( $_intId )->del();
		}
		$this->init();
		return false;
	}

	/**
	 * Get domain name by id
	 * @param $strDomain
	 * @return Project_Placement
	 */
	public function getDomen( &$strDomain ){
		if( !empty($this->_withIds) ){
			$this->onlyOne()->getList( $this->_place );
			$strDomain=$this->_place['domain_http'];
		}
		return $this;
	}

	/**
	 * Check hosting remote or local
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function isRemote(){
		if( empty($this->_place)&&empty($this->_withIds) ){
			throw new Project_Placement_Exception(Core_Errors::DEV.'|Can\'t find placement');
		}
		if( empty($this->_place) ){
			$this->onlyOne()->getList( $this->_place );
		}
		return  $this->_place['flg_type']==self::REMOTE_HOSTING;
	}

	public function setToExteral(){
		if( empty($this->_withIds) ){
			return false;
		}
		$this->getList( $_arrDomains );
		foreach( $_arrDomains as $_domain ){
			$_domain['flg_type']=self::LOCAL_HOSTING;
			$_domain['flg_checked']=0;
			$_domain['expiry_domain']=0;
			$_domain['flg_sended_domain']=0;
			$_domain['instruction_send']=true;
			$this->setEntered( $_domain )->set();
			$this->init();
		}
	}

	public function del(){
		if( empty($this->_withIds) ){
			return false;
		}
		// delete content projects for domain
		$_arrNcsbSiteIds=Core_Sql::getField('SELECT id FROM es_ncsb WHERE placement_id IN ('.Core_Sql::fixInjection( $this->_withIds ).')' );
		$_arrNvsbSiteIds=Core_Sql::getField('SELECT id FROM es_nvsb WHERE placement_id IN ('.Core_Sql::fixInjection( $this->_withIds ).')' );
		$_arrBFSiteIds=Core_Sql::getField('SELECT id FROM bf_blogs WHERE placement_id IN ('.Core_Sql::fixInjection( $this->_withIds ).')' );
		if(!empty($_arrNcsbSiteIds))
		$_arrProjectsNcsbIds=Core_Sql::getField('SELECT a.project_id FROM pub_autosites a WHERE a.site_id IN ('.Core_Sql::fixInjection($_arrNcsbSiteIds).') AND a.flg_type='.Project_Sites::NCSB );
		if(!empty($_arrProjectsNvsbIds))
		$_arrProjectsNvsbIds=Core_Sql::getField('SELECT a.project_id FROM pub_autosites a WHERE a.site_id IN ('.Core_Sql::fixInjection($_arrNvsbSiteIds).') AND a.flg_type='.Project_Sites::NVSB );
		if(!empty($_arrProjectsBFIds))
		$_arrProjectsBFIds=Core_Sql::getField('SELECT a.project_id FROM pub_autosites a WHERE a.site_id IN ('.Core_Sql::fixInjection($_arrBFSiteIds).') AND a.flg_type='.Project_Sites::BF );
		if(!empty($_arrProjectsNcsbIds)){
			Core_Sql::setExec('DELETE FROM pub_autosites WHERE project_id IN ('.Core_Sql::fixInjection($_arrProjectsNcsbIds).')');
			Core_Sql::setExec('DELETE FROM pub_cache WHERE project_id IN ('.Core_Sql::fixInjection($_arrProjectsNcsbIds).')');
			Core_Sql::setExec('DELETE FROM pub_schedule WHERE project_id IN ('.Core_Sql::fixInjection($_arrProjectsNcsbIds).')');
			Core_Sql::setExec('DELETE FROM pub_types WHERE project_id IN ('.Core_Sql::fixInjection($_arrProjectsNcsbIds).')');
			Core_Sql::setExec('DELETE FROM pub_project WHERE id IN ('.Core_Sql::fixInjection($_arrProjectsNcsbIds).')');
		}
		if(!empty($_arrProjectsNvsbIds)){
			Core_Sql::setExec('DELETE FROM pub_autosites WHERE project_id IN ('.Core_Sql::fixInjection($_arrProjectsNvsbIds).')');
			Core_Sql::setExec('DELETE FROM pub_cache WHERE project_id IN ('.Core_Sql::fixInjection($_arrProjectsNvsbIds).')');
			Core_Sql::setExec('DELETE FROM pub_schedule WHERE project_id IN ('.Core_Sql::fixInjection($_arrProjectsNvsbIds).')');
			Core_Sql::setExec('DELETE FROM pub_types WHERE project_id IN ('.Core_Sql::fixInjection($_arrProjectsNvsbIds).')');
			Core_Sql::setExec('DELETE FROM pub_project WHERE id IN ('.Core_Sql::fixInjection($_arrProjectsNvsbIds).')');
		}
		if(!empty($_arrProjectsBFIds)){
			Core_Sql::setExec('DELETE FROM pub_autosites WHERE project_id IN ('.Core_Sql::fixInjection($_arrProjectsBFIds).')');
			Core_Sql::setExec('DELETE FROM pub_cache WHERE project_id IN ('.Core_Sql::fixInjection($_arrProjectsBFIds).')');
			Core_Sql::setExec('DELETE FROM pub_schedule WHERE project_id IN ('.Core_Sql::fixInjection($_arrProjectsBFIds).')');
			Core_Sql::setExec('DELETE FROM pub_types WHERE project_id IN ('.Core_Sql::fixInjection($_arrProjectsBFIds).')');
			Core_Sql::setExec('DELETE FROM pub_project WHERE id IN ('.Core_Sql::fixInjection($_arrProjectsBFIds).')');
		}
		// delete sites for hosting
		Core_Sql::setExec('DELETE FROM es_ncsb WHERE placement_id IN ('.Core_Sql::fixInjection( $this->_withIds ).')' );
		Core_Sql::setExec('DELETE FROM es_nvsb WHERE placement_id IN ('.Core_Sql::fixInjection( $this->_withIds ).')' );
		Core_Sql::setExec('DELETE FROM bf_blogs WHERE placement_id IN ('.Core_Sql::fixInjection( $this->_withIds ).')' );
		$this->onlyOne()->getList( $arrRes )->withIds( $arrRes['id'] );
		$_bool1=true;
		switch ( $arrRes['flg_type'] ){
			case self::LOCAL_HOSTING_DOMEN :
			case self::LOCAL_HOSTING :
			case self::IFUNELS_HOSTING :
					$_hosting=new Project_Placement_Hosting();
					$_bool1=$_hosting->setHostingInfo( $arrRes )->delete();
				break;
			case self::REMOTE_HOSTING :
			default :
				break;
		}
		return $_bool1&&parent::del();
	}

	public function withType( $_mixType=false ){
		if( $_mixType!==false ){
			$this->_withType=$_mixType;
		}
		return $this;
	}

	public function onlyChecked(){
		$this->_onlyChecked=true;
		return $this;
	}

	public function onlyNoChecked(){
		$this->_onlyNoChecked=true;
		return $this;
	}

	public function withOptgroup(){
		$this->_withOptgroup=true;
		return $this;
	}

	public function setLimit( $_int ){
		$this->_limit=$_int;
		return $this;
	}

	public function onlyExpiredHosting(){
		$this->_onlyExpiredHosting=true;
		return $this;
	}

	public function onlyNotExpiredHosting(){
		$this->_onlyNotExpiredHosting=true;
		return $this;
	}

	public function onlyLimitedHosting(){
		$this->_onlyLimitedHosting=true;
		return $this;
	}

	public function onlyExpiredDomain(){
		$this->_onlyExpiredDomain=true;
		return $this;
	}

	public function withoutLoss(){
		$this->_withoutLoss=true;
		return $this;
	}

	public function onlyNotExpiredDomain(){
		$this->_onlyNotExpiredDomain=true;
		return $this;
	}

	public function onlyNotVerified(){
		$this->_onlyNotVerified=true;
		return $this;
	}

	public function withDomain( $_str ){
		if( !empty($_str)){
			$this->_withDomain=$_str;
		}
		return $this;
	}

	public function withExpiryDomain( $_str ){
		if( !empty($_str)){
			$this->_withExpiryDomain=$_str;
		}
		return $this;
	}

	public function withExpiryHosting( $_str ){
		if( !empty($_str)){
			$this->_withExpiryHosting=$_str;
		}
		return $this;
	}

	public function withUsersExplode(){
		$this->_withUsersExplode=true;
		return $this;
	}

	public function withSendedDomain( $_int ){
		$this->_withSendedDomain=$_int;
		return $this;
	}

	public function withSendedHosting( $_int ){
		$this->_withSendedHosting=$_int;
		return $this;
	}

	public function withPeriod( $_int ){
		$this->_withPeriod=$_int;
		return $this;
	}

	public function onlyAuto(){
		$this->_onlyAuto=true;
		return $this;
	}

	public function onlyCountSites(){
		$this->_onlyCountSites=true;
		return $this;
	}

	public function forCreditsRewards( $time ){
		if(!empty($time)){
			$this->_forCreditsRewards=$time;
		}
		return $this;
	}

	public function withUserId( $_str ){
		$this->_withUserId=$_str;
		return $this;
	}

	public function likeDomainName( $_str ){
		$this->_likeDomainName=$_str;
		return $this;
	}

	public function notSendMessageToUser(){
		$this->_sendMessageToUser=false;
		return $this;
	}

	public function getList( &$arrRes ){
		$_withOptgroup=$this->_withOptgroup;
		$_withUsersExplode=$this->_withUsersExplode;
		parent::getList( $arrRes );
		if( isset( $arrRes['domain_http'] ) ){
			$arrRes['domain_http']=strtolower( $arrRes['domain_http'] );
		}else{
			foreach( $arrRes as &$_domain ){
				$_domain['domain_http']=strtolower( $_domain['domain_http'] );
			}
		}
		if( $_withOptgroup ){
			$this->domainWithGroup( $arrRes );
		}
		if( $_withUsersExplode ){
			$this->explodeByUsers( $arrRes );
		}

		return $this;
	}

	protected function assemblyQuery(){
		parent::assemblyQuery();
		if( $this->_withType!==false ){
			$this->_crawler->set_where('d.flg_type IN('.Core_Sql::fixInjection($this->_withType).')');
		}
		if( $this->_onlyChecked ){
			$this->_crawler->set_where('d.flg_checked='.Project_Placement_Domen_Availability::AVAILABLE );
		}
		if( $this->_onlyNoChecked ){
			$this->_crawler->set_where('d.flg_checked='.Project_Placement_Domen_Availability::NOT_AVAILABLE );
		}
		if( $this->_limit ){
			$this->_crawler->set_limit( $this->_limit );
		}
		if( $this->_withoutLoss!==false ){
			$this->_crawler->set_where('d.expiry_domain!=0 AND d.expiry_domain < '.(time()+self::$_time).' AND d.expiry_domain > '.(time()-25*24*60*60+15*60)  ); 
		}
		if( $this->_withPeriod!==false ){
			$this->_crawler->set_where('d.expiry_domain!=0 AND d.expiry_domain < '.( time()+(int)$this->_withPeriod ) );
		}
		if( $this->_onlyExpiredDomain ){
			$this->_crawler->set_where('d.expiry_domain!=0 AND d.expiry_domain < '.(time()-self::$_time) );
		}
		if( $this->_onlyNotExpiredDomain ){
			$this->_crawler->set_where('d.expiry_domain!=0 AND d.expiry_domain > '.(time()-self::$_time) );
		}
		if( $this->_onlyExpiredHosting ){
			$this->_crawler->set_where('d.expiry_hosting!=0 AND d.expiry_hosting < '.(time()-self::$_time) );
		}
		if( $this->_onlyNotExpiredHosting ){
			$this->_crawler->set_where('d.expiry_hosting!=0 AND d.expiry_hosting > '.(time()-self::$_time) );
		}
		if( $this->_onlyLimitedHosting ){
			$this->_crawler->set_where('d.expiry_hosting=0 AND d.flg_type='.self::LOCAL_HOSTING );
		}
		if( $this->_onlyNotVerified ){
			$this->_crawler->set_where('d.flg_checked='.Project_Placement_Domen_Availability::NOT_VERIEFIED );
		}
		if( $this->_withDomain ){
			$this->_crawler->set_where('d.domain_http='.Core_Sql::fixInjection( $this->_withDomain) );
		}
		if( $this->_withExpiryDomain ){
			$this->_crawler->set_where("d.expiry_domain!=0 AND DATE_FORMAT(FROM_UNIXTIME(d.expiry_domain),'%Y-%m-%d')=".Core_Sql::fixInjection($this->_withExpiryDomain) );
		}
		if( $this->_withExpiryHosting ){
			$this->_crawler->set_where("d.expiry_hosting!=0 AND DATE_FORMAT(FROM_UNIXTIME(d.expiry_hosting),'%Y-%m-%d')=".Core_Sql::fixInjection($this->_withExpiryHosting) );
		}
		if( $this->_withSendedDomain!==false ){
			$this->_crawler->set_where('d.flg_sended_domain='.$this->_withSendedDomain );
		}
		if( $this->_withSendedHosting!==false ){
			$this->_crawler->set_where('d.flg_sended_hosting='.$this->_withSendedHosting );
		}
		if( $this->_onlyAuto!==false ){
			$this->_crawler->set_where('d.flg_auto='.$this->_onlyAuto );
		}
		if( $this->_withOptgroup ){
			$this->_crawler->set_select('((SELECT COUNT(s1.id) FROM es_ncsb as s1 WHERE s1.placement_id=d.id AND d.flg_type!='. self::REMOTE_HOSTING .')
			+(SELECT COUNT(s2.id) FROM es_nvsb as s2 WHERE s2.placement_id=d.id AND d.flg_type!='. self::REMOTE_HOSTING .')
			+(SELECT COUNT(s3.id) FROM bf_blogs as s3 WHERE s3.placement_id=d.id AND d.flg_type!='. self::REMOTE_HOSTING .') ) as count_sites');
		}
		if( $this->_onlyCountSites ){
			$this->onlyCell();
			$this->_crawler->clean_select();
			$this->_crawler->set_select('((SELECT COUNT(s1.id) FROM es_ncsb as s1 WHERE s1.placement_id=d.id )
			+(SELECT COUNT(s2.id) FROM es_nvsb as s2 WHERE s2.placement_id=d.id )
			+(SELECT COUNT(s3.id) FROM bf_blogs as s3 WHERE s3.placement_id=d.id ) ) as count_sites');
		}
		if( $this->_forCreditsRewards ){
			$this->onlyCell();
			$this->_crawler->clean_select();
			$this->_crawler->set_select('((SELECT COUNT(s1.id) FROM es_ncsb as s1 WHERE s1.placement_id=d.id AND added>='. Core_Sql::fixInjection($this->_forCreditsRewards) .' )
			+(SELECT COUNT(s2.id) FROM es_nvsb as s2 WHERE s2.placement_id=d.id  AND added>='. Core_Sql::fixInjection($this->_forCreditsRewards) .' )
			+(SELECT COUNT(s3.id) FROM bf_blogs as s3 WHERE s3.placement_id=d.id  AND added>='. Core_Sql::fixInjection($this->_forCreditsRewards) .' ) ) as count_sites');
		}
		if( $this->_likeDomainName ){
			$this->_crawler->set_where("d.domain_http LIKE ".Core_Sql::fixInjection('%'.$this->_likeDomainName.'%'));
		}
		if( $this->_withUserId ){
			$this->_crawler->set_where("d.user_id IN (".Core_Sql::fixInjection($this->_withUserId).")");
		}
	}

	public function getErrors( &$arrErrors ){
		$arrErrors=Core_Data_Errors::getInstance()->getErrors();
		$this->init();
		return $this;
	}

	public static function setCheckedStatus( $_intId, $_intStatus ){
		return Core_Sql::setExec('UPDATE site_placement SET checked='.time().', flg_checked='.$_intStatus.' WHERE id='.$_intId );
	}

	protected function init(){
		parent::init();
		$this->_withOptgroup=false;
		$this->_withType=false;
		$this->_onlyChecked=false;
		$this->_onlyNoChecked=false;
		$this->_limit=false;
		$this->_withoutLoss=false;
		$this->_onlyExpiredDomain=false;
		$this->_onlyExpiredHosting=false;
		$this->_withDomain=false;
		$this->_onlyNotVerified=false;
		$this->_onlyNotExpiredDomain=false;
		$this->_onlyNotExpiredHosting=false;
		$this->_withExpiryDomain=false;
		$this->_withExpiryHosting=false;
		$this->_withUsersExplode=false;
		$this->_withSendedDomain=false;
		$this->_withSendedHosting=false;
		$this->_onlyAuto=false;
		$this->_withPeriod=false;
		$this->_onlyCountSites=false;
		$this->_sendMessageToUser=true;
		$this->_onlyRenew=false;
		$this->_onlyReactivate=false;
		$this->_likeDomainName=false;
		$this->_withUserId=false;
	}

	private $_onlyRenew=false;
	
	public function renew(){
		if( empty($this->_withIds) ){
			return false;
		}
		$_intId=$this->_withIds;
		$_getAdmin=false;
		$this->onlyOne()->getList( $arrRes );
		if( $arrRes['user_id'] != Core_Users::$info['id'] ){
			if( in_array( 'Super Admin', Core_Users::$info['groups'] ) ){ // only 'Super Admin'
				$_getAdmin=Core_Users::$info['id'];
				Zend_Registry::get( 'objUser' )->setById( $arrRes['user_id'] );
			}else{
				return Core_Data_Errors::getInstance()->setError('You do not have sufficient permissions to access this page.');
			}
		}
		$this->_onlyRenew=true;
		if( !$this->setEntered( $arrRes )->set() ){
			$this->setEntered( array() );
			$this->init();
			if( $_getAdmin !== false ){
				Zend_Registry::get( 'objUser' )->setById( $_getAdmin );
			}
			return false;
		}
		if( $_getAdmin !== false ){
			Zend_Registry::get( 'objUser' )->setById( $_getAdmin );
		}
		$this->init();
		return true;
	}

	private $_onlyReactivate=false;
	
	public function reactivate(){
		if( empty($this->_withIds) ){
			return false;
		}
		$_intId=$this->_withIds;
		$_getAdmin=false;
		$this->onlyOne()->getList( $arrRes );
		if( $arrRes['user_id'] != Core_Users::$info['id'] ){
			if( in_array( 'Super Admin', Core_Users::$info['groups'] ) ){ // only 'Super Admin'
				$_getAdmin=Core_Users::$info['id'];
				Zend_Registry::get( 'objUser' )->setById( $arrRes['user_id'] );
			}else{
				return Core_Data_Errors::getInstance()->setError('You do not have sufficient permissions to access this page.');
			}
		}
		$this->_onlyReactivate=true;
		if( !$this->setEntered( $arrRes )->set() ){
			$this->setEntered( array() );
			$this->init();
			if( $_getAdmin !== false ){
				Zend_Registry::get( 'objUser' )->setById( $_getAdmin );
			}
			return false;
		}
		if( $_getAdmin !== false ){
			Zend_Registry::get( 'objUser' )->setById( $_getAdmin );
		}
		$this->init();
		return true;
	}

	protected function beforeSet(){
		$this->_data->setFilter( array( 'trim', 'clear' ) );
		if(!empty($this->_data->filtered['flg_checked'])){
			$this->_data->setElement('checked',time());
		}
		if( $this->_data->filtered['flg_type'] == self::IFUNELS_HOSTING ){
			$this->_data->setElement('domain_http',$this->_data->filtered['domain_http'].'.ifunnels.com');
		}
		if( $this->_data->filtered['flg_type'] == self::LOCAL_HOSTING_SUBDOMEN ){
			$_domainObj=new Project_Placement();
			$_domainObj->onlyOwner()->withIds( $this->_data->filtered['parent_domain_id'] )->onlyOne()->getList( $_arrData );
			$this->_data->setElement('domain_http',$this->_data->filtered['domain_http'].'.'.$_arrData['domain_http']);
		}
		if( ( $this->_onlyRenew || $this->_onlyReactivate ) && $this->_data->filtered['flg_type'] == self::LOCAL_HOSTING_DOMEN ){
			$_expiry=0;
			$_return=$_namecheap->setEntered( array('DomainName'=>$this->_data->filtered['domain_http']) )->getInfo( $_domainInfo );
			if( $_return !== false && strtotime( $_domainInfo['DomainDetails']['ExpiredDate'] )-$time > 30*24*60*60 && $_domainInfo['Status'] == 'Ok' ){
				$_expiry=strtotime( $_domainInfo['DomainDetails']['ExpiredDate'] );
			}else{
				$_domen=new Project_Placement_Domen();
				$_domen->setHostingInfo( $this->_data->filtered );
				if( $this->_onlyRenew && !$_domen->renew() ){
					return Core_Data_Errors::getInstance()->setError('This renew is not correct');
				}
				if( $this->_onlyReactivate && !$_domen->reactivate() ){
					return Core_Data_Errors::getInstance()->setError('This reactivate is not correct');
				}
				$_expiry=time()+$_domen->expiry;
			}
			$this->_data->setElement('expiry_domain',$_expiry);
		}
		if( !empty($this->_data->filtered['id']) ){
			return true;
		}
		if( !empty($this->_data->filtered['domain_http'])&&!self::prepareDomainName($this->_data->filtered['domain_http'],$this->_data->filtered['flg_type']) ){
			return Core_Data_Errors::getInstance()->setError('Domain name is not correct');
		}
		$this->_data->setElements(array(
			'domain_http'=>$this->_data->filtered['domain_http'],
			'instruction_send'=>true,
		));
		$_sendMessageToUser=$this->_sendMessageToUser;
		if( ($this->_data->filtered['flg_type']==self::LOCAL_HOSTING_DOMEN||$this->_data->filtered['flg_type']==self::LOCAL_HOSTING)&&
			$this->withDomain( $this->_data->filtered['domain_http'] )->onlyOne()->getList($tmpRes)->checkEmpty() ){
			return Core_Data_Errors::getInstance()->setError('This domain has already exist');
		}
		if( !$_sendMessageToUser ){
			$this->notSendMessageToUser();
		}
		$_bool1=true;
		$_bool2=true;
		switch ( $this->_data->filtered['flg_type'] ){
			case self::IFUNELS_HOSTING :
				$_hosting=new Project_Placement_Hosting();
				$_bool1=$_hosting->setHostingInfo( $this->_data->filtered )->create();
				Project_Users_Management::setDomainOrdered( Core_Users::$info['id'] );
				$this->_data->setElement('flg_checked',true);
				break;
			case self::LOCAL_HOSTING_SUBDOMEN :
				$_hosting=new Project_Placement_Hosting();
				$_bool1=$_hosting->setHostingInfo( $this->_data->filtered )->create();
				Project_Users_Management::setDomainOrdered( Core_Users::$info['id'] );
				$this->_data->setElement('flg_checked',true);
				break;
			case self::LOCAL_HOSTING_DOMEN :
				$_domen=new Project_Placement_Domen();
				if( !$_domen->setHostingInfo( $this->_data->filtered )->create() ){
					return Core_Data_Errors::getInstance()->setError('Can\'t create domain');
				}
				$this->_data->setElement('expiry_domain',(time()+$_domen->expiry));
				if( $_bool2 ){
					Project_Placement_Notification::registredDomain( $this->_data->filtered );
					Project_Users_Management::setDomainOrdered( Core_Users::$info['id'] );
				}
			case self::LOCAL_HOSTING :
				if( !$_bool2 ){
					return false;
				}
				$_hosting=new Project_Placement_Hosting();
				$_bool1=$_hosting->setHostingInfo( $this->_data->filtered )->create();
				if( $_bool1&&$this->_data->filtered['flg_type']!=self::LOCAL_HOSTING_DOMEN ){
					Project_Users_Management::setDomainParked( Core_Users::$info['id'] );
				}
				$this->onlyLimitedHosting()->withUserId( Core_Users::$info['id'] )->onlyCount()->getList( $_hostingLimit );
				$_flgPaymentHostings=$_hostingLimit>=Core_Users::$info['hosting_limit'] || Core_Users::$info['hosting_limit']==0;
				if( $_flgPaymentHostings ){
					$this->_data->setElement('expiry_hosting',(time()+$_hosting->expiry));
				}else{
					$this->_data->setElement('expiry_hosting',0);
				}
				break;
			case self::REMOTE_HOSTING :
				$_ftp=new Core_Media_Ftp();
				if( !$_ftp->setPassw( $this->_data->filtered['password'] )
						->setUser( $this->_data->filtered['username'] )
						->setHost( $this->_data->filtered['domain_ftp'] )
						->makeConnect() ){
						return false;
					}
				$this->_data->setElement('flg_checked',true);
				break;
		}
		return $_bool1&&$_bool2;
	}

	protected function afterSet(){
		if( empty($this->_data->filtered['instruction_send']) ){
			return true;
		}
		if( $this->_data->filtered['flg_type']== self::LOCAL_HOSTING&&$this->_sendMessageToUser ){
			Project_Placement_Notification::instructionDNS( $this->_data->filtered );
		}
		$this->init();
		return true;
	}

	private function domainWithGroup( &$arrRes ){
		foreach( $arrRes as $_item ){
			if( self::REMOTE_HOSTING==$_item['flg_type'] ){
				$_arrTmp['Domains you host externally'][$_item['id']]=$_item['domain_ftp'];
			} elseif( $_item['count_sites']<=self::MAX_SITES_FOR_DOMAIN ) {
				$_arrTmp['Domains hosted with us'][$_item['id']]=$_item['domain_http'];
			}
		}
		$arrRes=$_arrTmp;
	}

	private function explodeByUsers( &$arrRes ){
		foreach( $arrRes as $_item ){
			$_tmpArr[$_item['user_id']][]=$_item;
		}
		$arrRes=$_tmpArr;
		unset($_tmpArr);
	}

}
?>