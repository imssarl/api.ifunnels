<?php


/**
 * Exclusive контент функционал
 */
class Project_Content_Adapter_Exclusive extends Core_Data_Storage implements Project_Content_Interface {

	protected  $_fields=array( 'id', 'category_id','title','body','added','edited' );
	protected $_table='content_exclusive';

	public static $templates=array();
	private $_tags=array('body'=>'{body}');
	private $_withJson=false;
	private $_withRewrite=false;
	private $_withKeyword=false;
	protected $_settings=array();
	protected $_counter=0;
	protected $_limit=false;
	private $_post=array();
	private $_files=array();
	private $_result=false;

	public function __construct() {}

	public static function getInstance(){}

	public function getAdditional( &$arrRes ){ return $this; }

	public function setPost( $_arrPost=array() ){
		$this->_post=$_arrPost;
		return $this;
	}

	public function setFile( $_arrFile=array() ){
		$this->_files=$_arrFile;
		return $this;
	}

	public function getResult( &$arrRes ){
		return $this->_result;
	}

	public function setFilter( $_arrFilter=array() ){
		$this->_settings=$_arrFilter;
		if(empty($_arrFilter['category_id'])&&!empty($_arrFilter['category_pid'])){
			$category=new Core_Category( 'Exclusive' );
			$category->toSelect()->getLevel( $_arrCaterories, $_arrFilter['category_pid'] );
			$_arrFilter['category_id']=array_keys( $_arrCaterories );
		}
		$this
			->withCategories($_arrFilter['category_id'])
			->withKeyword( $_arrFilter['keywords'] );
		return $this;
	}

	public function withCategories( $_arr=array() ) {
		$this->_withCategory=$_arr;
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

	public function setSettings( $arrSettings ){
		if( empty($arrSettings) ){
			return false;
		}
		$this->_settings=$arrSettings;
		return $this;
	}

	public function withRewrite( $_int ){
		$this->_withRewrite=$_int;
		return $this;
	}

	public function withKeyword( $_words ){
		$this->_withKeyword=$_words;
		return $this;
	}

	public function withJson(){
		$this->_withJson=true;
		return $this;
	}

	public function withRandom(){
		$this->_withRandom=true;
		return $this;
	}

	protected function init(){
		parent::init();
		$this->_withJson=false;
		$this->_withKeyword=false;
		$this->_withRewrite=false;
		$this->_withRandom=false;
	}

	protected function assemblyQuery(){
		if( $this->_withRandom ){
			$this->_withOrder='';
		}
		parent::assemblyQuery();
		$this->_crawler->set_select( 'd.id, d.title, d.body, SUBSTRING(d.body FROM 1 FOR 100) summary' );
		$this->_crawler->set_select( 'c.title category_title' );
		$this->_crawler->set_from( 'INNER JOIN category_exclusive c ON c.id=d.category_id' );
		if ( !empty( $this->_withCategory ) ) {
			$this->_crawler->set_where( 'd.category_id IN('.Core_Sql::fixInjection( $this->_withCategory ).')' );
		}
		if( $this->_withRandom ){
			$this->_crawler->set_order('RAND()');
		}
		if($this->_limit){
			$this->_crawler->set_limit('0,'.$this->_limit);
		}
		if( $this->_withKeyword ){
			$_words=explode(' ', $this->_withKeyword );
			$this->_crawler->set_where('d.title LIKE \'%'. join('%',$_words) .'%\' OR d.body LIKE \'%'. join('%',$_words) .'%\' ');
		}
	}

	public function getList( &$mixRes ){
		$_withJson=$this->_withJson;
		parent::getList( $mixRes );
		if(!empty($_withJson)){
			foreach( $mixRes as &$_item ){
				$_item['fields']=serialize($_item);
			}
		}
		return $this;
	}

	public function prepareBody( &$mixRes ){
		foreach( $mixRes as &$_item ){
			if( !is_array($_item) ){
				return;
			}
			$_fields=unserialize($_item['body']);
			if(empty($_fields)){
				continue;
			}
			if( $this->_withRewrite ){
				Zend_Registry::get('rewriter')->setText( $_fields['title'] )->setDeep( $this->_withRewrite )->rewrite( $_tmpRes );
				$_fields['title']=(empty($_tmpRes))?$_fields['title']:array_shift( $_tmpRes );
				unset($_tmpRes);
				Zend_Registry::get('rewriter')->setText( $_fields['body'] )->setDeep( $this->_withRewrite )->rewrite( $_tmpRes );
				$_fields['body']=(empty($_tmpRes))?$_fields['body']:array_shift( $_tmpRes );
			}
			if (empty($this->_settings['template'])) {
				$_item['title']=$_fields['title'];
				$_item['body']=$_fields['body'];
				continue;
			}
			ksort($_fields);
			ksort($this->_tags);
			$_tmpTemplate=$this->_settings['template'];
			$_replace=array_intersect_key( $_fields, $this->_tags );
			$_tmpTemplate=str_replace( $this->_tags, $_replace, $_tmpTemplate );
			$_item['body']=$_tmpTemplate;
		}
	}
}
?>