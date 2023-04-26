<?php


/**
 * Arhitecture with database replication (master->slave)
 */
class Core_Sql_Arhitecture_Replication extends Core_Sql_Abstract {

	private $dbM, $dbS; // Zend_Db объекты для мастер и слэйв серверов

	public function __construct( Zend_Config $conf ) {
		$this->db_config=$conf;
		$this->connect();
	}

	public function connect() {
		$this->getDbConnect( $this->dbM, $this->db_config->master );
		$this->getDbConnect( $this->dbS, $this->db_config->slave );
	}

	public function prepareZendDbObject() {
		// все select на слэйв всё остальное на мастер
		// либо если переключено в режим только мастер
		if ( is_null( $this->sqlQuery )||$this->_singleMode ) {
			$this->db=$this->dbM;
		} else {
			$this->db=preg_match( '/^\s*SELECT/i', $this->sqlQuery ) ? $this->dbS:$this->dbM;
		}
	}

	public function getLastInsertId() {
		return $this->dbM->lastInsertId();
	}

	public function setDisconnect() {
		$this->dbM->closeConnection();
		$this->dbS->closeConnection();
	}
}
?>