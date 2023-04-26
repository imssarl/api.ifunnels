<?php


/**
 * Blog create transport
 */
class Project_Wpress_Connector_Create extends Project_Wpress_Connector {

	private $_wpPlugins=array(); // массив необходимый для активации плагинов
	private $_wpNamePlugins=array(); // массив необходимый для удаления старых плагинов
	private $_wpTheme=''; // имя темы для активации

	public function __construct( Core_Data $obj ) {
		parent::__construct( $obj );
	}

	public function prepareServer() {
		if ( !$this->prepare() ) {
			return false;
		}
		// заливаем анпакер
		if ( !$this->fileUpload( $this->_data->filtered['ftp_directory'].'cnm-unzip.php', $this->_createSrcDir.'cnm-unzip.php' ) ) {
			return $this->setError( 'unable upload '.$this->_data->filtered['ftp_directory'].'cnm-unzip.php' );
		}
		return true;
	}

	// на этом этапе к фтп не подключаемся
	public function generateMutator() {
		// подготовить диру, $this->_mutatorDir определяется выше потому что она ещё понадобится в других методах (шагах)
		$_strTmp='Project_Wpress_Connector_Create@generateMutator';
		if ( !Zend_Registry::get( 'objUser' )->prepareTmpDir( $_strTmp ) ) {
			return false;
		}
		// копируем архив c текущей версией wp
		if ( empty( $this->_data->filtered['id'] ) ) {
			$lock = new Core_Media_Lock( 'wordpress.zip' );
			$lock->whileLocked();
			$lock->lock();
			copy( $this->_createSrcDir.'wordpress.zip', $this->_mutatorDir.'step1.zip' );
			$lock->unLock();
		}
		// разархивируем структуру для мутатора
		if ( !$this->getZip()->setDir( $this->_mutatorDir )->extractZip( $this->_createSrcDir.'mutators.zip' ) ) {
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
		// упаковываем мутатор в step2.zip
		if ( true!==$this->getZip()->open( $this->_mutatorDir.'step2.zip', ZipArchive::CREATE ) ) {
			return false;
		}
		if ( !$this->getZip()->addDirAndClose( $this->_mutatorDir.'wordpress' ) ) {
			return false;
		}
		return true;
	}

	public function uploadWordpress() {
		if ( !$this->fileUpload( $this->_data->filtered['ftp_directory'].'step1.zip', $this->_mutatorDir.'step1.zip' ) ) {
			return $this->setError( 'unable upload '.$this->_mutatorDir.'step1.zip' );
		}
		return true;
	}

	public function uploadMutator() {
		if ( !$this->fileUpload( $this->_data->filtered['ftp_directory'].'step2.zip', $this->_mutatorDir.'step2.zip' ) ) {
			return $this->setError( 'unable upload '.$this->_mutatorDir.'step2.zip' );
		}
		return true;
	}

	public function installBlog() {
		// распаковываем
		if ( !$this->getResponce( $_strRes, $this->_data->filtered['url'].'cnm-unzip.php' ) ) {
			return $this->setError( 'no respond '.$this->_data->filtered['url'].'cnm-unzip.php' );
		}
		// инсталлируем
		if ( !$this->getResponce( $_strRes, $this->_data->filtered['url'].'cnm-install.php' ) ) {
			return $this->setError( 'no respond '.$this->_data->filtered['url'].'cnm-install.php' );
		}
		// чистим
		if ( !$this->getResponce( $_strRes, $this->_data->filtered['url'].'cnm-clean.php' ) ) {
			return $this->setError( 'no respond '.$this->_data->filtered['url'].'cnm-clean.php' );
		}
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
				if ( !$this->getZip()->setDir( $this->_mutatorDir.'wordpress'.DIRECTORY_SEPARATOR.'wp-content'.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR )
					->extractZip( $v['path'].$v['filename'] ) ) {
					return false;
				}
			}
		}
		return true;
	}

	// разархивируем тему
	public function extractTheme() {
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
		if ( !$this->getZip()->setDir( $this->_wpTheme['curdir'] )->extractZip( $this->_wpTheme['path'].$this->_wpTheme['filename'] ) ) {
			return false;
		}
		Core_Files::dirScan($_arr, $this->_wpTheme['curdir']);
		foreach ( $_arr as $_strDir=>$_arrFiles ) {
			if (in_array( 'style.css', $_arrFiles )){
				$_name = Core_Files::getBaseName($_strDir);
				if ( $_name != $this->_wpTheme['name']){
					$this->_wpTheme['parent'] = $_name;
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
					$this->setError( 'Upload Banner can\'t be empty');
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
					$this->setError("code can't be empty");
					return false;
				}
				$strFile=$this->_data->filtered['prop_settings']['code'];
			break;
			// adsense code
			case 'adsense_code':
				if ( empty( $this->_data->filtered['prop_settings']['adsense'] ) ) {
					$this->setError("Adsense ID can't by empty");
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
			$strCode.='
update_option( \'permalink_structure\', \''.Project_Wpress::$permalinkTypes[$this->_data->filtered['flg_permalink']].'\' );';

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
		$strCode.=' echo \'true\'; ?>';
		// send email
	}

	// код для удаления лишних файлов после установки блога
	private function getGarbageCollector( &$strCode ) {
		$strCode='<?php
@unlink(\'./cnm-install.php\');
@unlink(\'./cnm-unzip.php\');
@unlink(\'./step1.zip\');
@unlink(\'./step2.zip\');
echo \'true\';
?>';
	}
}
?>