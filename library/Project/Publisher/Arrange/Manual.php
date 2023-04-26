<?php
class Project_Publisher_Arrange_Manual {

	// мессив с проектом
	private $_project;
	// объекты
	private $_logger, $_schedule, $_adapter;

	public static function run( Zend_Log $logger, Core_Data $data ) {
		$_obj=new Project_Publisher_Arrange_Manual( $logger, $data );
		return $_obj->process();
	}

	public function __construct( Zend_Log $logger, Core_Data $data ) {
		$this->_logger=$logger;
		$this->_project=&$data->filtered;
		$this->_schedule=new Project_Publisher_Schedule( $data );
	}

	// стартуем выполнение проекта
	public function process() {
		if ( !$this->_schedule->onlyNonPosted()->withOrder( 'd.site_id--dn' )->withTime( time() )->getList( $arrSchedule )->checkEmpty() ) {
			$this->updateProject();
			return true;
		}
		$_adapter=Project_Content::factory( $this->_project['flg_source'] );
		if( method_exists($_adapter,'withThumb') ){
			$_adapter->withThumb(array(0,1,3));
		}
		$_adapter->withRewrite( $this->_project['flg_rewriting'] )
			->setFilter( $this->_project['settings'] )
			->prepareBody( $arrSchedule );
		Core_Sql::reconnect();
		foreach( $arrSchedule as $_item ){
			$this->_schedule->setData( $_item )->set();
		}
		// Меняем статус контента, после постинга статус поменяется еще раз
		$this->_schedule->setStatus( $arrSchedule, Project_Publisher_Schedule::$_status['errorlocal'] );
		$this->setSites( $arrSchedule );
		// TODO переделать, возможно вынести в отдельный класс ограничения по постингу.
		if( Core_Acs::haveAccess('Content Website Builder') ){
			foreach($this->_sites as &$_sites ){
				Project_Publisher::checkLimit( $_sites['posts'] );
					if( $_sites['posts'][0]['flg_type']==Project_Sites::NCSB&&($_sites['posts'][0]['content_count']+count($_sites['posts']))>30 ){
						$_sites['posts']=array_slice($_sites['posts'],0,(30-$_sites['posts'][0]['content_count']));
					}
			}
		}
		$this->publicate();
		$this->updateProject();
		return true;
	}

	// обновляем данные о проекте
	private function updateProject() {
		Project_Publisher::update( 'counter', $this->_project['counter'], $this->_project['id'] );
		if ( empty( $this->_project['end'] )&&$this->_schedule->onlyNonPosted()->getList( $_arrSchedule )->checkEmpty() ) { // проект ещё не закончен - не всё опубликовали
			return;
		} elseif ( $this->_project['end']>time() ) { // проект ещё не закончен - указана дата окончания и она в будущем
			return;
		}
		$this->applyNetworking(); // по окончанию публикации добавляем ссылки
		Project_Publisher::status( 'complete', $this->_project['id'] );
		Project_Publisher::saveLog( $this->_logger, $this->_project['id'], 'Project completed' );
	}

	// публикуем контент заново с добавлением сетевых ссылок
	private function applyNetworking() {
		if ( !$this->_schedule->generateNetworking( $_arrSchedule ) ) {
			return;
		}
		Project_Publisher::saveLog( $this->_logger, $this->_project['id'], 'update project content for Network linking' );
		Project_Publisher::status( 'crossLinking', $this->_project['id'] );
		foreach( $_arrSchedule as $k=>$from ) {
			$_adapter=Project_Publisher_Adapter_Factory::get( $from['flg_type'] );
			foreach( $_arrSchedule as $to ) {
				if ( $from['link_to']==$to['id'] ) { // закольцованая сеть (circular)
					$_arrSchedule[$k]['body'].=$_adapter->getLink( $to );
				}
				if ( $from['link_to_master']==$to['id'] ) { // сеть с ведущим блогом (master blog)
					$_arrSchedule[$k]['body'].=$_adapter->getLink( $to );
				}
			}
		}
		$this->setSites( $_arrSchedule );
		$this->publicate();
	}

	// распределяем контент по сайтам
	private function setSites( &$_arrSchedule ) {
		$this->_sites=array();
		foreach( $_arrSchedule as $v ) {
			if ( !empty( $this->_project['tags'] ) ) { // тэги нужны только для блогфьюжн пока
				$v['tags']=$this->_project['tags'];
			}
			$this->_sites[$v['site_id']]['posts'][]=$v;
		}
	}

	// публикуем на сайтах
	private function publicate() {
		foreach( $this->_sites as $siteId=>$v ) {
			try{
				if ( empty( $v['posts'] )||empty( $siteId ) ) {
					continue;
				}
				$_adapter=Project_Publisher_Adapter_Factory::get( $v['posts'][0]['flg_type'] );
				$_adapter->setProject( $this->_project );
				Project_Publisher::saveLog( $this->_logger, $this->_project['id'], 'start to publicate post content to ['.$siteId.']');
				$_intCount=count( $v['posts'] );
				if ( !$this->_schedule->setHistory( 
					$_adapter->setSite( $siteId )->setContent( $v['posts'] )->upload(),
					$v['posts']/*$this->_adapter->getContent()*/
				) ){
					Project_Publisher::saveLog( $this->_logger, $this->_project['id'], 'fail publicated '.$_intCount.' of posts' );
					continue;
				}
				$this->_project['counter']+=$_intCount; // при вызове из applyNetworking в БД не сохраняем
				Project_Publisher::saveLog( $this->_logger, $this->_project['id'], 'success publicated '.$_intCount.' of posts' );
			}catch( Exception $e ){
				Project_Publisher::saveLog( $this->_logger, 'Exception for site ['.$siteId.']! '.$e->getMessage() );
			}
		}
	}
}
?>