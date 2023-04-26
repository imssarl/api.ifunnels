<?php 

class Project_Updater_Ftp extends Core_Updater_Abstract {
	
	
	/**
	 * Статусы: 
	 * 0 - не проверен
	 * 1 - не коннектится
	 * 2 - не логинится
	 * 3 - не заливается файл
	 * 4 - не удаляется файл
	 * 5 - успешно проверен
	 *
	 * @param Core_Updater $obj
	 * @return unknown
	 */
	public function update( Core_Updater $obj ) {
		$fields = Core_Sql::getField( 'DESCRIBE hct_ftp_details_tb' );
		if ( !in_array( 'flg_status', $fields ) ){
			Core_Sql::setExec('ALTER TABLE hct_ftp_details_tb ADD flg_status tinyint(1) unsigned NOT NULL DEFAULT 0 AFTER id');
		}		
		Project_Users_Fake::zero();
		$_strDir='Project_Updater_Ftp@update';
		if ( !Zend_Registry::get( 'objUser' )->prepareTmpDir( $_strDir ) ) {
			return false;
		}
		$_strContent='test';
		Core_Files::setContent($_strContent,$_strDir.'test');
		$_arrList = Core_Sql::getAssoc('SELECT * FROM hct_ftp_details_tb WHERE user_id>0 AND flg_status=0 ORDER BY ftp_address DESC');
		foreach ( $_arrList as $_ftp ){
			$_objFtp=new Core_Media_Ftp();
			if ( !$_objFtp
				->setHost( $_ftp['ftp_address'] )
				->setUser( $_ftp['ftp_username'] )
				->setPassw( $_ftp['ftp_password'] )
				->makeConnect() ) {
				$_objFtp->getErrors( $_err );
				if ( stripos($_err[0],'ftp_connect' ) ) {
					$obj->logger->err('Can`t connect to ftp [ id:'.$_ftp['id'].' | user_id: '.$_ftp['user_id'].' | ftp_address: '. $_ftp['ftp_address'] .' | ftp_username: '.$_ftp['ftp_username'].' | ftp_password: '.$_ftp['ftp_password'].' ] ');
					Core_Sql::setExec('UPDATE hct_ftp_details_tb SET flg_status=1 WHERE id='.$_ftp['id']);	
				} else {
					$obj->logger->err('Can`t login to ftp [ id:'.$_ftp['id'].' | user_id: '.$_ftp['user_id'].' | ftp_address: '. $_ftp['ftp_address'] .' | ftp_username: '.$_ftp['ftp_username'].' | ftp_password: '.$_ftp['ftp_password'].' ] ');
					Core_Sql::setExec('UPDATE hct_ftp_details_tb SET flg_status=2 WHERE id='.$_ftp['id']);	
				}
				continue;
			}
			if ( !$_objFtp->fileUpload('/test',$_strDir.'test') ) {
				$obj->logger->err('Can`t upload file in ftp [ id:'.$_ftp['id'].' | user_id: '.$_ftp['user_id'].' | ftp_address: '. $_ftp['ftp_address'] .' | ftp_username: '.$_ftp['ftp_username'].' | ftp_password: '.$_ftp['ftp_password'].' ]');
				Core_Sql::setExec('UPDATE hct_ftp_details_tb SET flg_status=3 WHERE id='.$_ftp['id']);
				continue;
			}
			if ( !ftp_delete($_objFtp->ftp, '/test') ){
				$obj->logger->err('Can`t delete file from ftp [ id:'.$_ftp['id'].' | user_id: '.$_ftp['user_id'].' | ftp_address: '. $_ftp['ftp_address'] .' | ftp_username: '.$_ftp['ftp_username'].' | ftp_password: '.$_ftp['ftp_password'].' ]');
				Core_Sql::setExec('UPDATE hct_ftp_details_tb SET flg_status=4 WHERE id='.$_ftp['id']);
				continue;
			}
			Core_Sql::setExec('UPDATE hct_ftp_details_tb SET flg_status=5 WHERE id='.$_ftp['id']);
		}
	}
	
	
}
?>