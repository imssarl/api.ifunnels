<?php


/**
 * Services for others classes which use Core_Module class
 */
interface Core_Module_Interface {

	public function getUniqueId(); // для Core_Module_Router

	public function getModuleName(); // для Core_Module_Router и для Core_Module_Location

	public function getModuleAction( &$strRes ); // для Core_Module_Location

	public function getViewMode( &$strRes ); // для Core_Module_Location

	public function childFactory( $_arr=array() ); // для Project_Module

	public function before_run_parent(); // для конечного модуля лучше что-то вроде childBeforeRunAspect()

	public function after_run_parent(); // для конечного модуля лучше что-то вроде childAfterRunAspect()

	public function set_cfg(); // для конечного модуля тогда уже setCfg() ))
}
?>