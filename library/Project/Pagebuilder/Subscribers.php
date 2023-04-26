<?php
class Project_Pagebuilder_Subscribers extends Core_Data_Storage {

	protected $_table='pb_view_';
	protected $_fields=array('id', 'pb_id', 'pb_page', 'ip', 'country_id', 'added');

	protected $_afterDate=0; // c данными popup id
	protected $_withPagebuilderId=array(); // c данными popup id
	protected $_withUID=array(); // c данными popup id
	protected $_withIP=array(); // c данными popup id
	
	public function __construct( $_uid=false ){
		if( $_uid !== false ){
			$this->_table=$this->_table.$_uid;
		}
		$this->install( $_uid );
	}

	public static function install( $_uid=false){
		Core_Sql::getInstance();
		try {
			Core_Sql::setConnectToServer( 'lpb.tracker' );
			//========

			$_arrNulls=Core_Sql::getAssoc("SELECT NULL
				FROM INFORMATION_SCHEMA.COLUMNS
				WHERE table_name = 'pb_view_".$_uid."'
				AND column_name = 'pb_page';");
			if( count( $_arrNulls ) == 0 ){
				Core_Sql::setExec("ALTER TABLE `pb_view_".$_uid."` ADD `pb_page` VARCHAR(255) DEFAULT 'index' COLLATE 'utf8_unicode_ci';");
			}

			Core_Sql::setExec( "CREATE TABLE IF NOT EXISTS `pb_view_".$_uid."` (
				`id` INT(11) NOT NULL AUTO_INCREMENT,
				`pb_id` INT(11) NULL DEFAULT NULL,
				`ip` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_unicode_ci',
				`country_id` INT(4) NOT NULL DEFAULT '0',
				`added` INT(11) UNSIGNED NOT NULL DEFAULT '0',
				UNIQUE INDEX `id` (`id`)
			)
			COLLATE='utf8_general_ci'
			ENGINE=InnoDB" );
			//========
			Core_Sql::renewalConnectFromCashe();
		} catch(Exception $e) {
			Core_Sql::renewalConnectFromCashe();
			return $this;
		}
	}
	
	public function afterDate( $_date=0 ){
		$this->_afterDate=$_date;
		// Core_Sql::setExec( 'DELETE FROM '.$this->_table.' WHERE added<'.( time()-60*60*24*30 ) );
		return $this;
	}
	
	public function withPagebuilderId( $_arrIds=array() ){
		$this->_withPagebuilderId=$_arrIds;
		return $this;
	}

	public function withUID( $_arrIds=array() ){
		$this->_withUID=$_arrIds;
		return $this;
	}

	public function withIP( $_arrIPs=array() ){
		$this->_withIP=$_arrIPs;
		return $this;
	}

	protected function assemblyQuery(){
		parent:: assemblyQuery();
		if ( !empty( $this->_withSqueezeId ) ){
			$this->_crawler->set_where( 'd.pb_id IN ('.Core_Sql::fixInjection( $this->_withSqueezeId ).')' );
		}
		if ( !empty( $this->_withIP ) ){
			$this->_crawler->set_where( 'd.ip IN ('.Core_Sql::fixInjection( $this->_withIP ).')' );
		}
		if ( $this->_afterDate ){
			$this->_crawler->set_where( 'd.added > '.$this->_afterDate );
		}
	}

	protected function init(){
		parent::init();
		$this->_withIP=array();
		$this->_withPagebuilderId=array();
		$this->_withUID=array();
		$this->_afterDate=false;
	}
	
	protected function beforeSet() {
		$this->_data->setFilter();
		if( isset( $this->_data->filtered['ip'] ) ){
			$this->_data->setElement( 'country_id', Core_Sql::getCell('SELECT country_id FROM getip_countries2ip WHERE ip_start <= ' . sprintf("%u\n", ip2long($this->_data->filtered['ip'])) . ' AND ' . sprintf("%u\n", ip2long($this->_data->filtered['ip'])) . ' <= ip_end') );
		}
		return true;
	}
	
	public function setEntered( $_mix=array() ) {
		$this->_data=is_object( $_mix )? $_mix:new Core_Data( $_mix );
		return $this;
	}

	public function set() {
		if ( !$this->beforeSet() ) {
			return false;
		}
		$this->_data->setElement( 'edited', time() );
		if ( empty( $this->_data->filtered['id'] ) ) {
			$this->_data->setElement( 'added', $this->_data->filtered['edited'] );
		}
		try {
			Core_Sql::setConnectToServer( 'lpb.tracker' );
			//========
			$this->_data->setElement( 'id', Core_Sql::setInsertUpdate( $this->_table, $this->_data->setMask( $this->_fields )->getValid() ) );
			//========
			Core_Sql::renewalConnectFromCashe();
		} catch(Exception $e) {
			Core_Sql::renewalConnectFromCashe();
			return $this;
		}
		return $this->afterSet();
	}
	

	public function getList( &$mixRes ) {
		$this->_crawler=new Core_Sql_Qcrawler();
		$this->assemblyQuery();
		try {
			Core_Sql::setConnectToServer( 'lpb.tracker' );
			//========
			if ( !empty( $this->_withPaging ) ) {
				$this->_withPaging['rowtotal']=Core_Sql::getCell( $this->_crawler->get_result_counter( $_strTmp ) );
				$this->_crawler->set_paging( $this->_withPaging )->get_sql( $_strSql, $this->_paging );
			} elseif ( !$this->_onlyCount ) {
				$this->_crawler->get_result_full( $_strSql );
			}
			if ( $this->_onlyCell ) {
				$mixRes=Core_Sql::getCell( $_strSql );
			} elseif ( $this->_onlyIds ) {
				$mixRes=Core_Sql::getField( $_strSql );
			} elseif ( $this->_onlyCount ) {
				$mixRes=Core_Sql::getCell( $this->_crawler->get_result_counter() );
			} elseif ( $this->_onlyOne ) {
				$mixRes=Core_Sql::getRecord( $_strSql );
			} elseif ( $this->_toSelect ) {
				$mixRes=Core_Sql::getKeyVal( $_strSql );
			} elseif ( $this->_keyRecordForm ) {
				$mixRes=Core_Sql::getKeyRecord( $_strSql );
			} else {
				$mixRes=Core_Sql::getAssoc( $_strSql );
			}
			//========
			Core_Sql::renewalConnectFromCashe();
		} catch(Exception $e) {
			Core_Sql::renewalConnectFromCashe();
			return $this;
		}
		$this->_isNotEmpty=!empty( $mixRes );
		$this->init();
		return $this;
	}
	
	public static function checkSubscribers( $data=array() ){
		if( !isset( $data['uid'] ) && empty( $data['uid'] ) ){
			exit;
		}
		$subscribers = new Project_Pagebuilder_Subscribers($data['uid']);
		if( isset( $data['uid'] )
			&& !isset( $data['pbid'] )
			&& !isset( $data['pagename'] )
		){
			$_restrictions=new Project_Squeeze_Restrictions();
			$_restrictions->withUserId( $data['uid'] )->getList( $_arrRestrictions );
			$_intSqueezeRestrictions=0;
			if( !empty( $_arrRestrictions ) ){
				foreach( $_arrRestrictions as $_key=>$_rest ){
					if( $_rest['flg_type'] == 0 ){
						$_intSqueezeRestrictions+=$_rest['restrictions'];
					}elseif( $_rest['flg_type'] == 1 ){
						$subscribers->afterDate( $_rest['added'] )->onlyOne()->onlyCount()->withUID( $data['uid'] )->getList( $_subscribersCount );
						if( $_subscribersCount >= $_rest['restrictions'] && $_rest['added'] <= time()-60*60*24*30 ){
							$_restrictions->withIds( $_rest['id'] )->del();
							unset( $_arrRestrictions[$_key] );
						}else{
							$_intSqueezeRestrictions+=$_rest['restrictions'];
						}
					}
				}
			}
			$subscribers->afterDate( time()-60*60*24*30 )->onlyOne()->onlyCount()->getList( $_subscribersCount );
			if( $_subscribersCount >= $_intSqueezeRestrictions ){
				if( $_intSqueezeRestrictions >= 0 ){
					die('true');
				}else{ // -1 == unlim
					die('free');
				}
			}
			die('success');
		}
		if( ( !isset( $data['ip'] ) || empty( $data['ip'] ) ) 
			&& isset( $data['pbid'] ) 
			&& isset( $data['uid'] ) 
			&& !empty( $data['pbid'] ) 
			&& is_array( $data['pbid'] )
		){
			$subscribers->withPagebuilderId( $data['pbid'] )->afterDate( time()-60*60*24*30 )->getList( $_allSubscribers );
			$returnArray=array();
			foreach( $_allSubscribers as $_subscribe ){
				if( !isset( $returnArray[$_subscribe['pb_id']] ) ){
					$returnArray[$_subscribe['pb_id']]=array( 's'=>0, 'c'=>0 );
				}
				if( $_subscribe['added']<=time()-60*60*24*30 ){
					continue;
				}
				$returnArray[$_subscribe['pb_id']]['s']++;
			}
			$conversions=new Project_Pagebuilder_Conversion($data['uid']);
			$conversions->withPagebuilderId( $data['pbid'] )->afterDate( time()-60*60*24*30 )->getList( $_allConversions );
			foreach( $_allConversions as $_click ){
				if( $_click['added']<=time()-60*60*24*30 ){
					continue;
				}
				if( !isset( $returnArray[$_click['pb_id']] ) ){
					$returnArray[$_click['pb_id']]=array( 's'=>0, 'c'=>0 );
				}
				$returnArray[$_click['pb_id']]['c']++;
			}
			echo json_encode( $returnArray );
			exit;
		}
		$_restrictions=new Project_Squeeze_Restrictions();
		$_restrictions->withUserId( $data['uid'] )->getList( $_arrRestrictions );
		$subscribers=new Project_Pagebuilder_Subscribers($data['uid']);
		$_intSqueezeRestrictions=0;
		if( !empty( $_arrRestrictions ) ){
			foreach( $_arrRestrictions as $_key=>$_rest ){
				if( $_rest['flg_type'] == 0 ){
					$_intSqueezeRestrictions+=$_rest['restrictions'];
				}elseif( $_rest['flg_type'] == 1 ){
					$subscribers->afterDate( $_rest['added'] )->onlyOne()->onlyCount()->withUID( $data['uid'] )->getList( $_subscribersCount );
					if( $_subscribersCount >= $_rest['restrictions'] && $_rest['added'] <= time()-60*60*24*30 ){
						$_restrictions->withIds( $_rest['id'] )->del();
						unset( $_arrRestrictions[$_key] );
					}else{
						$_intSqueezeRestrictions+=$_rest['restrictions'];
					}
				}
			}
		}
		$subscribers->afterDate( time()-60*60*24*30 )->onlyOne()->onlyCount()->withUID( $data['uid'] )->getList( $_subscribersCount );
		if( $_subscribersCount >= $_intSqueezeRestrictions ){
			if( $_intSqueezeRestrictions >= 0 ){
				die('true');
			}else{ // -1 == unlim
				$subscribers->setEntered(array(
					'pb_id'=>$data['pbid'],
					'pb_page'=>( empty( @$data['pagename'] )? 'index': $data['pagename'] ),
					'ip'=>$data['ip'],
				))->set();
				die('free');
			}
		}else{
			$subscribers->setEntered(array(
				'pb_id'=>$data['pbid'],
				'pb_page'=>( empty( @$data['pagename'] )? 'index': $data['pagename'] ),
				'ip'=>$data['ip'],
			))->set();
			die('success');
		}
	}
}
?>