<?php


/**
 * Access Group control
 */
class Core_Mailer_Events_Type extends Core_Data_Storage {

	protected $_table='event_type';

	protected $_fields=array( 'id', 'site_id', 'flg_active', 'flg_user', 'sys_name', 'title', 'description' );

	private $_del=array();

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
		Core_Sql::setExec( '
			DELETE g, l FROM '.$this->_table.' g
			LEFT JOIN '.Core_Mailer_Events_Email::$tableLink.' l ON l.group_id=g.id
			WHERE g.id IN('.Core_Sql::fixInjection( $this->_withIds ).')
		' );
		$this->init();
		return true;
	}

	/**
	 * фильтр: ищем группы по системному имени
	 *
	 * @var array
	 */
	protected $_bySysName=array();

	protected function init() {
		parent::init();
		$this->_bySysName=array();
	}

	public function bySysName( $_arr=array() ) {
		if ( empty( $_arr ) ) {
			return $this;
		}
		$this->_bySysName=$_arr;
		return $this;
	}

	protected function assemblyQuery() {
		if ( $this->_toSelect ) {
			$this->withOrder( 'd.id--dn' );
		}
		parent::assemblyQuery();
		if ( !empty( $this->_bySysName ) ) {
			$this->_crawler->set_where( 'd.sys_name IN ('.Core_Sql::fixInjection( $this->_bySysName ).')' );
		}
	}
}
?>