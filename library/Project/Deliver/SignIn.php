<?php

class Project_Deliver_SignIn extends Core_Data_Storage {

	protected $_table = 'deliver_signin';
	protected $_table_connection = 'deliver_plan_customer';

	protected $_fields = array( 'id', 'customer_id', 'password', 'secretkey', 'last_login_datetime' );

	/** Installing */
	public static function install(){
		Core_Sql::setExec( "DROP TABLE IF EXISTS deliver_signin" );
		Core_Sql::setExec( 
			"CREATE TABLE `deliver_signin` (
				`id` INT(11) NOT NULL AUTO_INCREMENT,
				`customer_id` INT(11) NULL DEFAULT NULL,
				`password` TEXT NULL DEFAULT NULL,
				`secretkey` TEXT NULL DEFAULT NULL,
				`last_login_datetime` INT(11) UNSIGNED NOT NULL DEFAULT '0',
				UNIQUE INDEX `id` (`id`)
			)
			COLLATE='utf8_general_ci'
			ENGINE=InnoDB;" 
		);

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

		if( empty( $this->_data->filtered['id'] ) ) {
			/** Generated secret key */
			$this->_data->setElement( 'secretkey', uniqid() );

			/** Generate new password */
			$password = Core_Users::generatePassword();

			/** Set password hash */
			$this->_data->setElement( 'password', md5( $this->_data->filtered['secretkey'] . ':' . $password ) );

			/** Getting a customer data */
			$customer = new Project_Deliver_Member();
			$customer
				->withIds( $this->_data->filtered['customer_id'] )
				->onlyOne()
				->getList( $customerData );
			
			if( ! empty( $this->_data->filtered['membership_id'] ) ) {
				/** Getting a membership data */
				$membership = new Project_Deliver_Membership();
				$membership
					->withIds( $this->_data->filtered['membership_id'] )
					->onlyOne()
					->getList( $dataMembership );

				/** Added new membership for customer */
				$connection = new Project_Deliver_SignIn_Connection();
				$connection
					->setEntered( [
						'customer_id' => $this->_data->filtered['customer_id'],
						'membership_id' => $this->_data->filtered['membership_id']
					] )
					->set();

				if( $this->getCustomerData( $this->_data->filtered['customer_id'] ) !== false ) {
					return false;
				}
			}

			if( $this->_data->filtered['membership'] ) {
				$membership = new Project_Deliver_Membership();
				$membership
					->withIds( $this->_data->filtered['membership'] )
					->onlyOne()
					->getList( $dataMembership );
			}

			/** Send email for user with generated password */
			$mailer = new Core_Mailer();
			$mailer
				->setVariables( 
					[ 
						'email' => $customerData['email'], 
						'password' => $password, 
						'membership' => $dataMembership['name'],
						'membership_home_page_url' => $dataMembership['home_page_url']
					] 
				)
				->setTemplate( 'deliver_password' )
				->setSubject( 'Your Account Details: ' . $dataMembership['name'] )
				->setPeopleTo( [ 'email'=> $customerData['email'], 'name'=> $customerData['email'] ] )
				->setPeopleFrom( 
					[ 
						'name' => Zend_Registry::get( 'config' )->engine->project_sysemail->name, 
						'email' => Zend_Registry::get( 'config' )->engine->project_sysemail->email 
					] 
				)
				->sendOneToMany();
		} else {
			/** Genereted new secret key */
			$secret_key = uniqid();

			/** Updated password hash */
			$new_password_hash = md5( $secret_key . ":" . $this->_data->filtered['password'] );

			$this->_data->setElement( 'password', $new_password_hash );
			$this->_data->setElement( 'secretkey', $secret_key );
		}

		/** Updating last success login */
		$this->_data->setElement( 'last_login_datetime', time() );
		
		return true;
	}

	private $_withMemberships = false;
	public function withMemberships( $membership_id ) {
		if( ! empty( $membership_id ) ) {
			$this->_withMemberships = $membership_id;
		}

		return $this;
	}

	private $_withEmail = false;
	public function withEmail( $email ) {
		if( ! empty( $email ) ) {
			$this->_withEmail = $email;
		}

		return $this;
	}

	private $_withCustomerId = false;
	public function withCustomerId( $customer_id ) {
		$this->_withCustomerId = $customer_id;

		return $this;
	}

	protected function assemblyQuery() {
		parent::assemblyQuery();

		if( ! empty( $this->_withMemberships ) ){
			$this->_crawler->set_where( 'd.membership_id IN (' . Core_Sql::fixInjection( $this->_withMemberships ) . ')' );
		}

		if( ! empty( $this->_withCustomerId ) ) {
			$this->_crawler->set_where( 'd.customer_id = ' . Core_Sql::fixInjection( $this->_withCustomerId ) );
		}
	}

	protected function init() {
		$this->_withMemberships = false;
		$this->_withEmail = false;
		$this->_withCustomerId = false;
	}

	public function getList( &$mixRes ){
		parent::getList( $mixRes );

		$this->init();
		return $this;
	}

	/** 
	 * Sign in user to protected page
	 * 
	 * @param $email - User email
	 * @param $password - User password
	 * @param $membership_id - Membership Id
	 * 
	 * @return array
	 */
	public function auth( $email, $password, $membership_id ) {
		$errors = [];

		/** Validating email */
		if( empty( $email ) ) {
			$errors[][ 'message' ] = 'Input your email';
		}

		/** Validating password */
		if( empty( $password ) ) {
			$errors[][ 'message' ] = 'Input your password';
		}

		/** Check exist a membership_id */
		if( empty( $membership_id ) ) {
			$errors[][ 'message' ] = 'Empty membership value';
		}

		if( ! empty( $errors ) ) {
			return [
				'status' => false,
				'errors' => $errors
			];
		}

		$connection = new Project_Deliver_SignIn_Connection();

		$connection
			->getUserData()
			->withEmail( $email )
			->withMembershipId( $membership_id )
			->getList( $dataObj );

		/** Check exist a user */
		if( empty( $dataObj ) ) {
			return [
				'status' => false,
				'errors' => [ 
					[ 'message' => 'Incorrect login or password' ] 
				]
			];
		}

		/** Getting first record of the array */
		$member = $dataObj[0];

		if( ! Project_Deliver_Payment::checkStatusPayment( $member['member_id'], array_column( $dataObj, 'membership_id' ) ) ) {
			return [
				'status' => false,
				'errors' => [
					[ 'message' => 'You no longer have access to this page. (Payment failed)' ]
				]
			];
		}

		/** Generate password hash */
		$password_hash = md5( $member['secretkey'] . ':' . $password );

		if( $password_hash == $member['password'] ) {
			if( $this->setEntered( [ 'id' => $member['id'], 'password' => $password ] )->set() ) {
				$this->getEntered( $dataObj );

				return [
					'status' => true,
					'auth-token' => $email . ':' . md5( $dataObj['secretkey'] . ':' . $_SERVER['REMOTE_ADDR'] . ':' . $dataObj['last_login_datetime'] )
				];
			} else {
				return [
					'status' => false,
					'errors' => [
						[ 'message' => 'Problem with update secret key.' ]
					]
				];
			}
		}

		return [
			'status' => false,
			'errors' => [
				[ 'message' => 'Incorrect login or password' ]
			]
		];
	}

	public function auth_account( $cid, $password ) {
		$errors = [];

		/** Validating email */
		if( empty( $cid ) ) {
			$errors[][ 'message' ] = 'Input your email';
		}

		/** Validating password */
		if( empty( $password ) ) {
			$errors[][ 'message' ] = 'Input your password';
		}

		if( ! empty( $errors ) ) {
			return [
				'status' => false,
				'errors' => $errors
			];
		}

		$member = new Project_Deliver_Member();
		$member
			->withIds( $cid )
			->onlyOne()
			->getList( $memberData );

		if( empty( $memberData ) ) {
			return [
				'status' => false,
				'errors' => [ 
					[ 'message' => 'Incorrect login or password' ] 
				]
			];
		}

		$this
			->withCustomerId( $cid )
			->onlyOne()
			->getList( $dataObj );

		/** Check exist a user */
		if( empty( $dataObj ) ) {
			return [
				'status' => false,
				'errors' => [ 
					[ 'message' => 'Incorrect login or password' ] 
				]
			];
		}

		/** Generate password hash */
		$password_hash = md5( $dataObj['secretkey'] . ':' . $password );

		if( $password_hash == $dataObj['password'] ) {
			if( $this->setEntered( [ 'id' => $dataObj['id'], 'password' => $password ] )->set() ) {
				$this->getEntered( $dataObj );

				return [
					'status' => true,
					'cid' => $memberData['id']
				];
			} else {
				return [
					'status' => false,
					'errors' => [
						[ 'message' => 'Problem with update secret key.' ]
					]
				];
			}
		}

		return [
			'status' => false,
			'errors' => [
				[ 'message' => 'Incorrect login or password' ]
			]
		];
	}

	/** 
	 * Check auth a user to protected page by token
	 * 
	 * @param $token - User token
	 * 
	 * @return array
	 */
	public function isAuthorized( $token, $membership_ids ) {
		$dataObj = null;
		$data_array = explode( ":", $token );

		if( count( $data_array ) != 2 ) {
			return false;
		}

		list( $email, $token ) = $data_array;

		$connection = new Project_Deliver_SignIn_Connection();

		$connection
			->getUserData()
			->withEmail( $email )
			->withMembershipId( $membership_ids )
			->getList( $dataObj );
		
		if( empty( $dataObj ) ) {
			return false;
		}

		/** Getting first record of the array */
		$member = $dataObj[0];

		if( ! Project_Deliver_Payment::checkStatusPayment( $member['member_id'], array_column( $dataObj, 'membership_id' ) ) ) {
			return false;
		}

		$evaluate_hash = md5( $member['secretkey'] . ':' . $_SERVER['REMOTE_ADDR'] . ':' . $member['last_login_datetime'] );

		if( $token == $evaluate_hash ) {
			return true;
		}

		return false;
	}

	/**
	 * Find data in the table by field [customer_id] and return data record if exists
	 * 
	 * @param $customer_id - int
	 * 
	 * @return bool or array
	 */
	private function getCustomerData( $customer_id ) {
		$this
			->withCustomerId( $customer_id )
			->onlyOne()
			->getList( $dataObj );

		if( empty( $dataObj ) ) return false;

		return $dataObj;
	}
}