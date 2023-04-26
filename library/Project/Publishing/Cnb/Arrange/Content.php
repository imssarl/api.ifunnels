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
 * Arrange local contents (Article)
 *
 * @category Project
 * @package Project_Wpress
 * @copyright Copyright (c) 2005-2010, web2innovation
 * @license http://opensource.org/licenses/ MIT License
 */
class Project_Publishing_Cnb_Arrange_Content extends Project_Sites_Type_Cnb {

	public static function run( Zend_Log $logger, Core_Data $data ) {
		$_obj=new Project_Publishing_Cnb_Arrange_Content( $logger, $data );
		$_obj->update();
	}

	private $_logger;
	private $_project;
	private $_object = false;

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
		$this->_object=Project_Articles::getInstance(); 
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
					$this->_object->withIds( $v['content_id'] )->onlyOne()->getContent( $_arrPlan[$_site][$k] );
					$_arrPlan[$_site][$k]['schedule_id']=$v['id'];
				}
			}
		}
		unset($_tmpArrSites);
		unset($_arrSchedule);
		// постинг контента
		$_objSites = new Project_Sites( Project_Sites::CNB );
		foreach ( $_arrPlan as $_intSiteId=>$arrContent ){
			if ( !$_objSites->getSite( $_arrSite, $_intSiteId ) ) {
				continue;
			}
			$_arrSite['arrCnb']['arrContent']=$arrContent;
			$_objSites->setData( $_arrSite );
			if ( !$this->set( $_objSites ) ){
				// Ошибка при постинге
				foreach ( $arrContent as $v ){
					Core_Sql::setUpdate( 'pub_schedule', array( 'id'=> $v['schedule_id'], 'flg_status' => 2 ) );
					$this->_logger->err('public content ['.$v['schedule_id'].'] site id : ['.$_arrSite['arrCnb']['id'].'] site url : ['.$_arrSite['arrCnb']['url'].'] ');
				}
				continue;
			}
			// ставим статусы для запощеного КТ
			foreach ( $arrContent as $v ){
				Core_Sql::setUpdate( 'pub_schedule', array( 'id'=> $v['schedule_id'], 'flg_status' => 1 ) );
				$this->_logger->info( 'public content ['.$v['schedule_id'].'] site id : ['.$_arrSite['arrCnb']['id'].'] site url : ['.$_arrSite['arrCnb']['url'].'] ');
			}			
		}
		// Если весь контент для сайта запощен, меняем статус проекта.
		if ( !$_objSchedule->onlyNonPosted()->getList( $_arrSchedule ) ) {
			$this->_logger->info( 'Project was completed id : '. $this->_project->filtered['id'] .'. Set status "completed"' );
			Core_Sql::setUpdate('pub_project', array( 'id'=>$this->_project->filtered['id'], 'flg_status' => 2 ) );
		}
	}
	
	// переопределение родительского метода
	public function set( Project_Sites  $object ) {
		$this->data=new Core_Data( $object->data->setFilter( array( 'trim', 'clear' ) )->getRaw( 'arrCnb' ) );
		$this->data->setFilter();
		$_oldArticle = json_decode($object->data->filtered['strJson'],true);
		foreach( $_oldArticle as $item ) {
			$_arrIds[]=$item['id'];
		}
		$this->data->setElement( 'arrArticleIds', $_arrIds);
		if ( !$this->upload() ) {
			return false;
		}
		foreach ( $this->data->filtered['arrContent'] as $v){
			$arrArtIds[]=$v['id'];
		}
		if ( !Project_Articles_Links::saveIds( $arrArtIds, $this->data->filtered['id'], Project_Sites::CNB  ) ){
			return false;
		}		
		return true;
	}	
	
	public function prepareSource(){
		$this->_dir='Project_Publishing_Cnb_Arrange_Content@prepareSource';
		if ( !Zend_Registry::get( 'objUser' )->prepareTmpDir( $this->_dir ) ) {
			$this->_errors[] = 'Process Aborted. Can\'t create dir Project_Publishing_Cnb_Arrange_Content@prepareSource';
			return false;
		}
		mkdir($this->_dir . 'datas'. DIRECTORY_SEPARATOR );
		mkdir($this->_dir . 'datas'. DIRECTORY_SEPARATOR .'articles'.DIRECTORY_SEPARATOR );
		if ( !$this->generateArticles() ) {
			$this->_errors[] = 'Process Aborted. Can\'t generate articles';
			return false;
		}
		return true;
	}
	
	// переопределение родительского метода
	protected function generateArticles() {
		$_arrContent=array();
		if (!empty( $this->data->filtered['arrArticleIds'] )){
			if ( !Project_Articles::getInstance()->withIds( $this->data->filtered['arrArticleIds'] )->getContent( $_arrContent ) ) {
				return false;
			}
		}
		$_arrContent=array_merge($_arrContent,$this->data->filtered['arrContent']);
		$_strDir=$this->_dir.'datas'.DIRECTORY_SEPARATOR.'articles'.DIRECTORY_SEPARATOR;
		foreach( $_arrContent as $v ) {
			$_strContent=$v['title']."\n".$v['body'];
			$_strFileName=Core_String::getInstance( strtolower( strip_tags( $v['title'] ) ) )->toSystem( '-' ).'.txt';
			if ( !empty($v['id'] ) ) {
				$_arrFiels[]=$_strFileName;
			}
			if ( !empty($v['del']) ){
				continue;
			}
			if ( !Core_Files::setContent( $_strContent, $_strDir.$_strFileName ) ) {
				return false;
			}
		}
		$_strFiles=serialize($_arrFiels);
		Core_Files::setContent( $_strFiles, $this->_dir.'articles-list.txt');
		return true;
	}		
}
?>