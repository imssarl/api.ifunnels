<?php


/**
 * Services for others classes which use Core_Module class
 */
interface Core_Language_Interface {

	public function getTable();

	public function getFieldsForTranslate();

	public function getDefaultLang();

	// должен возвращать настроенный объект Core_Language
	public function getLng();

	// взывается в setImplant для подмешивания в результат нужного языка
	public function &getResult();
}
?>