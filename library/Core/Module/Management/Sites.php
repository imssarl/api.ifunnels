<?php


/**
 * Sites management
 */
class Core_Module_Management_Sites extends Core_Data_Storage implements Core_Singleton_Interface {

	protected $_table='sys_site';

	protected $_fields=array( 'id', 'root_id', 'flg_type', 'flg_active', 'domain', 'sys_name', 'title', 'added' );

	private static $_checked=false;

	public function __construct() {
		if ( !Zend_Registry::get( 'config' )->engine->check_installed ) {
			return;
		}
		if ( $this->checkInstalled() ) {
			self::$_checked=true;
		}
	}

	private function checkInstalled() {
		if ( self::$_checked ) {
			return true;
		}
		foreach( Zend_Registry::get( 'config' )->sites->toArray() as $k=>$v ) {
			if ( $this->onlyCell()->withSysName( $v['sys_name'] )->getList( $_intId )->checkEmpty() ) {
				continue;
			}
			$v['flg_type']=$v['flg_type']=='backend'? 1:0;
			if ( !$this->setEntered( $v )->set() ) {
				throw new Exception( Core_Errors::DEV.'|"'.$v['title'].'" site isn\'t installed' );
				return false;
			}
		}
		return true;
	}

	/**
	 * аспект кторый вызывается до выполнения set()
	 * после переназначения тут например можно организовать проверку полей
	 *
	 * @return boolean
	 */
	protected function beforeSet() {
		$this->_data->setFilter();
		if ( !$this->_data->setChecker( array(
			'sys_name'=>empty( $this->_data->filtered['sys_name'] ),
		) )->check() ){
			$this->_data->getErrors( $this->_errors );
			return false;
		}
		// дополняем остальные данные для корректной вставки
		$this->_data->setElements( array(
			'flg_active'=>empty( $this->_data->filtered['flg_active'] ) ? 0:1,
			'flg_type'=>empty( $this->_data->filtered['flg_type'] ) ? 0:1,
		) );
		return true;
	}

	/**
	 * аспект кторый вызывается после выполнения set()
	 * после переназначения тут например можно сделать какие-либо действия после сохранения данных
	 *
	 * @return boolean
	 */
	protected function afterSet() {
		// корневая страница уже есть и это лишь редактирование сайта
		if ( !empty( $this->_data->filtered['root_id'] ) ) {
			return true;
		}
		// создаём корневую страницу
		if ( !Zend_Registry::get( 'pages' )->setEntered( array(
			'sys_name'=>$this->_data->filtered['sys_name'],
			'title'=>$this->_data->filtered['title'],
			'pid'=>'tree_root',
		) )->set() ) {
			return false;
		}
		// апдэйтим root_id
		Zend_Registry::get( 'pages' )->getEntered( $_arrPage );
		$this->_data->setElement( 'root_id', $_arrPage['id'] );
		Core_Sql::setInsertUpdate( $this->_table, $this->_data->setMask( $this->_fields )->getValid() );
		return true;
	}

	/**
	 * удаление сайта из таблицы сайтов и соответствующего дерева страниц
	 *
	 * @return boolean
	 */
	public function del() {
		$_arrId=$this->_withIds;
		$this->init();
		if ( empty( $_arrId ) ) {
			return false;
		}
		if ( !$this->withIds( $_arrId )->getList( $_arrSite ) ) {
			return false;
		}
		if ( empty( $_arrSite['root_id'] ) ) {
			return false;
		}
		// удаляем дерево сайта
		if ( !Zend_Registry::get( 'pages' )->withIds( $_arrSite['root_id'] )->del() ) {
			return false;
		}
		// удаляем сайт
		$this->withIds( $_arrId );
		return parent::del();
	}

	/**
	 * фильтр: страницы только определённого сайта
	 *
	 * @var integer
	 */
	protected $_withRootId=0;

	/**
	 * фильтр: страницы с таким системным именем
	 *
	 * @var integer
	 */
	protected $_withSysName='';

	protected function init() {
		parent::init();
		$this->_withRootId=0;
		$this->_withSysName='';
	}

	public function withRootId( $_int=0 ) {
		if ( empty( $_int ) ) {
			return $this;
		}
		$this->_withRootId=$_int;
		return $this;
	}

	public function withSysName( $_str='' ) {
		if ( empty( $_str ) ) {
			return $this;
		}
		$this->_withSysName=$_str;
		return $this;
	}

	protected function assemblyQuery() {
		if ( $this->_onlyIds ) {
			$this->_crawler->set_select( 'd.id' );
		} elseif ( $this->_toSelect ) {
			$this->_crawler->set_select( 'd.root_id, d.title' );
		} else {
			$this->_crawler->set_select( 'd.*' );
		}
		$this->_crawler->set_from( $this->_table.' d' );
		if ( !empty( $this->_withIds ) ) {
			$this->_crawler->set_where( 'd.id IN ('.Core_Sql::fixInjection( $this->_withIds ).')' );
		}
		if ( !empty( $this->_withSysName ) ) {
			$this->_crawler->set_where( 'd.sys_name='.Core_Sql::fixInjection( $this->_withSysName ) );
		}
		if ( !empty( $this->_withRootId ) ) {
			$this->_crawler->set_where( 'd.root_id='.Core_Sql::fixInjection( $this->_withRootId ) );
		}
		if ( !( $this->_onlyOne||$this->_onlyCell ) ) {
			$this->_crawler->set_order_sort( $this->_withOrder );
		}
		if ( !empty( $this->_withGroup ) ) {
			$this->_crawler->set_group( $this->_withGroup );
		}
	}

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
	public static function getInstance() {
		if ( self::$_instance==NULL ) {
			if ( !Zend_Registry::isRegistered( 'pages' ) ) {
				new Core_Module_Management_Pages();
			}
			self::$_instance=new self();
		}
		return self::$_instance;
	}
}
?>