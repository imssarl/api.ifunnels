<?php


/**
 * возможности которые можно добавить в модель по средствам использования Core_i18n_Dynamic_Interface:
 * если мы редактируем данные (в админке например) то на шаблон выкидываются сразу все варианты переводов вместе с остальными данными записи
 * если мы отображаем данные в таблицах или на фронтэнде то используется язык который указан по ссылке, если такого перевода нет то исходный
 * при обновлении исходных даных обновляется поле соответствующего языка
 */
class Core_i18n_Dynamic extends Core_Data_Storage/* implements Core_Singleton_Interface*/ {

	protected $_table='lng_storage';

	protected $_fields=array( 'id', 'table_id', 'reference_id', 'flg_lng', 'description' );

	// объект с реализацией Core_Language_Interface
	private $_model=NULL;

	private $_langFlipped=array();

	// id языка поумолчанию для данной модели
	private $_intDefLng=0;

	// id языка который является текущим (определяется исходя из ссылки и т.д.)
	private $_intCurLng=0;

	// предназначено для использования в шаблонах при редактировании данных
	// индексы этого массива подставляем в lng_storage.flg_lng для обозначения языка первода
	public static $flags=array(
		1=>array( 'lng'=>'en', 'title'=>'English' ),
		2=>array( 'lng'=>'fr', 'title'=>'French' ),
		3=>array( 'lng'=>'sp', 'title'=>'Spanish' ),
		4=>array( 'lng'=>'de', 'title'=>'German' ),
	);

	/**
	 * экземпляр объекта текущего класса (singleton)
	 *
	 * @var object
	 */
	private static $_instance=NULL;

	/**
	 * возвращает экземпляр объекта текущего класса (singleton)
	 * при первом обращении создаёт
	 *
	 * @return object
	 */
	public static function getInstance( $obj ) {
		$_str=get_class( $obj );
		if ( empty( self::$_instance[$_str] ) ) {
			self::$_instance[$_str]=new self( $obj );
		}
		return self::$_instance[$_str];
	}

	public function __construct( Core_i18n_Dynamic_Interface $obj ) {
		$this->initLngVars();
		// тут язык по ссылке, при необходимости можно перенезначить язык после вызова конструктора
		$this->setCurLang( Zend_Registry::get( 'locale' )->getLanguage() );
		$this->setModel( $obj );
	}

	private function initLngVars() {
		$_arrLng=Zend_Registry::get( 'config' )->i18n->languages->toArray();
		foreach( self::$flags as $k=>$v ) {
			if ( !in_array( $v['lng'], $_arrLng ) ) {
				unSet( self::$flags[$k] );
			}
			$this->_langFlipped[$v['lng']]=$k;
		}
	}

	// установка текущего языка
	// нужен только для того чтобы в запросе подставить
	// если отобразить надо какой-то другой язык, отличный от текущего, надо его тут выставить
	public function setCurLang( $_str='' ) {
		if ( !Zend_Registry::get( 'locale' )->checkAvailability( $_str ) ) {
			throw new Exception( Core_Errors::DEV.'|<'.$_str.'> language empty or no configurated. See config <i18n> section' );
			return;
		}
		$this->_intCurLng=$this->_langFlipped[$_str];
		return $this;
	}

	// указываем язык в котором у нас хранятся исходные данные
	// в соответствии с этим он копируется в нужную языковую версию
	private function setDefLang( $_str='' ) {
		if ( !Zend_Registry::get( 'locale' )->checkAvailability( $_str ) ) {
			// возможен вариант года на сайте языка нет, но данные именно на этом языке и его тоже нужно использовать
			// TODO!!! 24.01.2012
			throw new Exception( Core_Errors::DEV.'|<'.$_str.'> language empty or no configurated. See config <i18n> section' );
			return;
		}
		$this->_intDefLng=$this->_langFlipped[$_str];
		self::$flags[$this->_intDefLng]['def']=true; // показывает какой таб открывать первым при редактировании
		return $this;
	}

	// настройка текущего объекта
	private function setModel( Core_i18n_Dynamic_Interface $obj ) {
		$this->_model=$obj;
		$_strTable=$this->_model->getTable();
		$_arrFields=$this->_model->getFieldsForTranslate();
		if ( empty( $_strTable )||empty( $_arrFields ) ) {
			throw new Exception( Core_Errors::DEV.'|Table or Fields are empty' );
			return;
		}
		$_table=new Core_i18n_Dynamic_Table();
		if ( !$_table->withTitle( $_strTable )->check() ) {
			throw new Exception( Core_Errors::DEV.'|<'.$_strTable.'> not initialized' );
			return;
		}
		$_reference=new Core_i18n_Dynamic_Reference();
		if ( !$_reference->withTableId( $_table->id )->check( $_arrFields ) ) {
			throw new Exception( Core_Errors::DEV.'|<'.print_r( $_arrFields, true ).'> not initialized' );
			return;
		}
		$this->_tableId=$_table->id;
		$this->_fieldsWorked=$this->_fieldsFull=$_reference->getInstalled();
		$this->_fieldsFullFlipped=array_flip( $this->_fieldsFull );
		$this->setDefLang( $this->_model->getDefaultLang() );
		return $this;
	}

	/*
	служит для сохранения-обновления переводов одной строки данных из внешней таблицы
	сюда приходит массив из формы вида
	array(
		'id'=>
		'field1'=>
		'fieldN'=>
		'field1_lng'=>array(
			intLngId1=>content,
			intLngId2=>content,
			intLngId3=>content,
		)
		'fieldN_lng'=>...
	)
	где id это id во внешней таблице где хранятся эти данные
	intLngId1 - индекс из Core_i18n_Dynamic::$flags
	content - перевод для данного языка
	*/
	public function set( $_arrData=array() ) {
		if ( empty( $_arrData['id'] ) ) {
			return;
		}
		$_arrIns=array();
		foreach( $_arrData as $k=>$v ) {
			if ( substr( $k, -4 )!='_lng' ) {
				continue;
			}
			$_strField=substr( $k, 0, -4 );
			if ( empty( $this->_fieldsFullFlipped[$_strField] ) ) {
				continue;
			}
			foreach( $v as $_intLng=>$_strTranslate ) {
				$_arrIns[]=array( 
					'id'=>$_arrData['id'], 
					'table_id'=>$this->_tableId, 
					'reference_id'=>$this->_fieldsFullFlipped[$_strField], 
					'flg_lng'=>$_intLng, 
					'description'=>$_strTranslate );
			}
		}
		$this->withIds( $_arrData['id'] )->del();
		if ( !empty( $_arrIns ) ) {
			Core_Sql::setMassInsert( 'lng_storage', $_arrIns );
		}
	}

	/**
	 * удаление одной или нескольких записей
	 *
	 * @return boolean
	 */
	public function del() {
		if ( empty( $this->_withIds )||empty( $this->_tableId ) ) {
			$_bool=false;
		} else {
			Core_Sql::setExec( 'DELETE FROM '.$this->_table.' WHERE id IN('.Core_Sql::fixInjection( $this->_withIds ).') AND table_id='.$this->_tableId );
			$_bool=true;
		}
		$this->init();
		return $_bool;
	}

	// генерация подзапросов для выборки перевода по текущему языку либо дефолтного
	// можно указать поля для которых будет сгенерирован подзапрос
	// иначе он сгенерируется для всех мультиязычных полей данной модели
	// если нет неодного перевода то значение берётся из оригинального поля (в случае когда запись ниразу не редактировалась в мультиязычной форме)
	public function getSubQuery() {
		$_arrSql=array();
		foreach( $this->_fieldsWorked as $k=>$v ) {
			$_arrSql[]='IFNULL((SELECT ls.description FROM '.$this->_table.' ls WHERE 
					ls.id='.$this->_model->getTable( true ).'.id AND 
					ls.reference_id='.$k.' AND 
					ls.flg_lng='.(empty( $this->_intCurLng )? $this->_intDefLng:$this->_intCurLng).'),'.$this->_model->getTable( true ).'.'.$v.') '.$v;
		}
		$this->init();
		return join( ', ', $_arrSql );
	}

	// добавляет полученный контент (перевод/ы) в полученный результат в модели (для случая слияния данных в интерпретаторе)
	// когда мы выкидываем данные на форму где отображаются все варианты переводов
	public function setImplant( &$arrRes ) {
		if ( empty( $this->_intDefLng ) ) {
			return;
		}
		// если ids не указаны выгребаем их из данных
		if ( empty( $this->_withIds ) ) {
			if ( !empty( $arrRes['id'] ) ) {
				$this->_withIds=$arrRes['id'];
			} else {
				foreach( $arrRes as $v ) {
					$this->_withIds[]=$v['id'];
				}
			}
		}
		$this->withIds( $this->_withIds )->getList( $arrTranslation );
		if ( !empty( $arrRes['id'] ) ) {
			$this->prepareFields( $arrRes, $arrTranslation );
		} else {
			foreach( $arrRes as $k=>$v ) {
				$this->prepareFields( $arrRes[$k], $arrTranslation );
			}
		}
	}

	// обрабатываем каждую запись
	private function prepareFields( &$arrItem, &$_arrTranslation ) {
		foreach( $arrItem as $fieldName=>$fieldValue ) {
			if ( !in_array( $fieldName, $this->_fieldsWorked ) ) {
				continue;
			}
			if ( empty( $_arrTranslation ) ) {
				$arrItem[($fieldName.'_lng')][$this->_intDefLng]=$fieldValue;
			} else {
				// добавляем доступные переводы в поле fieldName_lng
				$arrItem[($fieldName.'_lng')]=$_arrTranslation[$arrItem['id']][$this->_fieldsFullFlipped[$fieldName]];
			}
		}
	}

	// id таблицы текущей модели
	private $_tableId=0;

	// все мультиполя текущей модели в виде lng_reference.id=>lng_reference.field_name
	private $_fieldsFull=array();

	// все мультиполя в виде lng_reference.field_name=>lng_reference.id
	private $_fieldsFullFlipped=array();

	// инициализируется при создании объекта
	// по умолчанию равно всем мультиполям текущей модели
	private $_fieldsWorked=array();

	// сброс настроек после выполнения getList/del
	protected function init() {
		parent::init();
		$this->_fieldsWorked=$this->_fieldsFull;
	}

	// поля с которыми работаем в созданном объекте
	public function setWorkedField( $_arrFields=null ) {
		// изначально $this->_fieldsWorked определён и содержит все мульти поля текущей модели
		if ( empty( $this->_fieldsWorked ) ) {
			return $this;
		}
		if ( !is_array( $_arrFields ) ) {
			$_arrFields=func_get_args();
		}
		$this->_fieldsWorked=array_intersect( $this->_fieldsWorked, $_arrFields );
		if ( empty( $this->_fieldsWorked ) ) {
			throw new Exception( Core_Errors::DEV.'|no fields for work' );
		}
		return $this;
	}

	protected function assemblyQuery() {
		parent::assemblyQuery();
		if ( !empty( $this->_fieldsWorked ) ) {
			$this->_crawler->set_where( 'd.reference_id IN('.Core_Sql::fixInjection( array_keys( $this->_fieldsWorked ) ).')' );
		}
	}

	// получение переводов в виде массива
	// id=>ref_id=>flg_lang=>content
	public function getList( &$arrTranslation ) {
		parent::getList( $_arrRes );
		if ( empty( $_arrRes ) ) {
			return $this;
		}
		foreach( $_arrRes as $v ) {
			$arrTranslation[$v['id']][$v['reference_id']][$v['flg_lng']]=$v['description'];
		}
		return $this;
	}
}
?>