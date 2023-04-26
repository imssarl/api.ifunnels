<?php
class Project_Mooptin_Everwebinar{
	private $api_key; // api_key* string(64)
	private $api_url = 'https://webinarjam.genndi.com/api/everwebinar/';//'https://app.webinarjam.com/api/v2/ever';
	private $timeout = 8;
	public $http_status;

	/**
	 * Set api key and optionally API endpoint
	 * @param	  $api_key
	 * @param null $api_url
	 */
	public function __construct($api_key)
	{
		$this->api_key = $api_key;
	}
	/**
	 * We can modify internal settings
	 * @param $key
	 * @param $value
	 */
	function __set($key, $value)
	{
		$this->{$key} = $value;
	}
	/**
	 * Retrieve a	full	list	of	all	webinars published	in	your	account
curl --data "api_key=demokey"
https://app.webinarjam.com/api/v2/ever/webinars


{
 "status": "success",
 "webinars": [
 {
 "webinar_id": "demo123",
 "name": "name of webinar",
 "description": "description of webinar",
"schedules": [
 "Every Day 18:30 PM",
 "Every Wednesday 19:00 PM",
 "Mon, 6 Jul 20:01 PM"
 ],
 "timezone": "America/Chicago",
 },
 ]
}
	 * @return mixed
	 */
	public function getCampaigns(){
		return $this->call('webinars');
	}

	/**
	 * Get	details	about	one	particular webinar from	your	account
curl --data "api_key=demokey"
https://app.webinarjam.com/api/v2/ever/webinars


{
 "status": "success",
 "webinars": [
 {
 "webinar_id": "demo123",
 "name": "name of webinar",
 "description": "description of webinar",
"schedules": [
 "Every Day 18:30 PM",
 "Every Wednesday 19:00 PM",
 "Mon, 6 Jul 20:01 PM"
 ],
 "timezone": "America/Chicago",
 },
 ]
}
	 * @return mixed
	 */
	public function getCampaign( $_webId ){
		return $this->call('webinar', array( 'webinar_id'=>$_webId ) );
	}
	/**
	 * Register	a	person	to	a	specific	webinar
	 *
	 
Example CURL request:
curl --data
"api_key=demokey&webinar_id=demo123&name=FirstName&email=test
@email.com&schedule=0"
https://app.webinarjam.com/api/v2/ever/register
	 
	 
webinar_id string Webinar ID
user_id int Attendee Internal ID
name string Attendee Name
email string Attendee Email
schedule int Attendee Schedule
date string Webinar date and time
	 */
	public function addContact( $params ){
		return $this->call('register', $params);
	}
	/**
	 * Curl run request
	 *
	 * @param null $api_method
	 * @param string $http_method
	 * @param array $params
	 * @return mixed
	 * @throws Exception
	 */
	private function call($api_method = null, $params = array()){
		if (empty($api_method)) {
			return (object)array(
				'httpStatus' => '400',
				'code' => '1010',
				'codeDescription' => 'Error in external resources',
				'message' => 'Invalid api method'
			);
		}
		$params['api_key']=$this->api_key;
		//$params = json_encode($params);
		$url = $this->api_url.$api_method;
		$options = array(
			CURLOPT_URL => $url,
			CURLOPT_FRESH_CONNECT => 1,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT => $this->timeout,
			CURLOPT_HEADER => false,
			CURLOPT_USERAGENT => 'PHP EverWebinar client 0.0.1',
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => http_build_query( $params ),
			CURLOPT_SSL_VERIFYPEER => true,
			CURLOPT_SSL_VERIFYHOST => true
		);
	//	if( $_SERVER['HTTP_HOST'] == 'cnm.local' ){
			$options[CURLOPT_CAINFO]=getcwd().'/cacert.pem';
	//	}else{
	//		$options[CURLOPT_CAINFO]='/etc/ssl/'.Zend_Registry::get( 'config' )->domain->host.'/members_creativenichemanager_info.crt';
		//	$options[CURLOPT_CAPATH]='/etc/ssl/'.Zend_Registry::get( 'config' )->domain->host.'/';
	//	}
		$curl = curl_init();
		curl_setopt_array($curl, $options);
		$_data=curl_exec($curl);
		$response = json_decode($_data);

//p( array( $options, $_data, curl_error($curl) ) );

		$this->http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);
		return (object)$response;
	}
}