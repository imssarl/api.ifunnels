<?php


/**
 * File system methods
 */
class Core_Files {

	public static $fileInfo=array( 'filenameOnly'=>1, 'withSize'=>2, 'withFrendlySize'=>3, 'withMTime'=>4 );

	/**
	 * Рекурсивное сканирование директорий
	 *
	 * @param string  $arrRes - список поддиректорий с файлами и файлов в текущей директории
	 * @param string  $_strDir - директория
	 * @param integer  $_intInfo - детализация информации о файлах. см Core_Files::$fileInfo
	 * @return boolean
	 */
	public static function dirScan( &$arrRes, $_strDir='', $_intInfo=1 ) {
		if ( empty( $_strDir ) ) {
			return false;
		}
		$arrRes=array();
		$_iterator=new RecursiveDirectoryIterator( $_strDir, RecursiveDirectoryIterator::KEY_AS_PATHNAME );
		// in RecursiveIteratorIterator first file from RecursiveDirectoryIterator  is removed when use ssh
		$_iterator->key();
		$_iterator->rewind();
		// 
		foreach( new RecursiveIteratorIterator( $_iterator, RecursiveIteratorIterator::SELF_FIRST ) as $directory => $info ) {
			// учитываем только читаемые файлы но не ссылки
			if ( $info->isLink()||!$info->isReadable() ) {
				continue;
			}
			if ( substr( $info->getPathname(), -1 )=='.' ) { // для "." или ".." на конце. например ./dir1/dir2/.. TODO!!! 24.02.2012
				continue;
			}
			if ( substr_count( $info->getPathname(), '.svn' )>0 ) { // это как-бы фильтр TODO!!! 08.07.2009
				continue;
			}
			if ( $info->isDir() ) {
				if ( !isSet( $arrRes[$info->getPathname()] ) ) {
					$arrRes[$info->getPathname()]=array();
				}
			}
			if ($info->isFile()) {
				// надо как-то гибче сделать и добавить все возможности DirectoryIterator TODO!!! 25.10.2010
				switch( $_intInfo ) {
					case self::$fileInfo['filenameOnly']:
						$arrRes[$info->getPath()][]=$info->getFilename();
					break;
					case self::$fileInfo['withSize']:
						$arrRes[$info->getPath()][]=array(
							'name'=>$info->getFilename(),
							'size'=>$info->getSize(),
						);
					break;
					case self::$fileInfo['withFrendlySize']:
						$arrRes[$info->getPath()][]=array(
							'name'=>$info->getFilename(),
							'size'=>$info->getSize(),
							'frendly_size'=>Core_Files::byteToFrendly($info->getSize())
						);
					break;
					case self::$fileInfo['withMTime']:
						$arrRes[$info->getPath()][]=array(
							'name'=>$info->getFilename(),
							'size'=>$info->getSize(),
							'frendly_size'=>Core_Files::byteToFrendly( $info->getSize() ),
							'date'=>$info->getMTime(),
						);
					break;
				}
			}
		}
		return !empty( $arrRes );
	}

	/**
	 * Перевод байтов в человеко-понятную форму
	 * это вынести в плагин смарти TODO!!! 25.10.2010
	 *
	 * @param integer $_intSize - размер в байтах
	 * @return string
	 */
	public static function byteToFrendly( $_intSize=0 ) {
		if ( $_intSize<1024 ) {
			$_strSize=$_intSize.' Byte';
		} elseif ( $_intSize<1024*1024 ) {
			$_strSize=((int)($_intSize/1024)).' Kb';
		} else {
			$_strSize=((int)(($_intSize/1024)*100/1024)/100).' Mb';
		}
		return $_strSize;
	}

	/**
	 * Получить расширение файла
	 *
	 * @param string  $_str - путь с файлом
	 * @return string
	 */
	public static function getExtension( $_str='' ) {
		return pathinfo( $_str, PATHINFO_EXTENSION );
	}

	/**
	 * Получить имя файла
	 *
	 * @param string  $_str - путь с файлом
	 * @return string
	 */
	public static function getFileName( $_str='' ) {
		if ( version_compare( phpversion(), "5.2.0", "<" ) ) {
			$_arr=pathinfo( $_str );
			return ( empty( $_arr['extension'] )? $_arr['basename']:substr($_arr['basename'],0,strlen($_arr['basename'])-strlen($_arr['extension'])-1) );
		}
		return pathinfo( $_str, PATHINFO_FILENAME );
	}

	/**
	 * Получить имя файла с расширением
	 *
	 * @param string  $_str - путь с файлом
	 * @return string
	 */
	public static function getBaseName( $_str='' ) {
		return pathinfo( $_str, PATHINFO_BASENAME );
	}

	/**
	 * Получить путь до файла
	 *
	 * @param string  $_str - путь с файлом
	 * @return string
	 */
	public static function getDirName( $_str='' ) {
		if ( is_dir( $_str ) ) { // если путь без файла pathinfo вернёт без последней категории!!
			return $_str;
		}
		return pathinfo( $_str, PATHINFO_DIRNAME );
	}

	/**
	 * Получить массив со всеми каталогами, корень имеет самый большой индекс
	 *
	 * @param string  $_str - путь с файлом или просто путь
	 * @return array
	 */
	public static function getDirsOfPath( $_str='' ) {
		$_obj=Core_String::getInstance( self::getDirName( $_str ) );
		return array_reverse( $_obj->separate( (DIRECTORY_SEPARATOR=='/'?'\/':'\\\\') ) );
	}

	/**
	 * удаление файлов
	 *
	 * @param mixed $_mix in (array или string)
	 * @return boolean
	 */
	public static function rmFile( $_mix=array() ) {
		if ( is_array( $_mix ) ) {
			$_arrErr=array_map( array( 'Core_Files', 'rmFile' ), $_mix );
			if ( !in_array( false, $_arrErr ) ) {
				return true;
			}
		}
		if ( is_string( $_mix )&&is_file( $_mix ) ) {
			return unlink( $_mix );
		}
		return false;
	}

	/**
	 * рекурсивное удаление файлов, директорий
	 *
	 * @param mixed $_mix in (array или string)
	 * @return boolean
	 */
	public static function rmDir( $_mix=array() ) {
		if ( is_array( $_mix ) ) {
			$_arrErr=array_map( array( 'Core_Files', 'rmDir' ), $_mix );
			if ( !in_array( false, $_arrErr ) ) {
				return true;
			}
		}
		if ( is_string( $_mix )&&is_dir( $_mix ) ) {
			if ( self::dirScan( $_arr, $_mix ) ) {
				$_arr=array_reverse( $_arr );
				foreach( $_arr as $_strDir=>$_arrFiles ) {
					foreach( $_arrFiles as $_strFile ) {
						self::rmFile( $_strDir.DIRECTORY_SEPARATOR.$_strFile );
					}
					@rmdir( $_strDir );
				}
			}
			@rmdir( $_mix );
			return true;
		}
		return false;
	}

	/**
	 * получение содиржимого файла
	 *
	 * @param string $strContent out
	 * @param string $_strFile in
	 * @return boolean
	 */
	public static function getContent( &$strContent , $_strFile='' ) {
		if ( empty( $_strFile ) ) {
			return false;
		}
		$strContent=@file_get_contents( $_strFile );
		return $strContent!==false;
	}

	/**
	 * запись содержимого в файл
	 *
	 * @param string $strContent in
	 * @param string $_strFile in
	 * @return boolean
	 */
	public static function setContent( &$strContent, $_strFile='' ) {
		if ( empty( $_strFile ) ) {
			return false;
		}
		$_intBytes=@file_put_contents( $_strFile, $strContent );
		return $_intBytes!==false;
	}

	public static function addContent( $_strContent, $_strFile='', $_fseek_set=false ){
		if(empty($_strFile)){
			return false;
		}
		$_f=fopen($_strFile,'a');
		if(!empty($_fseek_set)){
			fseek($_f,$_fseek_set);
		}
		$res=fwrite($_f, $_strContent, strlen($_strContent) );
		fclose($_f);
		return $res;
	}

	/**
	 * массовое создание файлов в директории
	 *
	 * @param array $arrFiles in array( 'fileName1'=>content, ... )
	 * @param string $strDest in папка в которой создавать файлы
	 * @return boolean
	 */
	public static function setContentMass( &$arrFiles, $strDest='' ) {
		if ( empty( $strDest )||empty( $arrFiles )||!is_array( $arrFiles ) ) {
			return false;
		}
		foreach( $arrFiles as $k=>$v ) {
			if ( !self::setContent( $v, $strDest.$k ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * приходящие данные добавляются в файл сообщений
	 * на вермя добавления файл блокируется
	 *
	 * @param string $_str in (.\path\to\file.ext - for win* path)
	 * @return string (/path/to/file.ext - for html client)
	 */
	public static function getWebPath( $_str='' ) {
		if ( empty( $_str ) ) {
			return '';
		}
		return str_replace( '\\', '/' , substr( $_str, 1 ) );
	}

	/**
	 * приходящие данные добавляются в файл сообщений
	 * на вермя добавления файл блокируется
	 *
	 * @param mixed $_mix in (array или string)
	 * @return boolean
	 */
	public static function devMess( $_mix=array() ) {
		if ( empty( $_mix ) ) {
			$_mix='empty data come to devMess';
		} elseif ( is_array( $_mix ) ) {
			$_mix=print_r( $_mix, true );
		}
		$_strHead=str_repeat( '-=', 25 ).' added on '.date( 'c' );
		$_intBytes=@file_put_contents( 
			Zend_Registry::get( 'config' )->path->relative->root.'debmes.txt', 
			$_strHead.str_repeat( PHP_EOL, 2 ).$_mix.str_repeat( PHP_EOL, 2 ), 
			FILE_APPEND | LOCK_EX );
		return $_intBytes!==false;
	}

	/**
	 * отгрузка файлов клиенту
	 * поддерживается докачка файла
	 *
	 * @param string $_strFile in путь к файлу и его имя
	 * @param string $_strMime in mime тип файла
	 * @return boolean
	 */
	public static function download( $_strFile='', $_strMime='application/octet-stream' ) {
		if ( !file_exists( $_strFile ) ) {
			return false;
		}
		header( 'HTTP/1.1 '.(isSet( $_SERVER['HTTP_RANGE'] )? '206 Partial Content':'200 Ok') );
		header( 'X-Powered-By: PHP/'.phpversion() );
		header( 'Connection: close' );
		header( 'Content-Type: '.$_strMime );
		header( 'Accept-Ranges: bytes');
		if ( ( $_time=filemtime( $_strFile ) )==false ) {
			$_time='now';
		}
		header( 'Last-Modified: '.Core_Datetime::getInstance()->toTimezone(
			Zend_Registry::get( 'config' )->date_time->dt_zone, 'GMT', $_time, 'D, d M Y H:i:s \G\M\T' ) );
		header( 'Date: '.Core_Datetime::getInstance()->toTimezone(
			Zend_Registry::get( 'config' )->date_time->dt_zone, 'GMT', 'now', 'D, d M Y H:i:s \G\M\T' ) );
		$_intSize=filesize( $_strFile );
		header( 'Content-Length: '.$_intSize );
		if ( isSet( $_SERVER['HTTP_RANGE'] ) ) {
			preg_match( "/bytes=(\d+)-/", $_SERVER['HTTP_RANGE'], $m );
			$_intTmpSize=$_intSize-intval( $m[1] );
			$p1=$_intSize-$_intTmpSize;
			$p2=$_intSize-1;
			$p3=$_intSize;
			header( 'Content-Range: bytes '.$p1.'-'.$p2.'/'.$p3 );
		}
		$etag=md5( $_strFile );
		$etag=substr( $etag, 0, 8 ).'-'.substr( $etag, 8, 7 ).'-'.substr( $etag, 15, 8 );
		header('ETag: "'.$etag.'"');
		header( 'Content-Disposition: attachment; filename="'.basename( $_strFile ).'";' ); // This line causes the browser's "save as" dialog
		// отдаём файл
		$_fp=fopen( $_strFile, "rb" );
		if ( !empty( $p1 ) ) {
			fseek( $_fp, $p1 );
		}
		$downloaded=0;
		set_time_limit(0); //reset time limit for big files
		while( !feof( $_fp )&&!connection_status()&&( $downloaded<$_intSize ) ) {
			echo fread( $_fp, 512000 );
			$downloaded+=512000;
			flush();
		}
		fclose( $_fp );
		return true;
	}

	/**
	 * Рекурсивно скопировать директорию со всеми файлами
	 * @static
	 * @param  $source
	 * @param  $target
	 * @return void
	 */
	public static function dirCopy($source, $target) {
  		if ( is_dir($source) ) {
    		@mkdir($target);
    		$d = dir($source);
    		while (false !== ($entry = $d->read())) {
      			if ($entry == '.' || $entry == '..') continue;
				$source=rtrim($source,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
				$target=rtrim($target,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
      			if( !self::dirCopy($source.$entry, $target.$entry) ){
					 return Core_Data_Errors::getInstance()->setError('Can\'t copy '.$source.$entry.' in '.$target.$entry);
				}
    		}
    		$d->close();
  		} else {
			return copy( $source, $target );
		}
		return true;
	}
}
?>