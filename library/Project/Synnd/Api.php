<?php
/**
 * WorkHorse Framework
 *
 * @category Project
 * @package Project_Synnd_Api
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2009-2012, web2innovation
 * @date 02.04.2012
 * @version 1.0
 */


/**
 * Synnd adapter
 *
 * @category Project
 * @package Project_Synnd_Api
 * @copyright Copyright (c) 2009-2012, web2innovation
 * @license http://opensource.org/licenses/ MIT License
 */
class Project_Synnd_Api{

	private static $_synndService=1; // 0 -test; 1-work
	
	private static $_synndUrl=array(
		0=>'http://api.sandbox.synnd.com/synnd/services/json/',
		1=>'http://api.synnd.com/synnd/services/json/',
	);
	
	private $_strUsername=array(
		0=>'JPS-test',
		1=>'jpssndapi1'
	);
	private $_strPassword=array(
		0=>'test-JPS',
		1=>'jpssynnd'
	);
	
	protected $_errors=array();
	protected $_project=array();
	protected $_content=array();

	public function __construct() {
		switch( @$_SERVER['HTTP_HOST'] ) {
			case 'cnm.local':
			case 'cnm.cnmbeta.info':
				self::$_synndService=0;
			break;
			case 'members.creativenichemanager.info': // репликация на продакшн
			default:
				self::$_synndService=1;
			break;
		}
		// Get sessionId
		unset( $_SESSION['sessionId'] );
		if( !isset( $_SESSION['sessionId'] ) ) {
			$_curl=Core_Curl::getInstance();
			$_curl
				->withCookie( 'api.synnd.com' )
				->withHeader()
				->setPost( http_build_query( array(
					'function'=>'login',
					'userName'=>$this->_strUsername[self::$_synndService],
					'password'=>$this->_strPassword[self::$_synndService],
				) ) )
				->getContent( self::$_synndUrl[self::$_synndService].'login/' );
			$_arrResp=Core_String::json2php( $_curl->getResponce() );
			if( !$_arrResp['success'] ) {
				$this->setError( $_arrResp['errors'] );
			}else{
				$_SESSION['sessionId']=str_replace( 'sessionId=','', $_curl->getCookie() );
			}
		}
	}
	
	public function getErrors( &$arrRes ) {
		$arrRes=$this->_errors;
		return $this;
	}

	public function setError( $_strError='' ) {
		$this->_errors[]=$_strError;
		return false;
	}

	public function setProject( $arrRes=array() ) {
		$this->_project=$arrRes;
		if( empty( $this->_project['domen'] ) ) {
			Project_Publishing_Site::setById( $this->_project['site_id'] );
			$this->_project['domen']='http://'.Project_Publishing_Site::$site['domen'];
		}
		return $this;
	}

	public function getProject( &$arrRes ) {
		$arrRes=$this->_project;
		return $this;
	}

	public function setContent( $arrRes=array() ) {
		$this->_content=$arrRes;
		return $this;
	}

	public function getContent( &$arrRes ) {
		$arrRes=$this->_content;
		return $this;
	}

	public function createCampaignSetup() {
		if( !empty( $this->_errors ) ) {
			return $this;
		}
		// Get all synnd sites
		if( !isset( $this->_content['synndSiteId'] ) ) {
				$this->getWebsiteSettings();
			// Create Site
			if( $this->_content['synndSiteId']==0 ) {
				$this->updateWebsite();
			}
		}
		// Create Campaign Setup
		$this->updateCampaignSetup();
		return $this;
	}

	public function titleModifier( $title ) {
		preg_match_all("/\{.*?\}|[\w\']+/im",$title,$_words);
		$wordsCount=count( $_words[0] );
		foreach( $_words[0] as &$_word ){
			$_word=explode( '|', trim($_word,'}{') );
			if( count( $_word )>1 ){
				foreach( $_word as $key=>$_w ){
					if( count( explode( ' ', $_w ) )+$wordsCount-1<4 && count( $_word )>1 ){
						unset( $_word[$key] );
					}
				}
			}
			if( count( $_word )>1 )
				$_word='{'.implode('|',$_word).'}';
			else
				$_word=$_word[0];
		}
		$title=implode(' ',$_words[0]);
		if( strpos( $title, '{' )!==false ){
			return $title;
		}
		$_arrWords=explode( ' ', $title );
		foreach( $_arrWords as &$_strWord ) {
			if( $_strWord != '' )
				$_strWord='{'.$_strWord.'|'.$_strWord.'}';
		}
		return trim( implode( ' ', $_arrWords ) );
	}

	public function descriptionModifier( $string ) {
		preg_match_all("/\{.*?\}|[\w\']+/im",$string,$_words);
		$wordsCount=count( $_words[0] );
		foreach( $_words[0] as &$_word ){
			$_word=explode( '|', trim($_word,'}{') );
			if( count( $_word )>1 ){
				foreach( $_word as $key=>$_w ){
					if( count( explode( ' ', $_w ) )+$wordsCount-1>9 && count( $_word )>1 ){
						unset( $_word[$key] );
					}
				}
			}
			if( count( $_word )>1 )
				$_word='{'.implode('|',$_word).'}';
			else
				$_word=$_word[0];
		}
		$string=implode(' ',$_words[0]);
		if( strpos( $string, '{' )!==false ){
			return $string;
		}
		$_arrWords=explode( ' ', $string );
		foreach( $_arrWords as &$_strWord ) {
			if( $_strWord != '' )
				$_strWord='{'.$_strWord.'|'.$_strWord.'}';
		}
		return trim( implode( ' ', $_arrWords ) );
	}

	public function tagModifier( $strTags ) {
		$_countTags=1;
		$arrTags=explode( ',', $strTags );
		foreach( $arrTags as $_key=>&$_tag ) {
			$_tag=self::spinningString( trim( $_tag ) );
			preg_match_all("/\{.*?\}|[\w\']+/im",$_tag,$_words);
			$wordsCount=count( $_words[0] );
			foreach( $_words[0] as &$_word ){
				$_word=explode( '|', trim($_word,'}{') );
				if( count( $_word )>1 ){
					foreach( $_word as $key=>$_w ){
						if( count( explode( ' ', $_w ) )+$wordsCount-1>3 && count( $_word )>1 ){
							unset( $_word[$key] );
						}
					}
				}
				if( count( $_word )>1 )
					$_word='{'.implode('|',$_word).'}';
				else
					$_word=$_word[0];
			}
			
			$_tag=implode(' ',$_words[0]);
		}
		return $arrTags;
	}

	public function createPromotion( $promotionSettings=array() ) {
		if( !empty( $this->_errors ) || empty( $promotionSettings['promote_count'] ) || $promotionSettings['promote_count']==0 ) {
			return $this->setError( 'Promotion type is broken' );
		}
		// Get Campaign Options
		$_curl=Core_Curl::getInstance();
		$_data=http_build_query( array(
			'sessionId'=>$_SESSION['sessionId'],
			'function'=>'newCampaign',
			'campaignSetupId'=>$this->_content['synndCampaignId'],
			'type'=>Project_Synnd::$promotionTypes[$promotionSettings['flg_type']]['title'],
		) );
		$_curl
			->setPost($_data)
			->getContent( self::$_synndUrl[self::$_synndService].'api/' );
		$_json=str_replace( array("\\\"","\\'"), array('``','`'),$_curl->getResponce());
		$_arrCampaign=Core_String::json2php( $_json );
		// Create Campaign
		$_arrCampaign['sessionId']=$_SESSION['sessionId'];
		$_arrCampaign['function']='updateCampaign';
		if( isset( $_arrCampaign['url2'] ) ) {
			$_arrCampaign['url2']=$_arrCampaign['url'];
		}
		if( isset( $_arrCampaign['title'] ) ) {
			$_arrCampaign['title']=$_arrCampaign['name'];
		}
		if( $promotionSettings['flg_type']==2 && !empty( $this->_content['category_id'] ) ) { // Social News required
			$_arrCampaign['siteId']=$this->_content['site_id'];
			$_arrCampaign['categoryId']=$this->_content['category_id'];
		}
		$_arrCampaign['promotionsMax']=($promotionSettings['promote_count']>$_arrCampaign['promotionsMaxLimit'])?$_arrCampaign['promotionsMaxLimit']:$promotionSettings['promote_count'];
		$_arrCampaign['promotionsMaxPerDay']=($promotionSettings['promote_count']<5)?$promotionSettings['promote_count']:5;
		$_curl=Core_Curl::getInstance();
		$_curl
			->setPost( http_build_query( $_arrCampaign ) )
			->getContent( self::$_synndUrl[self::$_synndService].'api/' );
		$_arrCampaign=Core_String::json2php( $_curl->getResponce() );
		if( !$_arrCampaign['success'] ) {
			return $this->setError( $_arrCampaign['errors']['reason'] );
		}
		return true;
	}

	public function createCampaign(){
		if( !empty( $this->_errors ) ) {
			return false;
		}
		if( !$this->updateCampaignContent( 'Title', $this->titleModifier( self::spinningString( trim( $this->_content['title'] ) ) ), $this->_content['titleId'] ) ){ 
			$this->_errors=array();
			if( !$this->updateCampaignContent( 'Title', $this->titleModifier( trim( $this->_content['title'] ) ), $this->_content['titleId'] ) )
				return false;
		}
		if( !$this->updateCampaignContent( 'Description', $this->descriptionModifier( self::spinningString( $this->_content['description'] ) ), $this->_content['descriptionId'] ) ){
			$this->_errors=array();
			if( !$this->updateCampaignContent( 'Description', $this->descriptionModifier( $this->_content['description'] ), $this->_content['descriptionId'] ) )
				return false;
		}
		foreach( $this->tagModifier( $this->_content['tags'] ) as $key=>$tag ){
			if( !isset( $this->_content['tagsIds'] ) ){
				$this->_content['tagsIds']=array();
			}
			if( !isset( $this->_content['tagsIds'][$key] ) ){
				$this->_content['tagsIds'][$key]=0;
			}
			if( !$this->updateCampaignContent( 'Tags', trim($tag), $this->_content['tagsIds'][$key] ) ){
				return false;
			}
		}
		return true;
	}

	public function someFunction( $_function=null ) {
		if( empty( $_function ) ) {
			return false;
		}
		$_curl=Core_Curl::getInstance();
		$_curl
			->setPost( http_build_query( array(
				'sessionId'=>$_SESSION['sessionId'],
				'function'=>$_function,
			) ) )
			->getContent( self::$_synndUrl[self::$_synndService].'api/' );
		return Core_String::json2php( $_curl->getResponce() );
	}

	public function getWebsiteSettings() {
		$_curl=Core_Curl::getInstance();
		$_curl
			->setPost( http_build_query( array(
				'sessionId'=>$_SESSION['sessionId'],
				'function'=>'getAllWebsitesForUser',
				'gridList'=>'true',
			) ) )
			->getContent( self::$_synndUrl[self::$_synndService].'api/' );
		$_synnds=Core_String::json2php( $_curl->getResponce() );
		$this->_content['synndSiteId']=0;
		if( isset( $_synnds['results'] ) ) {
			foreach( $_synnds['results'] as $_site ) {
				if( $_site['url']==$this->_project['domen'] ) {
					$this->_content['synndSiteId']=$_site['id'];
					continue;
				}
			}
		}
	}
	
	private function updateDomenProtocol() {
		return (stripos($this->_project['domen'],'://')===false?'http://':'').$this->_project['domen'];
	}
	
	public function updateWebsite() {
		$_curl=Core_Curl::getInstance();
		$_data=http_build_query( array(
			'sessionId'=>$_SESSION['sessionId'],
			'function'=>'updateWebsite',
			'status'=>'Active',
			'id'=>$this->_content['synndSiteId'],
			'name'=>$this->_content['title'],
			'url'=>$this->updateDomenProtocol(),
		) );
		$_curl
			->setPost($_data)
			->getContent( self::$_synndUrl[self::$_synndService].'api/' );
		$_responce=Core_String::json2php( $_curl->getResponce() );
		if( !$_responce['success'] ) {
			return $this->setError( $_responce['errors']['reason'] );
		}
		$this->_content['synndSiteId']=$_responce['id'];
	}
	
	public function updateCampaignSetup(){
		$_curl=Core_Curl::getInstance();
		$_data=http_build_query( array(
			'sessionId'=>$_SESSION['sessionId'],
			'function'=>'updateCampaignSetup',
			'id'=>(isset($this->_content['synndCampaignId'])?$this->_content['synndCampaignId']:0),
			'name'=>$this->_content['title'],
			'url'=>$this->updateDomenProtocol(),
			'websiteId'=>$this->_content['synndSiteId'],
			'categoryId'=>$this->_content['category_id'],
		) );
		$_curl
			->setPost($_data)
			->getContent( self::$_synndUrl[self::$_synndService].'api/' );
		$_responce=Core_String::json2php( $_curl->getResponce() );
		if( !$_responce['success'] ) {
			return $this->setError( $_responce['errors']['reason'] );
		}
		$this->_content['synndCampaignId']=$_responce['id'];
	}
	
	public function updateCampaignContent( $_type='', $_content='', &$_id ) {
		if( !in_array( $_type, array( 'Article', 'AuthorBio', 'Comment', 'Description', 'Profile', 'Tags', 'Title' ) ) ) {
			return $this->setError( 'Content type is broken' );
		}
		$_curl=Core_Curl::getInstance();
		$_data=http_build_query( array(
			'sessionId'=>$_SESSION['sessionId'],
			'function'=>'updateCampaignContent',
			'id'=>( empty($_id)?0:$_id ),
			'campaignSetupId'=>$this->_content['synndCampaignId'],
			'type'=>$_type,
			'content'=>$_content,
		) );
		$_curl
			->setPost($_data)
			->getContent( self::$_synndUrl[self::$_synndService].'api/' );
		$_arrSite=Core_String::json2php( $_curl->getResponce() );
		if( !$_arrSite['success'] ) {
			return $this->setError( $_arrSite['errors']['reason'] );
		}
		$_id=$_arrSite['id'];
		return true;
	}
	
	public function getCategories() {
		// Get Categories
		$_curl=Core_Curl::getInstance();
		$_curl
			->setPost( http_build_query( array(
				'sessionId'=>$_SESSION['sessionId'],
				'function'=>'getAllCategoriesForSite',
				'gridList'=>'false',
				'siteId'=>'0',
			) ) )
			->getContent( self::$_synndUrl[self::$_synndService].'api/' );
		return Core_String::json2php( str_replace( "\\'", "'", preg_replace( '/"id":(.\d*),/is','"id":"\\1",', $_curl->getResponce() ) ) );
	}
		
	public function getAllSitesForType( $type ) { // Article Directory, Blog, Bookmarking, Micro Blog, Profile, Social Network, Social News, Web 2.0, Web Directory
		// Get Sites
		$_curl=Core_Curl::getInstance();
		$_curl
			->setPost( http_build_query( array(
				'sessionId'=>$_SESSION['sessionId'],
				'function'=>'getAllSitesForType',
				'type'=>$type,
				'active'=>true
			) ) )
			->getContent( self::$_synndUrl[self::$_synndService].'api/' );
		$arrSites=array();
		$_responce=$_curl->getResponce();
		foreach( Core_String::json2php( str_replace( "\\'", "'", preg_replace( '/"id":(.\d*),/is','"id":"\\1",', $_responce ) ) ) as $_data ){
			$arrSites[]=array(
				'id'=>$_data['id'],
				'name'=>$_data['name']
			);
		}
		return $arrSites;
	}
	
	public function getAllPromotionsForCampaign( $id ) {
		// Use this function to retrieve the list of all promotions for the given campaign ID
		$_curl=Core_Curl::getInstance();
		$_curl
			->setPost( http_build_query( array(
				'sessionId'=>$_SESSION['sessionId'],
				'function'=>'getAllCampaignsForCampaignSetup',
				'campaignSetupId'=>$id
			) ) )
			->getContent( self::$_synndUrl[self::$_synndService].'api/' );
		$arrCampaignsTypes=Core_String::json2php( $_curl->getResponce() );
		$arrReturn=array();
		foreach( $arrCampaignsTypes['results'] as $_types ){
			$_curl
				->setPost( http_build_query( array(
					'sessionId'=>$_SESSION['sessionId'],
					'function'=>'getAllPromotionsForCampaign',
					'campaignId'=>$_types['id'],
					'csv'=>false,
					'gridList'=>true,
					'limit'=>100
				) ) )
				->getContent( self::$_synndUrl[self::$_synndService].'api/' );
			$_return=$_curl->getResponce();
			$arrReport=Core_String::json2php( str_replace( array("\\\"","\\'"), array('``','`'), $_return ) );
			foreach( $arrReport['results'] as $_reports ){
				if( $_reports['status'] != 'Completed'){
					continue;
				}
				$arrReturn[]=array(
					'Submission Url'=>$_reports['submissionUrl'],
					'Promotion Url'=>$_reports['promotionUrl']
				);
			}
		}
		return $this->array2scv($arrReturn);
	}
	
	private static function spinningString( $_strContent='' ) {
		if( $_strContent=='' ) {
			return '';
		}
		$_spinner=new Project_Synnd_Spinnerchief();
		$_strSpinnedContent=$_spinner->setSettings()->getContent( $_strContent );
		Core_Sql::reconnect();
		if( $_strSpinnedContent == $_strContent || $_strSpinnedContent=='' ) {
			$_arrWords=explode( ' ', $_strContent );
			foreach( $_arrWords as &$_strWord ) {
				$_strWord='{'.$_strWord.'|'.$_strWord.'}';
			}
			return implode( ' ', $_arrWords );
		}
		return $_strSpinnedContent;
	}
	
	function array2scv($array, $headerRow = true, $colSep = ",", $rowSep = "\n", $qut = '"'){
		if(!is_array($array) || !is_array($array[0]))
			return false;
		if ($headerRow){
			foreach ($array[0] as $key => $val){
				$key = str_replace($qut, "$qut$qut", $key);
				$output.="$colSep$qut$key$qut";
			}
			$output = substr($output, 1)."\n";
		}
		foreach ($array as $key => $val){
			$tmp = '';
			foreach ($val as $cell_key => $cellVal){
				$cellVal = str_replace($qut, "$qut$qut", $cellVal);
				$tmp.="$colSep$qut$cellVal$qut";
			}
			$output.=substr($tmp, 1).$rowSep;
		}
		return $output;
	}
	
}
?>