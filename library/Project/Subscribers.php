<?php
class Project_Subscribers extends Core_Data_Storage{

	protected $_table='s8rs_';
	protected $_fields=array('id', 'email', 'name', 'ip', 'tags', 'settings', 'options', 'status', 'status_data', 'flg_global_unsubscribe', 'added');

	public function __construct( $_uid=false ){
		if( $_uid !== false ){
			$this->_table=$this->_table.$_uid;
		}
		$this->install( $_uid );
	}
	
	public function install(){
		try{
			Core_Sql::setConnectToServer( 'lpb.tracker' );
			//========
			Core_Sql::setExec( "CREATE TABLE IF NOT EXISTS `".$this->_table."` (
				`id` INT(11) NOT NULL AUTO_INCREMENT,
				`email` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_unicode_ci',
				`name` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_unicode_ci',
				`ip` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_unicode_ci',
				`tags` TEXT NULL,
				`settings` TEXT NULL,
				`status` VARCHAR(15) NOT NULL DEFAULT '',
				`status_data` INT(11) UNSIGNED NOT NULL DEFAULT '0',
				`added` INT(11) UNSIGNED NOT NULL DEFAULT '0',
				UNIQUE INDEX `id` (`id`)
			);");
			//========

			$_arrNulls=Core_Sql::getAssoc("SELECT NULL FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '".$this->_table."' AND column_name = 'flg_global_unsubscribe';");
			if( count( $_arrNulls ) == 0 ){
				Core_Sql::setExec("ALTER TABLE `".$this->_table."` ADD `flg_global_unsubscribe` TINYINT NOT NULL DEFAULT '0'");
			}
			Core_Sql::renewalConnectFromCashe();
		}catch(Exception $e) {
			file_put_contents(Zend_Registry::get('config')->path->absolute->logfiles.'errors_'.$this->_table.'.txt', serialize($e->getMessage()));
			Core_Sql::renewalConnectFromCashe();
			return $this;
		}
	}

	protected $_withEmails=array();
	protected $_onlyMounthlyStatus=array();
	protected $_only6MounthlyStatus=array();
	protected $_withTags=array();

	public function withEmails( $_arrIds=array() ){
		$this->_withEmails=$_arrIds;
		return $this;
	}

	public function withTags( $_arrIds=array() ){
		$this->_withTags=$_arrIds;
		return $this;
	}

	public function onlyMounthlyStatus(){
		$this->_onlyMounthlyStatus=true;
		return $this;
	}

	public function only6MounthlyStatus(){
		$this->_only6MounthlyStatus=true;
		return $this;
	}

	protected function assemblyQuery(){
		parent::assemblyQuery();
		if ( !empty( $this->_withEmails ) ) {
			$this->_crawler->set_where( 'd.email IN ('.Core_Sql::fixInjection( $this->_withEmails ).')' );
		}
		if ( !empty( $this->_onlyMounthlyStatus ) ) {
			$this->_crawler->set_where( "d.status='' AND d.status_data>=".( time()-60*60*24*30.25 )." AND d.status_data<".time() );
		}
		if ( !empty( $this->_only6MounthlyStatus ) ) {
			$this->_crawler->set_where( "d.status_data>=".( time()-6*60*60*24*30.25 )." AND d.status_data<".time() );
		}
		if ( !empty( $this->_withTags ) ) {
			$_moreLikes=array();
			foreach( $this->_withTags as $_tagN ){
				if( !empty( $_tagN ) ){
					$_moreLikes[]= 'd.tags LIKE \'%,'.$_tagN.',%\'';
				}
				$this->_crawler->set_where( implode( ' OR ', $_moreLikes ) );
			}
		}
	}
	
	protected function init() {
		parent::init();
		$this->_withEmails=array();
		$this->_withTags=array();
		$this->_onlyMounthlyStatus=false;
		$this->_only6MounthlyStatus=false;
	}
	
	protected function beforeSet(){
		$this->_data->setFilter( array( 'trim', 'clear' ) );
		$this->withEmails( $this->_data->filtered['email'] )->onlyOne()->getList( $_arrSubscriber );
		if( isset( $_arrSubscriber ) && !empty( $_arrSubscriber ) ){
			$this->_data->setElement('id', $_arrSubscriber['id']);
			$_settings=base64_encode( serialize( ( (is_array($this->_data->filtered['settings']))?$this->_data->filtered['settings']:array() )+( (is_array($_arrSubscriber['settings']))?$_arrSubscriber['settings']:array() ) ) );
		}else{
			$_settings=base64_encode( serialize( ( (is_array($this->_data->filtered['settings']))?$this->_data->filtered['settings']:array() ) ) );
			$this->_data->setElement( 'added', time() );
		}
		$this->_data->setElement('settings', $_settings);
		if( isset( $this->_data->filtered['tags'] ) && !empty( $this->_data->filtered['tags'] ) ){
			if( !is_array( $this->_data->filtered['tags'] ) && strpos( $this->_data->filtered['tags'], ',' ) !== false ){
				$this->_data->filtered['tags']=Project_Tags::get( $this->_data->filtered['tags'] );
			}
			if( !is_array( $this->_data->filtered['tags'] ) && !empty( $this->_data->filtered['tags'] ) ){
				$this->_data->filtered['tags']=Project_Tags::get( $this->_data->filtered['tags'] );
			}
			if(Core_Acs::haveAccess( array( 'Automate' ) )){
				Project_Automation::setEvent( Project_Automation_Event::$type['CONTACT_TAGGED'] , $this->_data->filtered['tags'], $this->_data->filtered['email'], array() );
			}
			$this->_data->filtered['tags']=array_merge( ( is_array( $this->_data->filtered['tags'] )?$this->_data->filtered['tags']:array() ), ( is_array( $_arrSubscriber['tags'] )?$_arrSubscriber['tags']:array() ) );
			$this->_data->setElement( 'tags', Project_Tags::set( $this->_data->filtered['tags'] ) );
		}
		$this->_data->setFilter( array( 'trim', 'clear' ) );
		return parent::beforeSet();
	}

	public function getList( &$mixRes ){
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
			$_tagsIds=array();
			foreach( $mixRes as &$_item ){
				$_item['settings']=unserialize( base64_decode( $_item['settings'] ) );
				if( strpos( $_item['tags'], ',' ) !== false ){
					$_item['tags']=array_filter( explode( ',', trim( $_item['tags'], ',' ) ) );
				}
				if( !empty( $_item['tags'] ) && !in_array( $_item['tags'], $_tagsIds ) ){
					foreach( $_item['tags'] as $_tagId ){
						$_tagsIds[$_tagId]=$_tagId;
					}
				}
			}
			if( !empty( $_tagsIds ) ){
				$_tags = Project_Tags::get( implode( ',', $_tagsIds ) );
				foreach( $mixRes as &$item ){
					foreach( $item['tags'] as &$_tagGetName ){
						foreach( $_tags as $_tagId=>$_tagName ){
							if( $_tagId == $_tagGetName ) {
								$_tagGetName=$_tagName;
							}
						}
					}
				}
			}
		}elseif( isset( $mixRes['settings'] ) ){
			$mixRes['settings']=unserialize( base64_decode( $mixRes['settings'] ) );
			if( !empty( $mixRes['tags'] ) ){
				$mixRes['tags'] = Project_Tags::get( $mixRes['tags'] );
			}
		}
		return !empty($mixRes);
	}

	public function set() {
		if ( !$this->beforeSet() ) {
			return false;
		}
		try {
			Core_Sql::setConnectToServer( 'lpb.tracker' );
			//========
			$fields=Core_Sql::getField( 'DESCRIBE '.$this->_table );
			if ( !in_array( 'status', $fields ) ){
				Core_Sql::setExec('ALTER TABLE '.$this->_table.' ADD COLUMN status VARCHAR(15) NOT NULL DEFAULT \'\'');
				Core_Sql::setExec('ALTER TABLE '.$this->_table.' ADD COLUMN status_data INT(11) UNSIGNED NOT NULL DEFAULT \'0\'');
			}
			$this->_data->setElement( 'id', Core_Sql::setInsertUpdate( $this->_table, $this->_data->setMask( $this->_fields )->getValid() ) );
			//========
			Core_Sql::renewalConnectFromCashe();
		} catch(Exception $e) {
			Core_Sql::renewalConnectFromCashe();
			return $this;
		}
		return $this->afterSet();
	}
	
	public function setMass(){
		$this->_data->setFilter();
		try {
			Core_Sql::setConnectToServer( 'lpb.tracker' );
			//========
			$_arrSend=$_arrValues=array();
			foreach( $this->_data->filtered as $_send ){
				foreach( array_keys( $_send ) as $_name ){
					$_arrValues[$_name]=true;
				}
				$_arrSend[]=implode( '","', $_send );
			}
			Core_Sql::setExec( 'INSERT INTO '.$this->_table.' (`'.implode( '`,`', array_keys( $_arrValues ) ).'`) VALUES ("'.implode( '"),("', $_arrSend ).'")' );
			//========
			Core_Sql::renewalConnectFromCashe();
		} catch(Exception $e) {
			Core_Sql::renewalConnectFromCashe();
			$this->init();
			return false;
		}
		return;
	}
	
	public function del(){
		$_strWith=array();
		if ( !empty( $this->_withEmail ) ){
			$_strWith[]='email IN ('.Core_Sql::fixInjection( $this->_withEmail ).')';
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