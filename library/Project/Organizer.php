<?php

class Project_Organizer extends Core_Storage {
	
	public $fields=array(
		'id', 'title', 'description', 'added', 'flg_archive', 'user_id'
	);
	public $table='content_organizer';
	protected $_link=false; // тут линк нам не нужен

	public function __construct() {
		if ( !Zend_Registry::get( 'objUser' )->getId( $_int ) ) {
			throw new Exception( Core_Errors::DEV.'|Zend_Registry::get( \'objUser\' )->getId( $_int ) is not return an User Id' );
			return;
		}
		$this->_userId=$_int;
	}

	public function set() {
		if ( !$this->_data->setFilter( array( 'trim', 'clear' ) )->setChecker( array(
			'title'=>empty( $this->_data->filtered['title'] ),
			'description'=>empty( $this->_data->filtered['description'] )
		) )->check() ) {
			$this->_data->getErrors( $this->_errors['filtered'] );
			return false; 
		}
		if ( empty( $this->_data->filtered['id']) ) {
			$this->_data->setElement( 'user_id', $this->_userId );
			$this->_data->setElement( 'added', time());
		}
		$this->_data->setElement( 'id', Core_Sql::setInsertUpdate( $this->table, $this->_data->setMask( $this->fields )->getValid() ) );
		return true;
	}
	
	public function getOwnerId() {
		return $this->_userId;
	}

	// всегда отображать профайлы только user_id
	public function getList( &$mixRes ) {
		$this->onlyOwner();
		return parent::getList( $mixRes );
	}
	
	private $_onlyArchive=false;
	
	public function init(){
		parent::init();
		$this->_onlyArchive=false;
	}
	
	public function onlyArchive(){
		$this->_onlyArchive=true;
		return $this;
	}
	
	public function assemblyQuery(){
		parent::assemblyQuery();
		if ( $this->_onlyArchive ) {
			$this->_crawler->set_where( 'd.flg_archive=1');
		} else {
			$this->_crawler->set_where( 'd.flg_archive=0');
		}
	}
	
	public function archive($_arr,$_type=false){
		if ( empty( $_arr ) ) {
			return false;
		}
		Core_Sql::setExec('UPDATE '.$this->table.' SET flg_archive='.(int)$_type.' WHERE id IN ('.Core_Sql::fixInjection( $_arr ).') AND user_id='.$this->getOwnerId() );
		return true;		
	}
}
?>