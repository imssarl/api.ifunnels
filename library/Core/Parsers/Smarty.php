<?php
/**
 * Parsers
 * @category framework
 * @package Parsers
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2009, Rodion Konnov
 * @author Rodion Konnov <kindzadza@mail.ru>
 * @date 09.04.2009
 * @version 2.0
 */


/**
 * Html-parser driver
 * smarty only
 * @category framework
 * @package Parsers
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2009, Rodion Konnov
 * @author Rodion Konnov <kindzadza@mail.ru>
 * @date 09.04.2009
 * @version 3.0
 */


class Core_Parsers_Smarty implements Core_Singleton_Interface {

	private static $_instance=NULL;
	public $config;
	private $_smarty;
	private $_disable=false;

	public function __construct() {
		$this->init();
	}

	public static function getInstance() {
		if ( self::$_instance==NULL ) {
			self::$_instance=new Core_Parsers_Smarty();
		}
		return self::$_instance;
	}

	public function disableDebug() {
		$this->_disable=true;
	}

	private function init() {
		$this->config=Zend_Registry::get( 'config' );
		if ( !file_exists( $this->config->path->relative->smarty.'Smarty.class.php' ) ) {
			trigger_error( $this->config->debugging->error_type->err_smarty.'|Smarty lib not found.' );
			return false;
		}
		require_once $this->config->path->relative->smarty.'Smarty.class.php';
		$this->_smarty=new Smarty();
		return true;
	}

	private function debug() {
		if ( $this->_disable ) {
			$this->_smarty->force_compile=true; // переисываем кэш
			Zend_Registry::set( 'debug_in_smarty', false );
			return;
		}
		Zend_Registry::set( 'debug_in_smarty', $this->config->debugging->show_tpl_path );
		Zend_Registry::set( 'debug_in_smarty_show_hash', $this->config->debugging->show_tpl_hash );
	}

	public function template( &$strRes, $_arrOut=array(), $_strTpl='' ) {
		if ( empty( $_arrOut )||empty( $_strTpl ) ) {
			return false;
		}
		$_arrTpl=pathinfo( $_strTpl );
		if ( !file_exists( $_strTpl ) ) {
			trigger_error( $this->config->debugging->error_type->err_filesys.'|"'.$_arrTpl['basename'].'" template file not found' );
			return false;
		}
		//$this->debug();
		//p( $this->_smarty );
		$this->_smarty->compile_dir=$this->config->path->relative->compiled;
		$this->_smarty->template_dir=$_arrTpl['dirname'].DIRECTORY_SEPARATOR;
		foreach ( $_arrOut as $k=>$v ) {
			$this->_smarty->assignByRef( $k, $_arrOut[$k] );
		}
		$strRes=trim( $this->_smarty->fetch( $_arrTpl['basename'] ) ); // коварные пробелы в начале документа (stupid ie fix)
		$this->_smarty->clearAllAssign();
		$this->_force=false;
		return true;
	}

	public function memory( &$strRes, $_arrOut=array(), $_strTpl='' ) {
		if ( empty( $_arrOut )||empty( $_strTpl ) ) {
			return false;
		}
		$this->_smarty->force_compile=true;
		$this->_smarty->compile_dir=$this->config->path->relative->compiled;
		$this->_smarty->register_resource( 'mem:', array( 
			'smarty_resource_mem_source', 
			'smarty_resource_mem_timestamp', 
			'smarty_resource_mem_secure', 
			'smarty_resource_mem_trusted' ) );
		$this->_smarty->resource['mem']['tpl']=$_strTpl;
		foreach ( $_arrOut as $k=>$v ) {
			$this->_smarty->assign_by_ref( $k, $_arrOut[$k] );
		}
		$strRes=$this->_smarty->fetch( 'mem:tpl' );
		$this->_smarty->clear_all_assign();
		return true;
	}
}
?>