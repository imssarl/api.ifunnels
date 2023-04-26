<?php
/**
 * WorkHorse Framework
 *
 * @category Project
 * @package Project_Billing
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2005-2015, web2innovation
 * @author Slepov Viacheslav <shadowdwarf@mail.ru>
 * @date 11.03.2015
 * @version 1.0
 */
 
/**
 * Project_Billing
 *
 * @category Project
 * @package Project_Billing
 * @copyright Copyright (c) 2005-2015, web2innovation
 * @license http://opensource.org/licenses/ MIT License
 */

class Project_Billing_Oxigen8 extends Core_Data_Storage {

	protected $_table='billing_oxigen8';
	protected $_fields=array( 'id', 'clientid', 'service', 'transactionid', 'added' );
	
	private $_withTransactionId=false;

	public function withTransactionId( $_str ){
		$this->_withTransactionId=$_str;
		return $this;
	}

	protected function init() {
		parent::init();
		$this->_withTransactionId=false;
	}
	
	protected function assemblyQuery() {
		parent::assemblyQuery();
		if( $this->_withTransactionId ){
			$this->_crawler->set_where('d.transactionid='.Core_Sql::fixInjection( $this->_withTransactionId ) );
		}
	}
}
?>