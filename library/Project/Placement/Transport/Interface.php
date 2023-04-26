<?php
/**
 * интерфейс к разным типам транспорта
 *
 * @category Project
 * @package Project_Placement
 * @copyright Copyright (c) 2005-2012, web2innovation
 * @license http://opensource.org/licenses/ MIT License
 */
interface Project_Placement_Transport_Interface {

	/**
	* Получает объект с настройками
	*
	* @param object $object объект с настройками
	* @return void
	*/
	public function __construct( Project_Placement_Transport $object );

	/**
	* Стартует размещение файлов на конечном сервере
	*
	* @return boolean
	*/
	public function place();

	/**
	* Обрывает коннект транспорта при необходимости
	*
	* @return void
	*/
	public function __destruct();
}
?>