<?php

/**
 * Модель управления кроном
 */
class Project_Ccs_Arrange extends Core_Data_Storage {

	protected $_table='ccs_cron';
	protected $_fields=array('id','user_id','flg_status','start','action','added','edited');
	private $_readyStart=false;
	private $_onlyInProgress=false;
	private $_onlyCompleted=false;
	private $_onlyNotStarted=false;
	private $_logger=false;
	public static $status=array(
		'notstarted'=>0,
		'inprogress'=>1,
		'completed'=>2,
		'error'=>3
	);
	const
			ACTION_CALL_CONFIRM=1,
			ACTION_CALL_CREATE_SITE=2,
			ACTION_CALL_BALANCE=3;

	public static function getDays(){
		for( $_i=1; $_i<=30; $_i++ ){
			$days[]=$_i;
		}
		return $days;
	}

	public static function getHours(){
		for( $_i=1; $_i<=12; $_i++ ){
			$hours[]=$_i;
		}
		return $hours;
	}

	/**
	 * Крон-скрипт - проверка статусов звонков
	 */
	public function checkStatusCalls(){
		$_voice=new Project_Ccs_Voice();
		if( !$_voice->setLimit( 5 )->withStatus(array(
			Project_Ccs_Voice::$status['queued'],
			Project_Ccs_Voice::$status['ringing'],
			Project_Ccs_Voice::$status['inprogress'],
		))->getList($arrRes)->checkEmpty() ){
			return;
		}
		$_twilio=new Project_Ccs_Twilio_Client();
		foreach( $arrRes as $_item ){
			$_twilio->getCallStatus($_item);
			$_voice->setEntered( $_item )->set();
		}
	}

	/**
	 * Крон-скрипт - проверка статусов для сообщений
	 */
	public function checkStatusSMS(){
		$_sms=new Project_Ccs_Sms();
		if( !$_sms->setLimit( 5 )->withStatus(array(
			Project_Ccs_Voice::$status['queued'],
			Project_Ccs_Voice::$status['sending'],
		))->getList($arrRes)->checkEmpty() ){
			return;
		}
		$_twilio=new Project_Ccs_Twilio_Client();
		foreach( $arrRes as $_item ){
			$_twilio->getSmsStatus( $_item );
			$_sms->setEntered( $_item )->set();
		}
	}

	/**
	 * Крон-скрипт - обработка звонков и сообщений по расписанию
	 * @return bool
	 */
	public function run(){
		if( !$this->readyStart()->getList( $arrRes )->checkEmpty() ){
			return false;
		}
		$this->setLogger();
		foreach( $arrRes as $_item ){
			$this->_logger->info('Start process:'.$_item['id']);
			$this->process( $_item );
			$this->_logger->info('End process:'.$_item['id']);
		}
	}

	private function process( $_process ){
		$this->setStatus($_process['id'],self::$status['inprogress']);
		$_client=new Project_Ccs_Twilio_Client();
		$_client->setCalled( $_process['user_id']);
		switch( $_process['action'] ){
			case self::ACTION_CALL_BALANCE:
				$_client->balance();
				break;

			case self::ACTION_CALL_CONFIRM:
				$_client->confirmPhone();
				break;

			case self::ACTION_CALL_CREATE_SITE:
				$_client->createSites();
				break;

			default:
				throw new Project_Ccs_Exception('Can not define action');
				break;
		}
		$this->setStatus($_process['id'],self::$status['completed']);
	}

	private function setLogger() {
		$writer=new Zend_Log_Writer_Stream( 'php://output' );
		$writer->setFormatter( new Zend_Log_Formatter_Simple("%timestamp% %priorityName% (%priority%): %message%<br/>\r\n") );
		$this->_logger=new Zend_Log( $writer );
	}

	private function setStatus( $_intId, $_intStatus=0 ){
		if( empty($_intId) ){
			throw new Project_Ccs_Exception('ID is empty');
		}
		Core_Sql::setExec('UPDATE '.$this->_table.' SET flg_status='.intval($_intStatus).' WHERE id='.intval($_intId) );
	}

	protected function beforeSet(){
		if ( !Core_Data_Errors::getInstance()->setData( $this->_data->setFilter('trim','clear') )->setValidators( array(
			'start'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ),
			'action'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ),
		) )->isValid() ) {
			return Core_Data_Errors::getInstance()->setError('Incorrect entered data');
		}
		return true;
	}

	public function readyStart(){
		$this->_readyStart=true;
		return $this;
	}

	public function onlyInProgress(){
		$this->_onlyInProgress=true;
		return $this;
	}

	public function onlyCompleted(){
		$this->_onlyCompleted=true;
		return $this;
	}

	public function onlyNotStarted(){
		$this->_onlyNotStarted=true;
		return $this;
	}
	protected function init(){
		parent::init();
		$this->_onlyCompleted=false;
		$this->_onlyInProgress=false;
		$this->_onlyNotStarted=false;
		$this->_readyStart=false;
	}

	protected function assemblyQuery(){
		parent::assemblyQuery();
		if( $this->_readyStart ){
			$this->_crawler->set_where('d.start<'.time().' AND d.flg_status='.self::$status['notstarted']);
		}
		if( $this->_onlyInProgress ){
			$this->_crawler->set_where('d.flg_status='.self::$status['inprogress']);
		}
		if( $this->_onlyCompleted ){
			$this->_crawler->set_where('d.flg_status='.self::$status['completed']);
		}
		if( $this->_onlyNotStarted ){
			$this->_crawler->set_where('d.flg_status='.self::$status['notstarted']);
		}
		$this->_crawler->set_select('u.email,u.buyer_name');
		$this->_crawler->set_from('LEFT JOIN u_users u ON u.id=d.user_id');
	}
}
?>