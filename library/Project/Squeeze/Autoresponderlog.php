<?php


/**
 * Project_Squeeze_Autoresponderlog
 */

class Project_Squeeze_Autoresponderlog extends Core_Data_Storage{

	protected $_table='lpb_subscriberslog';
	protected $_fields=array('id', 'mo_id', 'user_id', 'ar_id', 'subscriber_id', 'autoresponders', 'message', 'request', 'ip', 'added');

	protected $_withMoId=array(); // c данными popup id
	protected $_withSubscriberIds=array(); // c данными popup id
	protected $_withEmail=array(); // c данными email

	public function __construct() {
		self::update();
	}
	
	public static function update(){
		$_arrNulls=Core_Sql::getAssoc("SELECT NULL
            FROM INFORMATION_SCHEMA.COLUMNS
           WHERE table_name = 'lpb_subscriberslog'
             AND column_name = 'message';");
		if( count( $_arrNulls ) == 0 ){
			Core_Sql::setExec("ALTER TABLE `lpb_subscriberslog` ADD `message` LONGTEXT NULL COLLATE 'utf8_unicode_ci'");
		}
		$_arrNulls=Core_Sql::getAssoc("SELECT NULL
            FROM INFORMATION_SCHEMA.COLUMNS
           WHERE table_name = 'lpb_subscriberslog'
             AND column_name = 'request';");
		if( count( $_arrNulls ) == 0 ){
			Core_Sql::setExec("ALTER TABLE `lpb_subscriberslog` ADD `request` LONGTEXT NULL COLLATE 'utf8_unicode_ci'");
		}
	}
	
	public static function install (){
		Core_Sql::setExec("CREATE TABLE IF NOT EXISTS `lpb_subscriberslog` (
			`id` INT(11) NOT NULL AUTO_INCREMENT,
			`mo_id` INT(11) NULL DEFAULT NULL,
			`user_id` INT(11) NULL DEFAULT NULL,
			`subscriber_id` INT(11) NULL DEFAULT NULL,
			`ar_id` INT(11) NULL DEFAULT NULL,
			`autoresponders` LONGTEXT NULL COLLATE 'utf8_unicode_ci',
			`message` LONGTEXT NULL COLLATE 'utf8_unicode_ci',
			`request` LONGTEXT NULL COLLATE 'utf8_unicode_ci',
			`ip` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_unicode_ci',
			`added` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			UNIQUE INDEX `id` (`id`)
		);");
	}
	
	public function withMoId( $_arrIds=array() ) {
		$this->_withMoId=$_arrIds;
		return $this;
	}
	
	public function withSubscriberIds( $_arrIds=array() ) {
		$this->_withSubscriberIds=$_arrIds;
		return $this;
	}
	
	protected function assemblyQuery() {
		parent::assemblyQuery();
		if ( !empty( $this->_withMoId ) ) {
			$this->_crawler->set_where( 'd.mo_id IN ('.Core_Sql::fixInjection( $this->_withMoId ).')' );
		}
		if ( !empty( $this->_withSubscriberIds ) ) {
			$this->_crawler->set_where( 'd.subscriber_id IN ('.Core_Sql::fixInjection( $this->_withSubscriberIds ).')' );
		}
	}
	
	protected function init() {
		parent::init();
		$this->_withMoId=array();
		$this->_withSubscriberIds=array();
	}
	
}
?>