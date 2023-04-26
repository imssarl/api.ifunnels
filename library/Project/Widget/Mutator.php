<?php

class Project_Widget_Mutator {

	 public static function encode( $mix ){
		$num = mt_rand(0,5);
		for($i=1;$i<=$num;$i++) {
			$mix = base64_encode($mix);
		}
		$seed_array = array('S','H','A','F','I','Q');
		$string = $mix. "+" . $seed_array[$num];
		return base64_encode($string);
	}

	public static function decode( $string ) {
		$seed_array = array('S','H','A','F','I','Q');
		$string =  base64_decode($string);
		@list($mix,$letter) = explode("+",$string);
		for($i=0;$i<count($seed_array);$i++) {
			if($seed_array[$i] == $letter)
			break;
		}
		for($j=1;$j<=$i;$j++) {
			$mix = base64_decode($mix);
		}
		return $mix;
	}

	public static function encodeArray( &$arr ){
		foreach( $arr as &$_item ){
			$_item=self::encode( $_item );
		}
	}

	public static function decodeArray( &$arr ){
		foreach( $arr as &$_item ){
			$_item=self::decode( $_item );
		}
	}
}
?>