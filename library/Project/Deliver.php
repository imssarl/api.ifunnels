<?php

define( 'TEST_MODE', true );

/** Connect Stripe-php library */
require_once Zend_Registry::get('config')->path->absolute->library . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

class Project_Deliver extends Core_Data_Storage {
	private static $client_id = array(
		'test' => 'ca_GnIy7t2uyVGCKZ3NgqM6AMj9qxZ1TX5M',
		'live' => 'ca_GnIyC2ChhfbwqRhAuVG7bV5EGEWBucbx'
	);

	private static $secret_key = array(
		'test' => 'sk_test_E3HvmFw7oI4RKFJRJKbHUXYp',
		'live' => 'sk_live_kko3NSIj6Ukq2BN9mr78docz'
	);

	protected $_table = 'deliver_stripe';
	
	/**
	 * @param [id] ==> Record id in a table
	 * @param [user_id] ==> User Id in the iFunnel
	 * @param [client_id] ==> Company Id from stripe.com
	 * @param [stripe_user_id] ==> User Id from stripe.com
	 * @param [status] ==> Сonnected or not connected, values [0 - connected, 1 - not connected]
	 * @param [settings] ==> Response in base64 from stripe.com
	 * @param [added] ==> Unixtime code when a record is created
	 */
	protected $_fields = array( 'id', 'user_id', 'client_id', 'stripe_user_id', 'status', 'settings', 'added' );

	/** Installing */
	public static function install(){
		Core_Sql::setExec( "DROP TABLE IF EXISTS deliver_stripe" );
		Core_Sql::setExec( 
			"CREATE TABLE `deliver_stripe` (
				`id` INT(11) NOT NULL AUTO_INCREMENT,
				`user_id` INT(11) NOT NULL DEFAULT '0',
				`client_id` VARCHAR(100) NULL DEFAULT NULL,
				`stripe_user_id` VARCHAR(100) NULL DEFAULT NULL,
				`status` TINYINT NULL DEFAULT '0',
				`settings` TEXT NULL,
				`added` INT(11) UNSIGNED NOT NULL DEFAULT '0',
				UNIQUE INDEX `id` (`id`)
			)
			COLLATE='utf8_general_ci'
			ENGINE=InnoDB;" 
		);

		return $this;
	}

	/** Return secret key */
	private static function getSecretKey() {
		if( TEST_MODE ) {
			return self::$secret_key['test'];
		}

		return self::$secret_key['live'];
	}

	public function beforeSet() {
		$this->_data->setFilter( array( 'clear' ) );

		/** Set field [status] */
		$this->_data->setElement( 'status', ( empty( $this->_data->filtered['settings']->stripe_user_id ) ? 0 : 1 ) );
		
		/** Set field [user_id] */
		$this->_data->setElement( 'user_id', Core_Users::$info['id'] );

		/** Set field [stripe_user_id] */
		if( ! empty( $this->_data->filtered['settings']->stripe_user_id ) ) {
			$this->_data->setElement( 'stripe_user_id', $this->_data->filtered['settings']->stripe_user_id );
		}

		$this->_data->setElement( 'client_id', self::getClientId() );

		/** Encode [setting] field */
		if( ! empty( $this->_data->filtered['settings'] ) ) {
			$this->_data->setElement( 'settings', base64_encode( serialize( $this->_data->filtered['settings'] ) ) );
		}

		return true;
	}

	public static function getAuthData( $code ) {
		\Stripe\Stripe::setApiKey( self::getSecretKey() );

		$response = \Stripe\OAuth::token([
			'grant_type' => 'authorization_code',
			'code' => $code,
		]);

		return $response;
	}

	/** Return [cliend_id] for link auth link the stripe */
	public static function getClientId( ) {
		if( TEST_MODE ) {
			return self::$client_id['test'];
		}

		return self::$client_id['live'];
	}

	/** Return [redirect_uri] for auth link the stripe */
	public static function getRedirectUrl() {
		return urlencode( sprintf( 
			'%s://%s%s', 
			( ! empty( $_SERVER['HTTPS'] ) && 'off' !== strtolower( $_SERVER['HTTPS'] ) ? 'https' : 'http' ),
			$_SERVER['SERVER_NAME'], 
			Core_Module_Router::getCurrentUrl( 
				array( 
					'name' => 'site1_deliver', 
					'action' => 'settings' 
				) 
			) 
		) );
	}

	private $_withUserId = false;
	public function withUserId( $user_id ) {
		if( ! empty( $user_id ) ) {
			$this->_withUserId = $user_id;
		}

		return $this;
	}

	private $_withAccountInfo = false;
	public function withAccountInfo() {
		$this->_withAccountInfo = true;

		return $this;
	}

	protected function assemblyQuery() {
		parent::assemblyQuery();

		if( ! empty( $this->_withUserId ) ){
			$this->_crawler->set_where( 'd.user_id=' . Core_Sql::fixInjection( $this->_withUserId ) );
		}
	}

	protected function init() {
		$this->_withUserId = false;
	}

	public function getList( &$mixRes ){
		parent::getList( $mixRes );

		if( !empty( $mixRes ) ) {
			if( array_key_exists( '0', $mixRes ) ) {
				foreach( $mixRes as &$item ) {
					$item['settings'] = unserialize( base64_decode( $item['settings'] ) );
				}
			} else {
				$mixRes['settings'] = unserialize( base64_decode( $mixRes['settings'] ) );
	
				if( $this->_withAccountInfo ) {
					/** Set secret API key */
					\Stripe\Stripe::setApiKey( self::getSecretKey() );
					
					/** Getting account data */
					$stripe_account_data = \Stripe\Account::retrieve( $mixRes['stripe_user_id'] /*'acct_1040Vj4lTQbl4K0W'*/  );
	
					$mixRes['company_data'] = array(
						'business_name' => $stripe_account_data->business_name,
						'support_email' => $stripe_account_data->support_email,
						'support_url' => $stripe_account_data->support_url
					);
	
					$this->_withAccountInfo = false;
				}
			}
		}

		$this->init();
		return $this;
	}
}