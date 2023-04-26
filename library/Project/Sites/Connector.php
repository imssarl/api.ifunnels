<?php
class Project_Sites_Connector extends Core_Media_Ftp {

	private $_url;
	private $_dir; // папка в которую набиваем содержимое сайта
	private $_permChecked=false; // показывает были-ли проверены какие пермишены надо выставлять на сервере

	/**
	 * url сайта
	 *
	 * @param string $_str
	 * @return object
	 */
	public function setHttpUrl( $_str='' ) {
		$this->_url=$_str;
		return $this;
	}

	/**
	 * папка в которой подготовлен код для заливки
	 *
	 * @param string $_str
	 * @return object
	 */
	public function setSourceDir( $_str='' ) {
		$this->_dir=$_str;
		return $this;
	}

	public function checkFtpAccessibility() {
		$_strDir='Project_Sites_Connector@checkFtpAccessibility';
		if ( !Zend_Registry::get( 'objUser' )->prepareTmpDir( $_strDir ) ) {
			return false;
		}
		// коннектим к фтп
		if ( !$this->makeConnectToRootDir() ) {
			return false;
		}
		// дополнительные файлы
		$this->getPermissionCheckerCode( $_strContent );
		if ( !Core_Files::setContent( $_strContent, $_strDir.'cnm-sapi.php' ) ) {
			return false;
		}
		// проверка прав на файлы
		if ( !$this->fileUpload( $this->_root.'cnm-sapi.php', $_strDir.'cnm-sapi.php' ) ) {
			return $this->setError( 'unable upload to '.$this->_root.'cnm-sapi.php' );
		}
		if ( !$this->checkPermissions() ) {
			return $this->setError( 'error on check permissions' );
		}
		return true;
	}

	public function upload() {
		$_strDir='Project_Sites_Connector@upload';
		if ( !Zend_Registry::get( 'objUser' )->prepareTmpDir( $_strDir ) ) {
			return false;
		}
		// упаковываем файлы из предыдущего шага в zip
		if ( true!==Core_Zip::getInstance()->open( $_strDir.'source.zip', ZipArchive::CREATE ) ) {
			return false;
		}
		if ( !Core_Zip::getInstance()->addDirAndClose( $this->_dir ) ) {
			return false;
		}
		// коннектим к фтп
		if ( !$this->makeConnectToRootDir() ) {
			$this->setError('error with connect to ftp');
			return false;
		}
		// дополнительные файлы
		$this->getPermissionCheckerCode( $_strContent );
		if ( !Core_Files::setContent( $_strContent, $_strDir.'cnm-sapi.php' ) ) {
			return false;
		}
		// проверка прав на файлы
		if ( !$this->fileUpload( $this->_root.'cnm-sapi.php', $_strDir.'cnm-sapi.php' ) ) {
			return $this->setError( 'unable upload to '.$this->_root.'cnm-sapi.php' );
		}
		if ( !$this->checkPermissions() ) {
			return $this->setError( 'error on check permissions' );
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
		if ( !$this->getResponce( $_strRes, $this->_url.'cnm-unzip.php' ) ) {
			return $this->setError( 'no respond '.$this->_url.'cnm-unzip.php' );
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

	private function checkPermissions() {
		if ( $this->_permChecked ) {
			return true;
		}
		// проверка прав
		$this->getResponce( $_strRes, $this->_url.'cnm-sapi.php' );

		// заливаем этот файл с правами 0777 если там cgi то в ответ получим Internal Server Error 500 (у апача)
		// поэтому пробуем поменять права на 0644 и проверить ещё раз (для папок создаваемых с сервера будут права 0755 см. $this->permissionDir)
		if ( !in_array( $_strRes, array( '0644', '0777' ) ) ) {
			$this->setChmod( '0644' );
			if ( !$this->chmod( $this->_root.'cnm-sapi.php' ) ) {
				return false;
			}
			if ( !$this->getResponce( $_strRes, $this->_url.'cnm-sapi.php' )||empty( $_strRes ) ) {
				return $this->setError( 'no respond '.$this->_url.'cnm-sapi.php' );
			}
			if ( !in_array( $_strRes, array( '0644', '0777' ) ) ) {
				return $this->setError( 'get permissions filed with '.$this->_url.'cnm-sapi.php' );
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

	private function getResponce( &$strRes, $_strFile='' ) {
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
}
?>