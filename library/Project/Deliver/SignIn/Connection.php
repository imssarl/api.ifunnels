<?php

class Project_Deliver_SignIn_Connection extends Core_Data_Storage {

	protected $_table = 'deliver_plan_customer';

	protected $_fields = array( 'id', 'customer_id', 'membership_id' );

	/** Installing */
	public static function install(){
		Core_Sql::setExec( "DROP TABLE IF EXISTS deliver_plan_customer" );
		Core_Sql::setExec( 
			"CREATE TABLE `deliver_plan_customer` (
				`id` INT(11) NOT NULL AUTO_INCREMENT,
				`customer_id` INT(11) NULL DEFAULT NULL,
				`membership_id` INT(11) NULL DEFAULT NULL,
				UNIQUE INDEX `id` (`id`)
			)
			COLLATE='utf8_general_ci'
			ENGINE=InnoDB;" 
		);
	}

	public function beforeSet() {
		$this->_data->setFilter( array( 'clear' ) );

		/** Check exist record in a table */
		if( $this->getCustomerData( $this->_data->filtered['customer_id'], $this->_data->filtered['membership_id'] ) !== false ) {
			return false;
		}

		return true;
	}

	private $_withMembershipId = false;
	public function withMembershipId( $membership_id ) {
		if( ! empty( $membership_id ) ) {
			$this->_withMembershipId = $membership_id;
		}

		return $this;
	}
	
	private $_withCustomerId = false;
	public function withCustomerId( $customer_id ) {
		$this->_withCustomerId = $customer_id;

		return $this;
	}

	private $_withEmail = false;
	public function withEmail( $email ) {
		$this->_withEmail = $email;

		return $this;
	}

	private $_getUserData = false;
	public function getUserData() {
		$this->_getUserData = true;
		return $this;
	}

	protected function assemblyQuery() {
		parent::assemblyQuery();

		if( $this->_getUserData ) {
			$this->_crawler->clean_select();
			$this->_crawler->set_select( 's.id as id, s.customer_id as member_id, c.email as email, s.password as password, s.secretkey as secretkey, s.last_login_datetime as last_login_datetime, d.membership_id' );
			$this->_crawler->set_from( 'INNER JOIN `deliver_signin` s on d.customer_id = s.customer_id and d.membership_id IN (' . Core_Sql::fixInjection( $this->_withMembershipId ) . ')' );
			$this->_crawler->set_from( 'INNER JOIN `deliver_customer` c on s.customer_id = c.id AND c.email = ' . Core_Sql::fixInjection( $this->_withEmail ) );
		} else {
			if( ! empty( $this->_withMembershipId ) ){
				$this->_crawler->set_where( 'd.membership_id IN (' . Core_Sql::fixInjection( $this->_withMembershipId ) . ')' );
			}

			if( ! empty( $this->_withCustomerId ) ) {
				$this->_crawler->set_where( 'd.customer_id = ' . Core_Sql::fixInjection( $this->_withCustomerId ) );
			}
		}

		// $this->_crawler->get_sql( $_strSql, $this->_paging );
		// p( $_strSql );
	}

	protected function init() {
		$this->_withMembershipId = false;
		$this->_withCustomerId = false;
		$this->_getUserData = false;
		$this->_withEmail = false;
	}

	public function getList( &$mixRes ){
		parent::getList( $mixRes );

		$this->init();
		return $this;
	}

	/**
	 * Find data in the table by field [customer_id] and return data record if exists
	 * 
	 * @param $customer_id - int
	 * 
	 * @return bool or array
	 */
	private function getCustomerData( $customer_id, $membership_id ) {
		$this
			->withCustomerId( $customer_id )
			->withMembershipId( $membership_id )
			->onlyOne()
			->getList( $dataObj );

		if( empty( $dataObj ) ) return false;

		return $dataObj;
	}
}