<?php
class Project_Placement_Hosting implements Core_Payment_Buns_Interface {

	private $_info=array();
	public $expiry=false;
	private $_hostingPrice=false;
	private $_logger2=false;
	private $_configFile='domainname.txt';
	private $_runFile='vhosts.sh';
	private $_scriptsDir=false;
	
	private $_withLogger=true;

	public function __construct(){
		$_buns=new Core_Payment_Buns();
		if( !$_buns->onlyOne()->withSysName('Project_Placement_Hosting')->getList( $_arrRes ) ){
			throw new Exception('Can\'t find Buns with name Project_Placement_Hosting');
		}
		$this->expiry=Core_Payment_Buns::getLength( $_arrRes );
		$this->_hostingPrice=$_arrRes['credits'];
		$this->_scriptsDir=DIRECTORY_SEPARATOR.'data'. DIRECTORY_SEPARATOR .'scripts'. DIRECTORY_SEPARATOR;
		/*---------------------------------------------*/
		if( $this->_withLogger ){
			$_writer=new Zend_Log_Writer_Stream( Zend_Registry::get('config')->path->absolute->logfiles.'Project_Placement_Hosting.log' );
			$_writer->setFormatter( new Zend_Log_Formatter_Simple("%timestamp% %priorityName% (%priority%): %message%\r\n") );
			$this->_logger2=new Zend_Log( $_writer );
			$this->_start=microtime(true);
		}
		/*---------------------------------------------*/
	}
	
	/**
	 * Удаляет хостинг не оплаченный более месяца
	 * и не прошедший проверку на DNS
	 */
	public function check2delete(){
		$this->setLogger();
		$this->_logger->info('Start HOSTING checkDelete');
		$this->notificationDelete();
		$_placement=new Project_Placement();
		if( !$_placement
			->withType( array(Project_Placement::LOCAL_HOSTING,Project_Placement::LOCAL_HOSTING_DOMEN) )
			->onlyExpiredHosting()
			->getList( $arrRes )
			->checkEmpty()
		){
			$this->_logger->err('can\'t find hosting for delete..');
			return;
		}
		$_strDir='Project_Delete_Host@updateFiles';
		if ( !Zend_Registry::get( 'objUser' )->prepareTmpDir( $_strDir ) ) {
			return Core_Data_Errors::getInstance()->setError( 'Can\'t create dir '.$_strDir );
		}
		foreach($arrRes as $_item ){
			if($_item['expiry_hosting']>time()-(60*60*24*25)){
				continue;
			}
		//	$this->_logger->info('Delete hosting: '.$_item['domain_http']);
		//	$_placement->withIds( $_item['id'] )->del();
		}
	}

	/**
	 * @param array $arrCurrentBunSettings
	 * @return mixed
	 */
	public function checkExpired( $arrCurrentBunSettings=array() ){
		$this->setLogger();
		$this->_logger->info('Start HOSTING checkExpired');
		$this->notification( $arrCurrentBunSettings );
		$_purse=new Core_Payment_Purse();
		$_placement=new Project_Placement();
		$this->_logger->info('Start auto renew');
		if( !$_placement->withType( array(Project_Placement::LOCAL_HOSTING,Project_Placement::LOCAL_HOSTING_DOMEN) )
				->onlyAuto()->onlyExpiredHosting()->getList( $arrRes )->checkEmpty() ){
			$this->_logger->err('can\'t find expired hosting..');
			return;
		}
		foreach( $arrRes as $_item ){
			Core_Users::getInstance()->setById( $_item['user_id'] );
			usleep(10);
			$this->_logger->info('Start renew for '.$_item['domain_http']);
			if( Core_Payment_Purse::getAmount() < $arrCurrentBunSettings['credits'] ){
				$this->_logger->err('user not have credits for renew hosting..');
				Core_Users::logout();
				continue;
			}
			$this->_logger->info('renew hosting for '.$_item['domain_http']);
			$_item['expiry_hosting']=time()+Core_Payment_Buns::getLength( $arrCurrentBunSettings );
			$_placement->setEntered( $_item )->set();
			$_purse
				->setAmount( $arrCurrentBunSettings['credits'] ) // Сумма кредитов за домен
				->setUserId( $_item['user_id'] )
				->setType( Core_Payment_Purse::TYPE_INTERNAL )
				->setMessage( $arrCurrentBunSettings['description'] .' domain:'.$_item['domain_http'] )
				->expenditure();
			Core_Users::logout();
		}
		$this->_logger->info('End HOSTING checkExpired');
		usleep(1000);
		$this->check2delete();
	}

	/**
	 * Отправка пользователям сообщений если до наступления
	 *  срока удаления остался месяц,неделя,день
	 * @static
	 */
	private function notificationDelete(){
		$this->_logger->info('Start notification delete HOSTING');
		$_placement=new Project_Placement();
		if( $_placement
				->withOrder('d.user_id--dn')
				->withExpiryHosting( date('Y-m-d', time()-(60*60*24*30)) )
				->withUsersExplode()
				->withSendedHosting( Project_Placement::DELETE_MONTH )
				->getList( $arrMonth )->checkEmpty() ){
			Project_Placement_Notification::hostingDelete( $arrMonth );
			$this->_logger->info('Sended mail to user for Month deleted');
		}
		if( $_placement
				->withOrder('d.user_id--dn')
				->withExpiryHosting( date('Y-m-d', time()-(60*60*24*7)) )
				->withUsersExplode()
				->withSendedHosting( Project_Placement::DELETE_WEEK )
				->getList( $arrWeek )->checkEmpty() ){
			Project_Placement_Notification::hostingDelete( $arrWeek );
			$this->_logger->info('Sended mail to user for Week deleted');
		}
		if( $_placement
				->withOrder('d.user_id--dn')
				->withExpiryHosting( date('Y-m-d', time()-(60*60*24)) )
				->withUsersExplode()
				->withSendedHosting( Project_Placement::DELETE_DAY )
				->getList( $arrDay )->checkEmpty() ){
			Project_Placement_Notification::hostingDelete( $arrDay );
			$this->_logger->info('Sended mail to user for Day deleted');
		}
		$this->_logger->info('End notification delete HOSTING');
	}

	/**
	 * Отправка пользователям сообщений если до наступления
	 *  срока оплаты остался месяц,неделя,день
	 * @static
	 */
	private function notification( $arrCurrentBunSettings ){
		$this->_logger->info('Start notification HOSTING');
		$_placement=new Project_Placement();
		if( $_placement
				->withOrder('d.user_id--dn')
				->withExpiryHosting( date('Y-m-d', time()+(60*60*24*30)) )
				->withUsersExplode()
				->withSendedHosting( Project_Placement::SENDED_MONTH )
				->getList( $arrMonth )->checkEmpty() ){
			Project_Placement_Notification::hostingExpired( $arrMonth, $arrCurrentBunSettings );
			$this->_logger->info('Sended mail to user for Month expired');
		}
		if( $_placement
				->withOrder('d.user_id--dn')
				->withExpiryHosting( date('Y-m-d', time()+(60*60*24*7)) )
				->withUsersExplode()
				->withSendedHosting( Project_Placement::SENDED_WEEK )
				->getList( $arrWeek )->checkEmpty() ){
			Project_Placement_Notification::hostingExpired( $arrWeek, $arrCurrentBunSettings );
			$this->_logger->info('Sended mail to user for Week expired');
		}
		if( $_placement
				->withOrder('d.user_id--dn')
				->withExpiryHosting( date('Y-m-d', time()+(60*60*24)) )
				->withUsersExplode()
				->withSendedHosting( Project_Placement::SENDED_DAY )
				->getList( $arrDay )->checkEmpty() ){
			Project_Placement_Notification::hostingExpired( $arrDay, $arrCurrentBunSettings );
			$this->_logger->info('Sended mail to user for Day expired');
		}
		$this->_logger->info('End notification HOSTING');
	}

	// domain_http
	public function setHostingInfo( $_arr=array() ) {
		$this->_info=$_arr;
		return $this;
	}

	public function create() {
		if( empty( $this->_info['domain_http'] ) ){
			return false;
		}
		if( @$_SERVER['HTTP_HOST'] == 'cnm.local' ){
			var_dump( 'Project_Placement_Hosting::create()' );
			var_dump( $this->_info );
			return true;
		}
		if( $this->_info['flg_type']==Project_Placement::IFUNELS_HOSTING ){
			$_ssh=new Project_Placement_Hosting_Ssh('master');
			$_ssh->ssh()->setDirMode('750')->setFileMode('640');
			$_ssh->ssh()->setContent2File( str_replace( '.ifunnels.com', '', $this->_info['domain_http'] ),'/data/scripts/subdomainifunnels.txt' );
			$_ssh->ssh()->runScript('/data/scripts/subvhosts.sh');
		}else{
			$_users=new Project_Users_Management();
			$_users->withIds(Core_Users::$info['id'])->onlyOne()->getList( $_arrUser );
			if(!empty($_arrUser['parent'])){
				$_arrUser['amount']=$_arrUser['parent']['amount'];
			}
			$_objHosts=new Project_Placement();
			$_objHosts->onlyLimitedHosting()->withUserId( Core_Users::$info['id'] )->onlyCount()->getList( $_hostingLimit );
			$_flgPaymentHostings=$_hostingLimit>=Core_Users::$info['hosting_limit'] || Core_Users::$info['hosting_limit']==0;
			if( $_flgPaymentHostings ){
				$_intAmount=($this->_info['flg_type']==Project_Placement::LOCAL_HOSTING)?$_arrUser['amount']:Core_Payment_Purse::getAmount();
				if( $_intAmount < intval($this->_hostingPrice) ){
					return Core_Data_Errors::getInstance()->setError('You have no credits for buy hosting');
				}
			}
			$_ssh=new Project_Placement_Hosting_Ssh('master');
			$_ssh->ssh()->setDirMode('750')->setFileMode('640');
			$_ssh->ssh()->setContent2File( $this->_info['domain_http'],'/data/scripts/'.$this->_configFile );
			$_ssh->ssh()->runScript('/data/scripts/'.$this->_runFile);
			if( !$this->addToNameServer() ){
				return false;
			}
			if( $_flgPaymentHostings ){
				$_purse=new Core_Payment_Purse();
				$_purse
					->setAmount( $this->_hostingPrice ) // Сумма кредитов за хостинг
					->setUserId( Core_Users::$info['id'] )
					->setType( Core_Payment_Purse::TYPE_INTERNAL )
					->setMessage('Hosting was purchased successfully domain:'.$this->_info['domain_http'])
					->expenditure();
			}
		}
		return true;
	}

	public function delete() {
		if( empty( $this->_info['domain_http'] ) ){
			return Core_Data_Errors::getInstance()->setError('Can\'t find domain for delete');
		}
		/*----*/
		$_transport=new Project_Placement_Transport();
		$_transport->setInfo( $this->_info );
		if( !$_transport->readFile($strContent, '.htaccess') ){
			return Core_Data_Errors::getInstance()->setError('Can not read content');
		}
		/*----
		$_strDir='Project_Delete_Host@updateFiles';
		if ( !Zend_Registry::get( 'objUser' )->prepareTmpDir( $_strDir ) ) {
			return Core_Data_Errors::getInstance()->setError( 'Can\'t create dir '.$_strDir );
		}
		$_obj=new Project_Placement_Hosting_Ssh('master');
		$_ssh=$_obj->ssh();
		unlink( $_strDir.'/update.tmp' );
		$_ssh->download( '/data/www/'.$this->_info['domain_http'].'/html/.htaccess', $_strDir.'/update.tmp' );
		Core_Files::getContent( $strContent, $_strDir.'/update.tmp' );
		*/
		if( strpos( $this->_info['domain_http'], 'onlinenewsletters.net' ) !== false ||
			strpos( $this->_info['domain_http'], '.ifunnels.com' ) !== false ||
			strpos( $this->_info['domain_http'], 'consumertips.net' ) !== false ){
			$strContent=<<<EOL
RewriteEngine On
RewriteRule ^$ http://qjmpz.com/services/amazideas.php [R=301]
#
EOL
			.str_replace("\n","\n#",$strContent);
		}else{
			$strContent=<<<EOL
RewriteEngine On
RewriteRule ^$ / [R=410]
#
EOL
			.str_replace("\n","\n#",$strContent);
		}
		
		/*----*/
		if( !$_transport->saveFile( $strContent, '.htaccess') ){
			return Core_Data_Errors::getInstance()->setError('Can not save content');
		}
		/*----
		Core_Files::setContent( $strContent, $_strDir.'/update.tmp');
		$_ssh->uploadFile( $_strDir.'/update.tmp', '/data/www/'.$this->_info['domain_http'].'/html/.htaccess' );
		*/
		if( $this->_info['flg_type'] == Project_Placement::IFUNELS_HOSTING ){
			return true;
		}
		/* old remove
		$_ssh=new Project_Placement_Hosting_Ssh('master');
		$_ssh->ssh()->setDirMode('750')->setFileMode('640');
		$_ssh->ssh()->setContent2File( $this->_info['domain_http'],'/data/scripts/rm-domainname.txt');
		$_ssh->ssh()->runScript('/data/scripts/rm-vhosts.sh');
		$_ssh->ssh()->reloadApache();
		$_ssh=new Project_Placement_Hosting_Ssh('slave');
		$_ssh->ssh()->setDirMode('750')->setFileMode('640');
		$_ssh->ssh()->setContent2File( $this->_info['domain_http'],'/data/scripts/rm-domainname.txt');
		$_ssh->ssh()->runScript('/data/scripts/rm-vhosts.sh');
		$_ssh->ssh()->reloadApache();
		*/
		return $this->removeFromNameServer();
	}

	private  function addToNameServer() {
		
		$_ssh=new Project_Placement_Hosting_Ssh('master');
		$_ssh->ssh()->execCmd( 'cd /data/scripts/ &&  sudo ./add.sh '.$this->_info['domain_http'] );
		return true;
		
		/*-------------------  OLD CODE -----------------*/
		if( $this->_withLogger ){
			$this->_start=microtime(true)-$this->_start;
			$this->_logger2->info('before addToNameServer '.$this->_info['domain_http'].': '.$this->_start );
			$this->_start=microtime(true);
		}
		/*---------------------------------------------*/
		try{
			Core_Sql::setConnectToServer( 'creativenichemanager.hosting' );
		} catch( Zend_Db_Adapter_Exception $e ) {
			Core_Sql::renewalConnectFromCashe();
			/*---------------------------------------------*/
			if( $this->_withLogger ){
				$this->_start=microtime(true)-$this->_start;
				$this->_logger2->info('connect error '.$this->_info['domain_http'].': '.serialize($e).' timing '.$this->_start );
				$this->_start=microtime(true);
			}
			/*---------------------------------------------*/
			return Core_Data_Errors::getInstance()->setError('Can\'t connect to server');
		}
		$_intId=Core_Sql::getCell( 'SELECT id FROM domains WHERE name='.Core_Sql::fixInjection( $this->_info['domain_http'] ) );
		if ( !empty( $_intId ) ) {
			Core_Sql::renewalConnectFromCashe();
			/*---------------------------------------------*/
			if( $this->_withLogger ){
				$this->_start=microtime(true)-$this->_start;
				$this->_logger2->info('have domain in base'.$this->_info['domain_http'].': '.$this->_start );
				$this->_start=microtime(true);
			}
			/*---------------------------------------------*/
			return true;
		}
		$_intId=Core_Sql::setInsert( 'domains', array(
			'name'=>$this->_info['domain_http'],
			'type'=>'MASTER',
		) );
		Core_Sql::setInsert( 'records', array(
			'domain_id'=>$_intId,
			'name'=>$this->_info['domain_http'],
			'type'=>'A',
			'content'=>'107.20.247.159',
			'ttl'=>300,
			'prio'=>0,
			'change_date'=>time(),
		) );
		Core_Sql::setInsert( 'records', array(
			'domain_id'=>$_intId,
			'name'=>'www.'.$this->_info['domain_http'],
			'type'=>'CNAME',
			'content'=>$this->_info['domain_http'],
			'ttl'=>300,
			'prio'=>0,
			'change_date'=>time(),
		) );
		Core_Sql::setInsert( 'records', array(
			'domain_id'=>$_intId,
			'name'=>$this->_info['domain_http'],
			'type'=>'SOA',
			'content'=>'ns1.ifunnels.com hostmaster.ifunnels.com 2016051701 28800 7200 604800 86400',
			//'content'=>'ns1.'.$this->_info['domain_http'].'. hostmaster.'.$this->_info['domain_http'].'. '.date( 'Ymds' ).' 2880001 7200 604800 86400',
			'ttl'=>86400,
			'prio'=>0,
			'change_date'=>time(),
		) );
		Core_Sql::setInsert( 'records', array(
			'domain_id'=>$_intId,
			'name'=>$this->_info['domain_http'],
			'type'=>'NS',
			'content'=>'ns1.ifunnels.com',
			'ttl'=>300,
			'prio'=>0,
			'change_date'=>time(),
		) );
		Core_Sql::setInsert( 'records', array(
			'domain_id'=>$_intId,
			'name'=>$this->_info['domain_http'],
			'type'=>'NS',
			'content'=>'ns2.ifunnels.com',
			'ttl'=>300,
			'prio'=>0,
			'change_date'=>time(),
		) );
		Core_Sql::setInsert( 'records', array(
			'domain_id'=>$_intId,
			'name'=>'ns1.'.$this->_info['domain_http'],
			'type'=>'A',
			'content'=>'54.204.26.50',
			'ttl'=>86400,
			'prio'=>0,
			'change_date'=>time(),
		) );
		Core_Sql::setInsert( 'records', array(
			'domain_id'=>$_intId,
			'name'=>'ns2.'.$this->_info['domain_http'],
			'type'=>'A',
			'content'=>'184.72.240.171',
			'ttl'=>86400,
			'prio'=>0,
			'change_date'=>time(),
		) );
		Core_Sql::renewalConnectFromCashe();
		/*---------------------------------------------*/
		if( $this->_withLogger ){
			$this->_start=microtime(true)-$this->_start;
			$this->_logger2->info('after2 addToNameServer '.$this->_info['domain_http'].': '.$this->_start );
			$this->_start=microtime(true);
		}
		/*---------------------------------------------*/
		return true;
	}

	private function removeFromNameServer() {
		/*
		try{
			Core_Sql::setConnectToServer( 'creativenichemanager.hosting' );
		} catch( Zend_Db_Adapter_Exception $e ) {
			Core_Sql::renewalConnectFromCashe();
			return Core_Data_Errors::getInstance()->setError('Can\'t connect to server.');
		}
		Core_Sql::setExec( 'DELETE d, r FROM domains d LEFT JOIN records r ON r.domain_id=d.id WHERE d.name='.Core_Sql::fixInjection( $this->_info['domain_http'] ) );
		Core_Sql::renewalConnectFromCashe();
		*/
		return true;
	}

	private function setLogger() {
		$writer=new Zend_Log_Writer_Stream( 'php://output' );
		$writer->setFormatter( new Zend_Log_Formatter_Simple("%timestamp% %priorityName% (%priority%): %message%<br/>\r\n") );
		$this->_logger=new Zend_Log( $writer );
	}
}
?>