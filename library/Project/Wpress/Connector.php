<?php


/**
 * Remote blogs management transport
 */
class Project_Wpress_Connector extends Core_Media_Ftp {

	public $_data; // объект с данными
	protected $_createSrcDir=''; // там где лежат исходники используемые для инсталляции блога
	protected $_cloneSrcDir=''; // там где лежат исходники используемые для копирования блога
	protected $_mutatorDir=''; // папка где собирается конкретная инсталляция (см. Project_Wpress_Connector_Create)
	protected $_zip; // объект создаётся только один раз в getZip
	protected $_permChecked=false; // показывает были-ли проверены какие пермишены надо выставлять на сервере
	private $_curl =false;

	public function __construct( Core_Data $obj ) {
		parent::__construct();
		$this->_data=$obj;
		$this->initDirs();
		$this
			->setHost( $this->_data->filtered['ftp_host'] )
			->setUser( $this->_data->filtered['ftp_username'] )
			->setPassw( $this->_data->filtered['ftp_password'] )
			->setRoot( $this->_data->filtered['ftp_directory'] );
	}
	
	public function __destruct(){
		$_filelist=@ftp_nlist($this->ftp,$this->_data->filtered['ftp_directory']);
		if( is_array($_filelist)&&in_array('.htaccess',$_filelist) ){
			@ftp_delete($this->ftp,$this->_data->filtered['ftp_directory'].'_.htaccess');
		} else {
			@ftp_rename( $this->ftp, $this->_data->filtered['ftp_directory'].'_.htaccess', $this->_data->filtered['ftp_directory'].'.htaccess' );
		}
	}

	private function initDirs() {
		$this->_createSrcDir=Zend_Registry::get( 'config' )->path->absolute->user_files.'blogfusion'.DIRECTORY_SEPARATOR.'create'.DIRECTORY_SEPARATOR;
		$this->_cloneSrcDir=Zend_Registry::get( 'config' )->path->absolute->user_files.'blogfusion'.DIRECTORY_SEPARATOR.'clone'.DIRECTORY_SEPARATOR;
		// физически дира создаётся в Project_Wpress_Connector_Create::generateMutator, но нам нужно знать где лежат файлы для их загрузки
		$this->_mutatorDir=Zend_Registry::get( 'objUser' )->getTmpDirName().'Project_Wpress_Connector_Create@generateMutator'.DIRECTORY_SEPARATOR;
	}

	protected function getResponce( &$strRes, $_strFile='' ) {
		if ( empty( $_strFile ) ) {
			return false;
		}
		$_objCurl=Core_Curl::getInstance();
		if ( !$_objCurl->getContent( $_strFile )) {
			return false;
		}
		$strRes=$_objCurl->getResponce();
		return true;
	}

	protected function getZip() {
		if ( is_object( $this->_zip ) ) {
			return $this->_zip;
		}
		$this->_zip=new Core_Zip();
		return $this->_zip;
	}

	// check ftp, perms and db access
	public function prepare() {
		if ( !$this->makeConnectToRootDir() ) {
			return false;
		}
		$_strDir='Project_Wpress_Connector@prepare';
		if ( !Zend_Registry::get( 'objUser' )->prepareTmpDir( $_strDir ) ) {
			return false;
		}
		$this->getPermissionCheckerCode( $_arrFiles['cnm-sapi.php'] );
		$this->getDbCheckerCode( $_arrFiles['cnm-dbcheck.php'] );
		if ( !Core_Files::setContentMass( $_arrFiles, $_strDir ) ) {
			return false;
		}
		@ftp_rename( $this->ftp, $this->_data->filtered['ftp_directory'].'.htaccess', $this->_data->filtered['ftp_directory'].'_.htaccess' );
		// проверка прав на файлы
		if ( !$this->fileUpload( $this->_data->filtered['ftp_directory'].'cnm-sapi.php', $_strDir.'cnm-sapi.php' ) ) {
			return $this->setError( 'unable upload to '.$this->_data->filtered['ftp_directory'].'cnm-sapi.php' );
		}
		// ссылка всегда заканчивается на /
		if ( substr( $this->_data->filtered['url'], -1 )!='/' ) {
			$this->_data->setElement( 'url', $this->_data->filtered['url'].'/' );
		}
		if ( !$this->checkPermissions() ) {
			return $this->setError( 'error on check permissions' );
		}
		// меняем права основной дире на полученные
		 if ( !$this->chmod( $this->_data->filtered['ftp_directory'], Core_Media_Ftp::CHMOD_DIR ) ) {
			return false;
		}
		// проверка доступности бд
		if ( !$this->fileUpload( $this->_data->filtered['ftp_directory'].'cnm-dbcheck.php', $_strDir.'cnm-dbcheck.php' ) ) {
			return $this->setError( 'unable upload to '.$this->_data->filtered['ftp_directory'].'cnm-dbcheck.php' );
		}
		if ( !$this->getResponce( $_strRes, $this->_data->filtered['url'].'cnm-dbcheck.php' ) ) {
			return $this->setError( 'no respond '.$this->_data->filtered['url'].'cnm-dbcheck.php' );
		}
		if ( empty( $_strRes ) || !in_array( trim( $_strRes ), array( 'true' ) ) ) {
			return $this->setError( $_strRes.' error on check bd' );
		}
		// сохранить фтп адресс
		$this->storeFtpParams();
		return true;
	}

	private function storeFtpParams() {
		$_ftp=new Project_Ftp();
		$_ftp->setData( array(
			'ftp_address'=>$this->_host,
			'ftp_username'=>$this->_user,
			'ftp_password'=>$this->_passw,
		) )->set();
	}

	protected function checkPermissions() {
		if ( $this->_permChecked ) {
			return true;
		}
		// проверка прав
		$this->getResponce( $_strRes, $this->_data->filtered['url'].'cnm-sapi.php' );
		
		// заливаем этот файл с правами 0777 если там cgi то в ответ получим Internal Server Error 500 (у апача)
		// поэтому пробуем поменять права на 0644 и проверить ещё раз (для папок создаваемых с сервера будут права 0755 см. $this->permissionDir)
		if ( !in_array( $_strRes, array( '0644', '0777' ) ) ) {
			$this->setChmod( '0644' );
			if ( !$this->chmod( $this->_data->filtered['ftp_directory'].'cnm-sapi.php' ) ) {
				return false;
			}
			if ( !$this->getResponce( $_strRes, $this->_data->filtered['url'].'cnm-sapi.php' )||empty( $_strRes ) ) {
				return $this->setError( 'no respond '.$this->_data->filtered['url'].'cnm-sapi.php' );
			}
			if ( !in_array( $_strRes, array( '0644', '0777' ) ) ) {
				return $this->setError( 'get permissions filed with '.$this->_data->filtered['url'].'cnm-sapi.php' );
			}
		}
		$this->setChmod( $_strRes );
		// если в $this->blog['directory'] есть какие-то файлы надо их либо удалить либо сделать $this->chmodRecursive() TODO!!! 10.07.2009
		// например мргут быть error_log, index.php, .htaccess
		$this->_permChecked=true;
		return true;
	}

	// создание файла для проверки sapi режима на сервере
	private function getPermissionCheckerCode( &$strCode ) {
		$strCode='<?php echo (substr( php_sapi_name(), 0, 3 )==\'cgi\'? \'0644\':\'0777\');?>';
	}

	// создание файла для проверки доступности бд
	private function getDbCheckerCode( &$strCode ) {
		$strCode='<?php
if ( !function_exists( "mysql_connect" ) ) {
	echo "mysql extension not installed"; exit;
}
if ( !$res=@mysql_connect( "'.$this->_data->filtered['db_host'].'", "'.$this->_data->filtered['db_username'].'", "'.$this->_data->filtered['db_password'].'" ) ) {
	echo mysql_error()." (ErrNo: ".mysql_errno().")"; exit;
}
if ( !@mysql_select_db( "'.$this->_data->filtered['db_name'].'", $res ) ) {
	echo mysql_error()." (ErrNo: ".mysql_errno().")"; exit;
}
@mysql_close( $res );
echo "true";
?>';
	}
}
?>