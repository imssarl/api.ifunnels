<?php
class Project_Efunnel_Access{

	protected $_table='lpb_efunnels_funnel2groups';
	protected $_fields=array( 'funnel_id', 'group_id' );

	public static function install(){
		Core_Sql::setExec("drop table if exists lpb_efunnels_funnel2groups");
		Core_Sql::setExec( "CREATE TABLE `lpb_efunnels_funnel2groups` (
			`funnel_id` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			`group_id` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			PRIMARY KEY (`funnel_id`, `group_id`),
			INDEX `g_ids` (`funnel_id`),
			INDEX `u_idx` (`group_id`)
		)
		COLLATE='utf8_general_ci'
		ENGINE=MyISAM;
		" );
		return $this;
	}

	private $_groupIds=array();
	private $_data=array();
	
	public function withGroupIds( $_id=array() ){
		$this->_groupIds=$_id;
		return $this;
	}
	
	public function getList( &$mixRes ){
		if( !empty( $this->_groupIds ) ){
			$mixRes=Core_Sql::getAssoc( 'SELECT funnel_id, group_id FROM '.$this->_table.' WHERE group_id IN ('.Core_Sql::fixInjection( $this->_groupIds ).')' );
		}else{
			$mixRes=Core_Sql::getAssoc( 'SELECT funnel_id, group_id FROM '.$this->_table );
		}
		return $this;
	}

	public function setEntered( $_mix=array() ){
		$this->_data=$_mix;
		return $this;
	}

	public function del(){
		Core_Sql::setExec( 'DELETE FROM '.$this->_table.' WHERE group_id = '.Core_Sql::fixInjection( $this->_groupIds ) );
		return true;
	}

	public function set(){
		foreach( $this->_data as $_data ){
			$_addData=is_object( $_data )?$_data:new Core_Data( $_data );
			Core_Sql::setInsertUpdate( $this->_table, $_addData->setMask( $this->_fields )->getValid() );
		}
	}
}
?>