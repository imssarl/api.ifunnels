<?php

class Project_Deliver_Member extends Core_Data_Storage {

	protected $_table = 'deliver_customer';
	
	/**
	 * @param [id] ==> Record id in a table
	 * @param [site_id] ==> Site Id
	 * @param [user_id] ==> User Id
	 * @param [email] ==> Customer Email
	 * @param [added] ==> Unixtime code when a record is created
	 */
	protected $_fields = array( 'id', 'site_id', 'user_id', 'membership_id', 'email', 'customer_id', 'flg_lead', 'added', 'edited' );

	/** Installing */
	public static function install(){
		Core_Sql::setExec( "DROP TABLE IF EXISTS deliver_customer" );
		Core_Sql::setExec( 
			"CREATE TABLE `deliver_customer` (
				`id` INT(11) NOT NULL AUTO_INCREMENT,
				`site_id` INT(11) NOT NULL DEFAULT '0',
				`user_id` INT(11) NOT NULL DEFAULT '0',
				`email` VARCHAR(255) NULL DEFAULT NULL,
				`customer_id` TEXT NULL DEFAULT NULL,
				`flg_lead` BOOLEAN DEFAULT 0,
				`membership_id` INT(11) NULL DEFAULT NULL,
				`added` INT(11) UNSIGNED NOT NULL DEFAULT '0',
				`edited` INT(11) UNSIGNED NOT NULL DEFAULT '0',
				UNIQUE INDEX `id` (`id`)
			)
			COLLATE='utf8_general_ci'
			ENGINE=InnoDB;" 
		);
	}

	public function beforeSet() {
		$this->_data->setFilter( array( 'clear' ) );

		/** Check field [site_id] */
		if( empty( $this->_data->filtered['site_id'] ) ) {
			return Core_Data_Errors::getInstance()->setError( 'Not selected a site' );
		}
		
		/** Set field [user_id] */
		if( empty( $this->_data->filtered['user_id'] ) ) {
			$this->_data->setElement( 'user_id', Core_Users::$info['id'] );
		}

		return true;
	}

	private $_withUserId = false;
	public function withUserId( $user_id = false ) {
		if( ! $user_id ) {
			$this->_withUserId = Core_Users::$info['id'];
		} else {
			$this->_withUserId = $user_id;
		}

		return $this;
	}

	private $_withEmail = false;
	public function withEmail( $email ) {
		$this->_withEmail = $email;
		
		return $this;
	}

	private $_withSiteId = false;
	public function withSiteId( $site_id ) {
		$this->_withSiteId = $site_id;

		return $this;
	}

	private $_withConnectedMemberships = false;
	public function withConnectedMemberships() {
		$this->_withConnectedMemberships = true;
		
		return $this;
	}

	private $_paymentStatus = false;
	public function paymentStatus( $membershipIds ) {
		$this->_paymentStatus = $membershipIds;

		return $this;
	}

	private $_withCustomerId = false;
	public function withCustomerId( $cid ) {
		$this->_withCustomerId = $cid;

		return $this;
	}

	private $_withMembershipName = false;
	public function withMembershipName() {
		$this->_withMembershipName = true;
		return $this;
	}

	private $_onlyLeads = false;
	public function onlyLeads() {
		$this->_onlyLeads = true;
		return $this;
	}

	private $_withMembershipId = false;
	public function withMembershipId( $membership_id ) {
		$this->_withMembershipId = $membership_id;
		return $this;
	}

	private $_withPayMembership = false;
	public function withPayMembership( $membership_id ) {
		$this->_withPayMembership = $membership_id;
		return $this;
	}

	private $_filter = false;
	public function setFilter( $arrFilter ){
		$this->_filter = new Core_Data( $arrFilter );
		$this->_filter->setFilter();

		if( $this->_filter->filtered['membership_id'] ){
			$this->withMembershipId( $this->_filter->filtered['membership_id'] );
		}

		if( $this->_filter->filtered['membership_pay_id'] ) {
			$this->withPayMembership( $this->_filter->filtered['membership_pay_id'] );
		}

		return $this;
	}

	public function getFilter( &$arrFilter ){
		$arrFilter = $this->_filter->filtered;
		return $this;
	}

	protected function assemblyQuery() {
		parent::assemblyQuery();

		if( $this->_withUserId ){
			$this->_crawler->set_where( 'd.user_id=' . Core_Sql::fixInjection( $this->_withUserId ) );
		}

		if( ! empty( $this->_withEmail ) ){
			$this->_crawler->set_where( 'd.email=' . Core_Sql::fixInjection( $this->_withEmail ) );
		}

		if( ! empty( $this->_withCustomerId ) ){
			$this->_crawler->set_where( 'd.customer_id=' . Core_Sql::fixInjection( $this->_withCustomerId ) );
		}

		if( ! empty( $this->_withSiteId ) ) {
			$this->_crawler->set_where( 'd.site_id=' . Core_Sql::fixInjection( $this->_withSiteId ) );
		}

		if( ! empty( $this->_paymentStatus ) ) {
			$this->_crawler->clean_select();
			$this->_crawler->set_select( 'd.id, d.email, p.status as status, p.id as pid' );
			$this->_crawler->set_from( 'INNER JOIN deliver_payment p ON p.customer_id = d.id AND p.plan_id IN (' . Core_Sql::fixInjection( $this->_paymentStatus ) . ')' );
		}

		if( empty( $this->_withIds ) && empty( $this->_withCustomerId ) ) {
			if( $this->_onlyLeads ) {
				$this->_crawler->set_where( 'd.flg_lead = 1' );
			} else {
				$this->_crawler->set_where( 'd.flg_lead = 0' );
			}
		}

		if( $this->_withMembershipName ) {
			$this->_crawler->set_select( 'm.name as membership_name' );
			$this->_crawler->set_from( 'LEFT JOIN deliver_membership m ON m.id = d.membership_id' );
		}

		if( ! empty( $this->_withMembershipId ) ) {
			$this->_crawler->set_where( 'd.membership_id IN (' . Core_Sql::fixInjection( $this->_withMembershipId ) . ')' );
		}

		if( ! empty( $this->_withPayMembership ) ) {
			$this->_crawler->set_where( 'd.id IN ( SELECT p.customer_id FROM `deliver_payment` p WHERE p.plan_id IN (' . Core_Sql::fixInjection( $this->_withPayMembership ) . ') )' );
			
			/** TODO: 
			 * Another SQL query if long loading page
			 * SELECT dc.* FROM `deliver_customer` dc LEFT JOIN `deliver_payment` dp ON dc.id = dp.customer_id WHERE dp.plan_id = [membership_id] GROUP BY dc.id
			 */
		}

		// $this->_crawler->get_sql( $_strSql, $this->_paging );
		// var_dump( $_strSql );
	}

	protected function init() {
		$this->_withUserId = false;
		$this->_withSiteId = false;
		$this->_withEmail = false;
		$this->_withCustomerId = false;
		$this->_paymentStatus = false;
		$this->_withMembershipName = false;
		$this->_withMembershipId = false;
		$this->_onlyLeads = false;
		$this->_withPayMembership = false;
	}

	public function getList( &$mixRes ){
		parent::getList( $mixRes );

		if( $this->_withConnectedMemberships && ! empty( $mixRes ) ) {
			$membership = new Project_Deliver_Membership();

			if( array_key_exists( '0', $mixRes ) ) {
				foreach( $mixRes as &$record ) {
					$membership
						->withConnectedCustomer( $record['id'] )
						->getList( $record['arrPlans'] );
				}
			} else {
				$membership
					->withConnectedCustomer( $mixRes['id'] )
					->getList( $mixRes['arrPlans'] );
			}
			$this->_withConnectedMemberships = false;
		}

		$this->init();
		return $this;
	}

	/**
	 * Check status of payment for selected email
	 *
	 * @param [string] $email
	 * @param [int] $membership
	 * @return boolean
	 */
	public static function checkStatusPayment( $email, $membership ) {
		$self = new self();

		$self
			->withEmail( $email )
			->paymentStatus( $membership )
			->getList( $dataObj );

		$status = false;

		if( ! empty( $dataObj ) ) {
			foreach( $dataObj as $item ) {
				if( in_array( $item['status'], [ 'trialing', 'active', 'succeeded' ] ) ) {
					$status = true;
				}
			}
		}

		return $status;
	}

	/** 
	 * Create a new customer
	 * 
	 * @param {$data} - array with a keys [ payment_method, email, site_id, user_id ]
	 * @param {$stripe_account} - ID of the connected account from a stripe.com
	 * 
	 */
	public static function createCustomer( $data = array(), $stripe_account ) {
		if( empty( $data ) ) return false;
		
		extract( $data );

		if( ! isset( $site_id ) || ! isset( $user_id ) ) return false;

		$member = new self();
		$member
			->withEmail( $email )
			->onlyLeads()
			->onlyOne()
			->getList( $memberData );

		/** Getting data of membership */
		$membership = new Project_Deliver_Membership();
		$membership
			->withIds( $membership_id )
			->onlyOne()
			->getList( $membershipData );

		$instance = new Project_Subscribers( $user_id );

		/** Check exist user data with flg_lead */
		if( ! empty( $memberData ) ) {
			/** Update field membership_id */
			$member
				->setEntered( 
					[ 
						'id' => $memberData['id'], 
						'membership_id' => $membership_id,
						'site_id' => $memberData['site_id'],
						'user_id' => $memberData['user_id']
					] 
				)
				->set();

			$member->getEntered( $memberData );
			
			/** Added contact to general database of contacts */
			$instance
				->setEntered( [ 'email' => $email, 'tags' => '[Lead]: ' . $membershipData['name'] ] )
				->set();

			return $memberData;
		}

		$customerData = [
			'email' => $email
		];

		/** Enabled Shipping */
		if( isset( $shipping ) ) {
			$customerData['shipping'] = $shipping;
		}

		/** Payment Method */
		if( isset( $payment_method ) && ! empty( $payment_method ) ) {
			$customerData['payment_method'] = $payment_method;
			$customerData['invoice_settings'] = [ 'default_payment_method' => $payment_method ];
		}

		/** Create new customer on stripe.com */
		$customer = Project_Deliver_Stripe::setCustomer( $customerData, $stripe_account );

		/** Create new Customer on iFunnels */
		$member->setEntered( [
			'site_id' => $site_id,
			'user_id' => $user_id,
			'email' => $email,
			'customer_id' => $customer->id,
			'membership_id' => $membership_id,
			'flg_lead' => $flg_lead
		] )->set(); 

		$member->getEntered( $memberData );

		/** Added contact to general database of contacts */
		$instance
			->setEntered( [ 'email' => $email, 'tags' => '[Lead]: ' . $membershipData['name'] ] )
			->set();

		return $memberData;
	}

	/**
	 * Add payment method for user
	 *
	 * @param [string] $cid
	 * @param [string] $payment_method
	 * @param [string] $stripe_account
	 * @return mixed
	 */
	public static function addPaymentMethod( $cid, $payment_method, $stripe_account ) {
		$member = new self();

		$member
			->withCustomerId( $cid )
			->onlyOne()
			->getList( $memberData );

		if( empty( $memberData ) ) {
			return [ 'error' => [
					[ 'message' => 'User not found' ]
				]
			];
		}

		return Project_Deliver_Stripe::attachPaymentMethod( $memberData['customer_id'], $payment_method, $stripe_account );
	}
}