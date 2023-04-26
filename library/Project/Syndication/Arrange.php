<?php

 /**
 * размещение КТ на СА
 */
class Project_Syndication_Arrange {

	public $logger;
	private $_time=0;
	/**
	* конструктор
	* @return void
	*/
	public function __construct() {
		$this->_time=time();
		$this->setLogger();
	}

	public function run() {
		$this->logger->info( 'Start Project_Syndication_Arrange by crontab at '.date( 'r', $this->_time ) );
		$_intIds=Core_Sql::getField( '
			SELECT id 
			FROM '.Project_Syndication::$tables['project'].' 
			WHERE user_id IN ('.Core_Acs::haveRightAccess(array('services_@_syndication_post_content')).') AND flg_status IN('.Project_Syndication::$stat['approved'].','.Project_Syndication::$stat['progress'].')
			LIMIT 10
		' ); // разрешённые и стартовавшие проекты
		if ( empty( $_intIds ) ) {
			$this->logger->info( 'Stop Project_Syndication_Arrange::run - no project exists' );
			return false;
		}
		foreach( $_intIds as $v ) {
			$this->_projectId=$v;
			$this->process();
		}
		$this->logger->info( 'Finish Project_Syndication_Arrange by crontab at '.date( 'r' ) );
		return true;
	}

	private function process() {
		$data=new Core_Data( Core_Sql::getRecord( 'SELECT * FROM '.Project_Syndication::$tables['project'].' WHERE id='.$this->_projectId ) );
		$data->setFilter();
		$this->logger->info( 'Process "'.$data->filtered['title'].'" ['.$data->filtered['id'].'] project start' );
		if ( $data->filtered['flg_status']==Project_Syndication::$stat['approved'] ) { // генерация плана текущего проекта
			// устанавливаем владельца проекта для того чтобы
			// сайты на которые будем постить выбирались без сайтов владельца проекта
			Zend_Registry::get( 'objUser' )->withCashe()->setById( $data->filtered['user_id'] );
			$plan=new Project_Syndication_Content_Plan( $data );
			if ( !$plan->generate() ) {
				$this->logger->info( 'Can\'t generate project post plan for "'.$data->filtered['title'].'" ['.$data->filtered['id'].'] project' );
				Project_Syndication_Content::getInstance( $this->_projectId )->status( 'error' ); // весь KT - ошибки
				Project_Syndication::status( 'completed', $this->_projectId );
				return;
			}
			Zend_Registry::get( 'objUser' )->retrieveFromCashe();
			// проверка на то хватит ли пойнтов для постинга проекта
			Project_Syndication::status( 'progress', $this->_projectId );// выполнение проекта в процессе
		} else {
			// проверка на то хватит ли пойнтов для постинга проекта
		}
		$this->postContent($data);
		$this->logger->info( 'Process "'.$data->filtered['title'].'" ['.$data->filtered['id'].'] project finish' );
	}

	private function postContent($data) {
		Project_Syndication_Content_Plan::getProjectPlan( $_arrPlan, $arrContent, $this->_projectId );
		Zend_Registry::get( 'objUser' )->setZero();
		foreach( $_arrPlan as $v ) {
			if ( !Project_Syndication_Adapter_Factory::get( $v[0]['site_type'] )->setSite( $v[0]['site_realid'] )->setPlan( $v )->setContent( $arrContent )->upload() ) {
				// в случае ошибки для всех КТ выставляем 2 (error)
				Project_Syndication_Content_Plan::setStatus( $v, Project_Syndication_Content_Plan::$stat['error'] );
			}
			// для запощенного КТ выставляем 1 (published)
			Project_Syndication_Content_Plan::setStatus( $v, Project_Syndication_Content_Plan::$stat['published'] );
		}
		Project_Syndication_Checker::setUrls( Project_Syndication_Adapter_Factory::getLastUrls() ); // ссылки на контент на сайтах
		if ( Project_Syndication_Content_Plan::isCompleted( $this->_projectId ) ) {
			Project_Syndication_Content::getInstance( $this->_projectId )->status();
			Project_Syndication::status( 'completed', $this->_projectId );
			Project_Syndication_Counters::setPlacementPoint( $this->_projectId );
			$this->logger->info( 'Process "'.$data->filtered['title'].'" ['.$data->filtered['id'].'] project and content status updated' );
		}
	}

	private function setLogger() {
		$formatter = new Zend_Log_Formatter_Simple( Zend_Log_Formatter_Simple::DEFAULT_FORMAT.(php_sapi_name()=='cli'?PHP_EOL:'<br />'));
		$writer=new Zend_Log_Writer_Stream( 'php://output' );
		$writer->setFormatter( $formatter );
		$this->logger = new Zend_Log( $writer );
	}
}
?>