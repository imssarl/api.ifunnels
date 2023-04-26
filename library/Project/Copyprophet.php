<?php
class Project_Copyprophet {

	private $_table='content_copyprophet';
	
	private function decodeString( $_str ) {
		$_hexStr = "0123456789abcdef";
		$outStr = "";
		$_tmpStr = strtolower( $_str );
		while ( strlen($_tmpStr) > 0 ) {
			$nib = substr( $_tmpStr, 0, 2 );
			$_tmpStr = substr( $_tmpStr ,2 );
			$highNib = strpos( $_hexStr, substr( $nib, 0, 1 ) );
			$lowNib = strpos( $_hexStr, substr( $nib, 1 ,1 ) );
			$ordVal = ( ( $highNib * 16 ) + $lowNib );
			$outStr = $outStr.chr( $ordVal );
		}
		return $outStr;
	}

	public function getScore( $_strEncoded ){
		$i=0;
		$theScore=0;
		$_arrData=Core_Sql::getAssoc('SELECT * FROM '.$this->_table );
		$str=strtolower($this->decodeString( $_strEncoded ) );
		while ( $i < 2220 ) {
			if(strpos(" ".$str,$_arrData[$i]['word'])>0) {
				$theScore=$theScore+$_arrData[$i]['score'];
			}
			if (strpos("  ".$str," ".$_arrData[$i]['word'])>0) {
				$theScore=$theScore+$_arrData[$i]['score'];
			}
			$i=$i+1;
		}
		return $theScore;
	}
}
?>