<?php
/**
 * Управление настройками контента
 */
class Project_Content_Settings extends Core_Data_Storage {
	
	public $_table='content_setting';
	public $_fields=array('id','user_id','flg_source','settings','flg_default');

	public function __construct() {
		if ( !Zend_Registry::get( 'objUser' )->getId( $_int ) ) {
			throw new Exception( Core_Errors::DEV.'|Zend_Registry::get( \'objUser\' )->getId( $_int ) is not return an User Id' );
			return;
		}
		$this->_userId=$_int;		
	}
	
	/**
	 * Переопределение родительского метода
	 *
	 */
	public static function getInstance() {}

	protected function beforeSet(){
		$this->_data->setFilter( array( 'clear' ) );
		$this->_data->setElement('settings', serialize( $this->_data->filtered['settings'] ) );
		return true;
	}
	
	/**
	 * Только один источник
	 *
	 * @var int
	 */
	private $_onlySource=false;
	
	public function onlySource( $intId ){
		if (empty($intId)){
			return false;
		}
		$this->_onlySource=$intId;
		return $this;
	}

	private $_flgId2Record=false;
	private $_withFlgDefault=false;

	public function flgId2Record(){
		$this->_flgId2Record=true;
		return $this;
	}

	public function withFlgDefault(){
		$this->_withFlgDefault = true;
		return $this;
	} 
	
	protected function init(){
		parent::init();
		$this->_onlySource=false;
		$this->_flgId2Record=false;
		$this->_withFlgDefault=false;
	}
	
	protected function assemblyQuery(){
		$this->_crawler->set_select( 'd.*' );
		$this->_crawler->set_from( $this->_table.' d' );
		if ( $this->_onlyOwner&&$this->_userId ){
			$this->_crawler->set_where( 'd.user_id='.$this->_userId );
		}
		if ( !empty( $this->_withIds ) ) {
			$this->_crawler->set_where( 'd.id IN ('.Core_Sql::fixInjection( $this->_withIds ).')' );
		}
		if ( !( $this->_onlyOne||$this->_onlyCell ) ) {
			$this->_crawler->set_order_sort( $this->_withOrder );
		}
		if ( !empty( $this->_onlySource ) ) {
			$this->_crawler->set_where( 'd.flg_source = '.Core_Sql::fixInjection( $this->_onlySource ) );
		}	
		if( $this->_withFlgDefault ){
			$this->_crawler->set_where( 'd.flg_default=1' );
		}	
	}	
	
	/**
	 * Список свойств
	 *
	 */
	public function getContent( &$mixRes ) {
		$this->onlyOwner();
		parent::getList( $mixRes );
		return $this->prepare( $mixRes );
	}
	
	/**
	 * Подготовить данные для шаблона
	 *
	 * @param array $mixRes
	 * @return bool
	 */
	private function prepare( &$mixRes ) {
		if ( empty( $mixRes ) ){
			return false;
		}
		if ( isSet( $mixRes['settings'] ) ) {
			if ( empty( $mixRes['settings'] ) ) {
				return false;
			}
			$mixRes['settings']=unserialize($mixRes['settings']);
			return true;
		}
		foreach( $mixRes as $k=>$v ) {
			$mixRes[$k]['settings']=unserialize( $v['settings'] );
		}
		return true;
	}
}
?>