<?php

class Project_Deliver_Payment extends Core_Data_Storage {

	protected $_table = 'deliver_payment';
	
	protected $_fields = array( 'id', 'site_id', 'user_id', 'plan_id', 'customer_id', 'type_payment', 'one_payment_id', 'subscription_id', 'payment_intent', 'status', 'settings', 'added' );

	/** Installing */
	public static function install(){
		Core_Sql::setExec( "DROP TABLE IF EXISTS deliver_payment" );
		Core_Sql::setExec( 
			"CREATE TABLE `deliver_payment` (
				`id` INT(11) NOT NULL AUTO_INCREMENT,
				`site_id` INT(11) NOT NULL DEFAULT '0',
				`user_id` INT(11) NOT NULL DEFAULT '0',
				`plan_id` INT(11) NOT NULL DEFAULT '0',
				`customer_id` VARCHAR(255) NULL DEFAULT NULL,
				`type_payment` TINYINT(4) NULL DEFAULT NULL,
				`one_payment_id` TEXT NULL,
				`subscription_id` TEXT NULL,
				`payment_intent` TEXT NULL,
				`status` VARCHAR(255) NULL DEFAULT NULL,
				`settings` TEXT NULL,
				`added` INT(11) UNSIGNED NOT NULL DEFAULT '0',
				UNIQUE INDEX `id` (`id`)
			)
			COLLATE='utf8_general_ci'
			ENGINE=InnoDB" 
		);
	}

	public function beforeSet() {
		$this->_data->setFilter( array( 'clear' ) );

		/** Check field [site_id] */
		if( empty( $this->_data->filtered['id'] ) && empty( $this->_data->filtered['site_id'] ) ) {
			return Core_Data_Errors::getInstance()->setError( 'Not selected a site' );
		}

		if( ! empty( $this->_data->filtered['settings'] ) ) {
			$this->_data->setElement( 'settings', serialize( $this->_data->filtered['settings'] ) );
		}

		return true;
	}

	private $_withPaymentId = false;
	public function withPaymentId( $payment_id ) {
		if( ! empty( $payment_id ) ) {
			$this->_withPaymentId = $payment_id;
		}

		return $this;
	}

	private $_withPaymentIntentId = false;
	public function withPaymentIntentId( $payment_intent_id ) {
		$this->_withPaymentIntentId = $payment_intent_id;

		return $this;
	}

	private $_withSubscriptionId = false;
	public function withSubscriptionId( $subscriptionId ) {
		if( ! empty( $subscriptionId ) ) {
			$this->_withSubscriptionId = $subscriptionId;
		}

		return $this;
	}

	private $_withMembershipName = false;
	public function withMembershipName() {
		$this->_withMembershipName = true;

		return $this;
	}

	private $_withCustomerName = false;
	public function withCustomerName() {
		$this->_withCustomerName = true;

		return $this;
	}

	private $_withUserId = false;
	public function withUserId( $userId ) {
		if( ! empty( $userId ) ) {
			$this->_withUserId = $userId;
		}

		return $this;
	}

	private $_withCustomerId = false;
	public function withCustomerId( $customer_id ) {
		$this->_withCustomerId = $customer_id;
		return $this;
	}

	private $_withMembershipIds = false;
	public function withMembershipIds( $membership_ids ) {
		$this->_withMembershipIds = $membership_ids;
		return $this;
	}

	protected function assemblyQuery() {
		parent::assemblyQuery();

		if( ! empty( $this->_withPaymentId ) ){
			$this->_crawler->set_where( 'd.one_payment_id=' . Core_Sql::fixInjection( $this->_withPaymentId ) );
		}

		if( ! empty( $this->_withSubscriptionId ) ){
			$this->_crawler->set_where( 'd.subscription_id=' . Core_Sql::fixInjection( $this->_withSubscriptionId ) );
		}

		if( ! empty( $this->_withPaymentIntentId ) ) {
			$this->_crawler->set_where( 'd.payment_intent=' . Core_Sql::fixInjection( $this->_withPaymentIntentId ) );
		}

		if( $this->_withMembershipName ) {
			$this->_crawler->set_select( 'm.name as membership' );
			$this->_crawler->set_from( 'INNER JOIN deliver_membership m ON m.id = d.plan_id' );
		}

		if( $this->_withCustomerName ) {
			$this->_crawler->set_select( 'c.email as customer_email' );
			$this->_crawler->set_from( 'INNER JOIN deliver_customer c ON c.id = d.customer_id' );
		}

		if( ! empty( $this->_withCustomerId ) ) {
			$this->_crawler->set_where( 'd.customer_id = ' . Core_Sql::fixInjection( $this->_withCustomerId ) );
		}

		if( ! empty( $this->_withMembershipIds ) ) {
			$this->_crawler->set_where( 'd.plan_id IN (' . Core_Sql::fixInjection( $this->_withMembershipIds ) . ')' );
		}
	}

	protected function init() {
		$this->_withPaymentId = false;
		$this->_withSubscriptionId = false;
		$this->_withMembershipName = false;
		$this->_withCustomerName = false;
		$this->_withCustomerId = false;
		$this->_withMembershipIds = false;
		$this->_withPaymentIntentId = false;
	}

	/** Check status of payments for user
	 * 
	 * @param [int] - Customer ID of the module Deliver
	 * @param [array] - List of memberships ID
	 * 
	 * @return [boolean] - Return status payment true or false
	 */
	public static function checkStatusPayment( $customer_id, $membership_ids ) {
		
		$membership = new Project_Deliver_Membership();
		$membership
			->withIds( $membership_ids )
			->getList( $membershipData );

		$freeMemberships = array_filter( $membershipData, function( $data ) {
			return $data['type'] === '0';
		} );

		if( ! empty( $freeMemberships ) ) {
			return true;
		}

		$self = new self();
		$self
			->withCustomerId( $customer_id )
			->withMembershipIds( $membership_ids )
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

	public function getList( &$mixRes ){
		parent::getList( $mixRes );

		$this->init();
		return $this;
	}

	/**
	 * Update status of subscription
	 *
	 * @param [array] $options
	 * @return void
	 */
	public static function updateStatusSubscription( $options ) {
		extract( $options );

		$payment = new self();
		$payment
			->withSubscriptionId( $subid )
			->onlyOne()
			->getList( $subData );
		
		if( empty( $subData ) ) {
			return false;
		}

		if( isset( $status ) && ! empty( $status ) ) {
			return $payment	
				->setEntered( 
					[
						'id' => $subData['id'],
						'status' => $status
					]
				)
				->set();
		}
	}

	/** Update status for payment
	 * 
	 * @param [array] - Array with options
	 * @return [boolean] - Return true or false
	 */
	public static function updateStatusPayment( $options ) {
		extract( $options );

		$payment = new self();

		if( isset( $id ) ) {
			$payment
				->withIds( $id )
				->onlyOne()
				->getList( $paymentData );

			if( empty( $paymentData ) ) {
				return false;
			}

			$paymentData['status'] = $status;

			return $payment->setEntered( $paymentData )->set();
		}

		if( isset( $payment_intent_id ) ) {
			$payment
				->withPaymentIntentId( $payment_intent_id )
				->onlyOne()
				->getList( $paymentData );

			if( empty( $paymentData ) ) {
				return false;
			}

			$paymentData['status'] = $status;

			return $payment->setEntered( $paymentData )->set();
		}
	}

	/**
	 * Create subscription
	 *
	 * @param [int] $membershipId
	 * @param [string] $payment_method_id
	 * @param [string] $email
	 * @param boolean $trial
	 * @param [string] $paymentIntent
	 * @param [string] $stripe_account
	 * @return void
	 */
	public static function createSubscription( $membershipId, $payment_method_id, $email, $trial = false, $paymentIntent = null, $stripe_account ) {
		/** Get data of a plan */
		$membership = new Project_Deliver_Membership();

		$membership
			->withIds( $membershipId )
			->onlyOne()
			->getList( $planData );

		if( empty( $planData ) ) {
			return false;
		}

		$member = new Project_Deliver_Member();

		$member
			->withEmail( $email )
			->onlyOne()
			->getList( $customer );

		/** Check a customer exist */
		if( empty( $customer ) ) {
			/** Create new customer */
			$customer = Project_Deliver_Member::createCustomer(
				[
					'payment_method' => $payment_method_id, 
					'email' => $email, 
					'site_id' => $planData['site_id'], 
					'user_id' => $planData['user_id']
				],
				$stripe_account
			);
		}

		if( empty( $customer ) ) {
			return false;
		}

		if( ! empty( $planData['add_charges'] ) && $planData['add_charges_frequency'] == '0' ) {
			$site = new Project_Deliver_Site();

			$site
				->onlyOne()
				->withIds( $planData['site_id'] )
				->getList( $siteData );
			
			$amount = floatval( $planData['add_charges'] ) * 100;

			if( ! empty( $planData['add_taxes'] ) ) {
				$amount += intval( $amount * floatval( $planData['add_taxes'] ) / 100 );
			}

			Project_Deliver_Stripe::setInvoiceItem( [
				'amount' => $amount,
				'currency' => strtolower( $siteData['currency'] ),
				'customer' => $customer['customer_id'],
				'description' => ( ! empty( $planData['label_charges'] ) ? $planData['label_charges'] : 'Additional Charges' ),
			], $stripe_account );
		}

		/** Create Subscription on stripe.com and add to customer */
		$subscriptionData = [
			'customer' => $customer['customer_id'],
			'items' => [
				[
					'plan' => $planData['stripe_plan_id'],
				],
			],
			'application_fee_percent' => self::getUserFeePercent( $planData['user_id'] ),
			'expand' => ['latest_invoice.payment_intent'],
		];

		/** Limit rebills */
		if( ! empty( $planData['limit_rebills'] ) ) {
			$subscriptionData['cancel_at'] = strtotime( sprintf( '+%s %s', $planData['limit_rebills'], $planData['billing_frequency'] ) );
		}

		/** Trial */
		if( $trial && ! empty( $planData['trial_duration'] ) ) {
			$subscriptionData['trial_period_days'] = intval( $planData['trial_duration'] );
		}

		$subscription = Project_Deliver_Stripe::setSubscription( $subscriptionData, $stripe_account );

		/** Set data about subscription on table */
		$payment = new self();

		$payment->setEntered( [
			'site_id' => $planData['site_id'],
			'plan_id' => $planData['id'],
			'user_id' => $planData['user_id'],
			'customer_id' => $customer['id'],
			'type_payment' => '1',
			'payment_intent' => $paymentIntent,
			'subscription_id' => $subscription->id
		] )->set();

		return $subscription;
	}

	/** Create PaymentIntent on stripe.com
	 * 
	 * @param [string] - Membership ID on a module Deliver
	 * @param [string] - Customer ID on a stripe.com
	 * 
	 * @return [array]
	 */
	public static function createPaymentIntent( $membershipid, $cid, $paymentMethod, $trial, $stripe_account ) {
		/** Membership Data */
		$membership = new Project_Deliver_Membership();
		$membership
			->withIds( $membershipid )
			->onlyOne()
			->getList( $membershipData );

		if( empty( $membershipData ) ) {
			return false;
		}

		/** Site Data */
		$site = new Project_Deliver_Site();
		$site
			->withIds( $membershipData['site_id'] )
			->onlyOne()
			->getList( $siteData );

		$member = new Project_Deliver_Member();
		$member
			->withCustomerId( $cid )
			->onlyOne()
			->getList( $memberData );

		if( empty( $memberData ) ) {
			return false;
		}

		/** One time payment */
		if( $membershipData['frequency'] == '0' ) {
			$paymentIntentData = [
				'amount' => floatval( $membershipData['amount'] ) * 100,
				'currency' => strtolower( $siteData['currency'] ),
				'customer' => $cid,
				'payment_method_types' => [ 'card' ],
				'receipt_email' => $memberData['email'],
				'payment_method' => $paymentMethod
			];

			/** Add charges */
			if( ! empty( $membershipData['add_charges'] ) ) {
				$paymentIntentData['amount'] += floatval( $membershipData['add_charges'] ) * 100;
			}

			/** Add taxes */
			if( ! empty( $membershipData['add_taxes'] ) ) {
				$paymentIntentData['amount'] += intval( $paymentIntentData['amount'] * floatval( $membershipData['add_taxes'] ) / 100 );
			}

			/** Add application fee amount */
			$paymentIntentData['application_fee_amount'] = intval( $paymentIntentData['amount'] * self::getUserFeePercent( $membershipData['user_id'] ) / 100 ); // TODO: this application fee percent

			$paymentIntent = Project_Deliver_Stripe::setPaymentIntent( $paymentIntentData, $stripe_account );

			$payment = new self();
			$payment
				->setEntered( 
					[
						'site_id' => $membershipData['site_id'],
						'plan_id' => $membershipData['id'],
						'user_id' => $membershipData['user_id'],
						'customer_id' => $memberData['id'],
						'type_payment' => '0',
						'payment_intent' => $paymentIntent->id,
						'status' => $paymentIntent->status
					] 
				)
				->set();
			return $paymentIntent;
		}

		/** Subscription payment */
		if( $membershipData['frequency'] == '1' ) {

			/** Trial Subscribe */
			if( $trial ) {
				$amount = floatval( $membershipData['trial_amount'] ) * 100;
				
				/** Create Payment Intent */
				$paymentIntent = Project_Deliver_Stripe::setPaymentIntent(
					[
						'amount' => floatval( $membershipData['trial_amount'] ) * 100,
						'application_fee_amount' => intval( $amount * self::getUserFeePercent( $membershipData['user_id'] ) / 100 ), // TODO: this application fee percent
						'currency' => strtolower( $siteData['currency'] ),
						'customer' => $cid,
						'payment_method' => $paymentMethod
					],
					$stripe_account
				);

				$payment = new self();
		
				$payment
					->setEntered( 
						[
							'site_id' => $membershipData['site_id'],
							'plan_id' => $membershipData['id'],
							'user_id' => $membershipData['user_id'],
							'customer_id' => $memberData['id'],
							'type_payment' => '1',
							'payment_intent' => $paymentIntent->id,
							'status' => $paymentIntent->status
						]
					)
					->set();

				return $paymentIntent;
			} else {
				if( ! empty( $membershipData['add_charges'] ) && $membershipData['add_charges_frequency'] == '0' ) {
					
					$amount = floatval( $membershipData['add_charges'] ) * 100;
		
					if( ! empty( $membershipData['add_taxes'] ) ) {
						$amount += intval( $amount * floatval( $membershipData['add_taxes'] ) / 100 );
					}
		
					Project_Deliver_Stripe::setInvoiceItem( 
						[
							'amount' => $amount,
							'currency' => strtolower( $siteData['currency'] ),
							'customer' => $cid,
							'description' => ( ! empty( $membershipData['label_charges'] ) ? $membershipData['label_charges'] : 'Additional Charges' ),
						], 
						$stripe_account 
					);
				}
		
				/** Create Subscription on stripe.com and add to customer */
				$subscriptionData = [
					'customer' => $cid,
					'items' => [
						[
							'price' => $membershipData['stripe_plan_id'],
						],
					],
					'default_payment_method' => $paymentMethod,
					'application_fee_percent' => self::getUserFeePercent( $membershipData['user_id'] ), // TODO: this application fee percent
					'expand' => [ 'latest_invoice.payment_intent' ],
				];
		
				/** Limit rebills */
				if( ! empty( $membershipData['limit_rebills'] ) ) {
					$subscriptionData['cancel_at'] = strtotime( sprintf( '+%s %s', $membershipData['limit_rebills'], $membershipData['billing_frequency'] ) );
				}
		
				$subscription = Project_Deliver_Stripe::setSubscription( $subscriptionData, $stripe_account );

				$paymentIntent = $subscription->latest_invoice->payment_intent;
				
				/** Set data about subscription on table */
				$payment = new self();
		
				$payment
					->setEntered( 
						[
							'site_id' => $membershipData['site_id'],
							'plan_id' => $membershipData['id'],
							'user_id' => $membershipData['user_id'],
							'customer_id' => $memberData['id'],
							'type_payment' => '1',
							'payment_intent' => $paymentIntent->id,
							'subscription_id' => $subscription->id,
							'status' => $paymentIntent->status
						] 
					)
					->set();

				return $paymentIntent;
			}
		}

		return false;
	}

	/** Create trial subscribe
	 * 
	 * @param [string] - Membership ID from system the stripe.com
	 * @param [string] - Customer ID from system the stripe.com
	 * @param [string] - Payment Intent ID from system the stripe.com
	 * 
	 * @return {boolean} Return true or false
	 */
	public static function createTrialSubscribe( $membershipId, $customer_id, $payment_intent_id, $stripe_account ) {
		$membership = new Project_Deliver_Membership();
		$membership
			->withIds( $membershipId )
			->onlyOne()
			->getList( $membershipData );
		
		$site = new Project_Deliver_Site();
		$site
			->withIds( $membershipData['site_id'] )
			->onlyOne()
			->getList( $siteData );
		
		if( ! empty( $membershipData['add_charges'] ) && $membershipData['add_charges_frequency'] == '0' ) {
			$amount = floatval( $membershipData['add_charges'] ) * 100;

			if( ! empty( $membershipData['add_taxes'] ) ) {
				$amount += intval( $amount * floatval( $membershipData['add_taxes'] ) / 100 );
			}

			Project_Deliver_Stripe::setInvoiceItem( 
				[
					'amount' => $amount,
					'currency' => strtolower( $siteData['currency'] ),
					'customer' => $customer_id,
					'description' => ( ! empty( $membershipData['label_charges'] ) ? $membershipData['label_charges'] : 'Additional Charges' ),
				], 
				$stripe_account 
			);
		}

		/** Create Subscription on stripe.com and add to customer */
		$subscriptionData = [
			'customer' => $customer_id,
			'items' => [
				[
					'price' => $membershipData['stripe_plan_id'],
				],
			],
			'application_fee_percent' => self::getUserFeePercent( $membershipData['user_id'] ), // TODO: this application fee percent
			'expand' => [ 'latest_invoice.payment_intent' ],
		];

		/** Trial */
		if( ! empty( $membershipData['trial_duration'] ) ) {
			$subscriptionData['trial_period_days'] = intval( $membershipData['trial_duration'] );
		}

		/** Limit rebills */
		if( ! empty( $membershipData['limit_rebills'] ) ) {
			$subscriptionData['cancel_at'] = strtotime( sprintf( '+%s %s', $membershipData['limit_rebills'], $membershipData['billing_frequency'] ) );
		}

		$subscription = Project_Deliver_Stripe::setSubscription( $subscriptionData, $stripe_account );

		if( ! isset( $subscription['error'] ) ) {
			$payment = new self();

			$payment
				->withPaymentIntentId( $payment_intent_id )
				->onlyOne()
				->getList( $paymentData );
			
			if( ! empty( $paymentData ) ) {
				return $payment->setEntered( [ 'id' => $paymentData['id'], 'subscription_id' => $subscription->id ] )->set();
			} else {
				return false;
			}
		}

		return false;
	}

	/** Getting data of one time payment
	 * 
	 * @param [string] - Payment Intent ID from system the stripe.com
	 * @param [int] - Customer ID from module Deliver
	 * @param [int] - User ID if needed
	 * 
	 * @return [array] - Returned array with data or array of errors
	 */
	public function getOneTimePaymentDetails( $payment_intent, $customer_id, $stripe_account ) {
		$paymentIntent = Project_Deliver_Stripe::getPaymentIntent( $payment_intent, $stripe_account );

		if( array_key_exists( 'error', $paymentIntent ) ) {
			return $paymentIntent;
		}

		$customer = new Project_Deliver_Member();
		$customer
			->onlyOne()
			->onlyOwner()
			->withIds( $customer_id )
			->getList( $customerData );

		$response = [
			'amount' => ( $paymentIntent->amount / 100 ),
			'fee_amount' => $paymentIntent->application_fee_amount / 100,
			'currency' => $paymentIntent->currency,
			'created' => $paymentIntent->created,
			'customer' => $customerData,
			'payment_intent_id' => $payment_intent
		];

		return $response;
	}

	/**
	 * Refund payment of one time
	 *
	 * @param [string] $payment_intent_id
	 * @param [int] $payment_id
	 * @param [string] $stripe_account
	 * @return void
	 */
	public function refundOneTimePayment( $payment_intent_id, $payment_id, $stripe_account ) {
		$refundObj = Project_Deliver_Stripe::refundPayment( [
			'payment_intent' => $payment_intent_id
		], $stripe_account );

		if( $refundObj->status == 'succeeded' ) {
			$this->setEntered( [ 'id' => $payment_id, 'status' => 'refunded' ] )->set();
		}
	}

	/**
	 * Getting of subscription payment details
	 *
	 * @param [string] $subscription_id
	 * @param [int] $customer_id
	 * @param [string] $paymentIntent
	 * @param [string] $stripe_account
	 * @return array
	 */
	public function getSubscriptionPaymentDetails( $subscription_id, $customer_id, $paymentIntent = null, $stripe_account ) {
		$responce = [];

		$subscription = Project_Deliver_Stripe::getSubscription( $subscription_id, $stripe_account );

		if( empty( $paymentIntent ) ) {
			$invoiceObj = Project_Deliver_Stripe::retriveInvoice( $subscription->latest_invoice, $stripe_account );

			$responce = [
				'amount' => $invoiceObj->amount_paid / 100,
				'fee_amount' => $invoiceObj->application_fee_amount / 100,
				'currency' => $invoiceObj->currency,
				'payment_intent_id' => $invoiceObj->payment_intent
			];
		} else {
			$paymentIntent = Project_Deliver_Stripe::getPaymentIntent( $paymentIntent, $stripe_account );
			$responce = [
				'amount' => $paymentIntent->amount / 100,
				'fee_amount' => $paymentIntent->application_fee_amount / 100,
				'currency' => $paymentIntent->currency,
				'payment_intent_id' => $paymentIntent->id
			];
		}

		$customer = new Project_Deliver_Member();

		$customer
			->onlyOne()
			->onlyOwner()
			->withIds( $customer_id )
			->getList( $customerData );

		$responce['created'] = $subscription->created;
		$responce['customer'] =$customerData;

		return $responce;
	}

	/**
	 * Refund payment of subscribe
	 *
	 * @param [string] $payment_intent_id
	 * @param [string] $payment_id
	 * @param [string] $subscription_id
	 * @param [string] $stripe_account
	 * @return void
	 */
	public function refundSubscribePayment( $payment_intent_id, $payment_id, $subscription_id, $stripe_account ) {
		$refundObj = Project_Deliver_Stripe::refundPayment( [
			'payment_intent' => $payment_intent_id
		], $stripe_account );

		if( $refundObj->status == 'succeeded' ) {
			$this->setEntered( [ 'id' => $payment_id, 'status' => 'refunded' ] )->set();

			$this->unsubscribe( $subscription_id, false, $stripe_account );
		}
	}

	/** Unsubscribe user
	 * 
	 * @param [string] - ID subscribe from system stripe.com
	 * @param [int] - ID payment from module Deliver
	 * @param [int] - User ID
	 * 
	 * @return [string] - Return a action status
	 */
	public function unsubscribe( $subscription_id, $payment_id = false, $stripe_account ) {
		if( empty( $subscription_id ) ) {
			return json_encode( [ 'error' => 'Empty value: subscription id' ] );
		}

		$subscription = Project_Deliver_Stripe::getSubscription( $subscription_id, $stripe_account );
		$subscription = $subscription->delete();

		if( ! empty( $payment_id ) ) {
			$this->setEntered( [ 'id' => $payment_id, 'status' => $subscription->status ] )->set();
		}

		return $subscription->status;
	}

	/** Getting the percentage of remuneration set for the user or, if empty, return 4% by default
	 * @param [int or string] - User ID
	 * @return [int] - Fee percent of user or 4% by default
	 */
	public static function getUserFeePercent( $user_id ) {
		/** Default fee percent */
		$default_fee = 4;

		if( empty( $user_id ) ) return $default_fee;

		$instance = new Core_Users_Management();
		
		/** Getting data of user */
		$instance
			->withIds( $user_id )
			->onlyOne()
			->getList( $userData );

		if( empty( $userData ) || empty( $userData['stripe_fee'] ) ) return $default_fee;

		/** Update defautl fee percent */
		$default_fee = intval( $userData['stripe_fee'] );

		return $default_fee;
	}
}