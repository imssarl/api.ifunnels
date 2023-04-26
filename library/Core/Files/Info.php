<?php


/**
 * Сохранение файла в системе и списки файлов
 */
class Core_Files_Info extends Core_Data_Storage {

	/**
	 * имя таблицы в которую сохраняются данные
	 *
	 * @var string
	 */
	protected $_table='file_info';

	/**
	 * список полей таблицы, лишние поля в данных будут удалены при сохранении
	 *
	 * @var array
	 */
	protected $_fields=array( 
		'id', 'user_id', 'group_id', 'category_id', 'letter', 'extension', 'size', 'mime_type', 'title', 'name_original', 'name_system', 'path_web', 'path_sys', 'description', 'edited', 'added',
		'flg_type', 'flg_utilization', 'flg_handling', 'flg_sharing', 'flg_moderate', 'flg_cause', 'flg_rate', 'flg_tags', 'flg_comm', 'flg_embed', 
		'moderator_id', 'comment', /* могут заполняться после модерирования */
		'tumb_num', 'duration', 'converted', /* могут заполняться после обработки - характерно для video или audio */
		'deleted', /* может заполняться после утилизации */
	 );

	/**
	 * список статусов для поля flg_utilization
	 *
	 * @var array
	 */
	public static $utilization=array(
		'exists'=>0, /* в системе */
		'deleted'=>1, /* задание на удаление */
		'tmp'=>2, /* пока временный */
	);

	/**
	 * список статусов для поля flg_handling
	 *
	 * @var array
	 */
	public static $handling=array(
		'complete'=>0, /* обработан */
		'task'=>1, /* задание на обработку */
		'processed'=>2, /* обрабатываем */
		'error'=>3, /* ошибка в процессе */
	);

	/**
	 * список статусов для поля flg_sharing
	 * разделение доступа
	 *
	 * @var array
	 */
	public static $sharing=array(
		'all'=>0, /* доступен всем */
		'only_friends'=>1, /* только друзьям */
		'only_owner'=>2, /* может видеть только владелец */
	);

	/**
	 * список статусов для поля flg_moderate
	 * администрирование файлов
	 *
	 * @var array
	 */
	public static $moderate=array(
		'premoderated'=>0, /* на рассмотрении */
		'accept'=>1, /* утерждён */
		'reject'=>2, /* отклонён */
	);

	/**
	 * режимы работы с файлом
	 *
	 * @var array
	 */
	public static $mode=array(
		'upload'=>0,
		'copy'=>1,
		'move'=>2,
		'onlyDataEdit'=>3,
	);

	/**
	 * по умолчанию файл у нас заливается с клиента
	 *
	 * @var integer
	 */
	protected $_mode=0;

	/**
	 * условный тип медиа
	 * который диктует нам как отображать данный файл на сайте
	 *
	 * @var array
	 */
	public static $mediaType=array(
		'others'=>0,
		'audio'=>1,
		'video'=>2,
		'images'=>3,
	);

	/**
	 * запрещённые к заливке расширения файлов
	 *
	 * @var array
	 */
	protected $_extensionDisallowed=array( 'exe', 'bat', 'msi', 'sh', '' );

	/**
	 * разрешённые к заливке расширения файлов
	 * размещённые по медиа типу (см. self::$mediaType)
	 *
	 * @var array
	 */
	protected $_extensionByMediaType=array(
		array( 'zip', 'rar', 'arj', 'doc', 'xls', 'pdf', 'txt', 'cfg' ),
		array( 'mp3', 'wav', 'aiff', 'fla'/*flash audio*/ ),
		array( 'mp4', 'm4v', 'mov', '3gp', 'wmv', 'rm', 'ram', 'avi', 'asf', 'mpg', 'mpeg', 'flv'/*flash video*/ ),
		array( 'gif', 'jpg', 'jpeg', 'jpe', 'bmp', 'png', 'tif', 'tiff' ),
	);

	/**
	 * медиа тип для текущего файла
	 * в случае если тип не указан
	 * принудтельно определяем тип по расширению файла
	 *
	 * @var integer
	 */
	private $_mediaType=null;

	/**
	 * исходный файл и путь к нему
	 *
	 * @var string
	 */
	private $_sourcePath='';

	/**
	 * путь по которому сохраняется исходный файл
	 *
	 * @var string
	 */
	protected $_destinationPath='';

	/**
	 * флаг который позволяет инициализировать $this->_destinationPath один раз для каждого файла
	 *
	 * @var boolean
	 */
	private $_destinationExists=false;

	/**
	 * информация по текущей группе файла
	 *
	 * @var array
	 */
	protected $_group=array();

	/**
	 * информация по использованию файла
	 * по умолчению у нас обычный файл
	 *
	 * @var integer
	 */
	private $_fileUtilization=0;

	private $_fileSharing=0;

	private $_fileModerate=0;

	private $_enteredFile=false;

	/**
	 * предыдущая инфа по файлу
	 * чтобы вернуть всё как было в случае ошибки
	 *
	 * @var array
	 */
	private $_oldInfo=array();

	public function __construct( $_str='' ) {
		if ( empty( $_str ) ) {
			return;
		}
		$this->setGroupBySysName( $_str );
	}

	private function setGroupBySysName( $_str='' ) {
		$_group=new Core_Files_Group();
		if ( !$_group->onlyOne()->withSysName( $_str )->getList( $_arrGrp )->checkEmpty() ) {
			throw new Exception( Core_Errors::DEV.'|Group "'.$_str.'" not found' );
		}
		$this->_group=$_arrGrp;
	}

	/**
	 * данные из массива $_FILES
	 * например $file->setEntered( $_POST['fileinfo'] )->setEnteredFile( $_FILES['name'] )->set();
	 *
	 * @param array $_arr in
	 * @return object
	 */
	public function setEnteredFile( $_arr=array() ) {
		$this->_enteredFile=$_arr;
		return $this;
	}

	/**
	 * Вернуть данные о файле.
	 *
	 * @return array
	 */
	public function getEnteredFile(){
		return $this->_enteredFile;
	}

	/**
	 * используем в случае если надо скопировать или переместить файл
	 *
	 * @return object
	 */
	public function setMode( $_int=0 ) {
		$this->_mode=$_int;
		return $this;
	}

	/**
	 * используем в случае если сохраняем временный файл
	 *
	 * @return object
	 */
	public function setTmp() {
		$this->_fileUtilization=self::$utilization['tmp'];
		return $this;
	}


	/**
	 * меняем флаг для файла с указанным ids на нормальный (exists)
	 *
	 * @return object
	 */
	public function setExists() {
		if ( empty( $this->_withIds )&&empty( $this->_withGroups ) ) { // если ids ни групп ни файлов не указаны
			return false;
		}
		if ( !$this->onlyIds()->getList( $_arrIds )->checkEmpty() ) { // ids файлов
			return false;
		}
		Core_Sql::setExec( 'UPDATE '.$this->_table.' SET flg_utilization='.self::$utilization['exists'].' WHERE id IN('.Core_Sql::fixInjection( $_arrIds ).')' );
		return true;
	}

	/**
	 * вручную выставить источник файла
	 * $this->_sourcePath пользуется приоритетом над $this->_enteredFile['tmp_name']
	 *
	 * @return object
	 */
	public function setSourcePath( $_str='' ) {
		$this->_sourcePath=$_str;
		return $this;
	}

	public function setMediaType( $_type=null ) {
		if ( is_null( $_type )||!in_array( $_type, self::$mediaType ) ) {
			throw new Exception( Core_Errors::DEV.'|Media Type not set' );
			return $this;
		}
		$this->_mediaType=$_type;
		return $this;
	}

	public function setSharing( $_int=0 ) {
		if ( in_array( $_int, self::$sharing ) ) {
			$this->_fileSharing=$_int;
		}
		return $this;
	}

	/**
	 * принудительно апррувит файл без премодерации
	 *
	 * @return string
	 */
	public function setAccepted() {
		$this->_fileModerate=self::$moderate['accept'];
		return $this;
	}

	/**
	 * проверка и коррекция данных о файле
	 *
	 * @return boolean
	 */
	protected function verify() {
		// если у нас редактирование данных без заливки файла
		if ( !empty( $this->_data->filtered['id'] ) ) {
			if ( $this->_mode==self::$mode['onlyDataEdit'] ) {
				return true;
			}
			if ( !empty( $this->_enteredFile['error'] ) ) { // если есть ошибки то файл возможно и не пытались залить
				$this->setMode( self::$mode['onlyDataEdit'] ); // принудительно устанавливаем режим редактирования данных
				return true;
			}
		}
		// если это заливка файла то проверяем валидность основных параметров
		if ( !Zend_Validate::is( $this->_enteredFile, 'NotEmpty' ) ) { // нет инфы о файле
			return $this->setError( 'no_file_data' );
		}
		if ( !Zend_Validate::is( $this->_enteredFile['error'], 'NotEmpty' ) ) { // ошибка при заливке
			return $this->setError( 'upload_error' );
		}
		$_bool=true;
		if ( !$this->checkName() ) { // проблемы с названием файла
			$_bool=$this->setError( 'have_no_name' );
		}
		if ( !$this->checkExtension() ) { // проблемы с расширением
			$_bool=$this->setError( 'have_no_extension' );
		}
		if ( !$this->checkType() ) { // тип файла не найден в системе
			$_bool=$this->setError( 'media_type_notfound' );
		}
		return $_bool;
	}

	private function checkName() {
		$_strName=Core_Files::getFileName( $this->_enteredFile['name'] );
		if ( empty( $_strName ) ) {
			return false;
		}
		return true;
	}

	private function checkExtension() {
		$_strExt=Core_Files::getExtension( $this->_enteredFile['name'] );
		if ( empty( $_strExt ) ) {
			return false;
		}
		$this->_data->setElement( 'extension', strtolower( $_strExt ) );
		return true;
	}

	private function checkType() {
		if ( !is_null( $this->_mediaType ) ) { // может быть выставлен принудительно
			return true;
		}
		foreach( $this->_extensionByMediaType as $_type=>$_exts ) {
			if ( in_array( $this->_data->filtered['extension'], $_exts ) ) {
				$this->setMediaType( $_type );
			}
		}
		return !is_null( $this->_mediaType );
	}

	/**
	 * аспект кторый вызывается до выполнения set()
	 * после переназначения тут например можно организовать проверку полей
	 *
	 * @return boolean
	 */
	protected function beforeSet() {
		$this->_data->setFilter();
		if ( !$this->verify() ) {
			return false;
		}
		$this->storeOldInfo();
		if ( $this->_mode!=self::$mode['onlyDataEdit'] ) { // эти данные меняем только при наличии файла
			$this->_data->setElements( array(
				'flg_type'=>$this->_mediaType,
				'name_original'=>$this->_enteredFile['name'],
				'mime_type'=>$this->_enteredFile['type'],
				'letter'=>strtolower( $this->_enteredFile['name']{0} ),
				'flg_utilization'=>is_null( $this->_fileUtilization )? 0:$this->_fileUtilization,
			) );
		}
		$this->_data->setElements( array(
			'group_id'=>$this->_group['id'],
			'category_id'=>empty( $this->_data->filtered['category_id'] ) ? 0:$this->_data->filtered['category_id'],
			'flg_rate'=>empty( $this->_data->filtered['flg_rate'] )? 0:1,
			'flg_tags'=>empty( $this->_data->filtered['flg_tags'] )? 0:1,
			'flg_comm'=>empty( $this->_data->filtered['flg_comm'] )? 0:1,
			'flg_embed'=>empty( $this->_data->filtered['flg_comm'] )? 0:1,
			'flg_sharing'=>$this->_fileSharing,
			'flg_moderate'=>$this->_fileModerate,
		) );
		return true;
	}

	public function setMass() {
		$this->_data->setFilter();
		if ( empty( $this->_data->filtered ) ) {
			return true;
		}
		// чтобы нулевой элемент оказался в конце. сначала проверяем старые данные
		$this->_data->filtered=array_reverse( $this->_data->filtered, true );
		foreach( $this->_data->filtered as $k=>$v ) {
			if ( !$this->beforeSetMass( $k, $v ) ) {
				continue;
			}
			// если были ошибки в старых данных новый элемент не обрабатываем для избежания коллизий
			if ( $k===0&&!empty( $this->_errors ) ) {
				break;
			}
			$_strClass=get_class( $this );
			$_storage=new $_strClass( $this->_group['sysname'] );
			if ( !$_storage->setMode( $this->_mode )->setEntered( $v )->set() ) {
				$_storage->getErrors( $this->_errors[$k] );
				continue;
			}
			// для нового элемента добавляем в данные его id
			if ( empty( $this->_data->filtered[$k]['id'] ) ) {
				$_storage->getEntered( $_arrRow );
				$this->_data->filtered[$k]['id']=$_arrRow['id'];
			}
		}
		// возвращаем элементам первоначальный порядок
		$this->_data->filtered=array_reverse( $this->_data->filtered, true );
		if ( !empty( $this->_errors ) ) {
			return false;
		}
		return $this->afterSetMass();
	}
	
	/**
	 * аспект кторый вызывается после выполнения set()
	 * после переназначения тут например можно сделать какие-либо действия после сохранения данных
	 *
	 * @return boolean
	 */
	protected function afterSet() {
		if ( $this->_mode==self::$mode['onlyDataEdit'] ) { // если меняются только данные о файле
			return true;
		}
		if ( !$this->save() ) {
			return $this->rollback();
		}
		// записываем дополнительные данные для данного файла
		$this->_data->setFilter( 'strip_tags', 'trim', 'clear' )->setElements( array(
			'size'=>filesize( $this->getDestinationPath().$this->getFileSystemName() ),
			'flg_handling'=>($this->_mediaType==self::$mediaType['video'] ? self::$handling['task']:self::$handling['complete']),
			'name_system'=>$this->getFileSystemName(),
			'path_sys'=>$this->getDestinationPath(),
			'path_web'=>Core_Files::getWebPath( $this->getDestinationPath() ),
		) );
		Core_Sql::setInsertUpdate( $this->_table, $this->_data->setMask( $this->_fields )->getValid() );
		$this->initFileFields();
		return true;
	}

	/**
	 * берём предыдущую инфу о файле
	 *
	 * @return void
	 */
	private function storeOldInfo() {
		if ( empty( $this->_data->filtered['id'] ) ) {
			return;
		}
		if ( $this->withIds( $this->_data->filtered['id'] )->get( $_arrRes ) ) {
			$this->_oldInfo=$_arrRes;
		}
	}

	/**
	 * откат данных в бд при неудачной работе с файлами
	 *
	 * @return boolean
	 */
	private function rollback() {
		if ( empty( $this->_oldInfo ) ) {
			// при создании файла удаляем ранее созданную запись
			$this->withIds( $this->_data->filtered['id'] );
			parent::del();
		} else {
			// при редактировании возвращаем назад старые данные
			Core_Sql::setInsertUpdate( $this->_table, $this->_oldInfo );
		}
		return false;
	}

	/**
	 * ошибки при удалении файла
	 * отправляем админу
	 *
	 * @return void
	 */
	private function sendMailToAdmin() {}

	/**
	 * сохраняем файл по новому пути
	 *
	 * @return string
	 */
	private function save() {
		$_strSource=$this->getSourcePath();
		$_strDest=$this->getDestinationPath().$this->getFileSystemName();
		// при copy и move надо ещё разобраться с записью в бд
		// либо это из сторонних файлов в систему заливаем
		// либо работа происходит с файлом из систпемы
		// либо поддержка обоих вариантов
		// TODO!!! 23.09.2011
		if ( $this->_mode==self::$mode['copy'] ) {
			return copy( $_strSource, $_strDest );
		} elseif ( $this->_mode==self::$mode['move'] ) {
			$_bool=copy( $_strSource, $_strDest );
			if ( !Core_Files::rmFile( $_strSource ) ) {
				$this->sendMailToAdmin();
			}
			return $_bool;
		} else {
			if ( !empty( $this->_oldInfo ) ) { // при редактировании удаляем предыдущий файл
				if ( !Core_Files::rmFile( $this->_oldInfo['path_sys'].$this->_oldInfo['name_system'] ) ) {
					$this->sendMailToAdmin();
				}
			}
			return move_uploaded_file( $_strSource, $_strDest );
		}
	}

	/**
	 * отдаёт путь и имя файла одной строкой
	 *
	 * @return string
	 */
	protected function getSourcePath() {
		if ( !empty( $this->_sourcePath ) ) {
			return $this->_sourcePath;
		}
		if ( !empty( $this->_enteredFile['tmp_name'] ) ) {
			return $this->_enteredFile['tmp_name'];
		}
		throw new Exception( Core_Errors::DEV.'|File source not exists' );
		return '';
	}

	/**
	 * путь по которому прописываем новый файл
	 *
	 * @return string
	 */
	protected function getDestinationPath() {
		if ( $this->_destinationExists ) {
			return $this->_destinationPath;
		}
		if ( !Zend_Registry::get( 'objUser' )->prepareDtaDir( $this->_destinationPath ) ) {
			throw new Exception( Core_Errors::DEV.'|Zend_Registry::get( \'objUser\' )->prepareDtaDir( $_strDir ) no dir set' );
			return;
		}
		$this->_destinationExists=true;
		return $this->_destinationPath;
	}

	/**
	 * генерация системного имени файла для нового файла
	 *
	 * @return string
	 */
	protected function getFileSystemName() {
		return Zend_Crypt::hash( 'md5', $this->_data->filtered['id'] ).'.'.$this->_data->filtered['extension'];
	}

	/**
	 * инициализация переменных учавствующих в set()
	 *
	 * @return string
	 */
	protected function initFileFields() {
		$this->_mode=self::$mode['upload'];
		$this->_fileUtilization=self::$utilization['exists'];
		$this->_fileHandling=self::$handling['complete'];
		$this->_fileSharing=self::$sharing['all'];
		$this->_fileModerate=self::$moderate['premoderated'];
		$this->_mediaType=null;
		$this->_sourcePath='';
		$this->_destinationExists=false;
		$this->_destinationPath='';
	}

	/**
	 * при удалении, группа не удаляется а помечается как удалённая
	 * вместе с группой помечаются и файлы
	 * имеет право только владелец данных групп
	 *
	 * если надо удалить файлы только определённого владельца 
	 * вызываем так $obj->onlyOwner()->utilization();
	 *
	 * @return boolean
	 */
	public function utilization() {
		if ( empty( $this->_withIds )&&empty( $this->_withGroups ) ) { // если ids ни групп ни файлов не указаны
			return false;
		}
		if ( !$this->onlyIds()->getList( $_arrIds )->checkEmpty() ) { // ids файлов
			return false;
		}
		Core_Sql::setExec( 'UPDATE '.$this->_table.' SET flg_utilization='.self::$utilization['deleted'].', deleted='.time().' WHERE id IN('.Core_Sql::fixInjection( $_arrIds ).')' );
		return true;
	}

	/**
	 * физически удаляет crontab скрипт (см. Core_Files_Scavenger)
	 * вместе с группой удаляем и файлы
	 * т.к. это будет делать скрипт ->onlyOwner в этом случае использовать ненадо
	 *
	 * @return boolean
	 */
	public function del() {
		if ( !$this->onlyDeleted()->toScavenger()->getList( $_arrFiles )->checkEmpty() ) { // инфа по файлам
			return false;
		}
		foreach( $_arrFiles as $v ) {
			Core_Files::rmFile( $v['path_sys'].$v['sysname'] ); // физическое удаление
			$this->_withIds[]=$v['id'];
		}
		return parent::del();
	}

	/**
	 * фильтр: только удалённые файлы
	 *
	 * @var boolean
	 */
	protected $_onlyDeleted=false;

	/**
	 * фильтр: при физическом удалении файлов
	 *
	 * @var array
	 */
	protected $_toScavenger=false;

	/**
	 * фильтр: только в данных группах
	 *
	 * @var array
	 */
	protected $_withGroups=array();

	/**
	 * фильтр: учитывать модерирование
	 *
	 * @var integer
	 */
	protected $_withModerate=null;

	/**
	 * фильтр: ищем по тайтлу
	 *
	 * @var integer
	 */
	protected $_withTitle='';

	protected function init() {
		parent::init();
		$this->_onlyDeleted=false;
		$this->_toScavenger=false;
		$this->_withGroups=array();
		$this->_withModerate=null;
		$this->_withTitle='';
	}

	public function withModerate( $_int=null ) {
		$this->_withModerate=$_int;
		return $this;
	}

	public function withTitle( $_str='' ) {
		if ( empty( $_str ) ) {
			return $this;
		}
		$this->_withTitle=$_str;
		return $this;
	}

	// используется сборщиком мусора
	public function onlyDeleted() {
		$this->_onlyDeleted=true;
		return $this;
	}

	public function toScavenger() {
		$this->_toScavenger=true;
		return $this;
	}

	public function withGroups( $_mix=array() ) {
		if ( !empty( $_mix ) ) {
			$this->_withGroups=$_mix;
		}
		return $this;
	}

	protected function assemblyQuery() {
		parent::assemblyQuery();
		if ( !empty( $this->_withGroups ) ) {
			$this->_crawler->set_where( 'd.group_id IN('.Core_Sql::fixInjection( $this->_withGroups ).')' );
		} elseif ( !empty( $this->_group['id'] ) ) {
			$this->_crawler->set_where( 'd.group_id='.$this->_group['id'] );
		}
		if ( !is_null( $this->_withModerate ) ) {
			$this->_crawler->set_where( 'd.flg_moderate='.$this->_withModerate );
		}
		if ( !empty( $this->_withTitle ) ) {
			$this->_crawler->set_where( 'd.title='.Core_Sql::fixInjection($this->_withTitle) );
		}
		if ( $this->_onlyDeleted ) {
			$this->_crawler->set_where( 'd.flg_utilization IN('.self::$utilization['deleted'].', '.self::$utilization['tmp'].')' );
		} else {
			$this->_crawler->set_where( 'd.flg_utilization='.self::$utilization['exists'] );
		}
		if ( $this->_toScavenger ) {
			$this->_crawler->set_where( 'd.deleted>='.time().'-'.Core_Files_Scavenger::INTERVAL );
		}
	}
}
?>