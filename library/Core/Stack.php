<?php
class Core_Stack {

	public $stack=array();

	private $_max=0;

	public function __construct( $_str='' ) {
		$this->initStack( $_str );
	}

	public function initStack( $_str='' ) {
		if ( empty( $_str ) ) {
			return false;
		}
		if ( !isSet( $_SESSION['stacked'][$_str] ) ) {
			$_SESSION['stacked'][$_str]=$this->stack;
		}
		$this->stack=&$_SESSION['stacked'][$_str];
		return true;
	}

	public function setMaxNest( $_int=0 ) {
		$this->_max=$_int;
	}

	// добавляем элемент
	public function push( $_mix=null ) {
		array_unshift( $this->stack, $_mix );
		if ( empty( $this->_max ) ) {
			return true;
		}
		if ( $this->_count()>$this->_max ) {
			$this->pop();
		}
		return true;
	}

	// удаляем самый старый элемент
	public function pop() {
		if ( empty( $this->stack ) ) {
			return false;
		}
		array_pop( $this->stack );
		return true;
	}

	// удаляем самый новый элемент
	public function shift() {
		if ( empty( $this->stack ) ) {
			return false;
		}
		array_shift( $this->stack );
		return true;
	}

	// очищаем стэк
	public function clear() {
		$this->stack=array();
	}

	private function _count() {
		return count( $this->stack );
	}
}
?>