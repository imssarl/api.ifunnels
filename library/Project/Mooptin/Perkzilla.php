<?php
class Project_Mooptin_Perkzilla{
	private $api_key;
	private $api_url = 'https://perkzilla.com/app/apiauth.php';

	public function __construct($api_key){
		$this->api_key = $api_key;
	}
	/**
	* We can modify internal settings
	* @param $key
	* @param $value
	*/
	function __set($key, $value){
		$this->{$key} = $value;
	}
	/**
	* Return all campaigns
	* @return 
[ 
   { 
      "id":"1",
      "campaign_name":"Campaign1"
   },
   { 
      "id":"2",
      "campaign_name":"Campaign2"
   },
]
	*/
	public function getCampaigns(){
		return $this->call('get_campaigns');
	}
	/**
	* add single contact into your campaign
	*
	* @param $params
	* @return 
{  
  "message":"User Subscribed Successfully"
}

{
  "message":"Subscriber Email Already Exist"
}

{
  "message":"Invalid campaign"
}
	*/
	public function addContact($params){
		/*
		$params=array(
			'campaign_id' => 111,
			'variables' => array(
            'subscriber_name'   => 'John Doe',
            'subscriber_email'  => 'johndoe@example.com',
            'subscriber_IP'     => '111.222.333.444',
            'referred_by_email' => 'userone@example.com',
            'referred_by_refID' => '3d2'
			)
		);
		
		*/
		return $this->call('subscribe_user', $params);
	}

	public function getSubscriberDetails($refID){
		return $this->call('get_subscriber_details', array( 'refID'=>$refID ));
	}

	/**
	* Curl run request
	*/
	private function call($action = null, $params = array()){
		if (empty($action)) {
			return (object)array(
				'httpStatus' => '400',
				'code' => '1010',
				'codeDescription' => 'Error in external resources',
				'message' => 'Invalid api method'
			);
		}
		$_postFields=array(
			'api_key'=>$this->api_key,
			'action'=>$action
		);
		if( !empty( $params ) ){
			$_postFields=$params+$_postFields;
		}
		$options = array(
			CURLOPT_USERAGENT => $_SERVER['HTTP_USER_AGENT'],
			CURLOPT_URL => $this->api_url,
			CURLOPT_POST => 1,
			CURLOPT_POSTFIELDS => http_build_query($_postFields),
			CURLOPT_RETURNTRANSFER => 1,
		);
		$curl = curl_init();
		curl_setopt_array($curl, $options);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);    
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE); 
		$_data=curl_exec($curl);
		$response = json_decode($_data, true);
		$this->http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);
		return $response;
	}
}