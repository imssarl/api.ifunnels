<?php


/**
 * Services methods
 * @internal Вспомогательные матоды - родитель многих классов : )
 */


class Core_Services {
	public $config;
	public $user;
	/**
	* Setting for Core_A::array_check funk
	* @public array
	*/
	public $post_filter=array( 'strip_tags', 'stripslashes', 'trim', 'clear' );
	/**
	* Array with errors
	* @public array
	*/
	public $errors=array();
	/**
	* constructor
	* @param none
	* @return none
	*/
	public function __construct() {}

	public function getConfig() {
		$this->config=Zend_Registry::get( 'config' );
	}

	public function getUser() {
		$this->user=Zend_Registry::get( 'objUser' );
	}

	/**
	* get filtered array by values in mask array
	* @param array $_arrRaw in - dirty data
	* @param array $_arrMask in - template data
	* @return array
	*/
	public function get_valid_array( $_arrRaw=array(), $_arrMask=array() ) {
		$arrRes=array_intersect_key( $_arrRaw, array_flip( $_arrMask ) );
		ksort( $arrRes, SORT_STRING );
		return $arrRes;
	}
	/**
	* get array with checked data & error codes data
	* @param array $arrDat out - возвращаемые в аут данные
	* @param array $arrErr out - массив с ошибками
	* @param array $_arrDat in - данные от клиента ($_POST или другой массив)
	* @param array $_arrFilter in - фильтр со значениями
	* @return boolean
	*/
	public function error_check( &$arrDat, &$arrErr, $_arrDat, $_arrFilter ){
		$arrErr=array();
		if ( !empty( $_arrDat ) ) {
			if ( empty( $arrDat ) ) {
				$arrDat=array();
			}
			$arrDat=array_merge( $arrDat, $_arrDat );
		}
		if ( empty( $_arrFilter ) ) {
			return true;
		}
		foreach ( $_arrFilter as $k=>$v ) {
			if ( $v==true ) $arrErr[$k]=true;
		}
		return empty( $arrErr );
	}
	/**
	* возвращает массив с индексами искомых типов ($_arrN) из общего набора ($_arrT)
	* @param array $_arrT in - массив с типами 5=>'type'
	* @param array $_arrN in - массив с требуемыми типами 4=>'type'
	* @return array
	*/
	public function get_indexes( $_arrT=array(), $_arrN=array() ) {
		if ( empty( $_arrT ) ) {
			return array();
		}
		if ( empty( $_arrN ) ) {
			return array_keys( $_arrT );
		}
		if ( !is_array( $_arrN ) ) {
			$_arrN=array( $_arrN );
		}
		return array_keys( array_intersect( $_arrT, $_arrN ) );
	}
	/**
	* возвращает массив ($arrRes) с индексами искомых типов ($_mixTypes) из общего набора ($_arrAll)
	* @param array $arrRes out - массив с индексами 0=>3, 1=>5 и т.д.
	* @param array $_arrAll in - массив со всеми типами 0=>'type1', 1=>'type2'
	* @param mixed $_mixTypes in - массив или строка с требуемыми типами 0=>'type2', 1=>'type8' или 'type5'
	* @return boolean
	*/
	public function set_current_types( &$arrRes, $_arrAll=array(), $_mixTypes=array() ) {
		if ( empty( $_mixTypes ) ) {
			return false;
		}
		if ( !is_array( $_mixTypes ) ) {
			$_mixTypes=array( $_mixTypes );
		}
		$arrRes=array_keys( array_intersect( $_arrAll, $_mixTypes ) );
		return !empty( $arrRes );
	}
	/**
	* возвращает массив ($intRes) с индексами искомых типов ($_strType) из общего набора ($_arrAll)
	* @param integer $intRes out - массив с индексами 0=>3, 1=>5 и т.д.
	* @param array $_arrAll in - массив со всеми типами 0=>'type1', 1=>'type2'
	* @param string $_strType in - строка с требуемым типом 'type5'
	* @return boolean
	*/
	public function set_current_onetype( &$intRes, $_arrAll=array(), $_strType='' ) {
		if ( empty( $_strType ) ) {
			return false;
		}
		$_intType=array_search( $_strType, $_arrAll );
		if ( $_intType!==false ) {
			$intRes=$_intType;
		}
		return isSet( $intRes );
	}

	public function get_microtime() {
		list( $usec, $sec )=explode( ' ', microtime() );
		return ((float)$usec+(float)$sec);
	}
	/**
	* Check email record
	* @param string $_str in - строчка с электропочтой
	* @return boolean
	*/
	public function sv_check_email( $_strEml='' ) {
		if ( empty( $_strEml ) ) {
			return false;
		}
		$_strCondition='/^[0-9a-z]([-_.]?[0-9a-z])*@[0-9a-z]([-.]?[0-9a-z])*\.[a-z]{2,6}$/i';
		return ( preg_match( $_strCondition, $_strEml ) ? true:false );
	}
	/**
	* Separate multiple email addresses with commas (,;|all_spaces) to array with valid emails
	* @param string $_str in - строчка с электропочтой
	* @return boolean
	*/
	public function sv_check_email_list( &$arrRes, $_strEmls='' ) {
		if ( empty( $_strEmls ) ) {
			return false;
		}
		$_arrEmls=array_unique( preg_split( "/[\s,;\|]+/", $_strEmls, -1, PREG_SPLIT_NO_EMPTY ) );
		if ( empty( $_arrEmls ) ) {
			return false;
		}
		$arrRes=array();
		foreach( $_arrEmls as $v ) {
			if ( !$this->sv_check_email( $v ) ) {
				continue;
			}
			$arrRes[]=$v;
		}
		return !empty( $arrRes );
	}

	public function html_entity_convert( $_str='', &$strRes ) {
		$strRes='';
		if ( empty( $_str ) ) {
			return $strRes;
		}
		$trans_table=get_html_translation_table( HTML_SPECIALCHARS, ENT_QUOTES );
		$trans_table=array_flip( $trans_table );
		$strRes=strtr( $_str, $trans_table );
		return $strRes;
	}

	public function get_soap_xml( &$_arrDta, $_strUrl='' ) {
		if ( !( $_strXml=@file_get_contents( $_strUrl ) ) ) {
			trigger_error( ERR_SOAP.'|soap service crash. Get link: '.$_strUrl );
			return false;
		}
		$_objX=new Core_Parsers_Xml();
		$_objX->set_xml_format( '2.0' );
		if ( !$_objX->xml2array( $_arrDta, $_strXml ) ) {
			trigger_error( ERR_SOAP.'|no valid xml from soap service. Get link: '.$_strUrl );
			return false;
		}
		return true;
	}

	// генерит уникальный код
	// проверяет уникальность по md5-полям если поле и таблица заданы
	public function sv_get_uniq_code( &$strCode, $_strField='', $_strTable='' ) {
		$i=0;
		$_flg=1;
		do {
			if ( $i>20 ) {
				throw new Exception( Core_Errors::DEV.'|can\'t generate uniq code. 20 time try.'.
					(!empty( $_strTable )&&!empty( $_strField )) ? 'For '.$_strTable.' table and '.$_strField.' field':'' );
			}
			$i++;
			$strCode=Core_A::rand_string(6);
			if ( !empty( $_strTable )&&!empty( $_strField ) ) {
				$_flg=0;
				$_flg=Core_Sql::getCell( 'SELECT 1 FROM '.$_strTable.' WHERE '.$_strField.'="'.md5( $strCode ).'"' );
			}
		} while ( !empty( $_flg ) );
		return !empty( $strCode );
	}

	// генерит уникальный код из чисел
	// проверяет уникальность по полям если поле и таблица заданы
	public function sv_get_uniq_code_int( &$strCode, $_strField='', $_strTable='' ) {
		$i=0;
		$_flg=1;
		do {
			if ( $i>20 ) {
				throw new Exception( Core_Errors::DEV.'|can\'t generate uniq code. 20 time try.'.
					(!empty( $_strTable )&&!empty( $_strField )) ? 'For '.$_strTable.' table and '.$_strField.' field':'' );
			}
			$i++;
			$strCode=Core_A::rand_int();
			if ( !empty( $_strTable )&&!empty( $_strField ) ) {
				$_flg=0;
				$_flg=Core_Sql::getCell( 'SELECT 1 FROM '.$_strTable.' WHERE '.$_strField.'="'.$strCode.'"' );
			}
		} while ( !empty( $_flg ) );
		return !empty( $strCode );
	}

		/*
	YEAR(NOW())-YEAR(`BIRTHDATE`)-
	IF(IF(MONTH(NOW())<MONTH(`BIRTHDATE`),1,0)+IF(DATE_FORMAT(NOW(),'%d')<DATE_FORMAT(`BIRTHDATE`,'%d'),1,0)>0,1,0) as `AGE`
	*/
	public function sv_yearsold_by_unixtime( &$intRes, $_intBirth=0 ) {
		if ( empty( $_intBirth ) ) {
			return false;
		}
		$intRes=date( 'Y', time() )-date( 'Y', $_intBirth )-(((date( 'n', time() )<date( 'n', $_intBirth )? 1:0 ) + (date( 'j', time() )<date( 'j', $_intBirth )? 1:0))>0?1:0);
		return true;
	}

	// depercated!!! use http_build_query instead
	public function sv_make_url_vars(array $params,$add=false) {
		$str='';
		foreach ($params as $k=>$v) {
			if (is_array($v)) {
				$tmp=$this->sv_make_url_vars($v,true);
				if ($add==true) {
					$tmp=str_replace('&','&['.urlEncode($k).']',$tmp);
				} else {
					$tmp=str_replace('&','&'.urlEncode($k),$tmp);
				}
				$str.=$tmp;
			} elseif ($add==true) {
				$str.='&['.urlEncode($k).']='.urlEncode($v);
			} else {
				$str.='&'.urlEncode($k).'='.urlEncode($v);
			}
		}
		if ($add==false) {
			while(subStr($str,0,1)=='&') {
				$str=subStr($str,1);
			}
			for ($i=strLen($str)-1;$i>=0;$i--) {
				if ($str[$i]=='&') {
					$str=subStr($str,0,$i);
					continue;
				} else {
					break;
				}
			}
		}
		return $str;
	}
	/**
	* выполняет комманды и ведёт лог
	* @param string $_strCmd in - shell-строка
	* @return boolean
	*/
	public function shell_execute( &$arrRes, $_strCmd='' ) {
		if ( empty( $_strCmd ) ) {
			return false;
		}
		exec( $_strCmd, $_arrOut, $_intStat );
		$arrRes[]='cmd: '.$_strCmd;
		$arrRes[]='out: '.(empty( $_arrOut )?"no":join( "\n", $_arrOut ));
		$arrRes[]='err: '.(empty( $_intStat )?"no":$_intStat);
		return empty( $_intStat );
	}
}
?>