<?php
/**
 * WorkHorse Framework
 *
 * @category Project
 * @package Project_Publishing
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2005-2010, web2innovation
 * @author Rodion Konnov <kindzadza@mail.ru>
 * @date 02.02.2010
 * @version 0.1
 */


/**
 * Arrange content on project web sites
 *
 * @category Project
 * @package Project_Wpress
 * @copyright Copyright (c) 2005-2010, web2innovation
 * @license http://opensource.org/licenses/ MIT License
 */
class Project_Publishing_Arrange {

	public $logger;
	private $_time=0;

	public function __construct() {
		$this->_time=time();
		$this->setLogger();
	}

	public function run() {
		$this->logger->info( 'Start Project_Publishing_Arrange by crontab at '.date( 'r', $this->_time ) );
		$_intIds=Core_Sql::getField( 'SELECT id FROM pub_project WHERE start<='.$this->_time.' AND flg_status IN (0,1)' );
		if ( empty( $_intIds ) ) {
			$this->logger->info( 'Stop Project_Publishing_Arrange::run - no project exists' );
			return false;
		}
		Core_Sql::setExec( 'UPDATE pub_project SET flg_status=1 WHERE id IN('.Core_Sql::fixInjection( $_intIds ).')' );
		foreach( $_intIds as $v ) {
			$this->process( $v );
		}
		$this->logger->info( 'Finish Project_Publishing_Arrange by crontab at '.date( 'r' ) );
		return true;
	}

	private function process( $_intId ) {
//		$_objTmp=Core_Sql::getInstance( true );
		Zend_Registry::set('objUser', new Project_Users());
		$data=new Core_Data( Core_Sql::getRecord( 'SELECT * FROM pub_project WHERE id='.$_intId ) );
		$data->setFilter();
		$this->logger->info( 'Process "'.$data->filtered['title'].'" ['.$data->filtered['id'].'] project start' );
		switch( $data->filtered['flg_type'] ) {
			case Project_Sites::BF:
				if ( $data->filtered['flg_source']==3 ) {
					Project_Publishing_Blogfusion_Arrange_Rss::run( $this->logger, $data );
				} else {
					Project_Publishing_Blogfusion_Arrange_Content::run( $this->logger, $data );
				}
			break;
			case Project_Sites::CNB : 
				if ( $data->filtered['flg_source']==1 ) {
					Project_Publishing_Cnb_Arrange_Content::run( $this->logger, $data );
				} else {
					Project_Publishing_Cnb_Arrange_Keywords::run( $this->logger, $data );
				}			
			break; // TODO!!!
		}
		$this->logger->info( 'Process "'.$data->filtered['title'].'" ['.$data->filtered['id'].'] project finish' );
	}

	private function setLogger() {
		$formatter = new Zend_Log_Formatter_Simple( Zend_Log_Formatter_Simple::DEFAULT_FORMAT.(php_sapi_name()=='cli'?PHP_EOL:'<br />'));
		$writer=new Zend_Log_Writer_Stream( 'php://output' );
		$writer->setFormatter( $formatter );
		$this->logger = new Zend_Log( $writer );
	}
}
?>