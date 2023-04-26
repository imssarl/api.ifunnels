<?php
class Project_Updater_Cnb extends Core_Updater_Abstract{


	private $_table = 'es_cnb';
	private $_tableTemlate2site = 'es_template2site';
	private $_tableTemplate2user = 'es_template2user';
	private $_tableTemplates = 'es_templates';

	private $_oldTable = 'hct_portals_sites_tb'; // старые CNB сайты
	private $_oldTableTemplates = 'hct_templates';
	private $_fields=array( 'id', 'flg_type', 'flg_belong', 'flg_header', 'priority', 'filename', 'title', 'url', 'description', 'added' );
	private $_oldTableSpots = 'hct_spots';
	private $_oldTableSpotsLink = 'hct_spots_link';
	private $_logger = false;

	public function update( Core_Updater $obj ) {
		Project_Users_Fake::zero();
		$this->_logger = $obj->logger;
		$obj->logger->info('START MOVE SITE');
		if ( $obj->settings['type'] == 'template' ){
			$this->moveTemplates();
		} elseif ( $obj->settings['type'] == 'check' ) {
			$this->checkSites();
		} else {	
			$this->moveSite();
		}
		$obj->logger->info('END MOVED SITE');
	}
	
	private function initUserPaths( $_intId ) {
		Project_Users_Fake::byUserId($_intId);
		if ( !Zend_Registry::get( 'objUser' )->checkDtaDir( $_strDir ) ) {
			throw new Exception( Core_Errors::DEV.'|Zend_Registry::get( \'objUser\' )->checkDtaDir( $_strDir ) no dir set' );
			return;
		}
		$this->_commonDir='sites'.DIRECTORY_SEPARATOR.'cnb'.DIRECTORY_SEPARATOR;
		$this->_userDir=$_strDir.$this->_commonDir;
		if ( !is_dir( $this->_userDir ) ) {
			mkdir( $this->_userDir, 0777, true );
		}
		$this->_commonDir=Zend_Registry::get( 'config' )->path->absolute->user_files.$this->_commonDir;
		if ( !is_dir( $this->_commonDir ) ) {
			mkdir( $this->_commonDir, 0777, true );
		}
	}
	
	private function moveTemplates(){
		$this->_logger->info('START UPGRADE CNB TEMPLATES');
		$this->_path2templates = $this->_commonDir=Zend_Registry::get( 'config' )->path->absolute->root.'template'.DIRECTORY_SEPARATOR;
		$_arrTemplates = Core_Sql::getAssoc('SELECT * FROM '.$this->_oldTableTemplates );
		$_model = new Project_Sites_Templates( Project_Sites::CNB );
		$zip=new Core_Zip();
		$_data=new Core_Data();
		$_count=0;
		foreach ( $_arrTemplates as &$v ){
			$this->initUserPaths( (( $v['user_id'] < 1 )? 0 : $v['user_id']) );
			$this->_logger->info('------------------in progress template: name[ '.$v['temp_name'].' ]--------------------');
			if ( !is_dir( $this->_path2templates.$v['temp_name'].DIRECTORY_SEPARATOR ) ){
				$this->_logger->err('not found dir ' .$this->_path2templates.$v['temp_name'].DIRECTORY_SEPARATOR);
				continue;
			}
			$_arrData=array('name'=>$v['temp_name'].'.zip');
			if ( !is_file( $this->_path2templates.$v['temp_name'].DIRECTORY_SEPARATOR.'feed.xml' ) || 
				 !$_model->parseDesc( $_arrData, $this->_path2templates.$v['temp_name'].DIRECTORY_SEPARATOR.'datas'.DIRECTORY_SEPARATOR.'desc'.DIRECTORY_SEPARATOR.'description.txt') ){
				$this->_logger->err('not found file ' .$this->_path2templates.$v['temp_name'].DIRECTORY_SEPARATOR.'feed.xml');
				continue;
			}
			
			$_strDir=($v['user_id'] < 1) ? $this->_commonDir : $this->_userDir;
			// упаковываем тему (файлы сразу в корне шаблона)
			if ( true!==$zip->open( $_strDir.$_arrData['name'], ZipArchive::CREATE ) ) {
				$this->_logger->err('Can`t create archive '.$_strDir.$_arrData['name']);
				continue;
			}
			
			if ( !$zip->addDirAndClose( $this->_path2templates.$v['temp_name'].DIRECTORY_SEPARATOR ) ) {
				$this->_logger->err('Can`t add in archive this folder: '.$this->_path2templates.$v['temp_name'].DIRECTORY_SEPARATOR );
				continue;
			}
			$this->_logger->info('Created archive '. $_strDir.$_arrData['name']);
			// превьюха шаблона
			if ( is_file( $this->_path2templates.$v['temp_name'].DIRECTORY_SEPARATOR.$_arrData['screenshot'] ) ) {
				if ( !copy( $this->_path2templates.$v['temp_name'].DIRECTORY_SEPARATOR.$_arrData['screenshot'], $_strDir.Core_Files::getFileName($_arrData['name'] ) .'.jpg'  ) ) {
					$this->_logger->err(' Can`t copy screenshot from ' . $this->_path2templates.$v['temp_name'].DIRECTORY_SEPARATOR.$_arrData['screenshot'] . ' in ' .$_strDir.Core_Files::getFileName($_arrData['name']).'.jpg' );
					continue;
				}
			}
			$this->_logger->info('Copy screenshot '. $_strDir.Core_Files::getFileName($_arrData['name']).'.jpg' );
			$_intId=Core_Sql::setInsert( $this->_tableTemplates, $_data->setMask( $this->_fields )->getValidCurrent( $_arrData+array(
				'filename'=>$_arrData['name'],
				'added'=>time(),
				'flg_type'=>Project_Sites::CNB,
				'flg_belong' => (( $v['user_id'] < 1 ) ? 0 : 1)
			) ) );
			if ( !$_intId ){
				$this->_logger->err('Can`t add template in base ' );
				continue;
			}
			$this->_logger->info('Added template in base new ID: '.$_intId );
			if ( $v['user_id'] < 1 ){
				Zend_Registry::get( 'objUser' )->onlyParentIds()->withoutGroups( array( 'Super Admin', 'System Users', 'Content Admin', 'Visitor' ) )->getList( $_arrUsers );
			} else {
				$_arrUsers = array( $v['user_id'] );
			}
			if ( !$_model->linkToUser( $_arrUsers, $_intId ) ) {
				$this->_logger->err( 'Can`t linked template with users' );
			}
			$_count+=1;
			$this->_logger->info( '------------------Completed. Template new ID:'.$_intId.' old ID:'.$v['id'].'-----------------' );
		}
		$this->_logger->info( 'END. Count template: '. $_count);
	}
	
	private function setConnectToMotherDb() {
		Core_Sql::getInstance()->setDisconnect();
		Core_Sql::setConnectToServer( 'members.creativenichemanager.info' );
	}
		
	private function checkSites(){
//		$this->setConnectToMotherDb();
		$fields = Core_Sql::getField( 'DESCRIBE '.$this->_oldTable );
		if ( !in_array( 'flg_moved', $fields ) ){
			// флог статуса переноса сайта в новую таб.
			// 0 - не обработан;
			// 1 - рабочий;
			// 2 - ошибка, не активный FTP;	
			// 3 - ошибка, нет нужных файлов. сайт не рабочий;	
			Core_Sql::setExec("ALTER TABLE {$this->_oldTable} ADD flg_moved tinyint(1) unsigned NOT NULL DEFAULT 0");
		}
		// список старых сайтов, исключая тестовый сервер cnmbeta.info
		$_arrData = Core_Sql::getAssoc( "SELECT s.*,t.temp_name as template_name FROM {$this->_oldTable} as s LEFT JOIN {$this->_oldTableTemplates} as t ON s.template_id=t.id ".
		" WHERE s.flg_moved=0 AND s.id IN(select id from {$this->_oldTable} where  (ftp_address REGEXP '.*\.[a-z]{2,6}$' OR ftp_address REGEXP '[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}') AND ftp_address NOT IN('cnmbeta.info') AND user_id>0) ORDER BY s.id" );
		$i=0;
		foreach ( $_arrData as $_item ) {
		$i++;
		$_ftp = new Core_Media_Ftp();
			if ( $_ftp
			->setHost( urldecode( $_item['ftp_address'] ) )
			->setUser( urldecode( $_item['ftp_username'] ) )
			->setPassw( urldecode( $_item['ftp_password'] ) )
			->setRoot( urldecode( $_item['ftp_homepage'] ) )
			->makeConnectToRootDir() ) {
				$_fileList = ftp_nlist($_ftp->ftp,'.');
				if ( !in_array( 'feed.xml' , $_fileList ) && $_item['type'] == 'S' ){ // если это не портал то должен быть файл feed.xml
					$this->_logger->info('['. $i .'] not search file feed.xml id:'.$_item['id']);
					Core_Sql::setExec('UPDATE ' . $this->_oldTable .' SET flg_moved = 3 WHERE id = ' . $_item['id']);						
				}
				// ставим статус
				$this->_logger->err('['. $i .'] site true id:'.$_item['id']);
				Core_Sql::setExec('UPDATE ' . $this->_oldTable .' SET flg_moved = 1 WHERE id = ' . $_item['id']);				
			} else {
				// ставим статус
				$this->_logger->err('['. $i .'] not ftp connect id: '.$_item['id'].' ftp_adress:[ '. $_item['ftp_address'] .' ] ftp_user:[ '. $_item['ftp_username'] .' ] ftp_password: [ '. $_item['ftp_password'] .']');
				Core_Sql::setExec('UPDATE ' . $this->_oldTable .' SET flg_moved = 2  WHERE id = ' . $_item['id']);
			}

		}		
		
	}
	
	private function moveSite(){
		// список старых сайтов, исключая тестовый сервер cnmbeta.info
		$_arrData = Core_Sql::getAssoc( "SELECT s.*,t.temp_name as template_name FROM {$this->_oldTable} as s LEFT JOIN {$this->_oldTableTemplates} as t ON s.template_id=t.id ".
		" WHERE s.flg_moved=1 AND s.id IN(select id from {$this->_oldTable} where  (ftp_address REGEXP '.*\.[a-z]{2,6}$' OR ftp_address REGEXP '[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}') AND ftp_address NOT IN('cnmbeta.info') AND user_id>0) ORDER BY s.id" );
		$i=0;
		// начинаем копирование в новую таблицу.
		foreach ( $_arrData as $_item ) {
			$i++;
			$_data = array(
			'user_id'		=> $_item['user_id'],
			'profile_id'	=> $_item['profile_id'],
			'parent_id'		=> ( $_item['portal_id'] > 0 )?$_item['portal_id']:0,
			'flg_portal'	=> ( $_item['type'] == 'P' ) ? 1 : 0,
			'flg_damas'		=> ($_item['damas_type'] == 'single' ) ? 1:0,
			'damas_ids'		=> $_item['damas_ids'],
			'url'			=> $_item['url'],
			'sub_dir'		=> Core_Files::getBaseName($_item['ftp_homepage']),
			'title'			=> $_item['title'],
			'primary_keyword'=> (!$_item['prim_keyword'])?$_item['title']:$_item['prim_keyword'],
			'ftp_host'		=> $_item['ftp_address'],
			'ftp_username'	=> $_item['ftp_username'],
			'ftp_password'	=> $_item['ftp_password'],
			'ftp_directory'	=> $_item['ftp_homepage'],
			'added'			=> strtotime($_item['created_date']),
			'edited'		=> strtotime($_item['updatedate'])
			);
			Project_Users_Fake::zero();
			$_modelTemplate = new Project_Sites_Templates( Project_Sites::CNB );
			//добавление сайта в новую таб.
			$arrIds[ $_item['id'] ] = Core_Sql::setInsertUpdate( $this->_table , $_data );
			$this->_logger->info('['. $i .'] site moved in new table old id:'.$_item['id'] .' new id:'. $arrIds[ $_item['id'] ]);
			if ( $_modelTemplate->onlyOne()->withFilename( $_item['template_name'] .'.zip' )->getList( $_template ) ){
				// привязка к шаблону
				$arrTemplate2site = array('site_id' => $arrIds[ $_item['id'] ], 'template_id' => $_template['id'], 'flg_type' => Project_Sites::CNB );
				Core_Sql::setInsertUpdate( $this->_tableTemlate2site , $arrTemplate2site );
				$this->_logger->info('template set');
			} else {
				$this->_logger->err('not set template');
			}
		}

	}
}
?>