<?php
class Project_Squeeze_GoogleUTM{

	protected $_table='lpb_utm';
	protected $_fields=array('id', 'squeeze_id', 'ip', 'country_id', "utm_source","utm_medium","utm_term","utm_content","utm_campaign", 'click', 'view', 'added');

	protected $_afterDate=0; // c данными popup id
	protected $_withSqueezeId=array(); // c данными popup id
	protected $_withIP=array(); // c данными popup id
	protected $_onlyCount=false; // только количество
	protected $_onlyOne=false; // только одна запись

	public static function install(){
		$_obj=new Project_Squeeze_Restrictions();
		Core_Sql::setExec( "DROP TABLE IF EXISTS lpb_utm" );
		Core_Sql::setExec("CREATE TABLE lpb_utm (
			id INT(11) NOT NULL AUTO_INCREMENT,
			squeeze_id INT(11) NOT NULL DEFAULT '0',
			ip VARCHAR(255) NULL DEFAULT NULL,
			country_id INT(11) NOT NULL DEFAULT '0',
			
			utm_source VARCHAR(255) NULL DEFAULT NULL,
			utm_medium VARCHAR(255) NULL DEFAULT NULL,
			utm_term VARCHAR(255) NULL DEFAULT NULL,
			utm_content VARCHAR(255) NULL DEFAULT NULL,
			utm_campaign VARCHAR(255) NULL DEFAULT NULL,

			added INT(11) UNSIGNED NOT NULL DEFAULT '0',
			UNIQUE INDEX id (id)
		)
		COLLATE='utf8_general_ci'
		ENGINE=InnoDB;");
	}

	public static function update(){
		$_obj=new Project_Squeeze_Restrictions();
		Core_Sql::setExec( "ALTER TABLE `lpb_utm`
			ADD COLUMN `click` INT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `utm_campaign`,
			ADD COLUMN `view` INT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `utm_campaign`;");
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
		$this->init();
		return $this;
	}

	protected function init() {
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
		$this->_data->setElement( 'id', Core_Sql::setInsertUpdate( $this->_table, $this->_data->setMask( $this->_fields )->getValid() ) );
	}
	
}
?>