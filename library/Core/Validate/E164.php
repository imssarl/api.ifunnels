<?php

class Core_Validate_E164 extends Zend_Validate_Abstract implements Zend_Validate_Interface {

	const INVALID = 'incorrectFormat';
	/**
	  * @var array
	  */
	 protected $_messageTemplates = array(
		 self::INVALID => "Incorrect format. Number must be in <a target='_blank' href='http://en.wikipedia.org/wiki/E.164'>E.164</a> format: +N NNN NNN NN NN"
	 );

	public function isValid( $value ){
		if( !preg_match('/^\+[1-9][0-9]{7,14}$/',$value,$_match) ){
			$this->_error( self::INVALID );
			return false;
		}
		return true;
	}
}
?>