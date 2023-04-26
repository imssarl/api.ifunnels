<?php


/**
 * необходимые в Core_i18n_Dynamic методы которые должна поддерживать целевая модель
 */
interface Core_i18n_Dynamic_Interface {

	// в режиме редактирования в данные подмешиваем все варианты переводов
	public function editMode();

	public function getTable( $_bool=false );

	public function getFieldsForTranslate();

	public function getDefaultLang();

	// должен возвращать настроенный объект Core_i18n_Dynamic
	public function getLng();
}
?>