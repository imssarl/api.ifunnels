<?php
class Core_Common_Code {

	private $_code='';

	private $_lenght=0;

	private $_charset='';

	private $_charsetLen=0;

	public function getCode() {
		return $this->_code;
	}

	public function setLenght( $_int=0 ) {
		$this->_lenght=$_int;
		return $this;
	}

	public function setCharset( $_str='' ) {
		$this->_charset=$_str;
		$this->_charsetLen=mb_strlen( $this->_charset );
		return $this;
	}

	public function generator() {
		if ( $this->_lenght<1 ) {
			return $this;
		}
		for ($i=0; $i<$this->_lenght; $i++) {
			$this->_code.=$this->_charset[(mt_rand(0,$this->_charsetLen-1))];
		}
		return $this;
	}

	public function randInt() {
		return $this->setCharset( "0123456789" )->generator();
	}

	public function randStr() {
		if ( $this->_lenght<1 ) {
			return $this;
		}
		while ( strlen( $this->_code )<$this->_lenght ) {
			$_intRand=mt_rand(0,61);
			if ( $_intRand<10 ) {
				$this->_code.=chr( $_intRand+48 );
			} elseif ( $_intRand<36 ) {
				$this->_code.=chr( $_intRand+55 );
			} else {
				$this->_code.=chr( $_intRand+61 );
			}
		}
		return $this;
	}

	public static function genExtendUniqid() {
		return Zend_Crypt::hash( 'md5', uniqid( rand(), true ) );
	}

	private $_iteration=20;

	private $_table='';

	private $_field='';

	public function setTable( $_str=0 ) {
		$this->_table=$_str;
		return $this;
	}

	public function setField( $_str=0 ) {
		$this->_field=$_str;
		return $this;
	}

	// $code->setTable( 'some' )->setField( 'some' )->checkUniq()->getCode();
	public function checkUniq() {
		$_flg=1;
		$i=0;
		do {
			if ( $i>$this->_iteration ) {
				throw new Exception( Core_Errors::DEV.'|can\'t generate uniq code. '.$this->_iteration.' time try.' );
			}
			$i++;
			$this->_code=self::genExtendUniqid();
			if ( !empty( $this->_table )&&!empty( $this->_field ) ) {
				$_flg=0;
				$_flg=Core_Sql::getCell( 'SELECT 1 FROM '.$this->_table.' WHERE '.$this->_field.'="'.$this->_code.'"' );
			}
		} while( !empty( $_flg ) ) ;
		return $this;
	}

	public static function hex2rgb( $_hex ) {
		$_hex = str_replace("#", "", $_hex);
		if (strlen($_hex) == 3) {
			$_r = hexdec(substr($_hex, 0, 1) . substr($_hex, 0, 1));
			$_g = hexdec(substr($_hex, 1, 1) . substr($_hex, 1, 1));
			$_b = hexdec(substr($_hex, 2, 1) . substr($_hex, 2, 1));
		} else {
			$_r = hexdec(substr($_hex, 0, 2));
			$_g = hexdec(substr($_hex, 2, 2));
			$_b = hexdec(substr($_hex, 4, 2));
		}
		return "$_r, $_g, $_b";
	}
}
?>