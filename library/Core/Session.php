<?php
/**
 * Session Manager
 * @category framework
 * @package SessionManager
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2008, Rodion Konnov
 * @author Rodion Konnov <kindzadza@mail.ru>
 * @date 14.02.2008
 * @version 0.1
 */


/**
 * Session
 * @category framework
 * @package SessionManager
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2008, Rodion Konnov
 * @author Rodion Konnov <kindzadza@mail.ru>
 * @date 14.02.2008
 * @version 0.1
 */


class Core_Session extends Core_Session_Handler {

	private static $_instance=NULL;

	protected $def_name='sid';

	public function __construct() {
		if ( Zend_Registry::get( 'config' )->engine->session_handler ) {
			parent::__construct();
			$_bool=session_set_save_handler(
				array(&$this, "open"),
				array(&$this, "close"),
				array(&$this, "read"),
				array(&$this, "write"),
				array(&$this, "destroy"),
				array(&$this, "gc")
			);
			register_shutdown_function( 'session_write_close' );
			if ( !$_bool ) {
				trigger_error( ERR_PHP.'|do not set core session handler' );
			}
		}
	}

	public function init( $strName='' ) {
		if ( empty( $strName ) ) {
			session_name( $this->def_name );
		} else {
			session_name( $strName );
		}
		session_start();
	}

	//  implements Core_Singleton_Interface TODO!!!
	public static function getInstance( $strName='' ) {
		if ( self::$_instance==NULL ) {
			self::$_instance=new Core_Session();
			self::$_instance->init( $strName );
		}
		return self::$_instance;
	}
}
?>