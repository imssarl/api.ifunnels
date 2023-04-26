<?php
class Project_Placement_Transport_Internal implements Project_Placement_Transport_Interface {

	private $_ssh=false;

	public function __construct( Project_Placement_Transport $object ) {
		$this->_object=$object;
		$_strSubDir=$this->_object->getHttpHost();
		// чтобы всегда была папка конкретного хостинга. чтобы не потёрлись в случае чего чужие папки
		if ( empty( $_strSubDir ) ) {
			throw new Exception( Core_Errors::DEV.'|user host subdir not found' );
		}
		$this->_hostingDir='/data/www/'.$_strSubDir.'/html'.$this->_object->getDir();
		$this->_hostRootDir='/data/www/'.$_strSubDir.'/html/';
		$_ssh=new Project_Placement_Hosting_Ssh();
		$this->_ssh=$_ssh->ssh();
		if( !$this->_ssh->mkDir( $this->_hostRootDir ) || !$this->_ssh->mkDir( $this->_hostingDir ) ){
//			Core_Data_Errors::getInstance()->setError( Core_Errors::DEV.'|can\'t create ssh dir' );
		}
	}

	/**
	* Стартует размещение файлов на конечном сервере
	*
	* @param object $object объект с настройками
	* @return void
	*/
	public function place() {
		$_strDir='Project_Placement_Transport_Internal@place';
		if ( !Zend_Registry::get( 'objUser' )->prepareTmpDir( $_strDir ) ) {
			return $this->_error->setError( 'Can\'t create dir '.$_strDir );
		}
		// могут передать и архив - это в случае Project_Sites_Adapter_Blogfusion
		if ( is_file( $this->_object->getSourceDir() ) ) {
			if ( !$this->_ssh->uploadFile($this->_object->getSourceDir(), $this->_hostingDir.'source.zip' ) ) {
				return Core_Data_Errors::getInstance()->setError('Can not extract source.');
			}
		} else {
			// упаковываем файлы из предыдущего шага в zip
			if ( true!==Core_Zip::getInstance()->open( $_strDir.'source.zip', ZipArchive::CREATE ) ) {
				return Core_Data_Errors::getInstance()->setError( 'Can\'t create zip arhive' );
			}
			if ( !Core_Zip::getInstance()->addDirAndClose( $this->_object->getSourceDir() ) ) {
				return Core_Data_Errors::getInstance()->setError( 'Can\'t add files to zip' );
			}
		}
		//if ( !copy( Zend_Registry::get( 'config' )->path->absolute->user_files.'sites'.DIRECTORY_SEPARATOR.'cnm-unzip.php', $_strDir.'cnm-unzip.php' ) ) {
		//	return Core_Data_Errors::getInstance()->setError('Can not copy cnm-unzip');
		//}
		if( !$this->_ssh->uploadDir( $_strDir , $this->_hostingDir ) ){
			return Core_Data_Errors::getInstance()->setError('Can not upload source');
		}
		$this->_ssh->unzip($this->_hostingDir.'source.zip',$this->_hostingDir);
		//$_host=substr($this->_object->getHttpHost(),0,32);
		//$this->_ssh->chownR($_host,$_host,$this->_hostRootDir);
		//$_info=$this->_object->getInfo();
		// распаковываем архив со скриптами сайта
		//Core_Curl::getResult( $_strRes, $_info['url'].'cnm-unzip.php' );
		return true;
	}

	public function download( $remoteFile, $localFile ){
		return $this->_ssh->download($remoteFile,$localFile);
	}

	public function readFile( &$strContent, $remoteFile ){
		$_strDir='Project_Placement_Transport_Internal@readFile';
		if ( !Zend_Registry::get( 'objUser' )->prepareTmpDir( $_strDir ) ) {
			return Core_Data_Errors::getInstance()->setError( 'Can\'t create dir '.$_strDir );
		}
		$_bool=$this->download( $this->_hostingDir.$remoteFile, $_strDir . md5($remoteFile).'.tmp' );
		Core_Files::getContent($strContent,$_strDir . md5($remoteFile).'.tmp');
		return $_bool;
	}

	public function saveFile( &$strContent, $remoteFile ){
		$_strDir='Project_Placement_Transport_Internal@saveFile';
		if ( !Zend_Registry::get( 'objUser' )->prepareTmpDir( $_strDir ) ) {
			return Core_Data_Errors::getInstance()->setError( 'Can\'t create dir '.$_strDir );
		}
		Core_Files::setContent( $strContent, $_strDir . md5($remoteFile).'.tmp');
		return $this->_ssh->uploadFile( $_strDir . md5($remoteFile).'.tmp', $this->_hostingDir.$remoteFile );
	}

	public function removeFile( $_remoteFile ){
		if( empty($_remoteFile) ){
			return false;
		}
		$this->_ssh->rmFile( $this->_hostingDir.$_remoteFile );
	}
	
	public function removeDir( $_removeDir ){
		if( empty( $_removeDir ) ){
			return false;
		}
		return $this->_ssh->rmDir( $this->_hostRootDir.$_removeDir );
	}

	public function dirScan( &$arrRes, $_strDir ){
		$_bool=$this->_ssh->dirScan($arrTmp, $this->_hostingDir.$_strDir);
		foreach( $arrTmp as $_fullPath=>$_array ){
			foreach( $_array as $_file ){
				$_pathinfo=pathinfo($_file);
				switch ($_pathinfo['extension']){
					case 'php':
					case 'css':
					case 'js':
					case 'html':
					case 'htm': break;
					default: continue 2; break;
				}
			}
			$arrRes[substr($_fullPath,stripos($_fullPath,$this->_hostingDir)+strlen($this->_hostingDir),strlen($_fullPath)-(stripos($_fullPath,$this->_hostingDir)+strlen($this->_hostingDir))).'/']=$_array;
		}
		return $_bool;
	}

	/**
	 * Чистим кэш в различных типах сайтов, на удаленном сервере это делает скрипт cnm-unzip.php
	 */
	private function clearCache(){
		if( $this->_ssh->isDir( $this->_hostingDir . 'magpie/cache') ){
			if( !$this->_ssh->cleanDir($this->_hostingDir . 'magpie/cache') ){
				return false;
			}
		}
		if ( $this->_ssh->isDir( $this->_hostingDir.'cache' ) ){
			$this->_ssh->cleanDir($this->_hostingDir . 'cache');
		}
		if ( $this->_ssh->isDir( $this->_hostingDir.'articles-list.txt' ) ) {
			if ( $this->_ssh->isDir( $this->_hostingDir.'datas/articles/' ) ) casheflush( glob( 'datas/articles/*' ), unserialize( file_get_contents('articles-list.txt') ) );
			if ( $this->_ssh->isDir( $this->_hostingDir.'articles/' ) ) casheflush( glob( 'articles/*' ), unserialize( file_get_contents('articles-list.txt') ) );
		}
	}

	/**
	* Обрывает коннект транспорта при необходимости
	*
	* @return void
	*/
	public function __destruct() {}

	public function browseDirs( &$arrDirs ) {
		$this->_ssh->dirForLs( $this->_hostingDir )->dirLs( $arrDirs );
		return !empty($arrDirs);
	}

	public function makeDir( $_strNewDir='' ) {
		$this->_ssh->mkDir( $this->_hostingDir.'/'.$_strNewDir );
		$_host=substr($this->_object->getHttpHost(),0,32);
		return $this->_ssh->chownR($_host,$_host,$this->_hostingDir.'/'.$_strNewDir);
	}

	public function getCurrentDir() {
		return $this->_object->getDir();
	}

	public function getPrevDir() {
		return $this->getPrev( $this->_object->getDir() );
	}

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