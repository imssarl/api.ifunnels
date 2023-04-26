<?php


/**
 * Clickbank контент функционал
 */
class Project_Content_Adapter_Clickbank extends Core_Storage implements Project_Content_Interface {
	
	public $fields=array( 'id', 'category_id', 'flg_network', 'flg_language', 'vendor_name', 'vendor_id', 'title', 'long_description', 'short_description','video_url','url','smallthumb','largethumb',
	'file0','file1','file2','file3','file4','file5','file6','file7','file8','file9','type0','type1','type2','type3','type4','type5','type6','type7','type8','type9','added','edited' );
	public $table='content_clickbank';
	
	public static $templates=array (
		0=>'Long description',
		1=>'Short description',
		2=>'Clean',
	);
	private $_withJson=false;
	private $_withRewrite=false;
	
	/**
	 * Banners type
	 * @var array
	 */
	public static $bannerType=array(
			array('width'=>'300','height'=>'250','title'=>'300x250 IMU - (Medium Rectangle)'),
			array('width'=>'250','height'=>'250','title'=>'250x250 IMU - (Square Pop-Up)'),
			array('width'=>'240','height'=>'400','title'=>'240x400 IMU - (Vertical Rectangle)'),
			array('width'=>'336','height'=>'280','title'=>'336x280 IMU - (Large Rectangle)'),
			array('width'=>'180','height'=>'150','title'=>'180x150 IMU - (Rectangle) '),
			array('width'=>'300','height'=>'100','title'=>'300x100 IMU - (3:1 Rectangle)'),
			array('width'=>'720','height'=>'300','title'=>'720x300 IMU – (Pop-Under)'),
			array('width'=>'468','height'=>'60','title'=>'468x60 IMU - (Full Banner)'),
			array('width'=>'234','height'=>'60','title'=>'234x60 IMU - (Half Banner)'),
			array('width'=>'88', 'height'=>'31','title'=>'88x31 IMU - (Micro Bar)'),
			array('width'=>'120','height'=>'90','title'=>'120x90 IMU - (Button 1)'),
			array('width'=>'120','height'=>'60','title'=>'120x60 IMU - (Button 2)'),
			array('width'=>'120','height'=>'240','title'=>'120x240 IMU - (Vertical Banner)'),
			array('width'=>'125','height'=>'125','title'=>'125x125 IMU - (Square Button)'),
			array('width'=>'728','height'=>'90','title'=>'728x90 IMU - (Leaderboard)'),
			array('width'=>'160','height'=>'600','title'=>'160x600 IMU - (Wide Skyscraper)'),
			array('width'=>'120','height'=>'600','title'=>'120x600 IMU - (Skyscraper)'),
			array('width'=>'300','height'=>'600','title'=>'300x600 IMU - (Half Page Ad)')
	);
	/**
	 * Banners from user
	 * @var bool
	 */
	private $_files=false;
	
	/**
	 * Content settings
	 * @var array
	 */
	protected $_settings=array();
	protected $_counter=0;
	protected $_limit=false;
	protected $_withTags=false; // поиск по тегам
	protected $_withLanguage=false; // только один язык
	private $_withVendorId=false;
	private $_withThumb=false;
	private $_post=array();
	private $_result=false;
	private $_filesPath='';
	public static  $defaultAFid='ethiccash'; // Affiliate ID default;

	public function __construct() {
		$this->_filesPath=Zend_Registry::get('config')->path->relative->user_files.'clickbank/';
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

	public function getResult( &$arrRes ){
		return $this->_result;
	}

	public function getAdditional( &$arrRes ){
		return $this;
	}

	/**
	 * Create and save item
	 *
	 * @return bool
	 */
	public function set(){
		if ( !$this->_data->setFilter( array( 'trim' ) )->setChecker( array(
			'short_description'=>empty( $this->_data->filtered['short_description'] ),
			'long_description'=>empty( $this->_data->filtered['long_description'] ),
			'vendor_id'=>empty( $this->_data->filtered['vendor_id'] ),
			'url'=>empty( $this->_data->filtered['url'] ),
			'flg_network'=>empty( $this->_data->filtered['flg_network'] ),
			'category_id'=>empty( $this->_data->filtered['category_id'] ),
			'title'=>empty( $this->_data->filtered['title'] ),
		) )->check() ) {
			return $this->setError('Fill all required fields.');
		}
		if(!$this->uploadFiles()){
			return $this->setError('Can\'t save files');
		}
		$this->_data->setFilter( array( 'trim' ) );
		if ( empty($this->_data->filtered['id']) ){
			$this->_data->setElement( 'added', time() );
			// сохраняем тэги только если нет id
			$tags=new Core_Tags('clickbank');
		}
		$this->_data->setElement( 'edited', time() );
		$this->_data->setElement( 'id', Core_Sql::setInsertUpdate( $this->table, $this->_data->setMask( $this->fields )->getValid() ) );
		if( is_object($tags) && !empty($this->_data->filtered['tags']) ){
			if( !$tags->setItem( $this->_data->filtered['id'] )->setTags( $this->_data->filtered['tags'] )->set() ){
				return $this->setError('can\'t add tags');
			}
		}
		return true;
	}

	public function getOwnerId(){}

	/**
	 * Delete item
	 *
	 * @param int $intId
	 * @return abool
	 */
	public function del( $_arr=array() ){
		if (empty($_arr)){
			return false;
		}
		$this->_link=false;
		$this->withIds($_arr)->onlyOne()->getList( $arrItem );
		if ( !parent::del($_arr) ){
			return false;
		}
		for ($i=0; $i<10; $i++){
			if ( is_file( $this->_filesPath.$arrItem['file'.$i] ) ){
				unlink( $this->_filesPath.$arrItem['file'.$i] );
			}
		}
		if ( is_file($this->_filesPath.$arrItem['smallthumb']) ){
			unlink($this->_filesPath.$arrItem['smallthumb']);
		}
		if ( is_file($this->_filesPath.$arrItem['largethumb']) ){
			unlink($this->_filesPath.$arrItem['largethumb']);
		}
		return true;
	}
	
	/**
	 * Upload and resize banners and thumb
	 *
	 * @return bool
	 */
	private function uploadFiles(){
		foreach( $this->_data->filtered['banners'] as $key=>$index_banner ){
			if( !empty($this->_files['banners']['name'][$key])&&!in_array(Core_Files::getExtension($this->_files['banners']['name'][$key]),array('jpg','jpeg','gif'))){
				return $this->setError('Image format not supported: '.$this->_files['banners']['name'][$key]);
			}
		}
		if( !empty($this->_files['smallthumb']['name'])&&!in_array(Core_Files::getExtension($this->_files['smallthumb']['name']),array('jpg','jpeg','gif'))){
			return $this->setError('Image format not supported: '.$this->_files['smallthumb']['name']);
		}
		if( !empty($this->_files['largethumb']['name'])&&!in_array(Core_Files::getExtension($this->_files['largethumb']['name']),array('jpg','jpeg','gif'))){
			return $this->setError('Image format not supported: '.$this->_files['largethumb']['name']);
		}
		$_image=new Core_Files_Image_Thumbnail();
		$_strDir=Zend_Registry::get( 'config' )->path->absolute->user_files.'clickbank/';
		if (!empty($this->_data->filtered['smallthumb_delete'])){
			unlink($_strDir.$this->_data->filtered['smallthumb']);
			$this->_data->setElements(array('smallthumb'=>''));
		}
		if (!empty($this->_data->filtered['largethumb_delete'])){
			unlink($_strDir.$this->_data->filtered['largethumb']);
			$this->_data->setElements(array('largethumb'=>''));
		}
		if ( !empty( $this->_files['smallthumb']['tmp_name'] ) ){
			if (is_file($_strDir.$this->_data->filtered['smallthumb'])){
				unlink($_strDir.$this->_data->filtered['smallthumb']);
			}
			$filename=uniqid('smallthumb_').'.'.Core_Files::getExtension( $this->_files['smallthumb']['name'] );
			if ( $_image->setSrc( $_strDir.$filename )->setSource( $this->_files['smallthumb']['tmp_name'] )->setDimension(100,100)->resize() ){
				$this->_data->setElement('smallthumb',$filename);
			}
		}
		if ( !empty( $this->_files['largethumb']['tmp_name'] ) ){
			if (is_file($_strDir.$this->_data->filtered['smallthumb'])){
				unlink($_strDir.$this->_data->filtered['largethumb']);
			}
			$filename=uniqid('largethumb_').'.'.Core_Files::getExtension( $this->_files['largethumb']['name'] );
			if ( $_image->setSrc( $_strDir.$filename )->setSource( $this->_files['largethumb']['tmp_name'] )->setDimension(350,350)->resize() ){
				$this->_data->setElement('largethumb',$filename);
			}
		}
		foreach( $this->_data->filtered['banners'] as $key=>$index_banner ){
			if (!empty($this->_data->filtered['banner_delete'][$key])){
				unlink($_strDir.$this->_data->filtered['banner_file'][$key]);
				$this->_data->setElements(array(
					'type'.$key=>'',
					'file'.$key=>''
				));
				continue;
			}			
			if (empty($this->_files['banners']['tmp_name'][$key])){
				continue;
			}
			if( is_file($_strDir.$this->_data->filtered['banner_file'][$key])  ){
				unlink($_strDir.$this->_data->filtered['banner_file'][$key]);
			}
			$filename=uniqid( 'banner_'. $key .'_' ).
			'_'.self::$bannerType[$index_banner]['width'].'x'.self::$bannerType[$index_banner]['height'].'.'.Core_Files::getExtension($this->_files['banners']['name'][$key]);
			$_image->setSrc( $_strDir.$filename )->setSource( $this->_files['banners']['tmp_name'][$key] )->setDimension(self::$bannerType[$index_banner]['width'],self::$bannerType[$index_banner]['height'] )->resize();
			$this->_data->setElement('type'.$key,$index_banner);
			$this->_data->setElement('file'.$key,$filename);
		}
		return true;
	}

	protected $_withCategories=false;
	protected $_forBeckend=false;

	public function withCategories( $_arr=array() ) {
		$this->_withCategories=$_arr;
		return $this;
	}

	public function forBeckend() {
		$this->_forBeckend=true;
		return $this;
	}

	public function getCountItems( &$arrCategries ){
		$_withThumb=$this->_withThumb;
		$_forBeckend=$this->_forBeckend;
		foreach( $arrCategries as &$_item ){
			if(is_array($_item['node'])){
				foreach( $_item['node'] as &$_child ){
					$this->setFilter(array('category_id'=>$_child['id'],'withthumb'=>$_withThumb))->onlyCount()->getList( $_child['count'] );
					if( $_forBeckend ){
						$this->setFilter(array('category_id'=>$_child['id']))->onlyCount()->getList( $_child['count_all'] );
					}
				}
			}
			$this->setFilter(array('category_pid'=>$_item['id'],'withthumb'=>$_withThumb))->onlyCount()->getList( $_item['count'] );
			if( $_forBeckend ){
				$this->setFilter(array('category_pid'=>$_item['id'] ))->onlyCount()->getList( $_item['count_all'] );
			}
		}
	}

	public function setFilter( $_arrFilter=array() ){
		$this->_settings=$_arrFilter;
		if(empty($_arrFilter['category_id'])&&!empty($_arrFilter['category_pid'])){
			$category=new Core_Category( 'Clickbank' );
			$category->toSelect()->getLevel( $_arrCaterories, $_arrFilter['category_pid'] );
			$_arrFilter['category_id']=array_keys( $_arrCaterories );
		}
		if( !empty($_arrFilter['withthumb']) ){
			$this->withThumb($_arrFilter['withthumb']);
		}
		if( !empty($_arrFilter['with_vendor_id']) ){
			$this->withVendorId($_arrFilter['with_vendor_id']);
		}
		$this
			->withLanguage( $_arrFilter['flg_language'])
			->withTags( $_arrFilter['tags'] )
			->withCategories($_arrFilter['category_id']);
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

	public function withLanguage( $_intLang ){
		$this->_withLanguage=$_intLang;
		return $this;
	}

	public function withVendorId( $_strVendorId ){
		$this->_withVendorId=explode( ',' ,$_strVendorId );
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

	public function withThumb( $_arr=array(1,2) ){
		if(is_string($_arr)){
			$_arr=explode(',',$_arr);
		}
		if(!empty($_arr)){
			$this->_withThumb=$_arr;
		}
		return $this;
	}

	protected function init(){
		parent::init();
		$this->_withTags=false;
		$this->_withCategories=false;
		$this->_withRewrite=false;
		$this->_withJson=false;
		$this->_withThumb=false;
		$this->_forBeckend=false;
		$this->_withVendorId=false;
	}

	protected function assemblyQuery() {
		if ( $this->_onlyIds ) {
			$this->_crawler->set_select( 'd.id' );
		} elseif ( $this->_toSelect ) {
			$this->_crawler->set_select( 'd.id, d.title' );
		} else {
			$this->_crawler->set_select( 'd.*' );
		}
		$this->_crawler->set_from( $this->table.' d' );
		if( $this->_limit ){
			$this->_crawler->set_limit( $this->_counter.','.$this->_limit );
		}
		if ( !empty( $this->_withIds ) ) {
			$this->_crawler->set_where( 'd.id IN ('.Core_Sql::fixInjection( $this->_withIds ).')' );
		}
		if( $this->_withThumb ){
			$_sql='d.largethumb!="" ';
			for( $i=0; $i<10; $i++ ){
				$_sql.=' OR d.type'.$i.' IN ('. Core_Sql::fixInjection($this->_withThumb).') ';
			}
			$this->_crawler->set_where($_sql);
		}
		if ( !empty( $this->_withTags ) ) {
			$tags=new Core_Tags('clickbank');
			$tags->setTags( $this->_withTags )->getSearchQuery( $_strSql );
			$this->_crawler->set_where( 'd.id IN ('.$_strSql.')' );
		}
		if( $this->_withVendorId ){
			$this->_crawler->set_where( 'd.vendor_id IN ('.Core_Sql::fixInjection( $this->_withVendorId ).')' );
		}
		if ( $this->_onlyOwner ) {
			$this->_crawler->set_where( 'd.user_id='.$this->getOwnerId() );
		}
		if ( $this->_withLanguage ) {
			$this->_crawler->set_where( 'd.flg_language='.Core_Sql::fixInjection( $this->_withLanguage ) );
		}
		if ( !( $this->_onlyOne||$this->_onlyCell ) ) {
			$this->_crawler->set_order_sort( $this->_withOrder );
		}
		if ( !empty( $this->_withCategories ) ) {
			$this->_crawler->set_where( 'd.category_id IN ('.Core_Sql::fixInjection( $this->_withCategories ).')' );
		}
	}

	/**
	 * Set settings to content
	 *
	 * @param  $arrSettings
	 * @return bool|Project_Content_Adapter_Clickbank
	 */
	public function setSettings( $arrSettings ){
		if( empty($arrSettings) ){
			return false;
		}
		$this->_settings=$arrSettings;
		return $this;
	}

	private $_forEdit=false;

	public function forEdit(){
		$this->_forEdit=true;
		return $this;
	}

	/**
	 * Get content from DataBase
	 *
	 * @param array $mixRes
	 */
	public function getList( &$mixRes ){
		$_onlyOne=$this->_onlyOne;
		$_forEdit=$this->_forEdit;
		$_withJson=$this->_withJson;
		parent::getList( $mixRes );
		if( !$_forEdit ){
			if ( $_onlyOne ){
				$this->addPath($mixRes);
				$mixRes['affiliate_id']=$this->_settings['affiliate_id'];
				$mixRes['short_description']=str_replace(
					array('#vendorID#','#affiliateID#'),
					array($mixRes['vendor_id'], $this->_settings['affiliate_id']),
					$mixRes['short_description'] );
				$mixRes['long_description']=str_replace(
					array('#vendorID#','#affiliateID#'),
					array($mixRes['vendor_id'], $this->_settings['affiliate_id']),
					$mixRes['long_description'] );
			} else {
				foreach( $mixRes as &$_item ){
				$_item['affiliate_id']=$this->_settings['affiliate_id'];
				$_item['short_description']=str_replace(
					array('#vendorID#','#affiliateID#'),
					array($_item['vendor_id'], $this->_settings['affiliate_id']),
					$_item['short_description'] );
				$_item['long_description']=str_replace(
					array('#vendorID#','#affiliateID#'),
					array($_item['vendor_id'], $this->_settings['affiliate_id']),
					$_item['long_description'] );
				}
			}
		}
		if( $_forEdit ){
			$this->addPath($mixRes);
			return $this;
		}
		if(!empty($_withJson)){
			foreach( $mixRes as &$_item ){
				$_item['fields']=serialize($_item);
			}
		}
		return $this;
	}

	public function prepareBody( &$arrRes ){
		foreach( $arrRes as &$_item ){
			if( !is_array($_item) ){
				return;
			}
			$_fields=unserialize($_item['body']);
			$this->addPath( $_fields);
			for( $_i=0; $_i<10; $_i++ ){
				if( !empty($_fields['preview'.$_i])){
					$_item['files'][]=$_fields['preview'.$_i];
				}
			}
			if(!empty($_fields['smallthumb_preview'])){
				$_item['files'][]=$_fields['smallthumb_preview'];
			}
			if(!empty($_fields['largethumb_preview'])){
				$_item['files'][]=$_fields['largethumb_preview'];
			}
			if(empty($_fields)){
				continue;
			}
			if( $this->_withRewrite ){
				Zend_Registry::get('rewriter')->setText( $_fields['title'] )->setDeep( $this->_withRewrite )->rewrite( $_tmpRes );
				$_fields['title']=(empty($_tmpRes))?$_fields['title']:array_shift( $_tmpRes );
				unset($_tmpRes);
				Zend_Registry::get('rewriter')->setText( $_fields['long_description'] )->setDeep( $this->_withRewrite )->rewrite( $_tmpRes );
				$_fields['body']=(empty($_tmpRes))?$_fields['body']:array_shift( $_tmpRes );
			}
			if (empty($this->_settings['template'])) {
				$this->_settings['template']='0';
			}
			if( $this->_withThumb && empty($_fields['largethumb']) ){
				for( $i=0; $i<10; $i++ ){
					if( in_array($_fields['type'.$i], $this->_withThumb) ){
						$_fields['largethumb']=$_fields['file'.$i];
					}
				}
			}
			$_item['thumb']=$_fields['largethumb'];
			$path=Zend_Registry::get( 'config' )->path->relative->source.'site1_publisher'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'clickbank'.DIRECTORY_SEPARATOR;
			$_item['body']=Core_View::factory( Core_View::$type['one'] )
				->setTemplate( $path.$this->_settings['template'].'.tpl' )
				->setHash( $_fields )
				->parse()
				->getResult();
		}
		$this->init();
	}

	/**
	 * Add path for preview to banners and thumb 
	 *
	 * @param array $mixRes
	 */
	private function addPath( &$mixRes ){
		for ($i=0;$i<=count(self::$bannerType); $i++){
			if ( !empty($mixRes['file'.$i]) ){
				$mixRes['preview'.$i]=$this->_filesPath.$mixRes['file'.$i];
			}
		}
		if( !empty($mixRes['smallthumb']) ){
			$mixRes['smallthumb_preview']=$this->_filesPath.$mixRes['smallthumb'];
		}
		if( !empty($mixRes['largethumb']) ){
			$mixRes['largethumb_preview']=$this->_filesPath.$mixRes['largethumb'];
		}
	}
}
?>