<?php


/**
 * Each blog content manager
 */
abstract class Project_Wpress_Content_Abstract {

	protected $table='';
	protected $fields=array();
	protected $errors=array();
	public  $blog; // объект Core_Data

	public $data; // объект Core_Data

	// используем с пользоательских интерфейсов (там важен владелец)
	public function setBlogById( $_int=0 ) {
		if ( empty( $_int ) ) {
			return false;
		}
		$obj=new Project_Wpress();
		// берём блог по id и пренадлежащий текущему пользователю
		if ( !$obj->onlyOne()->withIds( $_int )->getList( $_arr ) ) {
			return false;
		}
		$this->setBlogByObject( new Core_Data( $_arr ) );
		return true;
	}

	// из программных интерфейсов
	public function setBlogByObject( Core_Data $obj ) {
		$this->blog=&$obj;
		$this->blog->setFilter(); // сделаем доступными данные (отфильтровав)
		return $this;
	}

	protected function setTable( $_str='' ) {
		$this->table=$_str;
		return $this;
	}

	protected function setFields( $_arr='' ) {
		$this->fields=$_arr;
		return $this;
	}

	public function setData( $_arrData=array() ) {
		$this->data=new Core_Data( $_arrData );
		return $this;
	}

	public function getData() {
		if ( get_class( $this->data )!='Core_Data' ) {
			return array();
		}
		return $this->data->getFiltered();
	}

	public function getErrors() {
		return $this->errors;
	}

	// настройки для getList
	protected $_byTitle=''; // 
	protected $_onlyPost = false; // только для поста
	protected $_onlyCategory = false; // только для категории
	protected $_withCategories = false; // с категориями
	protected $_onlyIds=false; // массив с ids
	protected $_onlyCount=false; // только количество
	protected $_onlyOne=false; // только одна запись
	protected $_withIds=array(); // c данными id
	protected $_withPagging=array(); // постранично
	protected $_withOrder=''; // c сортировкой
	protected $_paging=array(); // инфа по навигации
	protected $_cashe=array(); // закэшированный фильтр
	protected $_defaultOrder=''; // сортировка по умолчанию
	protected $_all=false; // все записи
	protected $_toJs=false;

	// могут быть разные варианты например tbl.id--dn см. Core_Sql_Qcrawler
	protected function setDefaultOrder( $_str='id--up' ) {
		$this->_withOrder=$this->_defaultOrder=$_str;
		return $this;
	}

	public function toJs(){
		$this->_toJs=true;
		return $this;
	}

	// сброс настроек после выполнения getArticles
	protected function init() {
		$this->_toJs=false;
		$this->_byTitle='';
		$this->_onlyPost=false;
		$this->_onlyCategory=false;
		$this->_withCategories=false;
		$this->_onlyIds=false;
		$this->_onlyCount=false;
		$this->_onlyOne=false;
		$this->_all=false;
		$this->_withIds=array();
		$this->_withPagging=array();
		$this->_withOrder=$this->_defaultOrder;
	}

	public function getAll() {
		$this->_all=true;
		return $this;
	}
	
	public function byTitle( $_str='' ) {
		$this->_byTitle=$_str;
		return $this;
	}
	
	public function withCategory( $_id ) {
		$this->_withCategory = $_id;
		return $this;
	}
	
	public function withCategories() {
		$this->_withCategories = true;
		return $this;
	}
	
	public function onlyPost( $_id ) {
		$this->_onlyPost = $_id;
		return $this;
	}

	public function onlyIds() {
		$this->_onlyIds=true;
		return $this;
	}

	public function onlyCount() {
		$this->_onlyCount=true;
		return $this;
	}

	public function onlyOne() {
		$this->_onlyOne=true;
		return $this;
	}

	public function withIds( $_arrIds=array() ) {
		$this->_withIds=is_array( $_arrIds ) ? $_arrIds:array( $_arrIds );
		return $this;
	}

	public function withPagging( $_arr=array() ) {
		$this->_withPagging=$_arr;
		return $this;
	}

	public function withOrder( $_str='' ) {
		if ( !empty( $_str ) ) {
			$this->_withOrder=$_str;
		}
		$this->_cashe['order']=$this->_withOrder;
		return $this;
	}

	// данные фильтра
	public function getFilter( &$arrRes ) {
		$arrRes=$this->_cashe;
	}

	// данные постранички
	public function getPaging( &$arrRes ) {
		$arrRes=$this->_paging;
		$this->_paging=array();
	}

	// получение элементов +фильтр +сортировка +постраничка по необходимости
	/*
		$_crawler=new Core_Sql_Qcrawler();
		// тут определяем запрос в $_crawler
		$this->init();
		return !empty( $mixRes );
	*/
	abstract public function getList( &$mixRes );

	// добавление/редактирование с формы
	abstract public function set();

	// используется как при импорте так и при управлении
	abstract public function setToDb( Core_Data $obj );

	// удаление из бд
	abstract public function del( $_mixId=array() );

	// берём инфу об одном элементе по id
	abstract public function get( &$arrRes, $_intId=0 );
}
?>