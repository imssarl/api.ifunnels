<?php

class Project_Content_Adapter_Purevideos implements Project_Content_Interface {

	protected $_settings=array();
	protected $_counter=0;
	protected $_limit=15;
	private $_tags=array('title'=>'{title}','body'=>'{body}','description'=>'{description}');
	private $_post=array();
	private $_result=false;
	private $_isNotEmpty=false;
	private $_paging=array();
	private $_withPaging=false;
	private $_paggedData=false;
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
		$arrRes=array(
			'curpage'=>$this->_withPaging['page'],
			'urlmin'=>'/?page=1'
			);
		if ( $this->_withPaging['page'] > 1 ) {
			$arrRes['urlminus']='/?page='.($this->_withPaging['page']-1);
			$arrRes['num'][]=array(
				'number' => ($this->_withPaging['page']-1),
				'url' => '/?page='.($this->_withPaging['page']-1)
			);
		}
		$arrRes['num'][] = array (
			'sel' => 1,
			'number' => $this->_withPaging['page']
		);
		$arrRes['num'][] = array (
			'number' => ($this->_withPaging['page']+1),
			'url' => '/?page='.($this->_withPaging['page']+1)
		);
		$arrRes['urlplus']='/?page='.($this->_withPaging['page']+1);
		return $this;
	}

	public function withPaging( $_arr=array() ) {
		$this->_withPaging=$_arr;
		if ( empty($this->_withPaging['page']) )
			$this->_withPaging['page'] = 1;
		return $this;
	}	

	public function withIds( $_arrIds=array() ){
		$this->_withIds = $_arrIds;
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

	public function withRewrite( $_int ){
		$this->_withRewrite=$_int;
		return $this;
	}

	public function withJson(){
		$this->_withJson=true;
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
		$this->_settings['width']=(empty($this->_settings['width'])||is_int($this->_settings['width'])?360:($this->_settings['width']<420?420:($this->_settings['width']>960?960:$this->_settings['width'])));
		$this->_settings['height']=(empty($this->_settings['height'])||is_int($this->_settings['height'])?360:($this->_settings['height']<315?315:($this->_settings['height']>720?720:$this->_settings['height'])));
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

	public function getList( &$mixRes ){
		if( !empty( $this->_withPaging['page'] ) ){
			$this->_counter=( $this->_withPaging['page']-1 ) * $this->_limit;
		}
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
			$this->_paggedData=false;
			return $this;
		}
		$yt = new Zend_Gdata_YouTube();
		$query = new Zend_Gdata_YouTube_VideoQuery();
		$query->setMaxResults( $this->_limit );
		$query->setStartIndex( $this->_counter+1 );
		$query->setVideoQuery( $this->_settings['keywords'] );
    	$videoFeed = $yt->getVideoFeed($query);
		$index=$this->_counter;
		foreach ($videoFeed as $videoEntry) {
    		$mixRes[$index]=array(
				'id'=>$index,
				'title'=>htmlentities($videoEntry->getVideoTitle(),ENT_QUOTES | ENT_IGNORE, "UTF-8"),
				'description'=> $videoEntry->getVideoDescription(),
				'category'=>$videoEntry->getVideoCategory(),
				'tags'=>$videoEntry->getVideoTags(),
				'page_url'=>$videoEntry->getVideoWatchPageUrl(),
				'flashplayer_url'=>$videoEntry->getFlashPlayerUrl(),
				'duration'=>$videoEntry->getVideoDuration(),
				'viewcount'=>$videoEntry->getVideoViewCount(),
				'ratiing'=>$videoEntry->getVideoRatingInfo(),
				'geolocation'=>$videoEntry->getVideoGeoLocation(),
				'body'=>'<iframe width="'.$this->_settings['width'].'" height="'.$this->_settings['height'].'" src="'.$videoEntry->getFlashPlayerUrl().'" frameborder="0" allowfullscreen></iframe>' // width & height - как выберет пользователь
			);
			$index++;
		}
		$this->_isNotEmpty=!empty( $mixRes );
		$this->_paggedData = array_merge ($this->_paggedData, $mixRes);
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
