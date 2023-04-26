<?php


/**
 * Инструмент для работы с данными (фильтрация, шаблоны, проверка по условиям)
 */
class Core_Data {

	/**
	 * данные в отфильтрованном виде (для составления условий _checker'а)
	 *
	 * @var array
	 */
	public $filtered=array();

	/**
	 * данные в первозданном виде
	 *
	 * @var array
	 */
	private $_raw=array();

	/**
	 * фитльтр данных по умолчанию
	 *
	 * @var array
	 */
	private $_filter=array( 'strip_tags', 'stripslashes', 'trim', 'clear' );

	/**
	 * маска (трафарет) накладываемый на данные
	 *
	 * @var array
	 */
	private $_mask=array();

	/**
	 * массив содержащий булевы результаты исполненных условий
	 *
	 * @var array
	 */
	private $_checker=array();

	/**
	 * Конструктор, может использоватся для установки данных в объект
	 *
	 * @param array $_arr
	 * @return void
	 */
	public function __construct( $_arr=array() ) {
		$this->_raw=$_arr;
	}

	/**
	 * установка фильтра данных
	 *
	 * @param mixed массив или набор строк: mysqlfix, htmlfix, strip_tags, stripslashes, trim, number, clear_num_idx, clear
	 * @return object
	 */
	public function setFilter() {
		$_mixArgs=func_get_args();
		if ( !empty( $_mixArgs[0] ) ) {
			if ( is_array( $_mixArgs[0] ) ) {
				$this->_filter=$_mixArgs[0];
			} else {
				$this->_filter=$_mixArgs;
			}
		}
		if ( !empty( $this->_raw ) ) {
			$this->filtered=$this->runFilter( $this->_raw );
		}
		return $this;
	}

	/**
	 * Установка массива со списком разрешённх полей данных
	 *
	 * @param array $_arr in - mask
	 * @return object
	 */
	public function setMask( $_arr=array() ) {
		$this->_mask=$_arr;
		return $this;
	}

	/**
	 * Добавление/обновление элемента в исходные данные и применение фильтра
	 *
	 * @param string $_strKey in - new key
	 * @param mixed $_mixVal in - new value
	 * @return object
	 */
	public function setElement( $_strKey='', $_mixVal=array() ) {
		if ( empty( $_strKey )||empty( $_mixVal ) ) {
			return $this;
		}
		$this->_raw[$_strKey]=$_mixVal;
		$this->filtered=array();
		$this->getFiltered();
		return $this;
	}

	/**
	 * Добавление/обновление массива элементов в исходные данные и применение фильтра
	 *
	 * @param string $_arrAdd in
	 * @return object
	 */
	public function setElements( $_arrAdd=array() ) {
		if ( empty( $_arrAdd ) ) {
			return $this;
		}
		$this->_raw=$_arrAdd+$this->_raw;
		$this->filtered=array();
		$this->getFiltered();
		return $this;
	}

	/**
	 * Возвращает массив с данными без обработки
	 *
	 * @return array
	 */
	public function getRaw( $_strKey='' ) {
		if ( empty( $_strKey )||!isSet( $this->_raw[$_strKey] ) ) {
			return $this->_raw;
		}
		return $this->_raw[$_strKey];
	}

	/**
	 * Возвращаются отфильтрованные данные (данные фильтруются один раз)
	 *
	 * @return array
	 */
	public function getFiltered() {
		if ( empty( $this->filtered )&&!empty( $this->_raw ) ) {
			$this->filtered=$this->runFilter( $this->_raw );
		}
		return $this->filtered;
	}

	/**
	 * Возвращает массив без элементов не найденных в шаблоне по всем данным $this->filtered
	 *
	 * @return array
	 */
	public function getValid() {
		return $this->getValidCurrent( $this->getFiltered() );
	}

	/**
	 * Возвращает массив без элементов не найденных в шаблоне 
	 * если в $this->filtered у нас несколько массивов то их нужно передвавть в эту функцию
	 *
	 * @return array
	 */
	public function getValidCurrent( $_arr=array() ) {
		$arrRes=array_intersect_key( $_arr, array_flip( $this->_mask ) );
		ksort( $arrRes, SORT_STRING );
		return $arrRes;
	}

	/**
	 * Фильтрует данные в соответствии с фильтром установленным в setFilter()
	 * Комплексное применение нескольких php-функций
	 *
	 * @param array $_arrRaw in - dirty array
	 * @return array
	 */
	private function runFilter( $_arrRaw=array() ) {
		if ( !is_array( $_arrRaw )&&!is_object( $_arrRaw ) ) {
			$_arr=$this->runFilter( array( $_arrRaw ) ); // если в функцию отправили не массив
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
				$_arr=$this->runFilter( $v );
				if ( !empty( $_arr ) ) { // если массив пустой то такого элемента в исходящих данных небудет
					$arrNewData[$k]=$_arr;
					unSet( $_arr );
				}
				continue;
			}
			if ( in_array( 'mysqlfix', $this->_filter ) ) {
				if ( !get_magic_quotes_gpc() ) $v=stripslashes( $v ); // если квоты включены удаляем лишние
				$v=mysql_escape_string( $v );
			}
			if ( in_array( 'urldecode', $this->_filter ) ) $v=urldecode( $v );
			if ( in_array( 'htmlfix', $this->_filter ) ) $v=htmlspecialchars( $v );
			if ( in_array( 'strip_tags', $this->_filter ) ) $v=strip_tags( $v );
			if ( in_array( 'stripslashes', $this->_filter ) ) $v=stripslashes( $v );
			if ( in_array( 'trim', $this->_filter ) ) $v=trim( $v );
			if ( in_array( 'number', $this->_filter ) ) if ( !is_numeric( $k ) ) unSet( $_arrRaw[$k] ); // key не цифра
			if ( in_array( 'clear_num_idx', $this->_filter ) ) if ( is_numeric( $k ) ) unSet( $_arrRaw[$k] ); // key цифра
			if ( in_array( 'clear', $this->_filter )&&isSet( $_arrRaw[$k] ) ) if ( $v=='' ) unSet( $_arrRaw[$k] ); // у элемента нету значения
			if ( isSet( $_arrRaw[$k] ) ) $arrNewData[$k]=$v; // если фильтр не удалил элемент он попадает в разрешённые данные
		}
		return $arrNewData;
	}

/* методы которые расположены ниже DEPERCATED!!! 15.05.2012 */

	public function getErrors( &$arrRes ) {
		$arrRes=$this->_errors;
		return $this;
	}

	/**
	 * Установка массива со списком результатов чекера
	 *
	 * @param array $_arr in
	 * @return object
	 */
	public function setChecker( $_arr=array() ) {
		$this->_checker=$_arr;
		return $this;
	}

	/**
	 * Возвращаются найденные ошибки
	 *
	 * @param array $arrErr out
	 * @return bool
	 */
	public function check() {
		$this->_errors=array();
		if ( empty( $this->_checker ) ) {
			return true;
		}
		foreach( $this->_checker as $k=>$v ) {
			if ( $v===true ) $this->_errors[$k]=true;
		}
		return empty( $this->_errors );
	}
}
?>