<?php
/**
 * Move old templates to new table for NCSB and NVSB
 *
 */
class Project_Updater_Templates extends Core_Updater_Abstract {

	private $_siteType=NULL;
	private $_strDir='';
	private $_strFromDir='';
	private $_logger=false;

	public function update( Core_Updater $obj ) {
		$this->_logger = $obj->logger;
		$this->_logger->info('START update templates');
		$this->_siteType = $obj->settings['type'];
		$this->_strFromDir= Zend_Registry::get( 'config' )->path->absolute->user_files . 'temp' . DIRECTORY_SEPARATOR . 'updater'. DIRECTORY_SEPARATOR;
		if ( !is_dir( $this->_strFromDir ) ){
			$this->_logger->err('This is not dir '.$this->_strFromDir);
			return false;
		}
		if ( !$this->init() ){
			$this->_logger->err('Can\'t find site type. Type must be one from [ psb, cnb, nvsb, ncsb ]');
			return false;
		}
		if( !$this->run() ){
			return false;
		}
		$this->_logger->info('END');
	}

	private function init(){
		switch( $this->_siteType ){
			case Project_Sites::$code[ Project_sites::PSB ] :
				$this->_strDir='sites'.DIRECTORY_SEPARATOR.$this->_siteType.DIRECTORY_SEPARATOR;
				$this->_siteType=Project_Sites::PSB;
				break;
			case Project_Sites::$code[ Project_sites::CNB ] :
				$this->_strDir='sites'.DIRECTORY_SEPARATOR.$this->_siteType.DIRECTORY_SEPARATOR;
				$this->_siteType=Project_Sites::CNB;
				break;
			case Project_Sites::$code[ Project_sites::NVSB ] :
				$this->_strDir='sites'.DIRECTORY_SEPARATOR.$this->_siteType.DIRECTORY_SEPARATOR;
				$this->_siteType=Project_Sites::NVSB;
				break;
			case Project_Sites::$code[ Project_sites::NCSB ] :
				$this->_strDir='sites'.DIRECTORY_SEPARATOR.$this->_siteType.DIRECTORY_SEPARATOR;
				$this->_siteType=Project_Sites::NCSB;
				break;
			default:
				return false;
				break;
		}
		return true;
	}


	private function run(){
		$arrList=Core_Sql::getAssoc('SELECT * FROM es_templates as t WHERE t.flg_type='.$this->_siteType .' ORDER BY flg_belong,id');
		if( !empty($arrList) )
		foreach( $arrList as $template ){
			if( $template['flg_belong'] == 0 ){
				if( !$this->commonTemplates($template) ){
					return false;
				}
			} else {
				$this->userTemplates($template);
			}
		}
		return true;
	}

	private function commonTemplates( $template ){
		$_strDir=Zend_Registry::get( 'config' )->path->absolute->user_files.$this->_strDir;
		if(!is_file($_strDir.$template['filename'])){
			return true;
		}
		if( !$this->repack($_strDir.$template['filename']) ){
			$this->_logger->err('Can\'t repacking '.$template['filename']);
			return false;
		}
		$this->_logger->info( 'Repack common template: '.$template['filename']);
		return true;
	}

	private function userTemplates( $template ){
		$userId=Core_Sql::getCell('SELECT user_id FROM es_template2user WHERE template_id='.$template['id']);
		$_strDir=Zend_Registry::get( 'config' )->path->absolute->user_data.$userId.DIRECTORY_SEPARATOR.$this->_strDir;
		if( !$this->repack($_strDir.$template['filename']) ){
			$this->_logger->err('Can\'t repacking '.$template['filename']);
			return false;
		}
		$this->_logger->info( 'Repack user template: '.$template['filename'] .' User ID: '.$userId);
		return true;
	}

	private function repack( $zipFile ){
		$_extractDir='Project_Updater_Templates@repack';
		Project_Users_Fake::zero();
		if ( !Zend_Registry::get( 'objUser' )->prepareTmpDir( $_extractDir ) ) {
			$this->_logger->err('Can\'t create dir '.$_extractDir);
			return false;
		}
		$zip=new Core_Zip();
		if ( !$zip->setDir( $_extractDir )->extractZip( $zipFile ) ) {
			$this->_logger->err('Can\'t unpack '. $zipFile );
			return false;
		}
		if ( true!==$zip->open( $zipFile, ZipArchive::CREATE ) ) {
			$this->_logger->err('Can\'t create zip '. $zipFile );
			return false;
		}
		if( !$zip->addDirectory($_extractDir) ){
			$this->_logger->err('Can\'t add dir '.$_extractDir.' to zip '. $zipFile );
			return false;
		}
		if ( !$zip->addDirAndClose( $this->_strFromDir ) ) {
			$this->_logger->err('Can\'t add dir '.$this->_strFromDir.' to zip '. $zipFile );
			return false;
		}
		return true;
	}
}
?>