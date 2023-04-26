<?php


/**
 * Route link for system through site tree
 */
class Core_Module_Router implements Core_Singleton_Interface {

	private static $_tree=array(); // данные дерева текущего сайта
	private static $_urls=array(); // прямые ссылки
	private static $_uris=array(); // обратные ссылки
	private static $_byIds=array(); // key массива является id

	public static $uriFull; // $_SERVER['REQUEST_URI']
	public static $uriVar; // $_SERVER['REQUEST_URI'] до знака '?'
	public static $curSiteName; // sys_site.sys_name
	public static $offset; // часть которая идёт вначале uri и не принедлежит дереву сайта (например языки или название бэкэнд)
	public static $domain; // $_SERVER['HTTP_HOST']
	public static $uriWithoutLng; // $_SERVER['REQUEST_URI'] без префикса с обозначением языка (если мультиязычность присутствует)

	private $_currentNode=array(); // данные текущего узла ссылки и парсинг ссылки
	private $_globalPrams=array(); // данные полученнце из дерева и ссылки (доступны всем запускаемым модулям)
	private $_localPrams=array(); // данные установленные для модулей запущенных ручками
	private $_objCMMS;

	public $currentSite=array(); // текущий сайт который открыл пользователь (найден системой)
	public $sites=array(); // все сайты в системе
	public $backend=array();
	public $frontend=array();
	public $frontends=array();
	public $isBackend=false;

	public $curPathDirect=array(); // разбитый на надо путь от начала до конца
	public $curPathReverse=array(); // разбитый на надо путь от конца до начала

	public function __construct() {
		$this->_uri=Core_Uri_Http::getInstance( 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'] );
		self::$domain=$this->_uri->getHost();
		self::$uriFull=$this->_uri->getPathFull();
		self::$uriVar=$this->_uri->getPath();
		if ( !Zend_Registry::isRegistered( 'objMR' ) ) {
			Zend_Registry::set( 'objMR', $this ); // закидываем объект в регистри
		}
		$this->initLanguage();
		$this->initCurrentSites(); // тут потребуется язык
		// инициализация названия массива кук которые будут хранить автологин
		// возможно надо всётаки убрать в Core_Users::linkSessionAndRegistry после рефакторинга роутера TODO!!! 06.12.2011
		Core_Users_Cookie::setCookieName(); // тут потребуется Core_Module_Router::$curSiteName
	}

	/*
	получаем язык из ссылки
	при необходимости ссылка корректируется
	возможное варианты ссылок
	domain+lang+prefix+url
	domain+prefix+url
	*/
	private function initLanguage() {
		if ( !Zend_Registry::isRegistered( 'locale' ) ) { // проект не мультиязычный
			return;
		}
		$this->_locale=Zend_Registry::get( 'locale' );
		$_arrPath=$this->_uri->getPathToArray();
		// в ссылке язык не найден
		if ( !$this->_locale->checkInUrl( $_arrPath[0] ) ) {
			header( 'Location: /'.$this->_locale->getLanguage().$this->_uri->getPathFull() );
			exit;
		}
		// в ссылке язык кторый не поддерживается системой
		if ( !$this->_locale->checkSupporting( $_arrPath[0] ) ) {
			$_arrPath[0]=$this->_locale->getLanguage();
			$this->_uri->setPath( '/'.implode( '/', $_arrPath ).'/' );
			header( 'Location: '.$this->_uri->getPathFull() );
			exit;
		}
		// настраиваем перевод
		$this->_locale->setTranslator();
		// полная ссылка без языкового суффикса self::$uriWithoutLng
		array_shift( $_arrPath );
		$_strTmp=$this->_uri->getPath();
		$this->_uri->setPath( '/'.(empty( $_arrPath )? '':implode( '/', $_arrPath ).'/') );
		self::$uriWithoutLng=$this->_uri->getPathFull();
		$this->_uri->setPath( $_strTmp );
		// установка настроек пользователя
		Core_Users::updateLng();
	}

	private function initCurrentSites() {
		if ( !Core_Module_Management_Sites::getInstance()->getList( $_arrSites )->checkEmpty() ) {
			throw new Exception( Core_Errors::DEV.'|sites not installed' );
			return;
		}
		foreach( Zend_Registry::get( 'config' )->sites->toArray() as $v ) {
			$this->getPrefix( $v['prefix'] );
			if ( $v['flg_type']=='backend' ) {
				$this->backends[]=$this->sites[]=$this->getSite( $_arrSites, $v );
			} else {
				$this->frontends[]=$this->sites[]=$this->getSite( $_arrSites, $v );
			}
		}
		$this->searchCurrentSite();
	}

	private function getPrefix( &$strPrefix ) {
		if ( Zend_Registry::isRegistered( 'locale' ) ) { // проект не мультиязычный
			$strPrefix='/'.$this->_locale->getLanguage().$strPrefix.'/';
		} else {
			$strPrefix=$strPrefix.'/';
		}
	}

	private function getSite( &$arrSites, $_arr=array() ) {
		foreach( $arrSites as $v ) {
			if ( $v['sys_name']==$_arr['sys_name'] ) {
				return ($_arr+$v);
			}
		}
		return $_arr;
	}

	private function searchCurrentSite() {
		if ( $this->initCurrentSite( $this->backends, 'backend' ) ) {
			$this->isBackend=true;
			return;
		}
		if ( $this->initCurrentSite( $this->frontends, 'frontend' ) ) {
			return;
		}
		throw new Exception( Core_Errors::DEV.'|site not found' );
	}

	private function initCurrentSite( $_arr, $var ) {
		foreach( $_arr as $v ) {
			if ( mb_substr( $this->_uri->getPath().'/', 0, mb_strlen( $v['prefix'] ) )==$v['prefix'] ) {
				$this->$var=$v;
				$this->currentSite=$v;
				self::$curSiteName=$v['sys_name'];
				self::$offset=$v['prefix'];
				return true;
			}
		}
	}






	// можно вызвать из кода чтобы откорректировать ссылку с языком
	public function correctLngInUrl() {
		if ( !Zend_Registry::isRegistered( 'locale' ) ) { // проект не мультиязычный
			return;
		}
		$_strLng=$this->_locale->getLanguage();
		$_arrPath=$this->_uri->getPathToArray();
		if ( $_arrPath[0]==$_strLng ) {
			return;
		}
		$_arrPath[0]=$_strLng;
		$this->_uri->setPath( '/'.implode( '/', $_arrPath ).'/' );
		header( 'Location: '.$this->_uri->getPathFull() );
		exit;
	}

	private function initPath() {
		$arrRes=array(
			'MOD_TREE'=>&self::$_tree, 
			'MOD_URLS'=>&self::$_urls, 
			'MOD_URIS'=>&self::$_uris, 
			'MOD_BYIDS'=>&self::$_byIds, 
		);
		Zend_Registry::get( 'pages' )->withRootId( $this->isBackend?$this->backend['root_id']:$this->frontend['root_id'] )->withRootNode()->getTree( $arrRes );
		$this->parsePath( self::$uriVar );
	}

	private function parsePath( $_strPath='' ) {
		if ( empty( self::$_uris )||empty( $_strPath ) ) {
			return false;
		}
		if ( $_strPath==self::$offset ) { // заглавная страница
			$this->curPathDirect=$this->curPathReverse=array( self::$_uris[self::$offset] );
			return true;
		}
		$this->curPathDirect=explode( '/', substr( substr( $_strPath, strlen( self::$offset ) ), 0, -1 ) );
		$_strUrl=self::$offset;
		foreach( $this->curPathDirect as $k=>$v ) {
			$_strUrl.=$v.'/';
			if ( !empty( self::$_uris[$_strUrl] ) ) {
				$this->curPathDirect[$k]=self::$_uris[$_strUrl];
			} else { // site_backend в дереве нет например TODO!!! 16.04.2009
				unSet( $this->curPathDirect[$k] );
			}
		}
		array_unshift( $this->curPathDirect, self::$_uris[self::$offset] );
		$this->curPathReverse=array_reverse( $this->curPathDirect );
		return !empty( $this->curPathReverse );
	}

	public function getCurrentTree() {
		return self::$_tree;
	}

	// это делаем только раз - проверить initPath
	// берём дерево данного сайта,  находим в нём нужную ноду (определяем по ссылке)
	// пороверяем доступ пользователя к экшену привязанному к странице и устанавливаем в случае успеха глобальные парпметры
	public function setGlobalParams( Core_Module_Interface &$module ) {
		if ( !empty( self::$_tree ) ) { // поидее с объектом этого класса мы работаем через регистри, значит у нас только один экземпляр его. значит этот if ненужен TODO!!!15.08.2011
			return;
		}
		$this->initPath();
		/*if ( !empty( $_REQUEST['new_frontend'] ) ) { // при переключении фронтэнда для редактирования в админке убираем переключающую переменную
			$module->objML->location( self::$uriVar, 'skip' );
		}*/
		if ( !$this->findNode() ) {
			$module->objML->location( self::$offset );
		}
		if ( self::$uriVar!=self::$offset&!$this->isBackend&empty( $this->_currentNode['flg_onmap'] ) ) { // страница скрыта с фронтэнда
			$module->objML->location( $module->objML->get() ); // возвращаем назад
		}
		$this->_globalPrams=$this->_currentNode; // _globalPrams понадобится в $module->objML->uniq()
		$module->objML->uniq(); // записываем уникальные линки в history
		// нету прав на экшен у текущего пользователя
		// self::$uriVar!=self::$offset можно к главному модулю привязывать экшн
		if ( self::$uriVar!=self::$offset&!empty( $this->_currentNode )&!Core_Acs::haveActionAccess( $this->_currentNode ) ) {
			$this->_globalPrams=array(); // нет прав
			$module->objML->location( $module->objML->get() ); // возвращаем назад
		}
	}

	// поидее это надо делать где-то в $this->parsePath();
	private function findNode() {
		$_arrPart=explode( '/', trim( self::$uriVar, '/' ) );
		// если есть ссылка - ищем
		if ( !empty( $_arrPart ) ) {
			$_strUrl='/';
			$_strUrlFind='';
			foreach( $_arrPart as $v ) {
				$_strUrl.=$v.'/';
				if ( !empty( self::$_uris[$_strUrl] ) ) {
					$_strUrlFind=$_strUrl;
					$this->_currentNode=self::$_uris[$_strUrl];
					continue;
				}
				// это условие только из-за того что в админке в дереве нету страницы 
				// со ссылкой /site-backend/ - нужно как-то решить 03.11.2008 TODO!!!
				if ( !empty( $_strUrlFind ) ) {
					break;
				}
			}
		}
		if ( !empty( $this->_currentNode ) ) { // дополнительные переменные могут приходить только в экшен (если доступный не найден то всё игнорим)
			// переменные которые приходят через чпу но не участвуют в определении экшена страницы
			// т.е. дополнительные переменные
			$this->_currentNode['action_vars']=trim( str_replace( $_strUrlFind, '', self::$uriVar ), '/' );
		}
		
//		p( self::$_uris ); // Project_Documents::getBySysName нужно использовать также
		
		// урл не нашёлся
		if ( empty( $this->_currentNode )&&self::$uriVar!=self::$offset ) {
			return false;
		}
		return true;
	}

	public function setLocalParams( $_arr=array() ) {
		if ( empty( $_arr['name'] ) ) {
			return false;
		}
		$this->_localPrams[$_arr['name']][$_arr['module_unique_id']]=$_arr;
	}

	public function getGlobalParams( $_strModuleName='' ) {
		if ( !empty( $_strModuleName ) ) { // в этом случае запрос идёт из getParams для текущего модуля
			if ( empty( $this->_globalPrams['name'] )||$this->_globalPrams['name']!=$_strModuleName ) {
				return array(); // поэтому если глобальные параметры не для текущего модуля то ничего не отдаём
			}
		}
		return $this->_globalPrams;
	}

	public function getParams( Core_Module_Interface &$module ) {
		$_arrLocal=$_arrGlobal=array();
		if ( !empty( $this->_localPrams[$module->getModuleName()][$module->getUniqueId()] ) ) {
			$_arrLocal=$this->_localPrams[$module->getModuleName()][$module->getUniqueId()];
		}
		$_arrGlobal=$this->getGlobalParams( $module->getModuleName() );
		$arrPrm=empty( $_arrGlobal )? $_arrLocal:( $_arrLocal+$_arrGlobal );
		if ( !isSet( $arrPrm['flg_tpl'] ) ) {
			// не во всех случаях flg_tpl выставлен - это потому что для модулей запускаемых 
			// из шаблона нету в params данных из inst_script TODO!!! вообще лучше эмулировать полный набор атрибутов экшена
			$arrPrm['flg_tpl']=0;
		}
		return $arrPrm;
	}

	// переключение текущего фронтэнда для бакэнда (для редактирования дерева например) TODO!!!
	// это в случае если у нас мульти фронтэндная конфигурация. т.е. на одном движке один бэкенд и несколько фронтэнов (магазинов например) 05.01.2012
	/*private function check_admin_frontend_mode() {
		if ( !empty( $this->frontend ) ) {
			return false;
		}
		if ( !empty( $_REQUEST['new_frontend'] ) ) {
			$_SESSION['new_frontend']=$_REQUEST['new_frontend'];
		} elseif ( empty( $_SESSION['new_frontend'] ) ) {
			$this->get_sys_name_by_twoleveldomain( $currentSite );
			foreach( $this->frontends as $v ) {
				if ($v['sys_name']==$currentSite) {
					$_SESSION['new_frontend']=$v['sys_name'];
					break;
				}
			}
			if (empty($_SESSION['new_frontend'])) {
				$_SESSION['new_frontend']=$this->frontends[0]['sys_name'];
			}
		}
		$this->admin_current_frontend=$_SESSION['new_frontend'];
		$_arrHost=array_reverse( explode( '.', $_SERVER['HTTP_HOST'] ) );
		$_arrHost[1]=$this->admin_current_frontend;
		$this->admin_current_frontend_url=join( '.', array_reverse( $_arrHost ) );
	}*/

	// например для ссылок в нотификейшены для админа
	// кстати интересно как это будет работать в шелл при отправки письма?
	// ни Zend_Registry::get( 'pages' ) ни Zend_Registry::get( 'objMR' ) не будет
	// наверно надо будет делать Core_Module_Router::getInstance() при необходимости (посмотреть TODO!!!)
	// и проверить есть ли $this->backend при просмотре фронтэнда и $this->frontend при просмотре бэкенда TODO!!!
	// в этом случае наверно надо указывать системное имя сайта (см.конфиг) по нему берём настройки а дальше как обычно
	// 24.01.2012
	public function generateBackendUrl( &$strUrl, $_arrSetting=array() ) {
		$arrRes=array(
			'MOD_URLS'=>&$_arrSetting['MOD_URLS'], 
			'MOD_BYIDS'=>&$_arrSetting['MOD_BYIDS'], 
		);
		Zend_Registry::get( 'pages' )->withRootId( $this->backend['root_id'] )->withRootNode()->getTree( $arrRes );
		$strUrl=self::generate( $_arrSetting );
		return $strUrl;
	}

	// например для ссылок в нотификейшены отсылаемые из админки пользователям
	public function generateFrontendUrl( &$strUrl, $_arrSetting=array() ) {
		$arrRes=array(
			'MOD_URLS'=>&$_arrSetting['MOD_URLS'], 
			'MOD_BYIDS'=>&$_arrSetting['MOD_BYIDS'], 
		);
		Zend_Registry::get( 'pages' )->withRootId( $this->frontend['root_id'] )->withRootNode()->getTree( $arrRes );
		$strUrl=self::generate( $_arrSetting );
		return $strUrl;
	}

	public static function generateLocationUrl( Core_Module_Interface &$module, $_arrSetting=array() ) {
		// если данные не указаны редиректим на текущий урл без параметров (которые после знака ?)
		if ( empty( $_arrSetting )||$_arrSetting===Core_Module_Location::URLPATH ) {
			return self::$uriVar;
		}
		if ( $_arrSetting===Core_Module_Location::URLFULL ) {
			return self::$uriFull;
		}
		// подразумевается что тут пришла ссылка на которую и редиректим
		if ( is_string( $_arrSetting ) ) {
			return $_arrSetting;
		}
		// при отсутствии имени модуля берём имя текущего модуля
		if ( empty( $_arrSetting['name'] ) ) {
			$_arrSetting['name']=$module->getModuleName();
		}
		// при отсутствии названия экшена берём текущее название
		if ( empty( $_arrSetting['action'] ) ) {
			$module->getModuleAction( $_arrSetting['action'] );
		}
		$_arrSetting['f']=self::$offset;
		return self::getCurrentUrl( $_arrSetting );
	}

	public static function getCurrentUrl( $_arrPrm=array() ) {
		$_arrPrm['MOD_URLS']=&self::$_urls;
		$_arrPrm['MOD_BYIDS']=&self::$_byIds;
		return self::generate( $_arrPrm );
	}

	public static function checkUrlAccess( $name='', $action='' ){
		return Core_Acs::haveActionAccess( array(
			'name'=>$name,
			'action'=>$action
		));
	}

	private static function generate( $_arrSetting=array() ) {
		// если передан id ноды (страницы) - нужно для генерации ссылки на страницу с неуникальным экшеном
		if ( !empty( $_arrSetting['id'] )&!empty( $_arrSetting['MOD_BYIDS'][$_arrSetting['id']] ) ) { 
			$strUrl=$_arrSetting['MOD_BYIDS'][$_arrSetting['id']];
		} elseif ( !empty( $_arrSetting['name'] )&!empty( $_arrSetting['action'] ) ) {
			$strUrl=$_arrSetting['MOD_URLS'][$_arrSetting['name']]['actions'][$_arrSetting['action']]['sys_name'];
		} elseif ( !empty( $_arrSetting['action'] ) ) {} // TODO!!! 16.04.2009
		if ( empty( $strUrl ) ) {
			if ( empty( $_arrSetting['f'] ) ) { // force_generate
				return false;
			}
			//если сгенерировать надо в любом случае, даже если в дереве ссылка не найдена (тут передаём например self::$offset или self::$uriVar)
			$strUrl=is_bool( $_arrSetting['f'] )?'':$_arrSetting['f'];
		}
		$strUrl.=self::vars( array_intersect_key( $_arrSetting, array( 'w'=>1, 'wg'=>1, 'wp'=>1, 'wr'=>1 ) ) );
		return $strUrl;
	}

	private static function vars( $_arrSetting=array() ) {
		foreach( $_arrSetting as $k=>$v ) {
			if ( is_bool( $v ) ) {
				$v=array();
			}
			if ( !is_array( $v ) ) {
				parse_str( $v, $v );
			}
			switch( $k ) {
				case 'w': return self::make( $v ); break; // with_this - добавляет переданную строчку
				case 'wg': return self::make( array_merge( $_GET, $v ) ); break; // with_current_get - добавляет $_GET + переданную строчку
				case 'wp': return self::make( array_merge( $_POST, $v ) ); break;
				case 'wr': return self::make( array_merge( $_REQUEST, $v ) ); break;
			}
		}
		return '';
	}

	private static function make( $_arrDta=array() ) {
		if ( empty( $_arrDta ) ) {
			return '';
		}
		return '?'.http_build_query( $_arrDta );
	}

	/**
	 * экземпляр объекта текущего класса (singleton)
	 *
	 * @var object
	 */
	private static $_instance=NULL;

	/**
	 * возвращает экземпляр объекта текущего класса (singleton)
	 * при первом обращении создаёт
	 *
	 * @return object
	 */
	public static function getInstance() {
		if ( self::$_instance==NULL ) {
			self::$_instance=new self();
		}
		return self::$_instance;
	}
}
?>