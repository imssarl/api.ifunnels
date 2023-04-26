<?php
class Project_Automation_Filter extends Core_Data_Storage {

	protected $_table='automation_filter';
	protected $_fields=array( 'id', 'auto_id', 'name', 'filter_type', 'filter_values', 'settings', 'edited', 'added' );

	public static function install(){
		Core_Sql::setExec("drop table if exists automation_filter");
		Core_Sql::setExec( "CREATE TABLE `automation_filter` (
			`id` INT(11) NOT NULL AUTO_INCREMENT,
			`auto_id` INT(11) NOT NULL DEFAULT '0',
			`filter_type` INT(2) NOT NULL DEFAULT '0',
			`name` VARCHAR(10) NOT NULL DEFAULT 'FX',
			`filter_values` TEXT NULL,
			`settings` TEXT NULL,
			`edited` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			`added` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			UNIQUE INDEX `id` (`id`)
		)
		COLLATE='utf8_general_ci'
		ENGINE=InnoDB;" );
		return $this;
	}
	
	public static $type=array( //(ability to add AND / OR so we combine filters)
		'HAVE_TAGS'=>1, //Contact has / does not have tag
		'OPEN_EMAILS'=>2, //Has opened Email
		'CLICK_EMAIL_LINK'=>3, //Has clicked Email link
		'HAVE_EF'=>4, //Is / Is Not in Email Funnel
		'COMPLEAT_EF'=>5, //Has completed Email Funnel
		'PAUSE_EF'=>6, //Is paused in Email Funnel
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
			if( isset( $mixRes['id'] ) ){
				$mixRes['settings']=unserialize(base64_decode($mixRes['settings']));
			}else{
				foreach( $mixRes as &$_res ){
					$_res['settings']=unserialize(base64_decode($_res['settings']));
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