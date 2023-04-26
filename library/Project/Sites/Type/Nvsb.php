<?php

 /**
 * система сайтов
 */
class Project_Sites_Type_Nvsb  extends Project_Sites_Type_Abstract {

	protected $_withOrder='edited--up';

	protected $_table='es_nvsb';

	protected static $_lastUrls=array();
	
	protected $_fields=array( 
		'id', 'user_id', 'category_id',
		'tag_cloud','mandatory_keywords','flg_articles','flg_comments','flg_related_keywords','flg_usage',
		'flg_damas', 'damas_ids', 'url', 'main_keyword', 'google_analytics','sub_dir',
		'ftp_host', 'ftp_username', 'ftp_password', 'ftp_directory', 'catedit', 'edited', 'added' );

	private $_arrUrlsLog=array();
	private $_strBaseUrl='';
	
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

	public function del( $_arrIds ) {
		// споты
		$options=new Project_Options(Project_Sites::NVSB);
		foreach( $_arrIds as $intId ) {
			$options->setSiteId( $intId )->clearOptions();
		}
		// ссылки на шаблоны
		$_templates=new Project_Sites_Templates( Project_Sites::NVSB );
		$_templates->siteLink( $_arrIds );
		// syndication
		Project_Syndication_Sites::setOutside( $_arrIds, Project_Sites::NVSB );
		// сами сайты
		Core_Sql::setExec( 'DELETE FROM '.$this->_table.' WHERE id IN('.Core_Sql::fixInjection( $_arrIds ).')' );
		return true;
	}

	public function get( &$arrRes, $_arrSite=array() ) {
		$arrRes['arrNvsb']=$arrRes['arrOpt']=$_arrSite;
		$arrRes['arrFtp']=array(
			'address'=>$_arrSite['ftp_host'],
			'username'=>$_arrSite['ftp_username'],
			'password'=> $_arrSite['ftp_password'],
			'directory'=>$_arrSite['ftp_directory']);
		$arrRes['arrNvsb']['syndication']=Project_Syndication_Sites::isSyndicated( $_arrSite['id'], Project_Sites::NVSB ); // syndication
		return true;
	}

	public function import( Project_Sites $object ) {
		$this->data=new Core_Data( $object->getDataObject()->setFilter( array( 'stripslashes', 'trim', 'clear' ) )->getRaw( 'arrNvsb' ) );
		if ( !$this->data
			->setElements( array(
				'ftp_host'=>$object->getDataObject()->filtered['arrFtp']['address'],
				'ftp_username'=>$object->getDataObject()->filtered['arrFtp']['username'],
				'ftp_password'=>$object->getDataObject()->filtered['arrFtp']['password'],
				'ftp_directory'=>$object->getDataObject()->filtered['arrFtp']['directory'], ) )
			->setChecker( array(
				'ftp_host'=>empty( $this->data->filtered['ftp_host'] ),
				'ftp_username'=>empty( $this->data->filtered['ftp_username'] ),
				'ftp_password'=>empty( $this->data->filtered['ftp_password'] ),
				'ftp_directory'=>empty( $this->data->filtered['ftp_directory'] ),
				'category_id'=>empty( $this->data->filtered['category_id'] ),
				'url'=>empty( $this->data->filtered['url'] ),
				'main_keyword'=>empty( $this->data->filtered['main_keyword'] ), ) )
			->check() ) {
			$this->data->getErrors( $this->_errors['arrNvsb'] );
			return false;
		}
		// исправляем ссылку если нет закрывающего слэша
		if ( substr( $this->data->filtered['url'], -1 )!='/' ) {
			$this->data->setElement( 'url', $this->data->filtered['url'].'/' );
		}
		if ( substr( $this->data->filtered['url'], 0, 7)!='http://' ) {
			$this->data->setElement( 'url', 'http://'.$this->data->filtered['url'] );
		}
		$_connector=new Project_Sites_Connector();
		if ( !$_connector
			->setHttpUrl( $this->data->filtered['url'] )
			->setHost( $this->data->filtered['ftp_host'] )
			->setUser( $this->data->filtered['ftp_username'] )
			->setPassw( $this->data->filtered['ftp_password'] )
			->setRoot( $this->data->filtered['ftp_directory'] )
			->checkFtpAccessibility() ) {
			$this->_errors['connect']='can not connect to ftp server ' . $this->data->filtered['ftp_host'] ;
			return false;
		}
		$this->data->setElements( array(
			'user_id'=>$this->_userId,
			'added'=>time(),
			'edited'=>time(),
		) );
		return $this->saveRec();
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

	public function set( Project_Sites $object ) {
		$this->data=new Core_Data( $object->getDataObject()->setFilter( array( 'stripslashes', 'trim', 'clear' ) )->getRaw( 'arrNvsb' ) );
		if ( !$this->data
			->setElements( array(
				'arrArticleIds'=>$object->getDataObject()->filtered['multibox_ids_content_wizard'],
				'ftp_host'=>$object->getDataObject()->filtered['arrFtp']['address'],
				'ftp_username'=>$object->getDataObject()->filtered['arrFtp']['username'],
				'ftp_password'=>$object->getDataObject()->filtered['arrFtp']['password'],
				'ftp_directory'=>$object->getDataObject()->filtered['arrFtp']['directory'], ) )
			->setChecker( array(
				'ftp_host'=>empty( $this->data->filtered['ftp_host'] ),
				'ftp_username'=>empty( $this->data->filtered['ftp_username'] ),
				'ftp_password'=>empty( $this->data->filtered['ftp_password'] ),
				'ftp_directory'=>empty( $this->data->filtered['ftp_directory'] ),
				'template_id'=>( empty($this->data->filtered['id']) && empty( $this->data->filtered['template_id'] ) ), // тк у регистрированного сайта нет template_id, то на него не проверяем
				'category_id'=>empty( $this->data->filtered['category_id'] ),
				'url'=>empty( $this->data->filtered['url'] ),
				'main_keyword'=>empty( $this->data->filtered['main_keyword'] ), ) )
			->check() ) {
			$this->data->getErrors( $this->_errors['arrNvsb'] );
			return false;
		}
		// исправляем ссылку если нет закрывающего слэша
		if ( substr( $this->data->filtered['url'], -1 )!='/' ) {
			$this->data->setElement( 'url', $this->data->filtered['url'].'/' );
		}
		if ( substr( $this->data->filtered['url'], 0, 7)!='http://' ) {
			$this->data->setElement( 'url', 'http://'.$this->data->filtered['url'] );
		}
		if ( empty( $this->data->filtered['id'] ) ) {
			$this->data->setElements( array(
				'user_id'=>$this->_userId,
				'added'=>time(),
			) );
		}
		$_arrIds=array();
		if ( !empty( $this->data->filtered['flg_articles'] ) ) {
			foreach( $this->data->filtered['arrArticleIds'] as $item ) {
				$_arrIds[]=$item['id'];
			}
		}
		$this->data->setElements( array(
			'edited'=>time(),
			'flg_damas'=>(!empty( $object->getDataObject()->filtered['headlines_spot1'] )? $object->getDataObject()->filtered['headlines_spot1']:0),
			'flg_articles'=>(empty( $this->data->filtered['flg_articles'] )? 0:1),
			'arrArticleIds'=>$_arrIds,
			'damas_ids'=>(!empty( $object->getDataObject()->filtered['dmascodetext'] )? $object->getDataObject()->filtered['dmascodetext']:''),
		) );
		$this->_optData=$object->getDataObject()->getRaw( 'arrOpt' );
		if ( !$this->upload() ) {
			return false;
		}
		if ( !$this->saveRec() ) {
			return false;
		}
		// линк на шаблон
		$_templates=new Project_Sites_Templates( Project_Sites::NVSB );
		if ( !$_templates->siteLink( $this->data->filtered['id'], $this->data->filtered['template_id'] ) ) {
			return false;
		}

		// Сохранить контент в таблицу, для последующего удаления.
		$_content=new Project_Sites_Content( Project_Sites::NVSB ); // подключаем класс
		$_content
			->withFlgFrom( Project_Sites_Content::$type['self'] )
			->withSourceIndex( Project_Content::$source['User\'s Content'][0]['flg_source'] )
			->withSiteId( $this->data->filtered['id'] )
			->setContent($this->_content)
			->set();

		$_opt=new Project_Options(  Project_Sites::NVSB , $this->data->filtered['id'] );
		if ( !$_opt->setData( $this->_optData )->set()){
			return false;
		}
		return true;
	}

	private function saveRec() {
		$this->data->setElement( 'id', Core_Sql::setInsertUpdate( $this->_table, $this->data->setMask( $this->_fields )->getValid() ) );
		if ( empty( $this->data->filtered['id'] ) ) {
			return false;
		}
		Project_Syndication_Sites::setOutside( $this->data->filtered['id'], Project_Sites::NVSB, empty( $this->data->filtered['syndication'] ) ); // Syndication
		return true;
	}


	protected function upload() {
		if ( !$this->prepareSource() ) {
			return false;
		}
		$_connector=new Project_Sites_Connector();
		if ( !$_connector
			->setSourceDir( $this->_dir )
			->setHttpUrl( $this->data->filtered['url'] )
			->setHost( $this->data->filtered['ftp_host'] )
			->setUser( $this->data->filtered['ftp_username'] )
			->setPassw( $this->data->filtered['ftp_password'] )
			->setRoot( $this->data->filtered['ftp_directory'] )
			->upload() ){
			$_connector->getErrors( $this->_errors );
			return false;
		}
		return true;
	}

	public function prepareSource() {
		$this->_dir='Project_Sites_Type_Nvsb@prepareSource';
		if ( !Zend_Registry::get( 'objUser' )->prepareTmpDir( $this->_dir ) ) {
			$this->_errors[]='Process Aborted. Can\'t create dir Project_Sites_Type_Nvsb@prepareSource';
			return false;
		}
		if ( !$this->getTemplate() ) {
			$this->_errors[]='Process Aborted. Can\'t get template';
			return false;
		}
		if ( !$this->patchFiles() ) {
			$this->_errors[]='Process Aborted. Can\'t patch files';
			return false;
		}
		if ( !$this->generateArticles() ) {
			$this->_errors[]='Process Aborted. Can\'t generate articles';
			return false;
		}
		return true;
	}

	private function getTemplate() {
		$_template=new Project_Sites_Templates( Project_Sites::NVSB );
		if ( !$_template->onlyOne()->withIds( $this->data->filtered['template_id'] )->getList( $_arrTemplate ) ) {
			return false;
		}
		return Core_Zip::getInstance()
			->setDir( $this->_dir )
			->extractZip( $_arrTemplate['path'].$_arrTemplate['filename'] );
	}

	private function patchFiles() {
		// dams
		$_strCode=Project_Options_GetCode::getDamsPhpCode( $this->_optData );
		$_strFile=file_get_contents( $this->_dir.'damscode.php' );
		$_arrFiles['damscode.php']=str_replace( '<damscode>', (empty( $_strCode )?'':$_strCode), $_strFile );
		// spots
		$_arrCode=Project_Options_GetCode::getSpotsCode( $this->_optData );
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
			$this->data->filtered['sub_dir'],
			$this->data->filtered['main_keyword'],
			$this->data->filtered['google_analytics']
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
			$this->data->filtered['flg_related_keywords'],
			(($this->data->filtered['flg_usage']==0)? 1:2),
			$this->data->filtered['mandatory_keywords'],
			$this->data->filtered['tag_cloud'],
			$this->data->filtered['flg_comments'],
		), $_strFile );
		 // Шаблон не менялся, удаляем все файлы шаблона, и создаем только те которые меняются.
		if( !empty($this->data->filtered['id'])&&($this->data->filtered['old_template_id']==$this->data->filtered['template_id']) ){
			$this->_dir='Project_Sites_Type_Nvsb@prepareSource';
			Zend_Registry::get( 'objUser' )->prepareTmpDir( $this->_dir );
		}
		// сохраняем
		if ( !empty( $this->_arrFiles ) ){
			Core_Files::setContentMass( $this->_arrFiles, $this->_dir );
		}
		return Core_Files::setContentMass( $_arrFiles, $this->_dir );
	}

	protected function generateArticles() {
		if ( empty( $this->data->filtered['flg_articles'] ) || empty($this->data->filtered['arrArticleIds']) ) { // сайт без статей
			return true;
		}
		if ( !Project_Articles::getInstance()->withIds( $this->data->filtered['arrArticleIds'] )->getList( $this->_content )->checkEmpty() ) {
			$this->_errors['articles']='Process Aborted. Unable to collect articles';
			return false;
		}
		$_strDir=$this->_dir.'articles'.DIRECTORY_SEPARATOR;
		if ( !is_dir($_strDir) ){
			mkdir($_strDir,0777,true);
		}
		foreach( $this->_content as $v ) {
			$_strContent=$v['title']."\n".$v['author']."\n".$v['body'];
			$_strFileName=Core_String::getInstance( strtolower( strip_tags( $v['title'] ) ) )->toSystem( '-' ).'.txt';
			if ( !Core_Files::setContent( $_strContent, $_strDir.$_strFileName ) ) {
				$this->_errors['articles']='Process Aborted. Unable to save articles';
				return false;
			}
			$_arrFiels[]=$_strFileName;
		}
		$_strFiles=serialize(array(1));
		Core_Files::setContent( $_strFiles, $this->_dir.'articles-list.txt');
		return true;
	}

	protected function setLinks( $_sheduleId, $_strFilename ){
		self::$_lastUrls[]=array('shedule_id'=>$_sheduleId, 'url'=> $this->data->filtered['url'] . 'article/' . Core_Files::getFileName($_strFilename) .'.html' );
	}

	public static function getLastUrls(){
		return self::$_lastUrls;
	}	
}
?>