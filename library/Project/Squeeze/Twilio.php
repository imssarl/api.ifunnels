<?php
class Project_Squeeze_Twilio extends Core_Data_Storage{
	protected $_table='squeeze_twilio';
	protected $_fields=array('id','user_id','phone','country','added','edited');

	protected $_withUserId=array();
	
	public static function install() {
		Core_Sql::setExec("drop table if exists squeeze_twilio");

		Core_Sql::setExec( "CREATE TABLE `squeeze_twilio` (
			`id` INT(11) NOT NULL AUTO_INCREMENT,
			`phone` VARCHAR(20) NULL DEFAULT NULL,
			`country` VARCHAR(2) NULL DEFAULT NULL,
			`user_id` INT(11) NOT NULL DEFAULT '0',
			`added` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			`edited` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			UNIQUE INDEX `id` (`id`)
		)
		COLLATE='utf8_general_ci'
		ENGINE=InnoDB;" );
		return $this;
	}
	
	protected $_onlyNumbers=false; // только номера телефонов
	
	public function onlyNumbers() {
		$this->_onlyNumbers=true;
		return $this;
	}
	
	protected $_withCountry=false; // только указанную страну
	
	public function withCountry( $_str='' ) {
		$this->_withCountry=$_str;
		return $this;
	}
	
	protected function init() {
		parent::init();
		$this->_onlyNumbers=false;
		$this->_withCountry=false;
	}
	
	protected function assemblyQuery(){
		parent::assemblyQuery();
		if( $this->_withCountry ){
			$this->_crawler->set_where('d.country='.Core_Sql::fixInjection($this->_withCountry));
		}
		if( $this->_onlyNumbers ){
			$this->_crawler->clean_select();
			$this->_crawler->set_select( 'd.phone' );
		}
	}
	
	public function getList( &$mixRes ) {
		$this->_crawler=new Core_Sql_Qcrawler();
		$this->assemblyQuery();
		if ( !empty( $this->_withPaging ) ) {
			$this->_withPaging['rowtotal']=Core_Sql::getCell( $this->_crawler->get_result_counter( $_strTmp ) );
			$this->_crawler->set_paging( $this->_withPaging )->get_sql( $_strSql, $this->_paging );
		} elseif ( !$this->_onlyCount ) {
			$this->_crawler->get_result_full( $_strSql );
		}
		if ( $this->_onlyNumbers ) {
			$mixRes=Core_Sql::getField( $_strSql );
		} elseif ( $this->_onlyCell ) {
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
		$this->_isNotEmpty=!empty( $mixRes );
		$this->init();
		return $this;
	}
	
}
?>