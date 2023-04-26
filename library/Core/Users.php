<?php
class Core_Users implements Core_Singleton_Interface {

	/**
	 * переменная с массивом данных текущего пользователя
	 *
	 * @var array
	 */
	public static $info=array();

	/**
	 * флаг позволяющий кэшировать в self::$_infoCashe
	 *
	 * @var boolean
	 */
	private $_withCashe=false;

	/**
	 * переменная с кэшем массива данных пердыдущего пользователя
	 *
	 * @var array
	 */
	private static $_infoCashe=NULL;

	/**
	 * настройки первичного пользователя для его 
	 * автоматического создания при необходимости
	 *
	 * @var array
	 */
	private static $_rootUser=array(
		'email'=>'cadmin@api.info',
		'nickname'=>'cadmin',
		'passwd'=>'J^H&TUUYf3e565',
		'flg_active'=>1,
	);

	/**
	 * экземпляр объекта текущего класса (singleton)
	 *
	 * @var Core_Users object
	 */
	private static $_instance=NULL;

	/**
	 * возвращает экземпляр объекта текущего класса (singleton)
	 * при первом обращении создаёт
	 *
	 * @return Core_Users object
	 */
	public static function getInstance() {
		if ( self::$_instance==NULL ) {
			// если у нас есть extends от Core_Users используем его
			self::$_instance=(class_exists( 'Project_Users' )? new Project_Users():new self());
		}
		return self::$_instance;
	}

	public static function updateStatistic(){
		$_arr=array_chunk( array_reverse( explode( '.', $_SERVER['HTTP_HOST'] ) ), 2 );
		$_strDomain=implode( '.', array_reverse( $_arr[0] ) );
		$_tail=substr( $_strDomain , strripos( $_strDomain, '.' )+1 );
		if ( $_tail=='local' ) {
			self::$info['statistic']=array(
				'lpb_campaigns_count'=>7,
				'lpb_limit'=>'unlim',
				'lpb_views'=>1000000,
				'lpb_clicks'=>100000,
				'traffic_credits'=>100,
				'lpb_campaigns_img'=>7,
				'traffic_campaigns_img'=>200,
				'traffic_received_img'=>'2m',
			);
			return '';
		}
		if( !isset( self::$info['statistic'] ) || empty( self::$info['statistic'] ) ){
			$_campaigns=new Project_Traffic();
			$_campaigns->withUserId(Core_Users::$info['id'])->getList( $_arrCampaigns );
			$_intCp=$_intRec=0;
			foreach( $_arrCampaigns as $_cp ){
				$_intCp++;
				$_intRec+=$_cp['clicks'];
			}
			$_intLPB=Project_Users_Stat::getCountLPB();
			if($_intLPB >= 200 ){
				$_intLPB=7;
			}elseif($_intLPB >= 100 ){
				$_intLPB=6;
			}elseif($_intLPB >= 50 ){
				$_intLPB=5;
			}elseif($_intLPB >= 20 ){
				$_intLPB=4;
			}elseif($_intLPB >= 10 ){
				$_intLPB=3;
			}elseif($_intLPB >= 5 ){
				$_intLPB=2;
			}elseif($_intLPB >= 1 ){
				$_intLPB=1;
			}
			
			if($_intCp >= 200 ){
				$_intCp=200;
			}elseif($_intCp >= 100 ){
				$_intCp=100;
			}elseif($_intCp >= 80 ){
				$_intCp=80;
			}elseif($_intCp >= 50 ){
				$_intCp=50;
			}elseif($_intCp >= 25 ){
				$_intCp=25;
			}elseif($_intCp >= 5 ){
				$_intCp=5;
			}elseif($_intCp >= 3 ){
				$_intCp=3;
			}elseif($_intCp >= 1 ){
				$_intCp=1;
			}
			
			if($_intRec >= 2000000 ){
				$_intRec='2m';
			}elseif($_intRec >= 1000000 ){
				$_intRec='1m';
			}elseif($_intRec >= 700000 ){
				$_intRec='700k';
			}elseif($_intRec >= 600000 ){
				$_intRec='600k';
			}elseif($_intRec >= 490000 ){
				$_intRec='490k';
			}elseif($_intRec >= 480000 ){
				$_intRec='480k';
			}elseif($_intRec >= 470000 ){
				$_intRec='470k';
			}elseif($_intRec >= 460000 ){
				$_intRec='460k';
			}elseif($_intRec >= 400000  ){
				$_intRec='400k';
			}elseif($_intRec >= 350000 ){
				$_intRec='350k';
			}elseif($_intRec >= 300000 ){
				$_intRec='300k';
			}elseif($_intRec >= 290000 ){
				$_intRec='290k';
			}elseif($_intRec >= 280000 ){
				$_intRec='280k';
			}elseif($_intRec >= 270000 ){
				$_intRec='270k';
			}elseif($_intRec >= 250000 ){
				$_intRec='250k';
			}elseif($_intRec >= 240000 ){
				$_intRec='240k';
			}elseif($_intRec >= 230000 ){
				$_intRec='230k';
			}elseif($_intRec >= 220000 ){
				$_intRec='220k';
			}elseif($_intRec >= 210000 ){
				$_intRec='210k';
			}elseif($_intRec >= 190000 ){
				$_intRec='190k';
			}elseif($_intRec >= 180000 ){
				$_intRec='180k';
			}elseif($_intRec >= 170000 ){
				$_intRec='170k';
			}elseif($_intRec >= 160000 ){
				$_intRec='160k';
			}elseif($_intRec >= 150000 ){
				$_intRec='150k';
			}elseif($_intRec >= 130000 ){
				$_intRec='130k';
			}elseif($_intRec >= 120000 ){
				$_intRec='120k';
			}elseif($_intRec >= 100000 ){
				$_intRec='100k';
			}elseif($_intRec >= 90000 ){
				$_intRec='90k';
			}elseif($_intRec >= 80000 ){
				$_intRec='80k';
			}elseif($_intRec >= 70000 ){
				$_intRec='70k';
			}elseif($_intRec >= 60000 ){
				$_intRec='60k';
			}elseif($_intRec >= 50000 ){
				$_intRec='50k';
			}elseif($_intRec >= 40000 ){
				$_intRec='40k';
			}elseif($_intRec >= 30000 ){
				$_intRec='30k';
			}elseif($_intRec >= 20000 ){
				$_intRec='20k';
			}elseif($_intRec >= 100 ){
				$_intRec=100;
			}elseif($_intRec >= 50 ){
				$_intRec=50;
			}elseif($_intRec >= 40 ){
				$_intRec=40;
			}elseif($_intRec >= 30 ){
				$_intRec=30;
			}elseif($_intRec >= 20 ){
				$_intRec=20;
			}elseif($_intRec >= 10 ){
				$_intRec=10;
			}elseif($_intRec >= 5 ){
				$_intRec=5;
			}elseif($_intRec >= 3 ){
				$_intRec=3;
			}elseif($_intRec >= 1 ){
				$_intRec=1;
			}
			self::$info['statistic']=array(
				'lpb_campaigns_count'=>Project_Users_Stat::getCountLPB(),
				'lpb_limit'=>Project_Users_Stat::getCountLPB('limits'),
				'lpb_views'=>Project_Users_Stat::getCountLPB('view'),
				'lpb_clicks'=>Project_Users_Stat::getCountLPB('clicks'),
				'traffic_credits'=>(int)$_campaigns->withUserId(Core_Users::$info['id'])->getUserCredits(),
				
				'lpb_campaigns_img'=>$_intLPB,
				'traffic_campaigns_img'=>$_intCp,
				'traffic_received_img'=>$_intRec,
			);
		}
		return '';
	}

	public function getCache(){
		return Core_Users::$_infoCashe;
	}

	/**
	 * Генератор паролей для пользователй.
	 * @return string
	 */
	public static function generatePassword(){
		$_chars="qazxswedcvfrtgbnhyujmkiolp1234567890QAZXSWEDCVFRTGBNHYUJMKIOLP";
		$_max=10;
		$_size=strlen($_chars)-1;
		$_password='';
		while( $_max-- )
			$_password.=$_chars[rand(0,$_size)];
		return $_password;
	}

	/**
	 * устанавливает флаг который говорит системе что нужно закешировать текущего пользователя
	 * objUser (self::$info) перед тем как инициализировть objUser новым пользователем
	 * пример: Core_Users::getInstance()->withCashe()->userById(<id>)
	 *
	 * @return Core_Users object
	 */
	public function withCashe() {
		$this->_withCashe=true;
		return $this;
	}

	/**
	 * возвращает предыдущего пользователя в self::$info
	 * пример: Core_Users::getInstance()->retrieveFromCashe()-><можно сделать ещё что-то>
	 *
	 * @return object
	 */
	public function retrieveFromCashe() {
		if ( empty( self::$_infoCashe ) ) {
			Core_Users::logout();
			return;
		}
		self::$info=self::$_infoCashe;
		self::$_infoCashe=NULL; // инициализируем кэш
		return $this;
	}

	/**
	 * поведение объекта такое как и сейчас. требуется для пользователя сайта
	 * задаётся в bootstrap Core_Users::getInstance()->setWebUser();
	 *
	 * @return boolean
	 */
	public function setWebUser() {
		$this->init();
		if ( empty( self::$info ) ) {
			Core_Acs::setMinimalUserRight( self::$info ); // пользователь получает права группы Visitor
		}
		Core_Datetime::setUserTimezone(); // и дефолтный временной пояс
		if ( !Zend_Registry::isRegistered( 'locale' )||empty( self::$info['lang'] ) ) { // проект не мультиязычный
			return true;
		}
		Zend_Registry::get( 'locale' )->setLocale();
		Zend_Registry::get( 'objMR' )->correctLngInUrl();
		return true;
	}

	/**
	 * бд не используется и id=0
	 * Zend_Registry::get( 'objUser' )->setZero();
	 *
	 * @return boolean
	 */
	public function setZero() {
		$arrProfile=array( 'id'=>0 );
		return $this->setByProfile( $arrProfile );
	}

	/**
	 * принудительно создаём объект по заданному id
	 * Zend_Registry::get( 'objUser' )->setById( <id> );
	 *
	 * @return boolean
	 */
	public function setById( $_int=0 ) {
		if ( empty( $_int ) ) {
			throw new Exception( Core_Errors::DEV.'|User id can be setted' );
		}
		$_user=new Core_Users_Management();
		if ( !$_user->onlyActive()->onlyOne()->withIds( $_int )->getList( $arrProfile )->checkEmpty() ) {
			return false;
		}
		return $this->setByProfile( $arrProfile );
	}

	/**
	 * инициализируем пользователя (например после логина)
	 * Zend_Registry::get( 'objUser' )->setByProfile( <profile> );
	 *
	 * @return boolean
	 */
	public function setByProfile( &$_arrProfile ) {
		$this->init();
		if ( empty( $_arrProfile ) ) {
			throw new Exception( Core_Errors::DEV.'|Profile info can be setted' );
		}
		$this->clearInfo();
		self::$info=$_arrProfile;
		Core_Datetime::setUserTimezone();
		if ( !Zend_Registry::isRegistered( 'locale' )||empty( self::$info['lang'] ) ) { // проект не мультиязычный
			return true;
		}
		Zend_Registry::get( 'locale' )->setLocale();
		Zend_Registry::get( 'locale' )->setTranslator();
		return true;
	}

	/**
	 * принудительное обновление данных текущего пользователя из БД
	 * Zend_Registry::get( 'objUser' )->reload();
	 *
	 * @return boolean
	 */
	public function reload() {
		if ( empty( self::$info['id'] ) ) {
			return false;
		}
		return $this->setById( self::$info['id'] );
	}

	/**
	 * удаление сессии и кук
	 *
	 * @return void
	 */
	public static function logout() {
		Core_Users_Cookie::delete();
		Zend_Session::destroy();
		Core_Users::$info=array();
		Core_Users::$_infoCashe=array();
	}

	/**
	 * иногда нужно сменить сессию в процессе выполнения
	 * Zend_Registry::get( 'objUser' )->initSession( $_arrOpt );
	 *
	 * @return object
	 */
	public function initSession( $_arrOpt=array() ) {
		Zend_Session::start( $_arrOpt ); // инициализируем сессию
		$this->linkSession(); // привязываем self::$info к ней
		return $this;
	}

	// id текущего пользователя
	public function getId( &$_intId ) {
		$_intId=self::$info['id'];
		return isSet( self::$info['id'] );
	}

	// папка для временных папок текущего пользователя
	public function getTmpDirName() {
		return Zend_Registry::get( 'config' )->path->relative->user_temp.($this->getId( $_intId )? $_intId:0).DIRECTORY_SEPARATOR;
	}

	// папка для папок текущего пользователя
	public function getDtaDirName() {
		return Zend_Registry::get( 'config' )->path->relative->user_data.($this->getId( $_intId )? $_intId:0).DIRECTORY_SEPARATOR;
	}

	// папки пользователей для временных файлов
	public function checkTmpDir( &$strDir ) {
		$strDir=$this->getTmpDirName();
		if ( !is_dir( $strDir ) ) {
			mkdir( $strDir, 0755, true );
		}
		return is_dir( $strDir );
	}

	// папки пользователей для файлов
	public function checkDtaDir( &$strDir ) {
		$strDir=$this->getDtaDirName();
		if ( !is_dir( $strDir ) ) {
			mkdir( $strDir, 0755, true );
		}
		return is_dir( $strDir );
	}

	// создание верменной папки пользователя {config->path->absolute->user_temp/<id>/<classname@methodname>/}
	public function prepareTmpDir( &$strDir ) {
		if ( !$this->checkTmpDir( $_tmpDir ) ) {
			return false;
		}
		$strDir=$_tmpDir.$strDir.DIRECTORY_SEPARATOR;
/*	
if(is_file('./wwwdatalog.txt')){
	Core_Files::getContent($_str,'./wwwdatalog.txt');
}
$_str.="\n".date('d.m.Y H:i:s').' '.serialize( $_SERVER );
Core_Files::setContent($_str,'./wwwdatalog.txt');
*/
		Core_Files::rmDir( $strDir ); // удаляем папку с файлами (если что-то осталось c прошлого раза)
		if ( !is_dir( $strDir ) ) {
			mkdir( $strDir, 0755, true );
		}
		return is_dir( $strDir );
	}

	// создание верменной папки пользователя {config->path->absolute->user_temp/<id>/$strDir/}
	public function prepareDtaDir( &$strDir ) {
		if ( !$this->checkDtaDir( $_tmpDir ) ) {
			return false;
		}
		$strDir=$_tmpDir.$strDir.DIRECTORY_SEPARATOR;
		if ( !is_dir( $strDir ) ) {
			mkdir( $strDir, 0755, true );
		}
		return is_dir( $strDir );
	}

	// очищаем и кэшируем при необходимости, нужно перед каждой новой инициализацией кроме web
	protected function clearInfo() {
		if ( $this->_withCashe ) {
			self::$_infoCashe=self::$info;
		}
		self::$info=array(); // обнуляем для новой инициализации
		$this->_withCashe=false; // инициализируем флаг
	}

	// вызывается при любой варианте инициализации объекта objUser
	protected function init() {
		$this->linkSession();
		if ( !Zend_Registry::isRegistered( 'objUser' ) ) {
			Zend_Registry::set( 'objUser', $this ); // закидываем объект в регистри
		}
		new Core_Acs(); // инициализация дефолтных групп (при необходимости инсталлирование)
		if ( !$this->checkRootUser() ) {
			throw new Exception( Core_Errors::DEV.'|Can\'t create root user' );
		}
	}

	private function linkSession() {
		if ( empty( $_SESSION['USER'] ) ) {
			$_SESSION['USER']=array();
		}
		self::$info=&$_SESSION['USER']; // привязываем к сессии
		self::$_infoCashe=&$_SESSION['USER_CACHE']; // привязываем к сессии
	}

	// инициализация рута (при необходимости создание)
	private function checkRootUser() {
		if ( !empty( self::$_rootUser['id'] ) ) {
			return true;
		}
		$_user=new Core_Users_Management();
		if ( $_user->onlyOne()->withEmail( self::$_rootUser['email'] )->withPasswd( self::$_rootUser['passwd'] )->getList( $_arrRes )->checkEmpty() ) {
			self::$_rootUser=$_arrRes;
			return true;
		}
		// создаём пользователя, если не создан
		Core_Datetime::getServerTimezone( self::$_rootUser['timezone'] ); // часовой пояс сервера
		return $_user->setEntered( self::$_rootUser )->withGroups( Core_Acs::$mandatory )->set();
	}

	// установка пользовательских кук если урл уже подходит
	public static function updateLng() {
		if ( !Zend_Registry::isRegistered( 'locale' ) ) { // проект не мультиязычный
			return;
		}
		$_strLng=Zend_Registry::get( 'locale' )->getLanguage();
		if ( self::$info['lang']==$_strLng ) {
			return;
		}
		Core_Users_Cookie::setLng();
		self::$info['lang']=$_strLng;
	}

	// пока только для фронтэнд пользователей
	public function regenerate() {
		Zend_Session::start( array( 'name'=>'sid' ) );
		$this->linkSession();
	}
}

?>