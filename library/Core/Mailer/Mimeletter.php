<?php


/**
 * Generate valid mime mail
 * @internal Generate mail with html,css,images,attached files
 */


class Core_Mailer_Mimeletter extends Core_Services {

	private $mtype='text/html';
	private $mcode='utf-8';
	private $body_encoding="quoted-printable"; // base64, 7bit, 8bit, binary, quoted-printable

	public $body='';
	public $head='';
	// используются для логгирования
	public $subject='';
	public $to='';
	public $from='';
	// используются при отправке сообщения
	public $subject_encode='';
	public $to_encode='';
	public $from_encode='';

	public $boundary='';
	public $message='';
	public $parts=array();
	public $unique=array();
	public $pnum=0;
	public $version="1.6";
	public $le="\n";
	/**
	* конструктор (void)
	* назначаем boundary - разделитель частей миме-письма
	* @return none
	*/
	public function __construct() {
		$this->objMd=Core_Media_Driver::getInstance();
		$this->boundary='BM----'.$this->gen_unique();
	}
	/**
	* тело письма
	* @param string $_strMess in
	* @return boolean
	*/
	public function set_message( $_strMess='' ) {
		if ( empty( $_strMess ) ) {
			return false;
		}
		$this->message=$this->encode_string( $_strMess, $this->body_encoding );
		return true;
	}

	/**
	* тема письма
	* @param string $_strMtype in
	* @return boolean
	*/
	public function set_subject( $_strSubj='' ) {
		if ( empty( $_strSubj ) ) {
			return false;
		}
		$this->subject=$_strSubj;
		$this->subject_encode='=?'.$this->mcode.'?B?'.base64_encode( $_strSubj ).'?=';
		return true;
	}

	/**
	* mime-тип тела письма
	* @param string $_strMtype in
	* @return boolean
	*/
	public function set_mtype( $_strMtype='' ) {
		if ( empty( $_strMtype ) ) {
			return false;
		}
		$this->mtype=$_strMtype;
		return true;
	}

	/**
	* кодировка письма
	* @param string $_strMtype in
	* @return boolean
	*/
	public function set_mcode( $_strMcode='' ) {
		if ( empty( $_strMcode ) ) {
			return false;
		}
		$this->mcode=$_strMcode;
		return true;
	}
	/**
	* информация относящаяся к файлам в сообщении и присоединёных к письму
	* @param array $arrRes out - линки на картинки для шаблона
	* @param array $_arrDta in array of array( 'path'=>strPath, 'file'=>strFile, 'tag'=>strTag )
	* @return boolean
	*/
	function set_parts( &$arrRes, $_arrDta=array() ) {
		if ( empty( $_arrDta ) ) {
			return false;
		}
		foreach( $_arrDta as $k=>$v ) {
			$_strPath=empty( $v['path'] )? DIR_LETTER_IMG:$v['path'];
			$this->parts[$this->pnum]['name']=empty( $v['file'] )? M_TUMB_NOIMAGE:$v['file'];
			if ( !$this->objMd->d_readfromfile( $_strPath, $this->parts[$this->pnum]['name'], $this->parts[$this->pnum]['body'] ) ) {
				continue;
			}
			if ( !$this->objMd->d_getmimetype( $_strPath, $this->parts[$this->pnum]['name'], $this->parts[$this->pnum]['mtype'] ) ) {
				continue;
			}
			if ( !empty( $v['tag'] ) ) {
				$this->parts[$this->pnum]['id']=$this->gen_unique();
				$arrRes[$v['tag']]='cid:'.$this->parts[$this->pnum]['id'];
			}
			$this->pnum++;
		}
		if ( empty( $this->parts[0]['mtype'] ) ) {
			$this->parts=array();
		}
		return true;
	}

	public function set_to( $_arrTo ) {
		if ( !empty( $_arrTo['name'] ) ) {
			$this->to_encode='=?'.$this->mcode.'?B?'.base64_encode( $_arrTo['name'] ).'?= <'.$_arrTo['email'].'>';
			$this->to=$_arrTo['name'].' <'.$_arrTo['email'].'>';
		} else {
			$this->to_encode=$this->to=$_arrTo['email'];
		}
	}

	public function set_from( $_arrFrom ) {
		$this->from_encode=$this->from=array();
		foreach( $_arrFrom as $v ) {
			if ( !empty( $v['name'] ) ) {
				$this->from_encode[]='=?'.$this->mcode.'?B?'.base64_encode( $v['name'] ).'?= <'.$v['email'].'>';
				$this->from[]=$v['name'].' <'.$v['email'].'>';
				continue;
			}
			$this->from_encode[]=$this->from[]=$v;
		}
		$this->from_encode=implode( ', ', $this->from_encode );
		$this->from=implode( ', ', $this->from );
	}

	/**
	* формируем письмо (head + body)
	* @return none
	*/
	function get_maildata() {
		// generate head
		$this->head="From: ".$this->from_encode.$this->le;
		//$this->head.="To: ".$this->to.$this->le;
		//$this->head.="Subject: ".$this->subject.$this->le;
		$this->head.="X-Mailer: Predator PHP mailer ".$this->version.$this->le;
		$this->head.="Reply-To: ".$this->from_encode.$this->le;
		$this->head.="MIME-Version: 1.0.".$this->le;
		$this->head.="Date: ".date( 'r' ).$this->le;
		$this->head.="Content-Type: multipart/mixed; boundary=\"$this->boundary\"".$this->le;
		if ( !empty( $this->add_head ) ) {
			$this->head.=join( $this->le, $this->add_head ).$this->le;
		}
		// generate body
		$this->body="This is a MIME encoded message.".$this->le.$this->le;
		// attachments
		if ( !empty( $this->parts ) ) {
			foreach ( $this->parts as $k=>$v ) {
				$this->body.="--".$this->boundary.$this->le;
				$this->get_parts( $k );
			}
		}
		// message
		$this->body.="--".$this->boundary.$this->le;
		$this->body.="Content-Type: ".$this->mtype."; charset=".$this->mcode.";".$this->le;
		$this->body.="Content-Transfer-Encoding: ".$this->body_encoding.$this->le.$this->le;
		$this->body.=$this->message.$this->le.$this->le;
		// end
		$this->body.="--".$this->boundary."--".$this->le;
	}
	/**
	* присоединяем файлы к письму (link + attachments)
	* @param integer $k in - id в $this->parts массиве
	* @return none
	*/
	function get_parts( $k ) {
		$this->body.="Content-Type: ".$this->parts[$k]['mtype'];
		$this->body.=!empty( $this->parts[$k]["name"] ) ? "; name = \"".$this->parts[$k]['name']."\"".$this->le : $this->le;
		$this->body.="Content-Transfer-Encoding: base64".$this->le;
		if ( !empty( $this->parts[$k]['id'] ) ) {
			$this->body.="Content-ID: <".$this->parts[$k]['id'].">".$this->le.$this->le;
		} else {
			$this->body.="Content-Disposition: attachment; filename=\"".$this->parts[$k]['name']."\"".$this->le.$this->le;
		}
		$this->body.=chunk_split( base64_encode( $this->parts[$k]["body"] ) )."".$this->le;
	}
	/**
	* генерим уникальный разделитель для частей письма
	* @return integer
	*/
	function gen_unique() {
		do {
			$intUniq=md5(uniqid(time()));
		} while ( in_array( $intUniq, $this->unique ) );
		$this->unique[]=$intUniq;
		return $intUniq;
	}
	/**
	* Encodes string to requested format. Returns an
	* empty string on failure.
	* @access private
	* @return	string
	*/
	 function encode_string($str, $encoding = "base64") {
		$encoded = "";
		switch(strtolower($encoding)) {
			case "base64":$encoded = chunk_split(base64_encode($str), 76, $this->le); break;
			case "7bit": case "8bit":
				$encoded = $this->fix_eol($str);
				if (substr($encoded, -(strlen($this->le))) != $this->le)
					$encoded .= $this->le;
			break;
			case "binary": $encoded = $str; break;
			case "quoted-printable": $encoded = $this->qp_enc($str); break;
			default: throw new Exception( Core_Errors::DEV.'|mail encoding not set' ); break;
		}
		return $encoded;
	}
	/**
	* Encode string to quoted-printable.
	* @access private
	* @return string
	*/
	function qp_enc($str) {
		$encoded = $this->fix_eol($str);
		if (substr($encoded, -(strlen($this->le))) != $this->le)
			$encoded .= $this->le;
		// Replace every high ascii, control and = characters
		$encoded = preg_replace('/([\000-\010\013\014\016-\037\075\177-\377])/e', "'='.sprintf('%02X', ord('\\1'))", $encoded);
		// Replace every spaces and tabs when it's the last character on a line
		$encoded = preg_replace("/([\011\040])".$this->le."/e", "'='.sprintf('%02X', ord('\\1')).'".$this->le."'", $encoded);
		// Maximum line length of 76 characters before CRLF (74 + space + '=')
		$encoded = $this->wrap_text($encoded, 74, true);
		return $encoded;
	}
	/**
	* Wraps message for use with mailers that do not
	* automatically perform wrapping and for quoted-printable.
	* Original written by philippe.
	* @access private
	* @return string
	*/
	function wrap_text($message, $length, $qp_mode = false) {
		$soft_break = ($qp_mode) ? sprintf(" =%s", $this->le) : $this->le;
		$message = $this->fix_eol($message);
		if (substr($message, -1) == $this->le)
			$message = substr($message, 0, -1);
		$line = explode($this->le, $message);
		$message = "";
		for ($i=0 ;$i < count($line); $i++) {
			$line_part = explode(" ", $line[$i]);
			$buf = "";
			for ($e = 0; $e<count($line_part); $e++){
				$word = $line_part[$e];
				if ($qp_mode and (strlen($word) > $length)) {
					$space_left = $length - strlen($buf) - 1;
					if ($e != 0) {
						if ($space_left > 20) {
							$len = $space_left;
							if (substr($word, $len - 1, 1) == "=")
							  $len--;
							elseif (substr($word, $len - 2, 1) == "=")
							  $len -= 2;
							$part = substr($word, 0, $len);
							$word = substr($word, $len);
							$buf .= " " . $part;
							$message .= $buf . sprintf("=%s", $this->le);
						} else {
							$message .= $buf . $soft_break;
						}
						$buf = "";
					}
					while (strlen($word) > 0) {
						$len = $length;
						if (substr($word, $len - 1, 1) == "=")
							$len--;
						elseif (substr($word, $len - 2, 1) == "=")
							$len -= 2;
						$part = substr($word, 0, $len);
						$word = substr($word, $len);
						if (strlen($word) > 0)
							$message .= $part . sprintf("=%s", $this->le);
						else
							$buf = $part;
					}
				} else {
					$buf_o = $buf;
					$buf .= ($e == 0) ? $word : (" " . $word);
					if (strlen($buf) > $length and $buf_o != "") {
						$message .= $buf_o . $soft_break;
						$buf = $word;
					}
				}
			}
			$message .= $buf . $this->le;
		}
		return $message;
	}
	/**
	* Changes every end of line from CR or LF to CRLF.
	* @access private
	* @return string
	*/
	function fix_eol($str) {
		$str = str_replace("\r\n", "\n", $str);
		$str = str_replace("\r", "\n", $str);
		$str = str_replace("\n", $this->le, $str);
		return $str;
	}
}
?>