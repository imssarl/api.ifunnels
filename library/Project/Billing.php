<?php


/**
 * Project_Billing
 */

class Project_Billing extends Core_Data_Storage {

	protected $_table='billing_aggregator';
	protected $_fields=array( 'id', 'aggregator', 'status', 'errormessage', 'event_type', 'clientid', 'revenuecurrency', 'phone', 'amount', 'service', 'transactionid', 'enduserprice', 'country', 'mno', 'mnocode', 'revenue', 'interval', 'opt_in_channel', 'sign', 'userid', 'added' );
	
	private $_withPhone=false;

	public function withPhone( $_str ){
		$this->_withPhone=$_str;
		return $this;
	}

	protected function init() {
		parent::init();
		$this->_withPhone=false;
	}
	
	protected function assemblyQuery() {
		parent::assemblyQuery();
		if( $this->_withPhone ){
			$this->_crawler->set_where('d.buyer_phone='.Core_Sql::fixInjection( str_replace( array(' ','-','+','(',')'),'',$this->_withPhone ) );
		}
	}
}
?>