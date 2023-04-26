<?php

/**
 * Абстактный класс для обработчиков входящих/исходящих SMS/звонков
 */
class Project_Ccs_Twilio_Abstract {

	/**
	 * Адрес обработчика приложений
	 * @var string
	 */
	// public $_urlCallsXML='https://members.creativenichemanager.info/services/twilio.php?method=';

	protected static $_token='235cbc28c40c70027db6fa49dfa97d97'; // real token
	protected static $_sid='ACa8c2b2d13b534902a9e840790371aa48'; // real sid
//	private static $_token='2313f49855176a3094cb43dc0ed2df25'; // test token
//	private static $_sid='AC68c8b4b283446d41cdce0148b1d80a84'; // test sid

	/**
	 * Телефон в привязанный к аккаунту в Twilio
	 * @var string
	 */
	protected static $_phone=array(
		'twilio'=>'+14156586177',
		'zonterest'=>'+19494854202'
	);

	/**
	 * Объект API
	 * @var object 
	 */
	public $_client=false;

	protected $_settings=array();

	public function __construct(){
		if( !empty( Core_Users::$info['twilio'] )
		&& isset( Core_Users::$info['twilio']['token'] )
		&& isset( Core_Users::$info['twilio']['sid'] )
		&& !empty( Core_Users::$info['twilio']['token'] )
		&& !empty( Core_Users::$info['twilio']['sid'] )
		){
			self::$_sid=Core_Users::$info['twilio']['sid'];
			self::$_token=Core_Users::$info['twilio']['token'];
		}
		require_once './library/Core/Services/Twilio/autoload.php';
		$this->_client=new Twilio\Rest\Client( self::$_sid, self::$_token );
	}

	public function setSettings( $_arr ){
		$this->_settings=$_arr;
		return $this;
	}

	public function getCallStatus( &$call ){
		$_call=$this->_client->calls( $call['CallSid'] )->fetch(); //->calls->get($call['CallSid']);
		$call['CallStatus']=$_call->status;
		$call['cost']=str_replace('-','',$_call->price);
	}

	public function getSmsStatus( &$sms ){
		$_massage=$this->_client
			->messages( $sms['SmsSid'] )
			->fetch();
		$sms['SmsStatus']=$_massage->status;
		$sms['cost']=str_replace('-','',$_massage->price);
	}
}
?>