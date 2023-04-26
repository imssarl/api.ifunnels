<?php
class Core_Mailer_Extension extends Zend_Mail {

	public function __construct( $charset='utf-8' ) {
		parent::__construct( $charset );
	}

	public function setReplyTo($email, $name='') {
		$this->_storeHeader('Reply-To', $this->_formatAddress($email, $name), true);
		return $this;
	}

	// array( array( 'body'=>string or stream, 'type'=>Zend_Mime::TYPE_..., 'filename'=>str ) )
	public function setAttachments( $_arrAttach=array() ) {
		foreach( $_arrAttach as $v ) {
			if ( empty( $v['body'] ) ) {
				continue;
			}
			if ( empty( $v['filename'] ) ) {
				$v['filename']='noname';
			}
			if ( empty( $v['type'] ) ) {
				$v['type']=Zend_Mime::TYPE_HTML;
			}
			if ( !empty( $v['type'] )&&$v['type']==Zend_Mime::TYPE_OCTETSTREAM ) {
				$v['encoding']=Zend_Mime::ENCODING_BASE64;
			} else {
				$v['encoding']=Zend_Mime::ENCODING_QUOTEDPRINTABLE;
			}
			$this->createAttachment( 
				$v['body'], 
				$v['type'], 
				Zend_Mime::DISPOSITION_ATTACHMENT, 
				$v['encoding'],
				$v['filename']
			);
		}
		return $this;
	}

	/**
	* файлы которые будут использованы для оформления Zend_Mime::TYPE_HTML частей
	* @param array $arrRes out - линки на картинки для шаблона
	* @param array $_arrDta in array of array( 'file'=>strFile, 'tag'=>тэг в шаблоне с html частью )
	* @return Core_Mailer_Extension Provides fluent interface
	*/
	public function setMessageFiles( &$arrRes, $_arrFiles=array() ) {
		if ( empty( $_arrFiles ) ) {
			return $this;
		}
		$objMd=Core_Media_Driver::getInstance();
		foreach( $_arrFiles as $v ) {
		
		
		
		
		}
		return $this;
			/*foreach( $_arrDta['attachment'] as $v ) {
				$mp = new Zend_Mime_Part($body);
				$mp->encoding = $encoding;
				$mp->type = $mimeType;
				$mp->disposition = $disposition;
				$mp->filename = $filename;
				$this->mail->addAttachment($mp);
			}*/
			// $this->set_parts( $_arrDta, $_arrDta['files'] ); сделать для встроенных в письмо файлов TODO!!! 12.06.2009
	
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
}
?>