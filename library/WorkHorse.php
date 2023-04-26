<?php


/**
 * Framework bootstrap
 */
final class WorkHorse {

	/**
	 * показывает в каком режиме старотовали
	 * true - запуск из коммандной строки; false - запуск по http
	 *
	 * @var boolean
	 */
	public static $_isShell=false;

	/**
	 * запуск по url
	 *
	 * @return void
	 */
	public static function run() {
		self::preparation();
		Core_Users::getInstance()->setWebUser();
		Core_Module::startSite();
	}

	/**
	 * системные вызывы приходящие по http
	 *
	 * @param array $_arr ($_GET массив)
	 * @return void
	 */
	public static function src( $_arr=array() ) {
		if ( empty( $_arr['name'] ) ) {
			header( 'HTTP/1.0 404 Not Found' );
			exit;
		}
		self::preparation();
		Core_Users::getInstance()->setWebUser();
		// href="/fs.php?get=123123"
		$objF=Core_Media;
		if ( !$objF->m_download_byname( $_arr['name'] ) ) {
			header( 'HTTP/1.0 404 Not Found' );
		}
		exit;
	}

	/**
	 * вызов скрипта из коммандной строки
	 *
	 * @return void
	 */
	public static function shell() {
		self::$_isShell=true; // для использования в местах где требуется user_id например
		self::preparation();
		Core_Users::getInstance()->setZero(); // по умолчанию скрипты у нас выполняются от нулевого пользователя
	}

	/**
	 * только подключение библиотек
	 *
	 * @return void
	 */
	public static function minimal() {
		require_once './library/Zend/Config.php';
		$_config=new Zend_Config( require 'config.php' );
		self::enableXdebug( $_config );
		self::enableAutoloader( $_config );
		Zend_Registry::set( 'config', $_config );
		self::initCacheObjects();
	}

	/**
	 * общая часть процесса инициализации движка для всех способов запуска
	 *
	 * @return void
	 */
	private static function preparation() {
		require_once './library/Zend/Config.php';
		$_config=new Zend_Config( require 'config.php' );
		self::enableXdebug( $_config );
		self::enableAutoloader( $_config );
		Zend_Registry::set( 'config', $_config );
		Core_Datetime::setServerTimezone();
		new Core_Errors();
		Core_i18n::getInstance();
		self::initCacheObjects();
		register_shutdown_function( 'Core_Sql::disconnect' ); // disconnect from db
		//register_shutdown_function( 'sql_report' ); // надо приделать нормальный логгер к системе TODO !!! 24.02.2010
		if ( !self::$_isShell ) {
			Zend_Session::start( array( 'name'=>(Core_Module_Router::getInstance()->isBackend? 'adm':'sid') ) );
		}
	}

	private static function initCacheObjects() {
		// т.к. у нас кодировка массива в json формат происходит самописными функциями (у стандартных криво реализована работа
		// c запрещёнными символами - разбивается результирующий json), это дело очень тормозит если встречается в коде несколько раз
		if( !is_dir( Zend_Registry::get( 'config' )->path->relative->cache ) ){
			mkdir( Zend_Registry::get( 'config' )->path->relative->cache );
		}
		Zend_Registry::set( 'CachedCoreString', Zend_Cache::factory(
			'Class', 'File',
			array( 'cached_entity'=>'Core_String', 'cached_methods'=>array( 'php2json' ), 'lifetime'=>NULL ),
			array( 'cache_dir'=>Zend_Registry::get( 'config' )->path->relative->cache )
		) );
		// генерация дерева сайта
		if ( !self::$_isShell ) {
			// чегото не генерит дерево так TODO!!! 24.01.2012
			/*Zend_Registry::set( 'CachedCoreModuleManagementPages', Zend_Cache::factory(
				'Class', 'File',
				array( 'cached_entity'=>'Core_Module_Management_Pages', 'cached_methods'=>array( 'makeTree' ), 'lifetime'=>NULL ),
				array( 'cache_dir'=>Zend_Registry::get( 'config' )->path->relative->cache )
			) );*/
		}
	}

	/**
	 * инициализация автолодера файлов движка.
	 * в данное время используется Zend_Loader_Autoloader
	 *
	 * @param object &$_config - Zend_Config
	 * @return void
	 */
	private static function enableAutoloader( &$_config ) {
		set_include_path( implode( PATH_SEPARATOR, array( $_config->path->relative->library, get_include_path() ) ) );
		require_once $_config->path->relative->zend.'Loader'.DIRECTORY_SEPARATOR.'Autoloader.php'; // zend loader
		$autoloader=Zend_Loader_Autoloader::getInstance();
		$autoloader->registerNamespace( 'Core' );
		$autoloader->registerNamespace( 'Project' );
	}

	/**
	 * инициализация Xdebug. подробности на http://www.xdebug.org
	 *
	 * @param object &$_config - Zend_Config
	 * @return void
	 */
	private static function enableXdebug( &$_config ) {
		if ( !function_exists( 'xdebug_enable' )||!$_config->debugging->xdebug_enable ) {
			return;
		}
		ini_set('xdebug.var_display_max_data', '50120');
		ini_set('xdebug.collect_includes', '0');
		ini_set('xdebug.collect_params', '2');
		ini_set('xdebug.show_mem_delta', '1');
		ini_set('xdebug.show_exception_trace', '1');
		ini_set('xdebug.var_display_max_depth', '20');
		ini_set('xdebug.trace_format', '9');
		ini_set('xdebug.auto_trace', '1');
	}
}


/**
 * Мини дебаггер - просмотр любых данных
 */
function p($mix) {
	while( @ob_end_clean() );
	header( 'Content-Type: text/html; charset="'.Zend_Registry::get( 'config' )->database->codepage.'"');
	if ( function_exists( 'xdebug_var_dump' )&&Zend_Registry::get( 'config' )->debugging->xdebug_enable ) {
		xdebug_var_dump( $mix );
	} else {
		echo '<div align="left"><hr><pre>';
		if ( is_bool( $mix ) )
			var_dump( $mix );
		elseif ( is_array( $mix )||is_object( $mix ) )
			print_r( $mix );
		else
			echo $mix;
		echo '</pre><hr></div>';
	}
	exit;
}
?>