<?php


/*$this->getLng()->setWorkedField( 'field1', 'field2'/array() )->setIds( int/array )->setModeFull()->getContent()
																											setModeCur()->setImplant()

$this->getLng()->setWorkedField( 'field1', 'field2'/array() )->setIds( int/array )->setModeFull()->set();
																											setModeCur()->
*/
/*
для статики посмотреть
http://framework.zend.com/manual/ru/zend.translate.html
http://web-blog.org.ua/articles/zend-framework-localizatsiya-proektov-itogi
http://zendframework.ru/forum/index.php?topic=2258.0 - пример бутстрапа
http://www.contentwithstyle.co.uk/content/zendtranslate-with-dynamic-parameters пример со sprintf
*/
// мультиязычность для динамического контента
class Core_Language {

	private $_model; // объект с реализацией Core_Language_Interface
	private $_fieldsFull; // ids всех полей относящихся к данной таблице - кэширование TODO!!!09.03.2011
	private $_fieldsWorked; // ids полей с которыми работаем

	// в будущем этот момент надо будет расширить
	// хранить в бд, чтобы можно было отключать ненужные языки
	// добавлять новые
	// менять приоритет и тип сортировки списка языков
	// добавить сокращения для использования в url (ru/en/fr и т.д.)
	// добавить сокращения для использования в gettext (ru_RU/en_EN/fr и т.д.)
	// добавить сокращения для языков предпочтения которые используются в браузерах (если они отличаются от сокращений gettext)
	// вынести это дело в Core_Language_List
	public static $lang=array(
		1=>'English',
		2=>'French',
		3=>'Spanish',
		4=>'German',
	);
	// предназначено для использования в шаблонах
	public static $flags=array(
		1=>array( 'ico'=>'en.gif', 'title'=>'English' ),
		2=>array( 'ico'=>'fr.gif', 'title'=>'French' ),
		3=>array( 'ico'=>'sp.gif', 'title'=>'Spanish' ),
		4=>array( 'ico'=>'gr.gif', 'title'=>'German' ),
	);
	private $_langFlipped=array();
	private $_curLang='';

	private static $_instance=array();

	public static function getInstanceFor( $obj ) {
		$_str=get_class( $obj );
		if ( empty( self::$_instance[$_str] ) ) {
			self::$_instance[$_str]=new Core_Language();
			self::$_instance[$_str]->setModel( $obj );
		}
		return self::$_instance[$_str];
	}

	public function __construct() {
		$this->_langFlipped=array_flip( self::$lang );
	}

	// установка текущего языка
	public function setCurLang( $_str='' ) {
		if ( empty( $_str )||!in_array( $_str, self::$lang ) ) {
			return;
		}
		$this->_curLang=$this->_langFlipped[$_str];
		return $this;
	}

	// установка языка по умолчанию (того что в оригинальном поле - чтобы автоматически скопировать первый раз в нужную языковую версию)
	public function setDefLang( $_str='' ) {
		if ( empty( $_str )||!in_array( $_str, self::$lang ) ) {
			return;
		}
		$this->_defLang=$this->_langFlipped[$_str];
		self::$flags[$this->_defLang]['def']=true;
		return $this;
	}

	// модель в которой требуется мультиязычность
	public function setModel( Core_Language_Interface $obj ) {
		$this->_model=$obj;
		// инициализация таблицы и полей для перевода, выгребаем из бд ids ссылок на поля
		$this->_fieldsFull=Core_Language_Manage::getFields( $this->_model->getTable(), $this->_model->getFieldsForTranslate() );
		$this->_fieldsFullFlipped=array_flip( $this->_fieldsFull );
		if ( empty( $this->_fieldsFull ) ) {
			// ? exeption тоже не вернёшь поидее
		}
		$this->_fieldsWorked=$this->_fieldsFull;
		$this->setDefLang( $this->_model->getDefaultLang() );
		return $this;
	}

	// поля с которыми работаем в созданном объекте
	public function setWorkedField( $_arrFields=null ) {
		if ( empty( $this->_fieldsWorked ) ) {
			return $this;
		}
		if ( !is_array( $_arrFields ) ) {
			$_arrFields=func_get_args();
		}
		$this->_fieldsWorked=array_intersect( $this->_fieldsWorked, $_arrFields );
		if ( empty( $this->_fieldsWorked ) ) {
			// эксепшн - нету полей для работы
		}
		return $this;
	}

	// генерация подзапросов
	public function getQuery() {
		$_strSql='';
		foreach( $this->_fieldsWorked as $k=>$v ) {
			$_strSql.='IFNULL((SELECT ls.description FROM lng_storage ls WHERE ls.id='.$this->_model->getTable().'.id AND ls.reference_id='.$k.' AND ls.flg_lng='.(empty( $this->_curLang )? $this->_defLang:$this->_curLang).'),'.$v.') '.$v;
		}
		return $_strSql;
	}

	// получение переводов
	// id=>ref_id=>flg_lang=>content
	public function getContent( &$arrRes ) {
		if ( empty( $this->_ids )||empty( $this->_fieldsWorked ) ) {
			return false;
		}
		$_arrTranslation=Core_Sql::getAssoc( '
			SELECT * 
			FROM lng_storage 
			WHERE 
				id IN('.Core_Sql::fixInjection( $this->_ids ).') AND 
				reference_id IN('.Core_Sql::fixInjection( array_keys( $this->_fieldsWorked ) ).')
				'.(empty( $this->_curLang )? '':'AND flg_lng='.$this->_curLang).'
		' );
		if ( empty( $_arrTranslation ) ) {
			return false;
		}
		foreach( $_arrTranslation as $v ) {
			$arrRes[$v['id']][$v['reference_id']][$v['flg_lng']]=$v['description'];
		}
		return true;
	}

	// добавляет полученный контент (перевод/ы) в полученный результат в модели (для случая слияния данных в интерпретаторе)
	public function setImplant() {
		$arrRes=&$this->_model->getResult();
		if ( empty( $this->_ids ) ) {
			foreach( $arrRes as $v ) {
				$this->_ids[]=$v['id'];
			}
		}
		// если есть $this->_defLang то генерируем перевод на этот язык из оригинального поля
		if ( !$this->getContent( $_arrTranslation )&&empty( $this->_defLang ) ) {
			return;
		}
		foreach( $arrRes as $k=>$v ) {
			foreach( $v as $fieldName=>$fieldValue ) {
				if ( !in_array( $fieldName, $this->_fieldsWorked ) ) {
					continue;
				}
				if ( empty( $_arrTranslation ) ) {
					$arrRes[$k][($fieldName.'_lng')][$this->_defLang]=$fieldValue;
				} else {
					// добавляем доступные переводы в поле fieldName_lng
					$arrRes[$k][($fieldName.'_lng')]=$_arrTranslation[$v['id']][$this->_fieldsFullFlipped[$fieldName]];
				}
			}
		}
	}

	// режим получения всех доступных переводов
	public function setModeFull() {}

	// режим получения перевода на текущем языке
	public function setModeCur() {}

	// ids строк переводы полей которых надо получить
	public function setIds( $_arrIds=array() ) {
		if ( !is_array( $_arrIds ) ) {
			$_arrIds=array( $_arrIds );
		}
		$this->_ids=$_arrIds;
		return $this;
	}

	public function del() {
		if ( empty( $this->_ids ) ) {
			return;
		}
		Core_Sql::setExec( '
			DELETE FROM lng_storage 
			WHERE 
				id IN('.Core_Sql::fixInjection( $this->_ids ).') AND 
				reference_id IN('.Core_Sql::fixInjection( array_keys( $this->_fieldsFull ) ).')
		' );
		$this->_ids=array();
	}

	// сохранение/обновление переводов
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
			if ( !in_array( $_strField, $this->_fieldsWorked ) ) {
				continue;
			}
			foreach( $v as $_intLng=>$_strTranslate ) {
				$_arrIns[]=array( 'id'=>$_arrData['id'], 'reference_id'=>$this->_fieldsFullFlipped[$_strField], 'flg_lng'=>$_intLng, 'description'=>$_strTranslate );
			}
		}
		$this->setIds( $_arrData['id'] )->del();
		if ( !empty( $_arrIns ) ) {
			Core_Sql::setMassInsert( 'lng_storage', $_arrIns );
		}
	}
}
?>