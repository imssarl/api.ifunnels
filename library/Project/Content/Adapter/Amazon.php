<?php
class Project_Content_Adapter_Amazon implements Project_Content_Interface {

	protected $_settings=array();
	protected $_counter=0;
	protected $_limit=10;
	private $_post=array();
	private $_result=false;
	private $_isNotEmpty=false;
	private $_paging=array();
	private $_withPaging=false;
	private $_paggedData=false;
	private $_asinResults=false;
	private $_withIds=false;
	private $_withJson=false;
	private $_withRewrite=false;
	/**
	  * List of Amazon Web Service base URLs, indexed by country code
	  *
	  * @var array
	  */
	 private $_baseUriList=array(
		'US' => 'http://www.amazon.com/',
		'UK' => 'http://www.amazon.co.uk/',
		'DE' => 'http://www.amazon.de/',
		'JP' => 'http://www.amazon.co.jp/',
		'FR' => 'http://www.amazon.fr/',
		'IT' => 'http://www.amazon.it/',
		'CN' => 'http://www.amazon.cn/',
		'ES' => 'http://www.amazon.es/',
		'CA' => 'http://www.amazon.ca/',
		
		'BR' => 'http://www.amazon.com.br/',
		'IN' => 'http://www.amazon.in/',
		'MX' => 'http://www.amazon.com.mx/'
	);
	/**
	 * Список для параметра MarketplaceDomain
	 * http://docs.aws.amazon.com/AWSECommerceService/latest/DG/MarketplaceDomainParameter.html
	 * для amazon не используется данный параметр, в массиве он только для фронтенда, чтобы выводить в селекте!
	 *
	 * @var array
	 */
	public static $marketplaceDomain=array(
		'Amazon'=>array(
			'US' => 'amazon.com',
			'UK' => 'amazon.co.uk',
			'DE' => 'amazon.de',
			'JP' => 'amazon.co.jp',
			'FR' => 'amazon.fr',
			'IT' => 'amazon.it',
			'CN' => 'amazon.cn',
			'ES' => 'amazon.es',
			'CA' => 'amazon.ca',
			
			'BR' => 'amazon.com.br',
			'IN' => 'amazon.in',
			'MX' => 'amazon.com.mx',
		),
		'Supply'=>array(
			'US'=>'www.amazonsupply.com',
		),
		'Javari'=>array(
			'DE'=>'www.javari.de',
			'JP'=>'www.javari.jp',
			'UK'=>'www.javari.co.uk'
		)
	);

	public function __construct(){
		if( !is_array( $_SESSION['paggedData'] )){
			$_SESSION['paggedData']=array();
		}
		$this->_paggedData=&$_SESSION['paggedData'];
		$this->_asinResults=&$_SESSION['asinData'];
		$this->_paggedSettings=&$_SESSION['paggedSettings'];
	}

	private $_base='';
	private $_queryString='';
	
	private function _getSignature(){
		return urlencode( base64_encode( hash_hmac( "sha256", "GET\n".str_replace( 'http://', '', trim( str_replace('www','webservices',$this->_baseUriList[$this->_settings['site']]), '/' ) )."\n/onca/xml\n".$this->_queryString, $this->_settings['secret_key'], true ) ) );
	}
	
	private function getUrl($url){
		if (ini_get('allow_url_fopen') && function_exists('file_get_contents')){
			return @file_get_contents($url);
		}
		if (ini_get('allow_url_fopen') && !function_exists('file_get_contents')){
			if (false === $fh=fopen($url, 'rb', false)){
				user_error('file_get_contents() failed to open stream: No such file or directory', E_USER_WARNING);
				return false;
			}
			clearstatcache();
			if ($fsize=@filesize($url)){
				$data=fread($fh, $fsize);
			} else {
				$data='';
				while (!feof($fh)){
					$data .= fread($fh, 8192);
				}
			}
			fclose($fh);
			return $data;
		}
		if (function_exists('curl_init')){
			$c=curl_init($url);
			curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($c, CURLOPT_TIMEOUT, 15);
			$data=@curl_exec($c);
			curl_close($c);
			return $data;
		}
		return false;
	}

	/*
		$fields['Operation']='ItemSearch';
	
	*/
	
	private function useTracker( $fieldsAdd=array() ){
		if( $this->_settings['affiliate'] == '' ){
			return array();
		}
		$fields=array();
		$fields['Service']='AWSECommerceService';
		$fields['AWSAccessKeyId']=$this->_settings['api_key'];
		$fields['AssociateTag']=$this->_settings['affiliate'];
		ksort($fieldsAdd);
		foreach( $fieldsAdd as $key=>$value ){
			$fields[$key]=$value;
		}
		$fields['Version']='2011-08-01';
		$fields['Timestamp']=gmdate('Y-m-d\TH:i:s\Z');
		$query=array();
		ksort($fields);
		foreach( $fields as $key=>$value ){
			$query[]=$key.'='.urlencode($value);
		}
		$this->_queryString=implode('&', $query);
	   $this->_queryString=str_replace( ' ','%20', str_replace( ';',urlencode(';'), str_replace( '+','%20', str_replace( ',', '%2C', str_replace( ':', '%3A', $this->_queryString ) ) ) ) );
		$content=$this->getUrl( 'http://qjmpz.com/services/amazon.php?link='.base64_encode( str_replace('www','webservices',$this->_baseUriList[$this->_settings['site']])."onca/xml".'?'.$this->_queryString.'&Signature='.$this->_getSignature() ) );
		$_xmlContent=@simplexml_load_string( $content );
		if( $_xmlContent !== false || isset( $_xmlContent->Items->Request->Errors->Error->Message ) || !empty( $_xmlContent->Items->Request->Errors->Error->Message ) ){
			return json_decode(json_encode($_xmlContent->Items), TRUE);
		}else{
			$c=curl_init( str_replace('www','webservices',$this->_baseUriList[$this->_settings['site']])."onca/xml".'?'.$this->_queryString.'&Signature='.$this->_getSignature() );
			curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($c, CURLOPT_TIMEOUT, 15);
			$_xmlContent=@simplexml_load_string( curl_exec($c) );
			curl_close($c);
			$this->_error=(string)$_xmlContent->Error->Message;
			return Core_Data_Errors::getInstance()->setError( $this->_error );
		}
	}
	
	public static $templates=array (
		0=>'Default',
		1=>'Related Items',
		2=>'Clean',
	);

	public static function getInstance(){}

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
			$arrRes['urlmin']='/?page=1';
		}
		$arrRes['num'][]=array (
			'sel' => 1,
			'number' => $this->_paging['curpage']
		);
		if( $this->_paging['nextpage'] != false ){
			$arrRes['num'][]=array (
				'number' => $this->_paging['curpage']+1,
				'url'=> './?page='.($this->_paging['curpage']+1)
			);
			$arrRes['urlplus']='/?page='.($this->_paging['curpage']+1);
		}
		$this->_paging=array();
		return $this;
	}

	public function withPaging( $_arr=array() ){
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

	public function withCategories( $_arr=array() ){
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

	public function withRewrite( $_int ){
		$this->_withRewrite=$_int;
		return $this;
	}

	public function withJson(){
		$this->_withJson=true;
		return $this;
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
		$this->_withRewrite=false;
		$this->_withJson=false;
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
		if( !empty($this->_withIds) ){
			foreach ( $this->_withIds as $_contentId ){
				if( isset( $this->_paggedData[$_contentId] ) ){
					if( $this->getContent( $this->_paggedData[$_contentId] ) ){
						$mixRes[]=$this->_paggedData[$_contentId];
					}
				}elseif( isset( $this->_asinResults[$_contentId] ) ){
					if( $this->getContent( $this->_asinResults[$_contentId] ) ){
						$mixRes[]=$this->_asinResults[$_contentId];
					}
				}else{
					$_arrItem=array('asin'=>$_contentId);
					if( $this->getContent( $_arrItem ) ){
						$mixRes[]=$_arrItem;
					}
				}
			}
			//var_dump( count( $mixRes ) );exit;
			if( !empty($this->_withJson) ){
				foreach( $mixRes as &$_item ){
					$_item['fields']=serialize($_item);
				}
			}
			$mixRes=array_filter($mixRes);
			$this->_isNotEmpty=!empty( $mixRes );
			$this->init();
			$this->_paggedData=false;
			$this->_asinResults=false;
			$this->_paggedSettings=false;
			return $this;
		}
		$_testSettings=$this->_settings;
		unset( $_testSettings['asin'] );
		if ( $this->_paggedSettings != $_testSettings ){
			$this->_paggedData=false;
			$this->_paggedSettings=$_testSettings;
		}
		if($this->_limit>10){
			$this->_limit=10;
		}
		if( !empty( $this->_withPaging['page'] ) ){
			$this->_counter=( $this->_withPaging['page']-1 ) * $this->_limit;
			$_page=$this->_withPaging['page'];
		} else {
			$_page=ceil(($this->_counter+$this->_limit)/10);
		}
		if( $_page > 0 && $_page < 5 ){
			$this->_paging=array( 'curpage'=>$_page, 'nextpage'=>true );
		}else{
			$this->_paging=array( 'curpage'=>5, 'nextpage'=>false );
			$_page=5;
		}
		if( !empty( $this->_settings['asin'] ) ){
			foreach( explode(',',$this->_settings['asin'] ) as $_asin ){
				$item=$this->useTracker(array(
					'ItemId'=>trim($_asin),
					'Operation'=>'ItemLookup',
					'Condition'=>'All',
					'IdType'=>'ASIN',
					'ResponseGroup'=>'OfferFull,Accessories,EditorialReview,Images,ItemAttributes,Reviews,Medium'
				));
				if( empty( $item ) || isset( $item['Request']['Errors']['Error']['Message'] ) ){
					continue;
				}
				$this->_asinResults[$item['Item']['ASIN']]=array(
					'id'=>(string)$item['Item']['ASIN'],
					'asin'=>(string)$item['Item']['ASIN'],
					'title'=>(string)$item['Item']['ItemAttributes']['Title'],
					'preview'=>((string)$item['Item']['SmallImage'])?(string)$item['Item']['SmallImage']['URL']:null,
					'link2view'=>$this->_baseUriList[$this->_settings['site']].'dp/'.(string)$item['Item']['ASIN'].'/',
					'data'=>$item
				);
			}
		}
		if(stripos($this->_settings['category'],'::')!==false){
			$_tmp=explode('::',$this->_settings['category']);
			$this->_settings['category']=$_tmp[0];
			$this->_settings['BrowseNode']=$_tmp[1];
		}
		if(!empty($this->_settings['BrowseNode'])&&$this->_settings['category']==$this->_settings['keywords']){
			$this->_settings['keywords']='';
		}
		$searchIndex='All';
		$marketplaceDomain='';
		if(!empty($this->_settings['category'])){
			$searchIndex=str_replace(' ','',$this->_settings['category']);
		}
		if(!empty($this->_settings['marketplacedomain'])&&$this->_settings['marketplacedomain']!='Amazon'){
			$searchIndex='Marketplace';
			$marketplaceDomain=self::$marketplaceDomain[$this->_settings['marketplacedomain']][$this->_settings['site']];
		}
		$results=$this->useTracker(array(
			'Operation'=>'ItemSearch',
			'SearchIndex'=>$searchIndex,
			'Keywords'=>$this->_settings['keywords'],
			'BrowseNode'=>(!empty($this->_settings['BrowseNode']))?$this->_settings['BrowseNode']:'',
			'ItemPage'=>$_page,
			'ResponseGroup'=>'OfferFull,Accessories,EditorialReview,Images,ItemAttributes,Reviews,Medium',
			'MarketplaceDomain'=>$marketplaceDomain
		));
		$_number=$this->_counter;
		if( empty( $results ) ){
			return $this;
		}
		if( $results['TotalResults'] == 1 && isset( $results['Item']['ASIN'] ) ){
			$results['Item']=array( $results['Item'] );
		}
		foreach( $results['Item'] as $item ){
			$mixRes[$_number]=array(
				'id'=>$_number,
				'asin'=>$item['ASIN'],
				'title'=>$item['ItemAttributes']['Title'],
				'preview'=>($item['SmallImage'])?$item['SmallImage']['URL']:null,
				'link2view'=>$this->_baseUriList[$this->_settings['site']].'dp/'.$item['ASIN'].'/',
				'data'=>$item
			);
			$_number++;
		}
		if(!empty($mixRes)){
			$this->_paggedData=(empty($this->_paggedData)?array():$this->_paggedData )+$mixRes;
		}
		$mixRes=array_filter(array_merge( (empty($this->_asinResults)?array():$this->_asinResults ),$this->_paggedData ));
		if($this->_counter<count($mixRes)){
			$mixRes=array_slice(
				$mixRes,
				$this->_counter,
				$this->_limit,
				true
			);
		} else {
			$mixRes=array_slice(
				$mixRes,
				ceil($this->_counter%10),
				$this->_limit,
				true
			);
		}
		$this->_isNotEmpty=!empty( $mixRes );
		if(!empty($this->_withJson)){
			foreach( $mixRes as $_key=>&$_item ){
				$_return=$this->getContent( $_item );
				if( $_return !== false ){
					$_item['fields']=serialize($_item);
				}else{
					unset( $mixRes[$_key] );
				}
			}
			$mixRes=array_filter($mixRes);
		}
		$this->init();
		return $this;
	}

	public function getContent( &$arrItem ){
		$searchIndex='All';
		$marketplaceDomain='';
		if(!empty($this->_settings['marketplacedomain'])&&$this->_settings['marketplacedomain']!='Amazon'){
			$searchIndex='Marketplace';
			$marketplaceDomain=self::$marketplaceDomain[$this->_settings['marketplacedomain']][$this->_settings['site']];
		}
		if( isset( $arrItem['data'] ) && isset( $arrItem['data']['ASIN'] ) ){
			$item=$arrItem['data'];
		}else{
			$item=$this->useTracker(array(
				'Operation'=>'ItemLookup',
				'ItemId'=>$arrItem['asin'],
				'Condition'=> $searchIndex,
				'IdType'=>'ASIN',
				'ResponseGroup'=>'OfferFull,Accessories,EditorialReview,Images,ItemAttributes,Reviews,Medium',
				'MarketplaceDomain'=>$marketplaceDomain
			));
			if( empty( $item ) ){
				return false;
			}
			$item=$item['Item'];
		}
		if( empty( $item ) ){
			return false;
		}
		$curlData=Core_Curl::getInstance();
		/*
		if ( $curlData->getContent( $this->_baseUriList[$this->_settings['site']].'reviews/iframe?akid='.$this->_settings['api_key'].'&alinkCode=xm2&asin='.$arrItem['asin'].'&atag='.$this->_settings['affiliate'].'&exp='.gmdate("Y-m-d\TH:i:s\Z").'&v=2&sig='.$this->_getSignature() ) ){
			preg_match('~stars-(.?)-0~si', $curlData->getResponce(), $stars);
		}
		*/
		$_body=$item['EditorialReviews']['EditorialReview']['Content'];
		if( !empty( $this->_settings['length'] ) && $this->_settings['length'] != 'full' && strlen($_body) > $this->_settings['length'] ){
			$_str=new Core_String( strip_tags($_body) );
			$_body=$_str->ellipsis( $this->_settings['length'] );
		}
		//var_dump( $arrItem['asin'] );
		if( strpos( $this->_settings['asin'], $arrItem['asin'] ) === false ){
			if( $this->_settings['skip'] && $this->_settings['skip'] == Project_Content_Adapter_Amazon::NO_FOUND_DESCRIPTION
					&& empty($_body) ){
				//var_dump( 'NO_FOUND_DESCRIPTION' );
				return false;
			}
			if( $this->_settings['skip'] && $this->_settings['skip'] == Project_Content_Adapter_Amazon::NO_FOUND_IMAGES
					&& !$item['LargeImage'] ){
				//var_dump( 'NO_FOUND_IMAGES' );
				return false;
			}
			if( $this->_settings['skip'] && $this->_settings['skip'] == Project_Content_Adapter_Amazon::NO_FOUND_DESC_OR_IMG
					&& (!$item['LargeImage'] || empty($_body) ) ){
				//var_dump( 'NO_FOUND_DESC_OR_IMG' );
				return false;
			}
		}else{
			//var_dump( 'user asin' );
		}
		
		// =======================
		$_relatedItems='';
		foreach ( $item['Accessories'] as $value ){
			$_relatedItems .= '<a href="http://'.$this->_base.'/dp/'.$value['ASIN'].'/?tag='.$this->_settings['affiliate'].'">'.$value['Title'].'<br/>';
		}
		$price='';
		$_intCurrencyCode=$_intAmounte=$_intLowestNewPrice=$_strLowestNewPriceCurrency=false;
		if( isset( $item['ItemAttributes']['ListPrice']['CurrencyCode'] ) && isset( $item['ItemAttributes']['ListPrice']['Amount'] ) ){
			$_intCurrencyCode=$item['ItemAttributes']['ListPrice']['CurrencyCode'];
			$_intAmounte=(int)$item['ItemAttributes']['ListPrice']['Amount'];
		}
		if( isset( $item['OfferSummary']['LowestNewPrice'] ) && isset( $item['OfferSummary']['LowestNewPrice']['CurrencyCode'] ) ){
			$_intLowestNewPrice=(int)$item['OfferSummary']['LowestNewPrice']['Amount'];
			$_strLowestNewPriceCurrency=$item['OfferSummary']['LowestNewPrice']['CurrencyCode'];
		}
		if( isset( $item['OfferSummary']['LowestUsedPrice'] ) && isset( $item['OfferSummary']['LowestUsedPrice']['CurrencyCode'] ) && $item['OfferSummary']['LowestUsedPrice']['Amount'] < $_intLowestNewPrice ){
			$_intLowestNewPrice=(int)$item['OfferSummary']['LowestUsedPrice']['Amount'];
			$_strLowestNewPriceCurrency=$item['OfferSummary']['LowestUsedPrice']['CurrencyCode'];
		}
		$currency=$_offer->CurrencyCode;
		for( $_count=1; $_count<(int)$item['Offers']['TotalOffers']; $_count++ ){
			if( $_intLowestNewPrice===false || $_intLowestNewPrice>$item['Offers']['Offer'][$_count-1]['OfferListing']['Price']['Amount'] ){
				$_intLowestNewPrice=(int)$item['Offers']['Offer'][$_count-1]['OfferListing']['Price']['Amount'];
				$_strLowestNewPriceCurrency=$item['Offers']['Offer'][$_count-1]['OfferListing']['CurrencyCode'];
			}
		}
		if( ( $item['ItemAttributes']['Binding'] == 'Kindle Edition' || $item['ItemAttributes']['Binding'] == 'Format Kindle' ) && $_intLowestNewPrice===false ){
			$price=' Kindle ebook - check current price <a href="'.urldecode($item['DetailPageURL']).'">HERE</a>';
		}elseif( $_intLowestNewPrice===false ){
			$price=' Check options and current price <a href="'.urldecode($item['DetailPageURL']).'">HERE</a>';
		}elseif( $_intLowestNewPrice === 0 ){
			$price=' Special Offer';
		}else{
			$price='&nbsp;'.( $this->currency($_strLowestNewPriceCurrency) ).sprintf("%01.2f", $_intLowestNewPrice/100).'</span> <span style="font-weight:normal;color:black;font-size:14px;">(as of '.date( 'j/m/Y H:i', time() ).' PST - </span><a onmouseover="if(this.children[0].style){this.children[0].style.display=\'block\';return}this.firstElementChild.style.display=\'block\';"  onmouseout="if(this.children[0].style){this.children[0].style.display=\'none\';return}this.firstElementChild.style.display=\'none\';" style="cursor:pointer;color:green;font-weight:bold;">Details<span style="position: absolute;z-index: 4;width: 350px;font-size:14px;font-weight: normal;border: 0;border-color: #bbb;border-radius: .3em;box-shadow: inset 0 1px rgba(255,255,255,0.35), 0 0 0 1px rgba(140,126,126,0.5);background-color: #f0eded;color: #524d4d;font-family:\'helvetica neue\',arial,sans-serif;text-shadow: 0 1px rgba(255,255,255,0.9);text-align: center;cursor: pointer;line-height: 1em;margin: 0;padding: .45em .825em .45em;display: none;">Product prices and availability are accurate as of the date/time indicated and are subject to change. Any price and availability information displayed on Amazon Site at the time of purchase will apply to the purchase of this product.</span></a><span  style="font-weight:normal;color:black;font-size:14px;"> )';
		}
	//	$_formatedCurrentPrice=;
	//	if( $_intLowestNewPrice >= $_intAmounte ){
	//		$_formatedCurrentPrice=( ( $this->currency($_intCurrencyCode) ).sprintf("%01.2f", $_intAmounte*0.0105) );
	//	}
		$arrItem=array(
			'id'			=>  $arrItem['id'],
			'title'			=>	$item['ItemAttributes']['Title'],
			'link'			=>	urldecode($item['DetailPageURL']),
			'body'			=>	$_body,
			'LargeImage'	=> 	(isset($item['LargeImage']['URL']))?$item['LargeImage']['URL']:'',
			'oldPrice'		=>	( $this->currency($_intCurrencyCode) ).sprintf("%01.2f", $_intAmounte/100),//$_formatedCurrentPrice,
			'smallPrice'	=>	$price,
			'rating'		=> 	$item['SalesRank'],
			'ASIN'			=> 	$arrItem['asin'],
			'feature'		=>	implode('<br/>',$item['ItemAttributes']['Feature']),
			'availability'	=>	(count($item['Offers']['Offers'])!=0)?$item['Offers']['Offers'][0]['Availability']:'unspecified',
			'stars'			=>	(isset($stars))?$stars[1]:0,
			'relatedItems'	=>	$_relatedItems,
		);
		return true;
	}

	private function currency( $_strCode ){
		switch( $_strCode ){
			case 'USD' : return '&dollar;'; break;
			case 'EUR' : return '&euro;'; break;
			case 'GBP' : return '&pound;'; break;
			case 'CNY' : return '&yen;'; break;
			case 'JPY' : return '&yen;'; break;
			case 'CAD' : return 'CDN&dollar;'; break;
			default: '&dollar;';
		}
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
			if (empty($this->_settings['template'])){
				$this->_settings['template']='0';
			}
			$path=Zend_Registry::get( 'config' )->path->relative->source
					.'site1_publisher'.DIRECTORY_SEPARATOR
					.'templates'.DIRECTORY_SEPARATOR
					.'templates'.DIRECTORY_SEPARATOR
					.'amazon'.DIRECTORY_SEPARATOR
					.$this->_settings['site'].DIRECTORY_SEPARATOR;
			$_item['body']=Core_View::factory( Core_View::$type['one'] )
				->setTemplate( $path.$this->_settings['template'].'.tpl' )
				->setHash( $_fields )
				->parse()
				->getResult();
			if( strip_tags( $_item['body'] ) != $_item['body'] ){
				$_item['body']=preg_replace("/(\r\n|\r|\n|\t)/", "", $_item['body']);
			}
		}
		$this->init();
	}

	public static function getCategory( $_categoryName='All', $coreTag='US' ){
		$cat=new Core_Category( 'Amazon '.$coreTag );
		$cat->get( $_arrCats );
		$_categoryName=preg_replace( '/\s+/', '', $_categoryName);
		similar_text( $_categoryName, 'All', $_percent );
		$_result=array(array( 
			'percent' => $_percent, 
			'data' => array(
				'title'=>'All'
			)
		));
		foreach ($_arrCats as $_value) {
			similar_text( $_categoryName, $_value[ 'title' ], $_percent );
			$_result[]=array(
				'percent' => $_percent, 
				'data' => $_value 
			);
		}
		$_max_percent=$_result[0]['percent'];
		$category=$_result[0]['data'];
		foreach ($_result as $_item) {
			if( $_item['percent'] > $_max_percent ) {
				$_max_percent=$_item['percent'];
				$category=$_item['data'];
			}
		}
		return $category;
	}

}
