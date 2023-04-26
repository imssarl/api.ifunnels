<?php

class Project_Parking extends Core_Data_Storage {

	protected $_table='pa_domains';
	protected $_fields=array('id','flg_status','user_id','title','domains','keywords','added');
	public static $status=array('notStarted'=>0,'inProgress'=>1,'completed'=>2,'error'=>3);
	private $_arrFiles=false;

	public function setFile( $_arrFiles ){
		$this->_arrFiles=$_arrFiles;
		return $this;
	}

	protected function beforeSet(){
		if( !Core_Data_Errors::getInstance()->setData( $this->_data->setFilter( array('clear','trim') ) )->setValidators(array(
			'title'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' )
		))->isValid() ){
			return false;
		}
		if(!empty($this->_arrFiles['file_csv']['name'])){
			$this->import();
		}
		foreach($this->_data->filtered['domains'] as &$_item){
			$_item=strtolower($_item);
		}
		$this->_data->setElements(array(
			'flg_status'=> self::$status['notStarted'],
			'domains'=>serialize($this->_data->filtered['domains']),
			'keywords'=>serialize($this->_data->filtered['keywords'])
		));
		return true;
	}

	private function import(){
		$_parser=new Core_Parsers_Csv(array(
			'filename'=>$this->_arrFiles['file_csv']['tmp_name']
		));
		$_parser->get_data( $arrRes );
		foreach($arrRes as $_item ){
			if( empty($_item[0]) ){
     			continue;
			}
			$this->_data->filtered['domains'][]=$_item[0];
			$this->_data->filtered['keywords'][]=$_item[1];
		}
	}

	private $_withStatus=false;


	protected function init(){
		parent::init();
		$this->_withStatus=false;
	}

	public function withStatus( $_intStatus ){
		if(!in_array($_intStatus,self::$status)){
			throw new Exception('Can\'t find status');
		}
		$this->_withStatus=$_intStatus;
		return $this;
	}

	protected function assemblyQuery(){
		parent::assemblyQuery();
		if( $this->_withStatus!==false ){
			$this->_crawler->set_where('d.flg_status IN ('.$this->_withStatus.')');
		}
	}

	public function setStatus( $_intStatus,$_arrIds ){
		if(!in_array($_intStatus,self::$status)||empty($_arrIds)){
			return false;
		}
		return Core_Sql::setExec('UPDATE '.$this->_table.' SET flg_status='.$_intStatus.' WHERE id IN('. Core_Sql::fixInjection($_arrIds) .')');
	}
}
?>