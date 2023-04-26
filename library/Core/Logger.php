<?php
/**
 * Auxiliary classes
 * @category framework
 * @package Auxiliary
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2008, Rodion Konnov
 * @author Rodion Konnov <kindzadza@mail.ru>
 * @date 20.11.2008
 * @version 1.0
 */


/**
 * Process Logger
 * @internal Позволяет вести лог, сограняет в DIR_LOGFILES
 * @category framework
 * @package Auxiliary
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2008, Rodion Konnov
 * @author Rodion Konnov <kindzadza@mail.ru>
 * @date 25.08.2008
 * @version 1.0
 */


class Core_Logger extends Core_Media_Driver {
	private $file_name='';
	private $log_strings=array();

	public function __construct( $obj ) {
		if ( !is_object( $obj ) ) {
			trigger_error('todo');
		}
		$this->file_name=get_class( $obj ).'_'.date('Y_m_d', time()).(empty($obj->pid)?'':'_'.$obj->pid).'.log';
		$this->accumulation( "-----------------------------------------------" );
		$this->accumulation( "start logging at ".date("F j, Y, G:i", time()) );
		$this->accumulation( "-----------------------------------------------" );
	}

	public function accumulation( $_str='' ) {
		if ( empty( $_str ) ) {
			return;
		}
		$this->log_strings[]=date("F j, Y, G:i", time()).': '.$_str;
	}

	public function flush_log() {
		$this->accumulation( "-----------------------------------------------" );
		$this->accumulation( "flush logged data at ".date("F j, Y, G:i", time()) );
		$this->accumulation( "-----------------------------------------------" );
		if ( !$this->d_addtofile( DIR_LOGFILES, $this->file_name, join( "\n", $this->log_strings )."\n" ) ) {
			trigger_error('todo');
			return false;
		}
		$this->log_strings=array();
		return true;
	}
}
?>