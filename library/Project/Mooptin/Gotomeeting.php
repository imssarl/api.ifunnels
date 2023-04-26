<?php
class Project_Mooptin_Gotomeeting{
    private $options;
    private $type;
    private $api_url = 'https://api.getgo.com/G2M/rest';
    public $http_status;
    public $http_error;
    public $http_options;

    /**
     * Set api key and optionally API endpoint
     * @param      $api_key
     * @param null $api_url
     */
    public function __construct( $type='oauth' , $options=array() ){
		if( !empty( $options ) ){
			$this->type = $type;
			$this->options = $options;
		}
		if( !isset( $this->options['consumer_key'] ) || empty( $this->options['consumer_key'] ) ){
			$this->options['consumer_key']='uKmIWh3CQ8NAZC4XiUK244JkmyVkBFs6';
		}
		if( !isset( $this->options['consumer_secret'] ) || empty( $this->options['consumer_secret'] ) ){
			$this->options['consumer_secret']='TAbXbqgGGrGQWr0D';
		}
    }

    public function getOptions(){
		return $this->options;
	}

    public function getOrganizers(){
		$this->api_url='https://api.getgo.com/G2W/rest/v2';
        return $this->call('organizers');
    }

    public function getWebinars(){
		$this->api_url='https://api.getgo.com/G2W/rest/v2';
        return $this->call('organizers/'.$this->options['organizer_key'].'/insessionWebinars');
    }

    public function createRegistrants($webinarKey,$params){	
		$this->api_url='https://api.getgo.com/G2W/rest/v2';
        return $this->call('organizers/'.$this->options['organizer_key'].'/webinars/'.$webinarKey.'/registrants', 'POST', $params);
    }

    public function applicationAuthentication(){
		return 'https://api.getgo.com/oauth/v2/authorize?client_id='.$this->options['consumer_key'].'&response_type=code';
	}

    public function userAuthentication(){
        if (empty($this->options['response_key']) || empty( $this->options['consumer_key'] ) || empty( $this->options['consumer_secret'] )) {
            return (object)array(
                'httpStatus' => '400',
                'code' => '1010',
                'codeDescription' => 'Error in external resources',
                'message' => 'Invalid api method'
            );
        }
		return $this->OAuth();
	}
    /**
     * OAuth 2
     *
     * @param null $api_method
     * @param string $http_method
     * @param array $params
     * @return mixed
     * @throws Exception
     */
    private function OAuth(){
        $url = 'https://api.getgo.com/oauth/v2/token';
		$header=array(
			'Content-Type: application/x-www-form-urlencoded',
			'Authorization: Basic '.base64_encode($this->options['consumer_key'].':'.$this->options['consumer_secret']),
		);
        $this->http_options = array(
            CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $header,
			CURLOPT_POST => 1,
			CURLOPT_SSL_VERIFYPEER => 0,
			CURLOPT_POSTFIELDS => 'grant_type=authorization_code&code='.$this->options['response_key']
        );
        $curl = curl_init();
        curl_setopt_array($curl, $this->http_options);
		$_data=curl_exec($curl);
        $response = json_decode($_data);
        $this->http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $this->http_error = curl_error($curl);
        curl_close($curl);
        return $response;
    }
	
	public function getToken(){
        $url = 'https://api.getgo.com/oauth/v2/token';
		$header=array(
			'Content-Type: application/x-www-form-urlencoded',
			'Authorization: Basic '.base64_encode($this->options['consumer_key'].':'.$this->options['consumer_secret']),
		);
        $this->http_options = array(
            CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $header,
			CURLOPT_POST => 1,
			CURLOPT_SSL_VERIFYPEER => 0,
			CURLOPT_POSTFIELDS => 'grant_type=password&username='.$this->options['username'].'&password='.$this->options['password']
        );
        $curl = curl_init();
        curl_setopt_array($curl, $this->http_options);
		$_data=curl_exec($curl);
        $response = json_decode($_data);
        $this->http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $this->http_error = curl_error($curl);
        curl_close($curl);
        return $response;
    }
	
	public function refreshToken(){
        $url = 'https://api.getgo.com/oauth/v2/token';
		$header=array(
			'Content-Type: application/x-www-form-urlencoded',
			'Authorization: Basic '.base64_encode($this->options['consumer_key'].':'.$this->options['consumer_secret']),
		);
        $this->http_options = array(
            CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $header,
			CURLOPT_POST => 1,
			CURLOPT_SSL_VERIFYPEER => 0,
			CURLOPT_POSTFIELDS => 'grant_type=refresh_token&refresh_token='.$this->options['refresh_token']
        );
        $curl = curl_init();
        curl_setopt_array($curl, $this->http_options);
		$_data=curl_exec($curl);
        $response = json_decode($_data, true);
        $this->http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $this->http_error = curl_error($curl);
        curl_close($curl);
        return $response;
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
	var $redirect=0;
    private function call($api_method = null, $http_method = 'GET', $params = array()){
        if (empty($api_method)) {
            return (object)array(
                'httpStatus' => '400',
                'code' => '1010',
                'codeDescription' => 'Error in external resources',
                'message' => 'Invalid api method'
            );
        }
        $url = $this->api_url.'/'.$api_method;
		$header=array(
			'Content-Type: application/json',
//			'Accept: application/vnd.citrix.g2wapi-v1.1+json'
		);
		if( isset( $this->options['access_token'] ) ){
			$header[]='Authorization: ' .$this->options['access_token'];
		}
        $this->http_options = array(
            CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_HTTPHEADER => $header,
        );
        if ($http_method == 'POST') {
            $this->http_options[CURLOPT_POST] = 1;
            $this->http_options[CURLOPT_POSTFIELDS] = $params;
        }
        $curl = curl_init();
        curl_setopt_array($curl, $this->http_options);
		$_data=curl_exec($curl);
        $response = json_decode($_data, true);
        $this->http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		$this->http_error = curl_error($curl);
        curl_close($curl);
		if( isset( $response['int_err_code'] ) && $response['int_err_code']=="InvalidToken" && $this->redirect<1 ){
			$response=$this->refreshToken();
			if( isset( $response['access_token'] ) ){
				$this->options=$response+$this->options;
				$this->redirect++;
				$response=$this->call( $api_method, $http_method, $params );
			}
		}
		if( isset( $response['int_err_code'] ) ){
			$response=array( 'error'=>$response, 'options'=>$this->options, 'http'=>$this->http_options );
		}
		if( isset( $response['errorCode'] ) ){
			$response=array( 'error'=>$response,'http'=>$this->http_options, 'description'=>$response['description'] );
		}
        return $response;
    }
}