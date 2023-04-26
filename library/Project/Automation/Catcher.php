<?php
class Project_Automation_Catcher extends Core_Data_Storage {

	protected $_table='automation_catcher';
	protected $_fields=array( 'id', 'user_id', 'email', 'auto_ids', 'event_type', 'event_value', 'parameters', 'added' );

	public static function install(){
		Core_Sql::setExec("drop table if exists automation_catcher");
		Core_Sql::setExec( "CREATE TABLE `automation_catcher` (
			`id` INT(11) NOT NULL AUTO_INCREMENT,
			`user_id` INT(11) NOT NULL DEFAULT '0',
			`auto_ids` VARCHAR(255) NOT NULL DEFAULT '',
			`email` VARCHAR(255) NOT NULL DEFAULT '',
			`event_type` INT(2) NOT NULL DEFAULT '0',
			`event_value` TEXT NULL,
			`parameters` TEXT NULL,
			`added` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			UNIQUE INDEX `id` (`id`)
		)
		COLLATE='utf8_general_ci'
		ENGINE=InnoDB;" );
		return $this;
	}

	protected $_onlyLast=false;

	public function onlyLast(){
		$this->_onlyLast=true;
		return $this;
	}
	
	protected function assemblyQuery(){
		parent::assemblyQuery();
		if ( !empty( $this->_onlyLast ) ){
			$this->_crawler->set_where( 'd.added <= '.( time()-60 ) );
			$this->_crawler->set_limit( '0, 1000' ); // обрабатывает ближайшие 1000 записей, проверить по времени сколько будет занимать обработка и увеличить
		}
	}

	protected function init(){
		parent::init();
		$this->_onlyLast=false;
	}

	protected function beforeSet() {
		$this->_data->setFilter( array( 'clear' ) );
		$this->_data->setElements(array(
			'parameters'=>base64_encode( serialize( $this->_data->filtered['parameters'] ) ),
		));
		return true;
	}
	
	protected function afterSet() {
		$this->_data->filtered['parameters']=unserialize( base64_decode( $this->_data->filtered['parameters'] ) );
		return true;
	}
	
	public function getList( &$mixRes ){
		parent::getList( $mixRes );
		if( !empty( $mixRes ) ){
			if( isset( $mixRes['parameters'] ) ){
				$mixRes['parameters']=unserialize(base64_decode($mixRes['parameters']));
			}else{
				foreach( $mixRes as &$_res ){
					if( isset( $_res['parameters'] ) ){
						$_res['parameters']=unserialize(base64_decode($_res['parameters']));
					}
				}
			}
		}
		return $this;
	}
}
?>