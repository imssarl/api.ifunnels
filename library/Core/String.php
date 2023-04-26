<?php


/**
 * String additional methods
 * @internal php с поддержкой mbstring
 */


class Core_String extends Core_Services {

	private static $_instance=NULL;

	private $_source='';
	private $_result='';
	private $_workingEncoding='';

	private $_intNeedLength=80;

	public function __construct( $_str='' ) {
		$this->initialize( $_str );
	}

	public function initialize( $_str='' ) {
		if ( !function_exists( 'mb_detect_encoding' ) ) {
			throw new Exception( Core_Errors::DEV.'|mbstring-library not installed' );
			return;
		}
		$this->_intNeedLength=80;
		$this->_source=$_str;
		$this->_workingEncoding=@mb_detect_encoding( $this->_source );
		if ( $this->_workingEncoding===false ) {
			// если кодировка не распознаётся конвертим в utf8
			$this->_source=iconv( "", 'UTF-8//IGNORE', $this->_source );
		}
		$this->_workingEncoding=@mb_detect_encoding( $this->_source );
	}

	public function setNeedLength( $_int=80 ) {
		$this->_intNeedLength=$_int;
		return $this;
	}

	/**
	 * длинна строки в любой кодировке
	 * 
	 * @param string $_str - строка
	 * @return integer
	 */
	public static function getStrlen( $_str='' ) {
		if ( empty( $_str ) ) {
			return 0;
		}
		$_strEncoding=@mb_detect_encoding( $_str );
		if ( $_strEncoding===false ) {
			$_str=iconv( "", 'UTF-8//IGNORE', $_str ); // если кодировка не распознаётся конвертим в utf8
			$_strEncoding=@mb_detect_encoding( $_str );
		}
		return mb_strlen( $_str, $_strEncoding );
	}

	//  implements Core_Singleton_Interface TODO!!!
	public static function getInstance( $_str='' ) {
		if ( self::$_instance==NULL ) {
			self::$_instance=new Core_String( $_str );
		} else {
			self::$_instance->initialize( $_str );
		}
		return self::$_instance;
	}

	public function getWorkingEncoding() {
		return $this->_workingEncoding;
	}

	public function getResult() {
		return $this->_result;
	}

	public function getSource() {
		return $this->_source;
	}

	// по уполчанию - separate <items> by comma, space or newline
	public function separate( $_strSeparators='\s,' ) {
		return preg_split( '/['.$_strSeparators.']+/imu', $this->_source, -1, PREG_SPLIT_NO_EMPTY );
	}

	// функция транслитерации по ГОСТ 7.79-2000
	public function rus2translite() {
		$_arrConvert=array("а"=>"a","к"=>"k","х"=>"kh","б"=>"b","л"=>"l","ц"=>"c","в"=>"v","м"=>"m","ч"=>"ch","г"=>"g","н"=>"n","ш"=>"sh","д"=>"d","о"=>"o","щ"=>"shh","е"=>"e","п"=>"p","ъ"=>"\"","ё"=>"jo","р"=>"r","ы"=>"y","ж"=>"zh","с"=>"s","ь"=>"'","з"=>"z","т"=>"t","э"=>"eh","и"=>"i","у"=>"u","ю"=>"ju","й"=>"jj","ф"=>"f","я"=>"ja","А"=>"A","К"=>"K","Х"=>"Kh","Б"=>"B","Л"=>"L","Ц"=>"C","В"=>"V","М"=>"M","Ч"=>"Ch","Г"=>"G","Н"=>"N","Ш"=>"Sh","Д"=>"D","О"=>"O","Щ"=>"Shh","Е"=>"E","П"=>"P","Ъ"=>"\"","Ё"=>"Jo","Р"=>"R","Ы"=>"Y","Ж"=>"Zh","С"=>"S","Ь"=>"'","З"=>"Z","Т"=>"T","Э"=>"Eh","И"=>"I","У"=>"U","Ю"=>"Ju","Й"=>"Jj","Ф"=>"F","Я"=>"Ja");
		return strtr( strtr( $this->_source, $_arrConvert ), array( "\""=>'', "'"=>'' ) ); // убираем отображение " и ' из ссылок
	}

	public function toSystem( $_strDelim='-' ) {
		// модификатор u для utf-8 текста
		return join( $_strDelim, preg_split( '/[^\w\d]+/iu', mb_strtolower( substr( $this->_source, 0, 200 ), $this->_workingEncoding ), -1, PREG_SPLIT_NO_EMPTY ) );
	}

	// если Description получился более 200 символов — необходимо обрезать его по правому краю не нарушая целостности слов. 
	// Если предложение обрезано на середине — в конце description необходимо поставить знак «многоточие».
	public function metaDescription( $_intMax=200, $_strSym=' ...' ) {
		$_str=strip_tags( $this->_source );
		if ( mb_strlen( $_str, $this->_workingEncoding )<$_intMax ) {
			return $_str;
		}
		$c=0;
		$_strNew='';
		$_intSym=mb_strlen( $_strSym, $this->_workingEncoding );
		$_arrWords=explode( ' ', $_str );
		foreach( $_arrWords as $v ) {
			$_intLen=mb_strlen( $v, $this->_workingEncoding );
			if ( mb_substr( $_strNew, -1, 1, $this->_workingEncoding )=='.' ) {
				if ( $_intLen+$c>$_intMax ) {
					return $_strNew;
				}
			} else {
				if ( $_intSym+$_intLen+$c>$_intMax ) {
					return $_strNew.$_strSym;
				}
			}
			$_strNew.=(empty( $_strNew )?$v:' '.$v);
			$c+=++$_intLen;
		}
	}

	// использовать Core_Tags_Generate вместо этого метода TODO!!!03.06.2011
	// 10 наиболее часто встречающихся слов в полном описании товара/категории, имеющих более 2 букв. 
	// перечисленные через пробел
	public function metaKeywords( $_intNum=10 ) {
		$_str=strip_tags( $this->_source );
		$_strW=preg_replace( "/[^\w\x7F-\xFF\s]/", " ", mb_strtolower( $_str, $this->_workingEncoding ) );
		$_arrW=preg_split( '/[\s\/]+/', $_strW, -1, PREG_SPLIT_NO_EMPTY );
		$_arrGrp=array();
		foreach( $_arrW as $k=>$v ) {
			if ( mb_strlen( $v, $this->_workingEncoding )>2 ) {
				$_arrGrp[$v]=(empty( $_arrGrp[$v] )?1:++$_arrGrp[$v]);
			}
		}
		arsort( $_arrGrp );
		array_splice( $_arrGrp, $_intNum );
		return join( ' ', array_keys( $_arrGrp ) );
	}

	// функционал для смарти плагина вобщем-то
	public function ellipsis( $_intNeedLength=80, $_flgMode='middle', $_strSym=' ... ' ) {
		switch( $_flgMode ) {
			case 'beginning': return $this->ellipsisBeginning( $_intNeedLength, $_strSym ); break;
			case 'ending': return $this->ellipsisEnding( $_intNeedLength, $_strSym ); break;
			case 'middle': return $this->ellipsisMiddle( $_intNeedLength, $_strSym ); break;
		}
	}

	// вставляет в середину строки троеточие + урезает строку до 80 символов
	public function ellipsisMiddle( $_intNeedLength=80, $_strSym=' ... ' ) {
		if ( !( mb_strlen( $this->_source, $this->_workingEncoding )>$_intNeedLength ) ) {
			// если длинна строки подходит
			return $this->_source;
		}
		$_intPartMaxLength=floor( ( $_intNeedLength-mb_strlen( $_strSym, $this->_workingEncoding ) )/2 );
		if ( !( $_intPartMaxLength>0 ) ) { // $_strSym большой например
			return $this->_source;
		}
		// посмотреть тут же fullWords на счёт применить TODO!!! 27.10.2009
		$_arrWords=preg_split( '/[\s\/]+/u', $this->_source, -1, PREG_SPLIT_NO_EMPTY );
		if ( count( $_arrWords )==1 ) { // у нас одно длинное слово
			$this->_source=mb_substr( $this->_source, 0, $_intPartMaxLength, $this->_workingEncoding ).$_strSym.mb_substr( $this->_source, -$_intPartMaxLength, $_intPartMaxLength, $this->_workingEncoding );
		} else { // несколько слов
			$_strLast=$_arrWords[count($_arrWords)-1];
			if ( mb_strlen( $_strLast, $this->_workingEncoding )>$_intPartMaxLength ) {
				$_strLast=mb_substr( $_strLast, -$_intPartMaxLength, $_intPartMaxLength, $this->_workingEncoding );
			}
			$_intFirst=$_intNeedLength-( mb_strlen( $_strSym.$_strLast, $this->_workingEncoding ) );
			$this->_source=mb_substr( $this->_source, 0, $_intFirst, $this->_workingEncoding ).$_strSym.$_strLast;
		}
		return $this->_source;
	}

	// вставляет в конец строки троеточие + урезает строку до 80 символов
	public function ellipsisEnding( $_intNeedLength=80, $_strSym=' ... ' ) {
		if ( !( mb_strlen( $this->_source, $this->_workingEncoding )>$_intNeedLength ) ) {
			// если длинна строки подходит
			return $this->_source;
		}
		return mb_substr( $this->_source, 0, $_intNeedLength-mb_strlen( $_strSym, $this->_workingEncoding ), $this->_workingEncoding ).$_strSym;
	}

	// вставляет в начало строки троеточие + урезает строку до 80 символов
	public function ellipsisBeginning( $_intNeedLength=80, $_strSym=' ... ' ) {
		if ( !( mb_strlen( $this->_source, $this->_workingEncoding )>$_intNeedLength ) ) {
			// если длинна строки подходит
			return $this->_source;
		}
		return $_strSym.mb_substr( $this->_source, -$_intNeedLength+mb_strlen( $_strSym, $this->_workingEncoding ), $_intNeedLength+mb_strlen( $_strSym, $this->_workingEncoding ), $this->_workingEncoding );
	}

	public function str2filename(){
		$str=strip_tags($this->_source);
		$str=preg_replace('/[\r\n\t ]+/', ' ', $str);
		$str=preg_replace('/[\"\*\/\:\<\>\?\'\|]+/', ' ', $str);
		$str=strtolower($str);
		$str=html_entity_decode( $str, ENT_QUOTES, "utf-8" );
		$str=htmlentities($str, ENT_QUOTES|ENT_IGNORE, "utf-8");
		$str=preg_replace("/(&)([a-z])([a-z]+;)/i", '$2', $str);
		$str=str_replace(' ', '-', $str);
		$str=rawurlencode($str);
		$str=str_replace('%', '-', $str);
		$str=substr($str, 0, 255);
		return $str;
	}

	// отдаёт строку урезанную до первого пробела после 80 символов (т.е. с соблюдением целосности слов)
	public function fullWords( $_strSym=' ' ) {
		if ( mb_strlen( $this->_source, $this->_workingEncoding )<=$this->_intNeedLength ) {
			$this->_result=$this->_source;
			return false;
		}
		$_intPos=mb_strpos( $this->_source, $_strSym, $this->_intNeedLength, $this->_workingEncoding );
		if ( $_intPos===false ) {
			$this->_result=$this->_source;
			return false;
		}
		$this->_result=mb_substr( $this->_source, 0, $_intPos, $this->_workingEncoding );
		return true;
	}

	/**
	 * Сумма прописью
	 * Пока поддерживается русский язык
	 * 
	 * @param integer $stripkop необязательный параметр, позволяет убрать упоминание о копейках если =1
	 * @return string
	 */
	public function valueSpelledOut( $stripkop=0 ) {
		$str[100]= array('','сто','двести','триста','четыреста','пятьсот','шестьсот', 'семьсот', 'восемьсот','девятьсот','тысяча');
		$str[11]=array(
			10=>'десять',11=>'одиннадцать',12=>'двенадцать', 13=>'тринадцать',14=>'четырнадцать',15=>'пятнадцать', 
			16=>'шестнадцать', 17=>'семнадцать',18=>'восемнадцать', 19=>'девятнадцать');
		$str[10]=array('','','двадцать','тридцать','сорок','пятьдесят', 'шестьдесят','семьдесят','восемьдесят','девяносто','сто');
		$sex[1]=array('','один','два','три','четыре','пять','шесть','семь', 'восемь','девять');
		$sex[2]=array('','одна','две','три','четыре','пять','шесть','семь', 'восемь','девять');
		$forms=array(
			-1=>array('копейка', 'копейки',  'копеек',    2),
			0 =>array('рубль',   'рубля',    'рублей',    1), // 10^0
			1 =>array('тысяча',  'тысячи',   'тысяч',     2), // 10^3
			2 =>array('миллион', 'миллиона', 'миллионов', 1), // 10^6
			3 =>array('миллиард','миллиарда','миллиардов',1), // 10^9
			4 =>array('триллион','триллиона','триллионов',1), // 10^12
		);
		$out=$tmp=array();
		// Поехали!
		$tmp=explode('.', str_replace(',','.', $this->_source));
		$rub=number_format($tmp[0],0,'','-');
		 // нормализация копеек
		$kop=isset($tmp[1]) ? str_pad(substr($tmp[1],0,2), 2, '0', STR_PAD_LEFT) : '00';
		$levels=explode('-', $rub);
		$offset=sizeof($levels)-1;
		foreach($levels as $k=>$lev) {
			$lev=str_pad($lev, 3, '0', STR_PAD_LEFT); // нормализация
			$ind=$offset-$k; // индекс для $forms
			if ($lev[0]!='0') $out[]=$str[100][$lev[0]]; // сотни
			$lev=$lev[1].$lev[2];
			$lev=(int)$lev;
			if ($lev > 19) { // больше девятнадцати
				$lev=''.$lev;
				$out[]=$str[10][$lev[0]];
				$out[]=$sex[$forms[$ind][3]][$lev[1]];
			}
			else if ($lev>9) {
				$out[]=$str[11][$lev];
			}
			else if ($lev>0) {
				$out[]=$sex[$forms[$ind][3]][$lev];
			}
			if ($lev>0 || $ind==0) {
				$out[]=$this->pluralForm($lev, $forms[$ind][0], $forms[$ind][1] ,$forms[$ind][2] );
			}
		}
		if ($stripkop==0) {
			$out[]=$kop; // копейки
			$out[]=$this->pluralForm($kop, $forms[-1][0], $forms[-1][1] ,$forms[-1][2] );
		}
		return implode(' ',$out);
	}

	private function pluralForm($n, $f1, $f2, $f5) {
		$n=abs($n) % 100;
		$n1=$n % 10;
		if ($n > 10 && $n < 20) return $f5;
		if ($n1 > 1 && $n1 < 5) return $f2;
		if ($n1 == 1) return $f1;
		return $f5;
	}

	/**
	 * Convert a PHP scalar, array or hash to JS scalar/array/hash. This function is 
	 * an analog of json_encode(), but it can work with a non-UTF8 input and does not 
	 * analyze the passed data. Output format must be fully JSON compatible.
	 * Метод кэшируется при помощи Zend_Cache
	 * 
	 * @param mixed $_mix Any structure to convert to JS.
	 * @return string JavaScript equivalent structure.
	 */
	public static function php2json( $_mix=false ) {
		return Core_Json_Encoder::encode( $_mix );
	}

	/**
	 * Получаем из json-строки массив
	 * 
	 * @param string
	 * @return array
	 */
	public static function json2php( $_str='' ) {
		return json_decode( $_str, true );
	}

	/**
	 * Проверяем в верхнем регистре строка или нет
	 * 
	 * @param string $_str проверяемая строка
	 * @return boolean
	 */
	public static function isUpper( $_str ) {
		return ( strtoupper( $_str )===$_str );
	}

	/**
	 * Проверяем в нижнем регистре строка или нет
	 * 
	 * @param string $_str проверяемая строка
	 * @return boolean
	 */
	public static function isLower( $_str ) {
		return ( strtolower( $_str )===$_str );
	}

	/**
	 * Перевод уникальных символов латинских языков в стандартную латиницу
	 * Для корректной авто-генерации ссылок
	 * 
	 * @param string $_str предполагается что строка уже прошла через mb_strtolower
	 * @return string
	 */
	public static function removeAccents( $_str ) {
		return preg_replace( array(
			'/[á|â|à|å|ä]/iu',
			'/[ð|é|ê|è|ë]/iu',
			'/[í|î|ì|ï]/iu',
			'/[ó|ô|ò|ø|õ|ö]/iu',
			'/[ú|û|ù|ü]/iu',
			'/æ/iu',
			'/œ/iu',
			'/ç/iu',
			'/ÿ/iu',
			'/ß/iu',
		), array(
			'a',
			'e',
			'i',
			'o',
			'u',
			'ae',
			'oe',
			'c',
			'y',
			'ss',
		), $_str );
	}

	public static function removeSpecChar( $_str ){
		$_str=str_replace( array(
			'!','@','#','$','%','^','&','*','(',')','_','-','+','=','"','\'',',','.','/',':',';','<','>','[',']','{','}','|','\\','`','~'
		), array(
			'','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','',
		), $_str );
		return preg_replace( "/(\s){2,}/", ' ', $_str );
	}

	public static function parseUrl( $str ){
		if(empty($str)){
			return false;
		}
		$_tmp=parse_url($str);
		if( empty($_tmp['query']) ){
			return $_tmp;
		}
		$_params=explode('&',$_tmp['query']);
		foreach($_params  as $_str ){
			$_arr=explode('=',$_str );
			$_tmp[$_arr[0]]=$_arr[1];
		}
		return $_tmp;
	}
}
?>