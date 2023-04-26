<?php

interface Project_Widget_Adapter_Interface {

	public function get();

	public function set();

	/**
	 * Проверка ключа доступа к сервису.
	 * @abstract
	 * @param  $strKey
	 * @return void
	 */
	public function checkKey( $_strKey );

	/**
	 * Установка свойств для адаптера
	 * @abstract
	 * @param  $arrSettings
	 * @return void
	 */
	public function setSettings( $_arrSettings );
}
?>