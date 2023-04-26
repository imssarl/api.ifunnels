<?php

class Project_Deliver_Checkout extends Core_Data_Storage {

	protected $_table = 'deliver_checkout';
	
	/**
	 * @param [id] ==> Record id in a table
	 * @param [site_id] ==> Site Id
	 * @param [user_id] ==> User Id
	 * @param [stripe_session_id] ==> Stipe Session Checkout Id
	 * @param [status] ==> Stipe Paimentindent Status
	 * @param [response_webhook] ==> Stipe Webhook response
	 * @param [added] ==> Unixtime code when a record is created
	 */
	protected $_fields = array( 'id', 'site_id', 'user_id', 'stripe_session_id', 'status', 'response_webhook', 'added' );

	/** Installing */
	public static function install(){
		Core_Sql::setExec( "DROP TABLE IF EXISTS deliver_checkout" );
		Core_Sql::setExec( 
			"CREATE TABLE `deliver_checkout` (
				`id` INT(11) NOT NULL AUTO_INCREMENT,
				`site_id` INT(11) NOT NULL DEFAULT '0',
				`user_id` INT(11) NOT NULL DEFAULT '0',
				`stripe_session_id` VARCHAR(255) NULL DEFAULT NULL,
				`status` VARCHAR(255) NULL DEFAULT NULL,
				`response_webhook` TEXT NULL DEFAULT NULL,
				`added` INT(11) UNSIGNED NOT NULL DEFAULT '0',
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
		$this->_data->setElement( 'user_id', Core_Users::$info['id'] );

		if( $this->_data->filtered['response_webhook'] ) {
			$this->_data->setElement( 'response_webhook', json_encode( $this->_data->filtered['response_webhook'] ) );
		}

		return true;
	}

	private $_withSessionId = false;
	public function withSessionId( $session_id ) {
		$this->_withSessionId = $session_id;
		return $this;
	}

	protected function assemblyQuery() {
		parent::assemblyQuery();

		if( ! empty( $this->_withSessionId ) ){
			$this->_crawler->set_where( 'd.stripe_session_id=' . Core_Sql::fixInjection( $this->_withSessionId ) );
		}
	}

	protected function init() {
		$this->_withSessionId = false;
	}

	public function getList( &$mixRes ){
		parent::getList( $mixRes );

		$this->init();
		return $this;
	}
}