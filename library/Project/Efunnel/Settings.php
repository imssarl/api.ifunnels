<?php
class Project_Efunnel_Settings extends Core_Data_Storage {

	protected $_table='lpb_efunnels_smtp_settings';
	protected $_fields=array( 'id', 'user_id', 'title', 'settings', 'flg_active', 'edited', 'added' );
	
	public static function install(){
		Core_Sql::setExec("drop table if exists lpb_efunnels_smtp_settings");
		Core_Sql::setExec( "CREATE TABLE `lpb_efunnels_smtp_settings` (
			`id` INT(11) NOT NULL AUTO_INCREMENT,
			`user_id` INT(11) NOT NULL DEFAULT '0',
			`title` VARCHAR(100) NULL DEFAULT NULL,
			`settings` TEXT NULL,
			`flg_active` INT(1) NOT NULL DEFAULT '0',
			`added` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			`edited` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			UNIQUE INDEX `id` (`id`)
		)
		COLLATE='utf8_general_ci'
		ENGINE=InnoDB;" );
		return $this;
	}
	
	protected function beforeSet(){
		$this->_data->setFilter( array( 'clear' ) );
		$this->_data->setElement('settings', base64_encode( serialize( $this->_data->filtered['settings'] ) ) );
		return true;
	}

	protected function afterSet(){
		$this->_data->filtered['settings']=unserialize( base64_decode( $this->_data->filtered['settings'] ) );
		return true;
	}
	
	public function getList( &$mixRes ){
		parent::getList( $mixRes );
		if( array_key_exists( 0, $mixRes ) ){
			foreach( $mixRes as &$_arrZeroData ){
				$_oldSettings=$_arrZeroData['settings'];
				$_arrZeroData['settings']=unserialize( base64_decode( $_arrZeroData['settings'] ) );
				if( $_arrZeroData['settings']===false ){
					$_arrZeroData['settings']=$_oldSettings;
				}
			}
		}elseif( isset( $mixRes['settings'] ) ){
			$_oldSettings=$mixRes['settings'];
			$mixRes['settings']=unserialize( base64_decode( $mixRes['settings'] ) );
			if( $mixRes['settings']===false ){
				$mixRes['settings']=$_oldSettings;
			}
		}
		return $this;
	}
}
?>