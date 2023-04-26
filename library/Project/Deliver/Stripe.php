<?php

define( 'TEST_MODE', false );

/** Connect Stripe-php library */
require_once Zend_Registry::get('config')->path->absolute->library . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

class Project_Deliver_Stripe {

	private static $account_id = 'acct_1040Vj4lTQbl4K0W';

	private static $client_id = array(
		'test' => 'ca_GnIy7t2uyVGCKZ3NgqM6AMj9qxZ1TX5M',
		'live' => 'ca_GnIyC2ChhfbwqRhAuVG7bV5EGEWBucbx'
	);

	private static $secret_key = array(
		'test' => 'sk_test_E3HvmFw7oI4RKFJRJKbHUXYp',
		'live' => 'sk_live_kko3NSIj6Ukq2BN9mr78docz'
	);

	private static $public_key = array(
		'test' => 'pk_test_abE4wWit7vTWFkkFohwdGldW',
		'live' => 'pk_live_1zGuYOA45O8enC1efRvlogJy'
	);

	/** Return secret key */
	public static function getSecretKey() {
		if( TEST_MODE ) {
			return self::$secret_key['test'];
		}

		return self::$secret_key['live'];
	}

	/** Return public key */
	public static function getPublicKey() {
		if( TEST_MODE ) {
			return self::$public_key['test'];
		}

		return self::$public_key['live'];
	}

	/** Return [cliend_id] for link auth link the stripe */
	public static function getClientId() {
		if( TEST_MODE ) {
			return self::$client_id['test'];
		}

		return self::$client_id['live'];
	}

	/** Return [account_id] */
	public static function getAccountId() {
		return self::$account_id;
	}

	/** Return info about account created in stripe.com
	 * 
	 * @param string $account_id
	 * @return mixed Return object of data account or array with message error
	 */
	public static function getAccountInfo( $account_id ) {
		/** Set the secret API key */
		$stripe = new \Stripe\StripeClient( self::getSecretKey() );

		try {
			$account = $stripe->accounts->retrieve( $account_id );

			return $account;
		} catch ( Exception $e ) {
			return [ 'error' => true, 'message' => $e->getMessage() ];
		}
	}

	/** Return info about file from stripe.com
	 * 
	 * @param string $account_id
	 * @return mixed Return object of data account or array with message error
	 */
	public static function getFileData( $file_id ) {
		$stripe = new \Stripe\StripeClient( self::getSecretKey() );

		try {
			$fileData = $stripe
				->files
				->retrieve( $file_id );

			return $fileData;
		} catch (  Exception $e  ) {
			return [ 'error' => true, 'message' => $e->getMessage() ];
		}
	}

	/** Get connected user id in stripe.com */
	public static function getStripeAccountId( $user_id = false ) {
		$deliver = new Project_Deliver();

		if( ! empty( $user_id ) ) {
			$deliver->withUserId( $user_id );
		} else {
			$deliver->onlyOwner();
		}

		$deliver
			->onlyOne()
			->getList( $userData );
		
		if( empty( $userData ) ) return false;

		return $userData['stripe_user_id'];
	}

	/** Create new or update exist the product in stripe.com
	 * 
	 * @param {array} - Array of options for product
	 * @return {mixed} - Returned the object or array with errors
	 */
	public static function setProduct( $options ){
		$stripe_account = self::getStripeAccountId();

		if( ! $stripe_account ) return [
			'status' => false,
			'error' => 'Empty {stripe account}'
		];

		/** Set the secret API key */
		$stripe = new \Stripe\StripeClient( self::getSecretKey() );
		
		try {
			if( isset( $options['stripe_product_id'] ) ) {
				$product = $stripe
					->products
					->update(
						$options['stripe_product_id'],
						[ 'name' => $options['name'] ],
						[ 'stripe_account' => $stripe_account ]
					);
			} else {
				$product = $stripe
					->products
					->create(
						[ 'name' => $options['name'] ],
						[ 'stripe_account' => $stripe_account ]
					);
			}
		
			return $product->id;
		} catch( Exception $e ) {
			return [
				'status' => false,
				'error' => $e->getMessage()
			];
		}
	}

	/** Create a new price on stripe.com
	 * 
	 * @param {array} - Array of params for the price
	 * @return {mixed} - Returned the object or array with errors
	 */
	public static function setPrice( $options ) {
		$stripe_account = self::getStripeAccountId();
		
		if( ! $stripe_account ) return [
			'status' => false,
			'error' => 'Empty {stripe account}'
		];

		/** Set the secret API key */
		$stripe = new \Stripe\StripeClient( self::getSecretKey() );
		
		try {
			$price = $stripe
				->prices
				->create(
					[
						'unit_amount' => floatval( $options['amount'] ) * 100,
						'currency' => $options['currency'],
						'recurring' => [
							'interval' => $options['interval']
						],
						'product' => $options['stripe_product_id'],
					],
					[ 'stripe_account' => $stripe_account ]
				);
			return $price->id;
		} catch( Exception $e ) {
			return [
				'status' => false,
				'error' => $e->getMessage()
			];
		}
	}

	/** Return info about product created in stripe.com */
	public static function getInfoProduct( $stripe_product_id ) {
		if( empty( $stripe_product_id ) ) return false;

		$connected_stripe_account_id = self::getStripeAccountId();
		if( ! $connected_stripe_account_id ) return false;

		\Stripe\Stripe::setApiKey( self::getSecretKey() );

		$product = \Stripe\Product::retrieve( $stripe_product_id, [ 'stripe_account' => $connected_stripe_account_id ] );

		return $product;
	}

	public static function getInfoPlan( $stripe_plan_id ) {
		if( empty( $stripe_plan_id ) ) return false;

		$connected_stripe_account_id = self::getStripeAccountId();
		if( ! $connected_stripe_account_id ) return false;

		\Stripe\Stripe::setApiKey( self::getSecretKey() );

		$plan = \Stripe\Plan::retrieve( 
			$stripe_plan_id, [ 'stripe_account' => 'acct_1GT5svBgD1gSGU6w' ] 
		);

		return $plan;
	}

	public static function checkoutOneTime( $data ) {
		$connected_stripe_account_id = self::getStripeAccountId();
		if( ! $connected_stripe_account_id ) return false;

		\Stripe\Stripe::setApiKey( self::getSecretKey() );

		$sessionData = [
			'payment_method_types' => ['card'],
			'line_items' => [
				[
					'name' => $data['name'],
					'amount' => $data['amount'],
					'currency' => $data['currency'],
					'quantity' => 1,
					'images' => $data['logo']
				]
			],
			'customer' => $data['customer_id'],
			'customer_email' => $data['customer_email'],
			'payment_intent_data' => [
				'application_fee_amount' => $data['fee'],
			],
			'success_url' => ( ! empty( $_SERVER['HTTPS'] ) && 'off' !== strtolower( $_SERVER['HTTPS'] ) ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . '/deliver/checkout?success&session_id={CHECKOUT_SESSION_ID}',
			'cancel_url' => ( ! empty( $_SERVER['HTTPS'] ) && 'off' !== strtolower( $_SERVER['HTTPS'] ) ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . '/deliver/checkout?cancel&session_id={CHECKOUT_SESSION_ID}',
		];

		if( ! empty( $data['shipping_address_collection'] ) ) {
			$sessionData['shipping_address_collection'] = $data['shipping_address_collection'];
		}

		$session = \Stripe\Checkout\Session::create(
			$sessionData, 
			['stripe_account' => $connected_stripe_account_id]
		);

		return $session;
	}

	public static function getCheckoutSession( $stripe_session_id, $stripe_account ) {
		\Stripe\Stripe::setApiKey( self::getSecretKey() );

		$data = \Stripe\Checkout\Session::retrieve(
			$stripe_session_id,
			['stripe_account' => $stripe_account]
		);

		return $data;
	} 

	/** Create customer in stripe.com */
	public static function setCustomer( $data, $stripe_account ) {
		\Stripe\Stripe::setApiKey( self::getSecretKey() );

		$customer = \Stripe\Customer::create(
			$data,
			[ 
				'stripe_account' => $stripe_account,
				'stripe_version' => '2020-03-02'
			]
		);

		return $customer;
	}

	public static function getCustomer( $customer_id, $stripe_account ) {
		\Stripe\Stripe::setApiKey( self::getSecretKey() );

		try {
			$customer = \Stripe\Customer::retrieve(
				$customer_id,
				[
					'stripe_account' => $stripe_account,
					'stripe_version' => '2020-03-02'
				]
			);
	
			return $customer;
		} catch( Exception $e ) {
			return [ 'error' => [ [ 'message' => $e->getMessage() ] ] ];
		}
	}

	/** Create a new subscription on site stripe.com
	 * 
	 * @param {array} - $data
	 * @param {string} - $stripe_account
	 * 
	 * @return {object}
	 */
	public static function setSubscription( $data, $stripe_account ) {
		$stripe = new \Stripe\StripeClient( self::getSecretKey() );

		try {
			$subscription = $stripe
				->subscriptions
				->create( 
					$data,
					[ 
						'stripe_account' => $stripe_account,
						'stripe_version' => '2020-03-02' 
					]
				);

			return $subscription;
		} catch( Exception $e ) {
			return [ 'error' => [ [ 'message' => $e->getMessage() ] ] ];
		}
		
	}

	public static function getSubscription( $subscriptionId, $stripe_account ) {
		\Stripe\Stripe::setApiKey( self::getSecretKey() );

		$subscription = \Stripe\Subscription::retrieve( $subscriptionId, [
			'stripe_account' => $stripe_account,
			'stripe_version' => '2020-03-02'
		] );

		return $subscription;
	}

	/** Getting the payment intent data from system a stripe.com
	 * 
	 * @param {string} - Payment Intent ID from system a stripe.com
	 * @param {string} - Connected the stripe account ID
	 * 
	 * @return {mixed} - Returned object or array of errors
	 */
	public static function getPaymentIntent( $paymentIntentId, $stripe_account ) {
		\Stripe\Stripe::setApiKey( self::getSecretKey() );

		try {
			$paymentIntent = \Stripe\PaymentIntent::retrieve( 
				$paymentIntentId,
				[
					'stripe_account' => $stripe_account,
					'stripe_version' => '2020-03-02'
				] 
			);

			return $paymentIntent;
		} catch( Exception $e ) {
			return [
				'error' => [
					[
						'message' => $e->getMessage()
					]
				]
			];
		}
	}

	/** Create new or update exist the Payment Intent in stripe.com
	 * 
	 * @param {array} - Array of options for the Payment Intent
	 * @return {mixed} - Returned the object or array with errors
	 */
	public static function setPaymentIntent( $data, $stripe_account ) {
		$stripe = new \Stripe\StripeClient( self::getSecretKey() );

		try {
			if( ! empty( $data['id'] ) ) {
				$pid = $data['id'];
				unset( $data['id'] );
			
				$intent = $stripe->paymentIntents->update(
					$pid,
					$data,
					[
						'stripe_account' => $stripe_account,
						'stripe_version' => '2020-03-02' 
					]
				);
			} else {
				$intent = $stripe->paymentIntents->create(
					$data,
					[ 
						'stripe_account' => $stripe_account,
						'stripe_version' => '2020-03-02' 
					]
				);
			}
	
			return $intent;
		} catch(Exception $e) {
			return [
				'error' => [
					[
						'message' => $e->getMessage()
					]
				]
			];
		}
	}

	/** Getting the payment method data from system a stripe.com
	 * 
	 * @param {string} - Payment Method ID from system a stripe.com
	 * @return {mixed} - Returned the object or array with errors
	 */
	public static function getPaymentMethod( $payment_method_id, $stripe_account ) {
		\Stripe\Stripe::setApiKey( self::getSecretKey() );
		
		try {
			$paymentMethod = \Stripe\PaymentMethod::retrieve( 
					$payment_method_id,
					[
						'stripe_account' => $stripe_account,
						'stripe_version' => '2020-03-02' 
					]
				);
			return $paymentMethod;
		} catch(Exception $e) {
			return [
				'error' => [
					[
						'message' => $e->getMessage()
					]
				]
			];
		}
	}

	/** Create a new Invoice Item on site stripe.com
	 * 
	 * @param {array} - $data
	 * @param {string} - $stripe_account
	 * 
	 * @return {object}
	 */
	public static function setInvoiceItem( $data, $stripe_account ) {
		$stripe = new \Stripe\StripeClient( self::getSecretKey() );

		return $stripe->invoiceItems->create(
			$data,
			[ 'stripe_account' => $stripe_account ]
		);
	}

	public static function refundPayment( $data, $stripe_account ) {
		\Stripe\Stripe::setApiKey( self::getSecretKey() );

		$responce = \Stripe\Refund::create(
			$data,
			[
				'stripe_account' => $stripe_account,
				'stripe_version' => '2020-03-02'
			]
		);

		return $responce;
	}

	public static function retriveInvoice( $invoice_id, $stripe_account ) {
		\Stripe\Stripe::setApiKey( self::getSecretKey() );

		$invoice = \Stripe\Invoice::retrieve(
			$invoice_id,
			[
				'stripe_account' => $stripe_account,
				'stripe_version' => '2020-03-02'
			]
		);

		return $invoice;
	}

	/** Attach a payment method for user
	 * 
	 * @param {string} - User ID from a system stripe.com
	 * @param {string} - Payment Method ID from a system stripe.com
	 * @param {string} - Connected a stripe account ID
	 * 
	 * @return {mixed} - Returned boolean or array for errors
	 */
	public static function attachPaymentMethod( $customer_id, $payment_method_id, $stripe_account ) {
		$stripe = new \Stripe\StripeClient(
			self::getSecretKey()
		);

		try {
			$stripe->paymentMethods->attach(
				$payment_method_id,
				[ 'customer' => $customer_id ],
				[ 'stripe_account' => $stripe_account ]
			);

			$stripe->customers->update(
				$customer_id,
				[
					'invoice_settings' => [
						'default_payment_method' => $payment_method_id
					]
				],
				[ 'stripe_account' => $stripe_account ]
			);

			return true;
		} catch(\Stripe\Exception\CardException $e) {
			return [ 'error' => [ [ 'message' => $e->getError()->message ] ] ];
		} catch (\Stripe\Exception\RateLimitException $e) {
			// Too many requests made to the API too quickly
		} catch (\Stripe\Exception\InvalidRequestException $e) {
			// Invalid parameters were supplied to Stripe's API
		} catch (\Stripe\Exception\AuthenticationException $e) {
			// Authentication with Stripe's API failed
			// (maybe you changed API keys recently)
		} catch (\Stripe\Exception\ApiConnectionException $e) {
			// Network communication with Stripe failed
		} catch (\Stripe\Exception\ApiErrorException $e) {
			// Display a very generic error to the user, and maybe send
			// yourself an email
		} catch (Exception $e) {
			// Something else happened, completely unrelated to Stripe
		}
	}
}