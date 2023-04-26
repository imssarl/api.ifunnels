<?php


/**
 * ZipArchive standart php class extension
 */
class Core_Zip extends ZipArchive implements Core_Singleton_Interface {

	/**
	 * хранилище объекта для реализации Singleton паттерна
	 *
	 * @var Core_Zip object
	 */
	private static $_instance=NULL;

	/**
	 * директорию в которую разархивируем
	 *
	 * @var string
	 */
	protected $_dir='';

	/**
	 * корень папок который будет в архиве переменная должна оканчиватся на / (см. setRoot)
	 *
	 * @var string
	 */
	protected $_root='';

	/**
	 * Root of zip file setter.
	 * приводит к виду dir/dir/dir/
	 *
	 * @param string  $_str
	 * @return self
	 */
	public function setRoot( $_str='' ) {
		if ( empty( $_str ) ) {
			return $this;
		}
		if ( substr( $_str, -1 )!='/' ) {
			$_str.='/'; // если пришло dir/dir => dir/dir/
		}
		if ( substr( $_str, 0, 1 )=='/' ) {
			$_str=substr( $_str, 1 ); // если пришло /dir/dir/ => dir/dir/
		}
		$this->_root=$_str;
		return $this;
	}

	/**
	 * Часто требуется добавить директорию и закрыть файл
	 *
	 * @param string $_str - путь до папки (может оканчиватся на слэш может не оканчиватся)
	 * @return boolean
	 */
	public function addDirAndClose( $_dirName='' ) {
		if ( !$this->addDirectory( $_dirName ) ) {
			return false;
		}
		return $this->close();
	}

	/**
	 * Добавление папки в архив рекурсивно
	 *
	 * @param string $_str - путь до папки (может оканчиватся на слэш может не оканчиватся)
	 * @return boolean
	 */
	public function addDirectory( $_dirName='' ) {
		if ( empty( $_dirName ) ) {
			return false;
		}
		if ( substr( $_dirName, -1 )!=DIRECTORY_SEPARATOR ) {
			$_dirName.=DIRECTORY_SEPARATOR;
		}
		$_arrDirName=Core_Files::getDirsOfPath( $_dirName );
		if ( !Core_Files::dirScan( $_arrDirContent, $_dirName ) ) {
			return false;
		}
		foreach( $_arrDirContent as $_strDir=>$_arrFiles ) {
			$_arrStrDir=Core_Files::getDirsOfPath( $_strDir );
			$_arrLocalDir=array_diff_assoc( array_reverse($_arrStrDir), array_reverse($_arrDirName) );
			// / а не DIRECTORY_SEPARATOR потому что например dUnzip2 не понимает виндовый слэш, может ещё какие-то скрипты 13.07.2009
			$_strLocalDir=$this->_root.(empty( $_arrLocalDir )? '':implode( '/', $_arrLocalDir ).'/');
			if ( !empty( $_arrLocalDir ) ) {
				$this->addEmptyDir( $_strLocalDir );
			}
			foreach( $_arrFiles as $v ) {
				if ( !$this->addFile( realpath( $_strDir.DIRECTORY_SEPARATOR.$v ), $_strLocalDir.$v ) ) {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * Папка в которую распаковывает extractZip
	 *
	 * @param string  $_str
	 * @return self
	 */
	public function setDir( $_str='' ) {
		$this->_dir=$_str;
		return $this;
	}

	/**
	 * opens a stream to a ZIP archive file. calls the ZipArchive::open() internally.
	 * overwrites ZipArchive::open() to add fix 49072 bug 
	 * (see http://bugs.php.net/bug.php?id=49072 (5.2.12 still exists))
	 *
	 * @param string $_str
	 * @param int $flags (default values need for 5.3.x vrsion for WinOS)
	 * return mixed
	 */
	public function open( $_str='', $flags=ZIPARCHIVE::CREATE ) {
		if ( empty( $_str ) ) {
			return false; // нет имени файла
		}
		if ( empty( $flags ) ) {
			$_strOut=shell_exec( 'unzip -t "'.$_str.'"' );
			$_arrTest=explode( 'No errors detected in compressed data', $_strOut );
			if ( count( $_arrTest )==1 ) {
				return ZIPARCHIVE::ER_CRC; // corrupted zip file
			}
		}
		return parent::open( $_str, $flags );
	}

	/**
	 * Разархивация в нужную папку
	 *
	 * @param string  $_str - путь до архива
	 * @return boolean
	 */
	public function extractZip( $_str='' ) {
		if ( empty( $this->_dir ) ) {
			return false;
		}
		if ( true!==$this->open( $_str ) ) {
			return false;
		}
		$_bool=$this->extractTo( $this->_dir );
		$this->close();
		return $_bool;
	}

	/**
	 * Бережём память. Singleton паттерн
	 *
	 * @return Core_Zip object
	 */
	public static function getInstance() {
		if ( self::$_instance==NULL ) {
			self::$_instance=new self();
		}
		return self::$_instance;
	}
}
?>