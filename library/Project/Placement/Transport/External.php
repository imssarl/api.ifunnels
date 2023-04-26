<?php
class Project_Placement_Transport_External extends Core_Media_Ftp implements Project_Placement_Transport_Interface {

	/**
	 * Permissions checked flag
	 * показывает были-ли проверены какие пермишены надо выставлять на сервере
	 *
	 * @var boolean
	 */
	private $_permChecked=false;

	/**
	 * Site http host
	 *
	 * @var string
	 */
	private $_url='';

	public function __construct( Project_Placement_Transport $object ) {
		parent::__construct();
		$this->_object=$object;
	}

	/**
	 * Set site http host
	 *
	 * @param string $_str
	 * @return object
	 */
	public function setUrl( $_str='' ) {
		$this->_url=$_str;
		return $this;
	}

	public function checkAccessibility() {
		$_strDir='Project_Placement_Transport_External@checkAccessibility';
		if ( !Zend_Registry::get( 'objUser' )->prepareTmpDir( $_strDir ) ) {
			return $this->_error->setError( 'Can\'t create dir '.$_strDir );
		}
		return $this->connectAndCheck( $_strDir );
	}

	/**
	* Стартует размещение файлов на конечном сервере
	*
	* @return boolean
	*/
	public function place() {
		$_strDir='Project_Placement_Transport_External@place';
		if ( !Zend_Registry::get( 'objUser' )->prepareTmpDir( $_strDir ) ) {
			return $this->_error->setError( 'Can\'t create dir '.$_strDir );
		}
		if ( is_file( $this->_object->getSourceDir() ) ) {
			// могут передать и архив - это в случае Project_Sites_Adapter_Blogfusion
			copy( $this->_object->getSourceDir(), $_strDir.'blogfusion.zip' );
		} else {
			// упаковываем файлы из предыдущего шага в zip
			if ( true!==Core_Zip::getInstance()->open( $_strDir.'source.zip', ZipArchive::CREATE ) ) {
				return $this->_error->setError( 'Can\'t create zip arhive' );
			}
			if ( !Core_Zip::getInstance()->addDirAndClose( $this->_object->getSourceDir() ) ) {
				return $this->_error->setError( 'Can\'t add files to zip' );
			}
		}
		if ( !$this
			->setUrl( $this->_object->getHttpHost() )
			->setHost( $this->_object->getFtpHost() )
			->setUser( $this->_object->getFtpUser() )
			->setPassw( $this->_object->getFtpPassword() )
			->setRoot( $this->_object->getDir() )
			->setTransfer($this->_object->getTransfer())
			->connectAndCheck( $_strDir ) ) {
			return false;
		}
		// меняем права основной дире на полученные
		if ( !$this->chmod( $this->_root, Core_Media_Ftp::CHMOD_DIR ) ) {
			return false;
		}
		if ( !copy( Zend_Registry::get( 'config' )->path->absolute->user_files.'sites'.DIRECTORY_SEPARATOR.'cnm-unzip.php', $_strDir.'cnm-unzip.php' ) ) {
			return false;
		}
		// загрузка архива и распаковщика
		if ( !$this->setPathFrom( $_strDir )->dirUpload() ) {
			return false;
		}
		// распаковываем архив со скриптами сайта
		if ( !Core_Curl::getResult( $_strRes, $this->_url.'cnm-unzip.php' ) ) {
			return $this->_error->setError( 'No respond from '.$this->_url.'cnm-unzip.php' );
		}
		return true;
	}

	public function download( $remoteFile, $localFile ){
		$_strDir='Project_Placement_Transport_External@download';
		if ( !Zend_Registry::get( 'objUser' )->prepareTmpDir( $_strDir ) ) {
			return $this->_error->setError( 'Can\'t create dir '.$_strDir );
		}
		if ( !$this
			->setUrl( $this->_object->getHttpHost() )
			->setHost( $this->_object->getFtpHost() )
			->setUser( $this->_object->getFtpUser() )
			->setPassw( $this->_object->getFtpPassword() )
			->setRoot( $this->_object->getDir() )
			->setTransfer($this->_object->getTransfer())
			->connectAndCheck( $_strDir ) ) {
			return false;
		}
		return $this->fileDownload($remoteFile,$localFile);
	}

	public function readFile( &$strContent, $remoteFile ){
		if ( !$this
			->setUrl( $this->_object->getHttpHost() )
			->setHost( $this->_object->getFtpHost() )
			->setUser( $this->_object->getFtpUser() )
			->setPassw( $this->_object->getFtpPassword() )
			->setRoot( $this->_object->getDir() )
			->setTransfer($this->_object->getTransfer())
			->makeConnectToRootDir() ) {
			return false;
		}
		$_strDir='Project_Placement_Transport_External@readFile';
		if ( !Zend_Registry::get( 'objUser' )->prepareTmpDir( $_strDir ) ) {
			return $this->_error->setError( 'Can\'t create dir '.$_strDir );
		}
		$_bool=$this->fileDownload( $remoteFile, $_strDir . md5($remoteFile).'.tmp' );
		Core_Files::getContent( $strContent, $_strDir . md5($remoteFile).'.tmp');
		return $_bool;
	}

	public function saveFile( &$strContent, $remoteFile ){
		if ( !$this
			->setUrl( $this->_object->getHttpHost() )
			->setHost( $this->_object->getFtpHost() )
			->setUser( $this->_object->getFtpUser() )
			->setPassw( $this->_object->getFtpPassword() )
			->setRoot( $this->_object->getDir() )
			->setTransfer($this->_object->getTransfer())
			->makeConnectToRootDir() ) {
			return false;
		}
		$_strDir='Project_Placement_Transport_External@saveFile';
		if ( !Zend_Registry::get( 'objUser' )->prepareTmpDir( $_strDir ) ) {
			return $this->_error->setError( 'Can\'t create dir '.$_strDir );
		}
		Core_Files::setContent( $strContent, $_strDir . md5($remoteFile).'.tmp');
		return $this->fileUpload($remoteFile, $_strDir . md5($remoteFile).'.tmp');
	}

	public function removeFile( $_remoteFile ){
		if(empty($_remoteFile)){
			return false;
		}
		if ( !$this
			->setUrl( $this->_object->getHttpHost() )
			->setHost( $this->_object->getFtpHost() )
			->setUser( $this->_object->getFtpUser() )
			->setPassw( $this->_object->getFtpPassword() )
			->setRoot( $this->_object->getDir() )
			->setTransfer($this->_object->getTransfer())
			->makeConnectToRootDir() ) {
			return false;
		}
		return $this->rmFile( $_remoteFile );
	}
	
	public function removeDir( $_removeDir ){
		if(empty($_removeDir)){
			return false;
		}
		if ( !$this
			->setUrl( $this->_object->getHttpHost() )
			->setHost( $this->_object->getFtpHost() )
			->setUser( $this->_object->getFtpUser() )
			->setPassw( $this->_object->getFtpPassword() )
			->setRoot( $this->_object->getDir() )
			->setTransfer($this->_object->getTransfer())
			->makeConnectToRootDir() ) {
			if ( $this
				->setUrl( $this->_object->getHttpHost() )
				->setHost( $this->_object->getFtpHost() )
				->setUser( $this->_object->getFtpUser() )
				->setPassw( $this->_object->getFtpPassword() )
				->setRoot( '/' )
				->setTransfer($this->_object->getTransfer())
				->makeConnectToRootDir() ) {
				// empty dir == no dir
				return true;
			}
			return false;
		}
		return $this->rmDir( $_removeDir );
	}
	
	public function dirScan( &$arrRes, $_strDir ){
		if ( !$this
			->setUrl( $this->_object->getHttpHost() )
			->setHost( $this->_object->getFtpHost() )
			->setUser( $this->_object->getFtpUser() )
			->setPassw( $this->_object->getFtpPassword() )
			->setRoot( $this->_object->getDir() )
			->setTransfer($this->_object->getTransfer())
			->makeConnectToRootDir() ) {
			return false;
		}
		$_strDir=rtrim($_strDir,'/').'/';
		$_contents=ftp_nlist($this->ftp, $_strDir );
		foreach( $_contents as $_currentFile ) {
			if( substr($_currentFile,strlen($_currentFile)-1,1)=='.'||substr($_currentFile,strlen($_currentFile)-2,2)=='..' ){
				continue;
			}
			if ( strpos($_currentFile, '.') === false ) {
				$this->dirScan( $arrRes, $_currentFile);
				continue;
			}
			$_pathinfo=pathinfo($_currentFile);
			switch ($_pathinfo['extension']){
				case 'php':
				case 'css':
				case 'js':
				case 'html':
				case 'htm': break;
				default: continue 2; break;
			}
			$arrRes[$_strDir][]=$_pathinfo['basename'];
		}
		return !empty($arrRes);
	}

	private function connectAndCheck( $_strDir='' ) {
		// коннектим к фтп
		if ( !$this->makeConnectToRootDir() ) {
			return $this->_error->setError( 'Can\'t connect to "'.$this->_host.'" ftp host' );
		}
		// уже проверяли пермишены для данного сервера и они установлены
		// во время действий на фтп я надеюсь они не поменяются )
		if ( $this->_permChecked ) {
			return true;
		}
		// создание файла для проверки sapi режима на сервере
		$_str='<?php echo (substr( php_sapi_name(), 0, 3 )==\'cgi\'? \'0644\':\'0777\');?>';
		if ( !Core_Files::setContent( $_str, $_strDir.'cnm-sapi.php' ) ) {
			return $this->_error->setError( 'Error with create cnm-sapi.php' );
		}
		// проверка прав на файлы
		if ( !$this->fileUpload( $this->_root.'cnm-sapi.php', $_strDir.'cnm-sapi.php' ) ) {
			return $this->_error->setError( 'Unable upload to '.$this->_root.'cnm-sapi.php' );
		}
		// проверка прав
		Core_Curl::getResult( $_strRes, $this->_url.'cnm-sapi.php' );
		// заливаем этот файл с правами 0777 если там cgi то в ответ получим Internal Server Error 500 (у апача)
		// поэтому пробуем поменять права на 0644 и проверить ещё раз (для папок создаваемых с сервера будут права 0755 см. $this->permissionDir)
		if ( !in_array( $_strRes, array( '0644', '0777' ) ) ) {
			if ( !$this->setChmod( '0644' )->chmod( $this->_root.'cnm-sapi.php' ) ) {
				return $this->_error->setError( 'Error on check permissions' );
			}
			if ( !Core_Curl::getResult( $_strRes, $this->_url.'cnm-sapi.php' )||empty( $_strRes ) ) {
				return $this->_error->setError( 'No respond '.$this->_url.'cnm-sapi.php' );
			}
			if ( !in_array( $_strRes, array( '0644', '0777' ) ) ) {
				return $this->_error->setError( 'Get permissions filed with '.$this->_url.'cnm-sapi.php' );
			}
		}
		$this->setChmod( $_strRes );
		// если в $this->blog['directory'] есть какие-то файлы надо их либо удалить либо сделать $this->chmodRecursive() TODO!!! 10.07.2009
		// например мргут быть error_log, index.php, .htaccess
		$this->_permChecked=true;
		return true;
	}

	public function closeConnection() {
		$this->_permChecked=false;
		return parent::closeConnection();
	}

	/**
	* Обрывает коннект транспорта при необходимости
	*
	* @return void
	*/
	public function __destruct() {
		$this->closeConnection();
	}

	public function browseDirs( &$arrDirs ) {
		if ( !$this
			->setChmod( '0755' )
			->setHost( $this->_object->getFtpHost() )
			->setUser( $this->_object->getFtpUser() )
			->setPassw( $this->_object->getFtpPassword() )
			->setTransfer($this->_object->getTransfer())
			->makeConnect() ) {
			return false;
		}
		$_lastInfo=$this->_object->getInfo();
		$_bool=$this
			->dirForLs( $this->_object->getDir() )
			->ls( $arrDirs, Core_Media_Ftp::LS_DIRS_FILES );
		$_updatedInfo=$this->_object->getInfo();
		if( $_bool ){
			$newArrDirs=array();
			foreach( $arrDirs as $_haveDir ){
				if( $_lastInfo['ftp_directory'] == $_updatedInfo['ftp_directory'].'/'.$_haveDir['name'] && $_haveDir['is_dir'] ){
					$_bool=$this
						->dirForLs( rtrim($_lastInfo['ftp_directory'],'/').'/' )
						->ls( $newArrDirs, Core_Media_Ftp::LS_DIRS_FILES );
				}
			}
			if( !empty( $newArrDirs ) ){
				$arrDirs=$newArrDirs;
			}
		}
		Core_Sql::reconnect();
		return $_bool;
	}

	public function makeDir( $_strNewDir='' ) {
		$this->getForLsDir( $_strDir );
		return parent::makeDir( $_strDir.$_strNewDir );
	}

	public function getCurrentDir() {
		$this->getForLsDir( $_strDir );
		return $_strDir;
	}

	public function getPrevDir() {
		$this->getForLsDir( $_strDir );
		return $this->getPrev( $_strDir );
	}

	// dir/dir/file -> /dir/dir/ | /dir/dir -> /dir/ | /dir -> '' etc.
	private function getPrev( $_strDir='' ) {
		if ( empty( $_strDir ) ) {
			return '';
		}
		$_arrDir=explode( '/', $_strDir );
		if ( empty( $_arrDir[count( $_arrDir )-1] ) ) {
			unSet( $_arrDir[count( $_arrDir )-1] );
		}
		if ( empty( $_arrDir[0] ) ) {
			unSet( $_arrDir[0] );
		}
		$_arrDir=array_values( $_arrDir );
		unset( $_arrDir[count( $_arrDir )-1] );
		if ( empty( $_arrDir ) ) {
			return '';
		}
		return '/'.implode( '/', $_arrDir );
	}
}
?>