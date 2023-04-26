<?php
class Project_Sites_Profiles extends Core_Storage {

	public $fields=array(
		'id', 'profile_name', 'show_google_ads', 'show_yahoo_ads', 'show_search_feed', 'show_chitika', 'show_subscribe', 'show_amazon_ads', 
		'show_parteners', 'show_bestseller', 'show_best_products', 'show_centers', 'show_right', 'show_submit_article_form', 'no_of_results', 
		'switch', 'adsense_id', 'adsense_channel', 'yahoo_id', 'yahoo_channel', 'chitika_id', 'chitika_channel', 'clickbank_id', 'search_feed_id', 
		'search_feed_track_id', 'amazon_country', 'amazon_associates_id', 'no_of_amazon_products', 'ebayaffid', 'first_name', 'last_name', 
		'email', 'autoresponder_email', 'url_of_landingpage', 'date_created', 'user_id'
	);
	public $table='hct_profiles';
	protected $_link=false; // тут линк нам не нужен

	public function __construct() {
		if ( !Zend_Registry::get( 'objUser' )->getId( $_int ) ) {
			throw new Exception( Core_Errors::DEV.'|Zend_Registry::get( \'objUser\' )->getId( $_int ) is not return an User Id' );
			return;
		}
		$this->_userId=$_int;
	}

	public function set() {
		if ( !$this->_data->setFilter( array( 'trim', 'clear' ) )->setChecker( array(
			'profile_name'=>empty( $this->_data->filtered['profile_name'] ),
			'first_name'=>empty( $this->_data->filtered['first_name'] ),
			'last_name'=>empty( $this->_data->filtered['last_name'] ),
			'no_of_results'=>empty( $this->_data->filtered['no_of_results'] ),
			'adsense_id'=>empty( $this->_data->filtered['adsense_id'] ),
		) )->check() ) {
			$this->_data->getErrors( $this->_errors['filtered'] );
			return false; 
		}
		if ( empty( $this->_data->filtered['id']) ) {
			$this->_data->setElement( 'user_id', $this->_userId );
			$this->_data->setElement( 'date_created', date('Y-m-d',time()));
		}
		$this->_data->setElement( 'id', Core_Sql::setInsertUpdate( $this->table, $this->_data->setMask( $this->fields )->getValid() ) );
		return true;
	}

	public function changeSomeFields( &$arrRes ) {
		$arrRes['profile_name']=$arrRes['profile_name'].'_duplicated';
	}

	public function getOwnerId() {
		return $this->_userId;
	}

	// всегда отображать профайлы только user_id
	public function getList( &$mixRes ) {
		$this->onlyOwner();
		return parent::getList( $mixRes );
	}
}
?>