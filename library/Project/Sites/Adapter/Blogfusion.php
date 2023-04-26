<?php
// ->setSite( siteId )->setContent( $arrContent )->deleteContent();
class Project_Sites_Adapter_Blogfusion implements Core_Singleton_Interface, Project_Sites_Adapter_Interface {

	private static $_instance=NULL;

	public static function getInstance(){
		if ( self::$_instance==NULL ) {
			self::$_instance=new self();
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
			// error todo
		}
		$this->_siteId=$_intId;
		$_site=new Project_Sites( Project_Sites::BF );
		if ( !$_site->onlyOne()->withIds( $this->_siteId )->getList( $_arrSite )->checkEmpty() ) {
			return $this;
		}
		$this->_data=new Core_Data( $_arrSite );
		$this->_data->setFilter( array( 'trim', 'clear' ) );
		return $this;
	}

	public $withOrder='edited--up';

	public $table='bf_blogs';

	protected $_fields=array( 
		'id', 'user_id', 'category_id', 'flg_type', 'flg_status', 'flg_settings', 'title', 'url', 
		'ftp_directory', 'db_host', 'db_name', 'db_username', 'db_password', 'db_tableprefix',
		'dashboad_username', 'dashboad_password', 'flg_blogroll_links', 'flg_summary', 'flg_comment_status', 'flg_comment_moderated', 'flg_comment_notification', 
		'flg_ping_status', 'flg_ping_newpost', 'flg_permalink', 'post_perpage', 'post_per_rss', 'version', 'admin_email', 'blogtag_line', 'pingsite_list', 
		'catedit', 'edited', 'added','prop_settings', 'placement_id'
	);

	public function get( &$arrRes, $_arrSite=array() ) {
		$arrRes=$_arrSite;
		$_plugin=new Project_Wpress_Plugins();
		$_plugin->onlyIds()->onlySiteId( $_arrSite['id'] )->getList( $arrRes['plugins'] );
		$_themes=new Project_Wpress_Theme();
		$_themes->onlyIds()->onlySiteId( $_arrSite['id'] )->getList( $arrRes['theme'] );
		$arrRes['prop_settings']=Core_String::json2php( $_arrSite['prop_settings'] );
		$arrRes['syndication']=Project_Syndication_Sites::isSyndicated( $_arrSite['id'], Project_Sites::BF ); // syndication
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

	public function copyBlog( Project_Sites $object, $_desctData ){
		$this->_data=new Core_Data( $_desctData );
		$this->_data->setFilter();
		$_placement=new Project_Placement();
		if( !$_placement->withIds($this->_data->filtered['placement_id'])->getDomen( $strDomen )->isRemote() ){
			$this->_data->setElement('ftp_directory',($this->_data->filtered['ftp_root']==1)?'/':'/'.trim($this->_data->filtered['ftp_directory'],'/').'/');
			$this->_data->setElement('url','http://'.$strDomen.$this->_data->filtered['ftp_directory']);
		} else {
			if ( !$this->_error->setData( $this->_data )->setValidators( array(
				'db_host'=>$this->_error->getValidator( 'Zend_Validate_NotEmpty' ),
				'db_name'=>$this->_error->getValidator( 'Zend_Validate_NotEmpty' ),
				'db_username'=>$this->_error->getValidator( 'Zend_Validate_NotEmpty' ),
				'db_password'=>$this->_error->getValidator( 'Zend_Validate_NotEmpty' ),
			) )->isValid() ) {
				return false;
			}
		}
		if ( !$this->_error->setData( $this->_data->setFilter( array( 'trim', 'clear' ) ) )->setValidators( array(
			'placement_id'=>$this->_error->getValidator( 'Zend_Validate_NotEmpty' ),
			'dashboad_username'=>$this->_error->getValidator( 'Zend_Validate_NotEmpty' ),
			'dashboad_password'=>$this->_error->getValidator( 'Zend_Validate_NotEmpty' ),
			'url'=>$this->_error->getValidator( 'Zend_Validate_NotEmpty' ),
			'title'=>$this->_error->getValidator( 'Zend_Validate_NotEmpty' ),
		) )->isValid() ) {
			return false;
		}
		set_time_limit(0);
		ignore_user_abort(true);
		$data=$object->getDataObject()->setFilter( array( 'stripslashes', 'trim', 'clear' ) );
		$_clone=new Project_Wpress_Connector_Clone();
		$_clone->setClon( $data )->setDestination( $this->_data )->init();
		if (!$_clone->prepareServer() ){
			return false;
		}
		if( !$_clone->setConfigCloner() ){
			return false;
		}
		if ( !$_clone->uploadMutator() ){
			return false;
		}
		if ( !$_clone->startCloner() ){
			return false;
		}
		$_arrBlog=array_merge($data->setMask( $this->_fields )->getValid(),$this->_data->filtered);
		unset($_arrBlog['id']);
		$_data=new Core_Data( $_arrBlog );
		$_data->setFilter();
		Core_Sql::reconnect();
		$_data->setElement( 'id', Core_Sql::setInsertUpdate( $this->table, $_data->setMask( $this->_fields )->getValid() ));
		// импортируем контент
		$_import=new Project_Sites_Adapter_Blogfusion_Import( $_data );
		return $_import->setParts( array( 'pages', 'posts', 'cats', 'opt' ) )->start();
	}

	public function import( Project_Sites $object ) {
		$this->_data=new Core_Data( $object->getDataObject()->setFilter( array( 'stripslashes', 'trim', 'clear' ) )->getRaw( 'arrBlog' ) );
		if ( !$this->_error->setData( $this->_data->setFilter( array( 'trim', 'clear' ) ) )->setValidators( array(
			'placement_id'=>$this->_error->getValidator( 'Zend_Validate_NotEmpty' ),
			'dashboad_username'=>$this->_error->getValidator( 'Zend_Validate_NotEmpty' ),
			'dashboad_password'=>$this->_error->getValidator( 'Zend_Validate_NotEmpty' ),
			'ftp_directory'=>$this->_error->getValidator( 'Zend_Validate_NotEmpty' ),
			'category_id'=>$this->_error->getValidator( 'Zend_Validate_NotEmpty' ),
			'url'=>$this->_error->getValidator( 'Zend_Validate_NotEmpty' ),
			'title'=>$this->_error->getValidator( 'Zend_Validate_NotEmpty' ),
			'db_host'=>$this->_error->getValidator( 'Zend_Validate_NotEmpty' ),
			'db_name'=>$this->_error->getValidator( 'Zend_Validate_NotEmpty' ),
			'db_username'=>$this->_error->getValidator( 'Zend_Validate_NotEmpty' ),
			'db_password'=>$this->_error->getValidator( 'Zend_Validate_NotEmpty' ),
		) )->isValid() ) {
			return false;
		}
		$this->correctUrl();
		$this->_data->setElements( array(
			'user_id'=>Core_Users::$info['id'],
			'flg_type'=>1,
			'flg_status'=>1,
			'added'=>time(),
		) );
		$this->_data->setElement( 'id', Core_Sql::setInsertUpdate( $this->table, $this->_data->setMask( $this->_fields )->getValid() ) );
		$_import=new Project_Sites_Adapter_Blogfusion_Import( $this->_data );
		if( !$_import->setParts()->start() ){
			return $this->_error->setError('Import error');
		}
		Project_Syndication_Sites::setOutside( $this->_data->filtered['id'], Project_Sites::BF, empty( $this->_data->filtered['syndication'] ) ); // Syndication
		return true;
	}

	private $_blogCreate=false;

	private function checkProprietary( Project_Sites $object ) {
		if ( empty( $this->_data->filtered['id'] ) ) {
			$this->_data->setElement( 'check_prop', '0' );
			return;
		}
		$object->onlyOne()->withIds( $this->_data->filtered['id'] )->getList( $_blog );
		$this->_data->setElement( 'check_prop', (
			$_blog['prop_settings']==Zend_Registry::get( 'CachedCoreString' )->php2json( $this->_data->filtered['prop_settings'] )&&
			$_blog['theme_id']==$this->_data->filtered['theme_id']
		)? '1':'0' );
		$this->_data->setFilter( array( 'clear' ) ); // это непонятно зачем - разобраться TODO!!! 14.06.2012
	}

	public function set( Project_Sites $object ) {
		$this->_data=new Core_Data( $object->getDataObject()->setFilter( array( 'stripslashes', 'trim', 'clear' ) )->getRaw( 'arrBlog' ) );
		$this->_data->setFilter( array( 'trim', 'clear' ) );
		if ( !$this->setProprietaryFiles() ) {
			return false;
		}
		$_placement=new Project_Placement();
		if( !$_placement->withIds($this->_data->filtered['placement_id'])->getDomen( $strDomen )->isRemote() ){
			$this->_data->setElement('ftp_directory',($this->_data->filtered['ftp_root']==1)?'/':'/'.trim($this->_data->filtered['ftp_directory'],'/').'/');
			$this->_data->setElement('url','http://'.$strDomen.$this->_data->filtered['ftp_directory']);
		} else {
			if ( !$this->_error->setData( $this->_data )->setValidators( array(
				'db_host'=>$this->_error->getValidator( 'Zend_Validate_NotEmpty' ),
				'db_name'=>$this->_error->getValidator( 'Zend_Validate_NotEmpty' ),
				'db_username'=>$this->_error->getValidator( 'Zend_Validate_NotEmpty' ),
				'db_password'=>$this->_error->getValidator( 'Zend_Validate_NotEmpty' ),
			) )->isValid() ) {
				return false;
			}
		}
		if ( !$this->_error->setData( $this->_data )->setValidators( array(
			'placement_id'=>$this->_error->getValidator( 'Zend_Validate_NotEmpty' ),
			'ftp_directory'=>$this->_error->getValidator( 'Zend_Validate_NotEmpty' )
				->setMessage("Value is required and can't be empty. Click 'browse' link and select the folder, where your site should be installed."),
			'title'=>$this->_error->getValidator( 'Zend_Validate_NotEmpty' ),
			'url'=>$this->_error->getValidator( 'Zend_Validate_NotEmpty' ),
			'dashboad_username'=>$this->_error->getValidator( 'Zend_Validate_NotEmpty' ),
			'dashboad_password'=>$this->_error->getValidator( 'Zend_Validate_NotEmpty' ),
		) )->isValid() ) {
			return false;
		}
		$this->correctUrl();
		if ( empty( $this->_data->filtered['id'] ) ) {
			$this->_data->setElements( array(
				'user_id'=>$this->_userId,
				'added'=>time(),
			) );
			$this->_blogCreate=true;
		}
		$this->_data->setElements( array(
			'flg_settings'=>( empty( $this->_data->filtered['flg_settings'] )? 0:1 ),
			'edited'=>time(),
		) );
		$this->checkProprietary( $object );
		return $this->upload();
	}

	private function setProprietaryFiles() {
		if ( $this->_data->filtered['files']['header']['size']==0&&$this->_data->filtered['files']['banner']['size']==0 ) {
			return true;
		}
		$_file=new Project_Files_Blogfusion( 'blogfusion_prop_template' );
		// заливаем хедер
		if ( !$_file->setEntered()
				->setEnteredFile( $this->_data->filtered['files']['header'] )
				->setTmp()
				->setMediaType( Core_Files_Info::$mediaType['images'] )
				->set() ){
			return $this->_error->setError( 'No valid propriarity template Header image' );
		}
		$_file->getEntered( $_arrHeader );
		// заливаем баннер
		if ( !$_file->setEntered()
				->setEnteredFile( $this->_data->filtered['files']['banner'] )
				->setTmp()
				->setMediaType( Core_Files_Info::$mediaType['images'] )
				->set() ){
			return $this->_error->setError( 'No valid propriarity template Banner image' );
		}
		$_file->getEntered( $_arrBanner );
		$this->_data->setElement( 'prop_settings', array(
			'header'=>$_arrHeader,
			'banner'=>$_arrBanner,
		) );
		return true;
	}

	// импортируем контент только при создании блога
	private function goImport() {
		if ( !$this->_blogCreate ) {
			return true;
		}
		$_import=new Project_Sites_Adapter_Blogfusion_Import( $this->_data );
		return $_import->setParts( array( 'pages', 'posts', 'cats', 'opt' ) )->start();
	}

	protected function afterUpload() {
		Core_Sql::reconnect();
		$this->_data->setElement( 'prop_settings', Zend_Registry::get( 'CachedCoreString' )->php2json($this->_data->filtered['prop_settings']) );
		$this->_data->setElement( 'id', Core_Sql::setInsertUpdate( $this->table, $this->_data->setMask( $this->_fields )->getValid() ) );
		if ( empty( $this->_data->filtered['id'] ) ) {
			return false;
		}
		$this->_siteId=$this->_data->filtered['id'];
		// добавление в Syndication
		Project_Syndication_Sites::setOutside( $this->_data->filtered['id'], Project_Sites::BF, empty( $this->_data->filtered['syndication'] ) );
		// линкуем тему
		$themes=new Project_Wpress_Theme();
		if ( !$themes->blogLink( $this->_data->filtered['id'], $this->_data->filtered['theme_id'] ) ) {
			return false;
		}
		// линкуем плагины
		$plugins=new Project_Wpress_Plugins();
		if ( !$plugins->blogLink( $this->_data->filtered['id'], $this->_data->filtered['plugins'] ) ) {
			return false;
		}
		// подтверждаем что файлы для баннеров не удаляем
		if ( !empty( $this->_data->filtered['prop_settings']['header']['id'] ) ) {
			$_file=new Project_Files_Blogfusion( 'blogfusion_prop_template' );
			$_file->withIds(array($_POST['arrBlog']['prop_settings']['header']['id']))
				->onlyDeleted()
				->setExists();
		}
		if ( !empty( $this->_data->filtered['prop_settings']['banner']['id'] ) ) {
			$_file=new Project_Files_Blogfusion( 'blogfusion_prop_template' );
			$_file->withIds(array($_POST['arrBlog']['prop_settings']['banner']['id']))
				->onlyDeleted()
				->setExists();
		}
		// импортируем контент
		if ( !$this->goImport() ) {
			return false;
		}
		// Письмо пользователю при создании блога.
		Project_Wpress_Notification::createWP( $this->_data );
		// Сохранить контент в таблицу, для последующего удаления.
		$_content=new Project_Sites_Content( Project_Sites::BF );
		return $_content
			->withFlgFrom( Project_Sites_Content::$type['self'] )
			->withSourceIndex( Project_Content::$source['User\'s Content'][0]['flg_source'] )
			->withSiteId( $this->_siteId )
			->setContent( $this->_content )
			->set();
	}

	public function upload() {
		if( empty($this->_data->filtered) ){
			return false;
		}
		set_time_limit( 0 );
		ignore_user_abort( true );
		$this->_transport=new Project_Placement_Transport();
		if ( !$this->_transport->setInfo( $this->_data->filtered )->setDb( $this->_data ) ) {
			return false;
		}
		if ( !$this->prepareSource() ) {
			return false;
		}
		if ( !$this->_transport
			->setSourceDir( $this->_mutatorDir.'blogfusion.zip' )
			->placeAndBreakConnect() ) {
			return false;
		}
		// инсталлируем
		if ( !Core_Curl::getResult( $_strRes, $this->_data->filtered['url'].'cnm-install.php' ) ) {
			return $this->_error->setError( 'no respond '.$this->_data->filtered['url'].'cnm-install.php' );
		}
		// чистим
		if ( !Core_Curl::getResult( $_strRes, $this->_data->filtered['url'].'cnm-clean.php' ) ) {
			return $this->_error->setError( 'no respond '.$this->_data->filtered['url'].'cnm-clean.php' );
		}
		return $this->afterUpload();
	}

	private function prepareSource() {
		$this->_mutatorDir='Project_Sites_Adapter_Blogfusion@prepareSource';
		if ( !Zend_Registry::get( 'objUser' )->prepareTmpDir( $this->_mutatorDir ) ) {
			return false;
		}
		$_createSrcDir=Zend_Registry::get( 'config' )->path->absolute->user_files.'blogfusion'.DIRECTORY_SEPARATOR.'create'.DIRECTORY_SEPARATOR;
		// копируем архив c текущей версией wp только при создании блога
		if ( empty( $this->_data->filtered['id'] ) ) {
			$lock=new Core_Media_Lock( 'wordpress.zip' );
			$lock->whileLocked();
			$lock->lock();
			copy( $_createSrcDir.'wordpress.zip', $this->_mutatorDir.'blogfusion.zip' );
			$lock->unLock();
		}
		// разархивируем структуру для мутатора
		if ( !Core_Zip::getInstance()->setDir( $this->_mutatorDir )->extractZip( $_createSrcDir.'mutators.zip' ) ) {
			return false;
		}
		if ( !$this->extractTheme() ) {
			return false;
		}
		$this->extractPlugins();
		$_arrFiles=array();
		$this->getConfigCode( $_arrFiles['wp-config.php'] );
		$this->getHtaccessCode( $_arrFiles['.htaccess'] );
		$this->getInstallerCode( $_arrFiles['cnm-install.php'] );
		$this->getGarbageCollector( $_arrFiles['cnm-clean.php'] );
		if ( !Core_Files::setContentMass( $_arrFiles, $this->_mutatorDir.'wordpress'.DIRECTORY_SEPARATOR ) ) {
			return false;
		}
		// добавляем мутатор в blogfusion.zip
		if ( true!==Core_Zip::getInstance()->open( $this->_mutatorDir.'blogfusion.zip' ) ) {
			return false;
		}
		return Core_Zip::getInstance()->addDirAndClose( $this->_mutatorDir.'wordpress' );
	}

	// разархивируем тему
	private function extractTheme() {
		if ( empty( $this->_data->filtered['theme_id'] ) ) {
			// если тема в данных не указана то это скорее всего импортированный блог
			// в этом случае отсутствие темы не является ошибкой
			return !empty( $this->_data->filtered['id'] );
		}
		if ( $this->_data->filtered['check_prop']=='1' ) {
			// если тема не изменилась, то мы её не перезаливаем
			return true;
		}
		$themes=new Project_Wpress_Theme();
		if ( !$themes->onlyOne()->withIds( $this->_data->filtered['theme_id'] )->getList( $this->_wpTheme ) ) {
			return false;
		}
		$this->_wpTheme['name']=Core_Files::getFileName( $this->_wpTheme['filename'] );
		$this->_wpTheme['curdir']=$this->_mutatorDir.'wordpress'.DIRECTORY_SEPARATOR.'wp-content'.DIRECTORY_SEPARATOR.'themes'.DIRECTORY_SEPARATOR;
		if ( !Core_Zip::getInstance()->setDir( $this->_wpTheme['curdir'] )->extractZip( $this->_wpTheme['path'].$this->_wpTheme['filename'] ) ) {
			return false;
		}
		Core_Files::dirScan($_arr, $this->_wpTheme['curdir']);
		foreach ( $_arr as $_strDir=>$_arrFiles ) {
			if (in_array( 'style.css', $_arrFiles )){
				$_name=Core_Files::getBaseName($_strDir);
				if ( $_name != $this->_wpTheme['name']){
					$this->_wpTheme['parent']=$_name;
				}
			}
		}
		if ( empty( $this->_wpTheme['flg_prop'] ) ) {
			return true;
		}
		$arrFiles=array();
		// настройка проприарити шаблона
		$this->_wpTheme['curdir']=$this->_wpTheme['curdir'].$this->_wpTheme['name'].DIRECTORY_SEPARATOR; // папка где хранится устанавливаемая тема
		if ( !$this->propHeaderGraphics( $arrFiles['style.css'] )||
			!$this->propBelowHeaderAndNavibar( $arrFiles['topbanner.php'] )||
			!$this->propSidebar( $arrFiles['l_sidebar.php'] )||
			!$this->propLinks( $arrFiles['header.php'] ) ) {
			return false;
		}
		return Core_Files::setContentMass( $arrFiles, $this->_wpTheme['curdir'] );
	}

	// Header graphics
	private function propHeaderGraphics( &$strFile ) {
		$strFile='';
		if ( !Core_Files::getContent( $strFile, $this->_wpTheme['curdir'].'style.css' ) ) {
			return false;
		}
		if ( empty( $this->_data->filtered['files']['header']['error'] ) ) {
			if( !empty($this->_data->filtered['prop_settings']['header']['id'])&&empty($this->_data->filtered['prop_settings']['header']['name_system']) ){
				$_file=new Project_Files_Blogfusion();
				$_file->withIds($this->_data->filtered['prop_settings']['header']['id'])->onlyOne()->getList($this->_data->filtered['prop_settings']['header']);
			}
			$_strNewFileName='header.'.Core_Files::getExtension( $this->_data->filtered['prop_settings']['header']['name_system'] );
			if ( !copy( '.'.str_replace('/',DIRECTORY_SEPARATOR,$this->_data->filtered['prop_settings']['header']['path_web']).$this->_data->filtered['prop_settings']['header']['name_system'], $this->_wpTheme['curdir'].'images'.DIRECTORY_SEPARATOR.$_strNewFileName ) ) {
				return false;
			}
			$strFile=str_replace( 'header.jpg', $_strNewFileName, $strFile );
		}
		return true;
	}

	// Below header and navigation bar
	private function propBelowHeaderAndNavibar( &$strFile ) {
		$strFile='';
		if ( empty( $this->_data->filtered['prop_settings']['bar'] ) ) {
			return true;
		}
		switch( $this->_data->filtered['prop_settings']['bar'] ) {
			// banner
			case 'upload_banner':
				if ( !empty( $this->_data->filtered['files']['banner']['error'] ) ) {
					$this->_error->setError( 'Upload Banner can\'t be empty');
					return false;
				}
				if( !empty($this->_data->filtered['prop_settings']['banner']['id'])&&empty($this->_data->filtered['prop_settings']['banner']['name_system']) ){
					$_file=new Project_Files_Blogfusion();
					$_file->withIds($this->_data->filtered['prop_settings']['banner']['id'])->onlyOne()->getList($this->_data->filtered['prop_settings']['banner']);
				}
				$_strNewFileName='banner.'.Core_Files::getExtension( $this->_data->filtered['prop_settings']['banner']['name_system'] );
				if ( !copy( '.'.str_replace('/',DIRECTORY_SEPARATOR,$this->_data->filtered['prop_settings']['banner']['path_web']).$this->_data->filtered['prop_settings']['banner']['name_system'], $this->_wpTheme['curdir'].'images'.DIRECTORY_SEPARATOR.$_strNewFileName ) ) {
					return false;
				}
				$strFile='<a href="'.$this->_data->filtered['prop_settings']['url'].'"><img src="wp-content/themes/altmed/images/'.$_strNewFileName.'" border="0"></a>';
			break;
			// code snippet
			case 'code':
				if ( empty( $this->_data->filtered['prop_settings']['code'] ) ) {
					$this->_error->setError("code can't be empty");
					return false;
				}
				$strFile=$this->_data->filtered['prop_settings']['code'];
			break;
			// adsense code
			case 'adsense_code':
				if ( empty( $this->_data->filtered['prop_settings']['adsense'] ) ) {
					$this->_error->setError("Adsense ID can't by empty");
					return false;
				}
				if ( !Core_Files::getContent( $strFile, $this->_wpTheme['curdir'].'topbanner.php' ) ) {
					return false;
				}
				$strFile=str_replace( '##ADSENSE_ID##', $this->_data->filtered['prop_settings']['adsense'], $strFile );
			break;
		}
		return true;
	}

	// Links
	private function propLinks( &$strFile ) {
		$strFile='';
		if ( !Core_Files::getContent( $strFile, $this->_wpTheme['curdir'].'header.php' ) ) {
			return false;
		}
		if ( empty( $this->_data->filtered['prop_settings']['links'] ) ) {
			return true;
		}
		$_arrLinks=array_unique( preg_split( "/[,]+/", $this->_data->filtered['prop_settings']['links'], -1, PREG_SPLIT_NO_EMPTY ) );
		if ( empty( $_arrLinks ) ) {
			return true;
		}
		$_data=new Core_Data( $_arrLinks );
		$_data->setFilter(array('trim','clear'));
		$strFile=str_replace( '<!--LINKS-->', '<li>'.join( '</li><li>', $_data->getFiltered() ).'</li>', $strFile );
		return true;
	}

	// Configure sidebar
	private function propSidebar( &$strFile ) {
		$strFile='';
		if ( !Core_Files::getContent( $strFile, $this->_wpTheme['curdir'].'l_sidebar.php' ) ) {
			return false;
		}
		$strFile=str_replace( array( '<!--sidebar1-->', '<!--sidebar2-->', '<!--sidebar3-->' ), array( 
			$this->_data->filtered['prop_settings'][$this->_data->filtered['prop_settings']['place'][0]], 
			$this->_data->filtered['prop_settings'][$this->_data->filtered['prop_settings']['place'][1]], 
			$this->_data->filtered['prop_settings'][$this->_data->filtered['prop_settings']['place'][2]], 
		 ), $strFile );
		return true;
	}

	// разархивировать туда устанавливаемые плагины
	private function extractPlugins() {
		if ( empty( $this->_data->filtered['plugins'] ) ) { // plugins
			return false;
		}
		$plugins=new Project_Wpress_Plugins();
		if ( $plugins->withIds( $this->_data->filtered['plugins'] )->getList( $_arrPlugins )->checkEmpty() ) {
			foreach( $_arrPlugins as $v ) {
				$this->_wpPlugins[]=$v['wp_path'];
				$temp=explode('/',$v['wp_path']);
				if ($temp){
					$this->_wpNamePlugins[]=$temp[0];
				}
				if ( !Core_Zip::getInstance()->setDir( $this->_mutatorDir.'wordpress'.DIRECTORY_SEPARATOR.'wp-content'.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR )
					->extractZip( $v['path'].$v['filename'] ) ) {
					return false;
				}
			}
		}
		return true;
	}

	// заглушка
	protected function prepareData() {
		return true;
	}

	// контент из статей при создании-редактировании сайта
	protected function getFileContent( &$arrItem ) {
		return $arrItem['title']."\n".$arrItem['author']."\n".$arrItem['body'];
	}

	protected function generateContent() {
		if ( empty( $this->_content ) ) {
			return false;
		}
		$_strDir=$this->_dir.'articles'.DIRECTORY_SEPARATOR;
		if ( !is_dir( $_strDir ) ) {
			mkdir( $_strDir, 0755, true );
		}
		if ( !is_dir( $_strDir ) ) {
			return $this->_error->setError( 'Can\'t create dir '.$_strDir );
		}
		foreach( $this->_content as $v ) {
			$_strFileName=Core_String::getInstance( strtolower( strip_tags( $v['title'] ) ) )->toSystem( '-' ).'.txt';
			$_str=$this->getFileContent( $v );
			if ( !Core_Files::setContent( $_str, $_strDir.$_strFileName ) ) {
				return $this->_error->setError( 'Unable to save articles' );
			}
		}
		return true;
	}

	public function deleteContent() {
		if( empty( $this->_content ) ) {
			return false;
		}
		$_arrSites=array();
		foreach( $this->_content as $v ) {
			if( empty( $v['site_id'] )||empty( $v['link'] ) ) {
				continue;
			}
			$_arrSites[$v['site_id']][]=$v['link']; // тут ids постов блогфьюжн
		}
		if( empty( $_arrSites ) ) {
			return false;
		}
		$_posts=new Project_Wpress_Content_Posts();
		foreach( $_arrSites as $_intSiteId=>$_arrContentIds ) {
			if ( !$_posts->setBlogById( $_intSiteId ) ) {
				return false;
			}
			$_posts->withIds( $_arrContentIds )->getList( $_arrContent );
			$_arrDel=array();
			foreach( $_arrContent as $v ) {
				$_arrDel[$v['id']]=array(
					'id'=>$v['id'],
					'title'=>$v['title'],
					'del'=>'on',
					'ext_id'=>$v['ext_id'],
					'tags'=>$v['tags'],
					'content'=>$v['content'],
				);
			}
			if ( !$_posts->setData( $_arrDel )->delete() ) {
				return false;
			}
		}
		return true;
	}

	// сайты удаляем только из нашей БД
	public function deleteSites( $_arrIds ) {
		$_sites=new Project_Sites(Project_Sites::BF);
		$_sites->withIds($_arrIds)->getList( $arrRes );
		$_place=new Project_Placement();
		foreach( $arrRes as $_blog ){
			if(!$_place->onlyOne()->withIds($_blog['placement_id'])->getList( $arrPlace )->checkEmpty()){
				continue;
			}
			if( Project_Placement::REMOTE_HOSTING==$arrPlace['flg_type'] ){
				continue;
			}
			Core_Sql::setConnectToServer('creativenichemanager.hosting');
			$_prefix=str_replace('_','\_',$_blog['db_tableprefix']);
			$_statement=Core_Sql::getCell("SELECT CONCAT( 'DROP TABLE ', GROUP_CONCAT(CONCAT(table_schema,CONCAT('.',table_name))) , ';' ) AS statement FROM information_schema.tables WHERE table_schema='{$_blog['db_name']}' AND table_name LIKE '{$_prefix}%'");
			if(!$_statement){
				Core_Sql::renewalConnectFromCashe();
				continue;
			}
			Core_Sql::setExec($_statement);
			Core_Sql::renewalConnectFromCashe();

		}
		$_wp=new Project_Wpress();
		return $_wp->deleteBlog($_arrIds);
	}

	// создание файла конфига для wp
	private function getConfigCode( &$strCode ) {
		$strCode='<?php
deFine( "DB_HOST", "'.$this->_data->filtered['db_host'].'" );
deFine( "DB_USER", "'.$this->_data->filtered['db_username'].'" );
deFine( "DB_PASSWORD", "'.$this->_data->filtered['db_password'].'" );
deFine( "DB_NAME", "'.$this->_data->filtered['db_name'].'" );
deFine( "DB_CHARSET", "utf8" );
deFine( "DB_COLLATE", "" );
$table_prefix="'.$this->_data->filtered['db_tableprefix'].'";
deFine( "SECRET_KEY", "put your unique phrase here" );
deFine( "WPLANG", "" );
deFine( "ABSPATH", dirname(__FILE__)."/" );
require_once( ABSPATH.\'wp-settings.php\' );
?>';
	}

	// создание файла .htaccess для wp если не стандартеый flg_permalink
	private function getHtaccessCode( &$strCode ) {
		if ( empty( $this->_data->filtered['flg_permalink'] ) ) {
			return;
		}
		$_arrUrl=parse_url( preg_replace( '|/+$|', '', $this->_data->filtered['url'] ) );
		$_strRoot=empty( $_arrUrl['path'] )? '':$_arrUrl['path'];
		$strCode=
'# BEGIN WordPress
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase '.$_strRoot.'/
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . '.$_strRoot.'/index.php [L]
</IfModule>
# END WordPress';
	}

	// создание файла инсталлятора
	private function getInstallerCode( &$strCode ) {
		$strCode='<?php
define( "WP_SITEURL", "'.$this->_data->filtered['url'].'" );
define( "WP_INSTALLING", true );
require_once( "./wp-config.php" );
function wp_new_blog_notification(){}
require_once( "./wp-admin/includes/upgrade.php" );
require_once( "./wp-admin/includes/plugin.php" );
global $wpdb;
if ( !empty( $wpdb->error ) ) {
	wp_die( $wpdb->error->get_error_message() );
}
$wpdb->show_errors();';
		if (empty($this->_data->filtered['id'])){
		$strCode.='
wp_install( 
	stripslashes( \''.addslashes( $this->_data->filtered['title'] ).'\' ), 
	\''.$this->_data->filtered['dashboad_username'].'\', 
	\''.$this->_data->filtered['admin_email'].'\', 
	1 );';
		}
		$strCode.='
update_option( "comment_moderation", "'.(empty( $this->_data->filtered['flg_comment_moderated'])? '1':'0').'" );
update_option( "blogname", stripslashes( \''.addslashes( $this->_data->filtered['title'] ).'\' ) );
update_option( "blogdescription", stripslashes( \''.addslashes( $this->_data->filtered['blogtag_line'] ).'\' ) );
update_option( "posts_per_page", "'.$this->_data->filtered['post_perpage'].'" );
update_option( "posts_per_rss", "'.$this->_data->filtered['post_per_rss'].'" );
update_option( "rss_use_excerpt", "'.$this->_data->filtered['flg_summary'].'" );
update_option( "default_ping_status", "'.(empty( $this->_data->filtered['flg_ping_status'])? 'open':'closed').'" );
update_option( "ping_sites", "'.$this->_data->filtered['pingsite_list'].'" );';
	if ( !empty( $this->_wpTheme ) ) { // это для импортированных блогов без выбранной темы
		$strCode.='update_option( "current_theme", "'.$this->_wpTheme['title'].'" );
update_option( "stylesheet", "'.$this->_wpTheme['name'].'" );
update_option( "template", "'.( ( !empty( $this->_wpTheme['parent'] ) ) ? $this->_wpTheme['parent'] : $this->_wpTheme['name'] ).'" );';
	}
		$strCode.='wp_update_user( array( 
	"ID"=>1, 
	"user_login"=>"'.$this->_data->filtered['dashboad_username'].'", 
	"user_pass"=>"'.$this->_data->filtered['dashboad_password'].'" ) );
$wpdb->query( "UPDATE $wpdb->users SET user_login=\''.$this->_data->filtered['dashboad_username'].'\' WHERE ID=1 LIMIT 1" ); // login changing';
	if ( empty( $this->_data->filtered['id'] ) ){
		$strCode.='
		wp_update_post( array(
	"ID"=>$wpdb->get_var("SELECT id FROM $wpdb->posts WHERE post_name=\'hello-world\'"),
	"post_status" =>"'.(empty( $this->_data->filtered['create_default_pages'] )? 'draft':'publish').'" ) );
wp_update_post( array(
	"ID"=>$wpdb->get_var("SELECT id FROM $wpdb->posts WHERE post_name=\'about\'"),
	"post_status" =>"'.(empty( $this->_data->filtered['create_default_pages'] )? 'draft':'publish').'" ) );';
	}	
$strCode.='
	$wpdb->query("UPDATE $wpdb->term_taxonomy SET count=\''.(empty( $this->_data->filtered['flg_blogroll_links'] )? '0':'7').'\' WHERE term_id=\'2\'");';
		// Create first post 
		if ( !empty( $this->_data->filtered['first_post_title'] ) ) {
			$strCode.='
				wp_insert_post( array(
					\'post_title\'=>stripslashes(\''.addslashes( $this->_data->filtered['first_post_title'] ).'\'),
					\'post_content\'=>stripslashes(\''.addslashes( $this->_data->filtered['first_post_description'] ).'\'),
					\'tags_input\'=>stripslashes(\''.addslashes( $this->_data->filtered['first_post_tags'] ).'\'),
					\'post_status\'=>\'publish\',
				) );
			';
		}
		// plugins
		if ( !empty( $this->_wpPlugins ) ) {
			foreach ( $this->_wpPlugins as $plugin ){
				$strCode.='activate_plugin(\''.$plugin.'\');';	
			}
		} else {
			$strCode.='
update_option( "active_plugins", array() );';
		}
		// на этапе wp_install (см. выше) создаётся дефолтная категория Uncategorized
		// если указано новое название для этой категории в $this->_data->filtered['blog_default_category']
		// меняем его
		if( !empty( $this->_data->filtered['blog_default_category'] ) ) {
			$strCode.='
wp_update_category( array( 
	\'cat_ID\'=>get_option( \'default_category\' ),
	\'cat_name\'=>stripslashes(\''.addslashes( $this->_data->filtered['blog_default_category'] ).'\'),
	\'category_nicename\'=>\''.Core_String::getInstance( $this->_data->filtered['blog_default_category'] )->toSystem().'\',
) );';
		}


		if ( !empty( $this->_data->filtered['blog_categories'] ) ) {
			$_arrCats=array_unique( preg_split( "/[,]+/", $this->_data->filtered['blog_categories'], -1, PREG_SPLIT_NO_EMPTY ) );
			if ( !empty( $_arrCats ) ) {
				foreach( $_arrCats as $v ) {
					$v=trim( $v );
					if ( empty( $v ) ) continue;
					$strCode.='
wp_insert_category(array(
	\'cat_name\'=>stripslashes(\''.addslashes( $v ).'\'),
	\'category_nicename\'=>\''.Core_String::getInstance( $v )->toSystem().'\',
));';
				}
			}
		}
		$strCode.='
$wp_rewrite->set_permalink_structure( \''.Project_Wpress::$permalinkTypes[$this->_data->filtered['flg_permalink']].'\' );
create_initial_taxonomies();
flush_rewrite_rules();
';
		$strCode.=' echo \'true\'; ?>';
		// send email
	}

	// код для удаления лишних файлов после установки блога
	private function getGarbageCollector( &$strCode ) {
		$strCode='<?php
@unlink(\'./cnm-install.php\');
@unlink(\'./cnm-unzip.php\');
@unlink(\'./blogfusion.zip\');
echo \'true\';
?>';
	}
}
?>