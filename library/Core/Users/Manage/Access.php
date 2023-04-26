<?php
class Core_Users_Manage_Access {

	public function __construct( $_arrSet=array() ) {
		$this->objR=Core_Acs::getInstance(); // объект работы с правами и группами
	}

	// u_getprofilegroups
	public function getGroups( &$arrRes, $_intId=0 ) {
		if ( empty( $_intId ) ) {
			return false;
		}
		$_arrIds=Core_Sql::getField( 'SELECT group_id FROM u_link WHERE user_id="'.$_intId.'"' );
		if ( empty( $_arrIds ) ) {
			return false;
		}
		return $this->objR->r_get_sys_name_by_ids( $arrRes, $_arrIds );
	}

	// права пользователя по ids групп
	// u_getprofilerights
	public function getRights( &$arrRightsList, &$arrRightsTree, $_arrG=array() ) {
		$arrRightsList=array();
		if ( empty( $_arrG ) ) {
			return false;
		}
		$_arr=array();
		foreach( $_arrG as $k=>$v ) {
			$this->objR->r_getright2group( $_arrLink, $k );
			$_arr+=$_arrLink;
		}
		if ( !$this->objR->r_getrights( $arrRightsList, array( 'mode'=>'by_ids', 'ids'=>array_keys( $_arr ) ) ) ) {
			return false;
		}
		// приводим в удобную форму права пользователя
		foreach( $arrRightsList as $v ) {
			$_arr=explode( '_@_', $v );
			if ( count( $_arr )!=2 ) {
				continue;
			}
			$arrRightsTree[$_arr[0]][$_arr[1]]=1;
		}
		$arrRightsList=array_flip( $arrRightsList );
		return true;
	}

	/* Присавивание профайлу групп (прав)
	 * предыдущие настройки удаляются перед этим
	 *
	 * @param integer $_intId u_users.id пользователя
	 * @param mixed $_mixGroups строка или массив строк с названиями групп. если тут пусто значит удаляем ссылки на группы
	 * @return boolean
	 */
	public function setGroups( $_intId=0, $_mixGroups=array() ) {
		if ( empty( $_intId ) ) {
			return false;
		}
		$arrGrupsIds=array();
		if ( !empty( $_mixGroups ) ) {
			if ( !is_array( $_mixGroups ) ) {
				$_mixGroups=array( $_mixGroups );
			}
			$this->objR->r_get_ids_by_title( $arrGrupsIds, $_mixGroups );
		}
		return $this->setGroupsByIds( $_intId, $arrGrupsIds );
	}

	public function setGroupsByIds( $_intId=0, $_mixGroups=array() ) {
		if ( empty( $_intId ) ) {
			return false;
		}
		Core_Sql::setExec( 'DELETE FROM u_link WHERE user_id="'.$_intId.'"' );
		if ( empty( $_mixGroups ) ) {
			return false;
		}
		$arrGrupsIds=is_array( $_mixGroups )? $_mixGroups:array( $_mixGroups );
		foreach( $arrGrupsIds as $k=>$v ) {
			$arrRow[]=array( 'group_id'=>$k, 'user_id'=>$_intId );
		}
		Core_Sql::setMassInsert( 'u_link', $arrRow );
		return true;
	}

	public function setRootUserGroups( $_intRootId=0 ) {
		return $this->setGroups( $_intRootId, $this->objR->r_mandatory_groups );
	}

	public function setSysUserGroups( $_intSysId=0 ) {
		return $this->setGroups( $_intSysId, $this->objR->r_system_groups );
	}

	public function setMinimalUserRight( &$arrRes ) {
		$this->objR->r_get_ids_by_title( $arrRes['groups'], $this->objR->r_minimal_groups );
		$this->getRights( $arrRes['right'], $arrRes['right_parsed'], $arrRes['groups'] );
	}

	// оставляет только те ссылки из дерева ссылок на которые есть права recursion TODO!!!
	// пока только для дерева которое выводит меню в дминке
	public function checkUrlTreeRights( &$arrRes, $arrTree=array() ) {
		if ( empty( $arrTree )||empty( Core_Users::$info ) ) {
			return false;
		}
		$arrTree=$arrTree[0]['node'];
		foreach( $arrTree as $k=>$v ) {
			if ( empty( $v['node'] ) ) {
				continue;
			}
			$_arrA=array();
			foreach( $v['node'] as $i=>$j ) {
				// если нету прав на экшн и экшн попап или безтемплэйтный
				if ( empty( Core_Users::$info['right_parsed'][$j['name']][$j['action']] )||!empty( $j['flg_tpl'] ) ) {
					continue;
				}
				$_arrA[$i]=$j;
			}
			if ( empty( $_arrA ) ) {
				continue;
			}
			$arrRes[$k]=$v;
			$arrRes[$k]['node']=$_arrA;
		}
		return !empty( $arrRes );
	}
}
?>