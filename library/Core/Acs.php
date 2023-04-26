<?php


/**
 * Core functionality
 */
class Core_Acs {

	// в эти группы добавляются все права на инсталлируемые модули
	public static $mandatory=array( 'Super Admin' );

	// эти группы даются скриптам при необходимости, чтобы было понятно что работал скрипт
	public static $system=array( 'System Users' );

	// эти группы даются сразу пользователям поумолчению
	public static $minimal=array( 'Visitor' );

	// эти группа пользователей в программе обслуживания
	public static $maintenance='Maintenance';

	// группа для не активных пользователей.
	public static $inactive='Inactive';

	// эти группы инсталлируются когда система поднимается на новом месте
	public static $toCreate=array( 'Super Admin', 'System Users', 'Content Admin', 'Visitor' );

	public static $allDirect=array();

	public static $allReverse=array();

	public function __construct() {
		$this->init();
	}

	public static function populate( &$arr, $k, $v ) {
		if ( !in_array( $v, $arr ) ) {
			return false;
		}
		$arr=array_flip( $arr );
		$arr[$v]=$k;
		$arr=array_flip( $arr );
		return true;
	}

	private function init() {
		if( empty( self::$allDirect ) ){
			$_group=new Core_Acs_Groups();
			if ( !$_group->onlyIdAndSysNames()->getList( $_arrGroups )->checkEmpty() ) {
				// если нету self::$mandatory то создаём self::$toCreate
				if ( !$_group->setAutoMass( self::$toCreate ) ) {
					throw new Exception( Core_Errors::DEV.'|Can\'t create self::$toCreate groups' );
				}
				$_group->toSelect()->onlyIdAndSysNames()->getList( $_arrGroups );
			}
			foreach( $_arrGroups as $k=>$v ) {
				if ( self::populate( self::$mandatory, $k, $v ) ) {
					continue;
				}
				if ( self::populate( self::$system, $k, $v ) ) {
					continue;
				}
				self::populate( self::$minimal, $k, $v );
			}
			self::$allDirect=$_arrGroups;
			self::$allReverse=array_flip( $_arrGroups );
		}
	}

	/**
	 * Проверяет есть ли у пользователя права на запись.
	 * @return bool
	 */
	public static function haveWrite(){
		if(empty(Core_Users::$info)){
			return false;
		}
		return Core_Users::$info['flg_rights']==Core_Users_Management::WRITE_READ_RIGHT;
	}

	// минимальные права незалогиненому пользователю
	public static function setMinimalUserRight( &$arrRes ) {
		$arrRes['groups']=self::$minimal;
		self::getRights( $arrRes );
	}

	// возвращает подмассивы groups/right/right_parsed
	public static function getUserAccessRights( &$arrRes ) {
		$_group=new Core_Acs_Groups();
		if ( !$_group->withIds( $arrRes['id'] )->getGroupByUserId( $arrRes['groups'] ) ) {
			return;
		}
		self::getRights( $arrRes );
	}

	private static function getRights( &$arrRes ) {
		$_rights=new Core_Acs_Rights();
		if ( !$_rights->withGroup( array_keys( $arrRes['groups'] ) )->getRightWithGroup( $arrRightsList ) ) {
			return;
		}
		// приводим в удобную форму права пользователя
		foreach( $arrRightsList as $v ) {
			$_arr=explode( '_@_', $v );
			if ( count( $_arr )!=2 ) {
				continue;
			}
			$arrRes['right_parsed'][$_arr[0]][$_arr[1]]=1;
		}
		$arrRes['right']=array_flip( $arrRightsList );
	}

	// оставляет только те ссылки из дерева ссылок на которые есть права recursion TODO!!!
	// пока только для дерева которое выводит меню в админке
	public static function haveUrlTreeAccess( $arrTree=array() ) {
		if ( empty( $arrTree )||empty( Core_Users::$info ) ) {
			return array();
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
		usort( $arrRes, array( 'Core_Acs', 'cmp' ) );
		return $arrRes;
	}

	static private function cmp( $a, $b ) {
		return strnatcmp( $a['title'], $b['title'] );
	}

	// состоит ли полоьзователь в переданных группах
	public static function haveAccess( $_mixGroups=array() ) {
		if ( empty( $_mixGroups ) ) {
			return false;
		}
		$_arrGroups=is_array( $_mixGroups )? $_mixGroups:array( $_mixGroups );
		$_arr=array_intersect( $_arrGroups, Core_Users::$info['groups'] );
		return !empty( $_arr );
	}

	/**
	 * Проверяет есть ли у пользователя права.
	 * @static
	 * @param array $_mixRight
	 * @return bool
	 */
	public static function haveRight( $_arrRight=array() ){
		if( empty($_arrRight) ){
			return false;
		}
		foreach( $_arrRight as $_key=>$_array ){
			foreach( $_array as $_value ){
				if(!empty(Core_Users::$info['right_parsed'][$_key][$_value])){
					return true;
				}
			}
		}
		return false;
	}

	// изменился ли доступ у пользователя true - да;
	public static function changedAccess(){
		$_group=new Core_Acs_Groups();
		$_group->withIds( Core_Users::$info['id'] )->getGroupByUserId( $_arrGroups );
		return ($_arrGroups!==Core_Users::$info['groups']);
	}

	// чекает имеет ли пользователь доступ к данному модулю-экшену
	public static function haveActionAccess( $_arrMs=array() ) {
		if ( empty( $_arrMs )||count( $_arrMs )<2 ) { // может быть только action_vars но без name и action
			return false;
		}
		return !empty( Core_Users::$info['right'][($_arrMs['name'].'_@_'.$_arrMs['action'])] );
	}

	// отдаёт подзапрос который выберет пользователей имеющих требуемые права
	public static function haveRightAccess() {
		$_arrRights=func_get_args();
		if ( !empty( $_arrRights[0] )&&is_array( $_arrRights[0] ) ) {
			$_arrRights=$_arrRights[0];
		}
		if ( empty( $_arrRights ) ) {
			throw new Exception( Core_Errors::DEV.'|$_arrRights empty' );
		}
		$_arrIds=Core_Sql::getField( 'SELECT group_id FROM u_rights2group WHERE rights_id IN(SELECT id FROM u_rights WHERE sys_name IN('.Core_Sql::fixInjection( $_arrRights ).'))' );
		if ( empty( $_arrIds ) ) {
			throw new Exception( Core_Errors::DEV.'|Groups not finded' );
		}
		return 'SELECT user_id FROM u_link WHERE group_id IN('.Core_Sql::fixInjection( $_arrIds ).')';
	}

	public static function onlyHaveAccess( $_arrRights ) {
		$result = array_diff( Core_Users::$info['groups'], $_arrRights );
		if( empty( $result ) ) 
			return true;
		return false;
	}
}
?>