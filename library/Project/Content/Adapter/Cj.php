<?php
 
/**
 * Cj контент функционал
 */

class Project_Content_Adapter_Cj implements Project_Content_Interface {

	protected $_limit=10;
	protected $_counter=0;
	protected $_res;
	private $_settings; // параметры получаемые из шаблона
	protected $_isNotEmpty=false; // для проверки результата выборки (по умолчанию выборка пуста) отражает результаты последнего getList
	private $_pagedData=false;
	private $_withJson=false;
	private $_withRewrite=false;

	public static $templates=array (
		0=>'Default'
	);

	public function __construct() {
		if( !is_array( $_SESSION['pagedData'] )){
			$_SESSION['pagedData']=array();
		}
		$this->_pagedData=&$_SESSION['pagedData'];
	}

	public function getList( &$mixRes ) {
		if ( !empty($this->_withIds) ) {
			foreach ( $this->_withIds as $_contentId ) {
				$mixRes[] = $this->_pagedData[$_contentId];
			}
			if(!empty($this->_withJson)){
				foreach( $mixRes as &$_item ){
					$_item['fields']=serialize($_item);
				}
			}
			$this->_isNotEmpty=!empty( $mixRes );
			$this->init();
			$this->_pagedData = false;
			return $this;
		}
		if( !empty( $this->_withPaging['page'] ) ) {
			$this->_counter=( $this->_withPaging['page']-1 ) * $this->_limit;
			$_page=$this->_withPaging['page'];
		} else {
			$_page=( $this->_counter/$this->_limit <= 1 )? 1 : ceil($this->_counter/$this->_limit);
		}
		$this->_paging=array( 'curpage'=>$_page );
		$index=$this->_counter;
		$appkey = $this->_settings['appkey'];
		$_tmp=$this->_settings;
		foreach( $_tmp as $key => $v ) {
			if(empty($v)){
				unset($_tmp[$key]);
			}
		}
		unset($_tmp['appkey']);
		$httpsTail=str_replace('_','-',http_build_query($_tmp));
		$client=new Zend_Http_Client(
			"https://product-search.api.cj.com/v2/product-search?page-number=".$this->_withPaging['page']."&records-per-page=".$this->_limit."&".$httpsTail,
			array(
				'adapter'=>'Zend_Http_Client_Adapter_Curl',
				'curloptions'=>array(
					CURLOPT_SSL_VERIFYPEER=>false
				),
				'timeout'=>30
			)
		);
		$client->setHeaders('Authorization: '.$appkey);
		$xml = new SimpleXMLElement($client->request()->getBody( ));
		$this->_paging['recall'] = (string)$xml->products['total-matched'];
		foreach ($xml->products->product as $item) {
			$title = $item->xpath('name');
			$description =$item->xpath('description');
			$img = $item->xpath('image-url');
			$price = $item->xpath('price');
			$link = $item->xpath('buy-url');
			$currency = $item->xpath('currency');
			$sku = $item->xpath('sku');
			$mixRes[$index] = array (
				'id'				=> $index,
				'title'				=> (string)$title[0].' (id:'.(string)$sku[0].')',
				'name'			=> (string)$title[0],
				'description'	=> (string)$description[0],
				'image'			=> (string)$img[0],
				'price'			=> (string)$price[0],
				'link'				=> (string)$link[0],
				'currency'		=> (string)$currency[0],
				'body' => ''
			);
			$index++;
		}
		$this->_isNotEmpty=!empty( $mixRes );
		if ( !($this->_settings === $_SESSION['pagedSettings']) ) {
			$this->_pagedData=array();
		}
		$_SESSION['pagedSettings'] = $this->_settings;
		$this->_pagedData = array_merge( $this->_pagedData, $mixRes );
		if(!empty($this->_withJson)){
			foreach( $mixRes as &$_item ){
				$_item['fields']=serialize($_item);
			}
		}
		$this->init();
		return $this;
	}

	public function prepareBody( &$arrRes ){
		foreach( $arrRes as &$_item ){
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
				$this->_settings['template']='0';
			}
			$path=Zend_Registry::get( 'config' )->path->relative->source.'site1_publisher'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'cj'.DIRECTORY_SEPARATOR;
			$_item['body']=Core_View::factory( Core_View::$type['one'] )
				->setTemplate( $path.$this->_settings['template'].'.tpl' )
				->setHash( $_fields )
				->parse()
				->getResult();
		}
		$this->init();
	}

	protected function assemblyQuery() {}

	public function withRewrite( $_int ){
		$this->_withRewrite=$_int;
		return $this;
	}

	public function withJson(){
		$this->_withJson=true;
		return $this;
	}

	public function withIds( $_arrIds=array() ){
		$this->_withIds = $_arrIds;
		return $this;
	}
	
	public static function getInstance() {}

	public function setFilter( $_arrFilter=array() ) {
		$this->_settings=$_arrFilter;
		return $this;
	}

	public function getFilter( &$arrRes ) {
		$arrRes = $this->_settings;
		return $this;
	}

	public function setLimited( $_intLimit ) {
		$this->_limit=$_intLimit;
		return $this;
	}

	public function setCounter( $_intCounter ) {
		$this->_counter=$_intCounter;
		return $this;
	}

	public function getAdditional( &$arrRes ) {
		$this->_res = $arrRes;
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
		return $this;
	}

	public function withTags( $_str ){
		if( empty($_str) ){
			return $this;
		}
		$this->_withTags=$_str;
		return $this;
	}

	public function withPaging( $_arr=array() ) {
		$this->_withPaging=$_arr;
		if ( empty($this->_withPaging['page']) ) {
			$this->_withPaging['page'] = 1 ;
		} else {
			$this->arrSelect[0]['start'] = $this->arrSelect[0]['results'] * ($this->_withPaging['page']-1);
		}
		return $this;
	}

	public function getPaging( &$arrRes ) {
		$arrRes=$this->_paging;
		for($i=1; $i<$this->_paging['curpage']; $i++){
			$arrRes['num'][]=array('number'=>$i,'url'=>'./?page='.$i);
		}
		$arrRes['recfrom']=$arrRes['recto']=1;
		$arrRes['num'][] = array (
			'sel' => 1,
			'number' => $this->_paging['curpage']
		);
		if ( $arrRes['recall'] > $this->_paging['curpage']*$this->_limit) {
			$arrRes['num'][] = array (
				'number' => $this->_paging['curpage']+1,
				'url'=> './?page='.($this->_paging['curpage']+1)
			);
		}
		$this->_paging=array();
		return $this;
	}

	public function setSettings( $arrSettings ){
		if( empty($arrSettings) ){
			return false;
		}
		$this->_settings=$arrSettings;
		return $this;
	}

	public function checkEmpty() {
		return $this->_isNotEmpty;
	}

	protected function init(){
		$this->_withIds=false;
		$this->_withPaging=false;
		$this->_withJson=false;
		$this->_withRewrite=false;
	}
}
?>