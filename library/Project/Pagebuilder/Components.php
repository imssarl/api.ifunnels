<?php
class Project_Pagebuilder_Components extends Core_Data_Storage{
	protected $_table='pb_components';
	protected $_fields=array( 'id', 'components_category', 'components_thumb', 'components_height', 'components_markup' );

	public function getList(&$mixRes){
		parent::getList( $mixRes );
	}

	public function set(){
		$this->_data->setFilter( array( 'clear' ) );
		if ( !$this->beforeSet() ){
			return false;
		}
		$this->_data->setElement( 'id', Core_Sql::setInsertUpdate( $this->_table, $this->_data->filtered) );
		return $this->afterSet();
	}
}