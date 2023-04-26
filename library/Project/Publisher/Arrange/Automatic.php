<?php
class Project_Publisher_Arrange_Automatic {

	// мессив с проектом
	private $_project;
	// объекты
	private $_logger, $_autosites;

	public static function run( Zend_Log $logger, Core_Data $data ) {
		$_obj=new Project_Publisher_Arrange_Automatic( $logger, $data );
		return $_obj->process();
	}

	public function __construct( Zend_Log $logger, Core_Data $data ) {
		$this->_logger=$logger;
		$this->_project=&$data->filtered;
	}

	public function process() {
		if ( !$this->prepare() ) {
			$this->updateProject();
			return false;
		}
		$this->publicate();
		$this->updateProject();
		return true;
	}

	private function updateProject() {
		Project_Publisher::update( 'counter', $this->_project['counter'], $this->_project['id'] );
		$_intNextStart=$this->_project['start']+( 86400*$this->_project['post_every'] );
		// если дата окончания не указана или следующий старт будет раньше чем $this->_project['end']
		if ( empty( $this->_project['end'] )||$_intNextStart<$this->_project['end'] ) {
			Project_Publisher::update( 'start', ( $this->_project['start']+( 86400*$this->_project['post_every'] ) ), $this->_project['id'] );
			Project_Publisher::saveLog( $this->_logger, $this->_project['id'], 'Updated project start time' );
			return;
		}
		Project_Publisher::status( 'complete', $this->_project['id'] );
		Project_Publisher::saveLog( $this->_logger, $this->_project['id'], 'Project completed' );
	}

	private function prepare() {
		$this->_autosites=new Project_Publisher_Autosites( $this->_project['id'] );
		if ( !$this->_autosites->getList( $this->_sites )->setSiteToKey( $this->_sites )->checkEmpty()||
			!Project_Content::factory( $this->_project['flg_source'] )
				->setFilter( $this->_project['settings'] )
				->setLimited( $this->_project['post_num'] )
				->withJson()
				->setCounter( $this->_project['counter'] )
				->getList( $_arrContent )
				->checkEmpty() ) {
			Project_Publisher::saveLog( $this->_logger, $this->_project['id'], 'Can\'t find content for project '. Core_Data_Errors::getInstance()->getErrorFlowShift() );
			return false;
		}
		foreach( $_arrContent as &$_item ){
			$_item['body']=$_item['fields'];
		}
		// TODO переделать, возможно вынести в отдельный класс ограничения по постингу.
		if( Core_Acs::haveAccess('Content Website Builder') ){
			Project_Publisher::checkLimit( $this->_sites );
			foreach( $this->_sites as $_key=>$_site ){
				if( $_site['flg_type']==Project_Sites::NCSB&&($_site['content_count']+count($_arrContent))>30 ){
					unset($this->_sites[$_key]);
				}
			}
		}
		if(empty($this->_sites)){
			return true;
		}
		$_adapter=Project_Content::factory( $this->_project['flg_source'] );
		if( method_exists($_adapter,'withThumb') ){
			$_adapter->withThumb(array(0,1,3));
		}
		$_adapter->withRewrite( $this->_project['flg_rewriting'] )
			->setFilter( $this->_project['settings'] )
			->prepareBody( $_arrContent );
		Core_Sql::reconnect();
		$this->_cashe=new Project_Publisher_Cache( $this->_project['id'] );
		$this->_cashe->setSiteList( $this->_sites );
		foreach( $_arrContent as $v ) {
			if ( !empty( $this->_project['tags'] ) ) { // тэги нужны только для блогфьюжн пока
				$v['tags']=$this->_project['tags'];
			}
			// из-за этого возможно не весь указанный объём контента будет опубликован
			// возможно потребуется хотябы один раз сделать добор контента чтобы количество было равно $this->_project['post_num']
			if ( !$this->_cashe->getUniqSite( $v ) ) {
				continue;
			}
			$_siteId=$this->_cashe->getSiteId();
			$v['ext_category_id']=$this->_sites[$_siteId]['ext_category_id'];
			$this->_sites[$_siteId]['posts'][]=$v;
		}
		return true;
	}

	private function publicate() {
		$_place=new Project_Placement();
		foreach( $this->_sites as $siteId=>$v ) {
			try{
				if ( empty( $v['posts'] ) ) {
					continue;
				}
				if( empty($v['site_id']) ){
					Project_Publisher::saveLog( $this->_logger, $this->_project['id'], 'fail publicated site_id is empty' );
					continue;
				}
				$_adapter=Project_Publisher_Adapter_Factory::get( $v['flg_type'] );
				if( !$_adapter ){
					continue;
				}
				$_sites=new Project_Sites( $v['flg_type'] );
				$_sites->withIds($siteId)->onlyOne()->getList($_arrSite);
				if( empty($_arrSite) || !$_place->withIds($_arrSite['placement_id'])->onlyOne()->getList($_tmpPlace)->checkEmpty() ){
					continue;
				}
				$_adapter->setProject( $this->_project );
				Project_Publisher::saveLog( $this->_logger, $this->_project['id'], 'start to publicate post content to ['.$siteId.']  type='.$v['flg_type']);
				$_intCount=count( $v['posts'] );
				if ( !$_adapter->setSite( $siteId )->setContent( $v['posts'] )->upload() ) {
					$_adapter->getErrors( $_errors );
					Project_Publisher::saveLog( $this->_logger, $this->_project['id'], 'Errors: '.implode("\n",$_errors) );
					Project_Publisher::saveLog( $this->_logger, $this->_project['id'], 'fail publicated '.$_intCount.' of posts' );
					continue;
				}
				$this->_cashe->setSiteId( $v['site_id'] )->setMass();
				$this->_project['counter']+=$_intCount;
				Project_Publisher::saveLog( $this->_logger, $this->_project['id'], 'success publicated '.$_intCount.' of posts' );
			}catch( Exception $e ){
				Project_Publisher::saveLog( $this->_logger, 'Exception for site ['.$siteId.']! '.$e->getMessage() );
			}
		}
	}
}
?>