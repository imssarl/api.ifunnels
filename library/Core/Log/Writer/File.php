<?php

class Core_Log_Writer_File extends Zend_Log_Writer_Stream{


	public function __construct( $strDir, $formatFileName='Y-m-d' ){
		if(!is_dir($strDir)){
			mkdir($strDir,0777,true);
		}
		parent::__construct($strDir.date( $formatFileName ,time()).'.log');
	}
}
?>