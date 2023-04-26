<?php
// fake class для старта с автолодером если нужно
// ессно зендовский автолодер несработает в php4
// но для однообразности кода пускай будет
class Core_Emulate {}

if (!function_exists( 'array_intersect_key' )) {
	function array_intersect_key() {
		$arrs = func_get_args();
		$result = array_shift($arrs);
		foreach ($arrs as $array) {
			foreach ($result as $key => $v) {
				if (!array_key_exists($key, $array)) {
					unset($result[$key]);
				}
			}
		}
		return $result;
	}
}
if ( !function_exists( 'stripos' ) ) {
	function stripos($str,$needle,$offset=0) {
		return strpos(strtolower($str),strtolower($needle),$offset);
	}
}
if ( !function_exists( 'mime_content_type' ) ) { // c PHP 4 >= 4.3.0, PHP 5
	function mime_content_type( $f ) {
		return exec( trim( 'file -bi ' . escapeshellarg( $f ) ) ) ;
	}
}
if ( !function_exists( 'file_put_contents' ) ) { // с PHP 5
	function file_put_contents($filename, $data) {
		$f = @fopen($filename, 'w');
		if (!$f) {
			return false;
		} else {
			$bytes = fwrite($f, $data);
			fclose($f);
			return $bytes;
		}
	}
}
if ( !function_exists( 'file_get_contents' ) ) { // с PHP 4 >= 4.3.0, PHP 5
	function file_get_contents($filename, $data) {
		$hdl=fopen( $filename, "r" );
		if (!$hdl) {
			return false;
		}
		$fcontents=fread($fhandle, filesize($filename));
		fclose($fhandle);
		return $fcontents;
	}
}
?>