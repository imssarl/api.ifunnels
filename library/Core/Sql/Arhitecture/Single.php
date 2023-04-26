<?php


/**
 * Single database arhitecture
 */
class Core_Sql_Arhitecture_Single extends Core_Sql_Abstract {

	public function __construct( Zend_Config $conf ) {
		$this->db_config=$conf;
		$this->connect();
	}

	public function connect() {
		$this->getDbConnect( $this->db, $this->db_config->master );
	}

	public function prepareZendDbObject() {}

	public function getLastInsertId() {
		return $this->db->lastInsertId();
	}

	public function setDisconnect() {
		$this->db->closeConnection();
	}
}
?>