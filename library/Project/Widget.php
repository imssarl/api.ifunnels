<?php

class Project_Widget{

	private $_settings=array();

	public function __construct(){}

	public function setSettings( $_arr ){
		$this->_settings=$_arr;
		return $this;
	}

	/**
	 * Start http services
	 * @return bool
	 */
	public function run(){
		if( empty($this->_settings['name'])||empty($this->_settings['action']) ){
			return false;
		}
		$_class='Project_Widget_Adapter_'.$this->_settings['name'];
		if( !class_exists($_class) ){
			return false;
		}
		$_driver=new $_class();
		$_action=$this->_settings['action'];
		if( !method_exists($_driver,$_action) ){
			return false;
		}
		if( !$_driver->checkKey( $this->_settings['key'] ) ){
			return false;
		}
		$_driver->setSettings( $this->_settings )->$_action();
	}
}
?>