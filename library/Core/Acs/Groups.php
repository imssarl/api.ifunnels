<?php


/**
 * Access Group control
 */
class Core_Acs_Groups extends Core_Data_Storage {

	public static $tableLink='u_link';

	protected $_table='u_groups';

	protected $_fields=array( 'id', 'sys_name', 'title', 'description' );

	private $_del=array();

	private $_move=array();

	/**
	 * аспект кторый вызывается до выполнения каждого set() в setMass()
	 * после переназначения тут например можно организовать накапливание данных для пост обработки в afterSetMass()
	 *
	 * @return boolean
	 */
	protected function beforeSetMass( $k, $_arrRow=array() ) {
		if ( !empty( $_arrRow['del'] ) ) {
			$this->_del[]=$_arrRow['id'];
			$this->_move[]=array('del'=>$_arrRow['id'],'moveto'=>$_arrRow['moveto'],'sys_name'=>$_arrRow['sys_name']);
			return false;
		}
		return true;
	}

	/**
	 * аспект кторый вызывается после выполнения всех set() в setMass()
	 * после переназначения тут например можно сделать какие-либо действия с данными накопленными в beforeSetMass()
	 *
	 * @return boolean
	 */
	protected function afterSetMass() {
		if ( !empty( $this->_del ) ) {
			$this->withIds( $this->_del )->del();
			$this->_del=array();
		}
		return true;
	}

	/**
	 * удаление одной или нескольких записей
	 *
	 * @return boolean
	 */
	public function del() {
		if ( empty( $this->_withIds ) ) {
			return false;
		}
		$_users=new Core_Users_Management();
		foreach( $this->_move as $_item ){
			if( !in_array($_item['del'],$this->_withIds) ){
				continue;
			}
			if( $_item['moveto']==0 ){ // Удаляем пользователей для группы $_item['sys_name']
				$_users->withGroups(array($_item['sys_name']))->onlyIds()->getList( $arrIds );
				$_users->withIds( $arrIds )->del();
			} else { // Переносим пользователей в группу $_item['moveto']
				$_userIds=Core_Sql::getField('SELECT user_id FROM '.self::$tableLink.' WHERE group_id='.$_item['moveto']);
				if(!empty($_userIds)){ // удалить возможные дубликаты линков. в новой группе пользователь уже может быть.
					Core_Sql::setExec('DELETE FROM '.self::$tableLink.' WHERE group_id='.$_item['del'].' AND user_id IN ('. Core_Sql::fixInjection($_userIds) .')' );
				}
				Core_Sql::setExec('UPDATE '.self::$tableLink.' SET group_id='.$_item['moveto'].' WHERE group_id='.$_item['del'] );
			}
		}
		Core_Sql::setExec( 'DELETE FROM '.$this->_table.' WHERE id IN('.Core_Sql::fixInjection( $this->_withIds ).')' );
		$this->init();
		return true;
	}

	/**
	 * фильтр: ищем группы по системному имени
	 *
	 * @var array
	 */
	protected $_bySysName=array();

	/**
	 * фильтр: выборка id=>sys_name
	 *
	 * @var array
	 */
	protected $_onlyIdAndSysNames=false;

	protected function init() {
		parent::init();
		$this->_bySysName=array();
		$this->_onlyIdAndSysNames=false;
		$this->_move=array();
	}

	public function bySysName( $_arr=array() ) {
		if ( empty( $_arr ) ) {
			return $this;
		}
		$this->_bySysName=$_arr;
		return $this;
	}

	public function onlyIdAndSysNames() {
		$this->_onlyIdAndSysNames=true;
		return $this;
	}

	protected function assemblyQuery() {
		if ( $this->_onlyIds ) {
			$this->_crawler->set_select( 'd.id' );
		} elseif ( $this->_toSelect ) {
			$this->_crawler->set_select( 'd.id, d.title' );
			$this->withOrder( 'd.id--dn' );
		} elseif ( $this->_onlyIdAndSysNames ) {
			$this->_crawler->set_select( 'd.id, d.sys_name' );
			$this->_toSelect=true; // чтобы сработало Core_Sql::getKeyVal в парент над как-то гибче наверно сделать TODO!!! 26.01.2012
		} else {
			$this->_crawler->set_select( 'd.*' );
		}
		$this->_crawler->set_from( $this->_table.' d' );
		if ( !empty( $this->_withIds ) ) {
			$this->_crawler->set_where( 'd.id IN ('.Core_Sql::fixInjection( $this->_withIds ).')' );
		}
		if ( !empty( $this->_bySysName ) ) {
			$this->_crawler->set_where( 'd.sys_name IN ('.Core_Sql::fixInjection( $this->_bySysName ).')' );
		}
		if ( $this->_onlyOwner&&$this->getOwnerId( $_intId ) ) {
			$this->_crawler->set_where( 'd.user_id='.$_intId );
		}
		if ( !( $this->_onlyOne||$this->_onlyCell ) ) {
			$this->_crawler->set_order_sort( $this->_withOrder );
		}
		if ( !empty( $this->_withGroup ) ) {
			$this->_crawler->set_group( $this->_withGroup );
		}
	}

	public function setAutoMass( $_arrGrp=array() ) {
		if ( empty( $_arrGrp ) ) {
			return false;
		}
		foreach( $_arrGrp as $v ) {
			$arrDta[]=array( 'sys_name'=>$v, 'title'=>$v );
		}
		Core_Sql::setMassInsert( $this->_table, $arrDta );
		return true;
	}

	public function getGroupByUserId( &$arrRes ) {
		if ( empty( $this->_withIds ) ) {
			return false;
		}
		$arrRes=Core_Sql::getKeyVal( 'SELECT id, sys_name FROM '.$this->_table.' WHERE id IN(
			SELECT group_id FROM '.self::$tableLink.' WHERE user_id='.Core_Sql::fixInjection( $this->_withIds ).') ORDER BY id' );
		$this->init();
		return !empty( $arrRes );
	}
	
	public function getGroupsByUserIds( &$arrRes ) {
		if ( empty( $this->_withIds ) ) {
			return false;
		}
		$arrRes=Core_Sql::getAssoc( 'SELECT a.sys_name, b.user_id, b.group_id FROM '.$this->_table.' a LEFT JOIN '.self::$tableLink.' b ON  a.id = b.group_id WHERE b.user_id IN ('.Core_Sql::fixInjection( $this->_withIds ).') ORDER BY b.user_id' );
		$this->init();
		return !empty( $arrRes );
	}
	
	public function addGroupByName( $_strName ){
		if ( empty( $this->_withIds ) ) {
			return false;
		}
		$_id=$this->_withIds;
		$this->init();
		if( !$this->onlyOne()->bySysName( array($_strName) )->getList( $arrRes )->checkEmpty() ){
			return false;
		}
		Core_Sql::setExec('DELETE FROM '.self::$tableLink.' WHERE user_id='.Core_Sql::fixInjection($_id).' AND group_id='.$arrRes['id']);
		Core_Sql::setInsert(self::$tableLink,array('group_id'=>$arrRes['id'],'user_id'=>$_id ));
		return true;
	}

	public function removeGroupByName( $_strName ){
		if ( empty( $this->_withIds ) ) {
			return false;
		}
		$_id=$this->_withIds;
		$this->init();
		if( !$this->onlyOne()->bySysName( array($_strName) )->getList( $arrRes )->checkEmpty() ){
			return false;
		}
		Core_Sql::setExec('DELETE FROM '.self::$tableLink.' WHERE user_id='.Core_Sql::fixInjection($_id).' AND group_id='.$arrRes['id']);
		return true;
	}

	public function setGroupByIds( $_mixGroups=array() ) {
		if ( empty( $this->_withIds ) ) {
			return false;
		}
		Core_Sql::setExec( 'DELETE FROM '.self::$tableLink.' WHERE user_id='.Core_Sql::fixInjection( $this->_withIds ) );
		if ( empty( $_mixGroups ) ) {
			$this->init();
			return false;
		}
		$_mixGroups=is_array( $_mixGroups )? $_mixGroups:array( $_mixGroups=>1 );
		foreach( $_mixGroups as $k=>$v ) {
			$arrRow[]=array( 'group_id'=>$k, 'user_id'=>$this->_withIds );
		}
		Core_Sql::setMassInsert( self::$tableLink, $arrRow );
		$this->init();
		return true;
	}

	public function setGroupByName( $arrGroups=array() ) {
		if ( empty( $this->_withIds ) ) {
			return false;
		}
		$arrGroups=is_array( $arrGroups )? $arrGroups:array( $arrGroups );
		foreach( Core_Acs::$allDirect as $k=>$v ) {
			Core_Acs::populate( $arrGroups, $k, $v );
		}
		return $this->setGroupByIds( $arrGroups );
	}

	/* need refactoring TODO!!! 22.11.2011 */

	public static function getIdsBySysName( &$arrRes, $_arrSys=array() ) {
		if ( empty( $_arrSys ) ) {
			return false;
		}
		$arrRes=Core_Sql::getField( 'SELECT id FROM u_groups WHERE sys_name IN('.Core_Sql::fixInjection( $_arrSys ).')' );
		return !empty( $arrRes );
	}

	public static function getGroupsWithoutVisitorListSys( &$arrRes ) {
		$arrRes=Core_Sql::getKeyVal( 'SELECT sys_name, title FROM u_groups WHERE sys_name!="Visitor" ORDER BY title' );
	}

	public static function getGroupsWithoutVisitorListIds( &$arrRes ) {
		$arrRes=Core_Sql::getAssoc( 'SELECT id, title FROM u_groups WHERE title!="Visitor" ORDER BY id' );
		return !empty( $arrRes );
	}
}
?>