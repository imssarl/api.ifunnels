<?php


/**
 * i18n init current language
 */
class Core_i18n extends Zend_Locale implements Core_Singleton_Interface {

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
	public static function getInstance() {
		if ( !Zend_Registry::get( 'config' )->engine->i18n ) { // проект не мультиязычный
			return;
		}
		if ( self::$_instance==NULL ) {
			self::$_instance=new self();
		}
		return self::$_instance;
	}

	private $_availableLng=array();

	public static $lang=array(
		'en'=>'English',
		'fr'=>'French',
		'sp'=>'Spanish',
		'de'=>'German',
	);

	public function __construct( $locale=null ) {
		parent::__construct( $locale );
		$this->_availableLng=Zend_Registry::get( 'config' )->i18n->languages->toArray();
		$this->setDefault( Zend_Registry::get( 'config' )->i18n->default_language );
		$this->setTranslator();
		if ( !Zend_Registry::isRegistered( 'locale' ) ) {
			Zend_Registry::set( 'locale', $this ); // закидываем объект в регистри
			$this->initLngVars(); // только доступные языки
		}
	}

	private function initLngVars() {
		foreach( self::$lang as $k=>$v ) {
			if ( !in_array( $k, $this->_availableLng ) ) {
				unSet( self::$lang[$k] );
			}
		}
	}

	public function checkAvailability( $strLng='' ) {
		return !empty( $strLng )&&in_array( $strLng, $this->_availableLng );
	}

	/*
	1.пользовательские настройки
	2.куки
	2.браузер
	3.по дефолту
	*/
	public function setLocale( $strLng='' ) {
		if ( empty( $strLng ) ) {
			if ( !empty( Core_Users::$info['lang'] ) ) {
				$strLng=Core_Users::$info['lang'];
			} elseif ( Core_Users_Cookie::getLng()!=null ) {
				$strLng=Core_Users_Cookie::getLng();
			} else {
				$strLng=$this->getLanguage();
			}
		}
		if ( !$this->checkAvailability( $strLng ) ) { // ещё раз проверка на доступность
			$strLng=Zend_Registry::get( 'config' )->i18n->default_language; // применяем дефолтный из конфига
		}
		parent::setLocale( $strLng );
	}

	// существует ли язык указанный в ссылке или правильно ли он указан
	public function checkInUrl( $strLng='' ) {
		$_bool=true;
		// проверка языка на соответствие ISO 639-1 (1998) более новые трёхбуквенные не поддерживаются
		// если ничего не подошло значит язык в ссылке не указан
		if ( !Zend_Locale::isLocale( $strLng ) ) {
			$strLng='';
			$_bool=false; // значит нужно будет делать редирект
		}
		$this->setLocale( $strLng );
		//p( Core_Users::$info );
		return $_bool;
	}

	// поддерживается ли указанный по ссылке язык
	public function checkSupporting( $strLng='' ) {
		$_bool=true;
		if ( !$this->checkAvailability( $strLng ) ) { // язык по ссылке не подходит
			$strLng='';
			$_bool=false; // значит нужно будет делать редирект
		}
		$this->setLocale( $strLng );
		return $_bool;
	}

	public function setTranslator() {
		Zend_Registry::set( 'translate', new Zend_Translate( array( 
			'adapter'=>'gettext',
			'content'=>Zend_Registry::get( 'config' )->path->absolute->locale.$this->getLanguage().DIRECTORY_SEPARATOR.'LC_MESSAGES'.DIRECTORY_SEPARATOR.'messages.mo',
			'locale'=>$this->getLanguage() 
		) ) );
	}
}
?>