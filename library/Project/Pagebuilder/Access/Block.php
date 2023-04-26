<?php
class Project_Pagebuilder_Access_Block{

	protected $_table='pb_block2groups';
	protected $_fields=array( 'block_id', 'group_id' );

	public static function install(){
		Core_Sql::setExec("drop table if exists pb_block2groups");
		Core_Sql::setExec( "CREATE TABLE `pb_block2groups` (
			`block_id` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			`group_id` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			PRIMARY KEY (`block_id`, `group_id`),
			INDEX `g_ids` (`block_id`),
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
			$mixRes=Core_Sql::getAssoc( 'SELECT block_id, group_id FROM '.$this->_table.' WHERE group_id IN ('.Core_Sql::fixInjection( $this->_groupIds ).')' );
		}else{
			$mixRes=Core_Sql::getAssoc( 'SELECT block_id, group_id FROM '.$this->_table );
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