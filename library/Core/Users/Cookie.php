<?php
class Core_Users_Cookie {

	// префикс который используется для организации пользовательских кук
	public static $_cookieName='';

	public static function setCookieName() {
		if ( !empty( self::$_cookieName ) ) {
			return;
		}
		self::$_cookieName=str_replace( '.', '_', 
			Zend_Registry::get( 'config' )->user->cookies_name.
			Zend_Registry::get( 'config' )->engine->project_domain.'_'.
			Core_Module_Router::$curSiteName );
	}

	public static function write( &$_arrData ) {
		$_str=time()+Zend_Registry::get( 'config' )->user->interval;
		setcookie( self::$_cookieName."[email]", $_arrData['email'], $_str, '/' );
		setcookie( self::$_cookieName."[nickname]", $_arrData['nickname'], $_str, '/' );
		setcookie( self::$_cookieName."[passwd]", $_arrData['passwd'], $_str, '/' );
		setcookie( self::$_cookieName."[rem]", 1, $_str, '/' );
		return true;
	}

	public static function delete() {
		if ( empty( $_COOKIE[self::$_cookieName] ) ) {
			return false;
		}
		setcookie( self::$_cookieName."[email]", "", time()-42000, '/' );
		setcookie( self::$_cookieName."[nickname]", "", time()-42000, '/' );
		setcookie( self::$_cookieName."[passwd]", "", time()-42000, '/' );
		setcookie( self::$_cookieName."[rem]", "", time()-42000, '/' );
		return true;
	}

	public static function setLng() {
		$_strLng=Zend_Registry::get( 'locale' )->getLanguage();
		if ( empty( $_strLng )||$_strLng==$_COOKIE['client_lng'] ) {
			return;
		}
		// сюда доходит если первый раз указываем язык или меняем
		setcookie( "client_lng", $_strLng, (time()+Zend_Registry::get( 'config' )->user->interval), '/' );
	}

	public static function getLng() {
		return $_COOKIE['client_lng'];
	}
}
?>