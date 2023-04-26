<?php


/**
 * Data management for module (user interface)
 */
 
class Project_Publisher extends Core_Storage {

	/**
	 * Статусы проекта
	 * @var array
	 */
	public static $stat=array(
		'notStarted'=>0,
		'inProgress'=>1,
		'crossLinking'=>2,
		'complete'=>3,
		'error'=>4
	);
	public static $tables=array(
		'project'=>'pub_project',
		'automatic'=>'pub_autosites',
		'cache'=>'pub_cache',
		'schedule'=>'pub_schedule',
		'types'=>'pub_types'
	);

	public static $cnm_host='https://app.ifunnels.com';

	public $table='pub_project';

	public $fields=array('id','user_id','flg_mode','category_id', 'mastersite_id','flg_status','error_log','flg_source','flg_posting','flg_mastersite',
	'flg_circular','flg_rewriting','start','end','time_between','random','title','settings','tags','post_every','post_num','counter','edited','added','flg_run');
	private $_userId=false;
	/**
	 * Object Project_Publisher
	 * @var Project_Publisher null
	 */
	private static $_instance=NULL;
	private $_type=false;
	private $_withRights=false;

	public function __construct( $_withoutUser=false ){
		if ( $_withoutUser ){
			return;
		}
		if ( !Zend_Registry::get( 'objUser' )->getId( $_int ) ){
			throw new Exception( Core_Errors::DEV.'|Zend_Registry::get( \'objUser\' )->getId( $_int ) is not return an User Id' );
			return;
		}
		$this->_userId=$_int;
	}

	public static function checkLimit( &$_arrSites ){
		if(empty($_arrSites)){
			return;
		}
		foreach( $_arrSites as &$_site ){
			$site=new Project_Sites($_site['flg_type']);
			if( !$site->onlyOne()->withIds( $_site['site_id'] )->toJS()->getList( $_arr )->checkEmpty() ){
				continue;
			}
			$_site['content_count']=$_arr['content_count'];
		}
	}

	public static function getInstance(){
		if ( self::$_instance==NULL ){
			self::$_instance=new Project_Publisher( true );
		}
		return self::$_instance;
	}

	public function getProject( &$arrPrj ){
		$this->onlyOne()->getList( $arrPrj );
		if ( $arrPrj['flg_mode'] == '0' ){
			$obj=new Project_Publisher_Autosites( $arrPrj['id'] );
			$obj->getList( $arrPrj['arrSheduleSites'] );
		}else{
			$data=new Core_Data($arrPrj);
			$data->setFilter();
			$obj=new Project_Publisher_Schedule( $data );
			$obj->withGroup( 'title' )->getList( $arrPrj['arrSheduleContent'] );
			$obj->withGroup( 'site_id' )->getList( $arrPrj['arrSheduleSites'] );
		}
	}
	
	/**
	 * Создание проекта
	 *
	 * @return bool
	 */
	public function set(){
		$this->_data->setFilter();
		// проверим не запустился ли проект пока мы его редактировали
		if ( !empty( $this->_data->filtered['id'] )&& $this->_data->filtered['flg_status']==self::$stat['notStarted'] ){
			$_arrPrj=array();
			$this->onlyOne()->withIds( $this->_data->filtered['id'] )->getList( $_arrPrj );
			$this->_data->setElement( 'flg_status', $_arrPrj['flg_status'] );
			// если таки запустился - показываем ошибку (т.к. пользователь отослал все данные а можно только добавлять конетнт)
			if ( !$this->_data->setChecker( array( 'flg_status'=>( $this->_data->filtered['flg_status']!=self::$stat['notStarted'] ) ) )->check() ){
				return $this->setError('Can\'t save, project allready in progress');
			}
		}
		// проект в процессе
		if ( !empty( $this->_data->filtered['flg_status'] )&&$this->_data->filtered['flg_status']==self::$stat['inProgress'] ){
			if ( empty( $_arrPrj ) ){
				$this->withIds( $this->_data->filtered['id'] )->onlyOne()->getList( $_arrPrj );
			}
			$_arrPrj['title'] = $this->_data->filtered['title'];
			$_arrPrj['end'] = $this->_data->filtered['end'];
			$_arrPrj['settings']=serialize($this->_data->filtered['settings']);
			if ( !Core_Sql::setInsertUpdate( $this->table, $_arrPrj )){
				return $this->setError('Can\'t update project');
			}
			// manual проект
			if( $_arrPrj['flg_mode']==1 ){
				$obj=new Project_Publisher_Schedule($this->_data);
				if( !$obj->setContent( json_decode($this->_data->filtered['jsonContentIds'],true) )->addContent() ){
					return $this->setError('Can\'t add content to project');
				}
			}
			return true;
		}
		// рестарт проекта
		if ( !empty( $this->_data->filtered['flg_status'] )&&in_array( $this->_data->filtered['flg_status'], array( self::$stat['complete'], self::$stat['error'] ) )&&$this->_data->filtered['restart'] ){
			$this->_data->setElement( 'flg_status', self::$stat['inProgress'] );
		}
		// если проект ещё не запущен или уже завершён - можно менять все поля
		if ( !$this->_data->setChecker( array(
			'title'=>empty( $this->_data->filtered['title'] ),
			'flg_source'=>empty( $this->_data->filtered['flg_source'] ),
			'flg_posting'=>empty( $this->_data->filtered['flg_posting'] ),
		) )->check() ){
			return $this->setError('Can\'t save project. Please fill all required fields.');
		}
		// For project "all at once"
		if( !empty($this->_data->filtered['flg_run']) ){
			$this->_data
					->setElement('start',time())
					->setElement('random','')
					->setElement('time_between','')
					->setElement('end',time());
		}
		if ( empty( $this->_data->filtered['start'] )  ){
			$this->_data->setElement( 'start', time() );
		}
		if ( empty( $this->_data->filtered['id'] ) ){
			$this->_data
				->setElement( 'added', time() )
				->setElement( 'user_id', $this->_userId );
		} else {
			$this->_data->setElement( 'edited', time() );
		}
		$this->_data->setElement('settings',serialize($this->_data->filtered['settings']));
		if( $this->_data->filtered['flg_rewriting'] > 0 ){
			$this->_data->setElement( 'flg_rewriting', $this->_data->filtered['selectdepth'] );
		}
		// сохраняем проект
		$this->_data->setElement( 'id', Core_Sql::setInsertUpdate( $this->table, $this->_data->setMask( $this->fields )->getValid() ) );
		if ( empty( $this->_data->filtered['id'] ) ){
			return $this->setError('Can\'t save project');
		}
		$this->_data->filtered['settings']=unserialize($this->_data->filtered['settings']);
		$this->setData($this->_data->filtered);
		$this->_data->getFiltered();
		// Добавление контента и сайтов в проект.
		if( !empty($this->_data->filtered['flg_mode']) ){
			// Если нет контента значит возможно проект просто пересохранили без добавления нового контента.
			if( empty($this->_data->filtered['jsonContentIds']) ){
				return true;
			}
			// Manual
			$obj=new Project_Publisher_Schedule( $this->_data );
			if( !$obj->setSites($this->_data->filtered['arrSiteIds'])->setContent( json_decode($this->_data->filtered['jsonContentIds'],true))->generate() ){
				return $this->setError('Can\'t create schedule for manual project');
			}
		} else {
			// Autosite
			$obj=new Project_Publisher_Autosites($this->_data->filtered['id']);
			if( empty($this->_data->filtered['arrSiteIds'])||!$obj->setData( $this->_data->filtered['arrSiteIds'] )->store()){
				return $this->setError('Can\'t add sites in auto project');
			}
		}
		return true;
	}

	/**
	 * Удаление проекта из всех таблиц.
	 * 
	 * @param mix $_mix
	 * @return bool
	 */
	public function del( $_mix=0 ){
		if ( empty( $_mix ) ){
			return $this->setError('id project can\'t by empty');
		}
		$_mix=is_array( $_mix ) ? $_mix:array( $_mix );
		Core_Sql::setExec( '
			DELETE p, s, a, c
			FROM '.$this->table.' p
			LEFT JOIN pub_schedule s ON s.project_id=p.id
			LEFT JOIN pub_autosites a ON a.project_id=p.id
			LEFT JOIN pub_cache c ON c.project_id=p.id
			WHERE p.id IN('.Core_Sql::fixInjection( $_mix ).')
		' );
		return true;
	}

	private $_withStatus=false; // cо статистикой
	private $_toShell=false; // данные для shell-скрипта
	private $_notRun=false;
	private $_withSiteIds=false;

	public function notRun(){
		$this->_notRun=true;
		return $this;
	}

	public function getOwnerId(){
		return $this->_userId;
	}

	public function toShell(){
		$this->_toShell=true;
		return $this;
	}

	public function withStatus(){
		$this->_withStatus=true;
		return $this;
	}

	public function withSiteIds( $_arr ){
		if( !empty($_arr) ){
			$this->_withSiteIds=$_arr;
		}
		return $this;
	}

	public function withRights( $_arr ){
		if( !empty($_arr) ){
			$this->_withRights=$_arr;
		}
		return $this;
	}
	private $_limit=false;

	public function setLimit($_int){
		$this->_limit=$_int;
		return $this;
	}

	protected function init(){
		parent::init();
		$this->_withStatus=false;
		$this->_toShell=false;
		$this->_withRights=false;
		$this->_withSiteIds=false;
		$this->_limit=false;
	}

	protected function assemblyQuery(){
		if ( $this->_onlyIds ){
			$this->_crawler->set_select( 'd.id' );
		} else {
			$this->_crawler->set_select( 'd.*' );
		}
		$this->_crawler->set_from( $this->table.' d' );
		if( $this->_toShell ){
			$this->_crawler->set_where( 'd.flg_status IN ( ' . self::$stat['notStarted'] . ','. self::$stat['inProgress'] . ' )' );
			$this->_crawler->set_where( 'd.start <= '. Core_Sql::fixInjection( time() ) );
		}
		if ( !empty( $this->_withIds ) ){
			$this->_crawler->set_where( 'd.id IN ('.Core_Sql::fixInjection( $this->_withIds ).')' );
		}
		if ( !empty( $this->_withSiteIds ) ){
			$this->_crawler->set_where( 'd.mastersite_id IN ('.Core_Sql::fixInjection( $this->_withSiteIds ).')' );
		}
		if ( $this->_onlyOwner ){
			$this->_crawler->set_where( 'd.user_id='.$this->getOwnerId() );
		}
		if(!empty($this->_withRights)){
			$this->_crawler->set_where('d.user_id IN ('.Core_Acs::haveRightAccess($this->_withRights).')');
		}
		if( $this->_notRun ){
			$this->_crawler->set_where('d.flg_run=0');
		}
		if( $this->_withStatus ){
			$this->_crawler->set_select( '(SELECT COUNT(*) FROM pub_schedule as s WHERE s.project_id = d.id) as count_content' );
			$this->_crawler->set_select( '(SELECT COUNT(*) FROM pub_schedule as s WHERE s.project_id = d.id AND s.flg_status = 1) as count_posted_content' );
		}
		if ( !( $this->_onlyOne||$this->_onlyCell ) ){
			$this->_crawler->set_order_sort( $this->_withOrder );
		}
		if( $this->_limit ){
			$this->_crawler->set_limit( $this->_limit );
		}
	}

	public function getList( &$arrRes ){
		if ( self::$_instance==NULL ){
			$this->onlyOwner();
		}
		$_onlyOne=$this->_onlyOne;
		parent::getList( $arrRes );
		if( $_onlyOne ){
			$this->prepare( $arrRes );
		}
		return !empty($arrRes);
	}

	private function prepare( &$arrRes ){
		if ( empty($arrRes) ){
			return false;
		}
		$arrRes['settings']=unserialize($arrRes['settings']);
		return true;
	}
	
	private static $_strLogData='';
	
	public static function saveLog( Zend_Log $logger, $_projectId ,$_logString, $_flgSave=false ){
		if( empty( $_logString ) || empty( $_projectId ) ){
			return $this;
		}
		$logger->info( $_logString );
		self::$_strLogData.=$_logString."\n";
		if( $_flgSave ){
			Core_Sql::setExec( 'UPDATE '.self::$tables['project'] .' SET error_log="'.Core_Sql::fixInjection( self::$_strLogData ).'" WHERE id ='.Core_Sql::fixInjection( $_projectId ) );
			self::$_strLogData='';
		}
		return $this;
	}
	
	public static function status( $_strKey='', $_arrIds=0 ){
		if( empty($_arrIds) || !in_array($_strKey, array_keys(self::$stat)) ){
			return false;
		}
		return Core_Sql::setExec('UPDATE '.self::$tables['project'] .' SET flg_status='. self::$stat[$_strKey] .' WHERE id IN ('.Core_Sql::fixInjection( $_arrIds ).')');
	}

	public static function update( $strField, $mixValue, $mixIds ){
		if( empty($mixIds) || empty($strField) || empty($mixValue) ){
			return false;
		}
		return Core_Sql::setExec('UPDATE '.self::$tables['project'] .' SET '.$strField .'='.Core_Sql::fixInjection($mixValue) .' WHERE id IN ('.Core_Sql::fixInjection( $mixIds ).')');
	}

}