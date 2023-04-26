<?php


/**
 * Template spots management
 */
class Project_Sites_Spots {

	private $_legalTypes=array( /*Project_Sites::PSB*/ ); // пока в бд хранятся споты только для псб
	private $_isLegal=true;

	private $_siteType=0; // тип сайта
	private $_siteCode=''; // код сайта (см. Project_Sites::$code)
	//private $_userId=0;

	private $_table='es_template_spots';
	private $_fields=array( 'id', 'template_id', 'name', 'filename', 'width', 'height' );

	public function __construct( $_type='' ) {
		/*if ( !Zend_Registry::get( 'objUser' )->getId( $_int ) ) {
			throw new Exception( Core_Errors::DEV.'|Zend_Registry::get( \'objUser\' )->getId( $_int ) is not return an User Id' );
			return;
		}*/
		if ( empty( Project_Sites::$code[$_type] ) ) {
			throw new Exception( Core_Errors::DEV.'|Site Type not set' );
			return;
		}
		//$this->_userId=$_int;
		$this->_siteType=$_type;
		$this->_siteCode=Project_Sites::$code[$_type];
		if ( !in_array( $this->_siteType, $this->_legalTypes ) ) {
			$this->_isLegal=false;
		}
	}

	public function isLegal() {
		return $this->_isLegal;
	}

	public function del( $_mix=array() ) {
		if ( !$this->isLegal() ) {
			return;
		}
		$_mix=is_array( $_mix )? $_mix:array( $_mix );
		if ( empty( $_mix ) ) {
			return;
		}
		Core_Sql::setExec( 'DELETE FROM '.$this->_table.' WHERE template_id IN('.Core_Sql::fixInjection( $_mix ).')' );
	}

	public function set( $_arrSpots=array(), $_intTemplateId=0 ) {
		if ( !$this->isLegal() ) {
			return;
		}
		if ( empty( $_arrSpots )||empty( $_intTemplateId ) ) {
			return;
		}
		$this->del( $_intTemplateId );
		foreach( $_arrSpots as $k=>$v ) {
			$_arrSpots[$k]['template_id']=$_intTemplateId;
		}
		Core_Sql::setMassInsert( $this->_table, $_arrSpots );
	}

	public function getList( &$arrRes, $_mix=array() ) {
		if ( !$this->isLegal() ) {
			return;
		}
		$_mix=is_array( $_mix )? $_mix:array( $_mix );
		$arrRes=Core_Sql::getAssoc( 'SELECT * FROM '.$this->_table.' WHERE template_id IN('.Core_Sql::fixInjection( $_mix ).')' );
	}
}
?>