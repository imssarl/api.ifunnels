<?php

/**
 * Checker
 *
 * @category Project
 * @package Project_Placement_Domen
 * @copyright Copyright (c) 2009-2012, web2innovation
 */
class Project_Placement_Domen_Availability {

	// Whois settings
	public static $_NS1='darl.ns.cloudflare.com';
	public static $_NS2='emily.ns.cloudflare.com';

	public static $logger=false;

	/**
	 * Статусы проверки доменов поле flg_checked
	 * 0 - не доступен
	 * 1 - доступен
	 * 2 - недоступен более чем TIME_CHECKING секунд
	 */
	const NOT_AVAILABLE=0,AVAILABLE=1,NOT_VERIEFIED=2,IMPORTED=3;

	/**
	 * Максимальное количество времени на проверку одного домена
	 */
	const TIME_CHECKING=259200;

	public function __construct(){
		$this->setLogger();
	}
	/**
	 * Start process.
	 * @static
	 */
	public static function run(){
		$_placement=new Project_Placement();
		$_self=new Project_Placement_Domen_Availability();
		self::$logger->info('Start checking domains');
		if( $_placement
				->setLimit(10)
				->withOrder('d.checked--dn')
				->onlyNoChecked()
				->withType( array(Project_Placement::LOCAL_HOSTING,Project_Placement::LOCAL_HOSTING_DOMEN) )
				->getList( $arrDomain )
				->checkEmpty() ){
			$_self->checkDNS( $arrDomain );
		} else {
			self::$logger->err('can\'t find domain for checking');
		}
		self::$logger->info('End checking domains');
	}

	/**
	 * Check status for remote domain. Checking DNS use whois
	 * @param $_arrDomains - domains list
	 */
	public function checkDNS( $_arrDomains ){
		foreach( $_arrDomains as $_item ){
			self::$logger->info('check domain: '.$_item['domain_http']);
			if( $_item['flg_type']==Project_Placement::LOCAL_HOSTING ){
				Project_Placement::setCheckedStatus( $_item['id'],self::IMPORTED );
				continue;
			}
			if( $_item['added']<(time()-self::TIME_CHECKING) ){
				Project_Placement::setCheckedStatus( $_item['id'],self::NOT_VERIEFIED );
				continue;
			}
			$_strResult=shell_exec('whois '.escapeshellarg(escapeshellcmd($_item['domain_http'])));
			if( stripos($_strResult,self::$_NS1)||stripos($_strResult,self::$_NS2) ){
				Project_Placement::setCheckedStatus( $_item['id'],self::AVAILABLE );
				self::$logger->info('is available..');
			} else {
				self::$logger->err('not available..');
			}
		}
	}

	/**
	 * Check domain availability, use whois
	 * @param $_strDomain
	 * @return bool
	 */
	public function checkAvailability( $_strDomain ){
		if( empty($_strDomain) ){
			return false;
		}
		$_namecheap=new Project_Placement_Domen_Namecheap();
		$_namecheap->setEntered(array('DomainList'=>$_strDomain))->check( $arrRes );
		Core_Sql::reconnect();
		if(!is_array($arrRes)){
			return false;
		}
		return $arrRes[$_strDomain]=='true';
	}

	public function checkWhoisAvailability( $_strDomain ){
		if( empty($_strDomain) ){
			return false;
		}
		$_strResult=shell_exec('whois '.escapeshellarg(escapeshellcmd($_strDomain)));
		if( stripos($_strResult,'Name Server')) {
			return false;
		}
		if( stripos($_strResult,'Not found')!==false||
			stripos($_strResult,'NOT FOUND')!==false||
			stripos($_strResult,'No match for')!==false||
			stripos($_strResult,'Status:	AVAILABLE')!==false||
			stripos($_strResult,'No entries found')!==false
		){
			return true;
		}
		return false;
	}

	private function setLogger() {
		$writer=new Zend_Log_Writer_Stream( 'php://output' );
		$writer->setFormatter( new Zend_Log_Formatter_Simple("%timestamp% %priorityName% (%priority%): %message%<br/>\r\n") );
		self::$logger=new Zend_Log( $writer );
	}
}
?>