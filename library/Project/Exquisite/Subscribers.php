<?php


/**
 * Project_Exquisite_Subscribers
 */

class Project_Exquisite_Subscribers extends Core_Data_Storage{

	protected $_table='ulp_subscribers';
	protected $_fields=array('id', 'popup_id', 'name', 'email', 'phone', 'message', 'added');

	protected $_withPopupId=array(); // c данными popup id
	protected $_withPopupTitle=false;
	
	public function withPopupId( $_arrIds=array() ) {
		$this->_withPopupId=$_arrIds;
		return $this;
	}
	
	public function withPopupTitle() {
		$this->_withPopupTitle=true;
		return $this;
	}
	
	protected function assemblyQuery() {
		parent::assemblyQuery();
		if ( !empty( $this->_withPopupId ) ) {
			$this->_crawler->set_where( 'd.popup_id IN ('.Core_Sql::fixInjection( $this->_withPopupId ).')' );
		}
		if ( !empty( $this->_withPopupTitle ) ) {
			$this->_crawler->set_select( 'd.*, pp.title' );
			$this->_crawler->set_from( 'LEFT JOIN ulp_popups pp ON pp.id =d.popup_id' );
		}
	}
	
	protected function init() {
		parent::init();
		$this->_withPopupId=array();
		$this->_withPopupTitle=false;
	}
}
?>