<?php


/**
 * Embed видео функционал
 */
class Project_Embed extends Core_Storage implements Project_Content_Interface {

	private static $_instance=NULL;
	protected $_settings=array();
	protected $_counter=false;
	protected $_limit=false;
	public $fields=array( 'id', 'user_id', 'category_id', 'source_id', 'title', 'body', 'url_of_video', 'edited', 'added' );
	public $table='content_video';
	protected $_link=false; // тут линк нам не нужен
	protected $_trueOrder=false;
	private $_withRights=false;

	public function __construct( $_withoutUser=false ) {
		if ( $_withoutUser ) {
			return;
		}
		if ( !Zend_Registry::get( 'objUser' )->getId( $_int ) ) {
			throw new Exception( Core_Errors::DEV.'|Zend_Registry::get( \'objUser\' )->getId( $_int ) is not return an User Id' );
			return;
		}
		$this->_userId=$_int;
	}

	public function setPost( $_arrPost=array() ){}

	public function setFile( $_arrFile=array() ){}

	public function getResult( &$arrRes ){}

	public function set() {
		if ( !$this->_data->setFilter( array( 'trim', 'clear' ) )->setChecker( array(
			'category_id'=>empty( $this->_data->filtered['category_id'] ),
			'title'=>empty( $this->_data->filtered['title'] ),
		) )->check() ) {
			$this->_data->getErrors( $this->_errors );
			return false; 
		}
		if ( empty( $this->_data->filtered['id']) ) {
			$this->_data->setElements( array(
				'user_id'=>$this->_userId,
				'added'=>time(),
				'edited'=>time(),
			) );
			$tags=new Core_Tags('video');
		} else {
			$this->_data->setElement( 'edited', time() );
		}
		$this->_data->setElement( 'id', Core_Sql::setInsertUpdate( $this->table, $this->_data->setMask( $this->fields )->getValid() ) );
		if( is_object($tags) && !empty($this->_data->filtered['tags']) ){
			if( !$tags->setItem( $this->_data->filtered['id'] )->setTags( $this->_data->filtered['tags'] )->set() ){
				return false;
			}
		}
		return true;
	}

	public function changeSomeFields( &$arrRes ) {
		$arrRes['title']=$arrRes['title'].'_duplicated';
	}

	public function getOwnerId() {
		return $this->_userId;
	}

	public function getAdditional( &$arrRes ) {
		$category=new Project_Embed_Category();
		$category->toSelect()->get( $arrRes['category'], $_arrTmp );
		$source=new Core_Category( 'Embed Manager Source' );
		$source->toSelect()->get( $arrRes['source'], $_arrTmp );
		return $this;
	}

	// depercated!!! use $this->onlyOwner()->get( $arrRes, $_intId ) instead
	public function getVideo( &$arrRes, $_intId=0 ) {
		return $this->onlyOwner()->get( $arrRes, $_intId );
	}

	protected $_withCategory=0; // видео в категории
	protected $_withTags=false;

	public function withCategory( $_intId=0 ){
		$this->_withCategory=$_intId;
		return $this;
	}

	public function withRights( $_arr){
		if(!empty($_arr)){
			$this->_withRights=$_arr;
		}
		return $this;
	}

	public function withTags( $_str ){
		if( empty($_str) ){
			return $this;
		}
		$this->_withTags=$_str;
		return $this;
	}

	public function setFilter( $_arrFilter=array() ){
		$this->_settings=$_arrFilter;
		$this
			->withCategory($_arrFilter['category_id'])
			->withTags($_arrFilter['tags']);
		return $this;
	}

	public function setCounter( $_intCounter ){
		$this->_counter=$_intCounter;
		return $this;
	}

	public function setLimited( $_intLimited ){
		$this->_limit=$_intLimited;
		return $this;
	}

	public function getFilter( &$arrRes ){
		$arrRes = $this->_settings;
		return !empty( $arrRes );
	}
	
	public function getOrder( &$arrRes ){
		$arrRes = $this->_trueOrder;
		return !empty( $arrRes );
	}

	protected function init() {
		$this->_withTags=false;
		$this->_withCategory=0;
		$this->_withRights=false;
		parent::init();
	}

	protected function assemblyQuery() {
		if ( $this->_onlyIds ) {
			$this->_crawler->set_select( 'd.id' );
		} elseif ( $this->_toSelect ) {
			$this->_crawler->set_select( 'd.id, d.title' );
		} else {
			$this->_crawler->set_select( 'd.*' );
		}
		$this->_crawler->set_from( $this->table.' d' );
		if ( !empty( $this->_withIds ) ) {
			$this->_crawler->set_where( 'd.id IN ('.Core_Sql::fixInjection( $this->_withIds ).')' );
		}
		if ( $this->_onlyOwner ) {
			$this->_crawler->set_where( 'd.user_id='.$this->getOwnerId() );
		}
		if ( !empty( $this->_withCategory ) ) {
			$this->_crawler->set_where( 'd.category_id='.Core_Sql::fixInjection( $this->_withCategory ) );
		}
		if(!empty($this->_withRights)){
			$this->_crawler->set_where('d.user_id IN ('. Core_Acs::haveRightAccess( $this->_withRights ) .')');
		}
		if ( !empty( $this->_withTags ) ) {
			$tags=new Core_Tags('video');
			$tags->setTags( $this->_withTags )->getSearchQuery( $_strSql );
			$this->_crawler->set_where( 'd.id IN ('.$_strSql.')' );
		}
		if ( !( $this->_onlyOne||$this->_onlyCell ) ) {
			if ( ( $pos=strpos( $this->_withOrder, 'category_id' ) )!==false ) { // чтобы сортировало по названиям а не по ids
				$this->_crawler->set_select( '(SELECT title FROM category_category WHERE id=d.category_id) category_title' );
				$this->_crawler->set_order_sort( str_replace( 'category_id', 'category_title', $this->_withOrder ) );
			} elseif ( ( $pos=strpos( $this->_withOrder, 'source_id' ) )!==false ) { // чтобы сортировало по названиям а не по ids
				$this->_crawler->set_select( '(SELECT title FROM category_category WHERE id=d.source_id) source_title' );
				$this->_crawler->set_order_sort( str_replace( 'source_id', 'source_title', $this->_withOrder ) );
			} else {
				$this->_crawler->set_order_sort( $this->_withOrder );
			}
		}
		$this->_trueOrder = $this->_withOrder;
	}

	// всегда отображать видое только user_id
	public function getList( &$mixRes ) {
		if ( self::$_instance==NULL ) { // иначе это статик вызов без всяких владельцев
			$this->onlyOwner();
		}
		return parent::getList( $mixRes );p('');
	}

	// поддержка Project_Content_Interface

	// в статике работаем без user_id 14.01.2011
	public static function getInstance() {
		if ( self::$_instance==NULL ) {
			self::$_instance=new Project_Embed( true );
		}
		return self::$_instance;
	}
	
	public function setSettings( $arrSettings ){
		return $this;
	}
}
?>