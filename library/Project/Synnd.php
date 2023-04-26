<?php


/**
 * Работа с таблицами synnd
 */

class Project_Synnd extends Core_Data_Storage{

	protected $_table='synnd_campaigns';
	protected $_fields=array('id','user_id','settings','flg_type','flg_pause','added','edited');
	protected $_tableSettings='synnd_default';

	public static $promotionTypes=array(
		1=>array( 'title'=>'Facebook','name'=>'Facebook Likes','flg_active'=>false,'amount'=>0.25 ),
		2=>array( 'title'=>'Social News','name'=>'Social News','flg_active'=>false,'flg_siteId'=>true,'amount'=>0.1 ),
		3=>array( 'title'=>'Bookmarking','name'=>'Social Bookmarks','flg_active'=>true,'amount'=>0.1 ),
		4=>array( 'title'=>'Twitter','name'=>'Twitter Re-tweets','flg_active'=>false,'amount'=>0.1 ),
		5=>array( 'title'=>'Google','name'=>'Google','flg_active'=>false,'amount'=>0.33 ),
	);
	
	public static $promotionPeriod=array(
		0=>array( 'title'=>'one time', 'amount'=>0 ),
		1=>array( 'title'=>'once a week', 'amount'=>604800 ),
		2=>array( 'title'=>'once a month', 'amount'=>2592000 ),
	);

	public static function getCountBookmarks(){
		return Core_Sql::getCell('select count(*) from synnd_reports where flg_type=3 and campaign_id IN (select id from synnd_campaigns where user_id='. Core_Users::$info['id'] .')');
	}
	
	public function validate( $settings ){
		if( !preg_match( '!^((http|https)://)?(([a-z0-9\-]*\.)+[a-z0-9\-]{2,})(/([^#\?]+)?(\?[^#]*)?(#.*)?)?$!i', $settings['url'] ) ) {
			return Core_Data_Errors::getInstance()->setError( 'Domain name is not correct' );
		}
		if( empty( $settings['tags'] ) 
			|| empty( $settings['description'] ) 
			|| empty( $settings['url'] ) 
			|| empty( $settings['title'] ) ) {
			return Core_Data_Errors::getInstance()->setError( 'Required data is empty' );
		}
		if( str_word_count( $settings['description'] )<10 ) {
			return Core_Data_Errors::getInstance()->setError( 'A description must have at least 10 words!' );
		}
		$tags=explode( ',' , $settings['tags'] );
		if( count($tags)>5 ) {
			return Core_Data_Errors::getInstance()->setError( 'A maximum of 5 tags can be added to a campaign!' );
		}
		foreach( $tags as $tag ) {
			if( str_word_count( $tag ) > 3 ) {
				return Core_Data_Errors::getInstance()->setError( 'Tags must not contain more than three words!' );
			}
			if( strlen( trim( $tag ) ) < 3 ) {
				return Core_Data_Errors::getInstance()->setError( 'A tag must have at least 3 characters!' );
			}
		}
		if( str_word_count( $settings['title'] ) < 4 ) {
			return Core_Data_Errors::getInstance()->setError( 'A title must have at least 4 words!' );
		}
		return true;
	}
	
	protected function beforeSet() {
		$this->_data->setFilter( array( 'clear' ) );
		if( !$this->validate( $this->_data->filtered['settings'] ) ){
			return false;
		}
		$_amount=0;
		if( empty( $this->_data->filtered['settings']['promoteTypes'] ) ) {
			return Core_Data_Errors::getInstance()->setError( 'Please choose your promotion type' );
		}
		foreach( $this->_data->filtered['settings']['promoteCount'] as $key=>$_count ) {
			$_amount += $_count * Project_Synnd::$promotionTypes[$key]['amount'];
		}
		$_amount=(int)ceil( $_amount );
		if( Core_Payment_Purse::getAmount()<$_amount ) {
			return Core_Data_Errors::getInstance()->setError('<a href="'.Core_Module_Router::getCurrentUrl( array( "name"=>"site1_accounts", "action"=>"payment") ).'" target="_blank">Purchase extra credits</a>');
		}
		$this->_data->setElements(array(
			'settings'=>serialize( $this->_data->filtered['settings'] ),
		));
		return true;
	}

	protected function afterSet() {
		$_reports=new Project_Synnd_Reports();
		$this->_data->filtered['settings']=unserialize( $this->_data->filtered['settings'] );
		foreach( $this->_data->filtered['settings']['promoteTypes'] as $key=>$_value ) {
			$_reports->withCampaignId( $this->_data->filtered['id'] )->setEntered( array( 'flg_type'=>$_value, 'promote_count'=>$this->_data->filtered['settings']['promoteCount'][$key] ) )->set();
		}
		$_reports->withCampaignId( $this->_data->filtered['id'] )->delCorrupted();
		return true;
	}

	public function setType( $_flgType ) {
		if( array_key_exists( $_flgType, self::$promotionPeriod ) && empty( $this->_withIds ) ) {
			return Core_Data_Errors::getInstance()->setError( 'Data is not correct' );
		}
		Core_Sql::setExec('UPDATE '.$this->_table.' SET flg_type='.Core_Sql::fixInjection( $_flgType ).' WHERE id='.Core_Sql::fixInjection( $this->_withIds ) );
		$this->init();
		return true;
	}

	public function setPause( $_flgPause=0 ) {
		if( empty( $this->_withIds ) ) {
			return Core_Data_Errors::getInstance()->setError('Data is not correct');
		}
		$_reports=new Project_Synnd_Reports();
		$_campaignId=$this->_withIds;
		if( $_flgPause==1 ) {
			$_reports->withCampaignId( $_campaignId )->del();
		}else{
			$this->onlyOne()->getList( $_arrCampaign );
			foreach( $_arrCampaign['settings']['promoteTypes'] as $key=>$_value ) {
			$_reports->withCampaignId( $_arrCampaign['id'] )->setEntered( array( 'flg_type'=>$_value, 'promote_count'=>$_arrCampaign['settings']['promoteCount'][$key] ) )->set();
			}
		}
		Core_Sql::setExec('UPDATE '.$this->_table.' SET flg_pause='.Core_Sql::fixInjection( $_flgPause ).' WHERE id='.Core_Sql::fixInjection( $_campaignId ) );
		$this->init();
	}

	protected $_onlyLast=false;

	public function onlyLast() {
		$this->_onlyLast=true;
		return $this;
	}

	protected function assemblyQuery() {
		parent::assemblyQuery();
		if( $this->_onlyLast ) {
			$this->_crawler->set_order_sort( 'd.added--dn' );
			$this->_onlyOne=true;
		}
	}

	protected function init() {
		parent::init();
		$this->_onlyLast=false;
	}

	public function getList( &$mixRes ) {
		parent::getList( $mixRes );
		if( empty($mixRes) ){
			return $this;
		}
		if( array_key_exists( 0, $mixRes ) ) {
			foreach( $mixRes as &$_data ) {
				$_data['settings']=unserialize($_data['settings']);
			}
		}else{
			$mixRes['settings']=unserialize($mixRes['settings']);
		}
		return $this;
	}

	public function setSettings( $_data=array() ) {
		if( empty( $this->_withIds ) || empty( $_data ) ) {
			return Core_Data_Errors::getInstance()->setError('Data is not correct');
		}
		Core_Sql::setExec( "UPDATE ".$this->_table." SET settings=".Core_Sql::fixInjection( serialize( $_data  ) )." WHERE id=".Core_Sql::fixInjection( $this->_withIds ) );
		$this->init();
	}
	
	public function setDefaultSettings( $_userId, $_data=array() ) {
		if( empty( $_userId ) || $_data===array() ) {
			return Core_Data_Errors::getInstance()->setError('Data is not correct');
		}
		$this->getDefaultSettings( $_userId, $arrList );
		$arrList['user_id']=$_userId;
		$arrList['settings']=Zend_Registry::get( 'CachedCoreString' )->php2json( $_data );
		return Core_Sql::setUpdateInsert( $this->_tableSettings, $arrList );
	}

	public function getDefaultSettings( $_userId, &$arrList ) {
		if( empty( $_userId ) ) {
			return Core_Data_Errors::getInstance()->setError('Data is not correct');
		}
		$arrList=Core_Sql::getRecord( 'SELECT * FROM '.$this->_tableSettings.' WHERE user_id='.$_userId );
		$arrList['settings']=Zend_Registry::get( 'CachedCoreString' )->json2php( $arrList['settings'] );
		return $this;
	}
	
	public function getContent( $url, &$arrReturn=array() ){
		if( Project_Wizard_Adapter_ZonterestPro::isZonterest( $url, $_arrRes ) ) {
			$arrReturn['title']=$_arrRes['title'];
			$arrReturn['description']=$_arrRes['description'];
			$arrReturn['tags']=$_arrRes['tags'];
		}else{
			set_time_limit(20);
			$_curl=Core_Curl::getInstance();
			$_curl->getContent( $url );
			$_data = $_curl->getResponce();
			preg_match("/title>(.*?)<\/title/i",$_data,$_matches);
			$arrReturn['title']=htmlspecialchars_decode( self::getTitle( $_matches[1] ) );
			preg_match('/<meta.*?name=["|\']keywords["|\'].*?content=["|\']?([^>"]*)["|\']?/i',$_data,$_matches);
			$arrReturn['tags']=htmlspecialchars_decode( $_matches[1] );
			self::updateTags( $arrReturn['tags'] );
			if( empty( $arrReturn['tags'] ) ){
				$arrReturn['tags']=self::getTags( $arrReturn['title'] );
			}
			preg_match('/<meta.*?name=["|\'](description)["|\'].*?content=["|\']?([^>"]*)["|\']?/i', $_data, $_matches);
			$arrReturn['description']=self::getDescription( $arrReturn['title'], $_matches[2] );
		}
		$arrReturn['url']=$url;
		if( !$this->validate( $arrReturn ) ){
			$arrReturn['errors']=Core_Data_Errors::getInstance()->getErrors();
		}
	}
	
	public static function getTitle( $_strKeyword ){
		if(str_word_count($_strKeyword)==1){
			$_strKeyword ='News, Content and Resources on '.$_strKeyword;
		} elseif( str_word_count($_strKeyword)>1&&str_word_count($_strKeyword)<4 ){
			$_arr=array(
				'#keyword# Tips and Resources',
				'Get Latest News on #keyword#'
			);
			$_strKeyword=str_replace('#keyword#',$_strKeyword,$_arr[array_rand($_arr)]);
		}
		return ucwords($_strKeyword);
	}

	public static function updateTags( &$_str ){
		$_arrTags=explode(',', $_str);
		foreach( $_arrTags as &$_tag ){
			$_tag=explode(' ', $_tag);
			foreach( $_tag as &$_world ){
				if( strlen( $_world ) < 3 ){
					$_world='';
				}
			}
			$_tag=trim( implode(' ', array_diff($_tag, array(null))) );
			if( str_word_count( $_tag ) > 3 ){
				$_tag=implode(' ',array_slice(array_filter( explode(' ', $_tag) ),0,3));
			}
		}
		$_str=implode(',',array_slice($_arrTags,0,5));
	}

	public function del( $_mix=0 ) {
		if ( empty( $_mix ) ) {
			return $this->setError('id project can\'t by empty');
		}
		$_mix=is_array( $_mix ) ? $_mix:array( $_mix );
		Core_Sql::setExec('
			DELETE a, b
			FROM '.$this->_table.' a
			LEFT JOIN synnd_reports b ON b.campaign_id=a.id
			WHERE a.id IN('.Core_Sql::fixInjection( $_mix ).')
		');
		return true;
	}
	
	public static function getSocialNewsSites( &$sites ){
		$_synndApi=new Project_Synnd_Api();
		$sites=$_synndApi->getAllSitesForType( 'Social News' );
	}
	
	public static function getTags( $_strKeyword ){
		$_strKeyword=strtolower($_strKeyword);
		$_strKeyword=trim(preg_replace('@\s[a-z|0-9]{0,2}\s@si',' ',' '.$_strKeyword.' '));
		$_arrWords=explode(' ', $_strKeyword);
		if(count($_arrWords)>3){
			$_arrWords=array_slice($_arrWords,0,3);
			$_strKeyword=implode(' ',$_arrWords);
		}
		if( count($_arrWords)>1 ){
			$_str=join(', ',$_arrWords).', ';
		}
		$_str.=$_strKeyword.', ';
		if(count($_arrWords)>2){
			$_arrWords=array_slice($_arrWords,0,2);
			$_strKeyword=implode(' ',$_arrWords);
		}
		$_str.=$_strKeyword.' store';
		return $_str;
	}

	public static function getDescription( $_strTitle='', $_strDescription='' ){
		if( empty( $_strDescription ) && !empty( $_strTitle ) ){
			return "We've found this new website with a lot of great articles and tips about ".$_strTitle;
		}elseif( !empty( $_strDescription ) && str_word_count( $_strDescription )<10 ){
			$_oneString=$_strDescription;
			for( $i=0; str_word_count( $_strDescription )<10 && $i<10; $i++ ){
				$_strDescription.=" ".$_oneString;
			}
			return ucfirst( strtolower( htmlspecialchars_decode( $_strDescription ) ) );
		}elseif( str_word_count( $_strDescription )>10 ){
			return $_strDescription;
		}
		return '';
	}

	public function get_file() {
		if( empty( $this->_withIds ) ) {
			return Core_Data_Errors::getInstance()->setError('Data is not correct');
		}
		$this->onlyOne()->getList( $_arrCampaign );
		$this->init();
		$_synndApi=new Project_Synnd_Api();
		$_str=$_synndApi->getAllPromotionsForCampaign( $_arrCampaign['settings']['synndCampaignId'] );
		if ( empty( $_str ) ) {
			return Core_Data_Errors::getInstance()->setError('No promotion data');
		}
		ob_end_clean();
		set_time_limit(0);
		header( 'HTTP/1.1 200 OK' );
		header( 'Content-Length: '.strval(strlen($_str)) );
		header( 'Content-Type:  text/plain; charset="utf8"' );
		header( 'Content-Disposition: attachment; filename="promotions.csv"' );
		header( '' );
		print  $_str;
		exit;
	}
}
?>