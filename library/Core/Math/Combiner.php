<?php


/**
 * Комбинирование массивов для получения всех возможных комбинаций
 * в порядке поступления массивов
 * т.е. напрмер array( array( dog, cat ), array( food, tooth ) ) преобразуются в 
 * dog food
 * dog tooth
 * cat food
 * cat tooth
 */
class Core_Math_Combiner implements Iterator {
	protected $data=null; // масси массивов
	protected $limit=null; // количество возможных комбинаций (|arr1|*|arr2|...*|arrN|)
	protected $current=null;

	public function __construct() {
		$this->setData( func_get_args() );
	}

	public function setData( $_arr=array() ) {
		$this->data=array_reverse( $_arr );
		$this->init();
	}

	private function init() {
		$this->rewind();
		$this->limit=array_product(array_map('count', $this->data));
	}

	public function current() {
		/* this works like a baseX->baseY converter (e.g. dechex() )
		   the only difference is that each "position" has its own number of elements/"digits"
		*/
		// <-- add: test this->valid() -->
		$rv=array();
		$key=$this->current;
		foreach( $this->data as $e) {
			array_unshift( $rv, $e[$key % count($e)] );
			$key=(int)($key/count($e));
		}
		return $rv;
	}

	public function key() { return $this->current; }
	public function next() { ++$this->current; }
	public function rewind () { $this->current=0; }
	public function valid () { return $this->current < $this->limit; }
}
?>