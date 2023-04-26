<?php
class Project_Iam_Manager extends Core_Data_Storage{

	protected $_table='iam_project_sites';
	protected $_fields=array( 'id', 'site_url', 'settings', 'user_id', 'edited', 'added' );

	public static function install() {
		Core_Sql::setExec( "CREATE TABLE `iam_project_sites` (
			`id` INT(11) NOT NULL AUTO_INCREMENT,
			`site_url` VARCHAR(255) NOT NULL,
			`settings` TEXT NOT NULL,
			`edited` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			`added` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			UNIQUE INDEX `id` (`id`)
		)
		COLLATE='utf8_general_ci'
		ENGINE=MyISAM;" );
		return $this;
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
	
	public function getList( &$mixRes ) {
		parent::getList( $mixRes );
		if( empty($mixRes) ){
			return $this;
		}
		if( array_key_exists( 0, $mixRes ) ) {
			foreach( $mixRes as &$_data ) {
				$_data['settings']=unserialize( base64_decode( $_data['settings'] ) );
			}
		}else{
			$mixRes['settings']=unserialize( base64_decode( $mixRes['settings'] ) );
		}
		return $this;
	}
	
}
?>