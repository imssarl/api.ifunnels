<?php

class Project_Content_Adapter_Videos extends Project_Embed implements Project_Content_Interface {

	protected $_settings=array();
	protected $_counter=0;
	protected $_limit=false;
	private $_tags=array('body'=>'{body}');
	private $_post=array();
	private $_files=array();
	private $_result=false;
	private $_withJson=false;
	private $_withRewrite=false;


	public function withRewrite( $_int ){
		$this->_withRewrite=$_int;
		return $this;
	}

	public function withJson(){
		$this->_withJson=true;
		return $this;
	}

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

	public function setSettings( $arrSettings ){
		if( empty($arrSettings) ){
			return false;
		}
		$this->_settings=$arrSettings;
		return $this;
	}

	protected function init(){
		parent::init();
		$this->_withRewrite=false;
		$this->_withJson=false;
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
		$this->init();
	}
}
?>