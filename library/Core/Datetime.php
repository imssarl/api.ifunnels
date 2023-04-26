<?php


/**
 * все данные о дате-времени храняться в системе в UNIXTIME
 * пользователь вводит данные в систему в своём часовом поясе и просматривает данные тоже в своём
 * чтобы пользователи из разных часовых поясов видели время друг друга как в своём поясе
 * нужно при сохранении переводить в юникстайм (в результате получим время по UTC)
 * а при отображении преобразовывать учитывая указанный пользователем часовой пояс
 */
class Core_Datetime implements Core_Singleton_Interface {

	private $_format=Core_Datetime_Heir::RFC850;

	private $_time='now';

	private static $_instance=NULL;

	private static $_server='UTC';

	// http://www.php.net/manual/ru/timezones.php
	public static function setServerTimezone( $_str='' ) {
		if ( !empty( $_str ) ) {
			self::$_server=$_str;
		} elseif ( Zend_Registry::get( 'config' )->date_time->dt_zone ) {
			self::$_server=Zend_Registry::get( 'config' )->date_time->dt_zone;
		}
		if ( !date_default_timezone_set( self::$_server ) ) {
			throw new Exception( Core_Errors::DEV.'|Timezone ('.self::$_server.') is not a known timezone' );
		}
	}

	public static function getServerTimezone( &$strRes ) {
		$strRes=date_default_timezone_get();
		return !empty( $strRes );
	}

	// обычно для вывода временных зон на view
	public static function getTimezonesToSelect() {
		return array_combine( DateTimeZone::listIdentifiers(), DateTimeZone::listIdentifiers() );
	}

	// если у пользователя указан часовой пояс прописываем его в системе чтобы всё отображалось по этому поясу и сохранялось в UTC
	// иначе прописываем пользователю дефолтное серверное время
	public static function setUserTimezone() {
		if ( empty( Core_Users::$info['timezone'] ) ) {
			self::getServerTimezone( Core_Users::$info['timezone'] );
		} else {
			self::setServerTimezone( Core_Users::$info['timezone'] );
		}
	}

	// Format accepted by php-function date() http://www.php.net/manual/ru/function.date.php
	public function setFormat( $_str='' ) {
		if ( !empty( $_str ) ) {
			$this->_format=$_str;
		}
		return $this;
	}

	// принимаются значения в любом из этих http://www.php.net/manual/ru/datetime.formats.php вариантов
	public function setTime( $_str='' ) {
		if ( !empty( $_str ) ) {
			$this->_time=$_str;
		}
		return $this;
	}

	// данные от клиента приводятся в unixtime
	// часовой пояс либо серверный либо указанный на клиенте (см.setUserTimezone)
	// нужно делать перед сохранением в бд
	public function toUtc() {
		$obj=new Core_Datetime_Heir( $this->_time );
		return $obj->format( 'U' );
	}

	// для отображения клиенту данных из бд в его часовом поясе
	// в бд у нас хранится unixtime
	public function toLocal() {
		$obj=new Core_Datetime_Heir();
		$obj->setTimestamp( $this->_time );
		return $obj->format( $this->_format );
	}

	// преобразование времени из одного часового пояса в другой
	// это не касается unixtime
	public function toTimezone( $_strFrom='UTC', $_strTo='UTC' ) {
		$_objDt=new Core_Datetime_Heir( $this->_time, new DateTimeZone( $_strFrom ) );
		if ( $_strFrom!=$_strTo ) { // если пояса различаются приводим
			$_objDt->setTimeZone( new DateTimeZone( $_strTo ) );
		}
		return $_objDt->format( $this->_format ); // форматируем
	}

	public static function getInstance() {
		if ( self::$_instance==NULL ) {
			self::$_instance=new self();
		}
		return self::$_instance;
	}
}
?>