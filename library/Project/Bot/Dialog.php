<?php
class Project_Bot_Dialog extends Core_Data_Storage {

	protected $_table='bot_dialog';
	protected $_fields=array('id','botai_id','user_id','question','answer','settings','edited','added');
	
	public static function install(){	
		Core_Sql::setExec('DROP TABLE IF EXISTS bot_dialog');
		Core_Sql::setExec('CREATE TABLE `bot_dialog` (
				`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
				`botai_id` int(11) unsigned NOT NULL DEFAULT \'0\',
				`user_id` int(11) unsigned NOT NULL DEFAULT \'0\',
				`question` TEXT NULL,
				`answer` TEXT NULL,
				`settings` TEXT NULL,
				`edited` int(11) unsigned NOT NULL DEFAULT \'0\',
				`added` int(11) unsigned NOT NULL DEFAULT \'0\',
				PRIMARY KEY (`id`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8'
		);
	}

	private $_withUserId=false;

	public function withUserId( $_mixIds ){
		if( isset($_mixIds) ){
			$this->_withUserId=$_mixIds;
		}
		return $this;
	}
	
	protected function init(){
		parent::init();
		$this->_withUserId=false;
	}

	protected function assemblyQuery(){
		parent::assemblyQuery();
		if( $this->_withUserId ){
			$this->_crawler->set_where('d.user_id IN ('. Core_Sql::fixInjection($this->_withUserId) .')');
		}
	}
	
	protected function beforeSet(){
		$this->_data->setFilter( array( 'clear' ) );
		$this->_data->setElements(array(
			'settings'=>base64_encode( serialize( $this->_data->filtered['settings'] ) )
		));
		return true;
	}

	protected function afterSet(){
		$this->_data->filtered['settings']=unserialize( base64_decode( $this->_data->filtered['settings'] ) );
		return true;
	}
	
	public function getList( &$mixRes ){
		$_onlyIds=$this->_onlyIds;
		parent::getList( $mixRes );
		if( empty($mixRes) ){
			return $this;
		}
		if( $_onlyIds ){
			return $this;
		}
		if( array_key_exists( 0, $mixRes ) ){
			foreach( $mixRes as &$_arrZeroData ){
				if( isset( $_arrZeroData['settings'] ) ) $_arrZeroData['settings']=unserialize( base64_decode( $_arrZeroData['settings'] ) );
			}
		}else{
			if( isset( $mixRes['settings'] ) ) $mixRes['settings']=unserialize( base64_decode( $mixRes['settings'] ) );
		}
	}
}
?>