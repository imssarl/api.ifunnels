<?php


/**
 * View as xml
 */
class Core_View_Xml implements Core_View_Interface {

	public function setTemplate( $_str='' ) {
		return $this;
	}

	public function setHash( $_arr=array() ) {
		$this->_out=$_arr;
		return $this;
	}

	public function parse() {
		$objX=new Core_Parsers_Xml( array( 'xml_format'=>'2.0' ) );
		$objX->array2xml( $this->_result, $this->_out );
		$this->_charset=$objX->out_data;
		return $this;
	}

	public function header() {
		header( 'Content-Type: text/xml; charset="'.$this->_charset.'"');
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