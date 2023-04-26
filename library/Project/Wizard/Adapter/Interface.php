<?php

interface Project_Wizard_Adapter_Interface {

	/**
	 * Проверка доступности визарда.
	 * @abstract
	 * @return boolean
	 */
	public function check();

	/**
	 * Устанавливает входные данные.
	 * @abstract
	 * @param Core_Data $data
	 * @return mixed
	 */
	public function setEntered( Core_Data $data );

	/**
	 * Запускает процесс создания.
	 * @abstract
	 * @return boolean
	 */
	public function run();
}
?>