<?php
/**
 * Project_Mooptin_Moemail::install()
 */
class Project_Mooptin_Moemail extends Core_Data_Storage{

	protected $_table='mo_email';
	protected $_fields=array('id', 'mo_id', 'hach', 'settings', 'edited', 'added');

	public static function install(){
		Core_Sql::setExec('DROP TABLE IF EXISTS mo_email');
		Core_Sql::setExec("CREATE TABLE `mo_email` (
			`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
			`mo_id` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			`hach` CHAR(32) NULL,
			`settings` TEXT NULL,
			`added` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			`edited` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			PRIMARY KEY (`id`)
		)
		COLLATE='utf8_general_ci'
		ENGINE=MyISAM");
	}

	private $_flgSet=false;
	
	protected function beforeSet() {
		
		//Project_Mooptin_Moemail::install();
		
		$this->_data->setFilter( array( 'clear' ) );
		$this->_data->setElement('settings', base64_encode( serialize( $this->_data->filtered['settings'] ) ) );
		return true;
	}
	
	protected function afterSet() {
		if( empty( $this->_data->filtered['hach'] ) ){
			$this->_data->setElement('hach', md5( $this->_data->filtered['id'] ) );
			Core_Sql::setInsertUpdate( $this->_table, $this->_data->setMask( $this->_fields )->getValid() );
		}
		$this->_data->filtered['settings']=unserialize( base64_decode( $this->_data->filtered['settings'] ) );
		return true;
	}
	
	protected function init(){
		parent::init();
		$this->_flgSet=false;
		$this->_withHach=false;
	}
	
	public function getList( &$mixRes ) {
		parent::getList( $mixRes );
		if( array_key_exists( 0, $mixRes ) ) {
			foreach( $mixRes as &$_arrZeroData ) {
				$_arrZeroData['settings']=unserialize( base64_decode( $_arrZeroData['settings'] ) );
			}
		}elseif( isset( $mixRes['settings'] ) ) {
			$mixRes['settings']=unserialize( base64_decode( $mixRes['settings'] ) );
		}
		return $this;
	}
	
	private $_withHach=false;
	
	public function withHach( $_strHach='' ){
		if( !empty( $_strHach ) ){
			$this->_withHach=$_strHach;
		}
		return $this;
	}
	
	protected function assemblyQuery(){
		parent::assemblyQuery();
		if( $this->_withHach ){
			$this->_crawler->set_where('d.hach="'.$this->_withHach.'"' );
		}
	}
}
?>