<?php


/**
 * View as html (smarty-template)
 */
class Core_View_Smarty implements Core_View_Interface {

	private static $_isLoaded=false;

	public function __construct() {
		$this->load();
		$this->_smarty=new Smarty();
		$this->_smarty->setCompileDir( Zend_Registry::get( 'config' )->path->relative->compiled );
	}

	private function load() {
		if ( self::$_isLoaded ) {
			return;
		}
		$_path=Zend_Registry::get( 'config' )->path->relative->smarty.'Smarty.class.php';
		if ( !file_exists( $_path ) ) {
			throw new Exception( Core_Errors::LOCAL.'|Wrong Smarty path ('.$_path.')' );
			return;
		}
		require_once $_path;
		self::$_isLoaded=true;
	}

	public function setTemplate( $_str='' ) {
		if ( empty( $_str )||!file_exists( $_str ) ) {
			throw new Exception( Core_Errors::LOCAL.'|Template file not found ('.$_str.')' );
			return;
		}
		$this->_tpl=$_str;
		$this->_smarty->setTemplateDir( Core_Files::getDirName( $this->_tpl ).DIRECTORY_SEPARATOR );
		return $this;
	}

	public function setHash( $_arr=array() ) {
		foreach ( $_arr as $k=>$v ) {
			$this->_smarty->assignByRef( $k, $_arr[$k] );
		}
		return $this;
	}

	public function parse() {
		ob_start(); // выводимые шаблоны буфферизируются и в случае ошибки куски сайта не показываются
		$this->_result=$this->_smarty->fetch( Core_Files::getBaseName( $this->_tpl ) );
		$this->_result=trim( $this->_result ); // удаляем коварные пробелы в начале документа (stupid ie fix)
		$this->_smarty->clearAllAssign();
		return $this;
	}

	public function header() {
		return $this;
	}

	public function show() {
		ob_end_clean(); // есди за время компилирования шаблона что-то постороннее вылезло (посредством echo или print например) - чистим
		echo $this->_result;
	}

	public function getResult() {
		return $this->_result;
	}
}
?>