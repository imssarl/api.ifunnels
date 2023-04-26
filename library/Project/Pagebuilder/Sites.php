<?php
/**
 * Подключение библиотек
 */
require_once Zend_Registry::get('config')->path->relative->library . 'Helper/directory_helper.php';
require_once Zend_Registry::get('config')->path->relative->library . 'SimpleHTMLDom/simple_html_dom.php';

class Project_Pagebuilder_Sites extends Core_Data_Storage{
	protected $_table='pb_sites';
	protected $_fields=array( 'id', 'user_id', 'sites_name', 'flg_template', 'custom_domain', 'sub_domain', 'sub_folder', 'home_page', 'sites_created_on', 'sites_lastupdate_on', 'ftp_type', 'ftp_server', 'ftp_user', 'ftp_password', 'ftp_path', 'ftp_port', 'ftp_ok', 'ftp_published', 'publish_date', 'global_css', 'remote_url', 'sites_trashed', 'viewmode', 'created_at', 'modified_at', 'sitethumb', 'settings', 'url', 'category_id', 'header_script', 'footer_script' );
	protected $_dir=false;

	public function beforeSet(){
		$this->_data->setFilter( array( 'clear' ) );
		if($this->_data->filtered['siteData']){
			$this->_data->setElements($this->_data->filtered['siteData']);
			$this->_data->setElement('sites_lastupdate_on', time());
		}
		
		if(!empty($this->_data->filtered['settings'])){
			$this->_data->setElement('settings', base64_encode(serialize($this->_data->filtered['settings'])));
		}
		
		$this->updateBaseData( $this->_data->filtered['global_css'] );
		$this->_data->setElement('global_css', $this->_data->filtered['global_css']);
		$this->updateBaseData( $this->_data->filtered['header_script'] );
		$this->_data->setElement('header_script', $this->_data->filtered['header_script']);
		$this->updateBaseData( $this->_data->filtered['footer_script'] );
		$this->_data->setElement('footer_script', $this->_data->filtered['footer_script']);
		
		$pages = new Project_Pagebuilder_Pages();

		if(!empty($this->_data->filtered['pages'])){
			/** Update the pages */
			foreach ($this->_data->filtered['pages'] as $page => $pageData) {
				/** Dealing with a changed page */
				if ($pageData['status'] == 'changed'){
					if (!isset($pageData['pageID']) || $pageData['pageID'] == 0){
						$pages->withIds($this->_data->filtered['sites_id'])->withPageName($page)->onlyOne()->getList($pageDataOld);
						$pageID = $pageDataOld[id];
					} else {
						$pageID = $pageData['pageID'];
					}
					$data = array(
						'id' => $pageID,
						'pages_name' => $page,
						'pages_timestamp' => time(),
						'pages_title' => $pageData['pageSettings']['title'],
						'pages_meta_keywords' => $pageData['pageSettings']['meta_keywords'],
						'pages_meta_description' => $pageData['pageSettings']['meta_description'],
						'pages_header_includes' => $pageData['pageSettings']['header_includes'],
						'pages_css' => $pageData['pageSettings']['page_css'],
						'pages_header_script' => $pageData['pageSettings']['header_script'],
						'pages_footer_script' => $pageData['pageSettings']['footer_script'],
						'google_fonts' => (isset($pageData['pageSettings']['google_fonts']))? json_encode($pageData['pageSettings']['google_fonts']) : ''
					);
					$pages->setEntered($data)->set();
				} elseif ($pageData['status'] == 'new') {
					$data = array(
						'sites_id' => $this->_data->filtered['siteData']['sites_id'],
						'pages_name' => $page,
						'pages_timestamp' => time(),
						'pages_title' => $pageData['pageSettings']['title'],
						'pages_meta_keywords' => $pageData['pageSettings']['meta_keywords'],
						'pages_meta_description' => $pageData['pageSettings']['meta_description'],
						'pages_header_includes' => $pageData['pageSettings']['header_includes'],
						'pages_header_script' => $pageData['pageSettings']['header_script'],
						'pages_footer_script' => $pageData['pageSettings']['footer_script'],
						'pages_css' => $pageData['pageSettings']['page_css']
					);
					$pages->setEntered($data)->set();
					$pages->getEntered( $pageID );
					$pageID = $pageID['id'];
				}

				/** Page done, onto the blocks */
				/** Push existing frames into revision */

				$frames = new Project_Pagebuilder_Frames();
				if( !empty( $pageID ) && !empty( $this->_data->filtered['id'] ) ){
					$frames->withPageId($pageID)->withSiteId($this->_data->filtered['id'])->onlyIds()->getList($arrBlocks);
				}
				if(!empty($arrBlocks)){
					$frames->withIds($arrBlocks)->del();
				}

				if (isset($pageData['blocks'])) {
					foreach ($pageData['blocks'] as $block) {
						$data = array(
							'pages_id'              => $pageID,
							'sites_id'              => $this->_data->filtered['id'],
							'position' 				=> $block['position'],
							'frames_content'        => $block['frameContent'],
							'frames_height'         => $block['frameHeight'],
							'frames_original_url'   => $block['originalUrl'],
							'frames_sandbox'        => ($block['sandbox'] == 'TRUE') ? 1 : 0,
							'frames_loaderfunction' => $block['loaderFunction'],
							'frames_timestamp'      => time(),
							'frames_global'         => (isset($block['frames_global']))? 1: 0,
						);
						$frames->setEntered($data)->set();
					}
				}

				/** Added popups */
				if (isset($pageData['popups'])) {
					foreach ($pageData['popups'] as $popup) {
						$data = array(
							'pages_id'              => $pageID,
							'sites_id'              => $this->_data->filtered['id'],
							'frames_content'        => $popup['frameContent'],
							'frames_height'         => $popup['frameHeight'],
							'frames_original_url'   => $popup['originalUrl'],
							'frames_sandbox'        => ($popup['sandbox'] == 'TRUE') ? 1 : 0,
							'frames_loaderfunction' => $popup['loaderFunction'],
							'frames_timestamp'      => time(),
							'frames_global'         => (isset($popup['frames_global']))? 1: 0,
							'frames_popup'          => (isset($popup['type'])) ? $popup['type']: 'entry',
							'frames_embeds'         => (isset($popup['frames_embeds']))? $popup['frames_embeds'] : '',
							'frames_settings'       => (isset($popup['additionalSettings'])) ? json_encode($popup['additionalSettings']) : ''
						);
	
						$frames->setEntered($data)->set();
					}
				}

				$screenshotUrl = ( ! empty( $_SERVER['HTTPS'] ) && 'off' !== strtolower( $_SERVER['HTTPS'] ) ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . '/ifunnels-studio/loadsinglepage/?id=' . $pageID;
				$screen = new Project_Pagebuilder_Screenshot();

				if( $page == 'index' ){
					$filename = 'sitethumb_' . $pageID . '.jpg';

					$screenshot = $screen->make_screenshot( $screenshotUrl, $filename, '520x440', Zend_Registry::get( 'config' )->path->absolute->pagebuilder . 'tmp/sitethumbs/' );
					if ( $screenshot !== false ){
						$this->_data->setElement('sitethumb', 'tmp/sitethumbs/' . $screenshot);
						$pages->setEntered( array( 'id' => $pageID, 'pagethumb' => 'tmp/sitethumbs/' . $screenshot ) )->set();
					}
				} else {
					$filename = 'pagethumb_' . $pageID . '.jpg';

					$screenshot = $screen->make_screenshot( $screenshotUrl, $filename, '520x440', Zend_Registry::get( 'config' )->path->absolute->pagebuilder . 'tmp/pagethumbs/' );
					if( $screenshot !== false ){
						$pages->setEntered( array( 'id' => $pageID, 'pagethumb' => 'tmp/pagethumbs/' . $screenshot ) )->set();
					}
				}
			}
		}
		return true;
	}

	protected $_isTemplate = false;
	public function isTemplate(){
		$this->_isTemplate = true;
		return $this;
	}

	protected $_withoutSort=false;
	public function withoutSort (){
		$this->_withoutSort=true;
		return $this;
	}

	private $_withVisitors = false;
	public function withVisitors() {
		$this->_withVisitors = true;
		return $this;
	}

	private $_withoutIds = false;
	public function withoutIds( $ids ) {
		if( ! empty( $ids ) ) {
			$this->_withoutIds = $ids;
		}

		return $this;
	}

	protected function assemblyQuery(){
		parent::assemblyQuery();

		if( $this->_isTemplate ){
			$this->_crawler->set_where( 'd.flg_template=1' );
		} else {
			$this->_crawler->set_where( 'd.flg_template=0' );
		}

		if( $this->_withoutSort ){
			$this->_crawler->q_order=array();
		}

		if( $this->_withoutIds ) {
			$this->_crawler->set_where( 'd.id NOT IN (' . Core_Sql::fixInjection( $this->_withoutIds ) . ')' );
		}
	}

	public function del(){
		$_id = $this->_withIds;
		$this->onlyOne()->getList($siteData);
		if($siteData['flg_template'] != 1 && !empty($siteData['settings'])){
			$obj=new Project_Placement();
			if ( $obj->withIds( $siteData['settings']['placement_id'] )->onlyOne()->getList( $this->_info )->checkEmpty() ) {
				$_hosting=new Project_Placement_Hosting();
				$_hosting->setHostingInfo( $siteData['settings'] )->delete();
				/*
				$_transport=new Project_Placement_Transport();
				$_transport
					->setInfo(  )
					->removeDir( $siteData['settings']['ftp_directory'] );
				*/
			}
		}
		/*
		$this->_withIds = $_id;
		$pages = new Project_Pagebuilder_Pages();
		$pages->withSiteId($this->_withIds)->onlyIds()->getList($arrPages);
		$pages->withIds($arrPages)->del();
		parent::del();
		*/
	}
	
	public function duplicateTempalate( $_intId=0 ){
		if( $_intId !== 0 ){
			$this->_withIds=$_intId;
			$this->createNew( $this->_withIds, true, true );
		}
		return $this;
	}
	
	public function duplicate( $_intId=0 ){
		if( $_intId !== 0 ){
			$this->_withIds=$_intId;
			$this->createNew( $this->_withIds, true, false );
		}
		return $this;
	}


	private function updateBaseData(&$_str){
		$_str=str_replace( array( '\r', '\n' ), "", $_str );
		$_str=str_replace( array( "\r", "\n" ), "", $_str );
		$_str=str_replace( '\t', "", $_str );
	}
	
	public function getList(&$mixRes){
		$_withVisitors = $this->_withVisitors;
		parent::getList( $mixRes );
		$this->_withVisitors = $_withVisitors;

		if( array_key_exists( 0, $mixRes ) ){
			foreach( $mixRes as &$_mixData ){
				if( ! empty( $_mixData['settings'] ) ){
					$_mixData['settings'] = unserialize( base64_decode( $_mixData['settings'] ) );
				}
				if( isset( $_mixData['global_css'] ) ){
					$this->updateBaseData( $_mixData['global_css'] );
					$this->updateBaseData( $_mixData['header_script'] );
					$this->updateBaseData( $_mixData['footer_script'] );
				}
			}
		} elseif ( !empty( $mixRes['settings'] ) ){
			$mixRes['settings']=unserialize( base64_decode( $mixRes['settings'] ) );
			$this->updateBaseData( $mixRes['global_css'] );
			$this->updateBaseData( $mixRes['header_script'] );
			$this->updateBaseData( $mixRes['footer_script'] );
		}

		if( $this->_withVisitors ) {
			$this->appendVisitors( $mixRes );
		}

		$this->init();
		return $this;
	}

	protected function init(){
		parent::init();
		$this->_isTemplate = false;
		$this->_withoutSort = false;
		$this->_withVisitors = false;
		$this->_withoutIds = false;
	}

	public function processFrameContent($frameContent){
		$doc = new DOMDocument;
		$doc->loadHTML($frameContent);
		$xpath = new DOMXpath($doc);

		foreach($xpath->query("//*[@data-hover]") as $element){
			$element->removeAttribute("data-hover");
		}

		foreach($xpath->query("//*[@data-selector]") as $element){
			$element->removeAttribute('data-selector');
		}
		foreach($xpath->query("//*[@draggable]") as $element){
			$element->removeAttribute('draggable');
		}

		foreach($xpath->query("//*[contains(@class, 'sb_hover')]") as $element){
			$element->setAttribute('class', str_replace('sb_hover', '', $element->getAttribute('class')));
		}

		foreach($xpath->query("//div[contains(@class, 'canvasElToolbar')]") as $element){
			$element->parentNode->removeChild($element);
		}

		foreach($xpath->query("//script[contains(@class, 'builder')]") as $element){
			$element->parentNode->removeChild($element);
		}

		foreach($xpath->query("//*[@data-parallax]") as $element){
			$oldCss = $element->getAttribute('style');
			$replaceWith = "background-image: none";
			$regex = '/(background-image: url\((["|\']?))(.+)(["|\']?\))/';
			$oldCss = preg_replace($regex, $replaceWith, $oldCss);
			$element->setAttribute('style', $oldCss);
		}

		foreach($xpath->query("//*[@href]") as $element){
			$element->setAttribute('href', str_replace('..', '/skin/pagebuilder/elements', $element->getAttribute('href')));
		}

		foreach($xpath->query("//*[@src]") as $element){
			$element->setAttribute('src', str_replace('..', '/skin/pagebuilder/elements', $element->getAttribute('src')));
		}

		foreach($xpath->query("//*[@style]") as $element){
			$element->setAttribute('style', str_replace('..', '/skin/pagebuilder/elements', $element->getAttribute('style')));
		}

		return $doc->saveHTML();
	}

	/**
	 * Creates a new, empty shell site
	 *
	 * @return  integer     $new_site_id;
	 */
	public function createNew($templateID, $_flgDuplicate=false, $_flgTemplate=false) {
		$user_id = Core_Users::$info['id'];
		$pages = new Project_Pagebuilder_Pages();
		$frames = new Project_Pagebuilder_Frames();

		// Get the page thumnail
		$siteData=array('sites_name'=>'');
		if ( $templateID ){
			$siteData = Core_Sql::getRecord('SELECT * FROM ' . $this->_table . ' WHERE id=' . Core_Sql::fixInjection( $templateID ) );
			$pagethumb = $siteData['sitethumb'];
		} else {
			$pagethumb = "";
		}

		/** Create site */
		$data = array(
			'sites_name'        => "My new site",
			'users_id'          => $user_id,
			'sitethumb'         => $pagethumb,
			'sites_created_on'  => time(),
			'created_at'        => date("Y-m-d H:i:s")
		);
		if( $_flgDuplicate === true ){
			$data['sites_name']="Duplicate #".$templateID;
			$data['flg_template']=0;
		}
		if( $_flgTemplate === true ){
			$data['sites_name']=$siteData['sites_name']." Duplicate #".$templateID;
			$data['flg_template']=1;
		}
		$this->setEntered($data)->set();
		$this->getEntered($site);

		$new_site_id = $site['id'];

		/** Create empty index page */
		if($templateID){
			$pages->withSiteId($templateID)->withOrder('id--dn')->getList($arrPages);
			foreach($arrPages as $page){
				$pageID = $page['id'];
				unset($page['id']);
				$page['sites_id'] = $new_site_id;
				$page['pages_template'] = 0;

				$pages->setEntered($page)->set();
				$pages->getEntered($pageData);
				// grab the frames
				$frames->withSiteId($templateID)->withPageId($pageID)->withOrder('position--dn')->getList($_arrFrames);

				foreach( $_arrFrames as $frame ){
					$data = array(
						'pages_id'              => $pageData['id'],
						'sites_id'              => $new_site_id,
						'position'				=> $frame['position'],
						'frames_content'        => $frame['frames_content'],
						'frames_height'         => $frame['frames_height'],
						'frames_original_url'   => $frame['frames_original_url'],
						'frames_sandbox'        => $frame['frames_sandbox'],
						'frames_loaderfunction' => $frame['frames_loaderfunction'],
						'frames_timestamp'      => time(),
						'created_at'            => date("Y-m-d H:i:s")
					);
					$frames->withoutDecode()->setEntered($data)->set();
				}
			}
		} else {
			$data = array(
				'sites_id'          => $new_site_id,
				'pages_name'        => 'index',
				'pagethumb'         => $pagethumb,
				'pages_timestamp'   => time(),
				'created_at'        => date("Y-m-d H:i:s")
			);
			$pages->setEntered($data)->set();
			$pages->getEntered($page);
		}

		return $new_site_id;
	}

	/**
	 * Takes a site ID and returns all the site data, or FALSE is the site doesn't exist
	 *
	 * @param   integer     $site_id
	 * @return  mixed       $siteArray/FALSE
	 */
	public function getSite($site_id){
		$this->withIds($site_id)->onlyOne()->getList($site);

		if (empty($site)){
			return FALSE;
		}

		$siteArray = array();
		$site['sites_id'] = $site['id'];
		$siteArray['site'] = $site;

		/** Get the pages + frames */
		$pages = new Project_Pagebuilder_Pages();
		$pages->withSiteId($site['id'])->withOrder('id--dn')->getList($arrPages);

		$pageFrames = array();
		foreach ($arrPages as $page){
			/** Get the frames for each page */
			$frames = new Project_Pagebuilder_Frames();
			$frames->withPageId($page['id'])->withOrder('position--dn')->getList($_arrFrames);
			foreach ($_arrFrames as $_key=>$frame){
				if( $frame['sites_id'] != $site['id'] ){
					unset( $_arrFrames[$_key] );
				}
			}
			$pageDetails = array();

			foreach ($_arrFrames as $key => $frame) {
				if( empty( $frame['frames_popup'] ) ) {
					$pageDetails['blocks'][] = $frame;
				} else {
					$pageDetails['popups'][] = $frame;
				}
			}

			$pageDetails['page_id'] = $page['id'];
			$pageDetails['pages_title'] = $page['pages_title'];
			$pageDetails['meta_description'] = $page['pages_meta_description'];
			$pageDetails['meta_keywords'] = $page['pages_meta_keywords'];
			$pageDetails['header_includes'] = $page['pages_header_includes'];
			$pageDetails['page_css'] = $page['pages_css'];
			$pageDetails['google_fonts'] = json_decode($page['google_fonts']);
			$pageDetails['header_script'] = $page['pages_header_script'];
			$pageDetails['footer_script'] = $page['pages_footer_script'];

			$pageFrames[$page['pages_name']] = $pageDetails;
		}

		$siteArray['pages'] = $pageFrames;

		/** Grab the assets folders as well */

		$folderContent = directory_map('elements', 2);
		$assetFolders = array();

		if (is_array($folderContent)){
			foreach ($folderContent as $key => $item){
				if (is_array($item)){
					array_push($assetFolders, $key);
				}
			}
		}

		$siteArray['assetFolders'] = $assetFolders;
		return $siteArray;
	}

	public function setDir($dir){
		$this->_dir = $dir;
		return $this;
	}

	/**
	 * Export site
	 * 
	 * @param integer $siteID
	 */
	public function export( $siteID, $url ) {
		if( empty( $siteID ) ) return false;
 
		$markup = new Project_Pagebuilder_Markup( array( 'url' => $url ) );
		mkdir($this->_dir . DIRECTORY_SEPARATOR . 'bundles', 0777);
		
		/** Getting site data */
		$this
			->withIds( $siteID )
			->onlyOwner()
			->onlyOne()
			->getList( $siteData );

		/** Getting pages data */
		$pages = new Project_Pagebuilder_Pages();
		$pages
			->withSiteId( $siteData['id'] )
			->withOrder( 'id--dn' )
			->getList( $pagesData );
	

		$frames = new Project_Pagebuilder_Frames();
			
		$_getPageNames = $_replacePageNames = array();
		foreach( $pagesData as $pageData ){
			$_getPageNames[] = $pageData['pages_name'].'.html';
			$_replacePageNames[] = $pageData['pages_name'].'.php';
		}
		
		foreach($pagesData as $key => $page){
			$files = array();
			$markup->setTitlePage( $page['pages_title'] );
			$markup->setMeta( array( 'description' => $page['pages_meta_description'], 'keywords' => $page['pages_meta_keywords'] ) );
			
			$markup->AddCss( array( Zend_Registry::get('config')->path->html->pagebuilder . 'elements/css/media.css' ) );
			$markup->AddJs( array(
				Zend_Registry::get('config')->path->html->pagebuilder . 'build/statistic.bundle.js',
				Zend_Registry::get('config')->path->html->pagebuilder . 'build/flipobj.bundle.js',
				Zend_Registry::get('config')->path->html->pagebuilder . 'build/quizobj.bundle.js',
				Zend_Registry::get('config')->path->html->pagebuilder . 'build/lazyload.bundle.js',
				Zend_Registry::get('config')->path->html->pagebuilder . 'build/effects.bundle.js',
				Zend_Registry::get('config')->path->html->pagebuilder . 'build/sticky.bundle.js'
			) );

			/** Getting blocks data */
			$frames
				->withSiteId( $siteData['id'] )
				->withPageId( $page['id'] )
				->withOrder( 'position--dn' )
				->getList( $pagesData[$key][$page['pages_name']] );

			foreach( $pagesData[$key][$page['pages_name']] as $block ){

				if( ! empty( $block['frames_popup'] ) ) {
					$markup->addModal( $block['frames_content'], array( 'popup' => $block['frames_popup'], 'popup_settings' => $block['frames_settings'], 'id' => $page['id'] ) );
				} else {
					$markup->addPartial( $block['frames_content'] );
				}

				/** Add custom script */
				$markup
					->addScript( 
						sprintf( 'var uid="%s"; var pbid = "%s"; var pagename = "%s";', Core_Users::$info['id'], $siteID, $page['pages_name'] ) 
					);

				/** Add custom user CSS/JS */
				$markup->CustomUserCssJs( array(
					'pages_header_includes' => $page['pages_header_includes'],
					'pages_css' => $page['pages_css'],
					'global_css' => $siteData['global_css'],
					'header_script' => $siteData['header_script'],
					'footer_script' => $siteData['footer_script'],
					'pages_header_script' => $page['pages_header_script'],
					'pages_footer_script' => $page['pages_footer_script']
				) );
			}

			/** Getting markup */
			$markup->returnHTML( $totalHTML, $files );


			if( ! empty( $files['image'] ) ){
				foreach( $files['image'] as &$image ){
					list( $image ) = explode( '?', $image );
					$info = new SplFileInfo( Zend_Registry::get('config')->path->absolute->root . $image );

					if( file_exists( $info->getRealPath() ) ) {
						copy( $info->getRealPath(), $this->_dir . 'bundles' . DIRECTORY_SEPARATOR . $info->getFilename() );
					}
				}
			}

			/**
			 * Added unique font files to folder
			 */
			if( ! empty( $files['font'] ) ){
				foreach( $files['font'] as $font ) {
					$info = new SplFileInfo( Zend_Registry::get('config')->path->absolute->root . $font );

					/** Add current file to archive */
					if ( file_exists( $info->getRealPath() ) ){
						copy( $info->getRealPath(), $this->_dir . 'bundles' . DIRECTORY_SEPARATOR . $info->getFilename() );
					}
				}
			}

			/**
			 * Added unique css files to folder
			 */
			if( ! empty( $files['css'] ) ) {
				foreach( $files['css'] as $cssUrl ){
					$info = new SplFileInfo(Zend_Registry::get('config')->path->absolute->root . $cssUrl);

					/** Add current file to archive */
					if( file_exists( $info->getRealPath() ) ) {
						copy( $info->getRealPath(), $this->_dir . 'bundles' . DIRECTORY_SEPARATOR . $info->getFilename() );

						// foreach ($fonts as $key => $font) {
						// 	$cssCode = file_get_contents($this->_dir . 'bundles' . DIRECTORY_SEPARATOR . $info->getFilename());
						// 	$cssCode = str_replace($key, pathinfo($font, PATHINFO_BASENAME), $cssCode);
						// 	file_put_contents($this->_dir . 'bundles' . DIRECTORY_SEPARATOR . $info->getFilename(), $cssCode);
						// }
					}
				}
			}

			/** 
			 * add items in the $jsUrls to the ZIP  
			 */
			if ( ! empty( $files['js'] ) > 0){
				foreach( $files['js'] as $jsUrl ){
					$info = new SplFileInfo(Zend_Registry::get('config')->path->absolute->root . $jsUrl);

					/** Add current file to archive */
					if( file_exists( $info->getRealPath() ) ) {
						copy( $info->getRealPath(), $this->_dir . 'bundles' . DIRECTORY_SEPARATOR . $info->getFilename() );
					}
				}
			}
			
			/** Remove technical tags */
			$totalHTML = str_replace(
				array(
					'data-container="button"',
					'data-container="divider"',
					'data-container="form"',
					'data-component="heading"',
					'data-component="icon"',
					'data-component="image"',
					'data-component="text"',
					'data-component="video"',
					'data-component="list"',
					'data-component="grid"',
					'contenteditable="true"',
					'data-component="code"',
					'sb_open'
				), 
				'', 
				$totalHTML 
			);
			
			/** Replace pages name */
			$totalHTML = str_replace( $_getPageNames, $_replacePageNames, $totalHTML );

			$template = file_get_contents( Zend_Registry::get('config')->path->absolute->pagebuilder . 'template.php' );
			$template = str_replace( 
				array( '[%userid%]', '[%html%]', '[%domain%]' ), 
				array( Core_Users::$info['id'], $totalHTML, Zend_Registry::get( 'config' )->engine->project_domain ), 
				$template 
			);

			file_put_contents( $this->_dir . $page['pages_name'] . '.php', $template );
		}

		/** Copy file .htaccess to folder */
		copy( Zend_Registry::get('config')->path->absolute->pagebuilder . 'htaccess' . DIRECTORY_SEPARATOR . '.htaccess', $this->_dir . '.htaccess' );
	}

	/**
	 * Save template
	 */
	public function saveTemplate( $mixData ){
		$pages = new Project_Pagebuilder_Pages();
		$frames = new Project_Pagebuilder_Frames();
		if( empty( $mixData['pages'] ) ) return false;

		$this->setEntered( array( 'category_id' => $mixData['categoryID'], 'id' => $mixData['templateID'] ) )->set();
		/**
		 * Обновление/Создание страницы для шаблона и запись добавленных блоков в таблицу
		 */

		foreach( $mixData['pages'] as $page => $data ){
			if( $data['status'] == 'changed' ){
				$pages->setEntered( array(
					'pages_name' 			 => $page,
					'pages_title' 			 => $data['pageSettings']['title'],
					'pages_meta_description' => $data['pageSettings']['meta_description'],
					'pages_meta_keywords' 	 => $data['pageSettings']['meta_keywords'],
					'pages_header_includes'  => $data['pageSettings']['header_includes'],
					'pages_css' 			 => $data['pageSettings']['page_css'],
					'id' 					 => $data['pageID']
				) )->set();
				$pageID = $data['pageID'];
			} else {
				$pages->setEntered( array(
					'sites_id' 				=> $mixData['templateID'],
					'pages_name' 			=> $page,
					'pages_timestamp' 		=> time(),
					'pages_title' 			=> $data['pageSettings']['title'],
					'pages_meta_description'=> $data['pageSettings']['meta_description'],
					'pages_meta_keywords' 	=> $data['pageSettings']['meta_keywords'],
					'pages_header_includes' => $data['pageSettings']['header_includes'],
					'pages_css' 			=> $data['pageSettings']['page_css'],
					'pages_template' 		=> 1,
					'created_at'			=> date( "Y-m-d H:i:s" )
				) )->set();

				$pages->getEntered( $pageData );
				$pageID = $pageData['id'];
			}

			if( ! empty( $data['blocks'] ) ){
				if( ! empty( $pageID ) && ! empty( $mixData['templateID'] ) ){
					$frames
						->withPageId( $pageID )
						->withSiteId( $mixData['templateID'] )
						->onlyIds()
						->getList( $arrBlocks );
				}
				if( ! empty( $arrBlocks ) ){
					$frames->withIds( $arrBlocks )->del();
				}
	
				foreach( $data['blocks'] as $block ){
					$dataBlock = array(
						'pages_id'              => $pageID,
						'sites_id'              => $mixData['templateID'],
						'position' 				=> $block['position'],
						'frames_content'        => $block['frameContent'],
						'frames_height'         => $block['frameHeight'],
						'frames_original_url'   => $block['originalUrl'],
						'frames_sandbox'        => ( $block['sandbox'] == 'TRUE' ) ? 1 : 0,
						'frames_loaderfunction' => $block['loaderFunction'],
						'frames_timestamp'      => time(),
						'frames_global'         => ( isset( $block['frames_global'] ) ) ? 1: 0,
					);
					$frames->setEntered( $dataBlock )->set();
				}
			}


			$screenshotUrl = ( ! empty( $_SERVER['HTTPS'] ) && 'off' !== strtolower( $_SERVER['HTTPS'] ) ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . '/ifunnels-studio/loadsinglepage/?id=' . $pageID;
			$screen = new Project_Pagebuilder_Screenshot();

			if( $page == 'index' ){
				$filename = 'template_' . $pageID . '.jpg';

				$screenshot = $screen->make_screenshot( $screenshotUrl, $filename, '520x440', Zend_Registry::get( 'config' )->path->absolute->pagebuilder . 'tmp/sitethumbs/' );
				if ( $screenshot !== false ){
					$this->setEntered( array( 'sitethumb' => 'tmp/sitethumbs/' . $screenshot, 'id' => $mixData['templateID'] ) )->set();
					$pages->setEntered( array( 'id' => $pageID, 'pagethumb' => 'tmp/sitethumbs/' . $screenshot ) )->set();
				}
			} else {
				$filename = 'pagethumb_' . $pageID . '.jpg';

				$screenshot = $screen->make_screenshot( $screenshotUrl, $filename, '520x440', Zend_Registry::get( 'config' )->path->absolute->pagebuilder . 'tmp/pagethumbs/' );
				if( $screenshot !== false ){
					$pages->setEntered( array( 'id' => $pageID, 'pagethumb' => 'tmp/pagethumbs/' . $screenshot ) )->set();
				}
			}
		}

		return $mixData['templateID'];
	}

	/**
	 * Save site as Template
	 */
	public function saveAsTemplate( $site_id ) {
		$siteData = $pagesData = $framesData = array();

		$this
			->withIds( $site_id )
			->onlyOne()
			->getList( $siteData );

		if( ! empty( $siteData ) ) {
			unset( $siteData['id'] );
			$siteData['flg_template'] = 1;
			$siteData['sites_name'] = 'Template created from site #' . $site_id;

			$pages = new Project_Pagebuilder_Pages();
			$pages->withSiteId( $site_id )->getList( $pagesData );

			$frames = new Project_Pagebuilder_Frames();

			if( ! empty( $pagesData ) ) {
				$pagesData = array_map( function( $page ) use ( $site_id, $frames, &$framesData ) {
					$frames
						->withSiteId( $site_id )
						->withPageId( $page['id'] )
						->getList( $framesData[$page['id']] );

					$page['sites_id'] = null;

					return $page;
				}, $pagesData );
			}

			if( $this->setEntered( $siteData )->set() ) {
				$this->getEntered( $siteData );

				if( ! empty( $pagesData ) ) {
					foreach( $pagesData as $page ) {
						$page_id = $page['id'];
						unset( $page['id'] );
						$page['sites_id'] = $siteData['id'];

						if( $pages->setEntered( $page )->set() ) {
							$pages->getEntered( $page );

							if( ! empty( $framesData[$page_id] ) ) {
								foreach( $framesData[$page_id] as $frame ) {
									unset( $frame['id'] );
									$frame['pages_id'] = $page['id'];
									$frame['sites_id'] = $siteData['id'];

									$frames
										->withoutDecode()
										->setEntered( $frame )
										->set();
								}
							}
						}
					}
				}
			}
			
			return true;
		}

		return false;
	}

	private function appendVisitors( &$data ) {
		$pb_ids = array();

		if( array_key_exists( 0, $data ) ) {
			foreach( $data as &$rec ) {
				if( ! empty( $rec['url'] ) ) {
					$pb_ids[] = $rec['id'];
				} else {
					$rec['visitors'] = 0;
				}
			}
		} else {
			if( ! empty( $data['url'] ) ){
				$pb_ids[] = $data['id'];
			} else {
				$data['visitors'] = 0;
			}
		}

		if( ! empty( $pb_ids ) ) {
			$_table = 'pb_view_' . Core_Users::$info['id'];
			$_arrData = array();

			try {
				Core_Sql::setConnectToServer( 'lpb.tracker' );
				$_arrData = Core_Sql::getKeyVal('SELECT v.pb_id, COUNT(*) as visitors  FROM ' . $_table . ' v WHERE pb_id IN (' . Core_Sql::fixInjection( $pb_ids ) . ') GROUP BY v.pb_id' );
				Core_Sql::renewalConnectFromCashe();
			} catch( Exception $e ){
				Core_Sql::renewalConnectFromCashe();
			}

			if( ! empty( $_arrData ) ) {
				if( array_key_exists( 0, $data ) ) {
					foreach( $data as &$rec ) {
						if( ! empty( $rec['url'] ) && isset( $_arrData[ $rec['id'] ] ) ) {
							$rec['visitors'] = (int)$_arrData[ $rec['id'] ];
						} else {
							$rec['visitors'] = 0;
						}
					}
				} else {
					if( ! empty( $data['url'] ) && isset( $_arrData[ $data['id'] ] ) ){
						$data['visitors'] = (int)$_arrData[ $data['id'] ];
					} else {
						$data['visitors'] = 0;
					}
				}
			}
		}
	}
}