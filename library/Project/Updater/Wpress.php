<?php
class Project_Updater_Wpress extends Core_Updater_Abstract {

	private $_rootDir = '';
	private $_userDir = '';
	private $_oldCommonPlugins = '';
	private $_commonPlugins = '';
	private $_oldUsersPlugins = '';
	private $_usersTmpDir = '';
	private $_userPlugins = '';
	private $_obj = false;
	private $_oldCommonTheme = '';
	private $_commonTheme = '';
	private $_oldUsersThemes = '';
	
	private $_permalinkTypes=array(
		'',
		'/archives/%post_id%',
		'/%year%/%monthnum%/%day%/%postname%/',
		'/%year%/%monthnum%/%postname%/',
		'/%category%/%postname%/'
	);	
	
	// step = 1 - Обновление плагинов
	// step = 2 - Обновление тем
	// step = 3 - Обновление блогов
	// step = default - восстановление дэфолтных тем и плагинов
	public function update( Core_Updater $obj ) {
		$this->initPath();
		$this->_obj = $obj;
		
		if ( $obj->settings['updateNewUser'] == 1 ) {
			$obj->logger->info( 'start add link on theme' );
			$this->linkTheme4newUser();
			$obj->logger->info( 'end add link on theme' );
			$obj->logger->info( 'start add link on plugin' );
			$this->linkPlugin4newUser();
			$obj->logger->info( 'end  add link on plugin' );
			return true;
		}
		
		$obj->logger->info( 'start Project_Updater_Wpress' );
		if($obj->settings['step'] == 1) {
			$this->initPluginPaths();
			if (@$obj->settings['clear'] == 1) {
				$this->clearPlugin();
				$this->_obj->logger->info('Plugins was cleared');
				return true;
			}
			$this->updatePlugins();
		}
		
		if ($obj->settings['step'] == 2) {
			$this->initThemePath();
			if (@$obj->settings['clear'] == 1) {
				$this->clearTheme();
				$this->_obj->logger->info('Theme was cleared');
				return true;
			}			
			$this->updateTheme();
		}
		
		if ($obj->settings['step'] == 3) {
			if (@$obj->settings['clear'] == 1) {
				$this->clearBlog();
				$this->_obj->logger->info('Blogs was cleared');
				return true;
			}			
			$this->updateBlog();
		}
		if ($obj->settings['step'] == 4) {
			$this->transferContent();
		}

		return true;
				
	}
	
	private function clearPlugin(){
		$driver = new Core_Media_Driver();
		$plugins = $driver->dirScan($this->_commonPlugins);
		foreach ($plugins as $path=>$files){
			foreach ($files as $file) {
				$commonPlugins[] = $path.DIRECTORY_SEPARATOR.$file;
			}
		}
		$driver->d_rmfile($commonPlugins);
		Core_Sql::setExec("TRUNCATE TABLE bf_plugins");
		Core_Sql::setExec("TRUNCATE TABLE bf_plugin2user_link");
	}
	private function clearTheme(){
		Core_Sql::setExec("TRUNCATE TABLE bf_themes");
		Core_Sql::setExec("TRUNCATE TABLE bf_theme2user_link");
	}
	private function clearBlog(){
		Core_Sql::setExec("TRUNCATE TABLE bf_blogs");
		Core_Sql::setExec("TRUNCATE TABLE bf_plugin2blog_link");		
		Core_Sql::setExec("TRUNCATE TABLE bf_theme2blog_link");		
		Core_Sql::setExec("TRUNCATE TABLE bf_ext_category");		
		Core_Sql::setExec("TRUNCATE TABLE bf_ext_comments");		
		Core_Sql::setExec("TRUNCATE TABLE bf_ext_pages");		
		Core_Sql::setExec("TRUNCATE TABLE bf_ext_post2cat");		
		Core_Sql::setExec("TRUNCATE TABLE bf_ext_posts");		
	}
	
	private function clearDefault() {
		$commonPluginsIds = Core_Sql::getField('SELECT id FROM bf_plugins WHERE flg_type = 0');
		$commonThemesIds = Core_Sql::getField('SELECT id FROM bf_themes WHERE flg_type = 0');
		Core_Sql::setExec('DELETE FROM bf_themes WHERE flg_type = 0');
		Core_Sql::setExec('DELETE FROM bf_plugins WHERE flg_type = 0');
		Core_Sql::setExec('DELETE FROM bf_theme2user_link WHERE theme_id IN ('.join(',',$commonThemesIds).')');
		Core_Sql::setExec('DELETE FROM bf_plugin2user_link WHERE plugin_id IN ('.join(',',$commonPluginsIds).')');
	}
	
	private function initPath(){
		$this->_userDir=Zend_Registry::get( 'config' )->path->absolute->user_files;
		$this->_rootDir=Zend_Registry::get( 'config' )->path->absolute->root;		
		$this->_usersTmpDir = $this->_userDir.'users'. DIRECTORY_SEPARATOR;
	}
	
	private function initPluginPaths() {
		$this->_oldCommonPlugins = $this->_rootDir.'bmp'.DIRECTORY_SEPARATOR.'users'.DIRECTORY_SEPARATOR.'common'.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR;
		$this->_commonPlugins = $this->_userDir.'blogfusion'. DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR;
		$this->_oldUsersPlugins = $this->_rootDir.'bmp'. DIRECTORY_SEPARATOR .'users'. DIRECTORY_SEPARATOR;
	}
	
	private function initThemePath() {		
		$this->_oldCommonTheme = $this->_rootDir . 'bmp' . DIRECTORY_SEPARATOR . 'users' . DIRECTORY_SEPARATOR . 'common' . DIRECTORY_SEPARATOR . 'themes' .DIRECTORY_SEPARATOR;
		$this->_commonTheme = $this->_userDir . 'blogfusion' . DIRECTORY_SEPARATOR . 'themes' .DIRECTORY_SEPARATOR;
		$this->_oldUsersThemes = $this->_rootDir.'bmp'. DIRECTORY_SEPARATOR .'users'. DIRECTORY_SEPARATOR;
	}
	
	private function linkTheme4newUser(){
		$usersId = Core_Sql::getField('select id '
			.'from u_users u inner join u_link l2 on u.id=l2.user_id '
			.'where l2.group_id=5 and (select count(l.theme_id) from bf_themes t '
			.'inner join bf_theme2user_link l on l.theme_id=t.id '
			.'where l.user_id=u.parent_id and t.flg_type=0) = 0');
			foreach ( $usersId as $id ) {
				Zend_Registry::set( 'objUser', new Project_Users_Fake( $id ) );
				$theme = new Project_Wpress_Theme();
				if( $theme->addCommonThemesToNewUser() ){
					$this->_obj->logger->info('Add link on common theme to user: id ['.$id.']');
				} else {
					$this->_obj->logger->err('Cannot add link on common theme to user: id ['.$id.']');
				}
			}
	}
	
	private function linkPlugin4newUser(){
		$usersId = Core_Sql::getField('select id from u_users u inner join u_link l2 on u.id=l2.user_id '
			.'where l2.group_id=5 and  (select count(l.plugin_id) from bf_plugins t '
			.'inner join bf_plugin2user_link l on l.plugin_id=t.id '
			.'where l.user_id=u.parent_id and t.flg_type=0)  = 0');
			foreach ( $usersId as $id ) {
				Zend_Registry::set( 'objUser', new Project_Users_Fake( $id ) );
				$theme = new Project_Wpress_Plugins();
				if( $theme->addCommonPluginsToNewUser() ){
					$this->_obj->logger->info('Add link on common plugin to user: id ['.$id.']');
				} else {
					$this->_obj->logger->err('Cannot add link on common plugin to user: id ['.$id.']');
				}
			}
	}
		
	private function updatePlugins() {

		Zend_Registry::set('objUser', new Project_Users());
		$model = new Project_Wpress_Plugins();
		$this->_obj->logger->info('Start moved common plugins');
		$commonPlugins = Core_Sql::getAssoc('SELECT * FROM bmp_plugins WHERE status = 2 AND user_id != 0 GROUP BY plugin_path');
		$plugin2user = array();
		$commonPluginPath = array();
		foreach ($commonPlugins as $plugin) {
			if (file_exists($this->_oldCommonPlugins.$plugin['plugin_path'])) {
				$arrZip = array('name'=> $plugin['plugin_path'], 'tmp_name' => $this->_oldCommonPlugins.$plugin['plugin_path'], 'size' => 1);
				if (!@$model->checkFile($arrZip)) {
					$this->_obj->logger->err($plugin['plugin_path']);
					continue;
				} 
			
				$arrPlugin = array( 
					'url' 			=> $plugin['url'], 
					'wp_path' 		=> $arrZip['wp_path'], 
					'title' 		=> $plugin['title'], 
					'flg_type' 		=> 0, 
					'filename' 		=> $plugin['plugin_path'], 
					'added' 		=> strtotime($plugin['updatedate']),
					'url'			=> (!empty($arrZip['url']))? $arrZip['url'] : '',
					'version' 		=> (!empty($arrZip['version']))? $arrZip['version'] : '',
					'author' 		=> (!empty($arrZip['author']))? $arrZip['author'] : '',
					'author_url' 	=> (!empty($arrZip['author_url']))? $arrZip['author_url'] : '',
					'description' 	=> (!empty($arrZip['description']))? $arrZip['description'] : ''
				); 
				$id = Core_Sql::setInsert('bf_plugins', $arrPlugin);
				if ($id && copy($this->_oldCommonPlugins.$plugin['plugin_path'], $this->_commonPlugins.$plugin['plugin_path'])) {
					$this->_obj->logger->info("Plugin {$plugin['plugin_path']} is uploaded.");
					$commonPluginPath[] = $plugin['plugin_path'];
					$userIds = Core_Sql::getField("SELECT user_id from bmp_plugins WHERE user_id != 0 AND plugin_path = '{$plugin['plugin_path']}' GROUP BY user_id");
					foreach ($userIds as $user) {
						$plugin2user[] = array('user_id' => $user, 'plugin_id' => $id);
					}
				}
			}
		}
		
		if (!empty($plugin2user)) {
			Core_Sql::setMassInsert('bf_plugin2user_link', $plugin2user);
		}
		$this->_obj->logger->info('End moved common plugins');
	
		$this->_obj->logger->info('Start moved users plugins');
		$arrUsers = Core_Sql::getField('SELECT user_id FROM bmp_plugins WHERE status = 1');
		$arrUsers = array_unique($arrUsers);
		foreach ( $arrUsers as $userId ) {
			$userPlugins = Core_Sql::getAssoc("SELECT * FROM bmp_plugins WHERE status = 1 AND user_id = {$userId} GROUP BY plugin_path ");
			$plugin2user = array();
			if ( empty($userPlugins) ) {
				continue;
			}
			foreach ($userPlugins as $plugin) {
				if ( file_exists($this->_oldUsersPlugins.$plugin['user_id'].DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.$plugin['plugin_path'])) {
					if ( in_array($plugin['plugin_path'], array('all-in-one-seo-pack.zip') ) ) {
						continue;
					}
					if ( in_array( $plugin['plugin_path'], $commonPluginPath ) ) {
						continue;
					}

					$arrZip = array(
						'name'=> $plugin['plugin_path'], 
						'tmp_name' => $this->_oldUsersPlugins.$plugin['user_id'].DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.$plugin['plugin_path'], 
						'size' => 1
						);
					if (!$model->checkFile($arrZip)) {
						$this->_obj->logger->err($this->_oldUsersPlugins.$plugin['user_id'].DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.$plugin['plugin_path']);
						continue;
					}
					$tempPlugins[] = $plugin['plugin_path'];
					$arrPlugin = array(
						'url' 			=> $plugin['url'], 
						'wp_path' 		=> $arrZip['wp_path'], 
						'title' 		=> $plugin['title'], 
						'filename' 		=> $plugin['plugin_path'], 
						'added' 		=> strtotime($plugin['updatedate']),
						'url'			=> (!empty($arrZip['url']))? $arrZip['url'] : '',
						'version' 		=> (!empty($arrZip['version']))? $arrZip['version'] : '',
						'author' 		=> (!empty($arrZip['author']))? $arrZip['author'] : '',
						'author_url' 	=> (!empty($arrZip['author_url']))? $arrZip['author_url'] : '',
						'description' 	=> (!empty($arrZip['description']))? $arrZip['description'] : ''						
					);
					$id = Core_Sql::setInsert('bf_plugins', $arrPlugin);
					if (!is_dir($this->_usersTmpDir . $plugin['user_id'])) {
						mkdir($this->_usersTmpDir . $plugin['user_id']);
					}
					if (!is_dir($this->_usersTmpDir . $plugin['user_id'] . DIRECTORY_SEPARATOR . 'blogfusion' . DIRECTORY_SEPARATOR)) {
						mkdir($this->_usersTmpDir . $plugin['user_id'] . DIRECTORY_SEPARATOR . 'blogfusion' . DIRECTORY_SEPARATOR);
					}
					if (!is_dir($this->_usersTmpDir . $plugin['user_id'] . DIRECTORY_SEPARATOR . 'blogfusion' . DIRECTORY_SEPARATOR .'plugins' .DIRECTORY_SEPARATOR)) {
						mkdir($this->_usersTmpDir . $plugin['user_id'] . DIRECTORY_SEPARATOR . 'blogfusion' . DIRECTORY_SEPARATOR .'plugins' .DIRECTORY_SEPARATOR);
					}
					if ( $id && copy($this->_oldUsersPlugins.$plugin['user_id'].DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.$plugin['plugin_path'], $this->_usersTmpDir . $plugin['user_id'] . DIRECTORY_SEPARATOR . 'blogfusion' . DIRECTORY_SEPARATOR .'plugins' .DIRECTORY_SEPARATOR .$plugin['plugin_path']) ) {
						$i++;
						$this->_obj->logger->info($i.". Plugin {$plugin['plugin_path']} is uploaded.  ");
						$plugin2user[] = array('user_id' => $plugin['user_id'], 'plugin_id' => $id);
					}

				}
			}
			if(!empty($plugin2user)){
				Core_Sql::setMassInsert('bf_plugin2user_link', $plugin2user);
			}
		}
		
		$this->_obj->logger->info('End moved users plugins');
		return true;
	}

	/*
		всего 21427 - select count(*) from bmp_blogs
		с фтп хостами заканчивающимися на домен первого уровня 614 - select count(*) from bmp_blogs where ftp_host REGEXP '.*\.[a-z]{2,6}$'
		с нормальными хостами, заполненным user_id, и не в доменах cnmbeta.info, qjmp.com 388 - select * from bmp_blogs where ftp_host REGEXP '.*\.[a-z]{2,6}$' AND ftp_host NOT IN('cnmbeta.info','qjmp.com') AND user_id>0 ORDER BY ftp_host
	*/
	private function clearGarbageFromBmpDb() {
		//Core_Sql::setExec( 'DROP TABLE IF EXISTS bmp_existing_blogs, bmp_upgrade, bmp_settings' ); // абсолютно ненужные таблицы
	}
	
	private function updateBlog(){
//		$this->clearBlog();
		$sql = "SELECT * FROM bmp_blogs WHERE  id IN(select id from bmp_blogs where ftp_host REGEXP '.*\.[a-z]{2,6}$' AND ftp_host NOT IN('cnmbeta.info') AND user_id>0 ORDER BY ftp_host) ORDER BY id desc";
		$arrBlog = Core_Sql::getAssoc($sql);
		foreach ($arrBlog as $blog) {
			
			$arrTemp = array(
				'title' 					=> $blog['blog_name'],
				'url'						=> $blog['blog_url'],
				'ftp_directory'				=> $blog['directory'],
				'flg_blogroll_links'		=> ($blog['create_default_blogroll'] == 'Yes') ? 1 : 0,
				'flg_summary'				=> $blog['full_text_or_feed_summary'],
				'flg_comment_status'		=> ($blog['comment_status'] == 'open') ? 1 : 0,
				'flg_comment_moderated'		=> $blog['comment_moderated'],
				'flg_comment_notification'	=> $blog['email_notification'],
				'flg_ping_status'			=> ($blog['pingback_status'] == 'open') ? 1:0,
				'flg_ping_newpost'			=> $blog['pingsite_newpost'],
				'flg_permalink'				=> $blog['permalink_structure'],
				'edited'					=> strtotime($blog['modified']),
				'category_id'				=> 0,
				'flg_status'				=> 1,
				'added'						=> strtotime($blog['created'])
			);
			if ( substr( $arrTemp['url'], -1 )!='/' ) {
				$arrTemp['url']=$arrTemp['url'].'/';
			}
			$_arrFlipTypes=array_flip( Project_Wpress::permalinkTypes );
			$arrTemp['flg_permalink']=$_arrFlipTypes[$arrTemp['flg_permalink']];
			$data = new Core_Data($arrTemp+$blog);
			$transferBlog = $data->setMask( Project_Wpress::$fields )->getValid();
			unset($data);
			$transferBlog['id'] = Core_Sql::setInsert('bf_blogs', $transferBlog);
			
			if ( $transferBlog['id'] && $this->transferContent($errors, $transferBlog) ){
				$this->_obj->logger->info("Blog id: {$transferBlog['id']} - transfered.  URL: {$transferBlog['url']} | FTP_USER: {$transferBlog['ftp_username']} | FTP_PASS: {$transferBlog['ftp_password']}");
				Core_Sql::setInsertUpdate('bf_blogs',array('id'=>$transferBlog['id'],'flg_status'=>2));
			} else {
				foreach ($errors as $error){
					$this->_obj->logger->err('System: '.$error);
				}
				$this->_obj->logger->err("-Blog id: {$transferBlog['id']} - not transfer. URL: {$transferBlog['url']} | FTP_USER: {$transferBlog['ftp_username']} | FTP_PASS: {$transferBlog['ftp_password']}");
				Core_Sql::setInsertUpdate('bf_blogs',array('id'=>$transferBlog['id'],'flg_status'=>0));
			}
		}
		
		Core_Sql::setExec('DELETE FROM bf_blogs WHERE flg_status = 0');
		return true;
	}

	private function transferContent(&$_error, $_data){
		error_reporting(E_ALL ^ E_NOTICE);
		$_error=array();
		Zend_Registry::set('objUser', new Project_Users());
		$data = new Core_Data($_data);
		$data->setFilter();
		$_import=new Project_Wpress_Connector_Import( $data );
		if ( !$_import->setParts()->putImporter() ) {
			$_import->getErrors($_error);
			return false;
		}
		if ( !$_import->start() ) {
			$_import->getErrors($_error);
			return false;
		}
		return true;
	}
	
	private function updateTheme(){
		Zend_Registry::set('objUser', new Project_Users());
		@$model = new Project_Wpress_Theme();
		$this->_obj->logger->info('Start update Common Theme');
			$sql = "SELECT * FROM bmp_theme WHERE theme_type = 'C' GROUP BY theme_path";
			$arrThemes = Core_Sql::getAssoc($sql);
			$themes2users = array();
			foreach ($arrThemes as $theme) {
				if ( stripos($this->_oldCommonTheme . $theme['theme_path'], ' ')) {
					@rename( $this->_oldCommonTheme . $theme['theme_path'] , str_replace(' ', '_',$this->_oldCommonTheme . $theme['theme_path']) );
					$theme['theme_path'] = str_replace(' ', '_', $theme['theme_path']);					
				}

				if ( file_exists( $this->_oldCommonTheme . $theme['theme_path'] )) {

					$arrZip = array('name'=> $theme['theme_path'], 'tmp_name' => $this->_oldCommonTheme . $theme['theme_path'], 'size' => 1);
					if (!@$model->checkFile($arrZip)) {
						$errors = array();
						$model->getErrors( $errors );
						if ( !empty( $errors ) )
						foreach ( $errors as $error ){
							$this->_obj->logger->err('System: '. $error);
						}
						$this->_obj->logger->err('not correct format theme: ' . $theme['theme_path']);
						continue;
					} 
					$tmpTheme = array( 
						'flg_type' 		=> 0, 
						'priority' 		=> $theme['position'], 
						'title' 		=> $theme['title'], 
						'filename'		=> $theme['theme_path'], 
						'added' 		=> strtotime($theme['updatedate']),
						'url'			=> (!empty($arrZip['url']))? $arrZip['url'] : '',
						'version' 		=> (!empty($arrZip['version']))? $arrZip['version'] : '',
						'author' 		=> (!empty($arrZip['author']))? $arrZip['author'] : '',
						'author_url' 	=> (!empty($arrZip['author_url']))? $arrZip['author_url'] : '',
						'description' 	=> (!empty($arrZip['description']))? $arrZip['description'] : ''
					);		
					$tmpTheme['flg_prop'] = ($arrZip['name'] == 'altmed.zip')? 1:0;
					if ($arrZip['name'] == 'default.zip') { $tmpTheme['priority'] = 100;}
					if ($arrZip['name'] == 'classic.zip') { $tmpTheme['priority'] = 99;}
					$id = Core_Sql::setInsert('bf_themes', $tmpTheme);
					if ($id && 	copy( $model->_extractDir.$arrZip['name'], $this->_commonTheme . $arrZip['name'] )) {
						copy( $model->_extractDir.Core_Files::getFileName( $arrZip['name'] ).'.png', $this->_commonTheme.Core_Files::getFileName( $arrZip['name'] ).'.png' );
						$sql = "SELECT user_id FROM bmp_theme WHERE theme_path = '{$theme['theme_path']}' ";
						$usersIds = Core_Sql::getField($sql);
						$this->_obj->logger->info("Theme {$theme['theme_path']} was uploaded.");
						foreach ($usersIds as $userId) {
							$themes2users[] = array('user_id' => $userId, 'theme_id' => $id);
						}
					}
				}
			}
			if (!empty($themes2users)) {
				Core_Sql::setMassInsert('bf_theme2user_link',$themes2users);
			}
		$this->_obj->logger->info('End update Common Theme');
		
		$this->_obj->logger->info('Start update USER Theme');
			$sql = "SELECT * FROM bmp_theme WHERE theme_type ='I' ";
			$usersTheme = Core_Sql::getAssoc($sql);
			foreach ($usersTheme as $theme) {
				if (file_exists($this->_oldUsersThemes . $theme['user_id'] . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $theme['theme_path'])) {
					if ( in_array($theme['theme_path'], array('skin-care-theme.zip'))) {
						continue;
					}
					$arrZip = array('name'=> $theme['theme_path'], 'tmp_name' => $this->_oldUsersThemes . $theme['user_id'] . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $theme['theme_path'], 'size' => 1);					
					if (!@$model->checkFile($arrZip)) {
						$this->_obj->logger->err('not correct format theme: ' .$this->_oldUsersThemes . $theme['user_id'] . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $theme['theme_path']);
						continue;
					} 					
					$id = Core_Sql::setInsert('bf_themes', array(
						'flg_type' 		=> 1, 
						'priority' 		=> $theme['position'], 
						'title' 		=> $theme['title'], 
						'filename'		=> $theme['theme_path'], 
						'added' 		=> strtotime($theme['updatedate']),
						'url'			=> (!empty($arrZip['url']))? $arrZip['url'] : '',
						'version' 		=> (!empty($arrZip['version']))? $arrZip['version'] : '',
						'author' 		=> (!empty($arrZip['author']))? $arrZip['author'] : '',
						'author_url' 	=> (!empty($arrZip['author_url']))? $arrZip['author_url'] : '',
						'description' 	=> (!empty($arrZip['description']))? $arrZip['description'] : ''
						));
					if (!is_dir($this->_usersTmpDir . $theme['user_id'])) {
						mkdir($this->_usersTmpDir . $theme['user_id']);
					}
					if (!is_dir($this->_usersTmpDir . $theme['user_id'] . DIRECTORY_SEPARATOR . 'blogfusion' . DIRECTORY_SEPARATOR)) {
						mkdir($this->_usersTmpDir . $theme['user_id'] . DIRECTORY_SEPARATOR . 'blogfusion' . DIRECTORY_SEPARATOR);
					}
					if (!is_dir($this->_usersTmpDir . $theme['user_id'] . DIRECTORY_SEPARATOR . 'blogfusion' . DIRECTORY_SEPARATOR .'themes' .DIRECTORY_SEPARATOR)) {
						mkdir($this->_usersTmpDir . $theme['user_id'] . DIRECTORY_SEPARATOR . 'blogfusion' . DIRECTORY_SEPARATOR .'themes' .DIRECTORY_SEPARATOR);
					}
										
					if ( $id && copy( $model->_extractDir.$arrZip['name'], $this->_usersTmpDir . $theme['user_id'] . DIRECTORY_SEPARATOR . 'blogfusion' . DIRECTORY_SEPARATOR .'themes' .DIRECTORY_SEPARATOR .$arrZip['name'] )) {
						copy( $model->_extractDir.Core_Files::getFileName( $arrZip['name'] ).'.png', $this->_usersTmpDir . $theme['user_id'] . DIRECTORY_SEPARATOR . 'blogfusion' . DIRECTORY_SEPARATOR .'themes' .DIRECTORY_SEPARATOR.Core_Files::getFileName( $arrZip['name'] ).'.png' );
						$theme2user[] = array('user_id' => $theme['user_id'], 'theme_id' => $id);						
						$this->_obj->logger->info("Theme {$theme['theme_path']} was uploaded.");
					}
				}
			}
			if (!empty($theme2user)) {
				Core_Sql::setMassInsert('bf_theme2user_link', $theme2user);
			}
		$this->_obj->logger->info('End update USER Theme');
		return true;
	}
}
?>