<?php


/**
 * Abstract class for use Zend_Db connectors and make common operations
 */
abstract class Core_Sql_Abstract {

	public $db_config;
	public $db=null;
	public $sqlQuery=null;
	private $sqlResult=null;

	public function getDbConnect( &$db, Zend_Config $_db ) {
		$db=Zend_Db::factory( $_db->adapter, $_db->toArray() );
		if ( !empty( $_db->nosetnames ) ) {
			return;
		}
		$db->query( 'SET NAMES '.$this->db_config->codepage );
	}

	// возобновление текущего соединения
	public function reconnect() {
		$this->setDisconnect();
		$this->connect();
	}

	abstract public function prepareZendDbObject();

	protected $_singleMode=false;

	public function setSingleMode() {
		$this->_singleMode=true;
	}

	public function setReplicationMode() {
		$this->_singleMode=false;
	}

	private function setInit( $strQ='' ) {
		$this->sqlQuery=empty( $strQ )?null:$strQ;
		$this->sqlResult=null;
		$this->prepareZendDbObject();
	}

	public function getAssoc( $strQ='' ) {
		$this->setInit( $strQ );
		return $this->db->fetchAll( $this->sqlQuery );
	}

	public function getRecord( $strQ='' ) {
		$this->setInit( $strQ );
		return $this->db->fetchRow( $this->sqlQuery );
	}

	public function getField( $strQ='' ) {
		$this->setInit( $strQ );
		return $this->db->fetchCol( $this->sqlQuery );
	}

	public function getKeyVal( $strQ='' ) {
		$this->setInit( $strQ );
		return $this->db->fetchPairs( $this->sqlQuery );
	}

	public function getKeyRecord( $strQ='' ) {
		$this->setInit( $strQ );
		$_arrRes=$this->db->fetchAll( $strQ );
		if ( empty( $_arrRes ) ) {
			return array();
		}
		foreach( $_arrRes as $v ) {
			$_arrFirst=each( $v );
			$arrRes[$_arrFirst['value']]=$v;
		}
		return $arrRes;
	}

	public function getCell( $strQ='' ) {
		$this->setInit( $strQ );
		return $this->db->fetchOne( $this->sqlQuery );
	}

	public function setExec( $strQ='' ) {
		$this->setInit( $strQ );
		return $this->db->query( $this->sqlQuery );
	}

	public function setInsert( $strTbl, $arrDta ) {
		$this->setInit();
		$this->db->insert( $strTbl, $arrDta );
		return $this->db->lastInsertId();
	}

	public function setInsertUpdate( $strTbl, $arrDta, $strNdx ) {
		$this->setInit();
		if ( empty( $strNdx ) ) {
			$strNdx='id';
		}
		if ( isSet( $arrDta[$strNdx] ) ) {
			$this->db->update( $strTbl, $arrDta, $this->db->quoteIdentifier( $strNdx, true ).'='.$this->db->quote( $arrDta[$strNdx] ) );
			return $arrDta[$strNdx];
		} else {
			$this->db->insert( $strTbl, $arrDta );
			return $this->db->lastInsertId();
		}
	}

	public function setMassInsert( $_strTable, &$_arrDta ) {
		$this->setInit();
		$_arrFields=array_slice( $_arrDta, 0, 1 );
		$_arrFields=array_keys( $_arrFields[0] );
		foreach( $_arrFields as $k=>$v ) {
			$_arrFields[$k]=$this->db->quoteIdentifier( $v, true );
		}
		$_arrParts=array();
		foreach( $_arrDta as $k=>$v ) {
			$_arrParts[]='('.$this->db->quote( $v ).')';
		}
		if ( empty( $_arrParts ) ) {
			throw new Exception( Core_Errors::DB.'|Empty data parts in setMassInsert method ('.print_r( $_arrDta, true ).')' );
			return 0;
		}
		$sql='INSERT INTO '.$this->db->quoteIdentifier( $_strTable, true ).' ('.implode( ', ', $_arrFields ).') VALUES '.implode( ', ', $_arrParts );
		$this->db->beginTransaction(); // Старт транзакции явным образом
		try {
			$this->db->query( 'LOCK TABLES '.$this->db->quoteIdentifier( $_strTable, true ).' WRITE' );
			$stmt = $this->db->query( $sql );
			$this->db->query( 'UNLOCK TABLES' );
			$this->db->commit(); // закрепить
		} catch (Exception $e) {
			$this->db->rollBack(); // откатить
			throw new Exception( Core_Errors::DB.'|'.$e->getMessage().' ('.$sql.')' );
			return 0;
		}
		$result = $stmt->rowCount();
		return $result;
	}

	public function fixInjection( $_mixVar='' ) {
		$this->setInit();
		return $this->db->quote( $_mixVar );
	}
}
?>