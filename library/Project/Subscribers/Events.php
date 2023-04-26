<?php
class Project_Subscribers_Events extends Core_Data_Storage{

	protected $_table='s8rs_events_';
	protected $_fields=array('id', 'sub_id', 'event_type', 'added');
	protected $_userId='';

	// event types
	const FROM_ADMIN=3, LEAD_FORM=1, EMIAL_FUNNEL=2, AUTOMATION=4;
	
	public function __construct( $_uid=false ){
		if( $_uid !== false ){
			$this->_table=$this->_table.$_uid;
			$this->_userId=$_uid;
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
				`sub_id` INT(11) NULL DEFAULT NULL,
				`event_type` INT(3) NOT NULL DEFAULT '0',
				`added` INT(11) UNSIGNED NOT NULL DEFAULT '0',
				UNIQUE INDEX `id` (`id`)
			);");
			//========
			Core_Sql::renewalConnectFromCashe();
		} catch(Exception $e) {
			Core_Sql::renewalConnectFromCashe();
			return $this;
		}
	}

	protected $_withEventType=array();
	protected $_withSubscriberIds=array();
	protected $_withParam=array();

	public function withSubscriberIds( $_arrIds=array() ) {
		$this->_withSubscriberIds=$_arrIds;
		return $this;
	}

	public function withEventType( $_arrIds=array() ) {
		$this->_withEventType=$_arrIds;
		return $this;
	}

	public function withParam( $_name='', $_value='' ) {
		$this->_withParam=array( 'name'=>$_name, 'value'=>$_value );
		return $this;
	}
	
	protected function assemblyQuery(){
		parent::assemblyQuery();
		if ( !empty( $this->_withEventType ) ) {
			$this->_crawler->set_where( 'd.event_type IN ('.Core_Sql::fixInjection( $this->_withEventType ).')' );
		}
		if ( !empty( $this->_withSubscriberIds ) ) {
			$this->_crawler->set_where( 'd.sub_id IN ('.Core_Sql::fixInjection( $this->_withSubscriberIds ).')' );
		}
	}
	
	protected function init() {
		parent::init();
		$this->_withEventType=array();
		$this->_withSubscriberIds=array();
	}

	protected function afterSet(){
		if( isset( $this->_data->filtered['param'] ) 
			&& !empty( $this->_data->filtered['param'] ) 
			&& isset( $this->_data->filtered['id'] )
		){
			$_params=new Project_Subscribers_Parameters($this->_userId);
			$_params->withEventId($this->_data->filtered['id'])->setEntered( $this->_data->filtered['param'] )->setMass();
		}
		return true;
	}

	public function set() {
		if ( !$this->beforeSet() ) {
			return false;
		}
		if( empty( $this->_data->filtered['id'] ) && empty( $this->_data->filtered['added'] ) ){
			$this->_data->setElement( 'added', time() );
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
	
	public function getList( &$mixRes ){
		if( !empty( $this->_withParam ) ){
			$_params=new Project_Subscribers_Parameters($this->_userId);
			$_params->withNames( array( $this->_withParam['name'] ) )->withValues( array( $this->_withParam['value'] ) )->getList( $_arrEventIds );
			if( empty( $_arrEventIds ) ){
				$mixRes=array();
				return !empty($mixRes);
			}
			$this->_withIds=array();
			foreach( $_arrEventIds as $_p2ev ){
				$this->_withIds[$_p2ev['event_id']]=$_p2ev['event_id'];
			}
		}
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
		if( is_int( array_keys( $mixRes )[0] ) ){
			foreach( $mixRes as &$_item ){
				$_arrIds[]=$_item['id'];
			}
			$_params=new Project_Subscribers_Parameters($this->_userId);
			$_params->withEventIds( $_arrIds )->getList( $_arrParams );
			$_params->getList( $_arrParams );
			foreach( $mixRes as &$_item ){
				foreach( $_arrParams as $_param ){
					if( $_param['event_id'] == $_item['id'] ){
						$_item['param'][$_param['name']]=$_param['value'];
					}
				}
			}
		}else{
			$_params=new Project_Subscribers_Parameters($this->_userId);
			$_params->withEventIds( $mixRes['id'] )->getList( $_arrParams );
			foreach( $_arrParams as $_param ){
				$mixRes['param'][$_param['name']]=$_param['value'];
			}
		}
		return !empty($mixRes);
	}
	
	public function del(){
		$_strWith=array();
		if ( !empty( $this->_withSubscriberIds ) ){
			$_strWith[]='sub_id IN ('.Core_Sql::fixInjection( $this->_withSubscriberIds ).')';
		}
		if ( !empty( $this->_withEventType ) ){
			$_strWith[]='event_type IN ('.Core_Sql::fixInjection( $this->_withEventType ).')';
		}
		if( empty( $_strWith ) ){
			$this->init();
			return false;
		}
		try {
			Core_Sql::setConnectToServer( 'lpb.tracker' );
			//========
			Core_Sql::setExec( 'DELETE FROM '.$this->_table.' WHERE '.implode( ' and ', $_strWith ) );
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