<?php
/**
 * Update psb sites
 *
 */
class Project_Updater_Psb extends Core_Updater_Abstract {

	private $_table = 'es_psb';
	private static $_status=array('notupdate'=>0,'success'=>1,'error'=>2);
	private $_logger = false;
	
	public function update( Core_Updater $obj ) {
		$fields = Core_Sql::getField( 'DESCRIBE '.$this->_table );
		if ( !in_array( 'flg_updater', $fields ) ){
			Core_Sql::setExec( 'ALTER TABLE '.$this->_table.' ADD flg_updater tinyint(1) unsigned NOT NULL DEFAULT 0' );
		}
//		Core_Sql::setExec('UPDATE '.$this->_table.' SET flg_updater='.self::$_status['notupdate']);
		$this->_logger = $obj->logger;
		$obj->logger->info('START remote update SPB sites');
		$this->run();
		$obj->logger->info('END');
	}

	private function run(){
		$_arrList=Core_Sql::getKeyVal('SELECT id,user_id FROM '.$this->_table.' WHERE flg_updater=0 AND user_id IN (SELECT parent_id FROM u_users) ORDER BY id');
		foreach( $_arrList as $_siteId=>$_userId ){
			Core_Sql::disconnect();
 	 	    Core_Sql::setConnectToServer( 'members.creativenichemanager.info' );
			Project_Users_Fake::byUserId($_userId);
			$_psb=new Project_Sites( Project_Sites::PSB );
			$_psb->getSite($_site,$_siteId);
			$_site['multibox_ids_content_wizard']=json_decode($_site['strJson'],true);
			if( $_site['multibox_ids_content_wizard']==false ){
				$_site['arrPsb']['flg_articles']=0;
			}
			$_opt=new Project_Options(  Project_Sites::PSB , $_site['arrPsb']['id'] );
			$_opt->get($_site['arrOpt']);
			if( !$_psb->setData( $_site )->set() ){
				Core_Sql::disconnect();
  				Core_Sql::setConnectToServer( 'members.creativenichemanager.info' );
				$this->_logger->err('Site: [id: '.$_site['arrPsb']['id'].'] [user: '.$_site['arrPsb']['user_id'].'] [url: <a href="'.$_site['arrPsb']['url'].'" target="_blank">'.$_site['arrPsb']['url'].'</a>]');
				$_psb->getErrors($err);
				var_dump($err);
				Core_Sql::setExec('UPDATE '.$this->_table.' SET flg_updater='.self::$_status['error'] .' WHERE id='.$_site['arrPsb']['id']);
				continue;
			}
			Core_Sql::disconnect();
  			Core_Sql::setConnectToServer( 'members.creativenichemanager.info' );
			$this->_logger->info('Site: [id: '.$_site['arrPsb']['id'].'] [user: '.$_site['arrPsb']['user_id'].'] [url: <a href="'.$_site['arrPsb']['url'].'" target="_blank">'.$_site['arrPsb']['url'].'</a>]');
			Core_Sql::setExec('UPDATE '.$this->_table.' SET flg_updater='.self::$_status['success'] .' WHERE id='.$_site['arrPsb']['id']);
		}
	}

}
?>