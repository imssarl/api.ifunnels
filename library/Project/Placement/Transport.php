<?php
/*
варианты использоваения
1)
$transport->setHostingId()->setSourceDir()->placeAndBreakConnect()->getErrors();
2)
$transport->setHostingId()->setSourceDir()->place()->getErrors();
$transport->setSourceDir()->place()->getErrors();
$transport->setSourceDir()->place()->getErrors();
$transport->breakConnect()->getErrors();
*/
class Project_Placement_Transport {

	private $_info=array();

	private $_dir='';

	private $_transport;

	private function factory() {
		if ( is_object( $this->_transport ) ) {
			return;
		}
		if ( empty( $this->_info['flg_type'] ) ) {
			$this->_transport=new Project_Placement_Transport_External( $this );
		} else {
			$this->_transport=new Project_Placement_Transport_Internal( $this );
		}
	}

	/**
	* Сбор параметров для настройки трансферта
	*
	* @param array $_arr - array( 'placement_id', 'ftp_directory', 'url', )
	* @return object
	*/
	public function setInfo( $_arr=array() ) {
		if( !is_array( $_arr ) ){
			$_arr=array();
		}
		if( isset( $_arr['publishing_options'] ) && $_arr['publishing_options'] != 'local' && $_arr['publishing_options'] != 'local_nossl' ){
			if ( !isset( $_arr['publishing_options'] ) || !isset( $_arr['placement_id'] ) || empty( $_arr['placement_id'] ) ) {
				throw new Project_Placement_Exception( Core_Errors::DEV.'|no valid _arr set' );
				return;
			}
			$obj=new Project_Placement();
			if ( !$obj->withIds( $_arr['placement_id'] )->onlyOne()->getList( $this->_info )->checkEmpty() ) {
				throw new Project_Placement_Exception( Core_Errors::DEV.'|wrong placement_id set - info not found' );
				return;
			}
			unset($_arr['flg_type']);
		}elseif( isset( $_arr['placement_id'] ) && !empty( $_arr['placement_id'] ) ){
			$obj=new Project_Placement();
			if ( !$obj->withIds( $_arr['placement_id'] )->onlyOne()->getList( $this->_info )->checkEmpty() ) {
				throw new Project_Placement_Exception( Core_Errors::DEV.'|wrong placement_id set - info not found' );
				return;
			}
			unset($_arr['flg_type']);
		}
		$this->_info=$this->_info+$_arr;
		$this->_info['domain_http']=strtolower( $this->_info['domain_http'] );
		$this->factory();
		return $this;
	}

	public function setSourceDir( $_strDir='' ) {
		if ( empty( $_strDir ) ) {
			throw new Project_Placement_Exception( Core_Errors::DEV.'|no _strDir set' );
			return;
		}
		$this->_dir=$_strDir;
		return $this;
	}

	public function getInfo() {
		return $this->_info;
	}

	public function getHttpHost() {
		return (!empty( $this->_info['flg_type'] )? $this->_info['domain_http']:$this->_info['url']);
	}

	public function getFtpHost() {
		return $this->_info['domain_ftp'];
	}

	public function getDir() {
		if( Core_Files::getExtension($this->_info['ftp_directory'])!='' ){
			$this->_info['ftp_directory']=Core_Files::getDirName($this->_info['ftp_directory']);
		}
		return (empty( $this->_info['ftp_directory'] )? '/':rtrim($this->_info['ftp_directory'],'/').'/');
	}

	public function getFtpPassword() {
		return $this->_info['password'];
	}

	public function getFtpUser() {
		return $this->_info['username'];
	}

	public function getTransfer(){
		return ($this->_info['flg_passive']==1)?true:false;
	}

	public function getSourceDir() {
		return $this->_dir;
	}

	public function placeAndBreakConnect() {
		$_bool=$this->place();
		$this->breakConnect();
		if( $_bool == false ){
			$_writer=new Zend_Log_Writer_Stream( Zend_Registry::get('config')->path->absolute->logfiles.'Project_Placement_Transport.log' );
			$_writer->setFormatter( new Zend_Log_Formatter_Simple("%timestamp% %priorityName% (%priority%): %message%\r\n") );
			$_logger=new Zend_Log( $_writer );
			$_logger->err('Place Error: '. join('<br/> -',Core_Data_Errors::getInstance()->getErrorsFlow()) );
		}
		return $_bool;
	}

	public function place() {
		return $this->_transport->place();
	}

	public function download( $remoteFile, $localFile ) {
		return $this->_transport->download($remoteFile,$localFile);
	}

	public function readFile( &$strContent, $remoteFile ){
		return $this->_transport->readFile( $strContent, $remoteFile );
	}

	public function saveFile( &$strContent, $remoteFile ){
		return $this->_transport->saveFile( $strContent, $remoteFile );
	}

	public function removeFile( $remoteFile ){
		return $this->_transport->removeFile( $remoteFile );
	}
	
	public function removeDir( $remoteDir ){
		return $this->_transport->removeDir( $remoteDir );
	}

	public function downloadAndBreakConnect( $remoteFile, $localFile ) {
		$_bool=$this->_transport->download($remoteFile,$localFile);
		$this->breakConnect();
		return $_bool;
	}

	public function dirScan( &$arrRes, $_strDir ){
		return $this->_transport->dirScan($arrRes,$_strDir);
	}

	public function breakConnect() {
		unSet( $this->_transport );
	}

	public function chmod( $f, $v ) {
		if( method_exists( $this->_transport, 'chmod' ) ){
			return $this->_transport->chmod( $f, $v );
		}else{
			return $this;
		}
	}

/*browsing*/

	public function browseDirs( &$arrDirs ) {
		return $this->_transport->browseDirs( $arrDirs );
	}

	public function isPassive(){
		if( $this->_info['flg_type']==Project_Placement::REMOTE_HOSTING&&($this->_info['flg_passive']==1&&!$this->_transport->isPassive()) ){
			$_place=new Project_Placement();
			$this->_info['flg_passive']=0;
			$_place->setEntered( $this->_info )->set();
		}
	}

	public function makeDirAndBreakConnect( $_strNewDir='' ) {
		if ( empty( $_strNewDir ) ) {
			$this->breakConnect();
			return false;
		}
		$_bool=$this->_transport->makeDir( $_strNewDir );
		$this->breakConnect();
		return $_bool;
	}

	public function getCurrentDir() {
		return $this->_transport->getCurrentDir();
	}

	public function getPrevDir() {
		return $this->_transport->getPrevDir();
	}

/*create db if need on local host*/

	public function setDb( Core_Data &$obj ) {
		if ( !is_object( $this->_transport )||empty( $this->_info['flg_type'] ) ) {
			return true;
		}
		$_placement=new Project_Placement();
		if ( !$_placement->onlyCountSites()->withIds( $this->_info['id'] )->getList( $_intCount )->checkEmpty() ) {
			$_intCount=0;
		}
		if(empty($this->_info['db_tableprefix'])){
			$this->_info['db_tableprefix']=++$_intCount.'_';
		}
		// создаём бд
		if ( empty( $this->_info['db_name'] ) ) {
			$this->_info['db_host']='hosting.cyx83yxvxugb.us-east-1.rds.amazonaws.com'; // TODO! возможно прикрепить постоянный IP
			$this->_info['db_name']='hosting_'.str_replace(array('.','-'),array('_'),$this->_info['domain_http']);
			$_code=new Core_Common_Code();
			$this->_info['db_password']=$_code->setLenght( 10 )->randStr()->getCode();
			$this->_info['db_username']=$_code->setLenght( 16 )->randStr()->getCode();
			try{
				Core_Sql::setConnectToServer( 'creativenichemanager.hosting' );
			} catch ( Zend_Db_Adapter_Exception $e ){ // RDS сервер недоступен создавать сайты с базами данных нельзя.
				Core_Sql::renewalConnectFromCashe();
				return Core_Data_Errors::getInstance()->setError('Can\'t connect to mysql server');
			}
			Core_Sql::setExec( 'CREATE DATABASE IF NOT EXISTS '.$this->_info['db_name'] );
			Core_Sql::setExec( 'GRANT ALL PRIVILEGES ON '.$this->_info['db_name'].'.* TO "'.$this->_info['db_username'].'"@"%"
				IDENTIFIED BY "'.$this->_info['db_password'].'" WITH GRANT OPTION' );
			Core_Sql::setExec( 'FLUSH PRIVILEGES' );
			Core_Sql::renewalConnectFromCashe();
			// сохраняем в site_placement доступ
			$_arrUpd=array(
				'id'=>$this->_info['id'],
				'db_host'=>$this->_info['db_host'],
				'db_name'=>$this->_info['db_name'],
				'db_username'=>$this->_info['db_username'],
				'db_password'=>$this->_info['db_password'],
			);
			if ( !$_placement->setEntered( $_arrUpd )->set() ) {
				return false;
			}
		}
		$obj->setElements( array(
			'db_host'=>$this->_info['db_host'],
			'db_name'=>$this->_info['db_name'],
			'db_username'=>$this->_info['db_username'],
			'db_password'=>$this->_info['db_password'],
			'db_tableprefix'=>$this->_info['db_tableprefix'],
		) );
		return true;
	}
}
?>