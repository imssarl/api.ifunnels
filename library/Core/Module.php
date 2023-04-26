<?php


/**
 * Sorce modules base class
 */
class Core_Module extends Core_Services implements Core_Module_Interface {
	private $_uniqueId=''; // id модуля при рекурсивном запуске (знает только текущий модуль - но можно пройтись по всем TODO!!! 13.04.2009)
	private $_moduleName=''; // имя текущего модуля
	public $config;
	public $objMR; // статический объект Core_Module_Router
	public $objML; // статический объект Core_Module_Location
	public $objUser; // статический объект Core_Users
	public $out=array(); //то что попадает на шаблон
	public $out_js=array(); //то что конвертиться в json
	public $inst_script=array(); // описание модуля - задаётся в конечном классе
	public $params=array(); // параметры данного модуля

	public function __construct( $_arrPrm=array() ) {
		$this->_moduleName=get_class( $this );
		$this->checkOldConstruct();
		$this->config=Zend_Registry::get( 'config' );
		$this->factory( array( 'objMR', 'objML', 'objSniff' ) );
		$this->objML->initLocation( $this ); // хранилище уникальных ссылок - надо каждый раз перадавать актуальный модуль для формирования правильной ссылки при location()
		$objCMMM=new Core_Module_Management_Modules();
		$objCMMM->initModule( $this ); // проверяем заинталлирован-ли запрашиваемый модуль (по имени модуля)
		//$this->factory( array( 'objUser' ) ); // инициализация пользователя, там-же проверка наличия root-пользователя
		$this->objMR->setGlobalParams( $this );
		//$this->setPrmManual( $_arrPrm ); // сначала разобраться с передачей параметров через конструктор TODO!!! 24.11.2011
	}

	// костыли. при возможности переделать. завязать на мэнеджер сайтов TODO!!!16.08.2011
	public static function startSite() {
		if ( Zend_Registry::get( 'objMR' )->isBackend&&Core_Module_Management_Modules::includeModule( 'backend' ) ) {
			$obj=new backend();
			$obj->run();
		} elseif ( Core_Module_Management_Modules::includeModule( 'site1' ) ) {
			$obj=new site1();
			$obj->run();
		}
	}

	// если в модуле есть метод имя которого такое-же как у класса то пхп считает его конструктором
	// соответственно конструктор Core_Module не срабатывает
	private function checkOldConstruct() {
		if ( !in_array( $this->_moduleName, get_class_methods( $this ) ) ) {
			return;
		}
		throw new Exception( Core_Errors::DEV.'|Oldschool constructor detected in "'.$this->_moduleName.'" class. Please rename "'.$this->_moduleName.'" method.' );
	}

	protected final function factory( $_arr=array() ) {
		if ( !$this->childFactory( $_arr ) ) {
			return false;
		}
		foreach( $_arr as $v ) {
			if ( Zend_Registry::isRegistered( $v ) ) {
				$this->$v=Zend_Registry::get( $v );
				continue;
			}
			switch( $v ) {
				// этот кейс нужно только если мы запускаем модуль из shell - проверить вообще такую возможность да и решить надоли это TODO!!! 05.01.2012
				//case 'objMR': $this->$v=Core_Module_Router::getInstance(); return true; break; 
				case 'objML': $this->$v=new Core_Module_Location(); break; // лог местоположений на сайте
				//case 'objUser': $this->$v=(class_exists( 'Project_Users' )? new Project_Users():new Core_Users()); break; // пользователи и права вынести в стартер TODO!!! 12.08.2011
				case 'objSniff': $this->$v=new Core_Sniffer(); break; // инфа о клиенте
			}
			if ( !empty( $this->$v )&&is_object( $this->$v ) ) {
				Zend_Registry::set( $v, $this->$v );
			}
		}
		return true;
	}

	public function childFactory( $_arr=array() ) {return true;}

	// если модуль вызвали не по ссылке
	// назначаем ему идентификатор и параметры
	public function setPrmManual( $_arrPrm=array() ) {
		if ( empty( $_arrPrm ) ) {
			return;
		}
		$this->_uniqueId=$_arrPrm['module_unique_id'];
		$this->objMR->setLocalParams( $_arrPrm );
	}

	// запуск модуля с текущими настройками
	public function run() {
		$this->runNoTemplateAction();
		$this->beforeRunAspect();
		if ( !empty( $this->params['action'] ) ) {
			$_str=$this->params['action'];
			$this->$_str();
		}
		$this->afterRunAspect();
	}

	// для безшаблонных экшенов вложенности нет, поэтому выполняем такие сразу
	private function runNoTemplateAction() {
		$_arrPrm=$this->objMR->getGlobalParams();
		if ( !in_array( $_arrPrm['flg_tpl'], array( Core_View::$type['xml'], Core_View::$type['json'] ) ) ) {
			return;
		}
		$obj=self::startModule( $_arrPrm );
		$obj->beforeRunAspect();
		$obj->$_arrPrm['action']();
		$obj->afterRunAspect();
	}

	public static function &startModule( $_arrPrm=array() ) {
		if ( !Core_Module_Management_Modules::includeModule( @$_arrPrm['name'] ) ) {
			return false;
		}
		$obj=$_arrPrm['module_unique_id']=(string)Core_A::rand_uniqid();
		$$obj=new $_arrPrm['name']($_arrPrm);
		$$obj->setPrmManual( $_arrPrm ); // в конструктор параметры не передаются если запускаемый экшен из того же модуля что и текущий экшен TODO!!! 24.11.2011
		return $$obj;
	}

	// public для того чтобы в getModuleObject можно было использовать
	public function beforeRunAspect() {
		if( !$this->checkRights() ){
			Core_Data_Errors::getInstance()->setError('You have only read right');
		}
		$this->params=$this->objMR->getParams( $this );
		$this->objStore=new Core_Module_Store( $this ); // интерфейс для хранения переменных экшена
		$this->before_run_parent();
	}

	private function checkRights(){
		if( !empty($_POST)&&!Core_Acs::haveWrite() ){
			unset($_POST);
			return false;
		}
		// order|page разрешено передавать в _get при доступе на чтение
		$_tmpGET=$_GET;
		if(!empty($_tmpGET['order'])){
			unset($_tmpGET['order']);
		}
		if(!empty($_tmpGET['page'])){
			unset($_tmpGET['page']);
		}
		if(!empty($_tmpGET['flg_type'])){
			unset($_tmpGET['flg_type']);
		}
		if(!empty($_tmpGET['logout'])){
			unset($_tmpGET['logout']);
		}
		if( !empty($_tmpGET)&&!Core_Acs::haveWrite() ){
			unset($_GET);
			return false;
		}
		return true;
	}

	public function before_run_parent() {}

	public function afterRunAspect() {
		$this->after_run_parent();
		$this->getOutHash();
		if ( !empty( $this->out_js ) ) { // depercated!!! array
			$this->out=$this->out_js;
		}
		Core_View::factory( $this->params['flg_tpl'] )
			->setTemplate( $this->getTemplatePath() )
			->setHash( $this->out )
			->parse()
			->header()
			->show();
		// нужно ли это TODO!!! 16.08.2011
		if ( !empty( $this->_redirectAfterRecursion ) ) {
			$this->objML->initLocation( $this ); // восстановим в location текущий модуль т.к. возможно была модкульная рекурсия
			$this->objML->location( $this->_redirectAfterRecursion ); // редирект после модульной рекурсии
		}
	}

	public function after_run_parent() {}

	private function getOutHash() {
		if ( in_array( $this->params['flg_tpl'], array( Core_View::$type['xml'], Core_View::$type['json'] ) ) ) {
			return;
		}
		$this->out['arrPrm']=&$this->params; // во время исполнения экшена могут быть добавлены значения поэтому &
		$this->out['arrNest']=$this->objMR->getGlobalParams();
		$this->out['arrCurDirect']=$this->objMR->curPathDirect;
		$this->out['arrCurReverse']=$this->objMR->curPathReverse;
		$this->out['arrUser']=&Core_Users::$info;
		$this->out['arrParentUser']=Core_Users::getInstance()->getCache();
		$this->out['config']=&$this->config; // в каждом шаблоне доступен оъект с конфигом
		$this->out['strBackUrl']=$this->objML->get();
		$this->out['arrClientInfo']=&$this->objSniff->_browser_info;
		if ( Zend_Registry::isRegistered( 'translate' ) ) { // проект не мультиязычный
			$this->out['translate']=Zend_Registry::get( 'translate' );
		}
	}

	public function set_cfg() {}

	public function getUniqueId() {
		return $this->_uniqueId;
	}

	public function getModuleName() {
		return $this->_moduleName;
	}

	private function getTemplatePath() {
		return Zend_Registry::get( 'config' )->path->relative->source.$this->getModuleName().DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.$this->getModuleName().'.tpl';
	}

	public function getModuleAction( &$strRes ) {
		$_arrPrm=$this->objMR->getGlobalParams();
		if ( empty( $_arrPrm['action'] ) ) {
			return false;
		}
		$strRes=$_arrPrm['action'];
		return true;
	}

	public function getViewMode( &$strRes ) {
		$_arrPrm=$this->objMR->getGlobalParams();
		if ( empty( $_arrPrm['flg_tpl'] ) ) { // ссылочный экшен (обычный)
			return false;
		}
		$strRes=$_arrPrm['flg_tpl']; // попапы и прочая
		return true;
	}

	public function location( $_mix='', $_flgSkipBack=0 ) {
		$this->objML->location( $_mix, $_flgSkipBack );
	}

	public function moduleManagement( $_strName='', $_strMethod='' ) {
		if ( empty( $_strName )||empty( $_strMethod ) ) {
			return false;
		}
		if ( $_strName==$this->getModuleName() ) {
			$_obj=&$this;
		} else {
			$_obj=$this->startModule( array( 'name'=>$_strName ) );
		}
		$objCMMM=new Core_Module_Management_Modules();
		$objCMMM->setConfig( $_obj );
		$objCMMM->$_strMethod();
		return Zend_Registry::get( 'objUser' )->reload();
	}
}
?>