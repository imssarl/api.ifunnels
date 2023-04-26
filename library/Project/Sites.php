<?php


 /**
 * система сайтов
 */
class Project_Sites extends Core_Data_Storage {

	/**
	* типы сайтов, значения используются также в таблицах для указания типа
	* @var const
	*/
	const NCSB=2, NVSB=3, BF=5, NCSB_DOWNLOAD=6;

	/**
	* название таблицы где хранятся категории
	* @var const
	*/
	public static $category='category_blogfusion_tree';

	/**
	* типы сайтов, используются также как часть пути до шаблонов в Project_Sites_Templates
	* @var const
	*/
	public static $code=array(
		Project_Sites::NCSB=>'ncsb',
		Project_Sites::NVSB=>'nvsb',
		Project_Sites::BF=>'bf',
	);

	/**
	* таблицы хранящие основные данные для каждого типа
	* @var const
	*/
	public static $tables=array(
		Project_Sites::NCSB=>'es_ncsb',
		Project_Sites::NVSB=>'es_nvsb',
		Project_Sites::BF=>'bf_blogs',
	);

	public static $arrUrls=array();

	/**
	* объект с абстрактом Project_Sites_Type_Abstract
	* @var object
	*/
	protected $_driver;

	private $_userId=0;
	
	protected $_type=0;

	/**
	* конструктор
	* @return void
	*/
	public function __construct( $_type=0 ) {
		if ( !self::getUserId( $this->_userId ) ) {
			return;
		}
		$this->_type=$_type;
		$this->setDriver();
		$this->_driver->setUser( $this->_userId );
		$this->_withOrder=$this->_driver->withOrder;
		$this->_table=$this->_driver->table;
	}

	protected function setDriver() {
		if ( ( $this->_driver=Project_Sites_Adapter_Factory::get( $this->_type ) )==false ) {
			throw new Exception( Core_Errors::DEV.'|Project_Sites driver not found' );
			return;
		}
		//$this->_driver->setSiteCode( Project_Sites::$code[$this->_type] );
	}

	protected static function getUserId( &$_int ) {
		if ( !Zend_Registry::get( 'objUser' )->getId( $_int ) ) {
			throw new Exception( Core_Errors::DEV.'|Zend_Registry::get( \'objUser\' )->getId( $_int ) is not return an User Id' );
			return false;
		}
		return true;
	}

	public function urlLog($_arrSite){
		return $this->_driver->urlLog( $_arrSite );
	}

	public function copyBlog( $_arrData ){
		return $this->_driver->copyBlog( $this,$_arrData );
	}

	public function set() {
		return $this->_driver->set( $this );
	}	
	
	public function setFiles( $_arrFiles=array() ){
		return $this->_driver->setFiles( $_arrFiles );
	}

	public function setSite( $_intId ){
		return $this->_driver->setSite( $_intId );
	}

	public function setContent( &$data ){
		return $this->_driver->setContent( $data );
	}

	public function setAmazonSettings( $data ){
		$this->_driver->setAmazonSettings( $data );
		return $this;
	}

	public function sites2portal( $arr, $intId ){
		return $this->_driver->sites2portal( $arr, $intId );
	}

	public function getArchive(){
		return $this->_driver->getArchive( $this );
	}
	
	public function import() {
		return $this->_driver->import( $this );
	}

	public function changeCategory( $_intSiteId=0, $_intCatId=0 ) {
		if ( empty( $_intSiteId )||empty( $_intCatId ) ) {
			return false;
		}
		Core_Sql::setExec( '
			UPDATE '.$this->_driver->table.' SET category_id='.Core_Sql::fixInjection( $_intCatId ).' 
			WHERE user_id="'.$this->_userId.'" AND id='.Core_Sql::fixInjection( $_intSiteId ).' 
			LIMIT 1
		' );
		return true;
	}

	public function getErrors( &$arrRes ) {
		$this->_driver->getErrors( $arrRes );
		return $this;
	}
	
	public function getEntered( &$arrRes ) {
		$this->_driver->getEntered( $arrRes );
		return $this;
	}
	
	public function setData( $arrData ){
		return $this->setEntered( $arrData );
	}

	public function getSite( &$arrRes, $_intId=0 ) {
		if ( !$this->onlyOne()->withIds( $_intId )->getList( $arrSite ) ) {
			return false;
		}
		return $this->_driver->get( $arrRes, $arrSite );
	}

	public function delSites( $mixId=array() ) {
		if ( empty( $mixId ) ) {
			return false;
		}
		$mixId=is_array( $mixId )? $mixId:array( $mixId );
		return $this->_driver->deleteSites( $mixId );
	}

	protected $_onlyPortals=false; // только порталы, для CNB сайтов!
	protected $_withoutCategories=false; // без категорий
	protected $_withCategory=false;
	protected $_toJs=false; // для вывода данных в сокращённом виде
	protected $_withPlacementId=false;
	private $_withoutPlacementIds=false;
	private $_onlyLocal=false;
	private $_onlyRoot=false;
	private $_withUrl=false;

	// сброс настроек после выполнения getList
	protected function init() {
		parent::init();
		$this->_onlyPortals=false;
		$this->_withOrder=$this->_driver->withOrder;
		$this->_withoutCategories=false;
		$this->_withCategory=false;
		$this->_toJs=false;
		$this->_withPlacementId=false;
		$this->_withoutPlacementIds=false;
		$this->_onlyLocal=false;
		$this->_onlyRoot=false;
		$this->_withUrl=false;
	}

	public function withPlacementId( $_arrIds ){
		$this->_withPlacementId=$_arrIds;
		return $this;
	}

	public function onlyPortals() {
		$this->_onlyPortals=true;
		return $this;
	}

	public function withUserId($_id) {
		if( isset( $_id ) && !empty( $_id ) ){
			$this->_userId=$_id;
		}
		return $this;
	}

	public function withoutCategories() {
		$this->_withoutCategories=true;
		return $this;
	}

	public function toJs() {
		$this->_toJs=true;
		return $this;
	}

	public function onlyLocal(){
		$this->_onlyLocal=true;
		return $this;
	}

	public function withoutPlacementIds( $_arrIds ){
		$this->_withoutPlacementIds=$_arrIds;
		return $this;
	}

	public function onlyRoot(){
		$this->_onlyRoot=true;
		return $this;
	}

	public function withUrl( $_str ){
		$this->_withUrl=$_str;
		return $this;
	}

	public function withCategory( $str ){
		$_category=new Core_Category( 'Blog Fusion' );
		$_category->levelMore(1)->byTitle( $str )->getList( $arrCategory );
		if(!empty($arrCategory)){
			$this->_withCategory=$arrCategory['id'];
		}
		return $this;
	}

	protected function assemblyQuery(){
		parent::assemblyQuery();
		if ( $this->_toJs ) {
			$this->_crawler->clean_select();
			$this->_crawler->set_select( '(SELECT COUNT(*) FROM es_content co WHERE co.site_id=d.id AND flg_type='. $this->_type .') content_count,d.id, d.main_keyword as title, d.url, d.category_id, '.$this->_type.' as type' );
		} else {
			if( $this->_type!=self::BF ){
				$this->_crawler->set_select( 'd.*, ls.template_id' );
			}
			$this->_crawler->set_select( '(SELECT title FROM category_blogfusion_tree WHERE id=d.category_id) category' );
		}
		if( $this->_toSelect ){
			$this->_crawler->clean_select();
			$this->_crawler->set_select( 'd.id, d.url' );
		}
		if( $this->_onlyLocal ){
			$this->_crawler->set_where('d.placement_id IN (SELECT s.id FROM site_placement s WHERE s.user_id='.Core_Users::$info['id'].' AND s.flg_type IN ('.Project_Placement::LOCAL_HOSTING.','.Project_Placement::LOCAL_HOSTING_DOMEN.'))');
		}
		if( $this->_withoutPlacementIds ){
			$this->_crawler->set_where('d.placement_id NOT IN ('.Core_Sql::fixInjection($this->_withoutPlacementIds).'))');
		}
		if( $this->_onlyRoot ){
			$this->_crawler->set_where("d.ftp_directory='/'");
		}
		if( $this->_withCategory ){
			$this->_crawler->set_where('d.category_id='.$this->_withCategory );
		}
		if( $this->_withUrl ){
			$this->_crawler->set_where('d.url='.Core_Sql::fixInjection($this->_withUrl));
		}
		if ( !empty( $this->_userId ) ) {
			$this->_crawler->set_where( 'd.user_id='.Core_Sql::fixInjection( $this->_userId ) );
		}
		if ( !empty( $this->_withId ) ) {
			$this->_crawler->set_where( 'd.id IN('.Core_Sql::fixInjection( $this->_withId ).')' );
		}
		if ( !empty( $this->_withoutCategories ) ) {
			$this->_crawler->set_where( 'd.category_id=0' );
		}
		if ( $this->_onlyPortals ) {
			$this->_crawler->set_where( 'd.flg_portal=1' );
		}
		if( $this->_type!=self::BF ){
			$this->_crawler->set_from( 'LEFT JOIN es_template2site ls ON ls.site_id =d.id AND flg_type='. $this->_type );
		}
		if( $this->_withPlacementId ){
			$this->_crawler->set_where('d.placement_id IN ('. Core_Sql::fixInjection( $this->_withPlacementId) .')');
		}
	}
}
?>