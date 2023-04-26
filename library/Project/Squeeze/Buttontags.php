<?php
class Project_Squeeze_Buttontags extends Core_Data_Storage{
	protected $_table='lpb_buttontags';
	protected $_fields=array('id', 'tags');
	
	public function set() {
		if ( !$this->beforeSet() ) {
			return false;
		}
		$_validate=$this->_data->setMask( $this->_fields )->getValid();
		if( $this->withIds( $_validate['id'] )->getList( $_tmp )->checkEmpty() ){
			Core_Sql::setInsertUpdate( $this->_table, $_validate, 'id' );
		}else{
			Core_Sql::setInsertUpdate( $this->_table, $_validate, 'noindex' );
		}
		return $this->afterSet();
	}
}
?>