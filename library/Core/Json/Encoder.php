<?php
class Core_Json_Encoder extends Zend_Json_Encoder {

	// переопределяем т.к. new self
	public static function encode($value, $cycleCheck = false, $options = array()) {
		$encoder = new self(($cycleCheck) ? true : false, $options);
		return $encoder->_encodeValue($value);
	}

	// убирает опасные символы - нужно для того чтобы в js и xml небыло проблем
	protected function _encodeString( &$string ) {
		$string=str_replace( array( "\r","\n" ), array( '\\r','\\n' ), $string );
		parent::_encodeString( $string );
		$string=str_replace( array( '<', '>', '\'', '\"', '&' ), array( '\\u003C', '\\u003E', '\\u0027', '\\u0022', '\\u0026' ), $string );
		return '"'.$string.'"';
	}
}