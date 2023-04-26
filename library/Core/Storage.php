<?php
// сохранение и выдача данных на редактирование, а так-же списки данных с фильтрами и сортировками
class Core_Storage {

	public $object;

	public $fields=array();
	public $table='';

	protected $_errors=array();

	protected $_link='dataset_link';
	protected $_data;
	protected $_crawler;

	public function __construct( Core_Dataset_Object $_obj ) {
		$this->object=$_obj;
		$this->initDataset();
	}

	private function initDataset() {
		if ( !$this->object->checkSetExists()||!$this->object->checkWithStorage() ) {
			return false;
		}
		$_obj=new Core_Storage_Generate( $this->object );
		if ( !$_obj->checkDatasetTable() ) {
			return false;
		}
		$this->table=$this->object->getTable();
		$this->fields=array_flip( $this->object->getTableFieldsFull() );
	}

	// в случае ненужности $this->object можно переопределить
	public function getOwnerId() {
		return $this->object->getOwnerId();
	}

	public function del( $_arr=array() ) {
		if ( empty( $_arr ) ) {
			return false;
		}
		if ( empty( $this->_link ) ) {
			$strSql='
				DELETE d
				FROM '.$this->table.' d
				WHERE d.id IN('.Core_Sql::fixInjection( $_arr ).')
			';
		} else {
			$strSql='
				DELETE d, l 
				FROM '.$this->table.' d
				LEFT JOIN '.$this->_link.' l ON l.internal_id=d.id
				WHERE d.id IN('.Core_Sql::fixInjection( $_arr ).')
			';
		}
		if ( $this->getOwnerId() ) {
			$strSql.=' AND d.user_id='.$this->getOwnerId();
		}
		Core_Sql::setExec( $strSql );
		return true;
	}

	public function setData( $_arrData=array() ) {
		$this->_data=new Core_Data( $_arrData );
		return $this;
	}

	public function getData() {
		return $this->_data;
	}

	public function set() {
		if ( !$this->object->validate( $this->_data ) ) {
			$this->object->getErrors( $this->_errors );
			return false;
		}
		if ( empty( $this->_data->filtered['id'] ) ) {
			$this->_data->filtered['added']=time();
			$this->_data->setElement( 'user_id', $this->getOwnerId() );
		} else {
			$this->_data->filtered['edited']=time();
		}
		$this->_data->setElement( 'id', Core_Sql::setInsertUpdate( $this->table, $this->_data->setMask( $this->fields )->getValid() ) );
		return true;
	}

	// дублирование строк
	public function duplicate( $_intId=0 ) {
		if ( empty( $_intId )||!$this->onlyOne()->withIds( $_intId )->getList( $arrRes ) ) {
			return false;
		}
		unSet( $arrRes['id'] );
		$this->changeSomeFields( $arrRes );
		return $this->setData( $arrRes )->set();
	}

	// этот метод может быть переназначен для изменения полей при авто копировании строк
	public function changeSomeFields( &$arrRes ) {}

	public function getEntered( &$arrRes ) {
		if ( is_object( $this->_data ) ) {
			$arrRes=$this->_data->getFiltered();
		}
		return $this;
	}

	public function getErrors( &$arrRes ) {
		$arrRes=$this->_errors;
		if( empty($this->_errors) ){
			$arrRes=Core_Data_Errors::getInstance()->getErrors();
		}
		return $this;
	}
	
	public function setError( $_strError='' ) {
		$this->_errors[]=$_strError;
		return false;
	}

	// настройки для getList, protected сделано в основном для того чтобы была возможность переопределить assemblyQuery
	protected $_toSelect=false; // массив пар значений id=>title (название поля для названия всегда title - возможно нужна возможность указать его TODO!!! 17.01.2011)
	protected $_onlyIds=false; // массив с ids
	protected $_onlyCell=false; // только одна ячейка
	protected $_onlyCount=false; // только количество
	protected $_onlyOne=false; // только одна запись
	protected $_onlyOwner=false; // записи принадлежащие текущему пользователю
	protected $_keyRecordForm=false; // первая колонка будет индексом в полученном результате
	protected $_withIds=array(); // c данными id
	protected $_withPaging=array(); // постранично
	protected $_withOrder='d.id--up'; // c сортировкой
	protected $_paging=array(); // инфа по навигации
	protected $_cashe=array(); // закэшированный фильтр
	protected $_isNotEmpty=false; // для проверки результата выборки (по умолчанию выборка пуста) отражает результаты последнего getList

	// сброс настроек после выполнения getArticles
	protected function init() {
		$this->_toSelect=false;
		$this->_onlyIds=false;
		$this->_onlyCell=false;
		$this->_onlyCount=false;
		$this->_onlyOne=false;
		$this->_onlyOwner=false;
		$this->_keyRecordForm=false;
		$this->_withIds=array();
		$this->_withPaging=array();
		$this->_withOrder='d.id--up';
	}

	public function get( &$arrRes, $_intId=0 ) {
		if ( empty( $_intId ) ) {
			return false;
		}
		$this
			->onlyOne()
			->withIds( $_intId )
			->getList( $arrRes );
		return !empty( $arrRes );
	}

	public function onlyCell() {
		$this->_onlyCell=true;
		return $this;
	}

	public function onlyCount() {
		$this->_onlyCount=true;
		return $this;
	}

	public function onlyIds() {
		$this->_onlyIds=true;
		return $this;
	}

	public function onlyOne() {
		$this->_onlyOne=true;
		return $this;
	}

	public function onlyOwner() {
		$this->_onlyOwner=true;
		return $this;
	}

	public function keyRecordForm() {
		$this->_keyRecordForm=true;
		return $this;
	}

	public function withIds( $_arrIds=array() ) {
		$this->_withIds=$_arrIds;
		return $this;
	}

	// 'first_field, second_field' or array( 'first_field', 'second_field' ) are similar
	public function withGroup( $_mix=array() ) {
		$this->_withGroup=$_mix;
		return $this;
	}

	public function withPaging( $_arr=array() ) {
		$this->_withPaging=$_arr;
		return $this;
	}

	public function withOrder( $_str='' ) {
		if ( !empty( $_str ) ) {
			$this->_withOrder=$_str;
		}
		$this->_cashe['order']=$this->_withOrder;
		return $this;
	}

	public function toSelect() {
		$this->_toSelect=true;
		return $this;
	}

	protected function assemblyQuery() {
		if ( $this->_onlyIds ) {
			$this->_crawler->set_select( 'd.id' );
		} elseif ( $this->_toSelect ) {
			$this->_crawler->set_select( 'd.id, d.title' );
		} else {
			$this->_crawler->set_select( 'd.*' );
		}
		$this->_crawler->set_from( $this->table.' d' );
		if ( !empty( $this->_withIds ) ) {
			$this->_crawler->set_where( 'd.id IN ('.Core_Sql::fixInjection( $this->_withIds ).')' );
		}
		if ( $this->_onlyOwner ) {
			$this->_crawler->set_where( 'd.user_id='.$this->getOwnerId() );
		}
		if ( !( $this->_onlyOne||$this->_onlyCell ) ) {
			$this->_crawler->set_order_sort( $this->_withOrder );
		}
		if ( !empty( $this->withGroup ) ) {
			$this->_crawler->set_group( $this->withGroup );
		}
	}

	public function getList( &$mixRes ) {
		$this->_crawler=new Core_Sql_Qcrawler();
		$this->assemblyQuery();
		if ( !empty( $this->_withPaging ) ) {
			$this->_withPaging['rowtotal']=Core_Sql::getCell( $this->_crawler->get_result_counter( $_strTmp ) );
			$this->_crawler->set_paging( $this->_withPaging )->get_sql( $_strSql, $this->_paging );
		} elseif ( !$this->_onlyCount ) {
			$this->_crawler->get_result_full( $_strSql );
		}
		if ( $this->_onlyCell ) {
			$mixRes=Core_Sql::getCell( $_strSql );
		} elseif ( $this->_onlyIds ) {
			$mixRes=Core_Sql::getField( $_strSql );
		} elseif ( $this->_onlyCount ) {
			$mixRes=Core_Sql::getCell( $this->_crawler->get_result_counter() );
		} elseif ( $this->_onlyOne ) {
			$mixRes=Core_Sql::getRecord( $_strSql );
		} elseif ( $this->_toSelect ) {
			$mixRes=Core_Sql::getKeyVal( $_strSql );
		} elseif ( $this->_keyRecordForm ) {
			$mixRes=Core_Sql::getKeyRecord( $_strSql );
		} else {
			$mixRes=Core_Sql::getAssoc( $_strSql );
		}
		$this->_isNotEmpty=!empty( $mixRes );
		$this->init();
		return $this;
	}

	// ...->getList( $arrList )->checkEmpty()
	// !empty - true empty - false
	public function checkEmpty() {
		return $this->_isNotEmpty;
	}

	public function getFilter( &$arrRes ) {
		$arrRes=$this->_cashe;
		return $this;
	}

	public function getPaging( &$arrRes ) {
		$arrRes=$this->_paging;
		$this->_paging=array();
		return $this;
	}
}
?>