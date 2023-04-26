<?php


/**
 * Auxiliary Class
 */


class Core_A {
	/**
	* Depercated!!! use Core_Data class instead!!!
	* Returning an array (recursing function)
	*
	* available modes: dbfix, strip_tags, stripslashes, mysqlfix, trim, dbdata, number, clear
	* usage: Core_A::array_check( $_POST, array( 'strip_tags', 'stripslashes', 'trim', 'dbdata', 'clear' ) );
	*
	* @access public
	*/
	public static function array_check( $arrData, $arrMode ) {
		if (is_array($arrData)) $arrNewData = array();
		if (is_object($arrData)) $arrNewData = array();
		if (!is_array($arrData) && !is_object($arrData)) {
			// zmiter: maybe it's just a publiciable, not array
			$_arr = Core_A::array_check(array($arrData), $arrMode);
			return $_arr[0];
		}
		foreach ( $arrData as $k=>$v ) {
			if ( is_array( $v ) ) {
				if ( $arrTemp=Core_A::array_check( $v, $arrMode ) ) { // если массив из рекурсии нулевой то путого элемента тоже не будет
					$arrNewData[$k]=$arrTemp;
					unSet( $arrTemp );
				}
			} elseif ( !is_null( $v ) ) {
				if ( !is_null( $arrMode ) ) {
					if ( in_array( 'mysqlfix', $arrMode ) ) {
						if ( !get_magic_quotes_gpc() ) $v=stripslashes( $v ); // если квоты включены удаляем лишние
						$v=mysql_escape_string( $v );
					}
					if ( in_array( 'htmlfix', $arrMode ) ) $v=htmlspecialchars( $v );
					if ( in_array( 'strip_tags', $arrMode ) ) $v=strip_tags( $v );
					if ( in_array( 'stripslashes', $arrMode ) ) $v=stripslashes( $v );
					if ( in_array( 'trim', $arrMode ) ) $v=trim( $v );
					if ( in_array( 'dbdata', $arrMode ) ) if ( !preg_match( "/^[A-Z\_]+$/", $k ) ) unSet( $arrData[$k] ); // key не в верхнем регистре
					if ( in_array( 'number', $arrMode ) ) if ( !is_numeric( $k ) ) unSet( $arrData[$k] ); // key не цифра
					if ( in_array( 'clear_num_idx', $arrMode ) ) if ( is_numeric( $k ) ) unSet( $arrData[$k] ); // key цифра
					if ( in_array( 'clear', $arrMode )&&isSet( $arrData[$k] ) ) if ( $v=='' ) unSet( $arrData[$k] ); // у элемента нету значения
				}
				if ( isSet( $arrData[$k] ) ) $arrNewData[$k]=$v;
			} else {
				$arrNewData[$k]=$v; // $v=NULL то значит такую строку хотят вставить в БД - это можно, хотя...
			}
		}
		if ( !count( $arrNewData ) ) {
			return false;
		}
		return $arrNewData;
	}

	public static function rand_uniqid() {
		return md5(uniqid(rand(), true));
	}

	public static function rand_string( $_intLenght=5, $_arrWithout=array() ) {
		$_strNewstring='';
		if ( $_intLenght>0 ) {
			while ( strlen( $_strNewstring )<$_intLenght ) {
				$_intRand=mt_rand(0,61);
				if ( $_intRand<10 ) {
					$_strNewstring.=chr( $_intRand+48 );
				} elseif ( $_intRand<36 ) {
					$_strNewstring.=chr( $_intRand+55 );
				} else {
					$_strNewstring.=chr( $_intRand+61 );
				}
			}
		}
		return $_strNewstring;
	}

	/**
	* Return rand int needed lenght
	*/
	public static function rand_int( $_intLenght=5 ) {
		$key='';
		$charset="0123456789";
		for ($i=0; $i<$_intLenght; $i++) {
			$key.=$charset[(mt_rand(0,strlen($charset)-1))];
		}
		return $key;
	}
}
?>