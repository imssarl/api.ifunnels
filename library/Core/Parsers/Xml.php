<?php


/**
 * Xml parser
 * @internal only utf<->win or iso<->utf not iso<->win
 */


class Core_Parsers_Xml extends Core_Services {
	/**
	* формат xml
	* 1.0 - struc with child, 2.0 - struc with attr & child
	* @private string
	*/
	private $xml_format='1.0';
	/**
	* если данные в xml экранированы с помощью <![CDATA[ ... ]]>
	* то эскепить не надо - этим достигнем большей производительности
	* используется только для array2xml
	* false - escape true - do not escape
	* @private boolean
	*/
	private $cdata=false;
	/**
	* форматирование xml
	* @private string
	*/
	private $br="\n";
	/**
	* флаг который указывает в каком регистре будут тэги xml
	* варианты - lower и upper
	* @private string
	*/
	private $tag_case='lower';
	/**
	* режим отслеживания ошибок в php xml парсере (через trigger_error)
	* 0-off 1-on
	* @private integer
	*/
	private $debug=0;
	/**
	* кодировка входных данных
	* iso-8859-1/cp1251/utf-8
	* @public string
	*/
	public $out_data='utf-8';
	/**
	* кодировка выходных данных
	* iso-8859-1/cp1251/utf-8
	* @public string
	*/
	public $in_data='iso-8859-1';

	public function __construct( $_arrSet=array() ) {
		if ( !empty( $_arrSet['xml_format'] ) ) {
			$this->set_xml_format( $_arrSet['xml_format'] );
		}
		if ( !empty( $_arrSet['cdata'] ) ) {
			$this->set_cdata( $_arrSet['cdata'] );
		}
		if ( !empty( $_arrSet['tag_case'] ) ) {
			$this->tag_case=$_arrSet['tag_case'];
		}
		if ( !empty( $_arrSet['debug'] ) ) {
			$this->debug=$_arrSet['debug'];
		}
		if ( !empty( $_arrSet['out_data'] ) ) {
			$this->out_data=$_arrSet['out_data'];
		}
		if ( !empty( $_arrSet['in_data'] ) ) {
			$this->in_data=$_arrSet['in_data'];
		}
	}

	public function set_xml_format( $_strFormat='1.0' ) {
		$this->xml_format=$_strFormat;
	}

	public function set_cdata( $_bool=false ) {
		$this->cdata=$_bool;
	}

	public function xml2array( &$arrRes, $_strXml='' ) {
		if ( empty( $_strXml ) ) {
			return false;
		}
		$_strXml=trim( $_strXml );
		if ( !preg_match( '/^<\?xml/i', $_strXml ) ) {
			$_strXml='<?'.$this->apply_tagcase( 'xml' ).'>'.$this->br.$_strXml;
		}
		$_h=xml_parser_create();
		xml_parser_set_option( $_h, XML_OPTION_SKIP_WHITE, 1 );
		xml_parse_into_struct( $_h, $_strXml, $arrV, $_arrI );
		if ( !empty( $this->debug )&&xml_get_error_code( $_h ) ) {
			throw new Exception( Core_Errors::DEV.'|xml_parser error - '.xml_error_string( xml_get_error_code( $_h ) ).'. Data:'.print_r( $_strXml, true ).'. Values:'.print_r( $arrV, true ) );
			xml_parser_free( $_h );
			return false;
		}
		xml_parser_free( $_h );
		$i=0;
		$arrRes=$this->xml_format=='1.0' ? $this->from_10( $arrV ) : $this->from_20( $arrV, $i );
		return true;
	}

	public function array2xml( &$strRes, $_arrTree=array() ) {
		if ( empty( $_arrTree ) ) {
			return false;
		}
		$strRes="<?xml version=\"1.0\" encoding=\"".$this->out_data."\"?>".$this->br;
		$strRes.=$this->xml_format=='1.0' ? $this->to_10( $_arrTree ) : $this->to_20( $_arrTree );
		return true;
	}

	private function from_10( $vals ) {
		$params = array();
		$level = array();
		foreach ($vals as $xml_elem) {
			$xml_elem['value']=$this->apply_encode(@$xml_elem['value']);
			$xml_elem['tag']=$this->apply_tagcase(@$xml_elem['tag']);
			if ($xml_elem['type'] == 'open') {
				if (array_key_exists('attributes',$xml_elem)) {
					list($level[$xml_elem['level']],$extra) = array_values($xml_elem['attributes']);
				} else {
					$level[$xml_elem['level']] = $xml_elem['tag'];
				}
			}
			if ($xml_elem['type'] == 'complete') {
				$start_level = 1;
				$php_stmt = '$params';
				while($start_level < $xml_elem['level']) {
					$php_stmt .= '[$level['.$start_level.']]';
					$start_level++;
				}
				$php_stmt .= '[$xml_elem[\'tag\']] = $xml_elem[\'value\'];';
				eval($php_stmt);
			}
		}
		return $params;
	}

	private function from_20( &$arrVals, &$i ) {
		$arrChildren=array();
		while ($i < count($arrVals)) {
			$v=&$arrVals[$i++];
			switch ( $v['type'] ) {
				case 'cdata':
				case 'complete':
					$arrChildren[]=$this->xml_get_child( $v );
				break;
				case 'open':
					$arrChildren[]=$this->xml_get_child( $v, $this->from_20( $arrVals, $i ) );
				break;
				case 'close': break 2; // leave "while" loop
			}
		}
		return $arrChildren;
	}

	private function xml_get_child( &$v, $arrChildren=NULL ) {
		$arrChild=array();
		if ( isSet( $v['tag'] )&&$v['type']!='cdata' ) $arrChild['tag']=$this->apply_tagcase( $v['tag'] );
		if ( isSet( $v['attributes'] ) ) $arrChild['attr']=$this->apply_tagcase( $v['attributes'] );
		if ( isSet( $v['value'] ) ) $arrChild['val']=$v['value'];
		if ( is_array( $arrChildren ) ) $arrChild['val']=$arrChildren;
		return $arrChild;
	}

	private function to_10( $arrTree, $strKey='' ) {
		foreach ( $arrTree as $k=>$v ) {
			if ( is_array( $v ) ) {
				if ( is_int( $k ) ) {
					if ( isSet( $strKey ) ) {
						$strXml.='<'.$this->apply_tagcase( $strKey ).'>'.$this->to_10( $v ).'</'.$this->apply_tagcase( $strKey ).'>'.$this->br;
					} else {
						$strXml.=$this->to_10( $v );
					}
				} else {
					$strXml.='<'.$this->apply_tagcase( $k ).'>'.$this->br.$this->to_10( $v, $k ).'</'.$this->apply_tagcase( $k ).'>'.$this->br;
				}
			} else {
				$strXml.='<'.$this->apply_tagcase( $k ).'>'.$this->apply_encode( $v ).'</'.$this->apply_tagcase( $k ).'>'.$this->br;
			}
		}
		return $strXml;
	}

	private function to_20( $_arrTree ) {
		$strXml='';
		foreach ( $_arrTree as $k=>$v ) {
			$strXml.='<'.$this->apply_tagcase( $v['tag'] );
			if ( !empty( $v['attr'] ) ) {
				foreach ( $v['attr'] as $j=>$y ) {
					$strXml.=' '.$this->apply_tagcase( $j ).'="'.$this->apply_encode( $y ).'"';
				}
			}
			if ( !empty( $v['val'] ) ) {
				$strXml.='>';
				if ( is_array( $v['val'] ) ) {
					$strXml.=$this->br.$this->to_20( $v['val'] );
				} else {
					$strXml.=$this->apply_encode( $v['val'] );
				}
				$strXml.='</'.$this->apply_tagcase( $v['tag'] ).'>'.$this->br;
			} else {
				$strXml.='/>'.$this->br;
			}
		}
		return $strXml;
	}

	private function apply_tagcase( $_mix='' ) {
		switch( $this->tag_case ) {
			case 'lower':
				$_mix=is_array( $_mix ) ? array_change_key_case( $_mix ):strToLower( $_mix );
			break;
			case 'upper':
				$_mix=is_array( $_mix ) ? array_change_key_case( $_mix, CASE_UPPER ):strToUpper( $_mix );
			break;
		}
		return $_mix;
	}

	private function apply_encode( $_strData ) {
		$this->in_data=strtolower( $this->in_data );
		$this->out_data=strtolower( $this->out_data );
		if ( $this->in_data===$this->out_data ) {
			return $this->escapedata( $_strData );
		}
		switch ( $this->out_data ) {
			case 'utf-8': 
				if ( $this->in_data=='iso-8859-1' ) {
					return utf8_encode( $this->escapedata( $_strData ) );
				} elseif ( $this->in_data=='cp1251' ) {
					return $this->win2utf( $this->escapedata( $_strData ) );
				}
			break;
			case 'iso-8859-1': 
				if ( $this->in_data=='utf-8' ) {
					return $this->escapedata( utf8_encode( $_strData ) );
				}
			break;
			case 'cp1251': 
				if ( $this->in_data=='utf-8' ) {
					return $this->escapedata( $this->utf2win( $_strData ) );
				}
			break;
		}
		return 'error';
	}

	private function escapedata( $data ) {
		if ( $this->cdata ) {
			return $data;
		}
		$position=0;
		$length=strlen($data);
		$escapeddata="";
		for(;$position<$length;) {
			$character=substr($data,$position,1);
			$code=Ord($character);
			switch($code) {
				case 34: $character="&quot;"; break;
				case 38: $character="&amp;"; break;
				case 39: $character="&apos;"; break;
				case 60: $character="&lt;"; break;
				case 62: $character="&gt;"; break;
				default: if($code<32) $character=("&#".strval($code).";"); break;
			}
			$escapeddata.=$character;
			$position++;
		}
		return $escapeddata;
	}

	private function utf2win( $s ) {
		$out=$c1='';
		$byte2=false;
		for ( $c=0; $c<strlen($s); $c++ ) {
			$i=ord($s[$c]);
			if ($i<=127) $out.=$s[$c];
			if ($byte2) {
				$new_c2=($c1&3)*64+($i&63); 
				$new_c1=($c1>>2)&5; 
				$new_i=$new_c1*256+$new_c2; 
				if ($new_i==1025) {
					$out_i=168; 
				} else {
					$out_i=$new_i==1105 ? 184:($new_i-848);
				}
				$out.=chr($out_i); 
				$byte2=false; 
			}
			if (($i>>5)==6) { 
				$c1=$i; 
				$byte2=true; 
			}
		}
		return $out; 
	}

	private function win2utf( $str ) {
		$utf = '';
		for($i = 0; $i < strlen($str); $i++) {
			$donotrecode = false; 
			$c = ord(substr($str, $i, 1));
			if ($c == 0xA8) $res = 0xD081;
			elseif ($c == 0xB8) $res = 0xD191;
			elseif ($c < 0xC0) $donotrecode = true;
			elseif ($c < 0xF0) $res = $c + 0xCFD0;
			else $res = $c + 0xD090;
			$utf .= ($donotrecode) ? chr($c) : (chr($res >>8) . chr($res & 0xff));
		}
		return $utf;
	}
}
?>