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
 * Arrange local contents (Keywords)
 *
 * @category Project
 * @package Project_Wpress
 * @copyright Copyright (c) 2005-2010, web2innovation
 * @license http://opensource.org/licenses/ MIT License
 */
class Project_Publishing_Cnb_Arrange_Keywords {

	public static function run( Zend_Log $logger, Core_Data $data ) {
		$_obj=new Project_Publishing_Cnb_Arrange_Keywords( $logger, $data ); // опить же экономим память
		$_obj->update();
	}

	private $_logger;
	private $_project;
	private $_object = false;
	private $_data=false;

	public function __construct( Zend_Log $logger, Core_Data $data ) {
		$this->_logger=$logger;
		$this->_project=$data;
	}
	
	/**
	 * Posting content 
	 *
	 * @return unknown
	 */
	public function update() {
		$_objSchedule = new Project_Publishing_Cnb_Content( $this->_project );
		if ( !$_objSchedule->onlyNonPosted()->withOrder('s.site_id--dn')->_withDate( time() )->getList( $_arrSchedule ) ) {
			return false;
		}
		// группировка контента по сайтам
		foreach ( $_arrSchedule as $v ){
			$_tmpArrSites[] = $v['site_id'];
		}
		$_tmpArrSites = array_unique( $_tmpArrSites );
		foreach ( $_arrSchedule as $k=>$v ){
			foreach ( $_tmpArrSites as $_site ){
				if ( $_site == $v['site_id'] ){
					$_arrPlan[$_site][$k]['keyword'] = $v['keyword'];
					$_arrPlan[$_site][$k]['schedule_id']=$v['id'];
					
				}
			}
		}
		// постинг контента
		$_objSites = new Project_Sites( Project_Sites::CNB );
		foreach ( $_arrPlan as $_intSiteId=>$arrContent ){
			if ( !$_objSites->getSite( $arrSite, $_intSiteId ) ) {
				continue;
			}
			$arrSite['arrCnb']['arrContent']=$arrContent;
			if ( !$this->set( $arrSite ) ){
				// Ошибка при постинге
				foreach ( $arrContent as $v ){
					Core_Sql::setUpdate( 'pub_schedule', array( 'id'=> $v['schedule_id'], 'flg_status' => 2 ) );
					$this->_logger->err('public content ['.$v['schedule_id'].'] site id : ['.$arrSite['arrCnb']['id'].'] site url : ['.$arrSite['arrCnb']['url'].'] ');
				}
				continue;
			}
			// ставим статусы для запощеного КТ
			foreach ( $arrContent as $v ){
				Core_Sql::setUpdate( 'pub_schedule', array( 'id'=> $v['schedule_id'], 'flg_status' => 1 ) );
				$this->_logger->info( 'public content ['.$v['schedule_id'].'] site id : ['.$arrSite['arrCnb']['id'].'] site url : ['.$arrSite['arrCnb']['url'].'] ');
			}			
		}
		
		if ( !$_objSchedule->onlyNonPosted()->getList( $_arrSchedule ) ) {
			$this->_logger->info( 'Project was completed id : '. $this->_project->filtered['id'] .'. Set status "completed"' );
			Core_Sql::setUpdate('pub_project', array( 'id'=>$this->_project->filtered['id'], 'flg_status' => 2 ) );
		}
	}
	
	private function set( $arrData ) {
		$this->_data=new Core_Data( $arrData['arrCnb'] );
		$this->_data->setFilter();
		if ( !$this->upload() ) {
			return false;
		}
		return true;
	}	
	
	private function upload(){
		$this->_dir='Project_Publishing_Cnb_Arrange_Keywords@upload';
		if ( !Zend_Registry::get( 'objUser' )->prepareTmpDir( $this->_dir ) ) {
			$this->_errors[] = 'Process Aborted. Can\'t create dir Project_Publishing_Cnb_Arrange_Keywords@upload';
			return false;
		}
		$_connector=new Project_Sites_Connector();
		$_connector
			->setSourceDir( $this->_dir )
			->setHttpUrl( $this->_data->filtered['url'] )
			->setHost( $this->_data->filtered['ftp_host'] )
			->setUser( $this->_data->filtered['ftp_username'] )
			->setPassw( $this->_data->filtered['ftp_password'] )
			->setRoot( $this->_data->filtered['ftp_directory'] )
			->makeConnectToRootDir();
			
		$_connector->fileDownload('datas/keywords.txt', $this->_dir.'keywords.txt');
		Core_Files::getContent( $_strContent, $this->_dir.'keywords.txt' );
		foreach ( $this->_data->filtered['arrContent'] as $_value ){
			$_strContent .= $_value['keyword'] ."\n";
		}
		$_connector->setChmod('0777');
		Core_Files::setContent($_strContent, $this->_dir.'keywords.txt');
		if ( !$_connector->fileUpload('datas/keywords.txt', $this->_dir.'keywords.txt') ){
			return false;
		}
		return true;	
	}
	
}
?>