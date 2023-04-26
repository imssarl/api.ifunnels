<?php


/**
 * Tree&Pages management
 */
class Core_Module_Management_Pages extends Core_Data_Storage implements Core_i18n_Dynamic_Interface {

	protected $_table='sys_page';

	protected $_fields=array( 'id', 'pid', 'level', 'sort', 'root_id', 'action_id', 'item_id', 
		'sys_name', 'title', 'meta_description', 'meta_keywords', 'meta_robots', 'flg_onmap', 'added' );

	private $_tree;

	public function __construct() {
		$this->_tree=new Core_Adjacency( $this->_table );
		// возможно в регистри следует хранить объект с текущим деревом сайта
		// в остальных случаях создавать новый объект (может через синглтон чтобы $this->_tree кэшить) - подумать TODO!!! 10.01.2012
		if ( !Zend_Registry::isRegistered( 'pages' ) ) {
			Zend_Registry::set( 'pages', $this ); // закидываем объект в регистри
		}
	}

	/**
	 * автосоздание или обновление (только поля title) страницы бэкенда
	 * нужно при инсталляции или обновлении модуля
	 *
	 * @param array $_arrPage in
	 * @return integer
	 */
	public function setBackendModulePage( $_arrPage=array() ) {
		if ( $this->onlyOne()->withPid( $_arrPage['pid'] )->withRootId( $_arrPage['root_id'] )
			->withSysName( $_arrPage['sys_name'] )->getList( $_arrUpdate )->checkEmpty() ) {
			$_arrUpdate['title']=$_arrPage['title'];
			$_arrPage=$_arrUpdate;
		}
		if ( !$this->setEntered( $_arrPage )->set() ) {
			throw new Exception( Core_Errors::DEV.'|page autocreate fail' );
		}
		return $this->_data->filtered['id'];
	}

	/**
	 * аспект кторый вызывается до выполнения set()
	 * после переназначения тут например можно организовать проверку полей
	 *
	 * @return boolean
	 */
	protected function beforeSet() {
		$this->_data->setFilter();
		// при инсталляции каждого сайта создаётся заглавная страница родителем которой является коренная нода всего дерева
		$_boolOrdinaryPage=true;
		if ( !empty( $this->_data->filtered['pid'] )&&$this->_data->filtered['pid']=='tree_root' ) {
			$_boolOrdinaryPage=false;
			$this->_data->setElement( 'pid', $this->_tree->root_id );
		}
		// если парент не указан, рут сайта становится парентом страницы
		if ( empty( $this->_data->filtered['pid'] )&&!empty( $this->_data->filtered['root_id'] ) ) {
			$this->_data->setElement( 'pid', $this->_data->filtered['root_id'] );
		}
		// если системное имя не указано но указан заголовок, генерим системное имя из заголовка
		if ( empty( $this->_data->filtered['sys_name'] )&&!empty( $this->_data->filtered['title'] ) ) {
			$this->_data->setElement( 'sys_name', Core_String::getInstance( $this->_data->filtered['title'] )->rus2translite() ); // провермть что просто транслит TODO!!! 10.01.2012
		}
		if ( !empty( $this->_data->filtered['sys_name'] ) ) {
			// приводим к внутреннему формату - буквы в нижнем регистре, между словами знак минус
			$this->_data->setElement( 'sys_name', Core_String::getInstance( $this->_data->filtered['sys_name'] )->toSystem( '-' ) );
			if ( empty( $this->_data->filtered['title'] ) ) {
				// если заголовок не указан, системное имя становится заголовком
				$this->_data->setElement( 'title', $this->_data->filtered['sys_name'] );
			}
		}
		if ( !$this->_data->setChecker( array(
			'sys_name'=>empty( $this->_data->filtered['sys_name'] ),
			'sys_name_exists'=>$this->isNewPageUnique(),
			'pid'=>empty( $this->_data->filtered['pid'] ),
			'root_id'=>$_boolOrdinaryPage&&empty( $this->_data->filtered['root_id'] )
		))->check() ){
			$this->_data->getErrors( $this->_errors );
			return false;
		}
		// для новых страниц указываем уровень в дереве
		if ( empty( $this->_data->filtered['id'] ) ) {
			$this->_tree->node_info( $_arrPar, $this->_data->filtered['pid'] );
			$this->_data->setElement( 'level', ++$_arrPar['level'] );
		}
		// дополняем остальные данные для корректной вставки
		$this->_data->setElements( array(
			'meta_robots'=>empty( $this->_data->filtered['meta_robots'] ) ? 0:1,
			'flg_onmap'=>empty( $this->_data->filtered['flg_onmap'] ) ? 0:1,
			'meta_description'=>empty( $this->_data->filtered['meta_description'] ) ? NULL:$this->_data->filtered['meta_description'],
			'meta_keywords'=>empty( $this->_data->filtered['meta_keywords'] ) ? NULL:$this->_data->filtered['meta_keywords'],
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
		if ( empty( $this->_data->filtered['id'] ) ) {
			return false;
		}
		if ( Zend_Registry::isRegistered( 'locale' ) ) {
			$this->getLng()->set( $this->_data->filtered );
		}
		// это если у нас была вставки заглавной страницы сайта
		// в этом случае root_id сразу неопределён
		if ( empty( $this->_data->filtered['root_id'] ) ) {
			$this->_data->setElement( 'root_id', $this->_data->filtered['id'] );
			Core_Sql::setInsertUpdate( $this->_table, $this->_data->setMask( $this->_fields )->getValid() );
		}
		// в этом случае нода в ряду будет первой и позицию не установит node_posset
		if ( empty( $this->_data->filtered['position'] ) ) {
			return true;
		}
		// позиция в дереве после вставки ноды
		return $this->_tree->node_posset( $this->_data->filtered['id'], $this->_data->filtered['position'] );
	}

	// проверка на уникальность sys_name в пределах одного уровня с общим pid
	private function isNewPageUnique() {
		if ( empty( $this->_data->filtered['sys_name'] )||empty( $this->_data->filtered['pid'] ) ) {
			return true;
		}
		if ( !$this->withPid( $this->_data->filtered['pid'] )->getList( $_arrNode )->checkEmpty() ) {
			return false;
		}
		foreach( $_arrNode as $v ) {
			if ( $v['sys_name']==$this->_data->filtered['sys_name']&&( empty( $this->_data->filtered['id'] )||$v['id']!=$this->_data->filtered['id'] ) ) {
				return true;
			}
		}
		return false;
	}

	// сортировка вверх
	public function up( $_int=0 ) {
		return $this->_tree->node_posmov( $_int, 'up' );
	}

	// сортировка вниз
	public function down( $_int=0 ) {
		return $this->_tree->node_posmov( $_int, 'down' );
	}

	// скрыть-показать узел в дереве
	public function onSiteMap( $_int=0 ) {
		if ( empty( $_int ) ) {
			return false;
		}
		Core_Sql::setExec( 'UPDATE '.$this->_table.' SET flg_onmap=1-flg_onmap WHERE id="'.$_int.'" LIMIT 1' );
		return true;
	}

	/**
	 * этот метод может быть переназначен для изменения полей при дублировании строк
	 * добавить этот функционал и вывести в админке TODO!!! 10.01.2012
	 *
	 * @return void
	 */
	public function changeFields( &$arrRes ) {}

	/**
	 * удаление узла и всех подчинённых узлов
	 *
	 * @return boolean
	 */
	public function del() {
		if ( empty( $this->_withIds ) ) {
			$this->init();
			return false;
		}
		$_bool=$this->_tree->tree_delete( $_arrIds, $this->_withIds );
		$this->init();
		return $_bool;
	}

	/**
	 * фильтр: страницы только определённого сайта
	 *
	 * @var integer
	 */
	protected $_withRootId=0;

	/**
	 * фильтр: страницы c определённым pid (см. схему БД)
	 *
	 * @var integer
	 */
	protected $_withPid=0;

	/**
	 * фильтр: страницы по id экшена
	 *
	 * @var integer
	 */
	protected $_withActionId=0;

	/**
	 * фильтр: страницы с таким системным именем
	 *
	 * @var integer
	 */
	protected $_withSysName='';

	/**
	 * фильтр: только разрешённые к показу страницы
	 *
	 * @var boolean
	 */
	protected $_onlyOnMap=false;

	/**
	 * фильтр: это нужно чтобы взять список 'd.id, d.title' но через Core_Sql::getAssoc
	 *
	 * @var boolean
	 */
	protected $_toPosition=false;

	/**
	 * фильтр: когда собирается вся инфа для генерации дерева сайта указывать
	 *
	 * @var boolean
	 */
	protected $_withFullInfo=false;

	/**
	 * фильтр: получить дерево с рутовой нодой
	 *
	 * @var boolean
	 */
	protected $_withRootNode=false;

	/**
	 * флаг который используем при мультиязычности
	 * гаситм не в init а в getList
	 *
	 * @var boolean
	 */
	protected $_editMode=false;

	protected function init() {
		parent::init();
		$this->_withRootId=0;
		$this->_withPid=0;
		$this->_withActionId=0;
		$this->_withSysName='';
		$this->_onlyOnMap=false;
		$this->_toPosition=false;
		$this->_withFullInfo=false;
		$this->_withRootNode=false;
	}

	public function withRootId( $_int=0 ) {
		if ( empty( $_int ) ) {
			return $this;
		}
		$this->_withRootId=$_int;
		return $this;
	}

	public function withActionId( $_int=0 ) {
		if ( empty( $_int ) ) {
			return $this;
		}
		$this->_withActionId=$_int;
		return $this;
	}

	public function withPid( $_int=0 ) {
		if ( empty( $_int ) ) {
			return $this;
		}
		$this->_withPid=$_int;
		return $this;
	}

	public function withSysName( $_str='' ) {
		if ( empty( $_str ) ) {
			return $this;
		}
		$this->_withSysName=$_str;
		return $this;
	}

	public function withRootNode() {
		$this->_withRootNode=true;
		return $this;
	}

	public function onlyOnMap() {
		$this->_onlyOnMap=true;
		return $this;
	}

	public function toPosition() {
		$this->_toPosition=true;
		return $this;
	}

	public function withFullInfo() {
		$this->_withFullInfo=true;
		return $this;
	}

	protected function assemblyQuery() {
		$this->_crawler->set_from( $this->_table.' d' );
		if ( $this->_onlyIds ) {
			$this->_crawler->set_select( 'd.id' );
		} elseif ( $this->_toSelect||$this->_toPosition ) {
			if ( Zend_Registry::isRegistered( 'locale' ) ) {
				$this->_crawler->set_select( 'd.id, '.($this->_editMode? 'd.title':$this->getLng()->setWorkedField( 'title' )->getSubQuery()) );
			} else {
				$this->_crawler->set_select( 'd.id, d.title' );
			}
		} else {
			if ( Zend_Registry::isRegistered( 'locale' ) ) {
				$this->_crawler->set_select( 'd.*'.($this->_editMode? '':', '.$this->getLng()->getSubQuery()) );
			} else {
				$this->_crawler->set_select( 'd.*' );
			}
		}
		if ( $this->_withFullInfo ) {
			$this->_crawler->set_select( 'a.action, a.flg_tpl, m.name' );
			$this->_crawler->set_from( 'LEFT JOIN sys_action a ON a.id=d.action_id' );
			$this->_crawler->set_from( 'LEFT JOIN sys_module m ON m.id=a.module_id' );
		}
		if ( !empty( $this->_withActionId ) ) {
			$this->_crawler->set_where( 'd.action_id='.Core_Sql::fixInjection( $this->_withActionId ) );
		}
		if ( !empty( $this->_withSysName ) ) {
			$this->_crawler->set_where( 'd.sys_name='.Core_Sql::fixInjection( $this->_withSysName ) );
		}
		if ( !empty( $this->_withRootId ) ) {
			$this->_crawler->set_where( 'd.root_id='.Core_Sql::fixInjection( $this->_withRootId ) );
		}
		if ( !empty( $this->_withPid ) ) {
			$this->_crawler->set_where( 'd.pid='.Core_Sql::fixInjection( $this->_withPid ) );
		}
		if ( $this->_onlyOnMap ) {
			$this->_crawler->set_where( 'd.flg_onmap=1' );
		}
		if ( !empty( $this->_withIds ) ) {
			$this->_crawler->set_where( 'd.id IN ('.Core_Sql::fixInjection( $this->_withIds ).')' );
		}
		if ( !( $this->_onlyOne||$this->_onlyCell ) ) {
			$this->_crawler->set_order_sort( $this->_withOrder );
		}
		if ( !empty( $this->_withGroup ) ) {
			$this->_crawler->set_group( $this->_withGroup );
		}
	}

	public function getList( &$mixRes ) {
		parent::getList( $mixRes );
		if ( Zend_Registry::isRegistered( 'locale' )&&$this->_editMode ) {
			$this->getLng()->withIds( $this->_withIds )->setImplant( $mixRes );
		}
		$this->_editMode=false;
		return $this;
	}

	public function getTree( &$arrRes ) {
		if ( empty( $this->_withRootId ) ) {
			throw new Exception( Core_Errors::DEV.'|Root id not set' );
			return false;
		}
		// как-то надо переделывать self::makeTree возможно динамикой TODO!!! 25.01.2012
		$arrSetting=array(
			'node_id'=>$this->_withRootId,
			'with_root_node'=>$this->_withRootNode,
			'offset'=>'',
			'result'=>&$arrRes
		);
		// подстановка префикса пути
		if ( Zend_Registry::get( 'objMR' )->currentSite['root_id']==$this->_withRootId ) {
			$arrSetting['offset']=Zend_Registry::get( 'objMR' )->currentSite['prefix'];
		} else {
			foreach( Zend_Registry::get( 'objMR' )->sites as $v ) {
				if ( $v['root_id']!=$this->_withRootId ) {
					continue;
				}
				$arrSetting['offset']=$v['prefix'];
				break;
			}
		}
		if ( !$this->withFullInfo()->withOrder( array( 'd.level--dn', 'd.sort--dn' ) )->getList( $_arrNodes )->checkEmpty() ) {
			return false;
		}
		/*Zend_Registry::get( 'CachedCoreModuleManagementPages' )->*/self::makeTree( $_arrNodes, $arrSetting );
		return true;
	}

	private static function makeTree( &$arrNodes, $_arrSetting ) {
		$_arrRes=array();
		$k=0;
		foreach( $arrNodes as $v ) {
			if ( !empty( $_arrSetting['with_root_node'] )&&$v['id']==$_arrSetting['node_id'] ) { // корень дерева создаётся при инсталляции сайтов
				unSet( $_arrSetting['with_root_node'] );
				$_arrRes[$k]=array( 
					'sys_name'=>$_arrSetting['offset'], 
					'level'=>--$v['level'] )+$v;
				foreach( $_arrSetting['result'] as $need=>$tmp ) {
					switch( $need ) {
						case 'MOD_URIS': $_arrSetting['result']['MOD_URIS'][$_arrSetting['offset']]=$_arrRes[$k]; break; // backward
						case 'MOD_BYIDS': $_arrSetting['result']['MOD_BYIDS'][$v['id']]=$_arrRes[$k]; break; // by ids
					}
				}
				self::makeTree( $arrNodes, $_arrSetting );
				if ( isSet( $_arrSetting['result']['MOD_TREE'] ) ) {
					$_arrRes[$k]['node']=$_arrSetting['result']['MOD_TREE'];
					$_arrSetting['result']['MOD_TREE']=$_arrRes;
				}
				return;
			}
			if ( $v['pid']!=$_arrSetting['node_id'] ) { // пропускаем ноды с другими pid
				continue;
			}
			$_arrRes[$k]=array( 
				'page'=>$v['sys_name'], 
				'sys_name'=>$_arrSetting['offset'].$v['sys_name'].'/', // наращиваем ссылку
				'level'=>--$v['level'] )+$v;
			foreach( $_arrSetting['result'] as $need=>$tmp ) {
				switch( $need ) {
					case 'MOD_URLS': $_arrSetting['result']['MOD_URLS'][$v['name']]['actions'][$v['action']]=$_arrRes[$k]; break; // direct
					case 'MOD_URIS': $_arrSetting['result']['MOD_URIS'][$_arrRes[$k]['sys_name']]=$_arrRes[$k]; break; // backward
					case 'MOD_BYIDS': $_arrSetting['result']['MOD_BYIDS'][$v['id']]=$_arrRes[$k]; break; // by ids
				}
			}
			self::makeTree( $arrNodes, array( 'node_id'=>$v['id'], 'offset'=>$_arrRes[$k]['sys_name'] )+$_arrSetting );
			if ( isSet( $_arrSetting['result']['MOD_TREE'] ) ) {
				$_arrRes[$k]['node']=$_arrSetting['result']['MOD_TREE'];
			}
			$k++;
		}
		if ( isSet( $_arrSetting['result']['MOD_TREE'] ) ) {
			$_arrSetting['result']['MOD_TREE']=$_arrRes;
		}
	}

	public function editMode() {
		$this->_editMode=true;
		return $this;
	}

	public function getTable( $_bool=false ) {
		return ($_bool?'d':$this->_table);
	}

	public function getFieldsForTranslate() {
		return array( 'title', 'meta_description', 'meta_keywords' );
	}

	public function getDefaultLang() {
		return 'en';
	}

	public function getLng() {
		return Core_i18n_Dynamic::getInstance( $this );
	}
}
?>