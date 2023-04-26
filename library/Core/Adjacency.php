<?php


/**
 * Db Adjacency List tree
 * @internal надо разделить. сделать абстракцию TODO!!!21.11.2008
 */


class Core_Adjacency extends Core_Services {
	/**
	* имя таблицы, хранящей структуру Adjacency List tree
	* @public string
	*/
	public $table='tree';
	/**
	* максимальный уровень вложенности дерева
	* @public integer
	*/
	public $nested=0;

	public $root_id=0;
	/**
	* constructor - в конструкторе устанавливаем имя таблицы с которой будем работать
	* @param string $_strTable in
	* @return none
	*/
	function __construct( $_strTable='' ) {
		if ( !empty( $_strTable ) ) {
			$this->table=$_strTable;
		}
		$this->root_info( $_arrRoot );
		$this->root_id=$_arrRoot['id'];
	}
	/**
	* корень дерева - в нашем случае корень это pid=0 и level=0
	* если корня ещё нету то создаём
	* @param array $arrRes out
	* @return boolean
	*/
	function root_info( &$arrRes ) {
		if ( !empty( $GLOBALS['root_node'][$this->table] ) ) {
			$arrRes=$GLOBALS['root_node'][$this->table];
			return !empty( $arrRes['id'] );
		}
		$arrRes=Core_Sql::getRecord( 'SELECT * FROM '.$this->table.' WHERE level=0 AND pid=0' );
		if ( empty( $arrRes ) ) {
			$arrRes['level']=$arrRes['pid']=0;
			$arrRes['id']=Core_Sql::setInsert( $this->table, $arrRes );
		}
		$GLOBALS['root_node']=$arrRes;
		return !empty( $arrRes['id'] );
	}
	/**
	* информация по ноде
	* если $_intId не указан возвращается информация о корне дерева
	* @param array $arrRes out
	* @param integer $_intId in
	* @return boolean
	*/
	function node_info( &$arrRes, $_intId=0 ) {
		if ( empty( $_intId ) ) {
			$this->root_info( $arrRes );
		} else {
			$arrRes=Core_Sql::getRecord( 'SELECT * FROM '.$this->table.' WHERE id="'.$_intId.'"' );
		}
		return !empty( $arrRes['id'] );
	}
	/**
	* создание ноды
	* если указан $_intId то нода создаётся как child к этому паренту иначе к корню
	* @param integer $_intId in
	* @return boolean
	*/
	function node_set( &$intNod, $_intId=0 ) {
		if ( !$this->node_info( $_arrPar, $_intId ) ) {
			return false;
		}
		$intNod=Core_Sql::setInsert( $this->table, array(
			'pid'=>$_arrPar['id'],
			'level'=>++$_arrPar['level']
		) );
		return !empty( $intNod );
	}
	/**
	* переназначение ноды к другому родителю
	* $_arrDat=array( 'pid', 'node_id' )
	* @param array $_arrDat in
	* @return boolean
	*/
	function node_reassign( $_arrDat=array() ) {
		if ( empty( $_arrDat )||!$this->node_info( $_arrPar, $_arrDat['pid'] ) ) {
			return false;
		}
		Core_Sql::setUpdate( $this->table, array(
			'id'=>$_arrDat['node_id'],
			'pid'=>$_arrPar['id'],
			'level'=>++$_arrPar['level']
		) );
		return true;
	}
	/**
	* удаление ноды
	* @param integer $_intId in
	* @return boolean
	*/
	function node_delete( $_intId=0 ) {
		if ( empty( $_intId )||!$this->node_info( $_arrNod, $_intId ) ) {
			return false;
		}
		// переназначение child к другому паренту
		Core_Sql::setExec( 'UPDATE '.$this->table.' SET pid="'.$_arrNod['pid'].'", level=level-1 WHERE pid="'.$_intId.'"' );
		// удаление узла
		Core_Sql::setExec( 'DELETE FROM '.$this->table.' WHERE id="'.$_intId.'"' );
		return true;
	}
	/**
	* сортировка всего уровня одной ветки и апдэйт
	* @param array $_intId in кого поставить
	* @param integer $_intAfterPos in после какого поставить
	* @return boolean
	*/
	function node_posset( $_intId=0, $_intAfterPos='first' ) {
		if ( empty( $_intId )||!$this->tree_branch( $_arrIds, $_intId ) ) {
			return false;
		}
		$_arrNew=$_arrIds;
		if ( $_intAfterPos=='first' ) { // вставка в начало
			array_unshift( $_arrNew, $_intId );
		} elseif ( $_arrIds[count($_arrIds)-1]==$_intAfterPos||$_intAfterPos=='end' ) { // присоединяем в конец
			$_arrNew[]=$_intId;
		} else { // вставляем в серёдку
			$_arrNew=array();
			foreach ( $_arrIds as $k=>$v ) {
				$_arrNew[]=$v;
				if ( $v==$_intAfterPos ) {
					$_arrNew[]=$_intId;
				}
			}
		}
		$j=0;
		foreach ( $_arrNew as $v ) {
			Core_Sql::setUpdate( $this->table, array( 'id'=>$v, 'sort'=>$j ) );
			$j++;
		}
		return true;
	}
	/**
	* сортировка вверх и вниз на один элемент
	* @param integer $_intId in кого двигаем
	* @param string $_strMode in куда двигаем ('up','down')
	* @return boolean
	*/
	function node_posmov( $_intId=0, $_strMode='up' ) {
		if ( !$this->node_near( $_arrNear, $_arrNod, $_intId, $_strMode ) ) {
			return false;
		}
		Core_Sql::setUpdate( $this->table, array( 'id'=>$_arrNod['id'], 'sort'=>$_arrNear['sort'] ) );
		Core_Sql::setUpdate( $this->table, array( 'id'=>$_arrNear['id'], 'sort'=>$_arrNod['sort'] ) );
		return true;
	}
	/**
	* взять ближайший элемент к данному одного с ним уровня
	* @param array $arrNear out инфа о ближайшем
	* @param array $arrNod out инфа о данном
	* @param integer $_intId in данный элемент
	* @param string $_strMode in откуда брать ближнего ('up','down')
	* @return boolean
	*/
	function node_near( &$arrNear, &$arrNod, $_intId=0, $_strMode='up' ) {
		if ( empty( $_intId )||!$this->node_info( $arrNod, $_intId ) ) {
			return false;
		}
		if ( $_strMode=='up' ) {
			$arrNear=Core_Sql::getRecord( '
				SELECT * FROM '.$this->table.'
				WHERE pid="'.$arrNod['pid'].'" AND sort<"'.$arrNod['sort'].'"
				ORDER BY sort DESC
				LIMIT 1
			' );
		} elseif ( $_strMode=='down' ) {
			$arrNear=Core_Sql::getRecord( '
				SELECT * FROM '.$this->table.'
				WHERE pid="'.$arrNod['pid'].'" AND sort>"'.$arrNod['sort'].'"
				ORDER BY sort
				LIMIT 1
			' );
		}
		return !empty( $arrNear );
	}
	/**
	* удаление ветки
	* $arrIds array массив с перечислением id удаляемой ветки ( для удаление данных из других таблиц )
	* $_intId - id нода с которой начинается удаляемая ветвь (если 0 то удаляется всё дерево!!!)
	* @param array $arrIds out
	* @param integer $_intId in
	* @return boolean
	*/
	// тут вроде вылезает баг - при удалении узла без потомков не уменьшается уровень в дереве TODO!!!
	function tree_delete( &$arrIds, $_intId=0 ) {
		if ( !$this->node_info( $arrNod, $_intId ) ) {
			return false;
		}
		$this->tree_getall( $arrTree, $arrNod );
		$arrIds[]=$_intId; // текущий
		$this->tree_ids( $arrIds, $arrTree, $_intId ); //остальные узлы
		if ( !empty( $arrIds ) ) {
			Core_Sql::setExec( 'DELETE FROM '.$this->table.' WHERE id IN("'.join( '", "', $arrIds ).'")' );
		}
		return true;
	}
	/**
	* собирает id-массив элементов ветви которая начинается с $_intId
	* @param array $arrIds out
	* @param array $arrTree in
	* @param integer $_intId in
	* @return array
	*/
	function tree_ids( &$arrIds, $arrTree, $_intId=0 ) {
		foreach( $arrTree as $v ) {
			if ( $v['pid']==$_intId ) {
				$arrIds[]=$v['id'];
				$this->tree_ids( $arrIds, $arrTree, $v['id'] );
			}
		}
	}

	function check_node_in_branch( $_arrTree=array(), $_intBranchNodeId=0, $_intNodeId=0 ) {
		if ( empty( $_arrTree )||empty( $_intBranchNodeId )||empty( $_intNodeId ) ) {
			return false;
		}
		if ( !$this->node_info( $arrNod, $_intBranchNodeId ) ) {
			return false;
		}
		$this->tree_getall( $arrTree, $arrNod );
		$_arrIds[]=$_intBranchNodeId; // текущий
		$this->tree_ids( $_arrIds, $arrTree, $_intBranchNodeId ); //остальные узлы
		if ( empty( $_arrIds ) ) {
			return false;
		}
		return in_array( $_intNodeId, $_arrIds );
	}
	/**
	* получаем массив данных, уровнем не меньше чем у начальной ноды
	* @param array $arrTree out
	* @param array $_arrNod in
	* @return none
	*/
	function tree_getall( &$arrTree, $_arrNod ) {
		$arrTree=Core_Sql::getAssoc( 'SELECT * FROM '.$this->table.' WHERE level>="'.$_arrNod['level'].'"' );
	}
	/**
	* генерит структуру дерева по $arrTree, обязательно должны присутствовать id, pid, level
	* заданной глубины (если задан 'level_to') в массиве $_arrSet (array( 'id', 'level_to' ))
	* @param array $arrTree in
	* @param array $_arrSet in
	* @return array
	*/
	function tree_get( &$arrTree, $_arrSet=array( 'id'=>0 ) ) {
		$arrRes=array();
		foreach( $arrTree as $v ) {
			if ( $v['pid']==$_arrSet['id'] ) {
				// не показываем выбранный элемент в дереве
				if ( !empty( $_arrSet['current_id'] )&&$v['id']==$_arrSet['current_id'] ) {
					continue;
				}
				$arrRes[]=$v;
			}
		}
		foreach( $arrRes as $k=>$i ) {
			if ( !empty( $_arrSet['level_to'] )&&$_arrSet['level_to']<=$i['level'] ) {
				continue;
			}
			if ( $i['level']>$this->nested ) {
				$this->nested++;
			}
			$_arrSet['id']=$i['id'];
			if ( count( ( $arrTmp=$this->tree_get( $arrTree, $_arrSet ) ) )>0 ) {
				$arrRes[$k]['node']=$arrTmp;
			}
		}
		return $arrRes;
	}
	/**
	* ПУТЬ К ЗАДАННОЙ ВЕРШИНЕ
	* 1) возвращает путь к заданной вершине ( $_arrSet['node'] )
	* 2) сравнивать можно по любому полю, название передаётся ( $_arrSet['key'] )
	* 3) если нужно получить массив из набора определённых полей каждого элемента
	* можно передать название этого поля ( $_arrSet['get'] ), по дефолту возвратиться весь элемент,
	* естественно без поддерева
	* @param array $arrPath out
	* @param array $arrTree in
	* @param array $_arrSet in
	* @return array
	*/
	function tree_path( &$arrPath, &$arrTree, $_arrSet ) {
		if ( empty( $_arrSet['key'] )&&empty( $_arrSet['node'] ) ) {
			return false;
		}
		foreach( $arrTree as $v ) {
			if ( $v[$_arrSet['key']]==$_arrSet['node'] ) {
				unSet( $v['node'] );
				$arrPath[]=empty( $_arrSet['get'] ) ? $v:$v[$_arrSet['get']];
				return true;
			}
			if ( !empty( $v['node'] ) ) {
				if ( $this->tree_path( $arrPath, $v['node'], $_arrSet ) ) {
					unSet( $v['node'] );
					$arrPath[]=empty( $_arrSet['get'] ) ? $v:$v[$_arrSet['get']];
					return true;
				}
			}
		}
	}
	/**
	* Отдаёт поддерево вычисленное сравнением массивов $_arrSet и текущей ноды (вместе с ней)
	* @param array $arrRes out
	* @param array $arrTree in
	* @param array $_arrSet in
	* @return array
	*/
	public static function tree_get_subtree( &$arrRes, &$arrTree, $_arrSet=array() ) {
		if ( empty( $arrTree )||empty( $_arrSet ) ) {
			return false;
		}
		foreach( $arrTree as $v ) {
			// сравниваем массивы
			$_arrTest=$_arrSet;
			foreach( $_arrSet as $k=>$i ) {
				if ( !empty( $v[$k] )&&$v[$k]==$i ) {
					unSet( $_arrTest[$k] );
					if ( empty( $_arrTest ) ) {
						$arrRes=$v;
						return true;
					}
				}
			}
			if ( !empty( $v['node'] )&&self::tree_get_subtree( $arrRes, $v['node'], $_arrSet ) ) {
				return true;
			}
		}
	}
	/**
	* берём ids по pid
	* @param array $arrIds out
	* @param integer $_intParent in
	* @return boolean
	*/
	function tree_childids( &$arrIds, $_intParent=0 ) {
		if ( empty( $_intParent ) ) {
			return false;
		}
		$arrIds=Core_Sql::getField( 'SELECT id FROM '.$this->table.' WHERE pid="'.$_intParent.'"' );
		return !empty( $arrIds );
	}
	/**
	* берём ids текущей ветки текущего уровня без данного узла
	* @param array $arrIds out
	* @param integer $_intId in
	* @return boolean
	*/
	function tree_branch( &$arrIds, $_intId=0 ) {
		if ( empty( $_intId )||!$this->node_info( $_arrNode, $_intId ) ) {
			return false;
		}
		$arrIds=Core_Sql::getField( 'SELECT id FROM '.$this->table.' WHERE pid="'.$_arrNode['pid'].'" AND id!="'.$_intId.'" ORDER BY sort' );
		return !empty( $arrIds );
	}

	public function get_one_level_by_node( &$arrRes, $_arrTree, $_arrSet=array() ) {
		$_arrSet['only_one_level']=true;
		return $this->get_from_node( $arrRes, $_arrTree, $_arrSet );
	}

	/**
	* отдаёт ветку начинающуюся от заданного нода, без самого нода
	* @param array $arrRes out - полученная ветка
	* @param array $_arrTree in - дерево по которому смотрим
	* @param integer $_arrSet in - насторйки: id какой элемент использовать 
	* в качетве идентификатора, val чему он будет равен, sub_node какой 
	* элемент характерезует начало новой ветки
	* @return boolean
	*/
	public function get_from_node( &$arrRes, $_arrTree, $_arrSet=array() ) {
		foreach( $_arrTree as $v ) {
			if ( !empty( $v[$_arrSet['id']] )&&$v[$_arrSet['id']]==$_arrSet['val'] ) {
				if ( !empty( $_arrSet['only_one_level'] ) ) {
					foreach( $v[$_arrSet['sub_node']] as $n ) {
						unSet( $n[$_arrSet['sub_node']] );
						$arrRes[]=$n;
					}
				} else {
					$arrRes=$v[$_arrSet['sub_node']];
				}
				return true;
			}
			if ( is_array( $v[$_arrSet['sub_node']] )&&!empty( $v[$_arrSet['sub_node']] ) ) {
				if ( $this->get_one_level_by_node( $arrRes, $v[$_arrSet['sub_node']], $_arrSet ) ) {
					return true;
				}
			}
		}
	}
}
/*
    *  Как загрузить все дерево?
    * Как загрузить один уровень?
    * Как загрузить сразу и путь и уровень?
    * Как загрузить поддерево?
*/
?>