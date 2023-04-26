<?php

/**
 * Namecheap API
 * http://www.namecheap.com/support/api/api.aspx
 *
 * @category Project
 * @package Project_Placement_Domen
 * @copyright Copyright (c) 2009-2012, web2innovation
 */
class Project_Placement_Domen_Namecheap {

	const LIST_TYPE_ALL='ALL',LIST_TYPE_EXPIRING='EXPIRING',LIST_TYPE_EXPIRED='EXPIRED';

	const SORT_BY_NAME='NAME',SORT_BY_NAME_DESC='NAME_DESC',SORT_BY_EXPIREDATE='EXPIREDATE',SORT_BY_EXPIREDATE_DESC='EXPIREDATE_DESC',
		SORT_BY_CREATEDATE='CREATEDATE',SORT_BY_CREATEDATE_DESC='CREATEDATE_DESC';
	/**
	 * Url for production server
	 * @var string
	 */
	private $_apiUrl='https://api.namecheap.com/xml.response?ApiUser=#api_username#&ApiKey=#api_key#&UserName=#nc_username#&Command=#cmd_name#&ClientIp=#clientIPaddress#';

	/**
	 * Url for test server
	 * @var bool
	 */
	private $_apiTestUrl='https://api.sandbox.namecheap.com/xml.response?ApiUser=#api_username#&ApiKey=#api_key#&UserName=#nc_username#&Command=#cmd_name#&ClientIp=#clientIPaddress#';

	private $_withListType=false;
	private $_withKeyword=false;
	private $_per_page=false;
	private $_page=false;
	private $_sortBy=false;

	// test data
//	public static $apiUser='pavellivinskiy';
//	public static $apiKey='7b26f190f7ae4952ad5a8431139e4ef7';
//	public static $clientIp='178.26.248.85'; // Local IP
//	public static $clientIp='184.72.240.171'; // Dev IP
//	public static $clientIp='184.72.240.153'; // Prod IP
//	public static $userName='pavellivinskiy';

	public static $apiUser='ethiccash';
	public static $apiKey='817790f9952a488188737b43f30de7ec';//'ec1c6569fb094ab4a155cfd5c1365fdd';
	public static $clientIp='10.133.6.158'; // Prod IP
	public static $userName='ethiccash';
	private $_command=false;

	/**
	 * Core_Data object
	 * @var Core_Data object
	 */
	private $_data=false;

	/**
	 * Errors code from Namecheap API
	 * @var array
	 */
	private $_errorCode=array(
		// Global
		100=>'Can\'t connect to server',
		1010101=>'Parameter APIUser is missing',
		1030408=>'Unsupported authentication type',
		1010104=>'Parameter Command is missing',
		1010102=>'Parameter APIKey is missing',
		1011102=>'Parameter APIKey is missing',
		1010105=>'Parameter ClientIP is missing',
		1050900=>'Unknown error when validating APIUser',
		1011150=>'Parameter RequestIP is invalid',
		1017150=>'Parameter RequestIP is disabled or locked',
		1017105=>'Parameter ClientIP is disabled or locked',
		1017101=>'Parameter ApiUser is disabled or locked',
		1017410=>'Too many declined payments',
		1017411=>'Too many login attempts',
		1019103=>'Parameter UserName is not available',
		1016103=>'Parameter UserName is unauthorized',
		1017103=>'Parameter UserName is disabled or locked',
		// getList
		5019169=>'Unknown exceptions while retriving Domain list',
		// local
		0000100=>'Please fill in your contact information in Account Details. Those are required for registering your domain name correctly.',
		0000101=>'Can\'t build request',
		// Response
		0000102=>'Can\'t get response',
		// check
		3031510=>'Error response from Enom when the error count != 0',
		3011511=>'UnKnown response from Provider',
		// reactivate && renew
		2033409=>'Possibly a logical error in authentication phase. Order chargeable for Username is not found',
		2019166=>'Domain not found',
		2030166=>'Edit permission for Domain is not supported',
		2011170=>'Promotion Code is invalid',
		2011280=>'TLD is invalid',
		2011282=>'Parameter RegistrantPhone is Invalid. Please provide a valid phone number in your Account Details.',
		2528166=>'Order creation failed',
		3024510=>'Error Response from Enom while updating domain',
		3050511=>'Unknown error response from Enom',
		2020166=>'Domain does not meet the expiration date for reactivation',
		2016166=>'Domain is not associate with your account',
		5050900=>'Unhandled exceptions',
		4024166=>'Failed to update domain in your account',
		2020166=>'Domain has expired.Please reactivate your domain',
		3028166=>'Failed to renew error from Enom',
		3050900=>'Unknown error from Enom',
		4024167=>'Failed to update years for your domain',
		4023166=>'Error occured while domain renewal',
		4022337=>'Error in refunding funds',
		// dns
		4022288=>'Unable to get nameserver list',
		// create
		3031166=>'Domain name not available',
		4019166=>'Domain name not available',
		2033407=>'Cannot enable Whoisguard when AddWhoisguard is set as NO.',
		2015267=>'EUAgreeDelete option should not be set as NO',
		2015167=>'Validation error from Years',
		2030280=>'TLD is not supported in API',
		2011168=>'Nameservers are not valid',
		2011322=>'Extended Attributes are not Valid',
		2010323=>'Check required field for billing domain contacts',
		3031900=>'Unknown Response from provider',
		4023271=>'Error while adding free positive ssl for the domain',
		4026312=>'Error in refunding funds',
		5026900=>'Unknown exceptions error while refunding funds',
		2011182=>'Parameter RegistrantPhone is Invalid'
	);

	/**
	 * params:
	 *  ApiUser - Username required to access the API (str.)
	 *	ApiKey - Password required used to access the API (str.)
	 *  UserName - The Username on which a command is executed.Generally, the values of ApiUser and UserName parameters are the same. (str.)
	 *  ClientIp - IP address of the client accessing your application (End-user IP address)
	 *
	 * @param $_arrParams
	 */
	public function __construct( $_arrParams=array() ){
		if( !empty($_arrParams['ApiUser']) ){
			self::$apiUser=$_arrParams['ApiUser'];
		}
		if( !empty($_arrParams['ApiKey']) ){
			self::$apiKey=$_arrParams['ApiKey'];
		}
		if( !empty($_arrParams['UserName']) ){
			self::$userName=$_arrParams['UserName'];
		}
		if( empty($_arrParams['ClientIp']) ){
//			self::$clientIp=$_SERVER['SERVER_ADDR'];
		} else {
			self::$clientIp=$_arrParams['ClientIp'];
		}
		if( !empty($_arrParams['debug']) ){
			$this->_apiUrl=$this->_apiTestUrl;
		}
	}

	/**
	 * Set command.
	 *
	 * @param $_str
	 * @return Project_Placement_Domen_Namecheap
	 * @throws Exception
	 */
	public function setCommand( $_str ){
		if( empty($_str) ){
			throw new Exception('Command can\'t be empty!');
		}
		$this->_command=$_str;
		return $this;
	}

	/**
	 * Set ListType - Possible values are ALL/EXPIRING/EXPIRED
	 * use const LIST_TYPE_...
	 *
	 * @param string $_str - default LIST_TYPE_ALL
	 * @return Project_Placement_Domen_Namecheap
	 */
	public function withListType( $_str ){
		if( !empty($_str) ){
			$this->_withListType=$_str;
		}
		return $this;
	}

	/**
	 * Set SearchTerm - Keyword to look for on the domain list
	 * @param $_str
	 * @return Project_Placement_Domen_Namecheap
	 */
	public function withKeyword( $_str ){
		if( !empty($_str) ){
			$this->_withKeyword=$_str;
		}
		return $this;
	}

	/**
	 * Set Page - Page to return
	 *
	 * @param $_int
	 * @return Project_Placement_Domen_Namecheap
	 */
	public function setPage( $_int ){
		if( !empty($_int) ){
			$this->_page=$_int;
		}
		return $this;
	}

	/**
	 * Set PageSize - 	Number of domains to be listed in a page. Minimum value is 10 and maximum value is 100.
	 *
	 * @param $_int
	 * @return Project_Placement_Domen_Namecheap
	 */
	public function setPerPage( $_int ){
		if( !empty($_int) && $_int>=10 && $_int<=100 ){
			$this->_per_page=$_int;
		}
		return $this;
	}
	/**
	 * Possible values are NAME, NAME_DESC, EXPIREDATE, EXPIREDATE_DESC, CREATEDATE, CREATEDATE_DESC
	 * use const SORT_BY_...
	 *
	 * @param $_str
	 * @return Project_Placement_Domen_Namecheap
	 */
	public function setSortBy( $_str ){
		if( !empty($_str) ){
			$this->_sortBy=$_str;
		}
		return $this;
	}

	/**
	 * Returns a list of domains for the particular user
	 *
	 * @param $arrRes
	 * @return bool
	 */
	public function getList( &$arrRes ){
		$params=array();
		if( $this->_withKeyword ){
			$params['SearchTerm']=$this->_withKeyword;
		}
		if( $this->_withListType ){
			$params['ListType']=$this->_withListType;
		}
		if( $this->_page ){
			$params['Page']=$this->_page;
		}
		if( $this->_per_page ){
			$params['PageSize']=$this->_per_page;
		}
		if( $this->_sortBy ){
			$params['SortBy']=$this->_sortBy;
		}
		$this->setCommand('namecheap.domains.getList')->setEntered( $params );
		$this->_data->setFilter('clear','trim');
		if( !$this->getResponce( $result ) ){
			return false;
		}
		foreach( $result->DomainGetListResult->Domain as $_item ){
			$arrRes[]=array(
				'ID'=>(string) $_item->attributes()->ID,
				'Name'=>(string) $_item->attributes()->Name,
				'User'=>(string) $_item->attributes()->User,
				'Created'=>(string) $_item->attributes()->Created,
				'Expires'=>(string) $_item->attributes()->Expires,
				'IsExpired'=>(string) $_item->attributes()->IsExpired,
				'IsLocked'=>(string) $_item->attributes()->IsLocked,
				'AutoRenew'=>(string) $_item->attributes()->AutoRenew,
				'WhoisGuard'=>(string) $_item->attributes()->WhoisGuard
			);
		}
		return !empty( $arrRes );
	}

	/**
	 * Set params or data
	 * @param $_arrData
	 * @return Project_Placement_Domen_Namecheap
	 */
	public function setEntered( $_arrData ){
		$this->_data=new Core_Data( $_arrData );
		return $this;
	}

	/**
	 * Registers a new domain
	 * !! TODO все параметры брать с click2sell
	 * @param $response
	 * @return bool
	 */
	public function create( &$response ){
		if( !Core_Data_Errors::getInstance()->setData( $this->_data->setFilter( array('clear','trim') ) )->setValidators(array(
			'DomainName'			=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ), // The domain name to register str. max. len. 70
			'Years'					=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ), // Number of years to register int. max. len. 2
			'RegistrantFirstName'	=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ), //First name of the Registrant user str.
			'RegistrantLastName'	=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ), // Second name of the Registrant user str.
			'RegistrantAddress1'	=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ), // Address1 of the Registrant user str.
			'RegistrantCity'		=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ), //  City of the Registrant user str. max len 50
			'RegistrantStateProvince'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ), // State/Province of the Registrant user str. max len 50
			'RegistrantPostalCode'	=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ), // PostalCode of the Registrant user str. max 50
			'RegistrantCountry'		=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ), // Country of the Registrant user str. max 50
			'RegistrantPhone'		=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ), // Phone number in the format +NNN.NNNNNNNNNN str.
			'RegistrantEmailAddress'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ), // Email address of the Registrant user str.
			'TechFirstName'			=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ), // First name of the Tech user str.
			'TechLastName'			=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ), // Second name of the Tech user str.
			'TechAddress1'			=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ), // Address1 of the Tech user str.
			'TechCity'				=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ), // City of the Tech user str. max 50
			'TechStateProvince'		=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ), // State/Province of the Tech user str. max 50
			'TechPostalCode'		=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ), // PostalCode of the Tech user str. max 50
			'TechCountry'			=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ), // Country of the Tech user str. max 50
			'TechPhone'				=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ), // Phone number in the format +NNN.NNNNNNNNNN str.
			'TechEmailAddress'		=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ), // Email address of the Tech user str.
			'AdminFirstName'		=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ), // First name of the Admin user str.
			'AdminLastName'			=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ), // Second name of the Admin user str.
			'AdminAddress1'			=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ), // Address1 of the Admin user str.
			'AdminCity'				=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ), // City of the Admin user str. max 50
			'AdminStateProvince'	=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ), // State/Province of the Admin user  str. max 50
			'AdminPostalCode'		=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ), // PostalCode of the Admin user str. max 50
			'AdminCountry'			=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ), // Country of the Admin user str. max 50
			'AdminPhone'			=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ), // Phone number in the format +NNN.NNNNNNNNNN str. max 50
			'AdminEmailAddress'		=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ), // Email address of the Admin user str.
			'AuxBillingFirstName'	=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ), // First name of the AuxBilling user str.
			'AuxBillingLastName'	=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ), // Second name of the AuxBilling user str.
			'AuxBillingAddress1'	=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ), // Address1 of the AuxBilling user str.
			'AuxBillingCity'		=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ), // City of the AuxBilling user str. max 50
			'AuxBillingStateProvince'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ), // State/Province of the AuxBilling user str. max 50
			'AuxBillingPostalCode'	=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ), // PostalCode of the AuxBilling user str. max 50
			'AuxBillingCountry'		=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ), // Country of the AuxBilling user str. max 50
			'AuxBillingPhone'		=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ), // Phone number in the format +NNN.NNNNNNNNNN str. max
			'AuxBillingEmailAddress'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ), // Email address of the AuxBilling user str.
		))->isValid() ){
			return $this->setError( 0000100 );
		}
		$this->_data->setElements(array(
			'WGEnabled'=>'yes',
			'AddFreeWhoisguard'=>'yes'
		));
		if( !$this->setCommand('namecheap.domains.create')->getResponce( $result ) ){
			return false;
		}
		$response=array(
			'Domain'=>(string)$result->DomainCreateResult->attributes()->Domain,
			'Registered'=>(string)$result->DomainCreateResult->attributes()->Registered,
			'ChargedAmount'=>(string)$result->DomainCreateResult->attributes()->ChargedAmount,
			'DomainID'=>(string)$result->DomainCreateResult->attributes()->DomainID,
			'OrderID'=>(string)$result->DomainCreateResult->attributes()->OrderID,
			'TransactionID'=>(string)$result->DomainCreateResult->attributes()->TransactionID,
			'WhoisguardEnable'=>(string)$result->DomainCreateResult->attributes()->WhoisguardEnable,
			'FreePositiveSSL'=>(string)$result->DomainCreateResult->attributes()->FreePositiveSSL,
			'NonRealTimeDomain'=>(string)$result->DomainCreateResult->attributes()->NonRealTimeDomain
			);
		return $response['Registered'];
	}

	/**
	 * Checks the availability of domains.
	 * string - DomainList: One or more comma-separated list of domains to check
	 * @param array $response
	 * @return bool
	 */
	public function check( &$response ){
		if( !Core_Data_Errors::getInstance()->setData( $this->_data->setFilter( array('clear','trim') ) )->setValidators(array(
			'DomainList'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' )
		))->isValid() ){
			return $this->setError( 0000100 );
		}
		if( !$this->setCommand('namecheap.domains.check')->getResponce( $result ) ){
			return false;
		}
		foreach($result->DomainCheckResult as $_domain ){
			$response[(string)$_domain['Domain']]=(string)$_domain['Available'];
		}
		return !empty($response);
	}

	/**
	 * Reactivates an expired domain.
	 * string - DomainName: Domain name to reactivate.
	 * @param array $response
	 * @return bool
	 */
	public function reactivate( &$response ){
		if( !Core_Data_Errors::getInstance()->setData( $this->_data->setFilter( array('clear','trim') ) )->setValidators(array(
			'DomainName'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' )
		))->isValid() ){
			return $this->setError( 0000100 );
		}
		if( !$this->setCommand('namecheap.domains.reactivate')->getResponce( $result ) ){
			$response=$result;
			return false;
		}
		$response=array(
			'Domain'=>(string)$result->DomainReactivateResult->attributes()->Domain,
			'IsSuccess'=>(string)$result->DomainReactivateResult->attributes()->IsSuccess,
			'ChargedAmount'=>(string)$result->DomainReactivateResult->attributes()->ChargedAmount,
			'OrderID'=>(string)$result->DomainReactivateResult->attributes()->OrderID,
			'TransactionID'=>(string)$result->DomainReactivateResult->attributes()->TransactionID
		);
		return $response['IsSuccess'];
	}

	/**
	 * Renews an expiring domain.
	 *  string - DomainName: Domain Name to renew
	 *  Number - Years: Number of years to renew
	 * @param array $response
	 * @return bool
	 */
	public function renew( &$response ){
		if( !Core_Data_Errors::getInstance()->setData( $this->_data->setFilter( array('clear','trim') ) )->setValidators(array(
			'DomainName'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ),
			'Years'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ),
		))->isValid() ){
			return $this->setError( 0000100 );
		}
		if( !$this->setCommand('namecheap.domains.renew')->getResponce( $result ) ){
			$response=$result;
			return false;
		}
		$response=array(
			'DomainName'=>(string)$result->DomainRenewResult->attributes()->DomainName,
			'DomainID'=>(string)$result->DomainRenewResult->attributes()->DomainID,
			'Renew'=>(string)$result->DomainRenewResult->attributes()->Renew,
			'OrderID'=>(string)$result->DomainRenewResult->attributes()->OrderID,
			'TransactionID'=>(string)$result->DomainRenewResult->attributes()->TransactionID,
			'ChargedAmount'=>(string)$result->DomainRenewResult->attributes()->ChargedAmount
		);
		return $response['Renew'];
	}

	/**
	 * Sets domain to use custom DNS servers.
	 * NOTE: Services like URL forwarding, Email forwarding, Dynamic DNS will not work for domains using custom nameservers.
	 * @return bool
	 */
	public function dnsSetCustom(){
		if( !Core_Data_Errors::getInstance()->setData( $this->_data->setFilter( array('clear','trim') ) )->setValidators(array(
			'SLD'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ),
			'TLD'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ),
			'Nameservers'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ),
		))->isValid() ){
			return $this->setError( 0000100 );
		}
		if( !$this->setCommand('namecheap.domains.dns.setCustom')->getResponce( $result ) ){
			return false;
		}
		return $result->DomainDNSSetCustomResult->attributes()->Updated;
	}

	public function getInfo( &$response ){
		if( !Core_Data_Errors::getInstance()->setData( $this->_data->setFilter( array('clear','trim') ) )->setValidators(array(
			'DomainName'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' )
		))->isValid() ){
			return $this->setError( 0000100 );
		}
		if( !$this->setCommand('namecheap.domains.getInfo')->getResponce( $result ) ){
			return false;
		}
		$response=array(
			'DomainName'=>(string)$result->DomainGetInfoResult->attributes()->DomainName,
			'Status'=>(string)$result->DomainGetInfoResult->attributes()->Status,
			'OwnerName'=>(string)$result->DomainGetInfoResult->attributes()->OwnerName,
			'IsOwner'=>(string)$result->DomainGetInfoResult->attributes()->IsOwner,
			'DomainDetails'=>array(
				'CreatedDate'=>(string)$result->DomainGetInfoResult->DomainDetails->CreatedDate,
				'ExpiredDate'=>(string)$result->DomainGetInfoResult->DomainDetails->ExpiredDate
			),
			'DNS'=>(string)$result->DomainGetInfoResult->DnsDetails->attributes()->ProviderType

		);
		return ($response['Status']=='Ok');
	}

	/**
	 * Get response from Namecheap
	 * @param $result
	 * @return bool
	 * @throws Exception
	 */
	private function getResponce( &$result ){
		if( empty($this->_command) ){
			throw new Exception('Command can\'t be empty!');
		}
		$_query='';
		if( !empty($this->_data->filtered) ){
			$_query='&' . http_build_query( $this->_data->filtered );
		}
		$_url=str_replace(
			array(
				'#api_username#',
				'#api_key#',
				'#nc_username#',
				'#cmd_name#',
				'#clientIPaddress#'
			),
			array(
				self::$apiUser,
				self::$apiKey,
				self::$userName,
				$this->_command,
				self::$clientIp
			), $this->_apiUrl
		) . $_query;
		try{
			$_client=new Zend_Http_Client( $_url, array(
				'timeout'      => 50
			));
			$_responce=$_client->request();
		} catch( Zend_Http_Client_Exception $e ){
			return $this->setError( '" error "'.serialize( $e ).'"' );
		}
		Core_Sql::reconnect();
		$_responseBody=$_responce->getBody();
		
		$_writer=new Zend_Log_Writer_Stream( Zend_Registry::get('config')->path->absolute->logfiles.'Namecheap.log' );
		$_writer->setFormatter( new Zend_Log_Formatter_Simple("%timestamp% %priorityName% (%priority%): %message%\r\n") );
		$_logger=new Zend_Log( $_writer );
		$_logger->info( 'Response: '.$_responseBody );
		
		$_xml=simplexml_load_string($_responseBody);
		if( $_xml === false ){
			return $this->setError( '" response "'.$_responseBody.'"' );
		}
		if( $_xml->attributes()->Status != 'OK' ){
			return $this->setError( '" error number "'.$_xml->Errors->Error->attributes()->Number.'" response "'.$_responseBody.'"' );
		}
		$result=$_xml->CommandResponse;
		return true;
	}

	/**
	 * Set errors
	 * @param $_mix
	 * @return bool
	 */
	private function setError( $_mix ){
		if( isset( $this->_errorCode[ intval($_mix) ] ) && !empty( $this->_errorCode[ intval($_mix) ] ) ){
			
		$_writer=new Zend_Log_Writer_Stream( Zend_Registry::get('config')->path->absolute->logfiles.'Namecheap.log' );
		$_writer->setFormatter( new Zend_Log_Formatter_Simple("%timestamp% %priorityName% (%priority%): %message%\r\n") );
		$_logger=new Zend_Log( $_writer );
		$_logger->info( 'ERROR1: '.$this->_errorCode[ intval($_mix) ] );
		$_logger->info( 'Data: '.serialize($this->_data->filtered) );

			Core_Data_Errors::getInstance()->setError($this->_errorCode[ intval($_mix) ]);
		}else{
			
		$_writer=new Zend_Log_Writer_Stream( Zend_Registry::get('config')->path->absolute->logfiles.'Namecheap.log' );
		$_writer->setFormatter( new Zend_Log_Formatter_Simple("%timestamp% %priorityName% (%priority%): %message%\r\n") );
		$_logger=new Zend_Log( $_writer );
		$_logger->info( 'ERROR2: '.$_mix );
		$_logger->info( 'Data: '.serialize($this->_data->filtered) );
		
			Core_Data_Errors::getInstance()->setError( $_mix );
		}
		return false;
	}

	/**
	 * Get errors
	 * @return array
	 */
	public function getErrors(){
		return Core_Data_Errors::getInstance()->getErrors();
	}
}
?>