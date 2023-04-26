<?php


/**
 * Access Right control
 */
class Core_Mailer_Events_Email extends Core_Data_Storage {

	public static $tableLink='event_link';

	protected $_table='event_email';

	protected $_fields=array( 'id', 'email', 'name', 'added' );

	// ids прав которые были помечены к удалению
	private $_del=array();

	// ids прав которые были до этого обновления
	private $_old=array();

	/**
	 * аспект кторый вызывается до выполнения каждого set() в setMass()
	 * после переназначения тут например можно организовать накапливание данных для пост обработки в afterSetMass()
	 *
	 * @return boolean
	 */
	protected function beforeSetMass( $k, $_arrRow=array() ) {
		if ( !empty( $_arrRow['del'] ) ) {
			$this->_del[]=$_arrRow['id'];
			return false;
		}
		if ( !empty( $_arrRow['id'] ) ) {
			$this->_old[]=$_arrRow['id'];
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
		// удаляем отмеченные
		if ( !empty( $this->_del ) ) {
			$this->withIds( $this->_del )->del();
			$this->_del=array();
		}
		// привязываем только новые права к группам Core_Acs::$mandatory
		$this->withIdsExtract( $this->_data->filtered );
		$_arrIds=array_diff( $this->_withIds, $this->_old );
		if ( !empty( $_arrIds ) ) {
			$this->addRightsLink( array_keys( Core_Acs::$mandatory ), $_arrIds );
		}
		return true;
	}

	/**
	 * получение ids из массива вида array( array( 'id'=>... ), array(...) )
	 *
	 * @return object
	 */
	public function withIdsExtract( $_arrRight=array() ) {
		if ( empty( $_arrRight ) ) {
			return $this;
		}
		$_arrIds=array();
		foreach( $_arrRight as $v ) {
			if ( empty( $v['id'] ) ) {
				continue;
			}
			$_arrIds[]=$v['id'];
		}
		return $this->withIds( $_arrIds );
	}

	/**
	 * удаление прав и ссылок на группы
	 *
	 * @return boolean
	 */
	public function del() {
		if ( empty( $this->_withIds ) ) {
			return false;
		}
		Core_Sql::setExec( '
			DELETE r, l FROM '.$this->_table.' r
			LEFT JOIN '.self::$tableLink.' l ON l.rights_id=r.id
			WHERE r.id IN('.Core_Sql::fixInjection( $this->_withIds ).')
		' );
		$this->init();
		return true;
	}

	/**
	 * несколько прав привязываем к группе
	 * используется только при полном редактировании группы
	 *
	 * @return boolean
	 */
	public function rights2group() {
		if ( !$this->_data->setFilter()->setChecker( array(
			'group_id'=>empty( $this->_data->filtered['group_id'] ),
			'rights'=>empty( $this->_data->filtered['rights'] ),
		))->check() ){
			$this->_data->getErrors( $this->_errors );
			return false;
		}
		Core_Sql::setExec( 'DELETE FROM '.self::$tableLink.' WHERE group_id="'.$this->_data->filtered['group_id'].'"' );
		return $this->addRightsLink( $this->_data->filtered['group_id'], array_keys( $this->_data->filtered['rights'] ) );
	}

	/**
	 * несколько групп привязываем к одному праву
	 * используется только при полном редактировании права
	 *
	 * @return boolean
	 */
	public function groups2right() {
		if ( !$this->_data->setFilter()->setChecker( array(
			'right_id'=>empty( $this->_data->filtered['right_id'] ),
			'groups'=>empty( $this->_data->filtered['groups'] ),
		))->check() ){
			$this->_data->getErrors( $this->_errors );
			return false;
		}
		Core_Sql::setExec( 'DELETE FROM '.self::$tableLink.' WHERE rights_id="'.$this->_data->filtered['right_id'].'"' );
		return $this->addRightsLink( array_keys( $this->_data->filtered['groups'] ), $this->_data->filtered['right_id'] );
	}

	/**
	 * все возможноые варианты из двух массивов
	 * и вставка полученных вариантов линк
	 *
	 * @return boolean
	 */
	private function addRightsLink( $_arrGrp=array(), $_arrRgt=array() ) {
		if ( empty( $_arrGrp )||empty( $_arrRgt ) ) {
			return false;
		}
		if ( !is_array( $_arrGrp ) ) {
			$_arrGrp=array( $_arrGrp );
		}
		if ( !is_array( $_arrRgt ) ) {
			$_arrRgt=array( $_arrRgt );
		}
		foreach( $_arrGrp as $_intGroupId ) {
			foreach( $_arrRgt as $_intRightId ) {
				$arrIns[]=array( 'group_id'=>$_intGroupId, 'rights_id'=>$_intRightId );
			}
		}
		Core_Sql::setMassInsert( self::$tableLink, $arrIns );
		return true;
	}

	/**
	 * фильтр: ищем права по названию модуля
	 *
	 * @var string
	 */
	protected $_likeModuleName='';

	/**
	 * фильтр: ищем права по системному имени
	 *
	 * @var array
	 */
	protected $_bySysName=array();

	/**
	 * фильтр: ищем по id группы
	 *
	 * @var integer
	 */
	protected $_withGroup=0;

	/**
	 * фильтр: ищем по id группы
	 *
	 * @var integer
	 */
	protected $_withRight=0;

	protected $_withIdAndSys=false;

	protected function init() {
		parent::init();
		$this->_withIdAndSys=false;
		$this->_likeModuleName='';
		$this->_bySysName=array();
		$this->_withGroup=0;
		$this->_withRight=0;
	}

	public function withIdAndSys() {
		$this->_withIdAndSys=true;
		return $this;
	}

	public function withGroup( $_int=0 ) {
		if ( empty( $_int ) ) {
			return $this;
		}
		$this->_withGroup=$_int;
		return $this;
	}

	public function withRight( $_int=0 ) {
		if ( empty( $_int ) ) {
			return $this;
		}
		$this->_withRight=$_int;
		return $this;
	}

	public function bySysName( $_arr=array() ) {
		if ( empty( $_arr ) ) {
			return $this;
		}
		$this->_bySysName=$_arr;
		return $this;
	}

	public function likeModuleName( $_str='' ) {
		if ( empty( $_str ) ) {
			return $this;
		}
		$this->_likeModuleName=$_str;
		return $this;
	}

	protected function assemblyQuery() {
		if ( $this->_toSelect ) {
			$this->withOrder( 'd.title--dn' );
		}
		parent::assemblyQuery();
		if ( $this->_withIdAndSys ) {
			$this->toSelect();
			$this->_crawler->clean_select(); // чистим селект набранный в parent::assemblyQuery();
			$this->_crawler->set_select( 'd.id, d.sys_name' ); // ключ в результате будет id
		}
		if ( !empty( $this->_bySysName ) ) {
			$this->_crawler->set_where( 'd.sys_name IN ('.Core_Sql::fixInjection( $this->_bySysName ).')' );
		}
		if ( !empty( $this->_likeModuleName ) ) {
			if ( $this->_keyRecordForm ) {
				$this->_crawler->clean_select(); // чистим селект набранный в parent::assemblyQuery();
				$this->_crawler->set_select( 'd.sys_name "key", d.*' ); // ключ в результате будет системное имя
			}
			$this->_crawler->set_where( 'd.sys_name LIKE "'.$this->_likeModuleName.'_@_%"' ); // все права для требуемого модуля
		}
	}

	public function getRights2group( &$arrRes ) {
		if ( empty( $this->_withGroup ) ) {
			return false;
		}
		$arrRes=Core_Sql::getKeyVal( 'SELECT rights_id, 1 FROM u_rights2group WHERE group_id IN('.Core_Sql::fixInjection( $this->_withGroup ).')' );
		$this->init();
		return !empty( $arrRes );
	}

	public function getGroups2right( &$arrRes ) {
		if ( empty( $this->_withRight ) ) {
			return false;
		}
		$arrRes=Core_Sql::getKeyVal( 'SELECT group_id, 1 FROM u_rights2group WHERE rights_id IN('.Core_Sql::fixInjection( $this->_withRight ).')' );
		$this->init();
		return !empty( $arrRes );
	}

	public function getRightWithGroup( &$arrRes ) {
		if ( empty( $this->_withGroup ) ) {
			return false;
		}
		$arrRes=Core_Sql::getKeyVal( 'SELECT r.id, r.sys_name FROM '.$this->_table.' r WHERE r.id IN(
			SELECT rights_id FROM '.self::$tableLink.' WHERE group_id IN('.Core_Sql::fixInjection( $this->_withGroup ).'))' );
		return !empty( $arrRes );
	}

	public function getRightWithModule( &$arrRes ) {
		$_arrS=Core_Sql::getAssoc( '
			SELECT r.*, a.flg_tree, m.id mid, m.title mtitle
			FROM u_rights r, sys_module m, sys_action a
			WHERE r.sys_name=CONCAT(m.name,"_@_",a.action) AND a.module_id=m.id
			ORDER BY a.flg_tree, a.module_id, r.title
		' );
		if ( empty( $_arrS ) ) {
			return false;
		}
		foreach( $_arrS as $v ) {
			$arrRes[(!isSet( $v['flg_tree'] )? 3:$v['flg_tree'])][(!isSet( $v['mid'] )? 0:$v['mid'])][]=$v;
		}
		return !empty( $arrRes );
	}
}
?>