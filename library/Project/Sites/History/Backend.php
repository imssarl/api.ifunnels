<?php
class Project_Sites_History_Backend extends Project_Sites_History {

	protected $_withOrder='catedit--up'; // c сортировкой, значение из драйвера

	/**
	* конструктор
	* @return void
	*/
	public function __construct() {}

	// сброс настроек после выполнения getList
	protected function init() {
		$this->_onlyCount=false;
		$this->_withPagging=array();
		$this->_onlyOne=false;
		$this->_onlyPortals=false;
		$this->_withId=array();
		$this->_withType=0;
		$this->_withOrder='catedit--up';
		$this->_withoutCategories=false;
	}

	public function withoutCategories( $_flg=false ) {
		if ( !empty( $_flg ) ) {
			$this->_withoutCategories=true;
		}
		$this->_cashe['without_categories']=$this->_withoutCategories;
		return $this;
	}

	/**
	 * отдельно смена категории
	 * без указания пользователя
	 * и с указанием времени изменения категории
	 *
	 * @param integer $_intSiteId
	 * @param integer $_intCatId id новой категории
	 * @return bool
	 */
	public function changeCategory( $_intSiteId=0, $_intCatId=0, $_intType=0 ) {
		if ( empty( $_intSiteId )||empty( $_intCatId )||empty( $_intType ) ) {
			return false;
		}
		$this->_type=$_intType;
		$this->setDriver();
		Core_Sql::setExec( '
			UPDATE '.$this->_driver->getTable().' 
			SET category_id='.Core_Sql::fixInjection( $_intCatId ).', catedit='.time().'
			WHERE id='.Core_Sql::fixInjection( $_intSiteId ).' LIMIT 1' );
		return true;
	}
}
?>