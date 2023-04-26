<?php

class Project_Parking_Generate {

	public $logger;
	public $log=array();

	public static function run(){
		$_obj=new self();
		$_obj->generate();
	}

	/**
	 * Run user projects.
	 * @return bool
	 */
	public function generate(){
		$this->setLogger();
		$this->logger->info( 'Start Project_Publisher_Arrange by crontab at '.date( 'r' ) );
		$_model=new Project_Parking();
		if( !$_model->withStatus( Project_Parking::$status['notStarted'] )->getList( $_arrTasks )->checkEmpty() ){
			return false;
		}
		foreach( $_arrTasks as $_item ){
			$this->logger->info( 'Start item '.$_item['id'] );
			Core_Users::getInstance()->setById( $_item['user_id'] );
			// Set status inProgress
			$this->logger->info( 'Set status inProgress' );
			$_model->setStatus( Project_Parking::$status['inProgress'], $_item['id'] );
			// message to user about start project
			Project_Parking_Notification::startProject( $_item );
			// Create Hosting
			$this->logger->info( 'Domains: '.$_item['domains'] );
			$_item['domains']=unserialize($_item['domains']);
			$_item['keywords']=unserialize($_item['keywords']);
			$_arrRes=array();
			$this->logger->info( 'Create Hosting start' );
			foreach( $_item['domains'] as $_key=>$_domain ){
				$this->logger->info( 'Create domain "'.$_domain.'" start' );
				if( !$this->createNvsbSite( $this->createHosting( $_domain ) , $_item['keywords'][$_key] ) ){
					$_arrRes[$_domain]=Core_Data_Errors::getInstance()->getErrorsFlow();
					$this->logger->info( 'Create Hosting failed. Error: '.implode(' ',$_arrRes[$_domain]) );
					continue;
				}
				$this->logger->info( 'Create Hosting successful' );
				$_arrRes[$_domain]=true;
			}
			$_item['domains']=$_arrRes;
			Project_Placement_Notification::instructionDNSmass( $_item );
			// Update project
			$_model->setEntered($_item)->set();
			// Set status to project
			$_model->setStatus( Project_Parking::$status['completed'], $_item['id'] );
			// message to user about end project
			Project_Parking_Notification::endProject( $_item );
			Core_Users::getInstance()->retrieveFromCashe();
		}
		$this->logger->info( 'Finish Project_Publisher_Arrange by crontab at '.date( 'r' ) );
	}

	/**
	 * Create hosting and reg. domain
	 * @param $_domain
	 * @return bool
	 */
	private function createHosting( $_domain ){
		$_placement=new Project_Placement();
		if( !$_placement->setEntered(array(
			'domain_http'=>$_domain,
			'flg_type'=>Project_Placement::LOCAL_HOSTING,
		))->notSendMessageToUser()->set() ){
			return false;
		}
		$_placement->getEntered( $_place );
		return $_place;
	}

	/**
	 * Create NVSB site for domain
	 * @param $_place
	 * @param $_keyword
	 * @return bool
	 */
	private function createNvsbSite( $_place, $_keyword ){
		if(!$_place){
			return false;
		}
		$_nvsb=new Project_Sites( Project_Sites::NVSB );
		if( !$_nvsb->setEntered(array(
			'placement_id'=>$_place['id'],
			'ftp_directory'=>'/',
			'ftp_root'=>1,
			'template_id'=>$this->getTemplateId(),
			'category_id'=>$this->getCategoryId(),
			'main_keyword'=>$this->getKeyword($_keyword),
			'google_analytics'=>Core_Users::$info['adsenseid'],
			'flg_usage'=>2,
			'flg_related_keywords'=>0,
			'flg_comments'=>0
		))->set() ){
			$_nvsb->getErrors( $_err );
			$this->logger->info( 'Create Hosting failed. Error: '.join('; ',$_err) );
			return false;
		}
		return true;
	}

	/**
	 * Get keyword for site
	 * @param $_keyword
	 * @return mixed
	 */
	private function getKeyword( $_keyword ){
		if(!empty($_keyword)){
			return ucwords(strtolower(trim($_keyword)));
		}
		$_nresearch=new Project_Nicheresearch();
		$_nresearch->onlyOne()->withRandom()->getList($arrRes);
		return ucwords(strtolower(trim($arrRes['word'])));
	}

	/**
	 * Get templte for site
	 * @return mixed
	 */
	private function getTemplateId(){
		return Core_Sql::getCell('SELECT id FROM es_templates t LEFT JOIN es_template2user l ON l.template_id=t.id  WHERE l.user_id='.Core_Users::$info['id'].' AND t.flg_type='.Project_Sites::NVSB.'  ORDER BY RAND()' );
	}

	/**
	 * Get random category for site
	 * @return mixed
	 */
	private function getCategoryId(){
		$_category = new Core_Category( 'Blog Fusion' );
		$_category->getTree( $arrTree );
		$_pid=array_rand($arrTree);
		$_cid=array_rand($arrTree[$_pid]['node']);
		return $arrTree[$_pid]['node'][$_cid]['id'];
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