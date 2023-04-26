<?php
class Project_Efunnel_Smtpids extends Core_Data_Storage{

	protected $_table='ef_smtp';
	protected $_fields=array('id', 'user_id', 'email', 'smtp');

	protected $_withEmail=array();
	protected $_withSmtpId=false;
	
	public function withEmail( $_arrIds=array() ){
		$this->_withEmail=$_arrIds;
		return $this;
	}
	
	public function withSmtpId( $_varIds ){
		if( is_array( $_varIds ) ){
			foreach ($_varIds as $key => $value){
				$this->_withSmtpId[] = 'd.smtp LIKE "%' . trim( Core_Sql::fixInjection( $value ), "'" ) . '%"';
			}
			$this->_withSmtpId = implode( ' OR ', $this->_withSmtpId );
		}else{
			$this->_withSmtpId = 'd.smtp LIKE "%' . trim( Core_Sql::fixInjection( $_varIds ), "'" ) . '%"';
		}
		return $this;
	}

	protected function assemblyQuery(){
		parent::assemblyQuery();
		if ( !empty( $this->_withSmtpId ) ){
			$this->_crawler->set_where( $this->_withSmtpId );
		}
	}

	protected function init(){
		parent::init();
		$this->_withEmail=false;
		$this->_withSmtpId=false;
	}

	public function getList( &$mixRes ){
		try {
			Core_Sql::setConnectToServer( 'lpb.tracker' );
			//========
			parent::getList( $mixRes );
			//========
			Core_Sql::renewalConnectFromCashe();
		} catch(Exception $e) {
			Core_Sql::renewalConnectFromCashe();
			return $this;
		}
		return !empty($mixRes);
	}

	public function set() {
		if ( !$this->beforeSet() ) {
			return false;
		}
		if ( empty( $this->_data->filtered['id'] ) ) {
			$this->_data->setElement( 'added', $this->_data->filtered['edited'] );
		}
		try {
			Core_Sql::setConnectToServer( 'lpb.tracker' );
			//========
			$this->_data->setElement( 'id', Core_Sql::setInsertUpdate( $this->_table, $this->_data->setMask( $this->_fields )->getValid() ) );
			//========
			Core_Sql::renewalConnectFromCashe();
		} catch(Exception $e) {
			Core_Sql::renewalConnectFromCashe();
			return $this;
		}
		return $this->afterSet();
	}
	
	public function setMass(){
		$this->_data->setFilter();
		try {
			Core_Sql::setConnectToServer( 'lpb.tracker' );
			//========
			$_arrSend=$_arrValues=array();
			foreach( $this->_data->filtered as $_send ){
				foreach( array_keys( $_send ) as $_name ){
					$_arrValues[$_name]=true;
				}
				$_arrSend[]=implode( '","', $_send );
			}
			Core_Sql::setExec( 'INSERT INTO '.$this->_table.' (`'.implode( '`,`', array_keys( $_arrValues  ) ).'`) VALUES ("'.implode( '"),("', $_arrSend ).'")' );
			//========
			Core_Sql::renewalConnectFromCashe();
		} catch(Exception $e) {
			Core_Sql::renewalConnectFromCashe();
			$this->init();
			return false;
		}
		return;
	}
	
	public function del(){
		$_strWith=array();
		if ( !empty( $this->_withEmail ) ){
			$_strWith[]='email IN ('.Core_Sql::fixInjection( $this->_withEmail ).')';
		}
		if ( !empty( $this->_withSmtpId ) ){
			$_strWith[]='smtp IN ('.Core_Sql::fixInjection( $this->_withSmtpId ).')';
		}
		if( empty( $_strWith ) ){
			$this->init();
			return false;
		}
		try {
			Core_Sql::setConnectToServer( 'lpb.tracker' );
			//========
			Core_Sql::setExec( 'DELETE FROM '.$this->_table.' WHERE '.implode( ' AND ', $_strWith ) );
			//========
			Core_Sql::renewalConnectFromCashe();
		} catch(Exception $e) {
			Core_Sql::renewalConnectFromCashe();
			$this->init();
			return false;
		}
		$this->init();
		return true;
	}
}
?>