<?php

class Project_Widget_Adapter_Cnbgenerator {

	private $_key='SDFK-4TYI-567K-5566F'; // 4ea6d6c45e3cac07fd6f9c67b4e7aa5f054a4767
	private $_settings=array();
	private $_path='';

	public function __construct() {
		$this->_path=Zend_Registry::get( 'config' )->path->relative->source.'widget'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR;
	}

	public function setSettings( $_arrSettings ){
		$this->_settings=$_arrSettings;
		return $this;
	}

	public function get(){
		echo "var element=document.createElement('iframe');element.src='https://".Zend_Registry::get( 'config' )->engine->project_domain."/services/widgets.php?key=4ea6d6c45e3cac07fd6f9c67b4e7aa5f054a4767&name=Cnbgenerator&action=set';element.width='285'; element.style.border='none'; element.height='390';document.getElementById('cnm-widget').appendChild( element );";
		die();
	}

	public function checkKey( $_key ){
		if( $_key !== sha1($this->_key) ){
			die('document.getElementById(\'cnm-widget\').innerHTML=\'Process Aborted. Wrong key.\';');
		}
		return true;
	}

	public function set(){
		session_start();
		if( isset($_GET['check']) ){
			echo empty($_SESSION['__download__']);
			exit();
		}
		if( !empty($_GET['arr'])){
			$_SESSION['__download__']=$_GET['session'];
			$this->createArchive( $strZip, $_GET['arr'] );
			Core_Files::download( $strZip );
			unset($_SESSION['__download__']);
			exit();
		}
		//Project_Users_Fake::zero();
		$arrOut=array('project_domain'=>Zend_Registry::get( 'config' )->engine->project_domain);
		$contentTemplates=new Project_Widget_Adapter_Cnbgenerator_Content();
		$contentTemplates->toSelect()->getList($arrOut['arrContent']);
		$arrOut['maxSites']=count($arrOut['arrContent']);
		$_templates=new Project_Sites_Templates( Project_Sites::CNB );
		$_templates->toSelect()->onlyCommon()->withPreview()->getList( $arrOut['arrTemplates'] );
		Core_View::factory( Core_View::$type['one'] )
			->setTemplate( $this->_path.'widget_iframe.tpl' )
			->setHash( $arrOut )
			->parse()
			->show();
	}

	private function createArchive( &$strZip, $_arrData ){
		//Project_Users_Fake::zero();
		// Templates
		$_template=new Project_Sites_Templates( Project_Sites::CNB );
		if( $_arrData['template_type'] == 1){ // manual
			$_arrTemplates=array($_arrData['template']);
		} else {
			$_template->onlyIds()->onlyCommon()->getList($_arrTemplates);
		}
		// Cotent
		$_content=new Project_Widget_Adapter_Cnbgenerator_Content();
		if( $_arrData['content_template_type']==1 ){ // manual
			$_content->withIds( $_arrData['content_templates'] );
		}
		$_content->getList($_arrCotents);

		$_dir='Project_Widget_Cnbgenerator@createArchive';
		if ( !Zend_Registry::get( 'objUser' )->prepareTmpDir( $_dir ) ) {
			throw new Exception( Core_Errors::DEV. 'Process Aborted. Can\'t create dir Project_Widget_Cnbgenerator@createArchive');
			return false;
		}
		if( empty($_arrData['amazon_associates_id']) ){
			$_arrData['amazon_associates_id']='letvsc-20';
		}
		if($_arrData['count_sites']==1){
			$_arrCotents=array_intersect_key($_arrCotents,array_flip(array_rand($_arrCotents,$_arrData['count_sites_num'])));
		}
		$_cnb=new Project_Sites( Project_Sites::CNB );
		foreach( $_arrCotents as $v ){
			$_data=array(
				'template_id'=>$_arrTemplates[array_rand($_arrTemplates,1)],
				'title'=>$v['primary_keyword'],
				'primary_keyword'=>$v['primary_keyword'],
				'profile'=>$_arrData
			);
			$_cnb->setData( $_data );
			$_site=$_cnb->getArchive();
			$_subDir=$_dir.str_replace(' ','_',$_data['title']);
			mkdir($_subDir);
			Core_Files::dirCopy($_site,$_subDir.DIRECTORY_SEPARATOR );
			Core_Files::setContent($v['keywords_list'],$_subDir.DIRECTORY_SEPARATOR.'datas'.DIRECTORY_SEPARATOR.'keywords.txt');
		}
		if( $_arrData['type_install'] == 1 ){ // portal
			Core_Files::setContent( 
				Core_View::factory( Core_View::$type['one'] )
					->setTemplate( $this->_path.'widget_portal.tpl' )
					->setHash( array('arrContent'=>$_arrCotents,'data'=>$_arrData) )
					->parse()
					->getResult(), 
				$_dir.'index.html'
			);
		}
		$strZip=$_dir.'source.zip';
		$zip=new Core_Zip();
		$zip->open( $strZip, ZipArchive::CREATE );
		$zip->addDirAndClose($_dir);
	}
}
?>