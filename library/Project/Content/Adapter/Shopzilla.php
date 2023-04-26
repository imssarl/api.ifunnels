<?php

class Project_Content_Adapter_Shopzilla implements Project_Content_Interface {

	protected $_settings=array();
	protected $_counter=0;
	protected $_limit=20;
	private $_post=array();
	private $_result=false;
	private $_isNotEmpty=false;
	private $_paging=array();
	private $_withPaging=false;
	private $_paggedData=false;
	private $_withIds=false;
//	private $_url='http://www.shopzilla.com/#keywords#/search?start=#page#';
	private $_url='http://catalog.bizrate.com/services/catalog/v1/us/product?apiKey=#API_KEY#&publisherId=#PUBLISHER_ID#&keyword=#KEYWORDS#&start=#START#&results=#NUM_RESULTS#&resultsOffers=#OFFERS#&minPrice=#MIN_PRICE#&maxPrice=#MAX_PRICE#&sort=#SORT#&biddedOnly=true';
	private $_withJson=false;
	private $_withRewrite=false;

	public function __construct() {
		if( !is_array( $_SESSION['paggedData'] )){
			$_SESSION['paggedData']=array();
		}
		$this->_paggedData=&$_SESSION['paggedData'];
	}

	public static $templates=array (
		0=>'Template 1'
	);

	public static function getInstance() {}

	public function setPost( $_arrPost=array() ){
		$this->_post=$_arrPost;
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

	public function setFile( $_arrFile=array() ){
		return $this;
	}

	public function getPaging( &$arrRes ){
		if( $this->_paging['curpage']>1){
			$arrRes['urlminus']='/?page='.($this->_paging['curpage']-1);
			$arrRes['num'][]=array(
				'number'=>($this->_paging['curpage']-1),
				'url'=>'./?page='.($this->_paging['curpage']-1)
			);
		}
		$arrRes['num'][] = array (
			'sel' => 1,
			'number' => $this->_paging['curpage']
		);
		$arrRes['num'][] = array (
			'number' => $this->_paging['curpage']+1,
			'url'=> './?page='.($this->_paging['curpage']+1)
		);
		$arrRes['urlmin']='/?page=1';
		$arrRes['urlplus']='/?page='.($this->_paging['curpage']+1);
		$this->_paging=array();
		return $this;
	}

	public function withPaging( $_arr=array() ) {
		$this->_withPaging=$_arr;
		return $this;
	}

	public function withIds( $_arrIds=array() ){
		$this->_withIds=$_arrIds;
		return $this;
	}

	public function checkEmpty(){
		return $this->_isNotEmpty;
	}

	public function getResult( &$arrRes ){
		return $this->_result;
	}

	public function getAdditional( &$arrRes ){
		return $this;
	}

	public function getOwnerId(){}

	protected $_withCategories=false;

	public function withCategories( $_arr=array() ) {
		$this->_withCategories=$_arr;
		return $this;
	}

	public function setFilter( $_arrFilter=array() ){
		$this->_settings=$_arrFilter;
		$this
			->withTags( $_arrFilter['tags'] )
			->withCategories($_arrFilter['category_id']);
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
		$arrRes=$this->_settings;
		return !empty($arrRes);
	}

	public function withTags( $_str ){
		if( empty($_str) ){
			return $this;
		}
		$this->_withTags=$_str;
		return $this;
	}

	protected function init(){
		$this->_withTags=false;
		$this->_withCategories=false;
		$this->_withIds=false;
		$this->_withPaging=false;
		$this->_withJson=false;
		$this->_withRewrite=false;
	}

	public function setSettings( $arrSettings ){
		if( empty($arrSettings) ){
			return false;
		}
		$this->_settings=$arrSettings;
		return $this;
	}

	const NO_FOUND_DESCRIPTION=1, NO_FOUND_IMAGES=2, NO_FOUND_DESC_OR_IMG=3;

	public function getList( &$mixRes ){
		if ( !empty($this->_withIds) ) {
			foreach ( $this->_withIds as $_contentId ) {
				$mixRes[] = $this->_paggedData[$_contentId];
			}
			if(!empty($this->_withJson)){
				foreach( $mixRes as &$_item ){
					$_item['fields']=serialize($_item);
				}
			}
			$this->_isNotEmpty=!empty( $mixRes );
			$this->init();
			$this->_paggedData=false;
			return $this;
		}
		if( !empty( $this->_withPaging['page'] ) ){
			$this->_counter= ($this->_withPaging['page']-1) * $this->_limit;
			$_page=$this->_withPaging['page'];
		} else {
			$_page=( ($this->_counter+$this->_limit)/$this->_limit <= 1 )? 1 : (int)ceil(($this->_counter+$this->_limit)/$this->_limit);
		}
		$this->_paging=array( 'curpage'=>$_page );
		$_curl=Core_Curl::getInstance();
		$_url=str_replace(
			array(
				'#API_KEY#',
				'#PUBLISHER_ID#',
				'#KEYWORDS#',
				'#START#',
				'#NUM_RESULTS#',
				'#OFFERS#',
				'#MIN_PRICE#',
				'#MAX_PRICE#',
				'#SORT#'
			),
			array(
				$this->_settings['api_key'],
				$this->_settings['pub_id'],
				$this->_settings['keywords'],
				$this->_counter,
				$this->_limit,
				$this->_settings['offers'],
				$this->_settings['minprice'],
				$this->_settings['maxprice'],
				$this->_settings['sort']
			),
			$this->_url
		);
		$_curl->getContent($_url);
		$_responce=$_curl->getResponce();
		$_result = simplexml_load_string($_responce);//p($_result);
		if (!isset($_result->Products->Product)) {
			$this->_isNotEmpty=!empty( $mixRes );
			return $this;
		}
		$index=$this->_counter;
		foreach($_result->Products->Product as $item) {
			$mixRes[$index]=array(
				'id'=>$index,
				'title'=>(string) $item->title,
				'description'=>(string) $item->description,
				'link'=>(string) $item->url,
				'image'=> (string) $item->Images->Image[2],
				'minprice'=>  str_replace("$", "$ ", (string) $item->PriceSet->minPrice),
				'maxprice'=> str_replace("$", "$ ", (string) $item->PriceSet->maxPrice),
				'stores'=>(string) $item->PriceSet->stores
			);
			$index++;
		}
		$this->_isNotEmpty=!empty( $mixRes );
		$this->_paggedData = array_merge($this->_paggedData, $mixRes);
		if(!empty($this->_withJson)){
			foreach( $mixRes as &$_item ){
				$_item['fields']=serialize($_item);
			}
		}
		$this->init();
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
				Zend_Registry::get('rewriter')->setText( $_fields['description'] )->setDeep( $this->_withRewrite )->rewrite( $_tmpRes );
				$_fields['description']=(empty($_tmpRes))?$_fields['description']:array_shift( $_tmpRes );
			}
			if (empty($this->_settings['template'])) {
				$this->_settings['template']='0';
			}
			$path=Zend_Registry::get( 'config' )->path->relative->source.'site1_publisher'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'shopzilla'.DIRECTORY_SEPARATOR;
			$_item['body']=Core_View::factory( Core_View::$type['one'] )
				->setTemplate( $path.$this->_settings['template'].'.tpl' )
				->setHash( $_fields )
				->parse()
				->getResult();
		}
		$this->init();
	}

}
