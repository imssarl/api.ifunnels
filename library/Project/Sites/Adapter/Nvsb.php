<?php
// ->setSite( siteId )->setContent( $arrContent )->deleteContent();
class Project_Sites_Adapter_Nvsb implements Core_Singleton_Interface, Project_Sites_Adapter_Interface {

	private static $_instance=NULL;

	public static function getInstance(){
		if ( self::$_instance==NULL ) {
			self::$_instance= new self();
		}
		return self::$_instance;
	}

	public function __construct() {
		$this->_error=Core_Data_Errors::getInstance();
	}

	/**
	 * Errors object
	 *
	 * @var Core_Data_Errors object
	 */
	protected $_error;

	public function getErrors( &$arrErrors ) {
		$arrErrors=$this->_error->getErrors();
		return $this;
	}

	protected $_userId=0;

	public function setUser( $_int ) {
		$this->_userId=$_int;
		return $this;
	}

	protected $_content=array();

	public function setContent( $data ) {
		if ( empty( $data ) ) {
			// error todo
		}
		$this->_content=$data;
		return $this;
	}

	public function getContent() {
		return $this->_content;
	}

	protected $_siteId=0;

	/**
	 * Core_Data object
	 *
	 * @var Core_Data object
	 */
	protected $_data;

	public function setSite( $_intId=0 ) {
		if ( empty( $_intId ) ) {
			throw new Exception( Core_Errors::DEV.'|Site id is empty' );
			return $this;
		}
		$this->_siteId=$_intId;
		$_site=new Project_Sites( Project_Sites::NVSB );
		if ( !$_site->onlyOne()->withIds( $this->_siteId )->getList( $_arrSite )->checkEmpty() ) {
//			throw new Exception( Core_Errors::DEV.'|Site with '.$this->_siteId.' not found' );
			return $this;
		}
		$this->_data=new Core_Data( $_arrSite );
		$this->_data->setFilter( array( 'trim', 'clear' ) );
		return $this;
	}

	public $withOrder='edited--up';

	public $table='es_nvsb';

	protected $_fields=array( 
		'id', 'user_id', 'placement_id','flg_traking','traking_code', 'category_id', 'tag_cloud','mandatory_keywords','flg_articles','flg_comments','flg_related_keywords','flg_usage',
		'flg_damas', 'damas_ids', 'url', 'main_keyword', 'google_analytics','sub_dir', 'ftp_directory', 'catedit', 'edited', 'added' );

	public function get( &$arrRes, $_arrSite=array() ) {
		$arrRes['arrNvsb']=$arrRes['arrOpt']=$_arrSite;
		$arrRes['arrNvsb']['syndication']=Project_Syndication_Sites::isSyndicated( $_arrSite['id'], Project_Sites::NVSB ); // syndication
		return true;
	}

	private function correctUrl() {
		// исправляем ссылку если нет закрывающего слэша
		if ( substr( $this->_data->filtered['url'], -1 )!='/' ) {
			$this->_data->setElement( 'url', $this->_data->filtered['url'].'/' );
		}
		if ( substr( $this->_data->filtered['url'], 0, 7)!='http://' ) {
			$this->_data->setElement( 'url', 'http://'.$this->_data->filtered['url'] );
		}
	}

	public function import( Project_Sites $object ) {
		$this->_data=new Core_Data( $object->getDataObject()->setFilter( array( 'stripslashes', 'trim', 'clear' ) )->getRaw( 'arrNvsb' ) );
		if ( !$this->_error->setData( $this->_data->setFilter( array( 'trim', 'clear' ) ) )->setValidators( array(
			'placement_id'=>$this->_error->getValidator( 'Zend_Validate_NotEmpty' ),
			'url'=>$this->_error->getValidator( 'Zend_Validate_NotEmpty' ),
			'ftp_directory'=>$this->_error->getValidator( 'Zend_Validate_NotEmpty' ),
			'category_id'=>$this->_error->getValidator( 'Zend_Validate_NotEmpty' ),
			'main_keyword'=>$this->_error->getValidator( 'Zend_Validate_NotEmpty' ),
		) )->isValid() ) {
			return false;
		}
		$this->correctUrl();
		$this->_data->setElements( array(
			'user_id'=>$this->_userId,
			'added'=>time(),
			'edited'=>time(),
		) );
		return $this->saveRec();
	}

	public function set( Project_Sites $object ) {
		$this->_data=new Core_Data( $object->getDataObject()->setFilter( array( 'stripslashes', 'trim', 'clear' ) )->getRaw( 'arrNvsb' ) );
		$this->_data->setElement( 'arrArticleIds', $object->getDataObject()->filtered['multibox_ids_content_wizard'] )->setFilter( array( 'trim', 'clear' ) );
		$_placement=new Project_Placement();
		if( !$_placement->withIds($this->_data->filtered['placement_id'])->getDomen( $strDomen )->isRemote() ){
			$this->_data->setElement('ftp_directory',($this->_data->filtered['ftp_root']==1)?'/':'/'.trim($this->_data->filtered['ftp_directory'],'/').'/');
			$this->_data->setElement('url','http://'.$strDomen.$this->_data->filtered['ftp_directory']);
		}
		if ( !$this->_error->setData( $this->_data )->setValidators( array(
			'placement_id'=>$this->_error->getValidator( 'Zend_Validate_NotEmpty' ),
			'ftp_directory'=>$this->_error->getValidator( 'Zend_Validate_NotEmpty' )
				->setMessage("Value is required and can't be empty. Click 'browse' link and select the folder, where your site should be installed."),
			'template_id'=>$this->_error->getValidator( 'Zend_Validate_NotEmpty' ),
			'category_id'=>$this->_error->getValidator( 'Zend_Validate_NotEmpty' ),
			'url'=>$this->_error->getValidator( 'Zend_Validate_NotEmpty' ),
			'main_keyword'=>$this->_error->getValidator( 'Zend_Validate_NotEmpty' ), 
		) )->isValid() ) {
			return false;
		}
		$this->correctUrl();
		if ( empty( $this->_data->filtered['id'] ) ) {
			$this->_data->setElements( array(
				'user_id'=>$this->_userId,
				'added'=>time(),
			) );
		}
		$_arrUrl=parse_url($this->_data->filtered['url']);
		if( !empty($_arrUrl['path']) ){
			$this->_data->setElement('sub_dir',trim($_arrUrl['path'],'/'));
		}
		$_arrIds=array(0=>array(),1=>array());
		if ( count( $this->_data->filtered['arrArticleIds'] )>0 ) {
			foreach( $this->_data->filtered['arrArticleIds'] as $item ) {
				$_arrIds[$item['flg_type']][]=$item['id'];
			}
		}
		$this->_data->setElements( array(
			'edited'=>time(),
			'arrArticleIds'=>$_arrIds,
			'damas_ids'=>(!empty( $object->getDataObject()->filtered['dmascodetext'] )? $object->getDataObject()->filtered['dmascodetext']:''),
			'flg_damas'=>(!empty( $object->getDataObject()->filtered['headlines_spot1'] )? $object->getDataObject()->filtered['headlines_spot1']:0),
			'flg_traking'=>(!empty( $object->getDataObject()->filtered['arrOpt']['flg_traking'] )? $object->getDataObject()->filtered['arrOpt']['flg_traking']:0),
			'traking_code'=>(!empty( $object->getDataObject()->filtered['arrOpt']['traking_code'] )? $object->getDataObject()->filtered['arrOpt']['traking_code']:''),
			'flg_articles'=>(empty( $this->_data->filtered['flg_articles'] )? 0:1),
		) );
		$this->_optData=$object->getDataObject()->getRaw( 'arrOpt' );
		return $this->upload();
	}

	private function saveRec() {
		$this->_data->filtered['google_analytics']=$this->_data->getRaw('google_analytics');
		$this->_data->setElement( 'id', Core_Sql::setInsertUpdate( $this->table, $this->_data->setMask( $this->_fields )->getValid() ) );
		if ( empty( $this->_data->filtered['id'] ) ) {
			return false;
		}
		$this->_siteId=$this->_data->filtered['id'];
		// добавление в Syndication
		Project_Syndication_Sites::setOutside( $this->_data->filtered['id'], Project_Sites::NVSB, empty( $this->_data->filtered['syndication'] ) );
		return true;
	}

	protected function afterUpload() {
		Core_Sql::reconnect();
		if ( !$this->saveRec() ) {
			return false;
		}
		// линк на шаблон
		$_templates=new Project_Sites_Templates( Project_Sites::NVSB );
		if ( !$_templates->siteLink( $this->_data->filtered['id'], $this->_data->filtered['template_id'] ) ) {
			return false;
		}
		// Сохранить контент в таблицу, для последующего удаления.
		$_arrContentTypes=array('0'=>array(),'1'=>array());
		foreach( $this->_content as $_article ){
			$_arrContentTypes[$_article['flg_type']][]=$_article;
		}
		$_content=new Project_Sites_Content( Project_Sites::NVSB ); // подключаем класс
		$_content
			->withFlgFrom( Project_Sites_Content::$type['self'] )
			->withSourceIndex( Project_Content::$source['User\'s Content'][0]['flg_source'] )
			->withSiteId( $this->_siteId )
			->setContent( $_arrContentTypes[0] )
			->set();
		$_content=new Project_Sites_Content( Project_Sites::NVSB ); // подключаем класс
		$_content
			->withFlgFrom( Project_Sites_Content::$type['self'] )
			->withSourceIndex( Project_Content::$source['Pure Content'][0]['flg_source'] )
			->withSiteId( $this->_siteId )
			->setContent( $_arrContentTypes[1] )
			->set();
		// опшинсы сайта
		$_opt=new Project_Options( Project_Sites::NVSB , $this->_data->filtered['id'] );
		return $_opt->setData( $this->_optData )->set();
	}

	public function upload() {
		if( empty($this->_data->filtered) ){
			return false;
		}
		if ( !$this->prepareData() ) {
			return false;
		}
		if ( !$this->prepareSource() ) {
			return false;
		}
		$_transport=new Project_Placement_Transport();
		if ( !$_transport
			->setInfo( $this->_data->filtered )
			->setSourceDir( $this->_dir )
			->placeAndBreakConnect() ) {
			return false;
		}
		return $this->afterUpload();
	}

	protected function prepareData() {
		if( empty( $this->_data->filtered['arrArticleIds'] ) ){
			return true;
		}
		if( isset( $this->_data->filtered['arrArticleIds'][1] ) )
			Project_Content::factory( 4 )->setFilter( array ('flg_language' => 1) )->withIds( $this->_data->filtered['arrArticleIds'][1] )->getList( $_arrPureContent );
		if( isset( $this->_data->filtered['arrArticleIds'][0] ) )
			Project_Articles::getInstance()->withIds( $this->_data->filtered['arrArticleIds'][0] )->getList( $_arrArticlesContent );
		$this->_content=array();
		foreach( $_arrPureContent as $_arrContent ){
			$this->_content[]=$_arrContent+array('flg_type'=>1);
		}
		foreach( $_arrArticlesContent as $_arrContent ){
			unset($_arrContent['id']);
			$this->_content[]=$_arrContent+array('flg_type'=>0);
		}
		return true;
	}

	protected function prepareSource() {
		$this->_dir='Project_Sites_Adapter_Nvsb@prepareSource';
		if ( !Zend_Registry::get( 'objUser' )->prepareTmpDir( $this->_dir ) ) {
			return $this->_error->setError( 'Can\'t create dir '.$this->_dir );
		}
		if ( !$this->getTemplate() ) {
			return $this->_error->setError( 'Can\'t get template' );
		}
		if ( !$this->patchFiles() ) {
			return $this->_error->setError( 'Can\'t patch files' );
		}
		if ( !$this->generateContent() ) {
			return $this->_error->setError( 'Can\'t generate content' );
		}
		return true;
	}

	// контент из статей при создании-редактировании сайта
	protected function getFileContent( &$arrItem ) {
		return $arrItem['title']."\n".$arrItem['author']."\n".$arrItem['body'];
	}

	protected function generateContent() {
		if ( empty( $this->_content ) ) {
			return true;
		}
		$_strDir=$this->_dir.'articles'.DIRECTORY_SEPARATOR;
		if ( !is_dir( $_strDir ) ) {
			mkdir( $_strDir, 0755, true );
		}
		if ( !is_dir( $_strDir ) ) {
			return $this->_error->setError( 'Can\'t create dir '.$_strDir );
		}
		$_strImageDir=$this->_dir.'images'.DIRECTORY_SEPARATOR;
		if ( !is_dir( $_strImageDir ) ) {
			mkdir( $_strImageDir, 0777, true );
		}
		foreach( $this->_content as $v ) {
			$_strFileName=Core_String::getInstance( strtolower( strip_tags( $v['title'] ) ) )->toSystem( '-' ).'.txt';
			$_str=$this->getFileContent( $v );
			if ( !Core_Files::setContent( $_str, $_strDir.$_strFileName ) ) {
				continue;
			}
			if( !empty($v['files']) ){
				foreach( $v['files'] as $_file ){
					copy( $_file, $_strImageDir.Core_Files::getBaseName($_file) );
				}
			}
		}
		return true;
	}

	private function patchFiles() {
		// dams
		$_strCode=Project_Options_GetCode::getDamsPhpCode( $this->_optData );
		$_strCode.=' '.Project_Options_GetCode::getTrakingCode( $this->_optData );
		$_strFile=file_get_contents( $this->_dir.'damscode.php' );
		$_arrFiles['damscode.php']=str_replace( '<damscode>', (empty( $_strCode )?'':$_strCode), $_strFile );
		// spots
		$_arrCode=Project_Options_GetCode::getSpotsCode( $this->_optData );
		if( empty( $this->_data->filtered['google_analytics'] ) ){
			$_arrCode=array(
				'spot1'=>isset($_arrCode['spot1'])?$_arrCode['spot1']:' ',
				'spot2'=>isset($_arrCode['spot2'])?$_arrCode['spot2']:' ',
				'spot3'=>isset($_arrCode['spot3'])?$_arrCode['spot3']:' ',
			);
		}
		// spot1
		$_strFile=file_get_contents( $this->_dir.'mainads.php' );
		if ( empty( $_arrCode['spot1'] ) ) { // if defult then $_arrCode['spot1']==false а также его может и не быть
			$_arrFiles['mainads.php']=str_replace( array( '<spot1>', '<default1>', '</default1>' ), array( '', '', '' ), $_strFile );
		} else {
			$_strDefaultCode=substr( $_strFile, stripos( $_strFile, '<default1>' ), stripos( $_strFile, '</default1>' ) + 3 );
			$_arrFiles['mainads.php']=str_replace( array( '<spot1>', $_strDefaultCode ), array( '', $_arrCode['spot1'] ), $_strFile );
		}
		// spot2
		$_strFile=file_get_contents( $this->_dir.'sideads.php' );
		if ( empty( $_arrCode['spot2'] ) ) {
			$_strFile=str_replace( array( '<spot2>', '<default2>', '</default2>' ), array( '', '', '' ), $_strFile );
		} else {
			$strposStart=stripos( $_strFile, '<default2>' );
			$strposEnd=stripos( $_strFile, '</default2>' );
			$_strDefaultCode=substr( $_strFile, $strposStart , $strposEnd - $strposStart + 11 );
			$_strFile=str_replace( array( '<spot2>', $_strDefaultCode ), array( '', $_arrCode['spot2'] ), $_strFile );
		}
		// spot3
		if ( empty( $_arrCode['spot3'] ) ) {
			$_strFile=str_replace( array( '<spot3>', '<default3>', '</default3>' ), array( '', '', '' ), $_strFile );
		} else {
			$strposStart=stripos( $_strFile, '<default3>' );
			$strposEnd=stripos( $_strFile, '</default3>' );			
			$_strDefaultCode=substr( $_strFile,  $strposStart , $strposEnd - $strposStart + 11 );
			$_strFile=str_replace( array( '<spot3>', $_strDefaultCode ), array( '', $_arrCode['spot3'] ), $_strFile );
		}
		$_arrFiles['sideads.php']=$_strFile;
		// config
		$_strFile=file_get_contents( $this->_dir.'config.php' );
		$_arrFiles['config.php']=str_replace( array(
			'$$$folder$$$',
			'$$$keyword$$$',
			'$$$adsense$$$',
		), array(
			$this->_data->filtered['sub_dir'],
			$this->_data->filtered['main_keyword'],
			$this->_data->filtered['google_analytics']
		), $_strFile );
		// glodal
		$_strFile=file_get_contents( $this->_dir.'global.php' );
		$_arrFiles['global.php']=str_replace( array(
			'$$$related$$$',
			'$$$usage$$$',
			'$$$mandatory$$$',
			'$$$cloud$$$',
			'$$$comments$$$',
		), array(
			$this->_data->filtered['flg_related_keywords'],
			(($this->_data->filtered['flg_usage']==0)? 1:2),
			$this->_data->filtered['mandatory_keywords'],
			$this->_data->filtered['tag_cloud'],
			$this->_data->filtered['flg_comments'],
		), $_strFile );
		 // Шаблон не менялся, удаляем все файлы шаблона, и создаем только те которые меняются.
		if( !empty($this->_data->filtered['id'])&&($this->_data->filtered['old_template_id']==$this->_data->filtered['template_id']) ){
			$this->_dir='Project_Sites_Adapter_Nvsb@prepareSource';
			if ( !Zend_Registry::get( 'objUser' )->prepareTmpDir( $this->_dir ) ) {
				return $this->_error->setError( 'Can\'t create dir '.$this->_dir );
			}
		}
		// сохраняем
		if ( !empty( $this->_arrFiles ) ){
			Core_Files::setContentMass( $this->_arrFiles, $this->_dir );
		}
		return Core_Files::setContentMass( $_arrFiles, $this->_dir );
	}

	private function getTemplate() {
		$_template=new Project_Sites_Templates( Project_Sites::NVSB );
		if ( !$_template->onlyOne()->withIds( $this->_data->filtered['template_id'] )->getList( $_arrTemplate )->checkEmpty() ) {
			return false;
		}
		return Core_Zip::getInstance()
			->setDir( $this->_dir )
			->extractZip( $_arrTemplate['path'].$_arrTemplate['filename'] );
	}

	public function deleteContent() {
		$_strDir='Project_Sites_Adapter_Nvsb@deleteContent';
		if ( !Zend_Registry::get( 'objUser' )->prepareTmpDir( $_strDir ) ) {
			return $this->_error->setError( 'Can\'t create dir '.$_strDir );
		}
		// в этом случае контент будет содержать данные из es_content
		// например для контента из сайт билдера берём так:
		// $_content=new Project_Sites_Content( Project_Sites::NCSB );
		// $_content->withSiteId( $this->_data->filtered['id'] )->withFlgFrom( Project_Sites_Content::$type['self'] )->getList( $_arrContrnt );
		// потом делаем через адаптер
		// ->setSite( $this->_data->filtered['id'] )->setContent( $arrContent )->deleteContent();
		foreach( $this->_content as $v ) {
			$_arrFiles[]=$v['link'].'.txt';
		}
		$_str=serialize( $_arrFiles );
		// файл по которому удаляем контент
		Core_Files::setContent( $_str, $_strDir.'articles-list.txt');
		$_transport=new Project_Placement_Transport();
		return $_transport
			->setInfo( $this->_data->filtered )
			->setSourceDir( $_strDir )
			->placeAndBreakConnect();
	}

	// сайты удаляем только из нашей БД
	public function deleteSites( $_arrIds ) {
		// ссылки на шаблоны
		$_templates=new Project_Sites_Templates( Project_Sites::NCSB );
		$_templates->siteLink( $_arrIds );
		// споты
		$options=new Project_Options(Project_Sites::NCSB);
		foreach( $_arrIds as $intId ) {
			$options->setSiteId( $intId )->clearOptions();
		}
		// удаление сайта пользователя из системы syndication
		Project_Syndication_Sites::setOutside( $_arrIds, Project_Sites::NVSB );
		// сайты с контентом
		Core_Sql::setExec( 'DELETE FROM '.$this->table.' WHERE id IN('.Core_Sql::fixInjection( $_arrIds ).')' );
	}

	private $_arrFiles=array();

	public function setFiles( $_arrFiles=array() ){
		if ( empty($_arrFiles) ){
			return false;
		}
		if ( is_file( $_arrFiles['keywords']['tmp_name'] )  && Core_Files::getExtension($_arrFiles['keywords']['name']) == 'txt'  ){
			Core_Files::getContent($_strKeywords,$_arrFiles['keywords']['tmp_name']);
			$this->_arrFiles['keywords.txt']=$_strKeywords;
		}
		if ( is_file( $_arrFiles['links']['tmp_name'] ) && Core_Files::getExtension($_arrFiles['links']['name']) == 'txt' ){
			Core_Files::getContent($_strLinks,$_arrFiles['links']['tmp_name'] );
			$this->_arrFiles['links.txt']=$_strLinks;
		}
		return true;
	}

	public function urlLog( $_arrSite ){
		if ( empty( $_arrSite ) ){
			return false;
		}
		$this->_strBaseUrl=$_arrSite['url'];
		$this->_arrUrlsLog[]=$this->_strBaseUrl;
		if ( $_arrSite['flg_articles']==1 ){
			$this->getUrlArticle($_arrSite['id']);
		}
		$this->getUrl4Keyword( $_arrSite['main_keyword'] );
		if ( !empty( $_arrSite['tag_cloud'] ) ){
			$_arrTag=explode(',',$_arrSite['tag_cloud']);
			foreach ($_arrTag as $_strKeyword ){
				$this->getUrl4Keyword( trim($_strKeyword) );
			}
			Core_Sql::disconnect();
			Core_Sql::setConnectToServer( 'members.creativenichemanager.info' );
		}
		return $this->_arrUrlsLog;
	}

	private function getUrlArticle( $_intId ){
		$_article=new Project_Articles_Links();
		$_article->getIds($_arrRes,$_intId,Project_Sites::NVSB );
		$this->_arrUrlsLog[]=$this->_strBaseUrl . 'article/';
		foreach ( $_arrRes as $_item ){
			$this->_arrUrlsLog[]=$this->_strBaseUrl . 'article/'.Core_String::getInstance( strtolower( strip_tags( $_item['title'] ) ) )->toSystem( '-' ).'.html';
		}
	}

	private function getUrl4Keyword( $_strKeyword ){ 
		if ( empty( $_strKeyword ) ){
			return false;
		}
		$maxreturned=25;
		$totalmax=999;
		$realmax=$this->getRssTotalResult($_strKeyword);
		if ( $realmax < $totalmax ){
			$totalmax=$realmax;
		}
		$totalpages=ceil($totalmax/$maxreturned);
		for( $i=0; $i<=$totalpages; $i++ ){
			$this->_arrUrlsLog[]=$this->_strBaseUrl . 'video-theme/' .(($i!=0)?"{$i}/":'') . str_replace( ' ', '+', $_strKeyword ) .'.html';
		}
	}

	private function getRssTotalResult( $_strKeyword ){
		$_objYoutube=new Zend_Gdata_YouTube();
		$query=new Zend_Gdata_YouTube_VideoQuery();
		$query->setStartIndex( 1 );
		$query->setVideoQuery( str_replace(' ','+',$_strKeyword) );
    	$videoFeed=$_objYoutube->getVideoFeed($query);	
		try {
			$_videoFeed=$_objYoutube->getVideoFeed( $_query );
		} catch ( Exception $e ){
			return false;
		}
		$totalResult=$_videoFeed->getTotalResults();
		return $totalResult->text;
	}
}
?>