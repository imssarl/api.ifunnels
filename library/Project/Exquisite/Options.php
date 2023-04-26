<?php


/**
 * Project_Exquisite_Options
 */

class Project_Exquisite_Options extends Core_Data_Storage{

	protected $_table='ulp_options';
	protected $_fields=array('id', 'user_id', 'options_key', 'options_value');
	
	public static $defaultOptions=array(
		"version" => '1.00',
		"cookie_value" => 'exquisitecookie',
		"csv_separator" => ";",
		"email_validation" => "off",
		"ga_tracking" => "off",
		"fa_enable" => "off",
		"aweber_consumer_key" => "",
		"aweber_consumer_secret" => "",
		"aweber_access_key" => "",
		"aweber_access_secret" => "",
		"mailchimp_enable" => "off",
		"mailchimp_api_key" => "",
		"mailchimp_list_id" => "",
		"mailchimp_double" => "off",
		"mailchimp_welcome" => "off",
		"icontact_enable" => "off",
		"icontact_appid" => "",
		"icontact_apiusername" => "",
		"icontact_apipassword" => "",
		"icontact_listid" => "",
		'campaignmonitor_enable' => "off",
		'campaignmonitor_api_key' => '',
		'campaignmonitor_list_id' => '',
		'getresponse_enable' => "off",
		'getresponse_api_key' => '',
		'getresponse_campaign_id' => '',
		'aweber_enable' => "off",
		'aweber_listid' => "",
		'madmimi_enable' => 'off',
		'madmimi_login' => '',
		'madmimi_api_key' => '',
		'madmimi_list_id' => '',
		'sendy_enable' => 'off',
		'sendy_url' => '',
		'sendy_listid' => '',
		'benchmark_enable' => 'off',
		'benchmark_api_key' => '',
		'benchmark_list_id' => '',
		'benchmark_double' => 'off',
		'activecampaign_enable' => 'off',
		'activecampaign_url' => '',
		'activecampaign_api_key' => '',
		'activecampaign_list_id' => '',
		'interspire_enable' => 'off',
		'interspire_url' => '',
		'interspire_username' => '',
		'interspire_token' => '',
		'interspire_listid' => '',
		'interspire_nameid' => '',
		"social2_facebook_appid" => "",
		"social2_google_clientid" => "",
		"social2_google_apikey" => "",
	);
	
	public function get_options( &$options=array()) {
		$options=array();
		$this->onlyOwner()->getList($options);
		$options=array_merge(self::$defaultOptions, $options);
		return $this;
	}
	
	public function update_options( $_options=array() ) {
		foreach ($_options as $_key => $_value) {
			$this->addOption( $_key, $_value );
		}
	}
	
	public function getList( &$options ) {
		if ( !empty( $this->_withKey ) ) {
			parent::getList( $options );
			return $this;
		}
		parent::getList( $mixRes );
		$options=array();
		foreach ($mixRes as $row) {
			if (array_key_exists($row['options_key'], self::$defaultOptions))
				$options[$row['options_key']]=$row['options_value'];
		}
		return $this;
	}
	
	public function addOption( $key, $value ) {
		$this->withKey( $key )->onlyOwner()->get( $option );
		if( !empty( $option ) ){
			if( $value != '' ){
				$this->setEntered( array(
					'id'=>$option['id'],
					'options_value'=>$value,
				) )->set();
			}else{
				$this->withIds( $option['id'] )->del();
			}
		}elseif( $value != '' ){
			$this->setEntered( array(
				'user_id'=>Core_Users::$info['id'],
				'options_key'=>$key,
				'options_value'=>$value,
			) )->set();
		}
	}
	
	protected $_withKey=false;
	
	public function withKey( $_key=false ) {
		$this->_withKey=$_key;
		return $this;
	}
	
	protected function assemblyQuery() {
		parent::assemblyQuery();
		if ( !empty( $this->_withKey ) ) {
			$this->_crawler->set_where( 'd.options_key = '.Core_Sql::fixInjection( $this->_withKey ) );
		}
	}
	
	protected function init() {
		parent::init();
		$this->_withKey=false;
	}
}
?>