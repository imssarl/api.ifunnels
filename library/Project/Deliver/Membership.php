<?php

class Project_Deliver_Membership extends Core_Data_Storage {

	protected $_table = 'deliver_membership';
	private $_userFilePath = false;
	
	/**
	 * @param [id] ==> Record id in a table
	 * @param [site_id] ==> Site Id
	 * @param [name] ==> Membership name
	 * @param [type] ==> Free or Paid
	 * @param [amount] ==> Amount price
	 * @param [frequency] ==> Frequency [One time / Recurring]
	 * @param [trial_amount] ==> Trial amount
	 * @param [trial_duration] ==> Trial duration
	 * @param [limit_rebills] ==> Limit rebills
	 * @param [add_charges] ==> Additional Charges
	 * @param [add_taxes] ==> Add Taxes
	 * @param [home_page_url] ==> Home page URL
	 * @param [added] ==> Unixtime code when a record is created
	 * @param [edited] ==> Unixtime code when was record is edited
	 */
	protected $_fields = array( 
		'id', 
		'site_id', 
		'user_id', 
		'stripe_account',
		'name', 
		'type', 
		'amount', 
		'frequency', 
		'billing_frequency', 
		'trial_amount', 
		'trial_duration', 
		'limit_rebills', 
		'require_shipping', 
		'allowed_contries', 
		'add_charges', 
		'add_charges_frequency',
		'label_charges', 
		'charges_frequency',
		'add_taxes',
		'home_page_url', 
		'stripe_product_id', 
		'stripe_plan_id', 
		'added', 
		'edited' );

	/** Installing */
	public static function install(){
		Core_Sql::setExec( "DROP TABLE IF EXISTS deliver_membership" );
		Core_Sql::setExec( 
			"CREATE TABLE `deliver_membership` (
				`id` INT(11) NOT NULL AUTO_INCREMENT,
				`site_id` INT(11) NOT NULL DEFAULT '0',
				`user_id` INT(11) NOT NULL DEFAULT '0',
				`stripe_account` VARCHAR(100) NULL DEFAULT NULL,
				`name` VARCHAR(255) NULL DEFAULT NULL,
				`type` TINYINT(4) NULL DEFAULT NULL,
				`amount` INT(11) NULL DEFAULT NULL,
				`frequency` TINYINT(4) NULL DEFAULT NULL,
				`billing_frequency` VARCHAR(11) NULL DEFAULT 'month',
				`trial_amount` INT(11) NULL DEFAULT NULL,
				`trial_duration` INT(11) NULL DEFAULT NULL,
				`limit_rebills` INT(11) NULL DEFAULT NULL,
				`require_shipping` TINYINT(4) NULL DEFAULT '0',
				`allowed_contries` TEXT NULL,
				`add_charges` INT(11) NULL DEFAULT NULL,
				`add_charges_frequency` TINYINT(4) NULL DEFAULT '0',
				`label_charges` VARCHAR(255) NULL DEFAULT NULL,
				`charges_frequency` INT(11) NULL DEFAULT NULL,
				`add_taxes` INT(11) NULL DEFAULT NULL,
				`home_page_url` VARCHAR(255) NULL DEFAULT NULL,
				`stripe_product_id` VARCHAR(255) NULL DEFAULT NULL,
				`stripe_plan_id` VARCHAR(255) NULL DEFAULT NULL,
				`added` INT(11) UNSIGNED NOT NULL DEFAULT '0',
				`edited` INT(11) UNSIGNED NOT NULL DEFAULT '0',
				UNIQUE INDEX `id` (`id`)
			)
			COLLATE='utf8_general_ci'
			ENGINE=InnoDB;" 
		);
	}

	public function beforeSet() {
		$this->_data->setFilter( [ 'trim', 'empty_to_null' ] );

		/** Check field [site_id] */
		if( empty( $this->_data->filtered['site_id'] ) ) {
			return Core_Data_Errors::getInstance()->setError( 'Not selected a site' );
		}

		/** Check field [name] */
		if( empty( $this->_data->filtered['name'] ) ) {
			return Core_Data_Errors::getInstance()->setError( 'Empty name a membership plan' );
		}

		if( $this->_data->filtered['type'] == '1' ) {
			if( empty( $this->_data->filtered['amount'] ) || ! is_numeric( $this->_data->filtered['amount'] ) ) {
				return Core_Data_Errors::getInstance()->setError( 'Input correct amount for membership' );
			} 
		}

		if( ! empty( $this->_data->filtered['require_shipping'] ) && empty( $this->_data->filtered['allowed_contries'] ) ) {
			return Core_Data_Errors::getInstance()->setError( 'Select countries of list or option all' );
		}
		
		/** Set field [user_id] */
		if( empty( $this->_data->filtered['id'] ) ) {
			$this->_data->setElement( 'user_id', Core_Users::$info['id'] );
			$this->_data->setElement( 'stripe_account', Project_Deliver_Stripe::getStripeAccountId() );
		}

		$this
			->withIds( $this->_data->filtered['id'] )
			->onlyOne()
			->getList( $membershipData );

		if( ! empty( $membershipData['stripe_account'] ) && Project_Deliver_Stripe::getStripeAccountId() !== $membershipData['stripe_account'] ) {
			return false;
		}

		// Billing frequency
		if( empty( $this->_data->filtered['billing_frequency'] ) ) {
			$this->_data->setElement( 'billing_frequency', 'month' );
		} elseif ( ! in_array( $this->_data->filtered['billing_frequency'], [ 'week', 'month', 'year' ] ) ) {
			$this->_data->filtered['billing_frequency'] = 'month';
		}

		// Allowed contries
		if( ! empty( $this->_data->filtered['allowed_contries'] ) ) {
			$this->_data->setElement( 'allowed_contries', serialize( $this->_data->filtered['allowed_contries'] ) );
		}

		/** Create/update data about a price/product in stripe.com */
		if( $this->_data->filtered['frequency'] == '1' ) {
			$site = new Project_Deliver_Site();

			$site
				->withIds( $this->_data->filtered['site_id'] )
				->onlyOne()
				->getList( $siteData );

			if( ! empty( $membershipData ) && ! empty( $membershipData['stripe_product_id'] ) && ! empty( $membershipData['stripe_plan_id'] ) ) {
				if( strcmp( $membershipData['name'], $this->_data->filtered['name'] ) !== 0 ) {
					
					/** Update product */
					$product = Project_Deliver_Stripe::setProduct( 
						[ 
							'name' => $this->_data->filtered['name'], 
							'stripe_product_id' => $membershipData['stripe_product_id'] 
						] 
					);

					/** Check for errors */
					if( is_array( $product ) && array_key_exists( 'error', $product ) ) {
						return Core_Data_Errors::getInstance()->setError( $product['error'] );
					}
					
					$this->_data->setElement( 'stripe_product_id', $product );
				} else {
					$this->_data->setElement( 'stripe_product_id', $membershipData['stripe_product_id'] ); 
				}

				/** Was updated someone params of lists amount, billing frequency, additional charges, additional charges frequency or taxes */
				if( 
					$this->_data->filtered['amount'] !== $membershipData['amount'] ||
					$this->_data->filtered['billing_frequency'] !== $membershipData['billing_frequency'] ||
					$this->_data->filtered['add_charges'] !== $membershipData['add_charges'] ||
					$this->_data->filtered['add_charges_frequency'] !== $membershipData['add_charges_frequency'] ||
					$this->_data->filtered['add_taxes'] !== $membershipData['add_taxes']
				) {
					/** Add charges */
					if( ! empty( $this->_data->filtered['add_charges'] ) && $this->_data->filtered['add_charges_frequency'] == '1' ) {
						$this->_data->filtered['amount'] += floatval( $this->_data->filtered['add_charges'] );
					}
			
					/** Add taxes */
					if( ! empty( $this->_data->filtered['add_taxes'] ) ) {
						$this->_data->filtered['amount'] += ( $this->_data->filtered['amount'] * floatval( $this->_data->filtered['add_taxes'] ) / 100 );
					}

					/** Create plan */
					$price = Project_Deliver_Stripe::setPrice( [
						'currency' => strtolower( $siteData['currency'] ),
						'amount' => $this->_data->filtered['amount'], 
						'stripe_product_id' => $this->_data->filtered['stripe_product_id'],
						'interval' => $this->_data->filtered['billing_frequency']
					] );

					/** Check for errors */
					if( is_array( $price ) && array_key_exists( 'error', $price ) ) {
						return Core_Data_Errors::getInstance()->setError( $price['error'] );
					}

					$this->_data->setElement( 'stripe_plan_id', $price );
				}
			} else {
				/** Create product */
				$product = Project_Deliver_Stripe::setProduct( [ 'name' => $this->_data->filtered['name'] ] );
				
				/** Check for errors */
				if( is_array( $product ) && array_key_exists( 'error', $product ) ) {
					return Core_Data_Errors::getInstance()->setError( $product['error'] );
				}

				$this->_data->setElement( 'stripe_product_id', $product );

				/** Add charges */
				if( ! empty( $this->_data->filtered['add_charges'] ) && $this->_data->filtered['add_charges_frequency'] == '1' ) {
					$this->_data->filtered['amount'] += floatval( $this->_data->filtered['add_charges'] );
				}
		
				/** Add taxes */
				if( ! empty( $this->_data->filtered['add_taxes'] ) ) {
					$this->_data->filtered['amount'] += ( $this->_data->filtered['amount'] * floatval( $this->_data->filtered['add_taxes'] ) / 100 );
				}

				/** Create plan */
				$price = Project_Deliver_Stripe::setPrice( [
					'currency' => strtolower( $siteData['currency'] ),
					'amount' => $this->_data->filtered['amount'], 
					'stripe_product_id' => $this->_data->filtered['stripe_product_id'],
					'interval' => $this->_data->filtered['billing_frequency']
				] );

				/** Check for errors */
				if( is_array( $price ) && array_key_exists( 'error', $price ) ) {
					return Core_Data_Errors::getInstance()->setError( $price['error'] );
				}

				$this->_data->setElement( 'stripe_plan_id', $price );
			}
		}

		return true;
	}

	public function del(){
		$this
			->onlyOne()
			->getList( $recData );

		if( ! empty( $recData ) ) {
			if( ! empty( $recData['logo'] ) ){
				unlink( Zend_Registry::get('config')->path->absolute->root . $recData['logo'] );
			}

			$this->withIds( $recData['id'] );
			parent::del();
		}
	}

	private $_withSiteId = false;
	public function withSiteId( $site_id ) {
		if( ! empty( $site_id ) ) {
			$this->_withSiteId = $site_id;
		}

		return $this;
	}

	private $_withSiteName = false;
	public function withSiteName() {
		$this->_withSiteName = true;
		return $this;
	}

	protected function assemblyQuery() {
		parent::assemblyQuery();

		if( ! empty( $this->_withSiteId ) ){
			$this->_crawler->set_where( 'd.site_id=' . Core_Sql::fixInjection( $this->_withSiteId ) );
		}

		if( ! empty( $this->_withConnectedMember ) ) {
			$this->_crawler->clean_select();
			$this->_crawler->set_select( 'd.name, d.id, d.frequency, p.subscription_id as subscription_id, p.status as status, p.id as payment_id' );
			$this->_crawler->set_from( 'INNER JOIN deliver_payment p ON p.customer_id = ' . Core_Sql::fixInjection( $this->_withConnectedMember ) . ' AND d.id = p.plan_id' );
		}

		if( $this->_withSiteName ) {
			$this->_crawler->set_select( 's.name as site_name' );
			$this->_crawler->set_from( 'INNER JOIN deliver_site s ON s.id = d.site_id' );
		}
	}

	private $_withConnectedMember = false;
	public function withConnectedCustomer( $member_id ) {
		if( ! empty( $member_id ) ) {
			$this->_withConnectedMember = $member_id;
		}

		return $this;
	}

	protected function init() {
		$this->_withSiteId = false;
		$this->_withConnectedMember = false;
		$this->_withSiteName = false;
	}

	public function getList( &$mixRes ){
		parent::getList( $mixRes );

		if( array_key_exists( '0', $mixRes ) ) {
			foreach( $mixRes as &$item ) {
				if( ! empty( $item['allowed_contries'] ) ) {
					$item['allowed_contries'] = unserialize( $item['allowed_contries'] );
				}
			}
		} else {
			if( ! empty( $mixRes ) ) {
				$mixRes['allowed_contries'] = unserialize( $mixRes['allowed_contries'] );
			}
		}

		$this->init();
		return $this;
	}

	public static function getCheckout( $planData ) {
		if( empty( $planData ) ) return false;

		$data = [];
		$payment = new Project_Deliver_Payment();

		$site = new Project_Deliver_Site();
		$site
			->withIds( $planData['site_id'] )
			->onlyOne()
			->getList( $siteData );


		$data['name'] = $planData['name'];
		$data['amount'] = floatval( $planData['amount'] ) * 100;

		if( ! empty( $planData['add_charges'] ) ) {
			$data['amount'] += floatval( $planData['add_charges'] ) * 100;
		}

		if( ! empty( $planData['add_taxes'] ) ) {
			$data['amount'] += ( $data['amount'] * floatval( $planData['add_taxes'] ) / 100 );
		}

		$data['currency'] = strtolower( $siteData['currency'] );

		if( ! empty( $siteData['logo'] ) ) {
			$data['logo'][] = 'https://app.ifunnels.com' .  $siteData['logo'];
		}

		$data['fee'] = intval( $data['amount'] * 4 / 100 );

		if( ! empty( $planData['require_shipping'] ) ) {
			$data['shipping_address_collection']['allowed_countries'] = $planData['allowed_contries'];
		}

		$session = Project_Deliver_Stripe::checkoutOneTime( $data );
		
		$payment
			->setEntered( 
				[ 
					'site_id' => $siteData['id'],
					'user_id' => $planData['user_id'],
					'plan_id' => $planData['id'], 
					'type_payment' => '0',
					'one_payment_id' => $session->id,
					'status' => 'proceed'
				] 
			)
			->set();

		return $session;
	}
}