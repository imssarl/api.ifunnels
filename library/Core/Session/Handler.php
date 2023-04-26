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
 * session_interface
 */
require_once 'Core/Session/Interface.php';


/**
 * Session Handler
 * @category framework
 * @package SessionManager
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2008, Rodion Konnov
 * @author Rodion Konnov <kindzadza@mail.ru>
 * @date 14.02.2008
 * @version 0.1
 */


class Core_Session_Handler extends Core_Services implements Core_Session_Interface {
	protected $tables=array(
		'u_session'=>array( 'session_id', 'user_id', 'added', 'updated', 'session_data' ),
	);
	protected $life_time='7200'; // 60*60*2
	public $session=array();

	public function __construct() {
		$_strLifeTime=get_cfg_var( "session.gc_maxlifetime" );
		if ( is_string( $_strLifeTime ) ) {
			$this->life_time=$_strLifeTime;
		}
	}

	// open the session.
	public function open( $save_path, $session_name ) {
		$_obj=Core_Sql::getInstance(); // инициализируем возможно первый раз будет fix_injection в read
		return true;
	}

	// close the session.
	public function close() {
		if ( empty( $this->session ) ) {
			return false;
		}
		//return $this->gc( $this->life_time );
	}

	// read any data for this session.
	public function read( $session_id ) {
		$_arrDta=Core_Sql::getRecord( 'SELECT * FROM u_session WHERE session_id='.Core_Sql::fixInjection( $session_id ) );
		if ( empty( $_arrDta['session_id'] ) ) {
			return '';
		}
		$this->session=$_arrDta;
		$this->session['session_data']='';
		return $_arrDta['session_data'];
	}

	// write session data to the database.
	public function write( $session_id, $session_data ) {
		if ( !empty( $this->session['session_id'] ) ) {
			// user is starting a new session with previous data check ip TODO!!!
			if ( $this->session['session_id']!=$session_id ) {
				$this->session=array();
			}
		}
		// new session record
		if ( empty( $this->session['session_id'] ) ) {
			Core_Sql::setInsert( 'u_session', array(
				'session_id'=>$session_id,
				'added'=>time(),
				'updated'=>time(),
				'session_data'=>$session_data
			) );
		// update session record
		} else {
			// переписать с использованием user_core TODO!!!
			if ( !empty( $_SESSION['USER']['id'] ) ) {
				$this->session['user_id']=$_SESSION['USER']['id'];
			}
			$this->session['updated']=time();
			$this->session['session_data']=$session_data;
			Core_Sql::setUpdate( 'u_session', $this->session, 'session_id' );
		}
		return true;
	}

	// destroy the specified session.
	public function destroy( $session_id ) {
		if ( empty( $session_id ) ) {
			return false;
		}
		Core_Sql::setExec( 'DELETE FROM u_session WHERE session_id='.Core_Sql::fixInjection( $session_id ) );
		return true;
	}

	// perform garbage collection.
	public function gc( $_intLifeTime ) {
		Core_Sql::setExec( 'DELETE FROM u_session WHERE updated<"'.(time()-$_intLifeTime).'"' );
		return true;
	}
}
?>