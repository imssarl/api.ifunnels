<?php


/**
 * Blogs data updater (by cron updaters too)
 */
class Project_Wpress_Connector_Upgrade extends Project_Wpress_Connector {

	/**
	 * экземпляр для синглтона
	 *
	 * @var object
	 */
	private static $_instance=NULL;
	private $_mail, $_logger, $_loggerMock;

	private $_currentVersion=''; // Версия WP из которой создаём блоги
	private $_updaterDir=''; // Папка с файлами для обновления блогов
	private $_versionDir=''; // Папка с версиями wp
	private $_pathPartToVersion=''; //часть пути до файла с верисей wp
	protected $_createSrcDir=''; // Папка с исходниками wp которые используются при создании блога

	private $_settings=array();
	private $_userId=0;
	private $_table='bf_updater';


	public function __construct() {
		$this->_versionDir=Zend_Registry::get( 'config' )->path->absolute->user_files.'blogfusion'.DIRECTORY_SEPARATOR.'version'.DIRECTORY_SEPARATOR;
		$this->_createSrcDir=Zend_Registry::get( 'config' )->path->absolute->user_files.'blogfusion'.DIRECTORY_SEPARATOR.'create'.DIRECTORY_SEPARATOR;
		$this->_pathPartToVersion=DIRECTORY_SEPARATOR.'wp-includes'.DIRECTORY_SEPARATOR.'version.php';
	}

	public function checkVersion( &$arrRes ){
		$_strAppLink='http://api.wordpress.org/core/version-check/1.6/?version=';
		$this->getCurVersion($_currentVersion);
		$_curl=Core_Curl::getInstance();
		$_curl->getContent($_strAppLink);
		$_response=$_curl->getResponce();
		$_response=unserialize($_response);
		$arrRes=$_response['offers'][0];
		if($arrRes['response']=='upgrade'){
			return false;
		}
		return true;
	}

	public static function getInstance() {
		if ( self::$_instance==NULL ) {
			self::$_instance=new Project_Wpress_Connector_Upgrade();
		}
		return self::$_instance;
	}

	public function runAsApplication() {
		if ( !Zend_Registry::get( 'objUser' )->getId( $_int ) ) {
			throw new Exception( Core_Errors::DEV.'|Zend_Registry::get( \'objUser\' )->getId( $_int ) is not return an User Id' );
			return;
		}
		$this->_userId=$_int;
		$this->getUpgradeSettings( $_arrTmp );
		return $this;
	}

	public function runAsService() {
		$this->_logger=new Zend_Log();
		// полный лог пишем в файлик на свервере
		$stdout=new Zend_Log_Writer_Stream( 'php://output' );
		$stdout->setFormatter( new Zend_Log_Formatter_Simple( Zend_Log_Formatter_Simple::DEFAULT_FORMAT.(php_sapi_name()=='cli'?PHP_EOL:'<br />')) );
		$this->_logger->addWriter( $stdout );
		return $this;
	}

	/**
	 * проверка новой версии wp и отправка сообщений пользователям.
	 *
	 * @return bool
	 */
	public function getLatest(){
		$this->_logger->info( 'Run of process of get new version of WP blog' );
		if ( !$this->getCurVersion( $_strTmp ) ) {
			$this->_logger->err( 'can`t get current version' );
		} elseif ( $this->download() ) {
			self::$_instance=null;
			Project_Wpress_Notification::newWordpressVersion(); // сообщаем пользователям о новой версии WP;
		}
		$this->_logger->info( 'End of process of get new version of WP blog' );
	}

	/**
	 * загрузка новой версии WP и перепаковка для создания нужной структуры директорий.
	 *
	 * @return bool
	 */
	private function download() {
		$_strTmp='Project_Wpress_Connector_Upgrade@download';
		if ( !Zend_Registry::get( 'objUser' )->prepareTmpDir( $_strTmp ) ) {
			$this->_logger->err( 'unavailable to create '.$_strTmp  );
			return false;
		}
		if ( ( $_fh=fopen( $_strTmp.'latest.zip', 'wb' ) )===false ) {
			$this->_logger->err( 'unavailable to create '.$_strTmp.'latest.zip' );
			return false;
		}
		if ( ( $_ch=curl_init() )===false ) {
			$this->_logger->err( 'unavailable curl_init()' );
			return false;
		}
		curl_setopt( $_ch, CURLOPT_FILE, $_fh );
		curl_setopt( $_ch, CURLOPT_HEADER, 0 );
		curl_setopt( $_ch, CURLOPT_URL, 'http://wordpress.org/latest.zip' );
		$_bool=curl_exec( $_ch );
		curl_close( $_ch );
		fclose( $_fh );
		if ( !$_bool ) {
			$this->_logger->err( 'can`t download new version' );
			return false;
		}
		// распаковываем архив
		if ( !Core_Zip::getInstance()->setDir( $_strTmp )->extractZip( $_strTmp.'latest.zip' ) ) {
			$this->_logger->err( 'unavailable extract '.$_strTmp.'latest.zip' );
			return false;
		}
		if ( !is_file( $_strTmp.'wordpress'.$this->_pathPartToVersion ) ) {
			$this->_logger->err( 'lost '.$_strTmp.'wordpress'.$this->_pathPartToVersion.' file' );
			return false;
		}
		require_once( $_strTmp.'wordpress'.$this->_pathPartToVersion );

		if ( version_compare( $this->_currentVersion, $wp_version )>=0 ) { // у нас свежая версия
			$this->_logger->info( 'we have actual version' );
			return false;
		}
		// удаляем папку wp-content т.к. в ней есть предустановленные темы и плагины
		if ( !Core_Files::rmDir( $_strTmp.'wordpress'.DIRECTORY_SEPARATOR.'wp-content'.DIRECTORY_SEPARATOR ) ) {
			$this->_logger->err( 'can`t delete '. $_strTmp.'wordpress'.DIRECTORY_SEPARATOR.'wp-content'.DIRECTORY_SEPARATOR );
			return false;
		}
		// перепаковываем архив с последней версией в место хранения
		if ( true!==Core_Zip::getInstance()->open( $this->_versionDir.$wp_version.'.zip', ZipArchive::OVERWRITE ) ) {
			$this->_logger->err( 'can`t create '.$this->_versionDir.$wp_version.'.zip' );
			return false;
		}
		if ( !Core_Zip::getInstance()->addDirAndClose( $_strTmp.'wordpress' ) ) {
			$this->_logger->err( 'can`t repack source' );
			return false;
		}
		// копируем в рабочую версию
		if ( !copy($this->_versionDir.$wp_version.'.zip', $this->_createSrcDir.'wordpress.zip.tmp' ) ) {
			$this->_logger->err( 'can`t copy file'.$this->_versionDir.$wp_version.'.zip in '. $this->_createSrcDir.'wordpress.zip.tmp' );
			return false;
		}
		$lock=new Core_Media_Lock( 'wordpress.zip' );
		$lock->whileLocked();
		$lock->lock();
		@unlink( $this->_createSrcDir.'wordpress.zip' );
		rename( $this->_createSrcDir.'wordpress.zip.tmp', $this->_createSrcDir.'wordpress.zip' );
		@unlink( $this->_createSrcDir.'version.php' ); // удаляем старую версию файла из которого getCurVersion берёт версию
		$lock->unLock();
		$this->_logger->info( 'downloaded new version WP '.$wp_version );
		return true;
	}

	public function getCurVersion( &$strRes ) {
		if ( !empty( $this->_currentVersion ) ) {
			$strRes=$this->_currentVersion;
			return true;
		}
		if ( !is_file( $this->_createSrcDir.'version.php' ) ) {
			if ( !$this->putCurVersionInfoFile() ) {
				throw new Exception( Core_Errors::DEV.'|don\'t know wp version' );
				return false;
			}
		}
		require( $this->_createSrcDir.'version.php' );
		$this->_currentVersion=$strRes=$wp_version;
		return true;
	}

	private function putCurVersionInfoFile() {
		$_strTmp='Project_Wpress_Connector_Upgrade@putCurVersionInfoFile';
		if ( !Zend_Registry::get( 'objUser' )->prepareTmpDir( $_strTmp ) ) {
			return false;
		}
		if ( !Core_Zip::getInstance()->setDir( $_strTmp.'wordpress' )->extractZip( $this->_createSrcDir.'wordpress.zip' ) ) {
			return false;
		}
		if ( !is_file( $_strTmp.'wordpress'.$this->_pathPartToVersion ) ) {
			return false;
		}
		$_bool=@copy( $_strTmp.'wordpress'.$this->_pathPartToVersion, $this->_createSrcDir.'version.php' );
		return $_bool;
	}

	/**
	 * Обновлнение блогов
	 *
	 * @return bool
	 */
	public function upgradeBlogs() {
		$this->_logger->info( 'Start blogs upgrade' );
		if ( !$this->getCurVersion( $_strTmp ) ) {
			$this->_logger->err( 'can`t get current version ' );
			return false;
		}
		if ( !$this->prepareSrc() ) {
			return false;
		}
		// ошибки по каждому блогу в письма пользователей
		$this->_loggerMock=new Zend_Log_Writer_Mock;
		$this->_logger->addWriter( $this->_loggerMock );
		$this->_loggerMock->addFilter( new Zend_Log_Filter_Priority( Zend_Log::ERR ) );
		// обновляем блоги
		$this->upgradeFromBlogList();
		$this->upgradeFromAll();
		$this->stopFinishedManualUpdaters();
		$this->_logger->info( 'End blogs upgrade' );
		return true;
	}

	// для пользовательских апдэйтеров для которых не проставлена галка в Automatic Upgrade
	// в случае обновления всех блогов апдэйт останавливается
	private function stopFinishedManualUpdaters() {
		$_arrIds=Core_Sql::getField( '
			SELECT u.id
			FROM bf_updater u
			INNER JOIN bf_blog2update b2u ON b2u.updater_id=u.id AND b2u.flg_update=0
			WHERE u.flg_status=1 AND u.flg_auto=0
			GROUP BY u.id
		' );
		Core_Sql::setExec( 'UPDATE bf_updater SET flg_status=0 WHERE flg_auto=0'.(empty( $_arrIds )? '':' AND id NOT IN('.Core_Sql::fixInjection( $_arrIds ).')') );
	}

	// блоги которые обновляютя не автоматически (flg_auto=0) имеют более высокий приоритет
	private function upgradeFromBlogList() {
		$_arrBlogs=Core_Sql::getAssoc( '
			SELECT b.*
			FROM bf_blog2update u
			INNER JOIN bf_blogs b ON b.id=u.blog_id
			WHERE
				u.flg_update=0 AND
				u.updater_id IN(SELECT id FROM bf_updater WHERE flg_status=1 AND flg_auto=0)
			ORDER BY u.added
			LIMIT 50
		' );
		if ( empty( $_arrBlogs ) ) {
			$this->_logger->info( 'Manual blog upgrade have no entry' );
			return;
		}
		$this->_logger->info( 'Start manual blog upgrade' );
		$this->upgrade( $_arrBlogs );
		$this->_logger->info( 'End manual blog upgrade' );
	}

	// блоги которые обновляютя автоматически (flg_auto=1)
	private function upgradeFromAll() {
		$_arrBlogs=Core_Sql::getAssoc( '
			SELECT b.*
			FROM bf_blog2update u
			INNER JOIN bf_blogs b ON b.id=u.blog_id
			WHERE
				u.flg_update=0 AND
				u.updater_id IN(SELECT id FROM bf_updater WHERE flg_status=1 AND flg_auto=1)
			ORDER BY u.added
			LIMIT 50
		' );
		if ( empty( $_arrBlogs ) ) {
			$this->_logger->info( 'Automatic blog upgrade have no entry' );
			return;
		}
		$this->_logger->info( 'Start automatic blog upgrade' );
		$this->upgrade( $_arrBlogs );
		$this->_logger->info( 'End automatic blog upgrade' );
	}

	private function upgrade( &$_arrBlogs ) {
		foreach ( $_arrBlogs as $arrBlog ) {
			$_arrMailContent[$arrBlog['user_id']][$arrBlog['id']]=$arrBlog;
			$this->_logger->info( 'Blog ['.$arrBlog['id'].'] upgrade ['.$arrBlog['version'].']>>>['.$this->_currentVersion.'] version' );
			Core_Sql::setExec( 'UPDATE bf_blog2update SET flg_update=1 WHERE blog_id='.$arrBlog['id'] );
			if ( !$this->process( $arrBlog ) ) { // ошибка
				Core_Sql::setExec( 'UPDATE bf_blog2update SET flg_update=2 WHERE blog_id='.$arrBlog['id'] );
				$_arrMailContent[$arrBlog['user_id']][$arrBlog['id']]['error']=$this->_loggerMock->events[0]['message'].' at '.$this->_loggerMock->events[0]['timestamp'];
				$this->_loggerMock->events=array();
			} else { // успешно
				Core_Sql::setExec( 'UPDATE bf_blogs SET version="'.$this->_currentVersion.'" WHERE id='.$arrBlog['id'] );
				Core_Sql::setExec( 'UPDATE bf_blog2update SET flg_update=3 WHERE blog_id='.$arrBlog['id'] );
				$this->_logger->info( 'complete blog upgrade' );
			}
		}
		Project_Wpress_Notification::result2Users( $_arrMailContent );
	}

	/**
	 * Обновление файлов блога на сервере
	 *
	 * @param array $blogs
	 */
	private function process( &$arrBlog ) {
		if(!$this->prepareSrc()){
			$this->_logger->err( 'Unable to prepare data for update blog' );
			return false;
		}
		// заливаем данные
		$_transport=new Project_Placement_Transport();
		if( !$_transport->setSourceDir( $this->_updaterDir.'update.zip' )->setInfo( $arrBlog )->placeAndBreakConnect() ){
			$this->_logger->err( 'Unable to upload source files' );
			return false;
		}
		// делаем апгрэйд системы
		if ( !Core_Files::getContent( $_strRes, $arrBlog['url'].'wp-admin/upgrade.php?step=upgrade_db' ) ) {
			$this->_logger->err( 'No respond '.$arrBlog['url'].'wp-admin/upgrade.php?step=upgrade_db' );
			return false;
		}
		return true;
	}

	private function prepareSrc() {
		$this->_updaterDir='Project_Wpress_Connector_Upgrade@prepareSrc';
		if ( !Zend_Registry::get( 'objUser' )->prepareTmpDir( $this->_updaterDir ) ) {
			return false;
		}
		$lock=new Core_Media_Lock( 'wordpress.zip' );
		$lock->whileLocked();
		$lock->lock();
		$_bool=@copy( $this->_createSrcDir.'wordpress.zip', $this->_updaterDir.'update.zip' );
		$lock->unLock();
		return $_bool;
	}

	/**
	 * Настройки обновления блогов текущего юзера
	 *
	 * @param unknown_type $arrSettings
	 * @return unknown
	 */
	public function getUpgradeSettings( &$arrRes ) {
		$this->_settings=$arrRes=Core_Sql::getRecord( 'SELECT * FROM '.$this->_table.' WHERE user_id='.$this->_userId );
		return !empty( $arrRes );
	}

	/**
	 * Установка свойств апгрэйда блогов текущего юзера
	 *
	 * @param array $_arrSettings
	 * @return int
	 */
	public function setUpgradeSettings( $_arrSettings=array() ) {
		if ( empty( $_arrSettings ) ) {
			return false;
		}
		if ( !empty( $this->_settings['id'] ) ) {
			$_arrSettings['id']=$this->_settings['id'];
		} else {
			$_arrSettings['user_id']=$this->_userId;
		}
		$this->_settings=$_arrSettings;
		$this->_settings['id']=Core_Sql::setInsertUpdate( $this->_table, $_arrSettings );
		return !empty( $this->_settings['id'] );
	}

	/**
	 * Установка списка блогов для апдэйта при flg_mode=1;
	 *
	 * @param int $_upgradeId
	 * @param string $_strJson
	 * @return bool
	 */
	public function setUpgradeBlogs( $_strJson, $arrSettings=array() ) {
		if ( empty( $this->_settings['id'] ) ) {
			return false;
		}
		Core_Sql::setExec( 'DELETE FROM bf_blog2update WHERE updater_id='.$this->_settings['id'] );
		$_arrBlogs=json_decode( $_strJson );
		if ( empty( $_arrBlogs )&&$arrSettings['flg_mode']==1 ) {
			return false;
		}
		if ( isset( $arrSettings['flg_mode'] )&&$arrSettings['flg_mode']==0 ) {
			$_wpress=new Project_Wpress();
			$_wpress->onlyIds()->getList( $_arrBlogs );
		}
		if ( empty( $_arrBlogs ) ) {
			return false;
		}
		$_arrData=array();
		foreach ( $_arrBlogs as $v ) {
			$_arrData[]=array(
				'blog_id'=>$v,
				'updater_id'=>$this->_settings['id'],
				'added'=>time()
			);
		}
		return Core_Sql::setMassInsert( 'bf_blog2update', $_arrData );
	}

	/**
	 * id блогов для обновления по списку.
	 *
	 * @param  array $arrList
	 * @return bool
	 */
	public function getUpgradeBlogs( &$arrList ) {
		if ( empty( $this->_settings['id'] ) ) {
			return false;
		}
		$arrList=Core_Sql::getKeyRecord( 'SELECT * FROM bf_blog2update WHERE updater_id='.$this->_settings['id'] );
		return !empty( $arrList );
	}
}
?>