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
 * Session Interface
 * @internal необходимый набор методов для работы с сессией
 * @category framework
 * @package SessionManager
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2008, Rodion Konnov
 * @author Rodion Konnov <kindzadza@mail.ru>
 * @date 14.02.2008
 * @version 0.1
 */


interface Core_Session_Interface {
	public function open( $save_path, $session_name );
	public function close();
	public function read( $session_id );
	public function write( $session_id, $session_data );
	public function destroy( $session_id );
	public function gc( $_intLifeTime );
}
?>