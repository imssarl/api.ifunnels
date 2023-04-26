<?php


class Core_Mailer extends Zend_Mail implements Core_Singleton_Interface {

	private static $_instance=NULL;

	public $smarty, $config;

	private $_version='4.0';

	private $_codepage='utf-8';

	private $_peopleTo=array();

	private $_peopleFrom=array();

	private $_withEvents=false;

	private $_templateVariables=array();

	private $_template='';

	private $_mailSubject='no subject';

	private $_apiKey = '';

	public function __construct(){
		$this->config=Zend_Registry::get( 'config' );
		$this->setTransport();
		parent::__construct( (empty( $this->config->mailer->codepage )? $this->_codepage:$this->config->mailer->codepage) );
	}

	public function setTransport(){
		switch ( $this->config->mailer->send_mode ){
			case 'sendmail': break; // Zend_Mail_Transport_Sendmail is default transport
			case 'debmes': self::setDefaultTransport(new Core_Mailer_Transport_Debmes()); break;
			case 'print': self::setDefaultTransport(new Core_Mailer_Transport_Print()); break;
			case 'smtp': self::setDefaultTransport(new Zend_Mail_Transport_Smtp( 
				$this->config->mailer->smtp->host, 
				$this->config->mailer->smtp->toArray() 
			)); break; // add params to config TODO!!! 21.10.2010
		}
	}

	public static function getInstance(){
		if ( self::$_instance==NULL ){
			self::$_instance=new self();
		}
		self::$_instance->init();
		return self::$_instance;
	}

	public function init(){
		$this->_peopleTo=array();
		$this->_peopleFrom=array();
		$this->_withEvents=false;
	}

	/**
	 * получение _peopleTo и _peopleFrom дополнительно из БД
	 *
	 * @return object
	 */
	public function withEvents(){
		$this->_withEvents=true;
		return $this;
	}

	/**
	 * установка получателей письма
	 *
	 * @param mixed $_mix 
	 * @return object
	 */
	public function setPeopleTo( $_mix=array() ){
		$this->_peopleTo=$this->cast( $_mix );
		return $this;
	}

	/**
	 * установка отправителей письма
	 *
	 * @param mixed $_mix 
	 * @return object
	 */
	public function setPeopleFrom( $_mix=array() ){
		$this->_peopleFrom=$this->cast( $_mix );
		return $this;
	}

	public function setSubject( $_str ){
		if ( !empty( $_str )&&is_string( $_str ) ){
			$this->_mailSubject=$_str;
		}
		return $this;
	}

	public function setVariables( $_arr=array() ){
		if ( !empty( $_arr )&&is_array( $_arr ) ){
			$this->_templateVariables=array_merge( $this->_templateVariables, $_arr );
		}
		return $this;
	}

	public function setTemplate( $_str='' ){
		if ( !empty( $_str )&&is_string( $_str ) ){
			$this->_template=$this->_templateVariables['flg_mode']=$_str;
		}
		return $this;
	}

	// array( array( 'body'=>string or stream, 'type'=>Zend_Mime::TYPE_..., 'filename'=>str ) )
	public function setAttachments( $_arrAttach=array() ){
		foreach( $_arrAttach as $v ){
			if ( empty( $v['body'] ) ){
				continue;
			}
			if ( empty( $v['filename'] ) ){
				$v['filename']='noname';
			}
			if ( empty( $v['type'] ) ){
				$v['type']=Zend_Mime::TYPE_HTML;
			}
			if ( !empty( $v['type'] )&&$v['type']==Zend_Mime::TYPE_OCTETSTREAM ){
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
	 * Один образец письма уходит нескольким/одному получателю
	 *
	 * @return boolean
	 */
	public function sendOneToMany(){
		if ( !$this->dataCheck() ){
			return false;
		}
		if (!class_exists('\SendGrid')){
			require_once 'library/sendgrid-php/sendgrid-php.php';
		}
		$from=$to=$content=null;
		foreach( $this->_peopleFrom as $v ){
			$from = new SendGrid\Email( $v['name'], $v['email'] );
		}
		$content = Core_View::factory( Core_View::$type['one'] )
					->setTemplate( Zend_Registry::get( 'config' )->path->relative->letters.'letter.tpl' )
					->setHash( $this->_templateVariables )
					->parse()
					->getResult();
		$content = new SendGrid\Content( "text/html", $content );
		foreach( $this->_peopleTo as $v ){
			$to = new SendGrid\Email( $v['name'], $v['email'] );
			$mail = new SendGrid\Mail( $from, $this->_mailSubject, $to, $content );
			$mail->setTemplateId("d33e45bf-dcc2-47b2-a14a-0d42b548a3ee");
			$sg = new \SendGrid( $this->_apiKey );
			$response = $sg->client->mail()->send()->post( $mail );
		}
		/*parent::setSubject( $this->_mailSubject );
		$this->setType( Zend_Mime::MULTIPART_MIXED )
			->addHeader( 'X-Mailer', 'Predator PHP mailer '.$this->_version )
			->addHeader( 'MIME-Version', '1.0' )
			->setBodyHtml( 
				Core_View::factory( Core_View::$type['one'] )
					->setTemplate( Zend_Registry::get( 'config' )->path->relative->letters.'letter.tpl' )
					->setHash( $this->_templateVariables )
					->parse()
					->getResult()
			);
		foreach( $this->_peopleFrom as $v ){
			$this->setFrom( $v['email'], @$v['name'] )->setReplyTo( $v['email'], @$v['name'] );
		}
		foreach( $this->_peopleTo as $v ){
			$this->addTo( $v['email'], @$v['name'] )
				->send()
				->clearRecipients();
			//$this->mail_log( $_arrDta ); Mailer_Logger TODO!!!
		}
		//$this->log_message_write(); // save log*/
		return true;
	}

	/**
	 * проверка достаточности данный
	 * сделать возможность получения ошибок TODO!!! 21.10.2010
	 *
	 * @return boolean
	 */
	private function dataCheck(){
		if ( empty( $this->_template ) ){
			return false;
		}
		// получаем дополнительных адресатов из Core_Mailer_Events
		if ( $this->_withEvents ){
			Core_Mailer_Events::getInstance()->get_info_by_event( $this->_peopleTo, $this->_peopleFrom, $this->_template );
		}
		if ( empty( $this->_peopleTo )||empty( $this->_peopleFrom ) ){
			return false;
		}
		return true;
	}

	/**
	 * приведение формата отправителей и получателей к нормальной форме:
	 * array( array( email=>email1, name=>name1 ), array( ... ) )
	 * поддерживаемые варианты входных данных: 
	 * email1, array( email1, email2 ), array( email=>email1, name=>name1 ), нормальная форма
	 *
	 * @param mixed $_mix 
	 * @return array
	 */
	private function cast( $_mix=array() ){
		if ( empty( $_mix ) ){
			return array();
		}
		if ( !is_array( $_mix ) ){ // email1
			return array( array( 'email'=>$_mix ) );
		}
		$_mixKey=key( $_mix );
		if ( empty( $_mixKey )&&!is_array( current( $_mix ) ) ){ // array( email1, email2 )
			$arrRes=array();
			foreach( $_mix as $v ){
				$arrRes[]=array( 'email'=>$v );
			}
			return $arrRes;
		}
		if ( is_string( $_mixKey ) ){ // array( email=>email1, name=>name1 )
			return array( $_mix );
		}
		if ( is_array( current( $_mix ) ) ){ // array( array( email=>email1, name=>name1 ), array( ... ) )
			return $_mix;
		}
		return array();
	}

	/**
	* файлы которые будут использованы для оформления Zend_Mime::TYPE_HTML частей
	* @param array $arrRes out - линки на картинки для шаблона
	* @param array $_arrDta in array of array( 'file'=>strFile, 'tag'=>тэг в шаблоне с html частью )
	* @return Core_Mailer_Extension Provides fluent interface
	*/
	public function setMessageFiles( &$arrRes, $_arrFiles=array() ){
		if ( empty( $_arrFiles ) ){
			return $this;
		}
		$objMd=Core_Media_Driver::getInstance();
		foreach( $_arrFiles as $v ){
		
		}
		return $this;
			/*foreach( $_arrDta['attachment'] as $v ){
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
	function set_parts( &$arrRes, $_arrDta=array() ){
		if ( empty( $_arrDta ) ){
			return false;
		}
		foreach( $_arrDta as $k=>$v ){
			$_strPath=empty( $v['path'] )? DIR_LETTER_IMG:$v['path'];
			$this->parts[$this->pnum]['name']=empty( $v['file'] )? M_TUMB_NOIMAGE:$v['file'];
			if ( !$this->objMd->d_readfromfile( $_strPath, $this->parts[$this->pnum]['name'], $this->parts[$this->pnum]['body'] ) ){
				continue;
			}
			if ( !$this->objMd->d_getmimetype( $_strPath, $this->parts[$this->pnum]['name'], $this->parts[$this->pnum]['mtype'] ) ){
				continue;
			}
			if ( !empty( $v['tag'] ) ){
				$this->parts[$this->pnum]['id']=$this->gen_unique();
				$arrRes[$v['tag']]='cid:'.$this->parts[$this->pnum]['id'];
			}
			$this->pnum++;
		}
		if ( empty( $this->parts[0]['mtype'] ) ){
			$this->parts=array();
		}
		return true;
	}
}





/*



class Core_Mailer extends Core_Mailer_Mimeletter {
	public $s_tbl=array( 'id', 'from_email', 'to_email', 'subject', 'body', 'sended' );
	public $s_num=0;
	public $s_type=3;
	public $s_logged=0;
	public $s_maillog=array();

	public function __construct( $_arrSet=array() ){
		parent::__construct();
		$this->smarty=new Core__Parsers_Smarty();
		if ( !empty( $_arrSet ) ){
			foreach( $_arrSet as $k=>$v ){
				$this->$k=$v;
			}
		}
	}

	public function send_massive( $_arrDta=array() ){
		if ( !$this->data_prepare( $_arrDta ) ){
			return false;
		}
		$this->smarty->template( $strMes, $_arrDta, DIR_SOURCE.'letters/letter.tpl' );// письмо полностью
		$this->set_message( $strMes );
		$this->set_subject( ( empty( $_arrDta['subject'] ) ? 'no subject':$_arrDta['subject'] ) );
		$this->set_from( $_arrDta['from'] );
		foreach( $_arrDta['to'] as $v ){
			$this->set_to( $v );
			if ( !$this->send() ){
				continue;
			}
			$this->mail_log( $_arrDta );
		}
		$this->log_message_write(); // save log
		return true;
	}

	private function data_prepare( &$_arrDta ){
		// получаем $_arrTo и $_arrFrom из входных данных
		if ( ( !$this->cast( $_arrTo, @$_arrDta['to'] )||!$this->cast( $_arrFrom, @$_arrDta['from'] ) )&&empty( $_arrDta['flg_mode'] ) ){
			return false;
		}
		// получаем $_arrTo и $_arrFrom из Core_Mailer_Events
		if ( !empty( $_arrDta['flg_mode'] ) ){
			Core_Mailer_Events::getInstance()->get_info_by_event( $_arrTo, $_arrFrom, $_arrDta['flg_mode'] );
		}
		if ( empty( $_arrTo )||empty( $_arrFrom ) ){
			return false;
		}
		if ( !empty( $_arrDta['files'] ) ){
			$this->set_parts( $_arrDta, $_arrDta['files'] );
		}
		$_arrDta['to']=$_arrTo;
		$_arrDta['from']=$_arrFrom;
		return $_arrDta;
	}

	// нормальной формой является array( array( email=>email1, name=>name1 ), array( ... ) )
	// возможные входные варианты от пользователя
	// email1, array( email1, email2 ), array( email=>email1, name=>name1 ), array( array( email=>email1, name=>name1 ), array( ... ) )
	private function cast( &$arrRes, $_mix='' ){
		if ( empty( $_mix ) ){
			return false;
		}
		if ( !is_array( $_mix ) ){ // email1
			$arrRes=array( array( 'email'=>$_mix ) );
			return true;
		}
		$_mixKey=key( $_mix );
		if ( empty( $_mixKey )&&!is_array( current( $_mix ) ) ){ // array( email1, email2 )
			$arrRes=array();
			foreach( $_mix as $v ){
				$arrRes[]=array( 'email'=>$v );
			}
			return true;
		}
		if ( is_string( $_mixKey ) ){ // array( email=>email1, name=>name1 )
			$arrRes=array( $_mix );
			return true;
		}
		if ( is_array( current( $_mix ) ) ){ // array( array( email=>email1, name=>name1 ), array( ... ) )
			$arrRes=$_mix;
			return true;
		}
		return false;
	}

	private function send(){
		$this->get_maildata();
		switch ( $this->s_type ){
			case 1: return $this->s_debmes(); break;
			case 2: $this->s_print(); break;
			case 3: return $this->s_phpmail(); break;
		}
		return true;
	}

	private function s_print(){
		$_arrHash=array( 'to'=>$this->to, 'from'=>$this->from, 'subject'=>$this->subject, 'headers'=>$this->head, 'body'=>$this->body );
		$this->smarty->template( $_strRes, $_arrHash, DIR_SOURCE.'letters/print.tpl' );
		ob_end_clean();
		print_r( $_strRes );
		exit;
	}

	private function s_debmes(){
		$_strRes="\r\nto: $this->to\r\nfrom: $this->from\r\nsubject: $this->subject\r\n";
		$_strRes.="headers:\r\n$this->head\r\nbody:\r\n ".str_replace( "\n", "\r\n", str_replace( "\r\n", "\n", $this->body ) )."\r\n\r\n";
		return $this->objMd->d_debmes( $_strRes );
	}

	private function s_phpmail(){
		return mail( $this->to_encode, $this->subject_encode, $this->body, $this->head );
	}

	private function log_message_write(){
		if ( empty( $this->s_maillog ) ){
			return false;
		}
		Core_Sql::setMassInsert( 'log_extmail', $this->s_maillog );
		$this->s_maillog=array();
		return true;
	}

	function get_message_from_log( &$arrRes, &$arrPg, $_arrSet=array() ){
		$obj=new Core_Sql_Qcrawler();
		$arrRes=Core_Sql::getAssoc( $obj->getPaged( $_strQ, $arrPg, 'SELECT * FROM log_extmail ORDER BY sended DESC', $_arrSet ) );
		return !empty( $arrRes );
	}

	function del_message_from_log( $_arrIds=array() ){
		if ( empty( $_arrIds ) ){
			return false;
		}
		Core_Sql::setExec( 'DELETE FROM log_extmail WHERE id IN("'.join( '", "', $_arrIds ).'")' );
		return true;
	}

	function get_client_log( &$arrRes, &$arrPg, $_arrSet=array() ){
		$obj=new Core_Sql_Qcrawler();
		$arrRes=Core_Sql::getAssoc( $obj->getPaged( $_strQ, $arrPg, 'SELECT * FROM mail_client_logger ORDER BY id DESC', $_arrSet ) );
		return !empty( $arrRes );
	}


	// получаем письмо без шапки - для логгирования
	private function parse_body( $_arrDta ){
		$this->smarty->template( $this->parsed_message, $_arrDta, DIR_SOURCE.'letters/inc_'.$_arrDta['flg_mode'].'.tpl' );
	}

	private function mail_log( $_arrDta ){
		if ( empty( $this->s_logged ) ){
			return false;
		}
		$this->parse_body( $_arrDta );
		$this->s_maillog[]=array(
			'from_email'=>$this->from,
			'to_email'=>$this->to,
			'subject'=>$this->subject,
			'body'=>$this->parsed_message,
			'sended'=>time(),
		);
		return true;
	}
}

*/
?>
