<?php


/**
 * Class for manage source modules
 */
class Core_Module_Management_Modules extends Core_Services {

	private $_moduleId=0;
	private $_installTime=0;
	private $_script=array();
	private $_tableFields=array( 'id', 'name', 'title', 'description', 'added' );
	private $_tableFieldsActions=array( 'id', 'module_id', 'flg_tree', 'flg_tpl', 'action', 'title', 'description', 'added' );
	private $_arrMod=array();
	private $_status='';

	private $_installedActions=array();
	private $_pages=array();
	private $_rights;
	private $objPages;
	private $moduleRouter;

	public function __construct() {}

	public function initModule( &$module ) {
		$this->setConfig( $module );
		if ( !Zend_Registry::get( 'config' )->engine->check_installed ) {
			return;
		}
		if ( !$this->isModuleInstalled() ) {
			$this->install();
		}
	}

	public function setConfig( &$module ) {
		$module->set_cfg();
		if ( !empty( $module->inst_script ) ) {
			$this->_script=&$module->inst_script;
		}
		$this->_script['module']['name']=$module->getModuleName();
		$this->moduleRouter=&$module->objMR;
	}

	public final function install() {
		$this->_status='install';
		if ( !$this->preparation() ) {
			return false;
		}
		if ( $this->isModuleInstalled() ) {
			return true;
		}
		if ( !$this->setModule()||!$this->setActions() ) {
			return false;
		}
		return $this->setTables();
	}

	public final function update() {
		$this->_status='update';
		if ( !$this->preparation() ) {
			return false;
		}
		if ( !$this->isModuleInstalled() ) {
			return false;
		}
		if ( !$this->setModule()||!$this->setActions() ) {
			return false;
		}
		return $this->setTables();
	}

	public final function reinstall() {
		$this->uninstall();
		$this->install();
	}

	public final function uninstall() {
		$this->_status='uninstall';
		if ( !$this->preparation() ) {
			return false;
		}
		if ( !$this->isModuleInstalled() ) {
			return false;
		}
		// удаление страниц из бэкенд ветки связанной с модулем
		if ( !empty( $this->_pages['module'] )&&$this->objPages
			->onlyOne()
			->withPid( $this->_pages['module']['pid'] )
			->withRootId( $this->_pages['module']['root_id'] )
			->withSysName( $this->_pages['module']['sys_name'] )
			->getList( $_arrPage )
			->checkEmpty() ) {
			$this->objPages->withIds( $_arrPage['id'] )->del();
		}
		// удаляем права связанные с этими экшенами
		$this->_rights->onlyIds()->likeModuleName( $this->_script['module']['name'] )->getList( $arrIds )->withIds( $arrIds )->del();
		// удалене модуля и экшенов
		Core_Sql::setExec( '
			DELETE m, a
			FROM sys_action a
			LEFT JOIN sys_module m ON a.module_id=m.id
			WHERE m.id IS NULL OR m.id="'.$this->_moduleId.'"
		' );
		return $this->setTables();
	}

	private function preparation() {
		if ( empty( $this->_script['module'] ) ) {
			return false;
		}
		$this->_installTime=time();
		if ( $this->preparePagesInfo() ) {
			$this->objPages=new Core_Module_Management_Pages();
		}
		$this->_rights=new Core_Acs_Rights();
		$this->_script['module']['added']=$this->_installTime;
		return true;
	}

	// данные из конфига подготавливаются для инсталлирования в бэкнед дерево
	private function preparePagesInfo() {
		if ( empty( $this->_script['actions'] ) ) {
			return false;
		}
		foreach( $this->_script['actions'] as $k=>$v ) {
			if ( empty( $this->_script['actions'][$k]['title'] ) ) {
				$this->_script['actions'][$k]['title']=$this->_script['actions'][$k]['action'];
			}
			if ( empty( $v['flg_tree'] )||$v['flg_tree']==2 ) {
				$this->_pages['actions'][$v['action']]=array(
					'root_id'=>$this->moduleRouter->backend['root_id'],
					'sys_name'=>$v['action'],
					'title'=>$v['title'],
					'added'=>$this->_installTime,
				);
			}
		}
		if ( !empty( $this->_pages ) ) { // если страниц нет то и корневую с именем модуля тоже ненужно
			$this->_pages['module']=array(
				'pid'=>$this->moduleRouter->backend['root_id'],
				'root_id'=>$this->moduleRouter->backend['root_id'],
				'sys_name'=>$this->_script['module']['name'],
				'title'=>$this->_script['module']['title'],
				'added'=>$this->_installTime,
			);
		}
		return !empty( $this->_pages['module'] );
	}

	private function isModuleInstalled() {
		if ( empty( $this->_script['module']['name'] ) ) {
			throw new Exception( Core_Errors::DEV.'|no module name found' );
			return false;
		}
		$_int=Core_Sql::getCell( 'SELECT id FROM sys_module WHERE name="'.$this->_script['module']['name'].'" LIMIT 1' );
		if ( empty( $_int ) ) {
			return false;
		}
		$this->_moduleId=$_int;
		return true;
	}

	private function setModule() {
		if ( empty( $this->_moduleId ) ) {
			$this->_moduleId=Core_Sql::setInsert( 'sys_module', $this->get_valid_array( $this->_script['module'], $this->_tableFields ) );
		} else {
			$this->_script['module']['id']=$this->_moduleId;
			Core_Sql::setUpdate( 'sys_module', $this->get_valid_array( $this->_script['module'], $this->_tableFields ) );
		}
		// страница в бэкэнд
		if ( !empty( $this->_pages['module'] ) ) {
			$this->_pages['module']['id']=$this->objPages->setBackendModulePage( $this->_pages['module'] );
		}
		return true;
	}

	private function setActions() {
		if ( $this->_status=='update' ) {
			$this->_installedActions=Core_Sql::getKeyRecord( 'SELECT action, id, flg_tree, flg_tpl, title, description FROM sys_action WHERE module_id="'.$this->_moduleId.'"' );
			$this->_rights->likeModuleName( $this->_script['module']['name'] )->keyRecordForm()->getList( $this->_installedRights );
		}
		if ( !empty( $this->_script['actions'] ) ) {
			foreach( $this->_script['actions'] as $k=>$v ) {
				if ( !empty( $this->_installedActions[$v['action']] ) ) { // update
					$v['flg_tpl']=empty( $v['flg_tpl'] )? 0:$v['flg_tpl'];
					$v['flg_tree']=empty( $v['flg_tree'] )? 0:$v['flg_tree'];
					$v=$v+$this->_installedActions[$v['action']];
				} else { // add
					$v=$v+array( 'module_id'=>$this->_moduleId, 'added'=>$this->_installTime );
				}
				$_intAid=Core_Sql::setInsertUpdate( 'sys_action', $this->get_valid_array( $v, $this->_tableFieldsActions ) );
				// страница в бэкэнд
				if ( !empty( $this->_pages['actions'][$v['action']] ) ) {
					$this->_pages['actions'][$v['action']]['pid']=$this->_pages['module']['id'];
					$this->_pages['actions'][$v['action']]['action_id']=$_intAid;
					$this->_pages['actions'][$v['action']]['id']=$this->objPages->setBackendModulePage( $this->_pages['actions'][$v['action']] );
				}
				// права в систему прав
				$_strSys=$this->_script['module']['name'].'_@_'.$this->_script['actions'][$k]['action'];
				if ( empty( $this->_installedRights[$_strSys] ) ) {
					$arrRight[$_strSys]=array(
						'sys_name'=>$_strSys,
						'title'=>$this->_script['module']['title'].' -> '.$this->_script['actions'][$k]['title'],
						'description'=>empty( $this->_script['actions'][$k]['description'] )? 'module action right':$this->_script['actions'][$k]['description'],
					);
				} else {
					$arrRight[$_strSys]=$this->_installedRights[$_strSys];
					$arrRight[$_strSys]['title']=$this->_script['module']['title'].' -> '.$this->_script['actions'][$k]['title'];
				}
				unSet( $this->_installedActions[$v['action']] );
				unSet( $this->_installedRights[$_strSys] );
			}
			$this->_rights->setEntered( $arrRight )->setMass();
		}
		$this->delNeedlessStuff();
	}

	// удаляем ненужные вещи
	private function delNeedlessStuff() {
		if ( !empty( $this->_installedRights ) ) {
			$this->_rights->withIdsExtract( $this->_installedRights )->del();
		}
		if ( empty( $this->_installedActions ) ) {
			return;
		}
		$_arrIds=$arrRights=array();
		foreach( $this->_installedActions as $k=>$v ) {
			$_arrIds[]=$v['id'];
			// если экшен админский и страница найдена то удаляем её
			if ( !empty( $this->_pages['actions'][$v['action']] )&&$this->objPages
				->onlyOne()
				->withRootId( $this->moduleRouter->backend['root_id'] )
				->withSysName( $v['action'] )
				->withActionId( $v['id'] )
				->getList( $_arrPage )
				->checkEmpty() ) {
				$this->objPages->withIds( $_arrPage['id'] )->del();
			}
			$arrRights[]=$this->_script['module']['name'].'_@_'.$v['action'];
		}
		if ( !empty( $arrRights ) ) {
			$this->_rights->bySysName( $arrRights )->onlyIds()->getList( $arrIds )->withIds( $arrIds )->del();
		}
		if ( !empty( $_arrIds ) ) {
			Core_Sql::setExec( 'UPDATE sys_page SET action_id=0 WHERE action_id IN("'.join( ', ', $_arrIds ).'")' ); // обнуление action_id у страниц фронтэнда
			Core_Sql::setExec( 'DELETE FROM sys_action WHERE id IN("'.join( ', ', $_arrIds ).'")' ); // удаление экшенов
		}
	}

	// создание таблиц по конфигу
	// добавлене новых при апдэйте TODO!!! 10.04.2009
	// удаление
	private function setTables() {
		if ( empty( $this->_script['tables'] ) ) {
			return true;
		}
		switch( $this->_status ) {
			case 'install':
				foreach( $this->_script['tables'] as $v ) {
					Core_Sql::setExec( $v );
				}
			break;
			case 'update': break;
			case 'uninstall': Core_Sql::setExec( 'DROP TABLE IF EXISTS '.join( ', ', array_keys( $this->_script['tables'] ) ) ); break;
		}
		return true;
	}

	public static function getModulesWithActions( &$arrRes, $_flgType=0 ) {
		self::getListFromDb( $_arrM );
		foreach( $_arrM as $v ) {
			$arrRes[$v['title']]=Core_Sql::getKeyVal( '
				SELECT id, CONCAT( title," (",action,")" )
				FROM sys_action
				WHERE module_id="'.$v['id'].'" AND flg_tree'.(empty($_flgType)? '!=0':'=0').'
				ORDER BY title
			' );
			if ( empty( $arrRes[$v['title']] ) ) {
				unSet( $arrRes[$v['title']] );
			}
		}
	}

	public static $include=array();

	// самодельный автолодер модулей
	// использоавть зендовский (придётся приводить модули к виду Source_ModuleName)
	// TODO!!!
	public static function includeModule( $_strName='' ) {
		if ( empty( $_strName ) ) {
			trigger_error( 'module $name param not set' );
			return false;
		}
		if ( in_array( $_strName, self::$include ) ) {
			return true;
		}
		self::$include[]=$_strName;
		$config=Zend_Registry::get( 'config' );
		if ( !file_exists( $config->path->relative->source.$_strName.DIRECTORY_SEPARATOR.$_strName.'.class.php' ) ) {
			trigger_error( 'module "'.$_strName.'" not found' );
			return false;
		}
		include_once $config->path->relative->source.$_strName.DIRECTORY_SEPARATOR.$_strName.'.class.php';
		return true;
	}

	public static function getModuleList( &$arrRes ) {
		if ( !self::getListFromFs( $arrRes )||!self::getListFromDb( $_arrDb ) ) {
			return false;
		}
		foreach( $arrRes as $k=>$v ) {
			if ( empty( $_arrDb[$v] ) ) {
				$arrRes[$k]=array( 'name'=>$v, 'installed'=>false );
			} else {
				$arrRes[$k]=$_arrDb[$v];
				$arrRes[$k]['installed']=true;
			}
		}
		return true;
	}

	private static function getListFromFs( &$arrRes ) {
		$config=Zend_Registry::get( 'config' );
		if ( !$_hdl=@openDir( $config->path->relative->source ) ) {
			return false;
		}
		while ( ( $_strFile=readDir( $_hdl ) )!== false ) {
			if ( in_array( $_strFile, array( '.', '..' ) )||!is_dir( $config->path->relative->source.$_strFile )||!file_exists( $config->path->relative->source.$_strFile.DIRECTORY_SEPARATOR.$_strFile.'.class.php' ) ) {
				continue;
			}
			$arrRes[]=$_strFile;
		}
		closeDir( $_hdl );
		return !empty( $arrRes );
	}

	private static function getListFromDb( &$arrRes ) {
		$_arrMod=Core_Sql::getAssoc( 'SELECT * FROM sys_module ORDER BY title' );
		foreach( $_arrMod as $v ) {
			$arrRes[$v['name']]=$v;
		}
		return !empty( $arrRes );
	}
}
?>