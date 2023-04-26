<?php

class Project_Content_Adapter_Ebay implements Project_Content_Interface {

	protected $_settings=array();
	protected $_counter=0;
	protected $_limit=15;
	private $_tags=array('title'=>'{title}','body'=>'{body}','link'=>'{link}','price'=>'{price}', 'currency'=>'{currency}','image'=>'{image}','endTime'=>'{endTime}','startTime'=>'{startTime}','sellerName'=>'{sellerName}');
	private $_post=array();
	private $_result=false;
	private $_isNotEmpty=false;
	private $_paging=array();
	private $_withPaging=false;
	private $_paggedData=false;
	private $_withIds=false;
	private $_withJson=false;
	private $_withRewrite=false;

	public function __construct() {
		if( !is_array( $_SESSION['paggedData'] )){
			$_SESSION['paggedData']=array();
		}
		$this->_paggedData=&$_SESSION['paggedData'];
	}

	public static function getInstance() {}

	public function setPost( $_arrPost=array() ){
		$this->_post=$_arrPost;
		return $this;
	}

	public function setFile( $_arrFile=array() ){
		$this->_files=$_arrFile;
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

	public function withRewrite( $_int ){
		$this->_withRewrite=$_int;
		return $this;
	}

	public function withJson(){
		$this->_withJson=true;
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
			$this->_counter=( $this->_withPaging['page']-1 ) * $this->_limit;
			$_page=$this->_withPaging['page'];
		} else {
			$_page=( $this->_counter/$this->_limit <= 1 )? 1 : ceil($this->_counter/$this->_limit);
		}
		$this->_paging=array( 'curpage'=>$_page );
		try{
    	$finding  = new Zend_Service_Ebay_Finding( array(
					'app_id'=>$this->_settings['app_id'],
					'global_id'=>$this->_settings['global_id']
					));
    	$response = $finding->findItemsByKeywords($this->_settings['keywords'],
					array(
						'paginationInput'=>array(
							'entriesPerPage'=>$this->_limit,
							'pageNumber'=>$_page
							),
						'sortOrder'=>$this->_settings['order']
						)
					);
		} catch ( Exception $e){
			return $this;
		}
		$index=$this->_counter;
    	foreach ( $response->searchResult->item as $item) {
			$currency=$item->sellingStatus->attributes('currentPrice');
			$mixRes[$index]=array(
				'id'=>$index,
				'title'=>$item->title,
				'price'=>$item->sellingStatus->currentPrice,
				'link'=>$item->viewItemURL,
				'image' => $item->galleryURL,
				'startTime'=> $item->listingInfo->startTime,
				'endTime'=> $item->listingInfo->endTime,
				'sellerName'=>$item->sellerInfo->sellerUserName,
				'location'=>$item->location,
				'currency'=>$currency['currencyId'],
				'body'=>''
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
			}
			if( empty( $this->_settings['template'] ) ){
				$this->_settings['template']='<table><tr><td><a href="{link}" ><img src="{image}" /></a></td><td valign="top"><a href="{link}" ><b>{title}</b></a><br/>Ended: <b>{endTime}</b><br/>Price: <b>{price} {currency}</b><br/>Seller: <b>{sellerName}</b><br/></td></tr></table>';
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
