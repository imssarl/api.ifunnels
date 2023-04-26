<?php
class Project_Automation_Action extends Core_Data_Storage {

	protected $_table='automation_action';
	protected $_fields=array( 'id', 'auto_id', 'action_type', 'action_values', 'settings', 'edited', 'added' );

	public static function install(){
		Core_Sql::setExec("drop table if exists automation_action");
		Core_Sql::setExec( "CREATE TABLE `automation_action` (
			`id` INT(11) NOT NULL AUTO_INCREMENT,
			`auto_id` INT(11) NOT NULL DEFAULT '0',
			`action_type` INT(2) NOT NULL DEFAULT '0',
			`action_values` TEXT NULL,
			`settings` TEXT NULL,
			`edited` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			`added` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			UNIQUE INDEX `id` (`id`)
		)
		COLLATE='utf8_general_ci'
		ENGINE=InnoDB;" );
		return $this;
	}
	
	public static $type=array( //ability to add AND so we have multiple actions
		'ADD_TAG'=>1, //Add tag
		'PAUSE_EF'=>2, //Pause from Email Funnel
		'REMOVE_EF'=>3, //Remove from Email Funnel
		'RESUME_EF'=>4, //Resume Email Funnel
		'ADD_EF'=>5, //Add to Email Funnel
		'UPDATE_CONTACT'=>6, //Update Contact (we might have some fields to update)
		'SEND_TO_LC'=>7, //Send to Lead Channel (for example to ping a url via Zapier, to add to a webinar…)
		'PING_URL'=>8, //Ping URL (https://screencast.com/t/LsQYCFlM33jg)
		'REMOVE_TAG'=>9, //Remove tag
	);

	protected $_withAutoId=false;
	
	public function withAutoId( $_var=false ){
		$this->_withAutoId=$_var;
		return $this;
	}

	protected function assemblyQuery(){
		parent::assemblyQuery();
		if ( !empty( $this->_withAutoId ) ){
			$this->_crawler->set_where( 'd.auto_id IN ( '.Core_Sql::fixInjection( $this->_withAutoId ).')' );
		}
	}

	protected function init(){
		parent::init();
		$this->_withAutoId=false;
	}
	
	protected function beforeSet() {
		$this->_data->setFilter( array( 'clear' ) );
		$this->_data->setElements(array(
			'settings'=>base64_encode( serialize( $this->_data->filtered['settings'] ) ),
		));
		return true;
	}
	
	protected function afterSet() {
		$this->_data->filtered['settings']=unserialize( base64_decode( $this->_data->filtered['settings'] ) );
		return true;
	}
	
	public function getList( &$mixRes ){
		parent::getList( $mixRes );
		if( !empty( $mixRes ) ){
			if( isset( $mixRes['settings'] ) ){
				$mixRes['settings']=unserialize(base64_decode($mixRes['settings']));
			}else{
				foreach( $mixRes as &$_res ){
					if( isset( $_res['settings'] ) ){
						$_res['settings']=unserialize(base64_decode($_res['settings']));
					}
				}
			}
		}
		return $this;
	}
	
	public function del(){
		if ( empty( $this->_withAutoId ) ){
			$_bool=false;
		} else {
			Core_Sql::setExec( 'DELETE FROM '.$this->_table.' WHERE auto_id IN('.Core_Sql::fixInjection( $this->_withAutoId ).')' );
			$_bool=true;
		}
		$this->init();
		return $_bool;
	}
}
?>