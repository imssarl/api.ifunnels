<?php
class Core_Uri_Http extends Zend_Uri_Http implements Core_Singleton_Interface {

	/**
	 * экземпляр объекта текущего класса (singleton)
	 *
	 * @var object
	 */
	private static $_instance=NULL;

	/**
	 * возвращает экземпляр объекта текущего класса (singleton)
	 * при первом обращении создаёт
	 *
	 * @return object
	 */
	public static function getInstance( $_url='' ) {
		if ( self::$_instance==NULL ) {
			$uri=explode(':', $_url, 2);
			self::$_instance=new self( $uri[0], $uri[1] );
		}
		return self::$_instance;
	}

	public function getPathToArray() {
		return explode( '/', trim( $this->getPath(), '/' ) );
	}

	// <схема>://<логин>:<пароль>@<хост>:<порт>/<URL-путь>?<параметры>#<якорь>
	public function getUrl() {
		return parent::getUri();
	}

	/**
	 * Validate the current URI from the instance variables. Returns true if and only if all
	 * parts pass validation.
	 *
	 * @return boolean
	 */
	public function validPathFull() {
		// Return true if and only if all parts of the URI have passed validation
		return $this->validatePath()
			and $this->validateQuery()
			and $this->validateFragment();
	}

	// /<URL-путь>?<параметры>#<якорь>
	public function getPathFull() {
		if ( $this->validPathFull()===false ) {
			throw new Exception( Core_Errors::DEV.'|One or more parts of the URI are invalid' );
		}
		$query= strlen($this->_query) > 0 ? "?$this->_query" : '';
		$fragment = strlen($this->_fragment) > 0 ? "#$this->_fragment" : '';
		return $this->_path
			.$query
			.$fragment;
	}
}
?>