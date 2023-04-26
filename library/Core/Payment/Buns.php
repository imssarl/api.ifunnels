<?php

/**
 * Периодическое списание кредитов за различные плюшки.
 */
class Core_Payment_Buns extends Core_Data_Storage {

	protected $_table='p_buns';

	protected $_fields=array( 'id', 'credits', 'length', 'flg_status', 'flg_length',
		'title','sys_name','description','edited','added' );

	private $_withSysName=false;

	const LENGTH_DAY=0,LENGTH_MONTH=1,LENGTH_YEAR=2,ITEMS=3;

	public static function getLength( $_arrItem ){
		if( $_arrItem['flg_length'] == self::LENGTH_DAY ){
			return $_arrItem['length']*(60*60*24);
		} elseif( $_arrItem['flg_length'] == self::LENGTH_MONTH ){
			return $_arrItem['length']*(60*60*24*30);
		} elseif( $_arrItem['flg_length'] == self::LENGTH_YEAR ){
			return $_arrItem['length']*(60*60*24*365);
		} elseif( $_arrItem['flg_length'] == self::ITEMS ){
			return $_arrItem['length'];
		}
	}

	public static function run( $_onlyOneItemName=false ){
		$_instance=new self();
		if( !$_instance->getList( $_arrBuns )->checkEmpty() ){
			return false;
		}
		foreach( $_arrBuns as $_item ){
			if( empty( $_onlyOneItemName )
				|| ( is_string( $_onlyOneItemName ) && $_onlyOneItemName==$_item['sys_name'] )
				|| ( is_array( $_onlyOneItemName ) && in_array( $_item['sys_name'], $_onlyOneItemName ) )
			){
				$_class=$_item['sys_name'];
				if( !class_exists($_class) ){
					$_item['flg_status']=1;
					$_instance->setEntered($_item)->set();
					continue;
				}
				$_obj=new $_class();
				if( !method_exists( $_obj,'checkExpired' ) ){
					$_item['flg_status']=1;
					$_instance->setEntered($_item)->set();
					continue;
				}
				$_obj->checkExpired( $_item );
			}
		}
	}

	public function withSysName( $_str ){
		if(!empty($_str)){
			$this->_withSysName=$_str;
		}
		return $this;
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
		Core_Sql::setExec( 'DELETE FROM '.$this->_table.' WHERE id IN('.Core_Sql::fixInjection( $this->_withIds ).')' );
		$this->init();
		return true;
	}

	protected function assemblyQuery(){
		parent::assemblyQuery();
		if( !empty($this->_withSysName) ){
			$this->_crawler->set_where('d.sys_name='.Core_Sql::fixInjection( $this->_withSysName) );
		}
	}

	protected function init(){
		parent::init();
		$this->_withSysName=false;
	}
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
		if( empty($_arrRow['sys_name'])||empty($_arrRow['title'])){
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
		// удаляем отмеченные
		if ( !empty( $this->_del ) ) {
			$this->withIds( $this->_del )->del();
			$this->_del=array();
		}
		return true;
	}

}
?>