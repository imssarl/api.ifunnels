<?php
/**
 * WorkHorse Framework
 *
 * @category Project
 * @package Project_Widget_Copt
 * @copyright Copyright (c) 2009-2011, web2innovation
 * @author Pavel Livinskij <pavel.livinskij@gmail.com>
 * @date 12.07.2011
 * @version 1.0
 */


/**
 * Управление сниппетами
 *
 * @category Project
 * @package Project_Widget_Copt
 * @copyright Copyright (c) 2009-2011, web2innovation
 */
class Project_Widget_Adapter_Copt_Snippets extends Core_Storage {

	public $table='co_snippets';
	public $fields=array('id','user_id','flg_enabled','title','description','added');
	private $_userId=false;
	protected $_link=false;
	private static $_instance=NULL;
	protected $_counter=0;
	protected $_limit=false;
	private $_withRights=false;

	public function __construct( $_withoutUser=false ) {
		if ( $_withoutUser ) {
			return;
		}
		if ( !Zend_Registry::get( 'objUser' )->getId( $this->_userId ) ) {
			throw new Exception( Core_Errors::DEV.'|Zend_Registry::get( \'objUser\' )->getId( $_int ) is not return an User Id' );
		}
	}

	public static function getInstance() {
		if ( self::$_instance==NULL ) {
			self::$_instance=new Project_Widget_Adapter_Copt_Snippets( true );
		}
		return self::$_instance;
	}

	/**
	 * Возвращает код для вставки в удаленные сайты. Данный код будет дергать сервис CNM
	 * @static
	 * @param  $intId
	 * @return string
	 */
	public static function getCode( $arrId ){
		if( empty($arrId) ){
			return false;
		}
		if( !is_array($arrId) ){
			$arrId=array($arrId);
		}
		Project_Widget_Mutator::encodeArray( $arrId );
		$_str='<script type="text/javascript" src="https://'.Zend_Registry::get( 'config' )->engine->project_domain.'/services/widgets.php?name=Copt&action=get&id='.join('-', $arrId).'"></script>';
		return $_str;
	}

	public function set(){
		if ( !$this->_data->setFilter( array( 'trim', 'clear' ) )->setChecker( array(
			'title'=>empty( $this->_data->filtered['title'] ),
			'description'=>empty( $this->_data->filtered['description'] )
		) )->check() ) {
			$this->_data->getErrors( $this->_errors );
			return false;
		}
		if ( empty($this->_data->filtered['id']) ){
			$this->_data->setElement( 'user_id', $this->_userId );
			$this->_data->setElement( 'added', time() );
		}
		$this->_data->setElement( 'id', Core_Sql::setInsertUpdate( $this->table, $this->_data->setMask( $this->fields )->getValid() ) );
		return true;
	}

	public function getStatistic( &$arrRes ){
		if( empty($this->_withIds) ){
			return false;
		}
		$_parts=new Project_Widget_Adapter_Copt_Parts();
		$_parts->onlyIds()->onlySnippet( $this->_withIds )->getList( $_partsIds );
		$_parts->withIds( $_partsIds )->withOrder($this->_withOrder)->getStatistic( $arrRes );
		return !empty($arrRes);
	}

	public function del( $_arr=array() ){
		$_parts=new Project_Widget_Adapter_Copt_Parts();
		if( $_parts->onlySnippet( $_arr )->onlyIds()->getList( $_arrParts )->checkEmpty()){
			$_parts->del($_arrParts);
		}
		parent::del(  $_arr );
	}

	public function getOwnerId(){
		return $this->_userId;
	}

	public function duplicate( $_intId=0 ) {
		if( !parent::duplicate($_intId) ){
			return false;
		}
		// дублирование всех частей сниппета.
		$_parts=new Project_Widget_Adapter_Copt_Parts();
		$_parts->onlySnippet( $_intId )->onlyIds()->getList( $_arrParts );
		foreach( $_arrParts as $_partId ){
			$_parts->onlySnippet( $this->_data->filtered['id'] )->duplicate( $_partId );
		}
		return true;
	}

	public function changeSomeFields( &$arrRes ){
		$arrRes['title']=$arrRes['title'].'_dup';
	}

	private $_withParts=false;

	protected function init(){
		$this->_withParts=false;
		$this->_withRights=false;
		parent::init();
	}

	public function withRights($_arr){
		if(!empty($_arr)){
			$this->_withRights=$_arr;
		}
		return $this;
	}

	public function withParts(){
		$this->_withParts=true;
		return $this;
	}

	protected function assemblyQuery() {
		if ( $this->_onlyIds ) {
			$this->_crawler->set_select( 'd.id' );
		}  else {
			$this->_crawler->set_select( 'd.*' );
			$this->_crawler->set_select( '(SELECT SUM(views) FROM co_parts as p WHERE p.snippet_id = d.id) as views' );
			$this->_crawler->set_select( '(SELECT SUM(clicks) FROM co_parts as p WHERE p.snippet_id = d.id) as clicks' );
			$this->_crawler->set_select( '(SELECT COUNT(*) FROM co_parts as p WHERE p.snippet_id = d.id) as parts' );
		}
		if( $this->_limit ){
			$this->_crawler->set_limit( $this->_counter.','.$this->_limit );
		}
		if ( $this->_userId ) {
			$this->_crawler->set_where( 'd.user_id='.$this->_userId );
		}
		if(!empty($this->_withRights)){
			$this->_crawler->set_where('d.user_id IN ('. Core_Acs::haveRightAccess( $this->_withRights ) .')');
		}
		$this->_crawler->set_from( $this->table.' d' );
		if ( !empty( $this->_withIds ) ) {
			$this->_crawler->set_where( 'd.id IN ('.Core_Sql::fixInjection( $this->_withIds ).')' );
		}
		if ( !( $this->_onlyOne||$this->_onlyCell ) ) {
			$this->_crawler->set_order_sort( $this->_withOrder );
		}
	}

	public function getList( &$arrRes ){
		$_withParts=$this->_withParts;
		parent::getList( $arrRes );
		if( !$_withParts || empty($arrRes) ){
			return $this;
		}
		$_parts=new Project_Widget_Adapter_Copt_Parts();
		foreach( $arrRes as &$_item ){
			$_parts->onlySnippet( $_item['id'] )->getList( $_item['arrParts'] );
		}
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
}
?>