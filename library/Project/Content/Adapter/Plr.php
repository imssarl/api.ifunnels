<?php

class Project_Content_Adapter_Plr extends Core_Storage implements Project_Content_Interface {

	protected $_settings=array();
	protected $_counter=0;
	protected $_limit=false;
	public $fields=array( 'id', 'category_id', 'word_count', 'title', 'body', 'summary', 'keywords' );
	public $table='articles_plr';
	private $_tags=array('body'=>'{body}');
	private $_post=array();
	private $_files=array();
	private $_result=false;
	private $_withCategory=false;
	private $_withkeywords=false;
	private $_withJson=false;
	private $_withRewrite=false;

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
		$this
			->withCategories($_arrFilter['category_id'])
			->withKeywords($_arrFilter['keywords']);
		return $this;
	}

	public function withKeywords( $_str ){
		if( empty($_str) ){
			return $this;
		}
		$this->_withkeywords=$_str;
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

	public function withJson(){
		$this->_withJson=true;
		return $this;
	}

	protected function assemblyQuery(){
		if ( $this->_onlyIds ) {
			$this->_crawler->set_select( 'd.id' );
		} elseif ( $this->_toSelect || $this->_toJs ) {
			$this->_crawler->set_select( 'd.id, d.title' );
		} elseif( $this->_onlyOne ) {
			$this->_crawler->set_select( 'd.*' );
		} else {
			$this->_crawler->set_select( 'd.id, d.title, d.body, SUBSTRING(d.summary FROM 1 FOR 100) summary' );
			$this->_crawler->set_select( 'c.title category_title' );
		}
		$this->_crawler->set_from( $this->table.' d' );
		$this->_crawler->set_from( 'INNER JOIN category_plr_tree c ON c.id=d.category_id' );
		if( $this->_limit ){
			$this->_crawler->set_limit( $this->_counter.','.$this->_limit );
		}
		if ( !empty( $this->_withCategory ) ) {
			$this->_crawler->set_where( 'd.category_id IN('.Core_Sql::fixInjection( $this->_withCategory ).')' );
		}
		if ( !empty( $this->_withIds ) ) {
			$this->_crawler->set_where( 'd.id IN('.Core_Sql::fixInjection( $this->_withIds ).')' );
		}
		if ( !empty( $this->_withkeywords ) ) {
			$this->_crawler->set_where( "d.keywords LIKE '%".trim(strip_tags($this->_withkeywords))."%'" );
		}
		if ( !( $this->_onlyOne||$this->_onlyCell ) ) {
			$this->_crawler->set_order_sort( $this->_withOrder );
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