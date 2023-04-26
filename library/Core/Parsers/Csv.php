<?php


/**
 * csv
 */


class Core_Parsers_Csv {
	public $content='';
	public $with_header=false;
	public $delim_row=",";
	public $delim_end="\r\n";

	function __construct( $_arrSet=array() ) {
		$this->content=file_get_contents( $_arrSet['filename'] );
		if ( !empty( $_arrSet['with_header'] ) ) {
			$this->with_header=true;
		}
		if ( !empty( $_arrSet['delim_row'] ) ) {
			$this->delim_row=$_arrSet['delim_row'];
		}
		if ( !empty( $_arrSet['delim_end'] ) ) {
			$this->delim_end=$_arrSet['delim_end'];
		}
		if ( !empty( $_arrSet['lenght'] ) ) {
			$this->length=$_arrSet['lenght'];
		}
	}

	function get_data( &$arrRes ) {
		$_arrStr=preg_split( '/['.$this->delim_end.']/i', $this->content, -1 );
		if ( empty( $_arrStr ) ) {
			return false;
		}
		if ( $this->with_header ) {
			$_strHead=array_shift( $_arrStr );
			$_arrHead=preg_split( '/['.$this->delim_row.']/i', $_strHead, -1 );
		}
		$j=0;
		foreach( $_arrStr as $v ) {
			$_arrStr=preg_split( '/['.$this->delim_row.']/i', $v, -1 );
			foreach( $_arrStr as $k=>$i ) {
				$arrRes[$j][(empty($_arrHead)||empty( $_arrHead[$k] )?$k:$_arrHead[$k])]=$i;
			}
			$j++;
		}
		return true;
	}
}
?>