<?php
class Project_Traffic extends Core_Data_Storage {
	
	protected static $rate=10;
	
	protected static $_trafficTrackingLink="http://qjmpz.com/services/traffic_exchange.php";
	protected static $_trafficUsersLink="";

	protected static $_flgTracing=false;
	
	protected $_table='traffic_campaigns';
	protected $_fields=array( 'id', 'url', 'flg_end','category_id', 'credits', 'user_id', 'edited', 'added' );
	
	function __construct(){
		self::updateServiceUrl();
	}
	
	private $_withUserId=false;
	private $_withoutUserId=false;
	private $_onlyActive=false;
	private $_onAction=false;
	private $_onlyPromoted=false;
	private $_onlyNotEnd=false;
	private $_withCategoryId=false;
	
	public function withUserId( $_str ){
		$this->_withUserId=$_str;
		return $this;
	}

	public static function updateServiceUrl(){
		$_arr=array_chunk( array_reverse( explode( '.', $_SERVER['HTTP_HOST'] ) ), 2 );
		$_strDomain=implode( '.', array_reverse( $_arr[0] ) );
		$_tail=substr( $_strDomain , strripos( $_strDomain, '.' )+1 );
		if ( $_tail!='local' ) {
			self::$_trafficUsersLink="http://qjmpz.com/te";//Core_Module::getUrl( array( 'name'=>'site1_traffic', 'action'=>'client_trafic_exchange' ) );
		}elseif( $_tail=='local' ){
			self::$_trafficUsersLink="http://qjmpz.local/te";
			self::$_trafficTrackingLink="http://qjmpz.local/services/traffic_exchange.php";
		}
	}

	public function withCategoryId( $_str ){
		$this->_withCategoryId=$_str;
		$this->_cashe['with_category_id']=$_str;
		return $this;
	}

	public function onlyActive(){
		$this->_onlyActive=true;
		return $this;
	}

	public function onAction(){
		$this->_onAction=true;
		return $this;
	}

	public function onlyPromoted(){
		$this->_onlyPromoted=true;
		return $this;
	}

	public function onlyNotEnd(){
		$this->_onlyNotEnd=true;
		return $this;
	}

	public function notLinkUpdate(){
		$this->_notLinkUpdate=true;
		return $this;
	}

	public function withoutUserId( $_str ){
		$this->_withoutUserId=$_str;
		return $this;
	}
	
	public function setEntered( $_mix=array() ) {
		$this->_data=is_object( $_mix )? $_mix:new Core_Data( $_mix );
		return $this;
	}
	
	public function getEntered( &$arrRes ) {
		if ( is_object( $this->_data ) ) {
			$arrRes=$this->_data->getFiltered();
		}
		return $this;
	}
// ALTER TABLE traffic_campaigns ADD COLUMN `flg_end` INT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `id`;
	public function end() {
		self::updateServiceUrl();
		if ( empty( $this->_withIds ) ) {
			$_bool=Core_Data_Errors::getInstance()->setError('Empty users data');
		}else{
			if( !self::$_flgTracing ){
				// cnm
				@file_get_contents( self::$_trafficTrackingLink.'?action=end&'.$this->dataToQuery() );
			}else{
				//tracking
				Core_Sql::setExec( 'UPDATE '.$this->_table.' SET `flg_end`=1 WHERE id IN('.Core_Sql::fixInjection( $this->_withIds ).')' );
			}
			$_bool=true;
		}
		$this->init();
		return $_bool;
	}

	public function del() {
		self::updateServiceUrl();
		if ( empty( $this->_withIds ) ) {
			$_bool=Core_Data_Errors::getInstance()->setError('Empty users data');
		}else{
			if( !self::$_flgTracing ){
				// cnm
				@file_get_contents( self::$_trafficTrackingLink.'?action=del&'.$this->dataToQuery() );
			}else{
				//tracking
				Core_Sql::setExec( 'DELETE FROM '.$this->_table.' WHERE id IN('.Core_Sql::fixInjection( $this->_withIds ).')' );
			}
			$_bool=true;
		}
		$this->init();
		return $_bool;
	}

	public function set() {
		self::updateServiceUrl();
		$this->_data->setElement( 'edited', time() );
		if( !self::$_flgTracing ){
			// cnm
			// get_headers($url, 1)
			$_return=@file_get_contents( self::$_trafficTrackingLink.'?action=set&'.$this->dataToQuery() );
			return (( $_return== 'true' )?true:Core_Data_Errors::getInstance()->setError('No connection with tracker'));
		}else{
			//tracking
			if( empty( $this->_data->filtered['id'] ) ) {
				$this->_data->setElement( 'added', time() );
			}
			$this->_data->setElement( 'id', Core_Sql::setInsertUpdate( $this->_table, $this->_data->setMask( $this->_fields )->getValid() ) );
		}
		return true;
	}

	protected function init() {
		parent::init();
		$this->_withUserId=false;
		$this->_withoutUserId=false;
		$this->_withCategoryId=false;
		$this->_onlyActive=false;
		$this->_onAction=false;
		$this->_onlyPromoted=false;
		$this->_notLinkUpdate=false;
		$this->_onlyNotEnd=false;
	}

	public function dataToQuery(){
		return http_build_query( array(
			'data'=>$this->_data->filtered,
			'toSelect'=>$this->_toSelect,
			'onlyIds'=>$this->_onlyIds,
			'onlyCell'=>$this->_onlyCell,
			'onlyCount'=>$this->_onlyCount,
			'onlyOne'=>$this->_onlyOne,
			'keyRecordForm'=>$this->_keyRecordForm,
			'withCategoryId'=>$this->_withCategoryId,
			'withIds'=>$this->_withIds,
			'withPaging'=>$this->_withPaging,
			'withOrder'=>$this->_withOrder,
			'withUserId'=>$this->_withUserId,
			'withoutUserId'=>$this->_withoutUserId,
			'onlyActive'=>$this->_onlyActive,
			'onAction'=>$this->_onAction,
			'onlyPromoted'=>$this->_onlyPromoted,
			'onlyNotEnd'=>$this->_onlyNotEnd,
		) );
	}

	public function getList( &$mixRes ) {
		self::updateServiceUrl();
		if( !self::$_flgTracing ){
			// cnm
			$return=unserialize( file_get_contents( self::$_trafficTrackingLink.'?action=getList&'.$this->dataToQuery() ) );
			if( $return=== false || !isset( $return['data'] ) ){
				$mixRes=array();
			}elseif( isset( $return['data'] ) ){
				$mixRes=$return['data'];
			}
			if( isset( $return['paging'] ) ){
				$this->_paging=$return['paging'];
			}
			if( isset( $return['cashe'] ) ){
				$this->_cashe=$return['cashe'];
			}
			$this->_isNotEmpty=!empty( $mixRes );
			$this->init();
		}else{
			//tracking
			$_refererUpdateLinks=false;
			if( $this->_withoutUserId != false && $this->_notLinkUpdate == false ){
				$_refererUpdateLinks=$this->_withoutUserId;
			}
			$_flgOnlyOne=$this->_onlyOne;
			parent::getList( $mixRes );
			if( $this->_isNotEmpty && $_refererUpdateLinks !== false ){
				if( $_flgOnlyOne ){
					$mixRes['screenshot_url']=$mixRes['url'];
					$mixRes['url']=$this->generateUrl( $mixRes['id'], $_refererUpdateLinks );
				}else{
					foreach( $mixRes as &$data ){
						$data['screenshot_url']=$data['url'];
						$data['url']=$this->generateUrl( $data['id'], $_refererUpdateLinks );
					}
				}
			}
		}
		return $this;
	}

	public function regenerateUrl( $code ){
		$string=base64_decode( urldecode( $code ) );
		$position=substr( strstr($string, 'q', true), 1 );
		$base=substr( strstr($string, 'q'), 1 );
		return json_decode( base64_decode( substr($base, 0, -$position-1).substr($base, -$position) ), true );
	}

	private function generateUrl( $id, $referer ){
		self::updateServiceUrl();
		if( !isset( $id ) || !isset( $referer ) ){
			return false;
		}
		$coded=array( 
			'v'=>$id,
			'u'=>$referer
		);
		$_string=base64_encode( json_encode( $coded ) );
		$_position=(int)( strlen( $_string )/3 );
		$_string=urlencode( base64_encode( 'j'.$_position.'q'.substr($_string, 0, -$_position).'s'.substr($_string, -$_position) ) );
		return self::$_trafficUsersLink.'?c='.$_string;
	}
	
	public $ip='127.0.0.0';
	public $linkReferer=false;
	
	public function checkIP(){
		if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
			$this->ip=$_SERVER['HTTP_CLIENT_IP'];
		} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$this->ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$this->ip=$_SERVER['REMOTE_ADDR'];
		}
		@preg_match('#app.ifunnels.com#im', $_SERVER['HTTP_REFERER'], $m0);
		@preg_match('#members.creativenichemanager.info#im', $_SERVER['HTTP_REFERER'], $m1);
		@preg_match('#qjmpz.com#im', $_SERVER['HTTP_REFERER'], $m2);
		if( @count( $m1 ) > 0 || @count( $m0 ) > 0 || @count( $m2 ) > 0 || isset( $_GET['referer'] ) ){
			$this->linkReferer=true;
		}
	}

	public function moveCredits( $_userId=false ){
		if( empty( $_userId ) ){
			return false;
		}
		$_subscribers=new Project_Traffic_Subscribers();
		$_subscribers->setEntered(array(
			'campaign_id'=>$_GET['v'],
			'ip'=>$this->ip,
			'referer'=>$_GET['u'],
		))->set();
		$_credits=new Project_Traffic_Credits();
		$_credits->onlyOne()->withIds( $_GET['u'] )->getList( $creditsTo );
		$_credits->onlyOne()->withIds( $_userId )->getList( $creditsFrom );
		$_credits->setEntered(array(
			'id'=>$_userId,
			'credits'=>$creditsFrom['credits']-1
		))->set();
		$_credits->setEntered(array(
			'id'=>$_GET['u'],
			'credits'=>$creditsTo['credits']+1
		))->set();
		return true;
	}

	public function showAds( $url, $_userId=false ){
		$_flgShowPage=$_flgNotShowRedirect=true;
		if( !empty( $_userId )
			&& isset( $_GET['v'] )
			&& isset( $_GET['u'] )
		){
			if( $this->ip == '23.21.236.50' || $this->ip == '127.0.0.1' || $this->linkReferer ){
				$_flgNotShowRedirect=false;
				if( isset( $_GET['update_credits'] ) ){
					$this->moveCredits( $_userId );
					$_flgShowPage=false;
				}
			}else{
				$this->moveCredits( $_userId );
			}
		}
		if( $_flgShowPage ){
			Core_Files::getContent($_adsScript,'./services/traffic/remote_ads.txt' );
			echo( Core_View::factory( Core_View::$type['one'] )
				->setTemplate( 'source'.DIRECTORY_SEPARATOR.'site1_traffic'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'show_site.tpl' )
				->setHash( array( 'url' => $url, 'flg_show_redirect'=>( !$_flgNotShowRedirect ), 'c'=>$_GET['c'], 'ads_script'=>$_adsScript ) )
				->parse()
				->getResult() );
		}else{
			echo 'checked';
		}
		exit;
	}
	
	public function getUserCredits(){
		if ( empty( $this->_withUserId ) ) {
			return 0;
		}
		self::updateServiceUrl();
		return file_get_contents( self::$_trafficTrackingLink.'?credit=get&id='.htmlspecialchars( $this->_withUserId ) );
	}

	public function sendUserCredits( $_amount=0 ){
		if ( empty( $this->_withUserId ) || $_amount==0 ) {
			return 0;
		}
		self::updateServiceUrl();
		return file_get_contents( self::$_trafficTrackingLink.'?credit=add&id='.htmlspecialchars( $this->_withUserId ).'&amount='.$_amount );
	}

	public function getAds(){
		self::updateServiceUrl();
		return file_get_contents( self::$_trafficTrackingLink.'?getads=default' );
	}

	public function setAds( $_ad='' ){
		self::updateServiceUrl();
		return file_get_contents( self::$_trafficTrackingLink.'?setads='.htmlspecialchars( base64_encode( $_ad ) ) );
	}

	public function getDefaultPage(){
		self::updateServiceUrl();
		return file_get_contents( self::$_trafficTrackingLink.'?geturl=default' );
	}

	public function setDefaultPage( $_page='' ){
		self::updateServiceUrl();
		return file_get_contents( self::$_trafficTrackingLink.'?seturl='.htmlspecialchars( $_page ) );
	}

	public function stopCampaign( $_campaignId ){
		self::updateServiceUrl();
		if( !isset( $_campaignId ) || empty( $this->_withUserId ) ){
			return false;
		}
		return file_get_contents( self::$_trafficTrackingLink.'?locking=action&user_id='.htmlspecialchars( $this->_withUserId ).'&campaign_id='.htmlspecialchars( $_campaignId ) );
	}

	public function endCampaign( $_campaignId ){
		self::updateServiceUrl();
		return file_get_contents( self::$_trafficTrackingLink.'?action=end&campaign_id='.htmlspecialchars( $_campaignId ) );
	}

	public static function addCredits( $_cost=0, $_amount=0, $_userId=false ){
		self::updateServiceUrl();
		if( $_userId===false && isset( Core_Users::$info['id'] ) ){
			$_userId=Core_Users::$info['id'];
		}
		$_purse=new Core_Payment_Purse();
		$_purse
			->setUserId( $_userId )
			->setAmount( $_cost );
		if( $_cost <= 0 ){
			$_purse->setMessage( 'Get free '.$_amount.' Traffic Credit(s)');
		}else{
			$_purse->setMessage( 'Buy '.$_amount.' Traffic Credit(s)');
		}
		$_purse	
			->setType( Core_Payment_Purse::TYPE_INTERNAL )
			->expenditure();
		$_traffic=new Project_Traffic();
		$_traffic->withUserId( $_userId )->sendUserCredits( $_amount );
	}

	public function addUserCredits( $_amount=0 ){
		if ( empty( $this->_withUserId ) ) {
			return Core_Data_Errors::getInstance()->setError('No user data');
		}
		$_oldUserId=Core_Users::$info['id'];
		if( $this->_withUserId != $_oldUserId ){
			Core_Users::getInstance()->setById( $this->_withUserId );
		}
		if( Core_Payment_Purse::getAmount()>( $_amount ) ){
			self::addCredits( $_amount, $_amount*self::$rate, $this->_withUserId );// Project_Traffic
		}else{
			return Core_Data_Errors::getInstance()->setError('No credits to buy '.$_amount*self::$rate.' traffic cradits' );
		}
		if( $this->_withUserId != $_oldUserId ){
			Core_Users::getInstance()->setById( $_oldUserId );
		}
		return true;
	}

	protected function assemblyQuery() {
		if( self::$_flgTracing ){
			parent::assemblyQuery();
			$this->_crawler->set_select( '( SELECT count(*) FROM traffic_subscribers WHERE d.id=campaign_id ) as clicks' );
			if ( !empty( $this->_withUserId ) ) {
				$this->_crawler->set_where( 'd.user_id IN ('.Core_Sql::fixInjection( $this->_withUserId ).')' );
			}
			if ( !empty( $this->_withoutUserId ) ) {
				$this->_crawler->set_where( 'd.user_id NOT IN ('.Core_Sql::fixInjection( $this->_withoutUserId ).')' );
			}
			if ( !empty( $this->_withCategoryId ) ) {
				$this->_crawler->set_where( 'd.category_id IN ('.Core_Sql::fixInjection( $this->_withCategoryId ).')' );
			}
			if ( !empty( $this->_onAction ) && !empty( $this->_withoutUserId ) ) {
				$this->_crawler->set_select( '( SELECT count(*) FROM traffic_locking l WHERE d.id=l.campaign_id AND l.user_id IN ('.Core_Sql::fixInjection( $this->_withoutUserId ).') ) as flg_locking' );
			}
			if( !empty( $this->_onlyPromoted ) && !empty( $this->_withoutUserId ) ) {
				$this->_crawler->set_where( '( SELECT count(*) FROM traffic_subscribers s WHERE d.id=s.campaign_id AND s.referer IN ('.Core_Sql::fixInjection( $this->_withoutUserId ).') )>0' );
			}
			if ( !empty( $this->_onlyNotEnd ) ) {
				$this->_crawler->set_where( 'd.flg_end=0' );
			}
			if ( !empty( $this->_onlyActive ) ) {
				if( !empty( $this->_withoutUserId ) ){
					$this->_crawler->set_select( '( SELECT count(*) FROM traffic_locking l WHERE d.id=l.campaign_id AND l.user_id IN ('.Core_Sql::fixInjection( $this->_withoutUserId ).') ) as flg_locking' );
					$this->_crawler->set_select( '( SELECT count(*) FROM traffic_subscribers s WHERE d.id=s.campaign_id AND s.referer IN ('.Core_Sql::fixInjection( $this->_withoutUserId ).') ) as user_clicks' );
				}
				$this->_crawler->set_where( 'd.credits > ( SELECT count(*) FROM traffic_subscribers s2 WHERE d.id=s2.campaign_id )' );
				$this->_crawler->set_where( '( SELECT credits FROM traffic_credits WHERE d.user_id=id ) >= 1' );
			}
		}
	}
}
?>