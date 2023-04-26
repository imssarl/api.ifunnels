<?php


/**
 * View interface
 */
interface Core_View_Interface {

	public function setTemplate();
	public function setHash();
	public function parse();
	public function header();
	public function show();
	public function getResult();
}
?>