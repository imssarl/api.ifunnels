<?php
class Project_Squeeze_Conversion{

	protected $_table='lpb_click_';
	protected $_fields=array('id', 'squeeze_id', 'ip', 'country_id', 'added');

	protected $_afterDate=0; // c данными popup id
	protected $_withSqueezeId=array(); // c данными popup id
	protected $_withIP=array(); // c данными popup id
	protected $_onlyCount=false; // только количество
	protected $_onlyOne=false; // только одна запись

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
			Core_Sql::setExec( "CREATE TABLE IF NOT EXISTS `lpb_click_".$_uid."` (
				`id` INT(11) NOT NULL AUTO_INCREMENT,
				`squeeze_id` INT(11) NULL DEFAULT NULL,
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
	
	public function clearOld() {
		// Core_Sql::setExec( 'DELETE FROM '.$this->_table.' WHERE added<'.( time()-60*60*24*30 ) );
		return $this;
	}
	
	public function withSqueezeId( $_arrIds=array() ) {
		$this->_withSqueezeId=$_arrIds;
		return $this;
	}

	public function withIP( $_arrIPs=array() ) {
		$this->_withIP=$_arrIPs;
		return $this;
	}

	public function onlyCount() {
		$this->_onlyCount=true;
		return $this;
	}

	public function onlyOne() {
		$this->_onlyOne=true;
		return $this;
	}

	protected function assemblyQuery() {
		$this->_crawler->set_select( 'd.*' );
		$this->_crawler->set_from( $this->_table.' d' );
		if ( !empty( $this->_withSqueezeId ) ) {
			$this->_crawler->set_where( 'd.squeeze_id IN ('.Core_Sql::fixInjection( $this->_withSqueezeId ).')' );
		}
		if ( !empty( $this->_withIP ) ) {
			$this->_crawler->set_where( 'd.ip IN ('.Core_Sql::fixInjection( $this->_withIP ).')' );
		}
		if ( $this->_afterDate ){
			$this->_crawler->set_where( 'd.added > '.$this->_afterDate );
		}
	}

	public function getList( &$mixRes ) {
		$this->_crawler=new Core_Sql_Qcrawler();
		$this->assemblyQuery();
		try {
			Core_Sql::setConnectToServer( 'lpb.tracker' );
			//========
			if ( !$this->_onlyCount ) {
				$this->_crawler->get_result_full( $_strSql );
			}
			if ( $this->_onlyCount ) {
				$mixRes=Core_Sql::getCell( $this->_crawler->get_result_counter() );
			} elseif ( $this->_onlyOne ) {
				$mixRes=Core_Sql::getRecord( $_strSql );
			} else {
				$mixRes=Core_Sql::getAssoc( $_strSql );
			}
			//========
			Core_Sql::renewalConnectFromCashe();
		} catch(Exception $e) {
			Core_Sql::renewalConnectFromCashe();
			return $this;
		}
		$this->init();
		return $this;
	}

	protected function init(){
		$this->_onlyCount=false;
		$this->_onlyOne=false;
		$this->_withIP=array();
		$this->_withSqueezeId=array();
	}

	public function setEntered( $_mix=array() ) {
		$this->_data=is_object( $_mix )? $_mix:new Core_Data( $_mix );
		return $this;
	}

	public function set() {
		$this->_data->setFilter();
		if( empty( $this->_data->filtered['id'] ) ) {
			$this->_data->setElement( 'added', time() );
		}
		$this->_data->setElement( 'country_id', Core_Sql::getCell('SELECT country_id FROM getip_countries2ip WHERE ip_start <= ' . sprintf("%u\n", ip2long($this->_data->filtered['ip'])) . ' AND ' . sprintf("%u\n", ip2long($this->_data->filtered['ip'])) . ' <= ip_end') );
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
	}
	
	public static function addConversion( $data=array() ){
		$conversions=new Project_Squeeze_Conversion( $data['uid'] );
		$conversions->setEntered(array(
			'squeeze_id'=>$data['id'],
			'ip'=>$data['ip'],
		))->set();
		if( isset( $data['splt'] ) && !empty( $data['splt'] ) ){
			$link=new Project_Squeeze_Split_Link();
			$link->withSplitIds(array($data['splt']))->withIds(array($data['id']))->updateClicks();
		}
		$_arrGet=array_intersect( array_keys( $data ), array( "utm_source","utm_medium","utm_term","utm_content","utm_campaign" ) );
		if( count( $_arrGet ) > 0 ){
			$utm=new Project_Squeeze_GoogleUTM();
			$utm->setEntered(array(
					"ip"=>$data['ip'],
					'squeeze_id'=>$data['id'],
					"utm_source"=>@$data["utm_source"],
					"utm_medium"=>@$data["utm_medium"],
					"utm_term"=>@$data["utm_term"],
					"utm_content"=>@$data["utm_content"],
					"utm_campaign"=>@$data["utm_campaign"],
					"click"=>1
				))->set();
		}
		die('success');
	}
	
}
?>