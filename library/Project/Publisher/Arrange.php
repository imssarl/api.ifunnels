<?php
class Project_Publisher_Arrange {

	public $logger, $project;
	public $log=array();

	public function __construct() {
		$this->setLogger();
		if( !Zend_Registry::isRegistered('rewriter') ){
			Zend_Registry::set('rewriter',new Core_Rewrite());
		}
	}

	public function run() {
		$this->logger->info( 'Start Project_Publisher_Arrange by crontab at '.date( 'r' ) );
		Project_Publisher::getInstance()->setLimit( 20 )->withRights(array('services_@_publisher_post_content'))->notRun()->toShell()->onlyIds()->getList( $_intIds );
		if ( empty( $_intIds ) ) {
			$this->logger->info( 'Stop Project_Publisher_Arrange::run - no project exists' );
			return false;
		}
		Project_Publisher::status( 'inProgress', $_intIds );
		foreach( $_intIds as $v ) {
			if ( !$this->getProject( $v ) ) {
				$this->logger->info( 'Can\'t get project ['.$_intIds.']' );
				continue;
			}
			if ( !$this->process() ) {
				Project_Publisher::status( 'error', $v );
			}
			ob_flush();
		}
		$this->logger->info( 'Finish Project_Publisher_Arrange by crontab at '.date( 'r' ) );
		return true;
	}

	public function publishImmediately( Core_Data $project ) {
		set_time_limit(0);
		ignore_user_abort(true);
		$this->project=$project;
		Project_Publisher::status( 'inProgress', $this->project->filtered['id'] );
		if ( !$this->process() ) {
			Project_Publisher::status( 'error', $this->project->filtered['id'] );
		}
		return true;
	}

	private function process() {
		Project_Publisher::saveLog( $this->logger, $this->project->filtered['id'], 'Process "'.$this->project->filtered['title'].'" ['.$this->project->filtered['id'].'] project start' );
		//$this->logger->info( 'Process "'.$this->project->filtered['title'].'" ['.$this->project->filtered['id'].'] project start' );
		if ( empty( $this->project->filtered['flg_mode'] ) ) {
			$_bool=Project_Publisher_Arrange_Automatic::run( $this->logger, $this->project );
		} else {
			$_bool=Project_Publisher_Arrange_Manual::run( $this->logger, $this->project );
		}
		Project_Publisher::saveLog( $this->logger, $this->project->filtered['id'], 'Process "'.$this->project->filtered['title'].'" ['.$this->project->filtered['id'].'] project finish', true );
		return $_bool;
	}

	private function getProject( $_intId ) {
		if ( !Project_Publisher::getInstance()->onlyOne()->withIds( $_intId )->getList( $arrRes ) ) {
			return false;
		}
		Zend_Registry::get( 'objUser' )->setById( $arrRes['user_id'] );
		$this->project=new Core_Data( $arrRes );
		$this->project->setFilter();
		return true;
	}

	private function setLogger() {
		if ( php_sapi_name()=='cli' ) {
			$writer=new Zend_Log_Writer_Stream( 'php://output' );
			$writer->setFormatter( new Zend_Log_Formatter_Simple() );
		} else {
			$writer=new Core_Log_Writer_Array();
			$writer->setContainer( $this->log )->setFormatter( new Zend_Log_Formatter_Simple( "%priorityName%: %message%" ) );
		}
		$this->logger=new Zend_Log( $writer );
	}
}
?>