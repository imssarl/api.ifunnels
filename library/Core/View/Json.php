<?php


/**
 * View as json
 */
class Core_View_Json implements Core_View_Interface {

	public function setTemplate( $_str='' ) {
		return $this;
	}

	public function setHash( $_arr=array() ) {
		$this->_out=$_arr;
		return $this;
	}

	public function parse() {
		$this->_result=Zend_Registry::get( 'CachedCoreString' )->php2json( $this->_out );
		return $this;
	}

	public function header() {
		header( 'Content-Type: text/javascript' );
		header( 'Content-Length: '.strval( strlen( $this->_result ) ) );
		return $this;
	}

	public function show() {
		echo $this->_result;
		exit;
	}

	public function getResult() {
		return $this->_result;
	}
}
?>