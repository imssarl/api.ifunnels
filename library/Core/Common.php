<?php

/**
 * Common methods
 */
class Core_Common {

	public static $filter=array( 'strip_tags', 'stripslashes', 'trim', 'clear' );

	/**
	 * Возвращает массив без элементов отсеянных по значениям с помощью фильтра
	 *
	 * @param array $_arrRaw in - dirty array
	 * @param array $_arrFilter in - filter array self::$filter - фильтр по умолчанию
	 * @return array
	 */
	public static function getFiltered( $_arrRaw=array(), $_arrFilter=array() ) {
		if ( empty( $_arrFilter ) ) {
			$_arrFilter=self::$filter;
		}
		if ( !is_array( $_arrRaw )&&!is_object( $_arrRaw ) ) {
			$_arr=self::getFiltered( array( $_arrRaw ), $_arrFilter ); // если в функцию отправили не массив
			return $_arr[0];
		} else {
			$arrNewData=array();
		}
		foreach( $_arrRaw as $k=>$v ) {
			if ( is_null( $v ) ) {
				$arrNewData[$k]=$v; // значит хотят вставить NULL в БД
				continue;
			}
			if( is_array( $v ) ) {
				$_arr=self::getFiltered( $v, $_arrFilter );
				if ( !empty( $_arr ) ) { // если массив пустой то такого элемента в исходящих данных небудет
					$arrNewData[$k]=$_arr;
					unSet( $_arr );
				}
				continue;
			}
			if ( in_array( 'mysqlfix', $_arrFilter ) ) {
				if ( !get_magic_quotes_gpc() ) $v=stripslashes( $v ); // если квоты включены удаляем лишние
				$v=mysql_escape_string( $v );
			}
			if ( in_array( 'htmlfix', $_arrFilter ) ) $v=htmlspecialchars( $v );
			if ( in_array( 'strip_tags', $_arrFilter ) ) $v=strip_tags( $v );
			if ( in_array( 'stripslashes', $_arrFilter ) ) $v=stripslashes( $v );
			if ( in_array( 'trim', $_arrFilter ) ) $v=trim( $v );
			if ( in_array( 'number', $_arrFilter ) ) if ( !is_numeric( $k ) ) unSet( $_arrRaw[$k] ); // key не цифра
			if ( in_array( 'clear_num_idx', $_arrFilter ) ) if ( is_numeric( $k ) ) unSet( $_arrRaw[$k] ); // key цифра
			if ( in_array( 'clear', $_arrFilter )&&isSet( $_arrRaw[$k] ) ) if ( $v=='' ) unSet( $_arrRaw[$k] ); // у элемента нету значения
			if ( isSet( $_arrRaw[$k] ) ) $arrNewData[$k]=$v; // если фильтр не удалил элемент он попадает в разрешённые данные
		}
		return $arrNewData;
	}

	/**
	 * Возвращает массив без элементов не найденных в шаблоне
	 *
	 * @param array $_arrRaw in - dirty array
	 * @param array $_arrMask in - template array
	 * @return array
	 */
	public static function getValid( $_arrRaw=array(), $_arrMask=array() ) {
		$arrRes=array_intersect_key( $_arrRaw, array_flip( $_arrMask ) );
		ksort( $arrRes, SORT_STRING );
		return $arrRes;
	}

	/**
	 * Check email record
	 *
	 * @param string $_str in - строчка с электропочтой
	 * @return boolean
	 */
	public static function checkEmail( $_strEml='' ) {
		if ( empty( $_strEml ) ) {
			return false;
		}
		$_strCondition='/^[0-9a-z]([-_.]?[0-9a-z_])*@[0-9a-z]([-.]?[0-9a-z])*\.[a-z]{2,10}$/i';
		return ( preg_match( $_strCondition, $_strEml ) ? true:false );
	}

	/**
	 * Separate multiple email addresses with commas (,;|all_spaces) to array with valid emails
	 *
	 * @param string $_str in - строчка с электропочтой
	 * @return boolean
	 */
	public static function checkEmailList( &$arrRes, $_strEmls='' ) {
		if ( empty( $_strEmls ) ) {
			return false;
		}
		$_arrEmls=array_unique( preg_split( "/[\s,;\|]+/", $_strEmls, -1, PREG_SPLIT_NO_EMPTY ) );
		if ( empty( $_arrEmls ) ) {
			return false;
		}
		$arrRes=array();
		foreach( $_arrEmls as $v ) {
			if ( !self::checkEmail( $v ) ) {
				continue;
			}
			$arrRes[]=$v;
		}
		return !empty( $arrRes );
	}

	/**
	 * Полное различие массивов
	 * since it is a set-theoretical complement as in http://en.wikipedia.org/wiki/Complement_(set_theory)
	 * Если понадобится сделать для переменного количества аргуменов TODO!!!
	 *
	 * @param array $left in
	 * @param array $right in
	 * @return array
	 */
	public static function fullArrayDiff( $left, $right ) {
		return array_diff(array_merge($left, $right), array_intersect($left, $right));
	}
}
?>