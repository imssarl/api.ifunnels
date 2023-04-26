<?php


/**
 * Tags types manage
 * @internal управление типами тэгов
 */

class Core_Tags_Types extends Core_Data_Storage {

	private static $_instance=NULL;
	private $_types=array();
	private $_withTitle=false;

	protected $_table='tag_types';
	protected $_fields=array('id','title');

	public static function getInstance() {
		if ( self::$_instance==NULL ) {
			self::$_instance=new Core_Tags_Types();
		}
		return self::$_instance;
	}


	public function getTypeByTitle( $_str='' ) {
		if ( empty( $_str ) ) {
			throw new Exception( Core_Errors::DEV.'|Tags type is empty' );
		}
		if( $this->withTitle( $_str )->onlyCell()->getList( $_id )->checkEmpty() ){
			return $_id;
		}
		$this->setEntered( array('title'=>$_str) )->set();
		$this->withTitle( $_str )->onlyCell()->onlyIds()->getList( $_id );
		return $_id;
	}

	public function withTitle( $_str ){
		if( empty($_str) ){
			return $this;
		}
		$this->_withTitle=$_str;
		return $this;
	}

	protected function beforeSetMass( $k, $_arrRow=array() ) {
		if ( !empty( $_arrRow['del'] ) ) {
			$this->_del[]=$_arrRow['id'];
			return false;
		}
		return true;
	}

	protected function afterSetMass() {
		// удаляем отмеченные
		if ( !empty( $this->_del ) ) {
			$this->withIds( $this->_del )->del();
			$this->_del=array();
		}
		return true;
	}

	public function del(){
		Core_Sql::setExec('DELETE FROM tag_link WHERE type_id IN ('.join(',',$this->_withIds).')');
		parent::del();
	}

	protected function init(){
		parent::init();
		$this->_withTitle=false;
	}

	protected function assemblyQuery(){
		parent::assemblyQuery();
		if( !empty($this->_withTitle) ){
			$this->_crawler->set_where('d.title ='.Core_Sql::fixInjection($this->_withTitle));
		}
	}

}
?>