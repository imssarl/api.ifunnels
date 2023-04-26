<?php

/**
 * Абстрактный класс для всех приложений
 */
class Project_Ccs_Twilio_Apps_Abstract{

	/**
	 * Объект, для генерации TwiML
	 *
	 * @var bool|Project_Ccs_Twilio_Apps_Abstract
	 */
	protected $_response=false;

	/**
	 * Свойства приложений
	 * @var bool
	 */
	protected $_settings=false;

	/**
	 * Голос которым говорит система
	 * @var string
	 */
	protected $_voice='woman';

	public function __construct( $_method, $_settings ){
		//parent::__construct();
		if( empty($_method) ){
			throw new Project_Ccs_Exception('Empty data');
		}
		if( !method_exists( $this, $_method ) ){
			throw new Project_Ccs_Exception('<'. $_method .'> is not supported method');
		}
		if(empty($_settings)){
			throw new Project_Ccs_Exception('Not correct data');
		}
		require_once './library/Core/Services/Twilio/autoload.php';
		$this->_response=new Twilio\Twiml();
		$this->setSettings( $_settings )->$_method();
		if( $this->_response ){
			$this->display();
		}
	}

	/**
	 * Set settings
	 * @param $_arr
	 * @return $this
	 */
	public function setSettings( $_arr ){
		$this->_settings=$_arr;
		return $this;
	}

	/**
	 * Display XML in format https://www.twilio.com/docs/api/twiml/
	 */
	public function display(){
		if( !isset( $_GET['test'] ) )
			ob_clean();
		echo $this->_response;
	}

	/**
	 * Update call-log
	 * @param $_commands
	 * @return array
	 */
	public function updateCall( $_commands ){
		if( !isset( $this->_settings['CallSid'] ) ){
			return array();
		}
		$_voice=new Project_Ccs_Voice();
		$_voice->withCallID( $this->_settings['CallSid'] )->onlyOne()->getList( $arrCall );
		$arrCall['commands']=array_merge((is_array($arrCall['commands'])?$arrCall['commands']:array()),$_commands);
		$arrCall=array_merge($arrCall,$this->_settings);
		$_voice->setEntered( $arrCall )->set();
		return $arrCall;
	}
	
	/**
	 * Get call-log
	 * @param $_commands
	 * @return array
	 */
	public function getCall(){
		$_voice=new Project_Ccs_Voice();
		$_voice->withCallID( $this->_settings['CallSid'] )->onlyOne()->getList( $arrCall );
		return $arrCall;
	}

	/**
	 * Prepare URL for next step
	 * @param array $_arrParams
	 * @return mixed
	 * @throws Project_Ccs_Exception
	 */
	public static function prepareUrl( $_arrParams=array() ){
		if( empty($_arrParams['app'])||empty($_arrParams['action']) ){
			throw new Project_Ccs_Exception('Empty params');
		}
		return str_replace( array( '#app#','#action#' ), array( $_arrParams['app'],$_arrParams['action'] ),Zend_Registry::get( 'config' )->domain->url.Project_Ccs_Twilio_Apps::$appUrl );
	}
}
?>