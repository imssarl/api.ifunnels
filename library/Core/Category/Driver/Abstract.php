<?php
abstract class Core_Category_Driver_Abstract extends Core_Services {

	protected $userId;
	protected $_type;

	protected $_table='category_category';
	protected $_fields=array( 'id', 'user_id', 'type_id', 'priority', 'title','remote_id' );

	protected $_byTitle='';
	protected $_byId=0;
	protected $_toSelect=false;
	protected $_withPagging=array();

	protected $_data;
	protected $_errors;

	public function __construct( $_arrRes=array() ) {
		$this->_type=&$_arrRes;
		$this->initUser();
		$this->setTable();
	}

	public function setData( $_arrData=array() ) {
		$this->_data=new Core_Data( $_arrData );
		return $this;
	}

	public function getEntered( &$arrRes ) {
		$arrRes=$this->_data->getFiltered();
		return $this;
	}

	public function getErrors( &$arrRes ) {
		$arrRes=$this->_errors;
		return $this;
	}

	private function initUser() {
		if ( empty( $this->_type['flg_user'] ) ) {
			return;
		}
		Zend_Registry::get( 'objUser' )->getId( $_int );
		if ( empty( $_int )&&!Zend_Registry::get( 'objMR' )->isBackend ) { // в админке это игнорим но id может быть нулевым
			throw new Exception( Core_Errors::DEV.'|User not initialized' );
			return;
		}
		$this->userId=$_int;
	}

	private function setTable() {
		if ( $this->_type['type']=='nested' ) {
			$this->_table=$this->_type['storage'];
			$this->_fields=array( 'id', 'pid', 'level', 'user_id', 'priority', 'title' );
		}
	}

	protected function checkTitle( $_arr=array() ) {
		if ( empty( $_arr['title'] ) ) {
			return false;
		}
		if ( $this->byTitle( $_arr['title'] )->get( $_arrTmp, $_arrTmp2 ) ) {
			if ( !empty( $_arr['id'] )&&$_arr['id']!=$_arrTmp['id'] ) {
				return false;
			}
			if ( empty( $_arr['id'] ) ) {
				return false;
			}
		}
		return true;
	}

	// сброс настроек после выполнения get
	protected function init() {
		$this->_byTitle='';
		$this->_byId=0;
		$this->_toSelect=false;
		$this->_withPagging=array();
	}

	public function byTitle( $_str='' ) {
		$this->_byTitle=$_str;
		return $this;
	}

	public function byId( $_mix=0 ) {
		$this->_byId=$_mix;
		return $this;
	}

	public function toSelect() {
		$this->_toSelect=true;
		return $this;
	}

	public function withPagging( $_arr=array() ) {
		$this->_withPagging=$_arr;
		return $this;
	}

	abstract public function get( &$mixRes, &$arrPg );

	// depercated!!!
	abstract public function set( &$arrRes, &$arrErr, $_arrData=array() );

	abstract public function setCategory();

	abstract public function del( $_mixId=array() );
}
?>