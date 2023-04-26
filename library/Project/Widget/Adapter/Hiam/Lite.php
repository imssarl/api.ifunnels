<?php

class Project_Widget_Adapter_Hiam_Lite extends Core_Data_Storage {

	protected $_table='hi_lite_campaign';
	protected $_fields=array('id', 'user_id', 'flg_type', 'flg_priority', 'title', 'body', 'start', 'end', 'edited', 'added');

	protected function beforeSet(){
		if( !Core_Data_Errors::getInstance()->setData( $this->_data->setFilter( array('clear','trim') ) )->setValidators(array(
			'title'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ),
			'body'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ),
			'start'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ),
			'end'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ),
			'groupsIds'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ),
		))->isValid() ){
			return false;
		}
		if( $this->_data->filtered['flg_type']==1&&$this->_data->filtered['flg_priority']==1){
			Core_Sql::setExec('UPDATE '.$this->_table.' SET flg_priority=0 WHERE flg_type=1');
		}
		return true;
	}

	protected function afterSet(){
		$_link=new Project_Widget_Adapter_Hiam_Lite_Link();
		if( !$_link->addLink( $this->_data->filtered['groupsIds'],$this->_data->filtered['id']) ){
			return false;
		}
		return true;
	}

	private $_forFrontend=false;


	public function forFrontend(){
		$this->_forFrontend=true;
		return $this;
	}

	public function del(){
		if(!empty($this->_withIds)){
			$_link=new Project_Widget_Adapter_Hiam_Lite_Link();
			$_link->del( $this->_withIds);
		}
		parent::del();
	}

	protected function init(){
		parent::init();
		$this->_forFrontend=false;
	}

	protected function assemblyQuery(){
		parent::assemblyQuery();
		if( $this->_forFrontend && !empty( Core_Users::$info['groups'] ) ){
			$this->_crawler->set_where('d.start<'. Core_Sql::fixInjection(time()).' AND d.end > '. Core_Sql::fixInjection(time()) );
			$this->_crawler->set_where('d.id IN (SELECT ad_id FROM hi_lite_link2group WHERE group_id IN ('. Core_Sql::fixInjection(array_keys(Core_Users::$info['groups'])) .'))');
		}
	}

	public function getList( &$mixRes ){
		parent::getList( $mixRes );
		if(empty($mixRes)){
			return $this;
		}
		$_link=new Project_Widget_Adapter_Hiam_Lite_Link();
		if( !is_array($mixRes[0]) ){
			$mixRes['groups']=$_link->getGroups2Campaign( $mixRes['id'] );
			return $this;
		}
		foreach( $mixRes as &$_item ){
			$_item['groups']=$_link->getGroups2Campaign( $_item['id'] );
		}
		return $this;
	}
}
?>