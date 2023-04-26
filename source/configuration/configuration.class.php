<?php
/**
 * WorkHorse Framework
 *
 * @category WorkHorse
 * @package ProjectSource
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2005-2012, Rodion Konnov
 * @author Rodion Konnov <kindzadza@mail.ru>
 * @date 22.11.2011
 * @version 2.0
 */


/**
 * Management modules, sites & site's trees
 *
 * @category WorkHorse
 * @package ProjectSource
 * @copyright Copyright (c) 2005-2012, Rodion Konnov
 * @license http://opensource.org/licenses/ MIT License
 */
class configuration extends Core_Module {

	public final function set_cfg() {
		$this->inst_script=array(
			'module'=>array(
				'title'=>'Configuration',
			),
			'actions'=>array(
				array( 'action'=>'apache', 'title'=>'Hosting' ),
				array( 'action'=>'modules', 'title'=>'Modules' ),
				array( 'action'=>'backups', 'title'=>'DB backups' ),
				array( 'action'=>'svn_backups', 'title'=>'SVN backups' ),
				array( 'action'=>'sites_list', 'title'=>'Sites list' ),
				array( 'action'=>'set_site', 'title'=>'Set site' ),
				array( 'action'=>'sites_map', 'title'=>'Sites map' ),
				array( 'action'=>'set_page', 'title'=>'Set page' ),
				array( 'action'=>'ajax_fillfields', 'title'=>'Fill fields', 'flg_tpl'=>3 ),
				array( 'action'=>'view_table', 'title'=>'View DB table', 'flg_tpl'=>1 ),
			),
			'needed'=>array(),
		);
	}

	public function apache(){

	}

	public function view_table(){
		if (!empty($_GET['table'])){ 
			$_obj=new Core_Sql_Backup();
			$this->out['arrList']=$_obj->withPagging(array( 
			'url'=>@$_GET, 
			'reconpage'=>50,
			'numofdigits'=>Core_Users::$info['arrSettings']['page_links'],
			))->setTable($_GET['table'])->b_view_table();
			$this->out['arrColumns']=$_obj->b_get_table_columns();
			$_obj->getPaging( $this->out['arrPg'] );
		}
	}
	
	function svn_backups() {
		// тут нужно реализовать shell запросы к продакшну
		
		
	}
	
	function backups() {
		$this->objMb=new Core_Sql_Backup();
		if ( !empty( $_GET['delete'] ) ) {
			$this->objMb->b_del_dump( $_GET['delete'] );
			$this->location( Core_Module_Router::$uriVar );
		}
		if ( !empty( $_GET['restore'] ) ) {
			set_time_limit(0);
			ignore_user_abort(true);
			$this->objMb->b_set_dump( $_GET['restore'] );
			$this->location( Core_Module_Router::$uriVar );
		}
		if ( !empty( $_GET['backup_sys'] ) ) {
			$_POST['arrSet']['tables']=array( 'sys_action', 'sys_module', 'sys_page', 'sys_site', 'u_groups', 'u_rights2group', 'u_rights','u_link2template','u_link2source','u_link2hosting' );
		}
		if ( !empty( $_POST['arrSet'] ) ) {
			$this->objMb->b_create_dump( $_POST['arrSet'] );
			$this->location( Core_Module_Router::$uriVar );
		}
		$this->objMb->b_get_dumps_list( $this->out['arrDumps'] );
		$this->objMb->b_get_db_tables( $this->out['arrTables'] );
	}

	public function modules() {
		if ( $this->moduleManagement( @$_POST['arrM']['name'], @$_POST['arrM']['mode'] ) ) {
			$this->location( Core_Module_Router::$uriFull );
		}
		Core_Module_Management_Modules::getModuleList( $this->out['arrMod'] );
	}

	public function sites_list() {
		$_sites=new Core_Module_Management_Sites();
		$_sites->getList( $this->out['arrSites'] );
	}

	public function set_site() {}

	public function sites_map() {
		// действия со страницами дерева
		$_pages=Zend_Registry::get( 'pages' );
		if ( !empty( $_POST['arrTree']['mode'] ) ) {
			$_pages->withIds( $_POST['arrTree']['id'] );
			switch( $_POST['arrTree']['mode'] ) {
				case 'page_del': $_pages->del(); break;
				case 'page_up': $_pages->up( $_POST['arrTree']['id'] ); break;
				case 'page_dn': $_pages->down( $_POST['arrTree']['id'] ); break;
				case 'page_site': $_pages->onSiteMap( $_POST['arrTree']['id'] ); break;
			}
			$this->location( array( 'wg'=>true ) );
		}
		// данные для селекта сайтов
		$_sites=new Core_Module_Management_Sites();
		$_sites->getList( $this->out['arrSites'] );
		if ( empty( $_GET['root_id'] ) ) {
			return;
		}
		$_sites->onlyOne()->withRootId( $_GET['root_id'] )->getList( $this->out['arrCurrentSite'] );
		// данные для отрисовки дерева
		$this->out['arrTree']=array();
		$arrRes=array(
			'MOD_TREE'=>&$this->out['arrTree'], 
		);
		$_pages->withRootId( $_GET['root_id'] )->withRootNode()->getTree( $arrRes );
	}

	public function set_page() {
		if ( empty( $_GET['root_id'] ) ) {
			$this->location( array( 'action'=>'sites_map' ) );
		}
		$_pages=Zend_Registry::get( 'pages' );
		if ( !empty( $_POST['arrPage'] ) ) {
			if ( $_POST['mode']=='chenge_pid' ) { // перерисовка при смене парента (для обновлния arrPos) можно сделать через ajax
				$this->out['arrPage']['pid']=$_POST['arrPage']['pid'];
			} elseif( $_pages->setEntered( $_POST['arrPage'] )->set() ) { // пробуем сохранить
				//$this->objStore->toAction( 'manage' )->set( array( 'msg'=>'saved' ) );
				$_pages->getEntered( $_arrPage );
				$this->location( array( 'wg'=>'id='.$_arrPage['id'] ) );
			} else {
				$_pages->getErrors( $this->out['arrErr'] )->getEntered( $this->out['arrPage'] );
			}
		} elseif( !empty( $_GET['id'] ) ) { // редактирование существующей страницы
			$_pages->editMode()->onlyOne()->withIds( $_GET['id'] )->getList( $this->out['arrPage'] );
		} elseif( !empty( $_GET['pid'] ) ) { // если создаём подчинённую страницуы
			$this->out['arrPage']['pid']=$_GET['pid'];
		} else { // если парент не указан то им является root_id дерева
			$this->out['arrPage']['pid']=$_GET['root_id'];
		}
		// это для выставления позиции ноды
		if ( !empty( $this->out['arrPage']['pid'] ) ) {
			$_pages->toPosition()->withOrder( 'd.sort--dn' )->withPid( $this->out['arrPage']['pid'] )->getList( $this->out['arrPos'] );
		}
		// информация по сайтам и текущему отдельно
		$_sites=new Core_Module_Management_Sites();
		$_sites->onlyOne()->withRootId( $_GET['root_id'] )->getList( $this->out['arrSite'] );
		$_sites->toSelect()->getList( $this->out['arrSites'] );
		// список экшенов с модулями
		Core_Module_Management_Modules::getModulesWithActions( $this->out['arrModulesWithActions'], $this->out['arrSite']['flg_type'] );
		// дерево к которому принадлежит редактируемая страница
		$this->out['arrTree']=array();
		$arrRes=array(
			'MOD_TREE'=>&$this->out['arrTree'], 
		);
		$_pages->withRootId( $_GET['root_id'] )->withRootNode()->getTree( $arrRes );
	}

	public function ajax_fillfields() {
		if ( empty( $_GET['type'] )||empty( $_POST['data'] ) ) {
			$this->out_js['error']='type or data don\'t set';
			return;
		}
		$this->out_js['data']=$_GET['type']=='meta_description' ? 
			Core_String::getInstance( $_POST['data'] )->metaDescription( 200, ' ...' ):
			Core_String::getInstance( $_POST['data'] )->metaKeywords( 10 );
		$this->out_js['error']=false;
	}
}
?>