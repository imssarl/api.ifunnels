<?php
class Project_Contentbox_Templates{

	protected $_table='lpb_cbtemplates2groups';
	protected $_fields=array( 'cbox_id', 'group_id' );
	
	public function __construct() {
		self::update();
	}
	
	protected $_withUserId=array();
	
	public static function update() {
		$_arrNulls=Core_Sql::getAssoc("SELECT NULL FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = 'lpb_cbtemplates2groups';");
			if( count( $_arrNulls ) == 0 ){
			Core_Sql::setExec( "CREATE TABLE `lpb_cbtemplates2groups` (
				`cbox_id` INT(11) UNSIGNED NOT NULL DEFAULT '0',
				`group_id` INT(11) UNSIGNED NOT NULL DEFAULT '0',
				PRIMARY KEY (`cbox_id`, `group_id`),
				INDEX `g_ids` (`cbox_id`),
				INDEX `u_idx` (`group_id`)
			)
			COLLATE='utf8_general_ci'
			ENGINE=MyISAM;
			" );
		}
	}

	private $_groupIds=array();
	private $_data=array();
	
	public function withGroupIds( $_id=array() ){
		$this->_groupIds=$_id;
		return $this;
	}
	
	public function getList( &$mixRes ) {
		if( !empty( $this->_groupIds ) ){
			$mixRes=Core_Sql::getAssoc( 'SELECT cbox_id, group_id FROM '.$this->_table.' WHERE group_id IN ('.Core_Sql::fixInjection( $this->_groupIds ).')' );
		}else{
			$mixRes=Core_Sql::getAssoc( 'SELECT cbox_id, group_id FROM '.$this->_table );
		}
		return $this;
	}

	public function setEntered( $_mix=array() ) {
		$this->_data=$_mix;
		return $this;
	}

	public function del() {
		Core_Sql::setExec( 'DELETE FROM '.$this->_table.' WHERE group_id = '.Core_Sql::fixInjection( $this->_groupIds ) );
		return true;
	}

	public function set() {
		foreach( $this->_data as $_data ){
			$_addData=is_object( $_data )?$_data:new Core_Data( $_data );
			Core_Sql::setInsertUpdate( $this->_table, $_addData->setMask( $this->_fields )->getValid() );
		}
	}
}
?>