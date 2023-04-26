<?php
class Core_Language_Manage {

	private static $_instance=NULL;

	public static function getInstance() {
		if ( self::$_instance==NULL ) {
			self::$_instance=new Core_Language_Manage();
		}
		return self::$_instance;
	}

	public static function getFields( $_strTable='', $_arrFields=array() ) {
		if ( empty( $_strTable )||empty( $_arrFields ) ) {
			return;
		}
		$_arrFields=is_array( $_arrFields ) ? $_arrFields:array( $_arrFields );
		$obj=self::getInstance();
		if ( !$obj->checkTable( $_strTable ) ) {
			return;
		}
		if ( !$obj->checkFields( $_arrFields ) ) {
			return;
		}
		return $obj->getFieldsList();
	}

	private function setTable( $_strTable='' ) {
		Core_Sql::setInsert( 'lng_tables', array(
			'table_name'=>$_strTable
		) );
	}

	private function delFields( $_arrIds=array() ) {
		Core_Sql::setExec( 'DELETE FROM lng_reference WHERE id IN('.Core_Sql::fixInjection( $_arrIds ).') AND table_id='.$this->_tableId );
	}

	private function setFields( $_arrFields=array(), $_arrInstalled=array() ) {
		$_arrInstalled=array_flip( $_arrInstalled );
		foreach( $_arrFields as $k=>$v ) {
			if ( isSet( $_arrInstalled[$v] ) ) {
				unSet( $_arrFields[$v], $_arrInstalled[$v] );
			}
		}
		if ( !empty( $_arrInstalled ) ) {
			$this->delFields( array_values( $_arrInstalled ) );
		}
		if ( empty( $_arrFields ) ) {
			return;
		}
		foreach( $_arrFields as $v ) {
			$_arrIns[]=array( 'field_name'=>$v, 'table_id'=>$this->_tableId );
		}
		Core_Sql::setMassInsert( 'lng_reference', $_arrIns );
	}

	public function checkFields( $_arrFields=array(), $_flgRecurrent=false ) {
		$_arrInstalled=Core_Sql::getKeyVal( 'SELECT id, field_name FROM lng_reference WHERE table_id='.$this->_tableId );
		$_arrDiff=Core_Common::fullArrayDiff( $_arrFields, $_arrInstalled );
		if ( !empty( $_arrDiff ) ) { // если список полей!=списку в бд
			if ( $_flgRecurrent ) {
				return false;
			}
			$this->setFields( $_arrFields, $_arrInstalled );
			return $this->checkFields( $_arrFields, true );
		}
		$this->_fields=$_arrInstalled;
		return true;
	}

	public function checkTable( $_strTable='', $_flgRecurrent=false ) {
		$_intTable=Core_Sql::getCell( 'SELECT id FROM lng_tables WHERE table_name='.Core_Sql::fixInjection( $_strTable ) );
		if ( empty( $_intTable ) ) {
			if ( $_flgRecurrent ) {
				return false;
			}
			$this->setTable( $_strTable );
			return $this->checkTable( $_strTable, true );
		}
		$this->_tableId=$_intTable;
		return true;
	}

	public function getFieldsList() {
		return $this->_fields;
	}
}
?>