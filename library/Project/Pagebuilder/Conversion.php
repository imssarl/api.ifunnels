<?php
class Project_Pagebuilder_Conversion{

	protected $_table='pb_click_';
	protected $_fields=array('id', 'pb_id', 'pb_page', 'ip', 'country_id', 'added');

	protected $_afterDate=0; // c данными popup id
	protected $_withPagebuilderId=array(); // c данными popup id
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

			$_arrNulls=Core_Sql::getAssoc("SELECT NULL
				FROM INFORMATION_SCHEMA.COLUMNS
				WHERE table_name = 'pb_click_".$_uid."'
				AND column_name = 'pb_page';");
			if( count( $_arrNulls ) == 0 ){
				Core_Sql::setExec("ALTER TABLE `pb_click_".$_uid."` ADD `pb_page` VARCHAR(255) DEFAULT 'index' COLLATE 'utf8_unicode_ci';");
			}

			Core_Sql::setExec( "CREATE TABLE IF NOT EXISTS `pb_click_".$_uid."` (
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
	
	public function clearOld() {
		// Core_Sql::setExec( 'DELETE FROM '.$this->_table.' WHERE added<'.( time()-60*60*24*30 ) );
		return $this;
	}
	
	public function withPagebuilderId( $_arrIds=array() ) {
		$this->_withPagebuilderId=$_arrIds;
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
		if ( !empty( $this->_withPagebuilderId ) ) {
			$this->_crawler->set_where( 'd.pb_id IN ('.Core_Sql::fixInjection( $this->_withPagebuilderId ).')' );
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
		$this->_withPagebuilderId=array();
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
		if( isset( $this->_data->filtered['ip'] ) ){
			$this->_data->setElement( 'country_id', Core_Sql::getCell('SELECT country_id FROM getip_countries2ip WHERE ip_start <= ' . sprintf("%u\n", ip2long($this->_data->filtered['ip'])) . ' AND ' . sprintf("%u\n", ip2long($this->_data->filtered['ip'])) . ' <= ip_end') );
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
	}
	
	public static function addConversion( $data=array() ){
		$conversions = new Project_Pagebuilder_Conversion( $data['uid'] );
		$conversions
			->setEntered(array(
				'pb_id'	=> $data['pbid'],
				'pb_page'=>( empty( @$data['pagename'] )? 'index': $data['pagename'] ),
				'ip'	=> $data['ip'],
			))
			->set();
		
		$data['url'] = parse_url($data['url'], PHP_URL_QUERY);
		if( !empty($data['url']) )
		{
			parse_str($data['url'], $data['url']);

			$_arrGet = array_intersect(array_keys($data['url']), array("utm_source", "utm_medium", "utm_term", "utm_content", "utm_campaign"));
			if( count( $_arrGet ) > 0 )
			{
				$utm=new Project_Pagebuilder_GoogleUTM();
				$utm
					->setEntered(array(
						"ip"			=> $data['ip'],
						"pb_id"			=> $data['pbid'],
						"utm_source"	=> @$data['url']["utm_source"],
						"utm_medium"	=> @$data['url']["utm_medium"],
						"utm_term"		=> @$data['url']["utm_term"],
						"utm_content"	=> @$data['url']["utm_content"],
						"utm_campaign"	=> @$data['url']["utm_campaign"],
						"click"			=> 1
					))
					->set();
			}
		}
	}
	
}
?>