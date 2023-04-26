<?php
final class Core_Sql {

	private static $_instance=NULL;

	// implements Core_Singleton_Interface TODO!!!
	public static function getInstance( $_needNew=false ) {
		if ( self::$_instance==NULL||$_needNew ) {
			self::createConnect( Zend_Registry::get( 'config' )->database );
		}
		return self::$_instance;
	}

	// кэшируем
	private static $_httpHost;
	private static $_config;
	private static $_connect;
	// сохраняем новый коннект в renewalConnectFromCashe
	private static $_prevHost;
	private static $_parentHost;

	private static $_arrConnections=array();
	
	public static function createConnect( Zend_Config $conf ) {
		if( isset( $_SERVER['HTTP_HOST'] ) && !empty( $_SERVER['HTTP_HOST'] ) && isset( self::$_arrConnections[$_SERVER['HTTP_HOST']] ) && is_object( self::$_arrConnections[$_SERVER['HTTP_HOST']] ) ){
			self::$_instance=self::$_arrConnections[$_SERVER['HTTP_HOST']];
			return;
		}
		switch( $conf->arhitecture ) {
			case 'single': self::$_instance=new Core_Sql_Arhitecture_Single( $conf ); break;
			case 'replication': self::$_instance=new Core_Sql_Arhitecture_Replication( $conf ); break;
		}
		if( isset( $_SERVER['HTTP_HOST'] ) && !empty( $_SERVER['HTTP_HOST'] ) && !isset( self::$_arrConnections[$_SERVER['HTTP_HOST']] ) ){
			if( empty( self::$_parentHost ) ){
				self::$_parentHost=$_SERVER['HTTP_HOST'];
			}
			self::$_arrConnections[$_SERVER['HTTP_HOST']]=self::$_instance;
		}
	}

	public static function setConnectToServer( $_strServerDomain='' ) {
		if( $_strServerDomain != $_SERVER['HTTP_HOST'] ){
			self::$_prevHost=$_SERVER['HTTP_HOST'];
		}
		if( isset( self::$_arrConnections[$_strServerDomain] ) && is_object( self::$_arrConnections[$_strServerDomain] ) ){
			self::$_instance=self::$_arrConnections[$_strServerDomain];
			return;
		}
		$_SERVER['HTTP_HOST']=$_strServerDomain; // устанавливаем требуемый
		if ( Zend_Registry::isRegistered( 'config' ) ) {
			self::$_config=Zend_Registry::get( 'config' ); // кэшируем конфиг
		}
		Zend_Registry::set( 'config', new Zend_Config( require 'config.php' ) ); // устанавливаем новый
		self::getInstance( true ); // устанавливаем соединение
		return;
	}

	// метод для восстановления предыдущего значения _instance
	public static function renewalConnectFromCashe(){
		if( isset( self::$_arrConnections[self::$_parentHost] ) && is_object( self::$_arrConnections[self::$_parentHost] ) ){
			self::$_instance=self::$_arrConnections[self::$_parentHost];
			$_SERVER['HTTP_HOST']=self::$_parentHost;
		}
	}

	// SQLSelect Key from 0 to n Value is Assoc Record
	public static function getAssoc( $strQ='' ) {
		$_GLOBALS['sql_log'][microtime()]=$strQ;
		return self::getInstance()->getAssoc( $strQ );
	}

	// SQLSelectOne - FirstRecord
	public static function getRecord( $strQ='' ) {
		$_GLOBALS['sql_log'][microtime()]=$strQ;
		return self::getInstance()->getRecord( $strQ );
	}

	// SQLSelectOneField - FirstField
	public static function getField( $strQ='' ) {
		$_GLOBALS['sql_log'][microtime()]=$strQ;
		return self::getInstance()->getField( $strQ );
	}

	// SQLSelectKeyValArray - FirstFieldAsKeySecondFieldAsValue
	public static function getKeyVal( $strQ='' ) {
		$_GLOBALS['sql_log'][microtime()]=$strQ;
		return self::getInstance()->getKeyVal( $strQ );
	}

	// SQLSelectKeyRec - FirstCellOfRecordAsKeyAssocRecordAsValue
	public static function getKeyRecord( $strQ='' ) {
		$_GLOBALS['sql_log'][microtime()]=$strQ;
		return self::getInstance()->getKeyRecord( $strQ );
	}

	// SQLSelectCell - FirstCellOfFirstRecord
	public static function getCell( $strQ='' ) {
		$_GLOBALS['sql_log'][microtime()]=$strQ;
		return self::getInstance()->getCell( $strQ );
	}

	// SQLExec
	public static function setExec( $strQ='' ) {
		$_GLOBALS['sql_log'][microtime()]=$strQ;
		if( strpos( $strQ, 'DELETE FROM es_ncsb ' ) !== false ){
			$_writer=new Zend_Log_Writer_Stream( Zend_Registry::get('config')->path->absolute->logfiles.'Amazideas_delete.log' );
			$_writer->setFormatter( new Zend_Log_Formatter_Simple("%timestamp% %priorityName% (%priority%): %message%\r\n") );
			$_logger=new Zend_Log( $_writer );
			$_logger->info( $strQ );
			$_logger->info('USER : '.serialize( Core_Users::$info ) );
			$_logger->info('SERVER : '.serialize( $_SERVER ) );
			$_logger->info('POST : '.serialize( $_POST ) );
			$_logger->info('GET : '.serialize( $_GET ) );
		}
		return self::getInstance()->setExec( $strQ );
	}

	// SQLInsert
	public static function setInsert( $strTbl='', $arrDta=array() ) {
		$_GLOBALS['sql_log'][microtime()]=$strQ;
		return self::getInstance()->setInsert( $strTbl, $arrDta );
	}

	// SQLUpdate
	public static function setUpdate( $strTbl='', $arrDta=array(), $strNdx='' ) {
		$_GLOBALS['sql_log'][microtime()]=$strQ;
		return self::getInstance()->setInsertUpdate( $strTbl, $arrDta, $strNdx );
	}

	// SQLUpdateInsert
	public static function setUpdateInsert( $strTbl='', $arrDta=array(), $strNdx='' ) {
		$_GLOBALS['sql_log'][microtime()]=$strQ;
		return self::getInstance()->setInsertUpdate( $strTbl, $arrDta, $strNdx );
	}

	// SQLInsertUpdate - alias of SQLUpdateInsert
	public static function setInsertUpdate( $strTbl='', $arrDta=array(), $strNdx='' ) {
		$_GLOBALS['sql_log'][microtime()]=$strQ;
		return self::getInstance()->setInsertUpdate( $strTbl, $arrDta, $strNdx );
	}

	// SQLInsertMass
	public static function setMassInsert( $strTbl='', $arrDta=array() ) {
		$_GLOBALS['sql_log'][microtime()]=$strQ;
		return self::getInstance()->setMassInsert( $strTbl, $arrDta );
	}

	public static function fixInjection( $_mixVar='' ) {
		return self::getInstance()->fixInjection( $_mixVar );
	}

	// в случае если пропадает коннект к бд после долгих операций
	public static function reconnect() {
		return self::getInstance()->reconnect();
	}

	// Core_Sql::disconnect
	public static function disconnect() {
		if ( self::$_instance!=NULL ) {
			return self::getInstance()->setDisconnect();
		}
	}

	public static function singleMode() {
		self::getInstance()->setSingleMode();
	}

	public static function replicationMode() {
		self::getInstance()->setReplicationMode();
	}
}
?>