<?php


/**
 * Project_Syndication_Sites interface
 */
interface Project_Syndication_Sites_Interface {

	public function setData( &$arrPlan, &$arrContent );

	// запуск обновления сайта
	public function run();

}
?>