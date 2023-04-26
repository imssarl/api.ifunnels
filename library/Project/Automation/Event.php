<?php
class Project_Automation_Event extends Core_Data_Storage {

	protected $_table='automation_event';
	protected $_fields=array( 'id', 'user_id', 'auto_id', 'event_type', 'event_values', 'settings', 'edited', 'added' );

	public static function install(){
		Core_Sql::setExec("drop table if exists automation_event");
		Core_Sql::setExec( "CREATE TABLE `automation_event` (
			`id` INT(11) NOT NULL AUTO_INCREMENT,
			`user_id` INT(11) NOT NULL DEFAULT '0',
			`auto_id` INT(11) NOT NULL DEFAULT '0',
			`event_type` INT(2) NOT NULL DEFAULT '0',
			`event_values` TEXT NULL,
			`settings` TEXT NULL,
			`edited` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			`added` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			UNIQUE INDEX `id` (`id`)
		)
		COLLATE='utf8_general_ci'
		ENGINE=InnoDB;" );
		return $this;
	}
	
	public static $type=array(
		'CONTACT_CREATED'=>1, //Contact created
		'CONTACT_TAGGED'=>2, //Contact tagged (multiple entries possible with OR)
		'CONTACT_ADDED_EF'=>3, //Contact added to Email Funnel (multiple entries with OR)
		'CONTACT_ADDED_LC'=>4, //Contact added to Lead Channel
		'CONTACT_COMPLEATED_EF'=>5, //Contact completed Email Funnel
		'OPEN_EMAIL'=>6, //Opened an email
		'CLICK_EMAIL_LINK'=>7, //Clicked in an email
		'VISIT_PAGE'=>8, //Visited a landing page (would require tracking to be set, but just so we keep that in mind)
		'REMOVE_TAG'=>9, //Tag removed
	);

	protected $_withAutoId=false;
	protected $_withEventType=false;
	protected $_withEventValue=false;
	protected $_onlyAutoIds=false;
	
	public function onlyAutoIds(){
		$this->_onlyAutoIds=true;
		return $this;
	}
	
	public function withAutoId( $_var=false ){
		$this->_withAutoId=$_var;
		return $this;
	}

	public function withEventType( $_int=false ){
		$this->_withEventType=$_int;
		return $this;
	}

	public function withEventValue( $_str=false ){
		$this->_withEventValue=$_str;
		return $this;
	}

	protected function assemblyQuery(){
		parent::assemblyQuery();
		if ( $this->_onlyAutoIds ){
			$this->_crawler->clean_select();
			$this->_crawler->set_select( 'd.auto_id' );
			$this->_crawler->set_group( 'd.auto_id' );
		}
		if ( !empty( $this->_withEventType ) ){
			$this->_crawler->set_where( 'd.event_type="'.$this->_withEventType.'"' );
		}
		if ( !empty( $this->_withEventValue ) ){
			$this->_crawler->set_where( 'd.event_values REGEXP "(^|,)+'.$this->_withEventValue.'(,|$)+"' );
		}
		if ( !empty( $this->_withAutoId ) ){
			$this->_crawler->set_where( 'd.auto_id IN ( '.Core_Sql::fixInjection( $this->_withAutoId ).')' );
		}
	}

	protected function init(){
		parent::init();
		$this->_withAutoId=false;
		$this->_withEventType=false;
		$this->_withEventValue=false;
		$this->_onlyAutoIds=false;
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
		$this->_crawler=new Core_Sql_Qcrawler();
		$this->assemblyQuery();
		if ( !empty( $this->_withPaging ) ){
			$this->_withPaging['rowtotal']=Core_Sql::getCell( $this->_crawler->get_result_counter( $_strTmp ) );
			$this->_crawler->set_paging( $this->_withPaging )->get_sql( $_strSql, $this->_paging );
		} elseif ( !$this->_onlyCount ){
			$this->_crawler->get_result_full( $_strSql );
		}
		if ( $this->_onlyCell ){
			$mixRes=Core_Sql::getCell( $_strSql );
		} elseif ( $this->_onlyIds || $this->_onlyAutoIds ){
			$mixRes=Core_Sql::getField( $_strSql );
		} elseif ( $this->_onlyCount ){
			$mixRes=Core_Sql::getCell( $this->_crawler->get_result_counter() );
		} elseif ( $this->_onlyOne ){
			$mixRes=Core_Sql::getRecord( $_strSql );
		} elseif ( $this->_toSelect ){
			$mixRes=Core_Sql::getKeyVal( $_strSql );
		} elseif ( $this->_keyRecordForm ){
			$mixRes=Core_Sql::getKeyRecord( $_strSql );
		} else {
			$mixRes=Core_Sql::getAssoc( $_strSql );
		}
		$this->_isNotEmpty=!empty( $mixRes );
		$this->init();
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