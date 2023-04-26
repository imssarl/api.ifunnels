<?php

class Project_Documents extends Core_Data_Storage {

	protected $_table='doc_items';
	protected $_fields=array('id','sys_name','title','body','edited','added');
	private $_bySysName=false;

	protected function beforeSet(){
		if ( !Core_Data_Errors::getInstance()->setData( $this->_data->setFilter('trim','clear') )->setValidators( array(
			'title'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ),
			'body'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ),
		) )->isValid() ) {
			return false;
		}
		return true;
	}

	public function getErrors( &$arrErrors ){
		$arrErrors=Core_Data_Errors::getInstance()->getErrors();
	}

	public function bySysName( $_sysName ){
		if( !empty($_sysName)){
			$this->_bySysName=$_sysName;
		}
		return $this;
	}

	protected function init(){
		parent::init();
		$this->_bySysName=false;
	}
	protected function assemblyQuery(){
		parent::assemblyQuery();
		if( $this->_bySysName ){
			$this->_crawler->set_where( 'd.sys_name='.Core_Sql::fixInjection($this->_bySysName) );
		}
	}

	public static function getBySysName( $_sysName ){
		$self=new self();
		$self->bySysName( $_sysName )->onlyOne()->getList( $arrRes );
		return $arrRes;
	}
}
?>