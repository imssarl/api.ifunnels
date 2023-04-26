<?php

 /**
 * сохранение ссылок (prod->tracker) при публикации, проверка в последующем (tracker) и удаление из системы(tracker->prod) недоступного КТ
 */
class Project_Syndication_Checker {

	/**
	* список полей таблицы
	* @var array
	*/
	private $_fields=array( 'shedule_id', 'flg_status', 'status_code', 'url', 'checked' );

	public $logger;

	/**
	* лимит на количество ссылок проверяемых за один заход
	* @var integer
	*/
	private $_urlLimit=100;

	/**
	* конструктор
	* @return void
	*/
	public function __construct() {
		$this->setLogger();
	}

	private function setLogger() {
		$formatter = new Zend_Log_Formatter_Simple( Zend_Log_Formatter_Simple::DEFAULT_FORMAT.(php_sapi_name()=='cli'?PHP_EOL:'<br />'));
		$writer=new Zend_Log_Writer_Stream( 'php://output' );
		$writer->setFormatter( $formatter );
		$this->logger = new Zend_Log( $writer );
	}

	// данные заполняются с продакшн сервера
	public static function setUrls( $_arrUrls=array() ) {
		if ( empty( $_arrUrls ) ) {
			return false;
		}
		Core_Sql::setMassInsert( Project_Syndication::$tables['checker'], $_arrUrls );
		return true;
	}

	private function setConnectToMotherDb() {
		Core_Sql::getInstance()->disconnect();
		Core_Sql::setConnectToServer( 'members.creativenichemanager.info' );
	}

	public function run() {
		$this->logger->info( 'Start Project_Syndication_Checker by crontab at '.date( 'r', time() ) );
		$this->setConnectToMotherDb();
		// не проверенные и и те которые во время прошлого просмотра были активны
		$_arrUrls=Core_Sql::getAssoc( '
			SELECT c.*, (
				SELECT user_id 
				FROM '.Project_Syndication::$tables['sites'].' 
				WHERE  user_id IN ('.Core_Acs::haveRightAccess(array('services_@_syndication_post_content')).') AND id=(SELECT site_id FROM '.Project_Syndication::$tables['content2site'].' WHERE id=c.shedule_id)
			) site_owner
			FROM cs_checker c
			WHERE c.flg_status IN(0,1) 
			ORDER BY c.checked 
			LIMIT '.$this->_urlLimit 
		);
		if ( empty( $_arrUrls ) ) {
			$this->logger->info( 'Stop Project_Syndication_Checker::run - no urls to check' );
			return false;
		}
		foreach( $_arrUrls as $v ) {
			$this->checkUrl( $v );
		}
		$this->logger->info( 'Finish Project_Syndication_Checker by crontab at '.date( 'r' ) );
		return true;
	}

	private function checkUrl( $_arrUrl=array() ) {
		if ( empty( $_arrUrl['url'] ) ) {
			return false;
		}
		$client=new Zend_Http_Client( $_arrUrl['url'], array( 'timeout'=>30 ) );
		$response=$client->request( Zend_Http_Client::HEAD );
		$_arrUrl['checked']=time();
		$_arrUrl['status_code']=$response->getStatus();
		$_arrUrl['flg_status']=$_arrUrl['status_code']==200? 1:2;
		Core_Sql::setUpdate( Project_Syndication::$tables['checker'], $_arrUrl, 'shedule_id' );
		$this->logger->info( 'Responce status code is '.$_arrUrl['status_code'].' for '.$_arrUrl['url'].' url' );
		if ( $_arrUrl['flg_status']==2 ) { // если КТ отсутствует уменьшеае счётчик владельцу сайта на 1
			Project_Syndication_Counters::decrease( 1, $_arrUrl['site_owner'] );
		}
		return true;
	}
}
?>