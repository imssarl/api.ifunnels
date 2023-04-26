<?php


/**
 * Locate & accumulate links history for each project visitor
 */
class Core_Module_Location extends Core_Stack {

	// <схема>://<логин>:<пароль>@<хост>:<порт>/<URL-путь>?<параметры>#<якорь>
	const URLFULL=1;

	// <схема>://<логин>:<пароль>@<хост>:<порт>/<URL-путь>?<параметры>
	const URLVARS=2;

	// <схема>://<логин>:<пароль>@<хост>:<порт>/<URL-путь>
	const URLPATH=3;

	private $_module; // текущий модуль

	public function __construct() {
		parent::__construct( 'location' );
	}

	public function initLocation( Core_Module_Interface &$module ) {
		$this->setMaxNest( $module->config->engine->max_back_urls );
		$this->_module=&$module;
	}

	// уникальные переходы
	public function uniq() {
		if ( $this->_module->getViewMode( $_int ) ) { // попапы и сервисы (xml,json) пропускаем
			return false;
		}
		if ( !empty( $this->stack[1] )&&Core_Module_Router::$uriFull==$this->stack[1] ) { // если воспользовались кнопой back, урл откуда пришли затираем
			$this->shift();
			return false;
		}
		if ( !empty( $this->stack[0] )&&Core_Module_Router::$uriFull==$this->stack[0] ) { // f5 неучитываем
			return false;
		}
		$this->push( Core_Module_Router::$uriFull );
		return true;
	}

	// история всех переходов
	public function hist() {
		if ( $this->_module->getViewMode( $_int ) ) { // попапы и сервисы (xml,json) пропускаем
			return false;
		}
		if ( !empty( $this->stack[0] )&&Core_Module_Router::$uriFull==$this->stack[0] ) { // f5 неучитываем
			return false;
		}
		$this->push( Core_Module_Router::$uriFull );
		return true;
	}

	public function get( $_intDepth=1 ) {
		if ( isSet( $this->stack[$_intDepth] ) ) {
			return $this->stack[$_intDepth];
		}
		return Core_Module_Router::$offset;
	}

	public function location( $_mix='', $_flgSkipBack=0 ) {
		if ( !empty( $_flgSkipBack ) ) { // если текущий урл запоминать ненадо
			$this->shift();
		}
		header( 'Location: '.Core_Module_Router::generateLocationUrl( $this->_module, $_mix ) );
		exit;
	}
}
?>