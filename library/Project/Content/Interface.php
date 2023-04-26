<?php


/**
 * интерфейс коннектора к разным типам данных
 */
interface Project_Content_Interface {

	/**
	* Выбирает список контента
	* id, title, text присутствует в данных в любом случае
	*
	* @param mixed $mixRes отдаёт в виде array( array( id, title, text ), array )
	* @return object
	*/
	public function getList( &$mixRes );

	/**
	* Подсобный метод для формирования запроса
	* $_obj->withIds( $_arrIds )->getContent( $mixRes )
	*
	* @param array $_arrIds - ids нужного контента
	* @return object
	*/
	public function withIds( $_arrIds=array() );

	/**
	* В случаях когда надо получить контент без
	* учёта принадлежности какому-либо пользователю
	*
	* @return object
	*/
	public static function getInstance();

	/**
	* Фильтр для списка контента
	* $_obj->setFilter( $_GET['arrFlt'] )->getList( $mixRes )
	*
	* @param array $_arrFilter - поля и значения фильтра
	* @return object
	*/
	public function setFilter( $_arrFilter=array() );

	/**
	* Получение массива для генерации постраничной навигации
	*
	* @param array $arrRes
	* @return object
	*/
	public function getPaging( &$arrRes ) ;

	/**
	* Ранее установленный фильтр для использования в шаблоне
	*
	* @param array $arrRes
	* @return object
	*/
	public function getFilter( &$arrRes ) ;

	/**
	 * Сколько контента вернуть
	 *
	 * @param  $_intLimit
	 * @return object
	 */
	public function setLimited( $_intLimit );

	/**
	 * Счетчик контента запощеного в проект от начала. Используется для внешних источников, те которые не име
	 *
	 * @param  $_intCounter
	 * @return object
	 */
	public function setCounter( $_intCounter );

	/**
	 * Дополнительные данные для генерации формы на шаблоне адаптера
	 * Нужно быть остарожным чтобы не обнулить массив который выкидывается на шаблон
	 *
	 * @param array $arrRes
	 * @return object
	 */
	public function getAdditional( &$arrRes );

	/**
	 * Сеттер для $_POST
	 *
	 * @param array $_arrPost
	 * @return object
	 */
	public function setPost( $_arrPost=array() );

	/**
	 * Сеттер для $_FILES
	 *
	 * @param array $_arrFile
	 * @return object
	 */
	public function setFile( $_arrFile=array() );

	/**
	 * Результат работы второго шага выбора данных
	 *
	 * @param array $arrRes
	 * @return boolean
	 */
	public function getResult( &$arrRes );

	/**
	 * ...->getList( $arrList )->checkEmpty()
	 * в Core_Storage возвращается например переменная $this->_isNotEmpty, которая определяется в getList
	 *
	 * @return boolean - !empty - true empty - false
	 */
	public function checkEmpty();

	/**
	 *	Set settings for content
	 *
	 * @param  $arrSettings
	 * @return object
	 */
	public function setSettings( $arrSettings );

}
?>