<?php


/**
 * Перехватчик ошибок и исключений
 */
class Core_Errors {

	const PHP=1; // ошибки которые кидает пхп
	const DEV=2; // все критические ошибки обработанные программистом
	const DB=3; // ошибки при запросах к БД
	const ENGINE=4; // критичные ошибки в движке
	const LOCAL=5; // ошибки при работе с локальной файловой системой
	const REMOTE=6; // ошибки при работе с удалёнными сервисами

	public static $version='4.0';

	private $_exception;
	private $_mode=0; // 0-на ошибки по возможности не реагируем, 1-отсылаем письмо, 2-и в браузер полную инфу
	private $_maxlen=128; // размер отображаемой в трэйсе текстовой переменной
	private $_info=array(); // массив с накопленными данными об ошибке
	private $_errorLog=false;

	private static $_off=false;

	private $_cods=array(
		E_ERROR=>'Error',
		E_WARNING=>'Warning',
		E_PARSE=>'Parsing Error',
		E_NOTICE=>'Notice',
		E_CORE_ERROR=>'Core Error',
		E_CORE_WARNING=>'Core Warning',
		E_COMPILE_ERROR=>'Compile Error',
		E_COMPILE_WARNING=>'Compile Warning',
		E_USER_ERROR=>'User Error',
		E_USER_WARNING=>'User Warning',
		E_USER_NOTICE=>'User Notice',
	);

	public function __construct() {
		if ( !function_exists( 'set_error_handler' ) ) { // < 4.0.1
			return;
		}
		if ( function_exists( 'version_compare' ) ) { // >= 4.0.7
			if ( version_compare( PHP_VERSION, '5', '>=' ) ) {
				$this->_cods[E_STRICT]='Runtime Notice';
			}
			if ( version_compare( PHP_VERSION, '5.2.0', '>=' ) ) {
				$this->_cods[E_RECOVERABLE_ERROR]='Catchable Fatal Error';
			}
		}
		set_error_handler( array( &$this, 'handling' ));
		if ( function_exists( 'set_exception_handler' ) ) { // >= 5
			set_exception_handler( array( &$this, 'catchExeption' ) );
		}
		$this->_mode=Zend_Registry::get( 'config' )->debugging->debug_mode;
		$this->_errorLog=Zend_Registry::get( 'config' )->path->absolute->logfiles.'error.mail.system.log';
	}

	public static function off() {
		self::$_off=true;
	}

	public function catchExeption( Exception $e ) {
		$this->_exception=$e;
		$this->handling( $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine() );
	}

	public function handling( $_intNum, $_strMsg, $_strFile, $_strLine ) {
		if ( self::$_off ) {
			return;
		}
		if ( in_array( $_intNum, array( E_NOTICE, E_WARNING ) ) ) { // не обарбатываем (как обработать Parse error? TODO!!!)
			return;
		}
		$this->_info['arrHeader']=array( 
			'errname'=>( empty( $this->_cods[$_intNum] )? 'Unknown Error':$this->_cods[$_intNum] ), 
			'msg'=>$_strMsg, 
			'file'=>str_replace( Zend_Registry::get( 'config' )->path->absolute->root, '', $_strFile ).' ['.$_strLine.' line]',
		);
		if ( ( $pos=strpos( $_strMsg, '|' ) )!==false ) { // эксепшн или триггер движка
			$this->_info['arrHeader']['myType']=substr( $_strMsg, 0, $pos );
			$this->_info['arrHeader']['msg']=substr( $_strMsg, ++$pos );
		} else { // системный эксепшн
			$this->_info['arrHeader']['myType']=self::PHP;
		}
		$this->_info['phpver']=PHP_VERSION;
		$this->_info['project']=Zend_Registry::get( 'config' )->engine->project_domain;
		$this->_info['datetime']=date( "M d, Y H:i:s" );
		$this->_info['session']=@print_r( $_SESSION, true );
		$this->_info['server']=@print_r( $_SERVER, true );
		$this->_info['request']=@print_r( $_REQUEST, true );
		$this->trace();
		$this->messaging();
	}

	private function trace() {
		if ( !function_exists( 'debug_backtrace' ) ) { // >= 4.3.0
			return;
		}
		$_arrTrace=empty( $this->_exception )? debug_backtrace():$this->_exception->getTrace();
		array_splice( $_arrTrace, 0, 3 );
		if ( empty( $_arrTrace ) ) {
			return;
		}
		foreach ( $_arrTrace as $k=>$v ) {
			$_arrA=$_arrO=array();
			if ( !empty( $v['args'] ) ) {
				foreach ( $v['args'] as $i ) {
					if ( is_null( $i ) ) {
						$_arrA[]='null';
					} elseif ( is_array( $i ) ) {
						$_arrA[]='Array('.sizeof( $i ).')';
					} elseif ( is_object( $i ) ) {
						$_arrA[]='Object:'.get_class( $i );
					} elseif ( is_bool( $i ) ) {
						$_arrA[]=$i ? 'true':'false';
					} elseif ( is_array( $i ) ) {
						$_arrA[]='array('.sizeof( $i ).')';
					} elseif ( !empty( $i ) ) {
//						$_str=Core_String::getInstance( htmlspecialchars( (string)$i ) );
//						$_arrO[]=$_str->setNeedLength( $this->_maxlen )->fullWords()? $_str->getResult().' ...':$_str->getResult();
					}
				}
			}
			if ( isSet( $v['class'] ) ) {
				$this->_info['trace'][$k]['method']=$v['class'].'::'.$v['function'].'('.join( ', ', $_arrA ).')';
			} elseif ( isSet( $v['function'] ) ) {
				$this->_info['trace'][$k]['function']=$v['function'].'('.join( ', ', $_arrA ).')';
			}
			if ( !empty( $_arrO )&&$v['function']!='trigger_error' ) {
				$this->_info['trace'][$k]['args']=join( ', ', $_arrO );
			}
			if ( !empty( $v['file'] )&!empty( $v['line'] ) ) {
				$this->_info['trace'][$k]['file']=str_replace( Zend_Registry::get( 'config' )->path->absolute->root, '', $v['file'] ).' ['.$v['line'].' line]';
			}
		}
	}

	private function messaging() {
		// отсылаем письмо + если ошибка произошла во время исполнения скрипта под апачем
		// и определён домен из любого метса TODO!!! 22.10.2010
		if ( ($this->_mode==1||$this->_mode==3)&&!empty( Zend_Registry::get( 'config' )->engine->project_domain ) ) {
			// во время ошибки 8192: 'Call-time pass-by-reference has been deprecated' тогда почемуто Fatal error: Class 'Core_Mailer' not found in D:\www\dev\wpplugin\svn\trunk\library\Core\Users\Management.php on line 125 разобраться TODO!!! 06.12.2011
			Core_Files::getContent( $_errorMailLog, $this->_errorLog );
			$_errorMailLog=unserialize($_errorMailLog);
			// отправляем однотипные сообщения на почту раз в час.
//			if( empty($_errorMailLog[$this->_info['arrHeader']['file'].'|'.$this->_info['arrHeader']['msg']])||$_errorMailLog[$this->_info['arrHeader']['file'].'|'.$this->_info['arrHeader']['msg']]<(time()-3600) ){
				$_errorMailLog[$this->_info['arrHeader']['file'].'|'.$this->_info['arrHeader']['msg']]=time();
				$_errorMailLog=serialize($_errorMailLog);
				Core_Files::setContent($_errorMailLog,$this->_errorLog);
				Core_Mailer::getInstance()
					->setVariables( $this->_info )
					->setTemplate( 'system_error' )
					->setSubject( 'Error hunter '.self::$version.': '.Zend_Registry::get( 'config' )->engine->project_domain.' system error' )
					->setPeopleTo( Zend_Registry::get( 'config' )->engine->default_bugtrack->toArray() )
					->setPeopleFrom( array( 'name' => Zend_Registry::get('config')->engine->project_sysemail->name, 'email' => Zend_Registry::get('config')->engine->project_sysemail->email ) )
					->sendOneToMany();
//			}
		}
		if ( $this->_mode<=1 ) {
			while( @ob_end_clean() );
			Core_View::factory()
				->setTemplate( Zend_Registry::get( 'config' )->path->relative->source.'error_page.tpl' )
				->setHash( $this->_info )
				->parse()
				->show();
			return;
		}
		if ( $this->_mode>1 ) { // отображаем в браузере
			while( @ob_end_clean() );
			Core_View::factory()
				->setTemplate( Zend_Registry::get( 'config' )->path->relative->source.'error_alert.tpl' )
				->setHash( $this->_info )
				->parse()
				->show();
			exit;
		}
		// возможно некотрые типы ошибок будем писать в отдельный файлик
		// error_log($err, 3, "/usr/local/php4/error.log");
	}
}
?>