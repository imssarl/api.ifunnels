<?php
class Core_Log_Writer_Array extends Zend_Log_Writer_Abstract {

	protected $_array=array();

	public function __construct() {
		$this->_formatter = new Zend_Log_Formatter_Simple();
	}
	
	public function setContainer( &$_arr ) {
		$this->_array=& $_arr;
		return $this;
	}

	protected function _write( $event ) {
		$line = $this->_formatter->format($event);
		$this->_array[]=$line;
	}

	static public function factory($config) {
		return new self();
	}
}
?>