<?php
class Project_Pagebuilder_Block_Favorite extends Core_Data_Storage{
	protected $_table='pb_blocks_favorites';
	protected $_fields=array( 'id', 'block_id', 'user_id', 'blocks_url', 'blocks_height', 'blocks_thumb' );

	protected function assemblyQuery(){
		parent::assemblyQuery();
	}

	public function del(){
		$ids = $this->_withIds;
		$this->withIds($ids)->onlyOne()->getList($dataBlock);
		/** delete the thumbnail */
		if (file_exists(Zend_Registry::get('config')->path->absolute->pagebuilder . $dataBlock['blocks_thumb']) ) 
			unlink(Zend_Registry::get('config')->path->absolute->pagebuilder . $dataBlock['blocks_thumb']);

		$frames = new Project_Pagebuilder_Frames();
		$frames->withIds($dataBlock['block_id'])->del();
		$this->_withIds = $ids;
		
		if ( empty( $this->_withIds ) ){
			$_bool=false;
		} else {
			Core_Sql::setExec( 'DELETE FROM '.$this->_table.' 
				WHERE id IN('.Core_Sql::fixInjection( $this->_withIds ).')'.($this->_onlyOwner&&$this->getOwnerId( $_intId )? ' AND user_id='.$_intId:'') );
			$_bool=true;
		}
		$this->init();
		return $_bool;
	}
}