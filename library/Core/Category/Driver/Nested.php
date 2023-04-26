<?php
class Core_Category_Driver_Nested extends Core_Category_Driver_Abstract implements Core_Language_Interface {

	private static $_root=NULL; // коренная нода берётся один раз
	private static $_nodes=array(); // кэш выбранных нод
	private $_count;
	private $_pid=array();
	private $_treeIds=array(); // ids удаляемых элементов


	private $_modeLang='view';
	public function setMode( $_str='' ) {
		if ( empty( $_str ) ) {
			return $this;
		}
		$this->_modeLang=$_str; // edit, view
		return $this;
	}

	public function getTable() {
		return $this->_table;
	}

	public function getFieldsForTranslate() {
		return 'title';
	}

	public function getDefaultLang() {
		return Core_Language::$lang[$this->_type['flg_deflng']];
	}

	public function getLng() {
		return Core_Language::getInstanceFor( $this )->setWorkedField( 'title' );
	}

	public function &getResult() {
		return $this->result;
	}

	public function getTree( &$arrRes, $_intPid=0 ) {
		if ( !$this->getAllNodes( $_arrNodes, $_intPid ) ) {
			return false;
		}
		$this->getNodeById( $_arrNode, $_intPid );
		$_arrSetting['from_node']=$_arrNode;
		$arrRes=$this->makeTree( $_arrNodes, $_arrSetting );
		return !empty( $arrRes );
	}

	private function getAllNodes( &$_arrNodes, $_intPid=0 ) {
		if ( !$this->getNodeById( $_arrNode, $_intPid ) ) {
			return false;
		}
		return $this->levelMore( $_arrNode['level'] )->withOrder( 'title, level, priority' )->getList( $_arrNodes );
	}

	private function makeTree( &$arrTree, $_arrSetting=array() ) {
		$arrRes=array();
		foreach( $arrTree as $k=>$v ) {
			// не принадлежит данной ноде
			if ( $v['pid']!=$_arrSetting['from_node']['id'] ) {
				continue;
			}
			// скрыть ноды
			/*if ( !empty( $_arrSetting['hide_nodes'] )&&in_array( $v['id'], $_arrSetting['hide_nodes'] ) ) {
				continue;
			}*/
			$arrRes[$k]=$v;
			$arrRes[$k]['node']=$this->makeTree( $arrTree, array( 'from_node'=>$v ) );
		}
		return $arrRes;
	}

	// если $_intPid не указан то берём первый уровень дерева (принадлежащий рутовой ноде)
	public function getLevel( &$arrRes, $_intPid=0 ) {
		return $this->withPid( ( empty( $_intPid )? $this->getRootId():$_intPid ) )->getList( $arrRes );
	}

	private function getRootId() {
		$_arrRes=$this->getRootNode();
		return $_arrRes['id'];
	}

	private function getRootPid() {
		$_arrRes=$this->getRootNode();
		return $_arrRes['pid'];
	}

	private function getNodeById( &$arrRes, $_intId=0 ) {
		$_intId=empty( $_intId )? $this->getRootId():$_intId;
		if ( empty( self::$_nodes[$this->_table][$_intId] ) ) {
			self::$_nodes[$this->_table][$_intId]=Core_Sql::getRecord( 'SELECT * FROM '.$this->_table.' WHERE id="'.$_intId.'"' );
		}
		$arrRes=self::$_nodes[$this->_table][$_intId];
		return !empty( $arrRes );
	}

	private function getRootNode() {
		if ( self::$_root[$this->_table]!=NULL ) {
			return self::$_root[$this->_table];
		}
		self::$_root[$this->_table]=Core_Sql::getRecord( 'SELECT * FROM '.$this->_table.' WHERE level=0 AND pid=0' );
		if ( empty( self::$_root[$this->_table] ) ) { // инициализируем таблицу
			self::$_root[$this->_table]['level']=self::$_root[$this->_table]['pid']=0;
			self::$_root[$this->_table]['id']=Core_Sql::setInsert( $this->_table, self::$_root[$this->_table] );
		}
		self::$_nodes[$this->_table][self::$_root[$this->_table]['id']]=self::$_root[$this->_table];
		return self::$_root[$this->_table];
	}

	protected $_withPid=0;
	protected $_levelMore=0;
	protected $_withOrder='';

	protected function init() {
		parent::init();
		$this->_withPid=0;
		$this->_levelMore=0;
		$this->_withOrder='';
	}

	public function withPid( $_int=0 ) {
		$this->_withPid=$_int;
		return $this;
	}

	public function levelMore( $_int=0 ) {
		$this->_levelMore=$_int;
		return $this;
	}

	public function withOrder( $_str=0 ) {
		$this->_withOrder=$_str;
		return $this;
	}

	public function getList( &$mixRes ) {
		$_crawler=new Core_Sql_Qcrawler();
		if ( $this->_toSelect ) {
			if ( empty( $this->_type['flg_multilng'] ) ) {
				$_crawler->set_select( 'id, title' );
			} else {
				$_crawler->set_select( 'id, '.$this->getLng()->getQuery() );
			}
		} else {
			if ( !empty( $this->_type['flg_multilng'] )&&$this->_modeLang=='view' ) {
				$_crawler->set_select( '*, '.$this->getLng()->getQuery() );
			} else {
				$_crawler->set_select( '*' );
			}
		}
		$_crawler->set_from( $this->_table );
		if ( !empty( $this->_type['flg_user'] ) ) {
			$_crawler->set_where( 'user_id="'.$this->userId.'"' );
		}
		if ( !empty( $this->_byTitle ) ) {
			$_crawler->set_where( 'title='.Core_Sql::fixInjection( $this->_byTitle ) );
		}
		if ( !empty( $this->_byId ) ) {
			$_crawler->set_where( 'id IN('.Core_Sql::fixInjection( $this->_byId ).')' );
		}
		if ( !empty( $this->_withPid ) ) {
			$_crawler->set_where( 'pid='.Core_Sql::fixInjection( $this->_withPid ) );
		}
		if ( !empty( $this->_levelMore ) ) {
			$_crawler->set_where( 'level>'.$this->_levelMore );
		}
		if ( empty( $this->_withOrder ) ) {
			$_crawler->set_order( ( empty( $this->_type['flg_sort'] )? 'title ASC':'priority DESC, title ASC' ) );
		} else {
			$_crawler->set_order( $this->_withOrder );
		}
		if ( !empty( $this->_withPagging ) ) {
			$this->_withPagging['rowtotal']=Core_Sql::getCell( $_crawler->get_result_counter( $_strTmp ) );
			$_crawler->set_paging( $this->_withPagging )->get_sql( $_strSql, $arrPg );
		} else {
			$_crawler->get_result_full( $_strSql );
		}
		if ( $this->_toSelect ) {
			$mixRes=Core_Sql::getKeyVal( $_strSql );
		} elseif ( !empty( $this->_byTitle )||!empty( $this->_byId ) ) {
			$mixRes=Core_Sql::getRecord( $_strSql );
		} else {
			$mixRes=Core_Sql::getAssoc( $_strSql );
			if ( !empty( $this->_type['flg_multilng'] )&&$this->_modeLang=='edit' ) {
				$this->result=&$mixRes;
				$this->getLng()->setImplant();
			}
		}
		$this->init();
		return !empty( $mixRes );
	}

	// заглушка. вместо этого см. getTree, getLevel и getList
	public function get( &$mixRes, &$arrPg ) {}

	// заглушка. вместо этого см. setCategory
	public function set( &$arrRes, &$arrErr, $_arrData=array() ) {}

	public function setPid( $_intPid=0 ) {
		$this->getNodeById( $this->_pid, $_intPid );
		return $this;
	}

	public function makeArray( $v ) {
		$this->_count=$this->_count-10;
		return array( 'title'=>$v, 'priority'=>$this->_count );
	}

	public function setData( $_arrData=array() ) {
		if ( !empty( $_arrData )&&!is_array( current( $_arrData ) ) ) { // если придёт array( 'title1', 'title2', ... , 'titleN' )
			$this->_count=count( $_arrData )*10+10; // это нужно для сохранения порядка в категориях как в массиве
			$_arrData=array_map( array( &$this, "makeArray" ), $_arrData ); // преобразуем в array( array( 'title'=>'title1' ), array( 'title'=>'title2' ), ... , array( 'title'=>'title3' ) )
		}
		return parent::setData( $_arrData );
	}

	public function setCategory() {
		$this->_data->setFilter()->setMask( $this->_fields );
		if ( empty( $this->_data->filtered ) ) {
			return true;
		}
		$_arrDel=array();
		foreach( $this->_data->filtered as $k=>$v ) {
			if ( !empty( $v['del'] ) ) {
				$_arrDel[]=$v['id'];
				continue;
			}
			if ( !$this->_data->setChecker( array(
				'title'=>empty( $v['title'] ),
				'priority'=>!empty( $v['priority'] )&&!is_numeric( $v['priority'] ),
			) )->check() ) {
				$this->_data->getErrors( $this->_errors[$k] );
				continue;
			}
			if ( empty( $v['id'] ) ) {
				unSet( $v['id'] );
				$v['pid']=$this->_pid['id'];
				$v['level']=$this->_pid['level']+1;
			}
			$this->_data->filtered[$k]['id']=Core_Sql::setInsertUpdate( $this->_table, $this->_data->getValidCurrent( $v ) );
			if ( !empty( $this->_type['flg_multilng'] ) ) {
				$this->getLng()->set( $this->_data->filtered[$k] );
			}
		}
		$this->del( $_arrDel, $this->_pid['id'] );
		return empty( $this->_errors );
	}

	/**
	 * Если к категориям привязаны какие-то элементы из других таблиц
	 * и надо их например удалить после удаления выбранных категорий
	 * можно взять тут список удалённых ids категорий
	 *
	 * @param array $arrRes - ids категорий
	 * @return object
	 */
	public function getTreeIds( &$arrRes ) {
		$arrRes=$this->_treeIds;
		return $this;
	}

	/**
	 * Удаление элементов дерева со всеми подэлементами
	 *
	 * @param mixed $_mixId - ids элементов
	 * @param integer $_intPid - если мы знаем pid удаляемых элементов нужно его указать
	 * инече на больших объёмах будет тормозить
	 * @return void
	 */
	public function del( $_mixId=array(), $_intPid=0 ) {
		if ( empty( $_mixId )||!$this->getAllNodes( $arrNodes, $_intPid ) ) {
			return;
		}
		$this->_treeIds=$_arrIds=is_array( $_mixId )? $_mixId:array( $_mixId );
		foreach( $_arrIds as $v ) {
			$this->getIdsFromNodeList( $arrNodes, $v );
		}
		if ( !empty( $this->_type['flg_multilng'] ) ) {
			$this->getLng()->setIds( $this->_treeIds )->del();
		}
		Core_Sql::setExec( 'DELETE FROM '.$this->_table.' WHERE id IN('.Core_Sql::fixInjection( $this->_treeIds ).')' );
	}

	/**
	 * Заполнение массива $this->_treeIds
	 *
	 * @param array $arrNodes - плоский список нод
	 * @param integer $_intId - id ноды с которой начинаем поиск ids подветки
	 * @return void
	 */
	private function getIdsFromNodeList( &$arrNodes, $_intId=0 ) {
		foreach( $arrNodes as $v ) {
			if ( $v['pid']==$_intId ) {
				$this->_treeIds[]=$v['id'];
				$this->getIdsFromNodeList( $arrNodes, $v['id'] );
			}
		}
	}
}
?>