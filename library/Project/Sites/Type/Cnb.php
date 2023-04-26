<?php

 /**
 * система сайтов
 */
class Project_Sites_Type_Cnb extends Project_Sites_Type_Abstract {

	protected $_withOrder='edited--up';

	protected $_table='es_cnb';
	
	protected static $_lastUrls=array();

	protected $_fields=array( 
		'id', 'parent_id', 'user_id', 'category_id', 'profile_id', 'flg_portal', 'flg_damas', 'damas_ids', 'url','sub_dir', 'title', 'primary_keyword', 
		'ftp_host', 'ftp_username', 'ftp_password', 'ftp_directory', 'catedit', 'edited', 'added' );

	public function del( $_arrIds ) {
		// споты
		$options=new Project_Options(Project_Sites::CNB);
		foreach( $_arrIds as $intId ) {
			$options->setSiteId( $intId )->clearOptions();
		}
		// ссылки на шаблоны
		$_templates=new Project_Sites_Templates( Project_Sites::CNB );
		$_templates->siteLink( $_arrIds );
		// syndication
		Project_Syndication_Sites::setOutside( $_arrIds, Project_Sites::CNB );
		// сами сайты
		Core_Sql::setExec( 'DELETE FROM '.$this->_table.' WHERE id IN('.Core_Sql::fixInjection( $_arrIds ).')' );
		return true;
	}

	public function sites2portal( $_arrSites, $_portalId ){
		if( empty($_arrSites)||empty($_portalId) ){
			return false;
		}
		Core_Sql::setExec('UPDATE '.$this->_table.' SET parent_id='.$_portalId.' WHERE id IN ('.Core_Sql::fixInjection($_arrSites).')');
		return true;
	}

	public function get( &$arrRes, $_arrSite=array() ) {
		$arrRes['arrCnb']=$arrRes['arrOpt']=$_arrSite;
		$arrRes['arrFtp']=array(
			'address'=>$_arrSite['ftp_host'],
			'username'=>$_arrSite['ftp_username'],
			'password'=> $_arrSite['ftp_password'],
			'directory'=>$_arrSite['ftp_directory']);
		$arrRes['arrCnb']['syndication']=Project_Syndication_Sites::isSyndicated( $_arrSite['id'], Project_Sites::CNB ); // syndication
		return true;
	}

	public function import( Project_Sites $object ) {
		$this->data=new Core_Data( $object->getDataObject()->setFilter( array( 'stripslashes', 'trim', 'clear' ) )->getRaw( 'arrCnb' ) );
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
				'title'=>empty( $this->data->filtered['title'] ), ) )
			->check() ) {
			$this->data->getErrors( $this->_errors['arrCnb'] );
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
			$this->_errors['connect'] = 'can not connect to ftp server ' . $this->data->filtered['ftp_host'] ;
			return false;
		}
		$this->data->setElements( array(
			'user_id'=>$this->_userId,
			'added'=>time(),
			'edited'=>time(),
		) );
		return $this->saveRec();
	}

	public function set( Project_Sites $object ) {
		$this->data=new Core_Data( $object->getDataObject()->setFilter( array( 'stripslashes', 'trim', 'clear' ) )->getRaw( 'arrCnb' ) );
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
				'template_id'=>empty( $this->data->filtered['template_id'] ),
				'category_id'=>empty( $this->data->filtered['category_id'] ),
				'url'=>empty( $this->data->filtered['url'] ),
				'title'=>empty( $this->data->filtered['title'] ), ) )
			->check() ) {
			$this->data->getErrors( $this->_errors['arrCnb'] );
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
		$this->data->setElements( array(
			'edited'=>time(),
			'flg_damas'=>(!empty( $object->getDataObject()->filtered['headlines_spot1'] )? $object->getDataObject()->filtered['headlines_spot1']:0),
			'flg_type'=>(empty( $this->data->filtered['flg_type'] )? 0:1),
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
		$_templates=new Project_Sites_Templates( Project_Sites::CNB );
		if ( !$_templates->siteLink( $this->data->filtered['id'], $this->data->filtered['template_id'] ) ) {
			return false;
		}

		// Сохранить контент в таблицу, для последующего удаления.
		$_content = new Project_Sites_Content( Project_Sites::CNB );
		$_content
			->withFlgFrom( Project_Sites_Content::$type['self'] )
			->withSourceIndex( Project_Content::$source['User\'s Content'][0]['flg_source'] )
			->withSiteId( $this->data->filtered['id'] )
			->setContent($this->_content)
			->set();

		$_opt=new Project_Options(  Project_Sites::CNB , $this->data->filtered['id'] );
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
		Project_Syndication_Sites::setOutside( $this->data->filtered['id'], Project_Sites::CNB, empty( $this->data->filtered['syndication'] ) ); // Syndication
		return true;
	}

	protected function upload() {
		if ( !$this->prepareSource() ) {
			return false;
		}
		$_connector=new Project_Sites_Connector();
		if( !$_connector
			->setSourceDir( $this->_dir )
			->setHttpUrl( $this->data->filtered['url'] )
			->setHost( $this->data->filtered['ftp_host'] )
			->setUser( $this->data->filtered['ftp_username'] )
			->setPassw( $this->data->filtered['ftp_password'] )
			->setRoot( $this->data->filtered['ftp_directory'] )
			->upload()){
			$_connector->getErrors( $this->_errors );
			return false;
		}
		return true;
	}

	public function prepareSource() {
		$this->_dir='Project_Sites_Type_Cnb@prepareSource';
		if ( !Zend_Registry::get( 'objUser' )->prepareTmpDir( $this->_dir ) ) {
			$this->_errors[] = 'Process Aborted. Can\'t create dir Project_Sites_Type_CNB@prepareSource';
			return false;
		}
		if ( !$this->getTemplate() ) {
			$this->_errors[] = 'Process Aborted. Can\'t get template';
			return false;
		}
		if ( !$this->patchFiles() ) {
			$this->_errors[] = 'Process Aborted. Can\'t patch files';
			return false;
		}
		if ( !$this->generateArticles() ) {
			$this->_errors[] = 'Process Aborted. Can\'t generate articles';
			return false;
		}
		return true;
	}

	public function getArchive(  Project_Sites $object  ){
		$this->data=new Core_Data( $object->getDataObject()->setFilter( array( 'stripslashes', 'trim', 'clear' ) )->getRaw( 'arrCnb' ) );
		$this->data->setFilter();
		$this->_optData=array();
		$this->_dir='Project_Sites_Type_Cnb@prepareSource';
		if ( !Zend_Registry::get( 'objUser' )->prepareTmpDir( $this->_dir ) ) {
			$this->_errors[] = 'Process Aborted. Can\'t create dir Project_Sites_Type_CNB@prepareSource';
			return false;
		}
		if ( !$this->getTemplate() ) {
			$this->_errors[] = 'Process Aborted. Can\'t get template';
			return false;
		}
		if ( !$this->patchFiles() ) {
			$this->_errors[] = 'Process Aborted. Can\'t patch files';
			return false;
		}
		if ( !$this->generateArticles() ) {
			$this->_errors[] = 'Process Aborted. Can\'t generate articles';
			return false;
		}
		return $this->_dir;
	}

	private function getTemplate() {
		$_template=new Project_Sites_Templates( Project_Sites::CNB );
		if ( !$_template->onlyOne()->withIds( $this->data->filtered['template_id'] )->getList( $_arrTemplate ) ) {
			return false;
		}
		return Core_Zip::getInstance()
			->setDir( $this->_dir )
			->extractZip( $_arrTemplate['path'].$_arrTemplate['filename'] );
	}

	private function patchFiles() {
		 // Шаблон не менялся, удаляем все файлы шаблона, и создаем только те которые меняются.
		$_dir = $this->_dir.'datas' . DIRECTORY_SEPARATOR;

		// dams
		$_strCode=Project_Options_GetCode::getDamsPhpCode( $this->_optData );
		$_strFile=file_get_contents( $_dir.'damscode.php' );
		$_arrFiles['damscode.php']=str_replace( '<damscode>', (empty( $_strCode )?'':$_strCode), $_strFile );
		// spots
		$_arrCode=Project_Options_GetCode::getSpotsCode( $this->_optData );
		
		// spot1
		$_strFile=file_get_contents( $_dir.'leftside.php' );
		if ( empty( $_arrCode['spot1'] ) ) {
			$_arrFiles['leftside.php']=str_replace( '<linkleftside>', '', $_strFile );
		} else {
			$_arrFiles['leftside.php']=str_replace( '<linkleftside>', $_arrCode['spot1'], $_strFile );
		}		
		
		// spot2
		$_strFile=file_get_contents( $_dir.'bestproducts.php' );
		if ( empty( $_arrCode['spot2'] ) ) { 
			$_arrFiles['bestproducts.php']=str_replace( '<adv1>', '', $_strFile );
		} else {
			$_arrFiles['bestproducts.php']=str_replace( '<adv1>', $_arrCode['spot2'], $_strFile );
		}
				
		// spot3
		$_strFile=file_get_contents( $_dir.'bestseller.php' );
		if ( empty( $_arrCode['spot3'] ) ) { 
			$_arrFiles['bestseller.php']=str_replace( '<adv2>', '', $_strFile );
		} else {
			$_arrFiles['bestseller.php']=str_replace( '<adv2>', $_arrCode['spot3'], $_strFile );
		}
		
		// for theindex.php
		// spot4
		$_strFile=file_get_contents( $_dir.'theindex.php' );
		if ( empty( $_arrCode['spot4'] ) ) { 
			$_strFile=str_replace( array( '<alttitle>', '<defaulttitle>','</defaulttitle>'), array('','',''), $_strFile );
		} else {
			$strposStart=stripos( $_strFile, '<defaulttitle>' );
			$strposEnd=stripos( $_strFile, '</defaulttitle>' );
			$_strDefaultCode=substr( $_strFile, $strposStart , $strposEnd - $strposStart + 15 );
			$_strFile=str_replace( array( '<alttitle>', $_strDefaultCode ), array( '', $_arrCode['spot4'] ), $_strFile );
		}
		// spot5
		if ( empty( $_arrCode['spot5'] ) ) { 
			$_strFile=str_replace( array( '<altdesc>', '<defaultdesc>','</defaultdesc>'), array('','',''), $_strFile );
		} else {
			$strposStart=stripos( $_strFile, '<defaultdesc>' );
			$strposEnd=stripos( $_strFile, '</defaultdesc>' );
			$_strDefaultCode=substr( $_strFile, $strposStart , $strposEnd - $strposStart + 14 );
			$_strFile=str_replace( array( '<altdesc>', $_strDefaultCode ), array( '', $_arrCode['spot5'] ), $_strFile );
		}	
		$_arrFiles['theindex.php']=$_strFile;	
		// for pages.php
		// spot4
		$_strFile=file_get_contents( $_dir.'pages.php' );
		if ( empty( $_arrCode['spot4'] ) ) { 
			$_strFile=str_replace( array( '<alttitle>', '<defaulttitle>','</defaulttitle>'), array('','',''), $_strFile );
		} else {
			$strposStart=stripos( $_strFile, '<defaulttitle>' );
			$strposEnd=stripos( $_strFile, '</defaulttitle>' );
			$_strDefaultCode=substr( $_strFile, $strposStart , $strposEnd - $strposStart + 15 );
			$_strFile=str_replace( array( '<alttitle>', $_strDefaultCode ), array( '', $_arrCode['spot4'] ), $_strFile );
		}
		// spot5
		if ( empty( $_arrCode['spot5'] ) ) { 
			$_strFile=str_replace( array( '<altdesc>', '<defaultdesc>','</defaultdesc>'), array('','',''), $_strFile );
		} else {
			$strposStart=stripos( $_strFile, '<defaultdesc>' );
			$strposEnd=stripos( $_strFile, '</defaultdesc>' );
			$_strDefaultCode=substr( $_strFile, $strposStart , $strposEnd - $strposStart + 14 );
			$_strFile=str_replace( array( '<altdesc>', $_strDefaultCode ), array( '', $_arrCode['spot5'] ), $_strFile );
		}
		$_arrFiles['pages.php']=$_strFile;
		// spot6
		$_strFile=file_get_contents( $_dir.'leaderboard.php' );
		if ( empty( $_arrCode['spot6'] ) ) { 
			$_arrFiles['leaderboard.php']=str_replace( array( '<adspot1>', '<defaultspot1>','</defaultspot1>'), array('','',''), $_strFile );
		} else {
			$strposStart=stripos( $_strFile, '<defaultspot1>' );
			$strposEnd=stripos( $_strFile, '</defaultspot1>' );
			$_strDefaultCode=substr( $_strFile, $strposStart , $strposEnd - $strposStart + 15 );
			$_arrFiles['leaderboard.php']=str_replace( array( '<adspot1>', $_strDefaultCode ), array( '', $_arrCode['spot6'] ), $_strFile );
		}		
		// spot7
		$_strFile=file_get_contents( $_dir.'square.php' );
		if ( empty( $_arrCode['spot7'] ) ) { 
			$_arrFiles['square.php']=str_replace( array( '<adspot2>', '<defaultspot2>','</defaultspot2>'), array('','',''), $_strFile );
		} else {
			$strposStart=stripos( $_strFile, '<defaultspot2>' );
			$strposEnd=stripos( $_strFile, '</defaultspot2>' );
			$_strDefaultCode=substr( $_strFile, $strposStart , $strposEnd - $strposStart + 15 );
			$_arrFiles['square.php']=str_replace( array( '<adspot2>', $_strDefaultCode ), array( '', $_arrCode['spot7'] ), $_strFile );
		}		
		// spot8
		$_strFile=file_get_contents( $_dir.'center.php' );
		if ( empty( $_arrCode['spot8'] ) ) { 
			$_arrFiles['center.php']=str_replace( '<adspot3>', '', $_strFile );
		} else {
			$_arrFiles['center.php']=str_replace( '<adspot3>', $_arrCode['spot8'], $_strFile );
		}
		
		// spot9
		$_strFile=file_get_contents( $_dir.'rightside.php' );
		if ( empty( $_arrCode['spot9'] ) ) { 
			$_strFile=str_replace( array( '<adspot4>', '<defaultspot4>','</defaultspot4>'), array('','',''), $_strFile );
		} else {
			$strposStart=stripos( $_strFile, '<defaultspot4>' );
			$strposEnd=stripos( $_strFile, '</defaultspot4>' );
			$_strDefaultCode=substr( $_strFile, $strposStart , $strposEnd - $strposStart + 15 );
			$_strFile=str_replace( array( '<adspot4>', $_strDefaultCode ), array( '', $_arrCode['spot9'] ), $_strFile );
		}			
		// spot10
		if ( empty( $_arrCode['spot10'] ) ) { 
			$_strFile=str_replace( '<adspot5>', '', $_strFile );
		} else {
			$_strFile=str_replace( '<adspot5>', $_arrCode['spot10'], $_strFile );
		}				
		$_arrFiles['rightside.php']=$_strFile;
		// config
		$_strFile=file_get_contents( $_dir.'config.php' );
		$profile=new Project_Sites_Profiles();
		$profile->get($_arrProfile, $this->data->filtered['profile_id'] );
		foreach( $_arrProfile as &$val ) {
			if( $val == 'NULL' ) {
				$val = "";
			}	
		}
		if( is_array($this->data->filtered['profile']) ){
			$_arrProfile=$this->data->filtered['profile'];
		}
		$_arrFiles['config.php']=str_replace( array(
			'$$$showgoogle$$$',
			'$$$showyahoo$$$',
			'$$$showsf$$$',
			'$$$SHOWCHITIKA$$$',
			'$$$showsubscribe$$$',
			'$$$showamazon$$$',
			'$$$showpartners$$$',
			'$$$showbestseller$$$',
			'$$$showbestproducts$$$',
			'$$$showcenter$$$',
			'$$$showright$$$',
			'$$$showsubmit$$$',
			'$$$numresults$$$',
			
			'$$$SUBFOLDER$$$',
			'$$$sitename$$$',
			'$$$keyword$$$',
			
			'$$$ADSENSEPUB$$$',
			'$$$ADSENSECHANNEL$$$',
			'$$$YAHOOPUB$$$',
			'$$$YAHOOCHANNEL$$$',
			'$$$CHITIKAPUB$$$',
			'$$$CHITIKACHANNEL$$$',
			'$$$CLICKBANKID$$$',
			'$$$SEARCHFEEDID$$$',
			'$$$SEARCHFEEDTRACK$$$',
			'$$$AMAZONCOUNTRY$$$',
			'$$$AMAZONID$$$',
			'$$$AMAZONNUM$$$',
			
			'$$$FIRST$$$',
			'$$$LAST$$$',
			'$$$EMAIL$$$',
			'$$$AUTORESPONDER$$$',
			'$$$URLOFINSTALL$$$',
		), array(
			$_arrProfile['show_google_ads'],
			$_arrProfile['show_yahoo_ads'],
			$_arrProfile['show_search_feed'],
			$_arrProfile['show_chitika'],
			$_arrProfile['show_subscribe'],
			$_arrProfile['show_amazon_ads'],
			$_arrProfile['show_parteners'],
			$_arrProfile['show_bestseller'],
			$_arrProfile['show_best_products'],
			$_arrProfile['show_centers'],
			$_arrProfile['show_right'],
			$_arrProfile['show_submit_article_form'],
			$_arrProfile['no_of_results'],

			'',
			$this->data->filtered['title'],
			$this->data->filtered['primary_keyword'],
			
			$_arrProfile['adsense_id'],
			$_arrProfile['adsense_channel'],
			$_arrProfile['yahoo_id'],
			$_arrProfile['yahoo_channel'],
			$_arrProfile['chitika_id'],
			$_arrProfile['chitika_channel'],
			$_arrProfile['clickbank_id'],
			$_arrProfile['search_feed_id'],
			$_arrProfile['search_feed_track_id'],
			$_arrProfile['amazon_country'],
			$_arrProfile['amazon_associates_id'],
			$_arrProfile['no_of_amazon_products'],
			
			$_arrProfile['first_name'],
			$_arrProfile['last_name'],
			$_arrProfile['email'],
			$_arrProfile['autoresponder_email'],
			$_arrProfile['url_of_landingpage'],
		), $_strFile );
		if( !empty($this->data->filtered['id'])&&($this->data->filtered['old_template_id']==$this->data->filtered['template_id']) ){
			$this->_dir='Project_Sites_Type_Cnb@prepareSource';
			Zend_Registry::get( 'objUser' )->prepareTmpDir( $this->_dir );
		}
		$_imgDir = $this->_dir.'datas' . DIRECTORY_SEPARATOR.'img'.DIRECTORY_SEPARATOR;
		if( !is_dir($_imgDir)){
			mkdir($_imgDir,0777,true);
		}
		if (!empty($_FILES['header']['tmp_name'])){
			copy($_FILES['header']['tmp_name'],$_imgDir.'header.jpg');
		}
		if (!empty($_FILES['report']['tmp_name'])){
			copy($_FILES['report']['tmp_name'],$_imgDir.'report-sm.jpg');
		}
		if (!empty($_FILES['thumb']['tmp_name'])){
			copy($_FILES['thumb']['tmp_name'],$_imgDir.'thumb1.jpg');
		}
		// сохраняем
		return Core_Files::setContentMass( $_arrFiles, $_dir );
	}

	protected function generateArticles(){
		$_strFiles=serialize(array(1));
		Core_Files::setContent( $_strFiles, $this->_dir.'articles-list.txt');
		return true;
	}

	protected function setLinks( $_sheduleId, $_strFilename ){
		self::$_lastUrls[] = array('shedule_id'=>$_sheduleId, 'url'=> $this->data->filtered['url'] . 'permalink.php?article=' . Core_Files::getFileName($_strFilename) .'.txt' );
	}

	public static function getLastUrls(){
		return self::$_lastUrls;
	}		
	
}
?>