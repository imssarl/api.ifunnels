<?php

class Core_Payment_Encode {

	private static $_arrCrypt=array('S','H','A','F','I','Q');

	/**
	 * Encode array to string
	 * @static
	 * @param $_array
	 * @return string
	 */
	public static function encode( $_array ) {
		$_str=serialize($_array);
		$_intRand=mt_rand( 0, 5 );
		for( $i=1; $i<=$_intRand; $i++ ) {
			$_str=base64_encode( $_str );
		}
		$_str = $_str . "+" . self::$_arrCrypt[ $_intRand ];
		$_str = base64_encode( $_str );
		return $_str;
	}

	/**
	 * Decode str to array
	 * @static
	 * @param $_str
	 * @return array
	 */
	public static function decode( $_str ) {
		$_str =  base64_decode( $_str );
		@list( $_str, $_letter )=preg_split("@\+@", $_str );
		for( $i=0; $i<count(self::$_arrCrypt); $i++ ) {
			if( self::$_arrCrypt[ $i ] == $_letter ){
				break;
			}
		}
		for( $j=1; $j<=$i; $j++ ){
			$_str=base64_decode( $_str );
		}
		return unserialize($_str);
	}
}
?>