<?php
class Project_Subscribers_Parameters extends Core_Data_Storage{

	protected $_table='s8rs_parameters_';
	protected $_fields=array('id', 'event_id', 'name', 'value');

	public function __construct( $_uid=false ){
		if( $_uid !== false ){
			$this->_table=$this->_table.$_uid;
		}
		$this->install( $_uid );
	}

	public function install(){
		Core_Sql::getInstance();
		try {
			Core_Sql::setConnectToServer( 'lpb.tracker' );
			//========
			Core_Sql::setExec( "CREATE TABLE IF NOT EXISTS `".$this->_table."` (
				`id` INT(11) NOT NULL AUTO_INCREMENT,
				`event_id` INT(11) NULL DEFAULT NULL,
				`name` VARCHAR(32) NULL DEFAULT NULL COLLATE 'utf8_unicode_ci',
				`value` TEXT NULL,
				UNIQUE INDEX `id` (`id`)
			);");
			//========
			Core_Sql::renewalConnectFromCashe();
		} catch(Exception $e) {
			Core_Sql::renewalConnectFromCashe();
			return $this;
		}
	}

	protected $_withEventIds=array();
	protected $_withEventId=false;
	protected $_withNames=array();
	protected $_withValues=array();

	public function withEventId( $_intId=false ){
		$this->_withEventId=$_intId;
		return $this;
	}

	public function withEventIds( $_arrIds=array() ) {
		$this->_withEventIds=$_arrIds;
		return $this;
	}

	public function withNames( $_arrIds=array() ) {
		$this->_withNames=$_arrIds;
		return $this;
	}

	public function withValues( $_arrIds=array() ) {
		$this->_withValues=$_arrIds;
		return $this;
	}

	protected function assemblyQuery() {
		parent::assemblyQuery();
		if ( !empty( $this->_withNames ) ) {
			$this->_crawler->set_where( 'd.name IN ('.Core_Sql::fixInjection( $this->_withNames ).')' );
		}
		if ( !empty( $this->_withValues ) ) {
			$this->_crawler->set_where( 'd.value IN ('.Core_Sql::fixInjection( $this->_withValues ).')' );
		}
		if ( !empty( $this->_withEventIds ) ) {
			$this->_crawler->set_where( 'd.event_id IN ('.Core_Sql::fixInjection( $this->_withEventIds ).')' );
		}
	}

	protected function init() {
		parent::init();
		$this->_withNames=array();
		$this->_withEventIds=array();
		$this->_withEventId=false;
	}

	public function set() {
		if ( !$this->beforeSet() ) {
			return false;
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
		if( $this->_withEventId !== false ){
			try {
				Core_Sql::setConnectToServer( 'lpb.tracker' );
				//========
				//чистим старые записи по этому event id
				Core_Sql::setExec( 'DELETE FROM '.$this->_table.' WHERE event_id='.$this->_withEventId );
				$_arrSend=array();
				foreach( $this->_data->filtered as $key=>&$value ){
					if( is_array( $value ) || is_object( $value ) ){
						$value=base64_encode( serialize( $value ) );
					}
					$_arrSend[]=implode( '","', array( $key, $value, $this->_withEventId ) );
				}
				Core_Sql::setExec( 'INSERT INTO '.$this->_table.' (`name`,`value`, `event_id`) VALUES ("'.implode( '"),("', $_arrSend ).'")' );
				//========
				Core_Sql::renewalConnectFromCashe();
			} catch(Exception $e) {
				Core_Sql::renewalConnectFromCashe();
				$this->init();
				return false;
			}
		}
		return;
	}
	
	public function getList( &$arrRes ){
		try {
			Core_Sql::setConnectToServer( 'lpb.tracker' );
			//========
			parent::getList( $arrRes );
			//========
			Core_Sql::renewalConnectFromCashe();
		} catch(Exception $e) {
			Core_Sql::renewalConnectFromCashe();
			return $this;
		}
		return $this;
	}
	
}
?>