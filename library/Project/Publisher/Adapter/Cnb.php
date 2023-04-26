<?php
/**
 * WorkHorse Framework
 *
 * @category Project
 * @package Project_Publisher_Adapter
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2005-2010, web2innovation
 * @author Rodion Konnov <kindzadza@mail.ru>
 * @date 23.03.2011
 * @version 2.0
 */


/**
 * Posting data on CNB sites
 *
 * @category Project
 * @package Project_Publisher_Adapter
 * @copyright Copyright (c) 2005-2010, web2innovation
 * @license http://opensource.org/licenses/ MIT License
 */
class Project_Publisher_Adapter_Cnb extends Project_Sites_Type_Cnb implements Project_Publisher_Adapter_Interface {

	private static $_instance=NULL;
	private $_content = false;
	private $_siteId=false;

	public static function getInstance(){
		if( self::$_instance == NULL ){
			self::$_instance= new Project_Publisher_Adapter_Cnb();
		}
		return self::$_instance;
	}

	public function setContent( &$data ){
		$this->_content=&$data;
		return $this;
	}

	public function setSite( $intId ){
		$this->_siteId=$intId;
		return $this;
	}

	public function post(){
		$_cnb = new Project_Sites( Project_Sites::CNB );
		if ( !$_cnb->getSite( $_arrSite, $this->_siteId ) ) {
			return false;
		}
		foreach ( $this->_content as &$_item ){
			$_arrSite['arrCnb']['arrContent'][]=$_item;
		}
		p('stop');
		$_cnb->setData( $_arrSite );
		return $this->set( $_cnb );
	}

	public function getPublicateResult(){
		return $this->_content;
	}
	
	public function set( Project_Sites  $object ) {
		$this->data=new Core_Data( $object->data->setFilter( array( 'trim', 'clear' ) )->getRaw( 'arrCnb' ) );
		$this->data->setFilter();
		if ( !$this->upload() ) {
			return false;
		}
		return true;
	}

	public function prepareSource(){
		$this->_dir='Project_Publisher_Adapter_Cnb@prepareSource';
		if ( !Zend_Registry::get( 'objUser' )->prepareTmpDir( $this->_dir ) ) {
			$this->_errors[] = 'Process Aborted. Can\'t create dir Project_Publisher_Adapter_Cnb@prepareSource';
			return false;
		}
		mkdir($this->_dir . 'datas'. DIRECTORY_SEPARATOR .'articles'.DIRECTORY_SEPARATOR, 0777, true );
		if ( !$this->generateArticles() ) {
			$this->_errors[] = 'Process Aborted. Can\'t generate articles';
			return false;
		}
		return true;
	}

	protected function generateArticles() {
		$_strDir=$this->_dir.'datas'.DIRECTORY_SEPARATOR.'articles'.DIRECTORY_SEPARATOR;
		foreach( $this->data->filtered['arrContent'] as $v ) {
			$_strContent=$v['title']."\n".$v['body'];
			$_strFileName=Core_String::getInstance( strtolower( strip_tags( $v['title'] ) ) )->toSystem( '-' ).'.txt';
			if ( !empty($v['del']) ){
				continue;
			}
			if ( !Core_Files::setContent( $_strContent, $_strDir.$_strFileName ) ) {
				return false;
			}
		}
		return true;
	}
	
	public static function getFilename( $_strTitle ){
		if( empty($_strTitle) ){
			return false;
		}
		return Core_String::getInstance( strtolower( strip_tags( $_strTitle ) ) )->toSystem( '-' ).'.txt';
	}
}
?>