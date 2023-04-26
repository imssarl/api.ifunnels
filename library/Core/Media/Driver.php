<?php


/**
 * Low-level file system function
 */


class Core_Media_Driver extends Core_Services implements Core_Singleton_Interface {

	private static $_instance=NULL;

	public static function getInstance() {
		if ( self::$_instance==NULL ) {
			self::$_instance=new Core_Media_Driver();
		}
		return self::$_instance;
	}


	/**
	* запрещённые к заливке расширения файлов
	* @public array
	*/
	public $m_ext_disallowed=array( 'exe', 'bat', 'msi', 'sh', 'htaccess', '' );
	/**
	* разрешённые к заливке расширения файлов
	* @public array
	*/
	public $m_ext_allowed=array(
		array( 'zip', 'rar', 'arj', 'doc', 'xls', 'pdf', 'txt', 'cfg' ),
		array( 'mp3', 'wav', 'aiff', 'fla'/*flash audio*/ ),
		array( 'mp4', 'm4v', 'mov', '3gp', 'wmv', 'rm', 'ram', 'avi', 'asf', 'mpg', 'mpeg', 'flv'/*flash video*/ ),
		array( 'gif', 'jpg', 'jpeg', 'jpe', 'bmp', 'png', 'tif', 'tiff' ),
		array( 'swf' ),
	);
	/**
	* mime-type файлов стркутура соответствует $this->m_ext_allowed
	* @public array
	*/
	public $m_mime_allowed=array(
		array(), array(), array(),
		array( 'image/gif', 'image/jpeg', 'image/jpeg', 'image/jpeg', 'image/bmp', 'image/png', 'image/tiff', 'image/tiff' ),
		array( 'application/x-shockwave-flash' ),
	);
	/**
	* системные типы файлов
	* @public array
	*/
	public $m_types=array( 'other', 'audio', 'video', 'image', 'flash' );
	/**
	* тип файла как индекс из $this->m_types
	* @public integer
	*/
	public $m_type=0;
	/**
	* тип файла как текстовая метка
	* @public integer
	*/
	public $m_type_txt=0;
	/**
	* системное имя текущего файла
	* @public string
	*/
	public $m_sys_name='';
	/**
	* расширение текущего файла
	* @public string
	*/
	public $m_sys_ext='';
	/**
	* системное имя и расширение текущего файла
	* @public string
	*/
	public $m_sys_file='';
	/**
	* под-директории для хранения файлов по типам стркутура соответствует $this->m_types
	* @public array
	*/
	public $m_folders=array( 'other', 'audio', 'video', 'image', 'other' );
	/**
	* пути для удаления или исрлдбзовать $this->m_folders? TODO!!!
	* @public array
	*/
	public $m_delpath=array();
	/**
	* пути до файлов-оригиналов в системе
	* @public array
	*/
	public $m_dirs_path=array();
	/**
	* пути до файлов-оригиналов в браузере
	* @public array
	*/
	public $m_urls_path=array();
	/**
	* текущий путь до файла-оригинала в системе
	* @public array
	*/
	public $m_dir='';
	/**
	* текущий путь до файла-оригинала в браузере
	* @public array
	*/
	public $m_url='';
	/**
	* печать на консоль результатов выполнения шелл-комманд, если false то нет
	* @public boolean
	*/
	public $m_gebug=false;
	/**
	* контейнер для логгированных действий (например шелл-комманд)
	* @public array
	*/
	public $m_activiylog=array();
	/**
	* результаты выполнения шелл-комманд (если команда выполнялась с >&/dev/null то естественно тут будет пусто)
	* @public array
	*/
	public $m_executeout=array();

	public $m_imgtumb_width=0;

	public $m_imgtumb_height=0;
	/**
	* constructor
	* @param array $_arrSet in - настройки
	* @return void
	*/
	public function __construct( $_arrSet=array() ) {
		$this->config=Zend_Registry::get( 'config' );
		foreach( $this->m_folders as $k=>$v ) {
			$this->m_dirs_path[$k]=$this->config->path->relative->media.$v.DIRECTORY_SEPARATOR;
			$this->m_urls_path[$k]=$this->config->path->html->media.$v.'/';
		}
		$this->d_set_typebytypename( @$_arrSet['flg_type'] );
	}
	/**
	* Устанавливает тип файла ($this->m_type) по строковой переменной, описывающией тип
	* @param string $_strType in
	* @return boolean
	*/
	public function d_set_typebytypename( $_strType='' ) {
		if ( empty( $_strType ) ) {
			return false;
		}
		if ( ( $_intKey=array_search( $_strType, $this->m_types ) )!==false ) {
			$this->m_type=$_intKey;
			$this->m_type_txt=$this->m_types[$this->m_type];
			return true;
		}
		return false;
	}
	/**
	* Устанавливает тип файла ($this->m_type) по строковой переменной, содержащей имя файла с расширением
	* @param string $_strName in
	* @return boolean
	*/
	public function d_set_typebyfilename( $_strName='' ) {
		$_strName=empty( $_strName ) ? $this->m_sys_file:$_strName;
		if ( empty( $_strName ) ) {
			return false;
		}
		if ( empty( $this->m_sys_ext )&&!$this->d_get_extension( $_strName ) ) {
			return false;
		}
		foreach( $this->m_ext_allowed as $k=>$v ) {
			if ( in_array( $this->m_sys_ext, $v ) ) {
				$this->m_type=$k;
				$this->m_type_txt=$this->m_types[$this->m_type];
				return true;
			}
		}
		return false;
	}
	/**
	* Устанавливает расширение файла ($this->m_sys_ext) по строковой переменной, содержащей имя файла с расширением
	* @param string $_strName in
	* @return boolean
	*/
	public function d_get_extension( $_strName='' ) {
		$_strName=empty( $_strName ) ? $this->m_sys_file:$_strName;
		if ( empty( $_strName ) ) {
			return false;
		}
		$this->m_sys_ext=strtolower( substr( strrchr( $_strName, '.' ), 1 ) );
		return !empty( $this->m_sys_ext );
	}
	/**
	* Устанавливает имя файла ($this->m_sys_ext) по строковой переменной, содержащей имя файла с расширением
	* @param string $_strName in
	* @return boolean
	*/
	public function d_get_filename( $_strName='' ) {
		$_strName=empty( $_strName ) ? $this->m_sys_file:$_strName;
		if ( empty( $_strName ) ) {
			return false;
		}
		$this->m_sys_name=str_replace( strrchr( $_strName, '.' ), '', $_strName );
		return !empty( $this->m_sys_name );
	}
	/**
	* Пути до оригиналов для текушего типа файлов ($this->m_type)
	* @return void
	*/
	public function d_get_path() {
		$this->m_dir=$this->m_dirs_path[$this->m_type];
		$this->m_url=$this->m_urls_path[$this->m_type];
	}
	/**
	* собирает всю возможную инфу о файле
	* @param string $_strName in
	* @return boolean
	*/
	public function d_get_fileinfo( $_strName ) {
		$_strName=empty( $_strName ) ? $this->m_sys_file:$_strName;
		if ( empty( $_strName ) ) {
			return false;
		}
		if ( !$this->d_get_extension( $_strName )||!$this->d_get_filename( $_strName ) ) {
			return false;
		}
		$this->m_sys_file=$this->m_sys_name.'.'.$this->m_sys_ext;
		$this->d_get_path();
		return true;
	}
	/**
	* Генератор уникальных системных имён файлов ($this->m_sys_name)
	* @return void
	*/
	public function d_get_newname() {
		$i=0;
		do {
			$this->m_sys_name=Core_A::rand_string( 9 );
			$i++;
			if ( $i>20 ) {
				trigger_error( $this->config->debugging->error_type->err_filesys.'|can\'t generate m_sys_file' );
			}
			$this->m_sys_file=$this->m_sys_name.'.'.$this->m_sys_ext;
		} while ( file_exists( $this->m_dir.$this->m_sys_file ) );
	}
	/**
	* Дописывает в конец файла с блокировкой
	* например для debmes.txt и различных логов
	* @param string $_strPath in - путь до файла
	* @param string $_strFile in - имя файла с расширением
	* @param string $_strContent in - контент для записи
	* @param string $_strMode in - в каком режиме открыть файл
	* @return boolean
	*/
	function d_addtofile( $_strPath='', $_strFile='', $_strContent='', $_strMode='a' ) {
		if ( empty( $_strPath )||empty( $_strFile ) ) {
			return false;
		}
		$_fp=fopen( $_strPath.$_strFile, $_strMode );
		$i=0;
		while ( !flock( $_fp, LOCK_EX ) ) {
			$i++;
			if ( $i>20 ) {
				trigger_error( $this->config->debugging->error_type->err_filesys.'|can\'t get access to file '.$_strPath.$_strFile );
				return false;
			}
			sleep(1);
		}
		fwrite( $_fp, $_strContent );
		flock( $_fp, LOCK_UN ); 
		fclose( $_fp );
		return true;
	}

	function d_get_mimetypebyext( $_strFile='', &$strMime ) {
		if ( !$this->d_set_typebyfilename( $_strFile ) ) { // берём расширение и тип файла
			$this->m_sys_ext='';
			return false;
		}
		if ( ( $_intKey=array_search( $this->m_sys_ext, $this->m_ext_allowed[$this->m_type] ) )===false ) { // такого расширения в системе нету
			$this->m_sys_ext='';
			return false;
		}
		if ( empty( $this->m_ext_allowed[$this->m_type][$_intKey] ) ) { // такого mime в системе нету
			return false;
		}
		$strMime=$this->m_ext_allowed[$this->m_type][$_intKey];
		return !empty( $strMime );
	}

	function d_getmimetype( $_strPath='', $_strFile='', &$strMime ) {
		if ( empty( $_strPath )||empty( $_strFile ) ) {
			return false;
		}
		$strMime=mime_content_type( $_strPath.$_strFile );
		if ( empty( $strMime )&&!$this->d_get_mimetypebyext( $_strFile, $strMime ) ) {
			return false;
		}
		return !empty( $strMime );
	}

	function d_readfromfile( $_strPath='', $_strFile='', &$strContent ) {
		if ( empty( $_strPath )||empty( $_strFile )||!file_exists( $_strPath.$_strFile ) ) {
			return false;
		}
		if ( !( $_fp=fopen( $_strPath.$_strFile, 'r' ) ) ) {
			return false;
		}
		$strContent=fread( $_fp, filesize( $_strPath.$_strFile ) );
		fclose( $_fp );
		return !empty( $strContent );
	}

	function d_get_external_file( &$strSrc ) {
		if ( empty( $strSrc )||substr( $strSrc, 0, 7 )!='http://' ) {
			return false;
		}
		$_arr=explode( '/', $strSrc );
		$_strNewSrc=$_arr[count($_arr)-1];
		if ( file_exists( $this->config->path->relative->mctmp.$_strNewSrc ) ) {
			$strSrc=$_strNewSrc;
			return true;
		}
		if ( !( $strImg=@file_get_contents( $strSrc ) ) ) {
			//trigger_error( $this->config->debugging->error_type->err_filesys.'|Can\'t get remote file: '.$strSrc );
			return false;
		}
		if ( !$this->d_addtofile( $this->config->path->relative->mctmp, $_strNewSrc, $strImg ) ) {
			return false;
		}
		$strSrc=$_strNewSrc;
		return true;
	}

	/**
	* Генерирует превью для картинок на лету
	* результат возвращается в img src как изображение
	* @param array $_arrSet in - настройки
	* @return content
	*/
	public function d_tumbonthefly( $_arrSet=array() ) {
		if ( empty( $_arrSet['src'] ) ) { // если неуказан src
			$this->m_sys_file=$this->config->fsdriver->noimage;
		} else {
			if ( $this->d_get_external_file( $_arrSet['src'] ) ) {
				$_arrSet['e']=1;
			}
			$this->d_set_typebyfilename( $_arrSet['src'] );
			$this->m_sys_file=$this->m_type_txt!='image' ? $this->config->fsdriver->noimage:$_arrSet['src']; // если это не картинка
			if ( isSet( $_arrSet['e'] ) ) {
				$this->m_dir=$this->config->path->relative->mctmp;
			} elseif ( isSet( $_arrSet['o'] ) ) {
				$this->m_dir=$this->config->path->relative->media;
			} else {
				$this->d_get_path();
			}
			$this->m_sys_file=$this->m_dir.$this->m_sys_file;
			if ( !file_exists( $this->m_sys_file ) ) {
				$this->m_sys_file=$this->config->fsdriver->noimage;
			}
		}
		$this->d_set_imgtumb_size( $_arrSet['w'], $_arrSet['h'] );
		if ( !$this->d_tumbmorf( $_strFile, $this->m_sys_file ) ) {
			header( 'HTTP/1.1 404 Object Not Found' );
		} else {
			header( 'Content-Type: image/jpeg' );
			readfile( $_strFile );
		}
		exit;
	}


	public function getThumbnail( $_strSrc='', $_intW=0, $_intH=0 ) {
		$_strSrc='.'.$_strSrc;
		if ( !$this->d_set_typebyfilename( $_strSrc )||$this->m_type_txt!='image' ) {
			$_strSrc=$this->config->path->relative->img.$this->config->fsdriver->noimage;
		}
		$this->d_set_imgtumb_size( $_intW, $_intH );
		if ( !$this->d_tumbmorf( $_strFile, $_strSrc ) ) {
			return str_replace( $this->config->path->relative->img.'backend'.DIRECTORY_SEPARATOR, $this->config->path->html->img.'backend/', $_strSrc );
		}
		return str_replace( $this->config->path->relative->tumb_cache, $this->config->path->html->tumb_cache, $_strFile );
	}

	/**
	* сеттер который устанавливает размеры к которым будет преобразовываться файл
	* @param integer $_intWidth in
	* @param integer $_intHeight in
	* @return void
	*/
	private function d_set_imgtumb_size( $_intWidth=0, $_intHeight=0 ) {
		$this->m_imgtumb_width=(int)$_intWidth;
		$this->m_imgtumb_height=(int)$_intHeight;
		if ( empty( $this->m_imgtumb_width )||$this->m_imgtumb_width>$this->config->fsdriver->maxw ) {
			$this->m_imgtumb_width=$this->config->fsdriver->nw;
		}
		if ( empty( $this->m_imgtumb_height )||$this->m_imgtumb_height>$this->config->fsdriver->maxh ) {
			$this->m_imgtumb_height=$this->config->fsdriver->nh;
		}
	}
	/**
	* генерит новую имагу, либо берёт из кэша
	* @param string $strCurFile out - путь и имя файла в кэше
	* @param string $_strNewFile in - путь и имя запрашиваемого файла
	* @return boolean
	*/
	private function d_tumbmorf( &$strCurFile, $_strNewFile ) {
		if ( $this->d_check_tumbcashe( $strCurFile, $_strNewFile ) ) {
			return true;
		}
		// create
		if ( $this->config->fsdriver->method==1 ) { // gd
			$this->d_gd_morf( $_strNewFile, $strCurFile );
		} elseif ( $this->config->fsdriver->method==2 ) { // im
			$this->d_im_morf( $_strNewFile, $strCurFile );
		}
		// $this->d_cleanup_tumbcashe(); // надо вешать на крон по хорошему, если сразу много картинок генерить то слишком часто проверяется кэш и это дико тормозит
		return true;
	}
	
	/**
	 * Set size for file: array('width'=>...,'height'=>...);
	 *
	 * @param array $arrSize
	 * @param string $strFile
	 */
	public function setSize( $arrSize, $strFile='' ){
		if (empty( $arrSize )){
			throw new Exception( Core_Errors::DEV.'| can\'t find size' );
		}
		
		if ( is_file($strFile) ){
			$tmp=getimagesize($strFile);
			$width=($arrSize['width']>$tmp[0])?$tmp[0]:$arrSize['width'];
			$height=($arrSize['height']>$tmp[1])?$tmp[1]:$arrSize['height'];
		} else {
			$width=$arrSize['width'];
			$height=$arrSize['height'];
		}
		$this->m_imgtumb_width=$width;
		$this->m_imgtumb_height=$height;
		return $this;
	}
	
	/**
	* генерит новую имагу с помощью gd_library
	* @param string $_strCurFile in - путь и имя запрашиваемого файла
	* @param string $_strNewFile in - путь и имя файла в кэше, который хотим сгенерить
	* @return boolean
	*/
	function d_gd_morf( $_strCurFile='', $_strNewFile='' ) {
		if ( empty( $_strCurFile )||empty( $_strNewFile ) ) {
			return false;
		}
		list( $_intOw, $_intOh )=getimagesize( $_strCurFile );
		$_intH = $this->m_imgtumb_height;
		$_intW = $this->m_imgtumb_width;
		if ($_intOw < $_intOh) {
			$_intH = ($this->m_imgtumb_width / $_intOw) * $_intOh;
		} else {
			$_intW = ($this->m_imgtumb_height / $_intOh) * $_intOw;
		}
		if ($_intW < $this->m_imgtumb_width) { //if the width is smaller than supplied thumbnail size
			$_intW = $this->m_imgtumb_width;
			$_intH = ($this->m_imgtumb_width/ $_intOw) * $_intOh;;
		}
		if ($_intH < $this->m_imgtumb_height) { //if the height is smaller than supplied thumbnail size
			$_intH = $this->m_imgtumb_height;
			$_intW = ($this->m_imgtumb_height / $_intOh) * $_intOw;
		}
		if ( !function_exists( 'imagecreatetruecolor' ) ) {
			trigger_error( $this->config->debugging->error_type->err_filesys.'|gd-library not installed' );
		}
		// ресайзим
		$_hdl=imagecreatetruecolor( $_intW, $_intH );
		$_shdl=imagecreatefromstring( file_get_contents( $_strCurFile ) );
		imagecopyresampled( $_hdl, $_shdl, 0, 0, 0, 0, $_intW, $_intH, $_intOw, $_intOh );
		// поправка
		$w1 =($_intW/2) - ($this->m_imgtumb_width/2);
		$h1 = ($_intH/2) - ($this->m_imgtumb_height/2);
		$_hdl2 = imagecreatetruecolor($this->m_imgtumb_width , $this->m_imgtumb_height);
		imagecopyresampled($_hdl2, $_hdl, 0,0, $w1, $h1,	$this->m_imgtumb_width , $this->m_imgtumb_height ,$this->m_imgtumb_width, $this->m_imgtumb_height);
		// результат
		imagedestroy( $_shdl );
		imagedestroy( $_hdl );
		$_bool=@imagejpeg( $_hdl2, $_strNewFile, $this->config->fsdriver->quality );
		/*if ( $_strCurFile=='.\skin\i\backend\0.gif' ) {
			die( $_strCurFile );
		}*/
		imagedestroy( $_hdl2 );
		return $_bool;
	}
	/**
	* генерит новую имагу с помощью image magic
	* @param string $_strCurFile in - путь и имя запрашиваемого файла
	* @param string $_strNewFile in - путь и имя файла в кэше, который хотим сгенерить
	* @return boolean
	*/
	function d_im_morf( $_strCurFile='', $_strNewFile='' ) {
		if ( empty( $_strCurFile )||empty( $_strNewFile ) ) {
			return false;
		}
		list( $_intOw, $_intOh )=getimagesize( $_strCurFile );
		// увеличиваем по меньшей стороне и кропим по центру
		$_wPrc=$_intOw/$this->m_imgtumb_width*100;
		$_hPrc=$_intOh/$this->m_imgtumb_height*100;
		if ( $_hPrc>$_wPrc ) {
			$_strSize=$this->m_imgtumb_width;
		} else {
			$_strSize='x'.$this->m_imgtumb_height;
		}
		return $this->shell_execute( $_arrRet, 
			$this->config->path->utilites->image_magic.'convert -quality '.
			$this->config->fsdriver->quality.' -antialias -resize '.$_strSize.' -gravity center -crop '.
			$this->m_imgtumb_width.'x'.$this->m_imgtumb_height.'+0+0 "'.$_strCurFile.'"[0] "'.$_strNewFile.'"' );
	}
	
	/**
	* проверка наличия превью в кэше
	* @param string $strRes out
	* @param mixed $_arrSet in
	* @return boolean
	*/
	private function d_check_tumbcashe( &$strRes, $_strFileName ) {
		if ( empty( $this->config->fsdriver->cashing ) ) {
			return false;
		}
		$strRes=$this->config->path->relative->tumb_cache.md5( $_strFileName.filemtime( $_strFileName ).$this->m_imgtumb_width.$this->m_imgtumb_height ).'.pic';
		if ( !file_exists( $strRes ) ) {
			return false;
		}
		return true;
	}
	/**
	* очистка кэша от старых файлов
	* @param none
	* @return boolean
	*/
	private function d_cleanup_tumbcashe() {
		if ( !( $_hdl=openDir( $this->config->path->relative->tumb_cache ) ) ) {
			return false;
		}
		while ( ( $_strFile=readDir( $_hdl ) )!== false ) {
			if ( in_array( $_strFile, array( '.', '..' ) )||is_dir( $this->config->path->relative->tumb_cache.$_strFile )||
				time()-filemtime( $this->config->path->relative->tumb_cache.$_strFile )<$this->config->fsdriver->exp ) {
				continue;
			}
			$this->d_rmfile( $this->config->path->relative->tumb_cache.$_strFile );
		}
		closeDir( $_hdl );
		return true;
	}

	public function d_getvideoinfo( &$arrRes, $_strSrc='' ) {
		if ( empty( $_strSrc ) ) {
			return false;
		}
		if ( !$this->shell_execute( $_arrRet, 'echo `'.$this->config->path->utilites->ffmpeg.'ffmpeg -i '.$_strSrc.' -vstats 0>&1 2>&1`' ) ) {
			$arrRes['err']=$_arrRet;
			return false;
		}
		// duration of video file "Duration: %02d:%02d:%02d.%01d" - формат длительности в ffmpeg
		if ( !preg_match( "/Duration:\s(\d{2}):(\d{2}):(\d{2}\.\d|\d{2})/i", join( ' ', $_arrRet ), $_arrD ) ) {
			$arrRes['err']='clip duration not recognized at '.$_strSrc;
			return false;
		}
		// если это надо будет то будем добавлять поля в БД
		/*if ( !preg_match( "/video:\s[^,]*,[^,]*,\s(\d+)x(\d+)/i", join( ' ', $_arrRet ), $_arrS ) ) {
			$arrRes['err']='clip size not recognized at  '.$_strSrc;
			return false;
		}*/
		$arrRes=array(
			'duration_sec'=>( intval( $_arrD[1] )*3600+intval( $_arrD[2] )*60+round( floatval( $_arrD[3] ) ) ),
			'duration_str'=>$_arrD[1].':'.$_arrD[2].':'.round( floatval( $_arrD[3] ) ),
		);
		return true;
	}

	public function d_getvideoframe( $_strInFile='', $_strOutDir='', $_strTumbName='', $_intDuration=0 ) {
		if ( empty( $_strInFile )||empty( $_strOutDir )||empty( $_strTumbName )||empty( $_intDuration ) ) {
			return false;
		}
		// setting
		$num_of_tumbs=4;
		$tumb_formats=array(
			'thumb_100x63'=>array( 'Nw'=>100, 'Nh'=>63 ),
			'thumb_400x290'=>array( 'Nw'=>400, 'Nh'=>290 ),
		);
		$_intStep=intval( $_intDuration/$num_of_tumbs );
		if ( $_intStep<1 ) {
			$_intStep=1;
		}
		// генерация
		$i=0;
		do {
			// сохраняем в темп превьюхи по 25 на каждую часть
			if ( !$this->shell_execute( $_arrRet, $this->config->path->utilites->ffmpeg.'ffmpeg -i '.$_strInFile.' -an -ss '.$i*$_intStep.' -an -vframes 25 -y '.$this->config->path->relative->mctmp.$_strTumbName.'-%d.jpeg' ) ) {
				$arrRes['err']=$_arrRet;
				return false;
			}
			// выбираем самый насыщенный и удаляем остальные
			$_arrFiles=glob( $this->config->path->relative->mctmp.$_strTumbName.'-*.jpeg' );
			if ( empty( $_arrFiles ) ) {
				return false;
			}
			$_arrBig=array();
			foreach ( $_arrFiles as $src ) {
				$_arrTmp=array( 'name'=>$src, 'size'=>filesize( $src ) );
				if ( empty( $_arrBig['size'] )||$_arrBig['size']<$_arrTmp['size'] ) {
					if ( !empty( $_arrBig['size'] ) ) {
						unlink( $_arrBig['name'] );
					}
					$_arrBig=$_arrTmp;
				} else {
					unlink( $_arrTmp['name'] );
				}
			}
			// приводим кадр ко всем нужным форматам и складываем в папку
			foreach( $tumb_formats as $k=>$v ) {
				$this->d_set_imgtumb_size( $v['Nw'], $v['Nh'] );
				$this->d_im_morf( $_arrBig['name'], $_strOutDir.$k.'/'.$_strTumbName.'-'.($i+1).'.jpg' );
			}
			unlink( $_arrBig['name'] );
			$i++;
		} while ( $i<$num_of_tumbs );
		return true;
	}
	/**
	* генерит картинку с буквено цифровым кодом
	* @return content
	*/
	public function d_random_txtimg() {
		if ( !function_exists( 'imagecreatetruecolor' ) ) {
			trigger_error( $this->config->debugging->error_type->err_filesys.'|gd-library not exists' );
		}
		$_hdl=imagecreatetruecolor( $this->config->fsdriver->captcha->WIDTH+8, $this->config->fsdriver->captcha->HEIGHT );
		imagefill( $_hdl, 0, 0, imagecolorallocate( $_hdl, 248, 248, 248 ) ); // фон и фигурки
		$_arrColor=array(
			0=>ImageColorAllocate( $_hdl, 36, 136, 195 ),
			1=>ImageColorAllocate( $_hdl, 234, 147, 68 ),
			2=>ImageColorAllocate( $_hdl, 222, 15, 152 ),
			3=>ImageColorAllocate( $_hdl, 183, 183, 183 )
		);
		for ($i=0; $i<120; $i++) {
			$a=rand(5,7);
			imagefilledellipse( $_hdl, rand(0,$this->config->fsdriver->captcha->WIDTH+8), rand(0,$this->config->fsdriver->captcha->HEIGHT ), $a, $a, $_arrColor[rand(0,3)] );
		}
		// шрифт и его позиционирование
		$c=ImageColorAllocate($_hdl, 0, 0, 0 );
		$text=Core_A::rand_string( $this->config->fsdriver->captcha->LENGHT );
		$_SESSION['USER']['d_random_txtimg']=mb_strtoupper( $text );
		$_intTtfFontH=20;
		for ($i=0; $i<$this->config->fsdriver->captcha->LENGHT; $i++) {
			imagettftext( $_hdl, $_intTtfFontH, rand(-12,12), (0.2+$i+1)*( $_intTtfFontH+2 ), rand(2,4)+$_intTtfFontH+10, $c, $this->config->path->relative->source.'arial.ttf', $text{$i} );
		}
		// показываем результат
		header( 'Content-type: image/jpeg' );
		ImagePng( $_hdl );
		ImageDestroy( $_hdl );
		exit;
	}
	/**
	* список файлов в директории
	* если будет php4 - убрать "public static"
	* кстати если у файла нету расширения или имени то не сработает (readme и .htaccess)
	* @param array $arrFileList out
	* @param string $_strDir in
	* @return boolean
	*/
	public static function d_get_dir_filesname( &$arrFileList, $_strDir='' ) {
		if ( empty( $_strDir ) ) {
			return false;
		}
		$arrFileList=glob( $_strDir.'*.*' );
		foreach( $arrFileList as $k=>$v ) {
			$_arr=explode( '/', $v );
			$arrFileList[$k]=array_pop( $_arr );
		}
		return !empty( $arrFileList );
	}
	/**
	* удаление файлов по glob-маске
	* @param mixed $_mix in
	* @return boolean
	*/
	public function d_rmfile( $_mix=array() ) {
		if ( is_string( $_mix ) ) {
			if ( is_file( $_mix ) ) {
				return unlink( $_mix );
			}
		} elseif ( is_array( $_mix ) ) {
			$_arrErr=array_map( array(&$this, 'd_rmfile'), $_mix );
			if ( in_array( false, $_arrErr ) ) {
				return false;
			}
		} else {
			trigger_error( $this->config->debugging->error_type->err_filesys.'|bad param to delete file system instance' );
			return false;
		}
		return true;
	}
	/**
	* удаление файлов, директорий, файлов по glob-маске
	* @param mixed $_mix in
	* @return boolean
	*/
	public function d_rm( $_mix=array() ) {
		if ( is_string( $_mix ) ) {
			if ( is_file( $_mix ) ) {
				return $this->d_rmfile( $_mix );
			} elseif ( is_dir( $_mix ) ) {
				$this->d_rm( $_mix.'/*' );
				return rmdir( $_mix );
			} elseif ( count( ( $_arr=glob( $_mix ) ) )>0 ) {
				$this->d_rm( $_arr );
			}
		} elseif ( is_array( $_mix ) ) {
			$_arrErr=array_map( array(&$this, 'd_rm'), $_mix );
			if ( in_array( false, $_arrErr ) ) {
				return false;
			}
		} else {
			trigger_error( $this->config->debugging->error_type->err_filesys.'|bad param to delete file system instance' );
			return false;
		}
		return true;
	}

	public function d_debmes( $_mixData='' ) {
		if ( is_array( $_mixData ) ) {
			$_mixData=print_r( $_mixData, true );
		}
		if ( empty( $_mixData ) ) {
			$_mixData='data is empty';
		}
		if ( !$this->d_addtofile( $this->config->path->relative->root, 'debmes.txt', $_mixData."\n\n===\n\n" ) ) {
			return false;
		}
		return true;
	}

	public function d_download( $_arrInfo=array() ) {
		if ( empty( $_arrInfo ) ) {
			return false;
		}
		$_strPath=$_arrInfo['sys_name'].'.'.$_arrInfo['extension'];
		$_strOrig=$_arrInfo['orig_name'];
		if ( isSet( $_arrInfo['flg_type'] ) ) {
			$this->m_type=$_arrInfo['flg_type'];
		} else {
			$this->d_set_typebyfilename( $_strPath );
		}
		$_strPath=$this->m_dirs_path[$this->m_type].$_strPath;
		if ( !file_exists( $_strPath ) ) {
			return false;
		}
		$_intSize=isSet( $_arrInfo['size'] ) ? $_arrInfo['size']:filesize( $_strPath );
		set_time_limit(0);
		$_arrSrv=empty( $_SERVER ) ? $GLOBALS["HTTP_SERVER_VARS"] : $_SERVER;
		$_fp=fopen( $_strPath, "rb" );
		// генерируем заголовки
		if ( !empty( $_arrSrv['HTTP_RANGE'] ) ) {
			preg_match( "/bytes=(\d+)-/", $_arrSrv['HTTP_RANGE'], $m );
			$_intTmpSize=$_intSize-intval( $m[1] );
			$p1=$_intSize-$_intTmpSize;
			$p2=$_intSize-1;
			$p3=$_intSize;
			fseek( $_fp, $p1 );
			header( 'HTTP/1.1 206 Partial Content' );
			$this->downloader_headers();
			header( 'Content-Range: bytes '.$p1.'-'.$p2.'/'.$p3 );
		} else {
			$this->downloader_headers();
			header( 'HTTP/1.1 200 OK' );
		}
		header( 'Content-Length: '.$_intSize.'' );
		//header( 'Content-Type: '.$_arrFile['mime_type'] );
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename="'.$_strOrig.'"' );
		//header( 'Age: 0' );
		//header( 'Proxy-Connection: close' );
		header( '' );
		$content=fread( $_fp, $_intSize );
		fclose ( $_fp );
		echo $content; // отдаём файл
		return false;
	}

	private function downloader_headers() {
		header( 'Date: '.Core_Datetime::getInstance()->to_timezone_from_timezone( Zend_Registry::get( 'config' )->date_time->dt_zone, 'GMT', 'now', 'D, d M Y H:i:s \G\M\T' ) );
		header( 'X-Powered-By: PHP/'.phpversion() );
		header( 'Expires: Thu, 29 Apr 1979 07:00:00 GMT' );
		header( 'Cache-Control: None' );
		header( 'Pragma: no-cache' );
		header( 'Accept-Ranges: bytes' );
	}

	public function dirCopy( $_strFrom='', $_strTo='', $_arrSource=array() ) {
		if ( empty( $_strFrom )||empty( $_strTo )||empty( $_arrSource ) ) {
			return false;
		}
		$_int=strlen( $_strFrom );
		foreach( $_arrSource as $_strDir=>$_arrFiles ) {
			$_strDest=str_replace( array( '\\' ), '/', $_strTo.substr( $_strDir, $_int ) );
			if ( !is_dir( $_strDest ) ) {
				mkdir( $_strDest );
			}
			if ( !is_dir( $_strDest )||empty( $_arrFiles ) ) {
				continue;
			}
			foreach( $_arrFiles as $v ) {
				if ( !copy( $_strDir.DIRECTORY_SEPARATOR.$v, $_strDest.DIRECTORY_SEPARATOR.$v ) ) {
					return false;
				}
			}
		}
		return true;
	}

	public function dirScan( $_strDir='' ) {
		if ( empty( $_strDir ) ) {
			return false;
		}
		$arrRes=array();
		foreach( new RecursiveIteratorIterator( 
				new RecursiveDirectoryIterator( $_strDir, RecursiveDirectoryIterator::KEY_AS_PATHNAME ), 
				RecursiveIteratorIterator::SELF_FIRST ) as $directory => $info ) {
			// учитываем только читаемые файлы но не ссылки
			if ( $info->isLink()||!$info->isReadable() ) {
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
				$arrRes[$info->getPath()][]=$info->getFilename();
				/*$arrRes[$info->getPath()][]=array(
					'filename'=>$info->getFilename(),
					'size'=>$info->getSize(),
					'pathname'=>$info->getPathname(),
				);*/
			}
		}
		return $arrRes;
	}

	public function prepareTmpDir( $_strActionTmp='' ) {
		// если в имени папки не содержится путь до пользовательского темпа то уходим - страховка от удаления не тех дир
		if ( stristr( $_strActionTmp, 'usersdata'.DIRECTORY_SEPARATOR.'temp'.DIRECTORY_SEPARATOR.'users'.DIRECTORY_SEPARATOR )===false ) {
			return false;
		}
		$this->d_rm( $_strActionTmp ); // удаляем папку с файлами (если что-то осталось)
		if ( !is_dir( $_strActionTmp ) ) {
			mkdir( $_strActionTmp );
		}
		return is_dir( $_strActionTmp );
	}
}
?>