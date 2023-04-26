<?php

/**
 * Модель управления логом СМС
 */
class Project_Ccs_Sms extends Core_Data_Storage {

	protected $_table='ccs_sms';
	/**
	 * http://www.twilio.com/docs/api/rest/sms#instance
	 * @var array
	 */
	protected $_fields=array('id','user_id','cost','SmsSid','SmsStatus','Direction','Body','To','From','added','edited');
	private $_withSID=false;
	private $_limit=false;
	private $_withStatus=false;
	public static $_status=array(
		'queued'=>'queued',
		'sending'=>'sending',
		'sent'=>'sent',
		'failed'=>'failed',
		'received'=>'received',
	);

	protected function beforeSet(){
		if ( !Core_Data_Errors::getInstance()->setData( $this->_data->setFilter('trim','clear') )->setValidators( array(
			'SmsSid'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ),
		) )->isValid() ) {
			return Core_Data_Errors::getInstance()->setError('Incorrect entered data');
		}
		return true;
	}

	public function withSID( $_intID ){
		$this->_withSID=$_intID;
		return $this;
	}

	public function withStatus( $_mix ){
		if( !is_array($_mix) ){
			$_mix=array($_mix);
		}
		$this->_withStatus=$_mix;
		return $this;
	}

	public function setLimit( $_int ){
		$this->_limit=$_int;
		return $this;
	}

	protected function init(){
		parent::init();
		$this->_withSID=false;
		$this->_withStatus=false;
		$this->_limit=false;
	}

	protected function assemblyQuery(){
		parent::assemblyQuery();
		if( $this->_withSID ){
			$this->_crawler->set_where('d.SmsSid='.Core_Sql::fixInjection($this->withSID));
		}
		if( $this->_withStatus ){
			$this->_crawler->set_where('d.SmsStatus IN ('.Core_Sql::fixInjection($this->_withStatus).')');
		}
		if( $this->_limit ){
			$this->_crawler->set_limit( $this->_limit );
		}
		$this->_crawler->set_select('u.email,u.buyer_name');
		$this->_crawler->set_from('LEFT JOIN u_users u ON u.id=d.user_id');
	}
}
?>