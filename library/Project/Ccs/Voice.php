<?php

/**
 * Модель управления логом звонков
 */
class Project_Ccs_Voice extends Core_Data_Storage {

	protected $_table='ccs_voice';
	protected $_fields=array('id','user_id','cost','CallSid','CallStatus','Direction','To','From','commands','added','edited');
	private $_withCallID=false;
	private $_withStatus=false;
	private $_limit=false;
	public static $status=array(
		'queued'=>'queued',
		'ringing'=>'ringing',
		'inprogress'=>'in-progress',
		'canceled'=>'canceled',
		'completed'=>'completed',
		'failed'=>'failed',
		'busy'=>'busy',
		'noanswer'=>'no-answer'
	);

	public function clean($_sidId=''){
		if( !empty( $_sidId ) ){
			Core_Sql::setExec( 'DELETE FROM '.$this->_table.' WHERE CallSid='.Core_Sql::fixInjection( $_sidId ) );
			sleep( 2 );
		}
	}

	protected function beforeSet(){
		if ( !Core_Data_Errors::getInstance()->setData( $this->_data->setFilter('trim','clear') )->setValidators( array(
			'CallSid'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ),
		) )->isValid() ) {
			return Core_Data_Errors::getInstance()->setError('Incorrect entered data');
		}
		$this->_data->setElement('commands', serialize($this->_data->filtered['commands']) );
		return true;
	}

	public function withCallID( $_strID ){
		$this->_withCallID=$_strID;
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
		$this->_withCallID=false;
		$this->_limit=false;
		$this->_withStatus=false;
	}

	protected function assemblyQuery(){
		parent::assemblyQuery();
		if( $this->_withCallID ){
			$this->_crawler->set_where('d.CallSid='.Core_Sql::fixInjection($this->_withCallID));
		}
		if( $this->_withStatus ){
			$this->_crawler->set_where('d.CallStatus IN ('.Core_Sql::fixInjection($this->_withStatus).')');
		}
		if( $this->_limit ){
			$this->_crawler->set_limit( $this->_limit );
		}
		$this->_crawler->set_select('u.email,u.buyer_name');
		$this->_crawler->set_from('LEFT JOIN u_users u ON u.id=d.user_id');
	}

	public function getList( &$mixRes ){
		$_onlyOne=$this->_onlyOne;
		parent::getList( $mixRes );
		if( $_onlyOne ){
			$mixRes['commands']=unserialize($mixRes['commands']);
		} else {
			foreach( $mixRes as &$_item ){
				$_item['commands']=unserialize($_item['commands']);
			}
		}
		return $this;
	}
}
?>