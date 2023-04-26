<?php
/**
 * WorkHorse Framework
 *
 * @category WorkHorse
 * @package Core_Users
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2005-2010, Rodion Konnov
 * @author Rodion Konnov <kindzadza@mail.ru>
 * @date 06.04.2010
 * @version 1.0
 */


/**
 * Fake class to use in scripts outside user's sessions
 * Zend_Registry::set( 'objUser', new Core_Users_Fake( $intUserId ) );
 *
 * @category WorkHorse
 * @package Core_Users
 * @copyright Copyright (c) 2005-2010, Rodion Konnov
 * @license http://opensource.org/licenses/ MIT License
 */
class Core_Users_Fake extends Core_Users implements Core_Users_Fake_Interface {

	private $_zero=false;

	private static $_isCashe=false;

	private static $_casheObj;

	// переделать Core_Users
	// добавить туда упрощённый вариант конструктора
	// сделать кэширование объекта по требованию
	// проверить что творится с сессией во время этих претурбаций
	// если избавимся от фэйк классов сделать factory private
	// TODO!!! 02.09.2010
	public function __construct( $_intId=0 ) {
		$this->factory( array( 'objManageAccess' ) );
		if ( !empty( $_intId ) ) {
			$this->getProfileById( Core_Users::$info, $_intId );
		}
		$this->u_info=&Core_Users::$info; // DEPERCATED!!!! 23.08.2010 use Core_Users::$info instead
	}

	public function getId( &$_intId ) {
		if ( $this->_zero ) {
			$_intId=0;
			return true; // в случае если нужен объект без определённого пользователя
			// продумать что в этом случае делать с папками и сохранением данных, поидее надо запрещать TODO!!!
		}
		$_intId=Core_Users::$info['id'];
		return true;
	}

	public function setZero() {
		$this->_zero=true;
	}

	public static function setCashe() {
		self::$_isCashe=true;
	}

	public static function byUserId( $_int=0 ) {
		if ( self::$_isCashe&&Zend_Registry::isRegistered( 'objUser' ) ) {
			self::$_casheObj=Zend_Registry::get( 'objUser' );
		}
		Zend_Registry::set( 'objUser', new Core_Users_Fake( $_int ) );
	}

	public static function zero() {
		if ( self::$_isCashe&&Zend_Registry::isRegistered( 'objUser' ) ) {
			self::$_casheObj=Zend_Registry::get( 'objUser' );
		}
		Zend_Registry::set( 'objUser', new Core_Users_Fake() );
		Zend_Registry::get( 'objUser' )->setZero();
	}

	// метод для восстановления предыдущего значения objUser
	public static function retrieveFromCashe() {
		if ( !is_object( self::$_casheObj ) ) {
			return;
		}
		Zend_Registry::set( 'objUser', self::$_casheObj );
	}
}
?>