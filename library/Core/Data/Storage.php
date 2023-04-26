<?php
class Core_Data_Storage {

	/**
	 * имя таблицы в которую сохраняются данные
	 *
	 * @var string
	 */
	protected $_table='';

	/**
	 * список полей таблицы, лишние поля в данных будут удалены при сохранении
	 *
	 * @var array
	 */
	protected $_fields=array();

	/**
	 * список ошибок
	 *
	 * @var array
	 */
	protected $_errors=array();

	/**
	 * объект Core_Data
	 *
	 * @var Core_Data object
	 */
	protected $_data;

	/**
	 * объект Core_Sql_Qcrawler
	 *
	 * @var Core_Sql_Qcrawler object
	 */
	protected $_crawler;

	public function getErrors( &$arrRes ){
		$arrRes=$this->_errors;
		if( empty($this->_errors) ){
			$arrRes=Core_Data_Errors::getInstance()->getErrors();
		}
		return $this;
	}
	
	public function setError( $_strError='' ){
		$this->_errors[]=$_strError;
		return false;
	}

	/**
	 * возвращает id пользователя из objUser
	 * обычно это текущий залогиненый пользователь
	 * также для объекта objUser можно назначить нужного пользователя
	 *
	 * @param integer $intRes out
	 * @return boolean
	 */
	private function getOwnerId( &$intRes ){
		if ( !empty( $this->_data->filtered['user_id'] ) ){
			$intRes=$this->_data->filtered['user_id'];
			return false; // переустанавливать user_id ненадо он уже указан
		}
		if ( !( Zend_Registry::isRegistered( 'objUser' )||in_array( 'user_id', $this->_fields ) ) ){
			return false;
		}
		Zend_Registry::get( 'objUser' )->getId( $intRes );
		return !empty( $intRes );
	}

	/**
	 * отдаёт Core_Data объект
	 *
	 * @return object
	 */
	public function getDataObject(){
		return $this->_data;
	}

	/**
	 * создаёт объект Core_Data по введённым данным
	 *
	 * @param array $_arr in - массив данных из вне
	 * @return object
	 */
	public function setEntered( $_mix=array() ){
		$this->_data=is_object( $_mix )? $_mix:new Core_Data( $_mix );
		return $this;
	}

	/**
	 * отдаёт отфильтрованные введённые данные
	 *
	 * @param array $arrRes out
	 * @return object
	 */
	public function getEntered( &$arrRes ){
		if ( is_object( $this->_data ) ){
			$arrRes=$this->_data->getFiltered();
		}
		return $this;
	}

	/**
	 * удаление одной или нескольких записей
	 *
	 * @return boolean
	 */
	public function del(){
		if ( empty( $this->_withIds ) ){
			$_bool=false;
		} else {
			Core_Sql::setExec( 'DELETE FROM '.$this->_table.' 
				WHERE id IN('.Core_Sql::fixInjection( $this->_withIds ).')'.($this->_onlyOwner&&$this->getOwnerId( $_intId )? ' AND user_id='.$_intId:'') );
			$_bool=true;
		}
		$this->init();
		return $_bool;
	}

	/**
	 * аспект кторый вызывается до выполнения set()
	 * после переназначения тут например можно организовать проверку полей
	 *
	 * @return boolean
	 */
	protected function beforeSet(){
		return true;
	}

	/**
	 * аспект кторый вызывается после выполнения set()
	 * после переназначения тут например можно сделать какие-либо действия после сохранения данных
	 *
	 * @return boolean
	 */
	protected function afterSet(){
		return true;
	}

	/**
	 * сохренение данных в таблицу (должны присутствовать поля added, edited и user_id если есть владелец)
	 *
	 * @return boolean
	 */
	public function set(){
		if ( !$this->beforeSet() ){
			return false;
		}
		$this->_data->setElement( 'edited', time() );
		if ( empty( $this->_data->filtered['id'] ) ){
			$this->_data->setElement( 'added', $this->_data->filtered['edited'] );
			if ( $this->getOwnerId( $_intId ) ){
				$this->_data->setElement( 'user_id', $_intId );
			}
		}
		$this->_data->setElement( 'id', Core_Sql::setInsertUpdate( $this->_table, $this->_data->setMask( $this->_fields )->getValid() ) );
		return $this->afterSet();
	}

	/**
	 * аспект кторый вызывается до выполнения каждого set() в setMass()
	 * после переназначения тут например можно организовать накапливание данных для пост обработки в afterSetMass()
	 *
	 * @return boolean
	 */
	protected function beforeSetMass( $k, $_arrRow=array() ){
		return true;
	}

	/**
	 * аспект кторый вызывается после выполнения всех set() в setMass()
	 * после переназначения тут например можно сделать какие-либо действия с данными накопленными в beforeSetMass()
	 *
	 * @return boolean
	 */
	protected function afterSetMass(){
		return true;
	}

	/**
	 * массовое редактирование данных + добавление нового элемента
	 * для каждой строки данных обработка идёт через текущий метод set()
	 * как несклько более мелких форм
	 *
	 * @return boolean
	 */
	public function setMass(){
		$this->_data->setFilter();
		if ( empty( $this->_data->filtered ) ){
			return true;
		}
		// чтобы нулевой элемент оказался в конце. сначала проверяем старые данные
		$this->_data->filtered=array_reverse( $this->_data->filtered, true );
		foreach( $this->_data->filtered as $k=>$v ){
			if ( !$this->beforeSetMass( $k, $v ) ){
				continue;
			}
			// если были ошибки в старых данных новый элемент не обрабатываем для избежания коллизий
			if ( $k===0&&!empty( $this->_errors ) ){
				break;
			}
			$_strClass=get_class( $this );
			$_storage=new $_strClass();
			if ( !$_storage->setEntered( $v )->set() ){
				$_storage->getErrors( $this->_errors[$k] );
				continue;
			}
			// для нового элемента добавляем в данные его id
			if ( empty( $this->_data->filtered[$k]['id'] ) ){
				$_storage->getEntered( $_arrRow );
				$this->_data->filtered[$k]['id']=$_arrRow['id'];
			}
		}
		// возвращаем элементам первоначальный порядок
		$this->_data->filtered=array_reverse( $this->_data->filtered, true );
		if ( !empty( $this->_errors ) ){
			return false;
		}
		return $this->afterSetMass();
	}

	/**
	 * дублирование строк в таблице
	 *
	 * @return boolean
	 */
	public function duplicate( $_intId=0 ){
		if ( empty( $_intId )||!$this->onlyOne()->withIds( $_intId )->getList( $arrRes ) ){
			return false;
		}
		unSet( $arrRes['id'] );
		$this->changeFields( $arrRes );
		return $this->setEntered( $arrRes )->set();
	}

	/**
	 * этот метод может быть переназначен для изменения полей при авто копировании строк
	 *
	 * @return void
	 */
	public function changeFields( &$arrRes ){}

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

	// сброс настроек после выполнения getList
	protected function init(){
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

	// ...->withIds( $_intId )->get( $arrRes )
	public function get( &$arrRes ){
		return $this
			->onlyOne()
			->getList( $arrRes )
			->checkEmpty();
	}

	public function onlyCell(){
		$this->_onlyCell=true;
		return $this;
	}

	public function onlyCount(){
		$this->_onlyCount=true;
		return $this;
	}

	public function onlyIds(){
		$this->_onlyIds=true;
		return $this;
	}

	public function onlyOne(){
		$this->_onlyOne=true;
		return $this;
	}

	/**
	 * очень важный метод, от которого будет зависить учитывается ли пользователь
	 * при выборке или удалении записи
	 *
	 * @return object
	 */
	public function onlyOwner(){
		$this->_onlyOwner=true;
		return $this;
	}

	public function keyRecordForm(){
		$this->_keyRecordForm=true;
		return $this;
	}

	public function withIds( $_arrIds=array() ){
		$this->_withIds=$_arrIds;
		return $this;
	}

	// 'first_field, second_field' or array( 'first_field', 'second_field' ) are similar
	public function withGroup( $_mix=array() ){
		$this->_withGroup=$_mix;
		return $this;
	}

	public function withPaging( $_arr=array() ){
		$this->_withPaging=$_arr;
		return $this;
	}

	public function withOrder( $_str='' ){
		if ( !empty( $_str ) ){
			$this->_withOrder=$_str;
		}
		$this->_cashe['order']=$this->_withOrder;
		return $this;
	}

	public function toSelect(){
		$this->_toSelect=true;
		return $this;
	}

	protected function assemblyQuery(){
		if ( $this->_onlyIds ){
			$this->_crawler->set_select( 'd.id' );
		} elseif ( $this->_toSelect ){
			$this->_crawler->set_select( 'd.id, d.title' );
		} else {
			$this->_crawler->set_select( 'd.*' );
		}
		$this->_crawler->set_from( $this->_table.' d' );
		if ( !empty( $this->_withIds ) ){
			$this->_crawler->set_where( 'd.id IN ('.Core_Sql::fixInjection( $this->_withIds ).')' );
		}
		if ( $this->_onlyOwner&&$this->getOwnerId( $_intId ) ){
			$this->_crawler->set_where( 'd.user_id='.$_intId );
		}
		if ( !( $this->_onlyOne||$this->_onlyCell ) ){
			$this->_crawler->set_order_sort( $this->_withOrder );
		}
		if ( !empty( $this->_withGroup ) ){
			$this->_crawler->set_group( $this->_withGroup );
		}
	}

	public function getList( &$mixRes ){
		$this->_crawler=new Core_Sql_Qcrawler();
		$this->assemblyQuery();
		if ( !empty( $this->_withPaging ) ){
			$this->_withPaging['rowtotal']=Core_Sql::getCell( $this->_crawler->get_result_counter( $_strTmp ) );
			$this->_crawler->set_paging( $this->_withPaging )->get_sql( $_strSql, $this->_paging );
		} elseif ( !$this->_onlyCount ){
			$this->_crawler->get_result_full( $_strSql );
		}
		if ( $this->_onlyCell ){
			$mixRes=Core_Sql::getCell( $_strSql );
		} elseif ( $this->_onlyIds ){
			$mixRes=Core_Sql::getField( $_strSql );
		} elseif ( $this->_onlyCount ){
			$mixRes=Core_Sql::getCell( $this->_crawler->get_result_counter() );
		} elseif ( $this->_onlyOne ){
			$mixRes=Core_Sql::getRecord( $_strSql );
		} elseif ( $this->_toSelect ){
			$mixRes=Core_Sql::getKeyVal( $_strSql );
		} elseif ( $this->_keyRecordForm ){
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
	public function checkEmpty(){
		return $this->_isNotEmpty;
	}

	public function getFilter( &$arrRes ){
		$arrRes=$this->_cashe;
		return $this;
	}

	public function getPaging( &$arrRes ){
		$arrRes=$this->_paging;
		$this->_paging=array();
		return $this;
	}
}
?>