<?php
class Project_Filters extends Core_Data_Storage {

	protected $_table='lpb_filters';
	protected $_fields=array( 'id', 'name', 'type', 'user_id', 'options', 'edited', 'added' );

	public static function install(){
		Core_Sql::setExec("drop table if exists lpb_filters");
		Core_Sql::setExec( "CREATE TABLE `lpb_filters` (
			`id` INT(11) NOT NULL AUTO_INCREMENT,
			`user_id` INT(11) NOT NULL DEFAULT '0',
			`options` TEXT NULL,
			`type` VARCHAR(50) NOT NULL DEFAULT '',
			`name` VARCHAR(256) NOT NULL DEFAULT '',
			`added` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			`edited` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			UNIQUE INDEX `id` (`id`)
		)
		COLLATE='utf8_general_ci'
		ENGINE=InnoDB;" );
		return $this;
	}
	
	public function getList( &$mixRes ){
		parent::getList( $mixRes );
		if( array_key_exists( 0, $mixRes ) ){
			foreach( $mixRes as &$_arrZeroData ){
				if( isset( $_arrZeroData['options'] ) ){
					$_oldSettings=$_arrZeroData['options'];
					$_arrZeroData['options']=unserialize( base64_decode( $_arrZeroData['options'] ) );
					if( $_arrZeroData['options']===false ){
						$_arrZeroData['options']=$_oldSettings;
					}
				}
			}
		}elseif( isset( $mixRes['options'] ) ){
			$_oldSettings=$mixRes['options'];
			$mixRes['options']=unserialize( base64_decode( $mixRes['options'] ) );
			if( $mixRes['options']===false ){
				$mixRes['options']=$_oldSettings;
			}
		}
		$this->init();
		return $this;
	}
	
	protected $_withType=false; // 
	
	public function withType( $_str=false ){
		$this->_withType=$_str;
		return $this;
	}

	protected $_withUserId=false;
	
	public function withUserId( $_arrIds=array() ){
		$this->_withUserId=$_arrIds;
		return $this;
	}
	
	protected function assemblyQuery(){
		parent::assemblyQuery();
		if ( !empty( $this->_withType ) ){
			$this->_crawler->set_where( 'd.type IN ('.Core_Sql::fixInjection( $this->_withType ).')' );
		}
		if ( !empty( $this->_withUserId ) ){
			$this->_crawler->set_where( 'd.user_id IN ('.Core_Sql::fixInjection( $this->_withUserId ).')' );
		}
	}

	protected function init(){
		parent::init();
		$this->_withUserId=array();
		$this->_withType=false;
	}
	
	
	protected function beforeSet(){
		$this->_data->setFilter( array( 'clear' ) );
		$_updateOptions=base64_encode( serialize( $this->_data->filtered['options'] ) );
		$this->_data->setElement('options', $_updateOptions );
		return true;
	}
	
	protected function afterSet(){
		$this->_data->filtered['options']=unserialize( base64_decode( $this->_data->filtered['options'] ) );
		return true;
	}
}
?>