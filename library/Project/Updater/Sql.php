<?php
class Project_Updater_Sql {

	//20200326 /services/updater.php?method=20200326
	public static function update20200326(){
		Project_Deliver::install();
	}
	
	//20200123 /services/updater.php?method=20200123
	public static function update20200123(){
		Core_Sql::setExec("ALTER TABLE `u_users` ADD `contact_limit` INT(11) NULL DEFAULT 50000;");
	}
	
	//20191202 /services/updater.php?method=20190912
	public static function update20190912(){
		Core_Sql::setExec("INSERT INTO `pb_blocks` (`blocks_category`, `blocks_url`, `blocks_height`, `blocks_thumb`) VALUES
			(31, 'elements/Yummy/popup1.html', '567', 'images/uploads/popup1.png'),
			(31, 'elements/Yummy/popup2.html', '567', 'images/uploads/popup2.png'),
			(31, 'elements/Yummy/popup3.html', '567', 'images/uploads/popup3.png'),
			(31, 'elements/Yummy/popup4.html', '567', 'images/uploads/popup4.png'),
			(31, 'elements/Yummy/popup5.html', '567', 'images/uploads/popup5.png'),
			(31, 'elements/Yummy/popup6.html', '567', 'images/uploads/popup6.png'),
			(31, 'elements/Yummy/popup7.html', '567', 'images/uploads/popup7.png'),
			(31, 'elements/Yummy/popup8.html', '567', 'images/uploads/popup8.png'),
			(31, 'elements/Yummy/popup9.html', '567', 'images/uploads/popup9.png');");
	}

	//20191202 /services/updater.php?method=20190212
	public static function update20190212(){
		Core_Sql::setExec("ALTER TABLE `pb_frames` ADD `frames_popup` varchar(20) NULL DEFAULT NULL;");
		Core_Sql::setExec("ALTER TABLE `pb_frames` ADD `frames_embeds` longtext NULL DEFAULT NULL;");
		Core_Sql::setExec("ALTER TABLE `pb_frames` ADD `frames_settings` TEXT NULL DEFAULT NULL;");
	}

	//20190722 /services/updater.php?method=20190722
	public static function update20190722(){
		Core_Sql::setExec("ALTER TABLE `u_users` ADD `flg_allow_sub` INT(1) NULL DEFAULT NULL;");
	}
	
	//20190618 /services/updater.php?method=UpdateEFCampaigns
	public static function updateUpdateEFCampaigns(){
		
		set_time_limit(0);
		
		try {
			Core_Sql::setConnectToServer( 'lpb.tracker' );
			//========
			
			
			$_arrUpdates=Core_Sql::getAssoc("SELECT b.sub_id, a.value, COUNT(a.VALUE) AS coutlimit, MIN(b.id) as minimumid FROM s8rs_parameters_1 a JOIN s8rs_events_1 b ON a.event_id=b.id JOIN s8rs_1 c ON b.sub_id=c.id WHERE a.NAME='ef_id' GROUP BY a.value, b.sub_id");
			
			foreach( $_arrUpdates as $_data ){
				if( $_data['coutlimit'] > 1000 ){
					$_arrCheck=Core_Sql::getAssoc("SELECT a.*, COUNT(a.event_id) AS checkcount  FROM s8rs_parameters_1 a JOIN s8rs_events_1 b ON a.event_id=b.id WHERE b.sub_id='".$_data['sub_id']."' GROUP BY a.event_id");
					$_arrDestroy=array();
					foreach( $_arrCheck as $_check ){
						if( $_check['checkcount'] == 1 && $_check['id']!=$_data['minimumid'] ){
							$_arrDestroy[]=$_check['event_id'];
						}
					}
					if( count( $_arrDestroy ) > 0 ){
						Core_Sql::setExec( 'DELETE FROM s8rs_parameters_1 WHERE event_id IN ('.implode(',', $_arrDestroy).')' );
						Core_Sql::setExec( 'DELETE FROM s8rs_events_1 WHERE id IN ('.implode(',', $_arrDestroy).')' );
					}
					echo "Clean Email id ".$_data['sub_id']." with ".count( $_arrDestroy )." errors<br/>";
				}
			}
			
			//========
			Core_Sql::renewalConnectFromCashe();
		}catch(Exception $e) {
echo date(DATE_RFC822).': '.$e->getMessage()."\n";
			Core_Sql::renewalConnectFromCashe();
		}
	}
	
	//20190418 /services/updater.php?method=AddSubscribersEfLimit
	public static function updateAddSubscribersEfLimit(){
		Core_Sql::setExec("ALTER TABLE `u_users` ADD `subscribers_limit` INT(11) NULL DEFAULT NULL;");
		Core_Sql::setExec("ALTER TABLE `u_users` ADD `subscribers_limit_date` INT(11) NULL DEFAULT NULL;");
		Core_Sql::setExec("ALTER TABLE `u_users` ADD `subscribers_count` INT(11) NULL DEFAULT NULL;");
		Core_Sql::setExec("ALTER TABLE `u_users` ADD `inactive_date_start` INT(11) NULL DEFAULT NULL;");
		Core_Sql::setExec("ALTER TABLE `u_users` ADD `inactive_message_sended` INT(1) NULL DEFAULT NULL;");
	}

	// 2019.04.10 /services/updater.php?method=20190410
	public static function update20190410(){
		Project_Efunnel_Import::install();
	}
	
	// 2019.04.05 /services/updater.php?method=20190405
	public static function update20190405(){
		Project_Efunnel_Order::install();
		Project_Efunnel_Emails::install();
	}
	
	// 2019.04.01 /services/updater.php?method=20190401
	public static function update20190401(){
		Core_Sql::setExec("ALTER TABLE `pb_sites` ADD `header_script` TEXT NULL DEFAULT NULL;");
		Core_Sql::setExec("ALTER TABLE `pb_sites` ADD `footer_script` TEXT NULL DEFAULT NULL;");
		Core_Sql::setExec("ALTER TABLE `pb_pages` ADD `pages_header_script` TEXT NULL DEFAULT NULL;");
		Core_Sql::setExec("ALTER TABLE `pb_pages` ADD `pages_footer_script` TEXT NULL DEFAULT NULL;");
	}

	// 2019.02.08 /services/updater.php?method=20190208
	public static function update20190208(){
		Core_Sql::setExec("ALTER TABLE `u_users` ADD `lpb_full_views` INT(11) UNSIGNED NOT NULL DEFAULT '0'");
		Project_Users_Stat::updateLpbFull();
	}

	// 2019.01.08 /services/updater.php?method=20190108
	public static function update20190108(){
		$_arrNulls=Core_Sql::getAssoc("SELECT NULL
			FROM INFORMATION_SCHEMA.COLUMNS
			WHERE table_name = 'squeeze_campaigns'
			AND column_name = 'network';");
		if( count( $_arrNulls ) == 0 ){
			Core_Sql::setExec("ALTER TABLE `squeeze_campaigns` ADD `network` VARCHAR(50) NULL DEFAULT NULL;");
		}
		$_squeeze=new Project_Squeeze();
		$_squeeze->onlyTemplates()->getList( $arrTemplates );
		foreach( $arrTemplates as $_template ){
			Core_Sql::setExec( 'UPDATE squeeze_campaigns SET network="'.$_template['tpl_settings']['network'].'" WHERE id='.$_template['id'] );
		}
	}
	
	// 2018.11.13 /services/updater.php?method=20181213
	public static function update20181213(){
		Project_Filters::install();
	}
	
	// 2018.11.29 /services/updater.php?method=20181205
	public static function update20181205(){
		Core_Sql::setExec("ALTER TABLE  `lpb_efunnels` ADD  `log_text` TEXT NULL DEFAULT NULL;");
	}
	
	// 2018.10.09 /services/updater.php?method=23112018
	public static function update23112018(){
		$_obj=new Project_Validations_Realtime();
		$_obj->install();
		
		$data=Core_Sql::getAssoc( 'SELECT id FROM `u_users` WHERE validation_realtime=1' );
		$_userIds=array();
		foreach ($data as $key => $value){
			Project_Validations_Realtime::setValue( Project_Validations_Realtime::USER, $value['id'], 1 );
			$_userIds[]=$value['id'];
		}
		
		$data=Core_Sql::getAssoc( 'SELECT id, user_id, options FROM `lpb_efunnels` WHERE user_id IN ('.Core_Sql::fixInjection($_userIds).')' );
		foreach ($data as $key => $value){
			$_settings=unserialize( base64_decode( $value['options'] ) );
			if( isset( $_settings['validation_realtime'] ) ){
				$_obj=new Project_Validations_Realtime();
				$_setData=array(
					'type'=> Project_Validations_Realtime::EMAIL_FUNNEL.$value['id'],
					'status'=>$_settings['validation_realtime'],
					'user_id'=>$value['user_id']
				);
				$_obj->setEntered($_setData)->set();
			}
		}
		
		$data=Core_Sql::getAssoc( 'SELECT id, user_id, settings FROM `squeeze_campaigns` WHERE user_id IN ('.Core_Sql::fixInjection($_userIds).')' );
		foreach ($data as $key => $value){
			$_settings=unserialize( base64_decode( $value['settings'] ) );
			if( isset( $_settings['validation_realtime'] ) ){
				$_obj=new Project_Validations_Realtime();
				$_setData=array(
					'type'=> Project_Validations_Realtime::FUNNEL.$value['id'],
					'status'=>$_settings['validation_realtime'],
					'user_id'=>$value['user_id']
				);
				$_obj->setEntered($_setData)->set();
			}
		}
		
		$data=Core_Sql::getAssoc( 'SELECT id, user_id, settings FROM `mooptin` WHERE user_id IN ('.Core_Sql::fixInjection($_userIds).')' );
		foreach ($data as $key => $value){
			$_settings=unserialize( base64_decode( $value['settings'] ) );
			if( isset( $_settings['validation_realtime'] ) ){
				$_obj=new Project_Validations_Realtime();
				$_setData=array(
					'type'=> Project_Validations_Realtime::MOOPTIN.$value['id'],
					'status'=>$_settings['validation_realtime'],
					'user_id'=>$value['user_id']
				);
				$_obj->setEntered($_setData)->set();
			}
		}
		
	}
	// 2018.10.09 /services/updater.php?method=09102018
	public static function update09102018(){
		Project_Automation_Catcher::install();
	}
	
	// 2018.10.02 /services/updater.php?method=20181002
	public static function update20181002(){
		Core_Sql::setExec("ALTER TABLE `u_users` ADD `lpb_full_clicks` INT(11) UNSIGNED NOT NULL DEFAULT '0'");
		Core_Sql::setExec("ALTER TABLE `u_users` ADD `lpb_full_s8rs` INT(11) UNSIGNED NOT NULL DEFAULT '0'");
		Core_Sql::setExec("ALTER TABLE `u_users` ADD `lpb_full_edited` INT(11) UNSIGNED NOT NULL DEFAULT '0'");
	}
	
	// 2018.09.26 /services/updater.php?method=26092018
	public static function update26092018(){
		Project_Automation::install();
		Project_Automation_Event::install();
		Project_Automation_Filter::install();
		Project_Automation_Action::install();
	}
	// 2018.09.04 /services/updater.php?method=04092018
	public static function update04092018(){
		Core_Sql::setExec("ALTER TABLE `u_users` ADD `validation_mounthly` INT(11) UNSIGNED NOT NULL DEFAULT '0'");
		Core_Sql::setExec("ALTER TABLE `u_users` ADD `validation_global` INT(11) UNSIGNED NOT NULL DEFAULT '0'");
	}
	// 2018.08.16 /services/updater.php?method=ClearUsersCredits
	public static function updateClearUsersCredits(){
		Core_Sql::setExec( 'UPDATE `u_users` SET amount = 0' );
	}
	
	// 2018.08.16 /services/updater.php?method=29082018
	public static function update29082018(){
		Core_Sql::setExec("ALTER TABLE `u_users` ADD `validation_realtime` TINYINT(1) NOT NULL DEFAULT '0'");
		Core_Sql::setExec("ALTER TABLE `u_users` ADD `validation_mounthly` INT(11) UNSIGNED NOT NULL DEFAULT '0'");
		Core_Sql::setExec("ALTER TABLE `u_users` ADD `validation_global` INT(11) UNSIGNED NOT NULL DEFAULT '0'");
	}
	// 2018.08.16 /services/updater.php?method=17082018
	public static function update17082018(){
		Core_Sql::setExec("ALTER TABLE `u_users` ADD `validation_limit` INT(11) NULL DEFAULT '0'");
		$_v=new Project_Validations();
		$_v->install();
	}
	
	// 2018.08.02 /services/updater.php?method=02082018
	public static function update02082018(){
		Core_Sql::setExec( 'DELETE FROM `content_setting` WHERE user_id = 100008859 AND flg_source = 9 AND id NOT IN ( SELECT * FROM ( SELECT MAX(id) FROM `content_setting` WHERE user_id = 100008859 AND flg_source = 9 ) t )' );
	}

	// 2018.07.05 /services/updater.php?method=05072018
	public static function update05072018(){
		Core_Sql::setExec( 'ALTER TABLE `lpb_efunnels_message` CHANGE COLUMN `subject` `subject` TEXT NULL DEFAULT NULL AFTER `name`' );
	}
	
	// 2018.06.07 /services/updater.php?method=07062018
	public static function update07062018(){
		Core_Sql::setExec( 'ALTER TABLE content_setting ADD COLUMN flg_default TINYINT NOT NULL DEFAULT \'0\' AFTER settings' );
		Core_Sql::setExec( 'DELETE FROM `content_setting` WHERE id NOT IN ( SELECT * FROM ( SELECT MAX(id) FROM `content_setting` GROUP BY user_id, flg_source ) t )' );
		Core_Sql::setExec( 'UPDATE `content_setting` SET flg_default = 1 WHERE id IN ( SELECT * FROM ( SELECT MAX(id) FROM `content_setting` GROUP BY user_id, flg_source ) t );' );
	}

	public static function update31052018(){
		Core_Sql::setExec( 'ALTER TABLE u_users ADD COLUMN hosting_limit INT(5) NOT NULL DEFAULT \'0\' AFTER added' );
	}

	// 2018.05.25 /services/updater.php?method=AddTermsAgree
	public static function updateAddTermsAgree(){
		Core_Sql::setExec("ALTER TABLE `u_users` ADD `dpa_agree_date` INT(11) NULL DEFAULT NULL;");
		Core_Sql::setExec("ALTER TABLE `u_users` ADD `dpa_agree_ip` INT(11) NULL DEFAULT NULL;");
	}

	// 02.05.2018 /services/updater.php?method=СoderActivation
	public static function updateСoderActivation(){
		Project_Coder::install();
	}

	// 20.03.2018 /services/updater.php?method=ResetSubscribers
	public static function updateResetSubscribers(){
		Core_Sql::setExec("drop table if exists lpb_efunnels_subscribers");
	}

	// 20.03.2018 /services/updater.php?method=EmailFunnels
	public static function updateEmailFunnels(){
		Project_Efunnel::install();
		Project_Efunnel_Message::install();
		Project_Efunnel_Access::install();
		Project_Efunnel_Settings::install();
	}
	
	public static function updateLpbTracking(){
		$dataSqueeze = Core_Sql::getAssoc( 'SELECT id, user_id FROM `squeeze_campaigns`' );
		$_squeeze2user=array();
		foreach( $dataSqueeze as $_data ){
			$_squeeze2user[$_data['id']]=$_data['user_id'];
		}
		ini_set('memory_limit', '-1');
		try {
			Core_Sql::setConnectToServer( 'syndication.qjmpz.com' );
			//========
			$dataView = Core_Sql::getAssoc( 'SELECT * FROM `lpb_subscribers`' );
			$dataClick = Core_Sql::getAssoc( 'SELECT * FROM `lpb_conversions`' );
			//========
			Core_Sql::renewalConnectFromCashe();
		} catch(Exception $e){
			Core_Sql::renewalConnectFromCashe();
			return $this;
		}
		try {
			Core_Sql::setConnectToServer( 'lpb.tracker' );
			//========
			$_userData=array();
			foreach( $dataView as &$_data ){
				if( ( !isset( $_data['uid'] ) || empty( $_data['uid'] ) )
					&& isset( $_data['squeeze_id'] ) 
					&& isset( $_squeeze2user[$_data['squeeze_id']] )
				){
					$_data['uid']=$_squeeze2user[$_data['squeeze_id']];
				}else{
					continue;
				}
				if( !isset( $_userData[$_data['uid']] ) ){
					Core_Sql::setExec( "CREATE TABLE IF NOT EXISTS `lpb_view_".$_data['uid']."` (
						`id` INT(11) NOT NULL AUTO_INCREMENT,
						`squeeze_id` INT(11) NULL DEFAULT NULL,
						`ip` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_unicode_ci',
						`country_id` INT(4) NOT NULL DEFAULT '0',
						`added` INT(11) UNSIGNED NOT NULL DEFAULT '0',
						UNIQUE INDEX `id` (`id`)
					)
					COLLATE='utf8_general_ci'
					ENGINE=InnoDB" );
					Core_Sql::setExec( "CREATE TABLE IF NOT EXISTS `lpb_click_".$_data['uid']."` (
						`id` INT(11) NOT NULL AUTO_INCREMENT,
						`squeeze_id` INT(11) NULL DEFAULT NULL,
						`ip` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_unicode_ci',
						`country_id` INT(4) NOT NULL DEFAULT '0',
						`added` INT(11) UNSIGNED NOT NULL DEFAULT '0',
						UNIQUE INDEX `id` (`id`)
					)
					COLLATE='utf8_general_ci'
					ENGINE=InnoDB" );
					$_userData[$_data['uid']]=array( 'view'=>array() );
				}
				$_userData[$_data['uid']]['view'][]="('".$_data['squeeze_id']."','".$_data['ip']."','".$_data['country_id']."','".$_data['added']."')";
			}
			foreach( $dataClick as &$_data ){
				if( ( !isset( $_data['uid'] ) || empty( $_data['uid'] ) )
					&& isset( $_data['squeeze_id'] ) 
					&& isset( $_squeeze2user[$_data['squeeze_id']] )
				){
					$_data['uid']=$_squeeze2user[$_data['squeeze_id']];
				}else{
					continue;
				}
				if( !isset( $_userData[$_data['uid']]['click'] ) ){
					Core_Sql::setExec( "CREATE TABLE IF NOT EXISTS `lpb_click_".$_data['uid']."` (
						`id` INT(11) NOT NULL AUTO_INCREMENT,
						`squeeze_id` INT(11) NULL DEFAULT NULL,
						`ip` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_unicode_ci',
						`country_id` INT(4) NOT NULL DEFAULT '0',
						`added` INT(11) UNSIGNED NOT NULL DEFAULT '0',
						UNIQUE INDEX `id` (`id`)
					)
					COLLATE='utf8_general_ci'
					ENGINE=InnoDB" );
					$_userData[$_data['uid']]['click']=array();
				}
				$_userData[$_data['uid']]['click'][]="('".$_data['squeeze_id']."','".$_data['ip']."','".$_data['country_id']."','".$_data['added']."')";
			}
			foreach( $_userData as $_userId=>$_user ){
				if( !empty( $_user['click'] ) && $_user['click']!==array() ){
					Core_Sql::setExec("INSERT INTO `lpb_click_".$_userId."` (`squeeze_id`, `ip`, `country_id`, `added`) VALUES ".implode(',',$_user['click']).";");
				}
				if( !empty( $_user['view'] ) && $_user['view']!==array() ){
					Core_Sql::setExec("INSERT INTO `lpb_view_".$_userId."` (`squeeze_id`, `ip`, `country_id`, `added`) VALUES ".implode(',',$_user['view']).";");
				}
			}
			//========
			Core_Sql::renewalConnectFromCashe();
		} catch(Exception $e){
			Core_Sql::renewalConnectFromCashe();
			p( array( $e, $_userData ) );
			return $this;
		}
	}
	
	// 2018.02.26 /services/updater.php?method=FlgFunnel
	public static function updateFlgFunnel(){
		Core_Sql::setExec( "ALTER TABLE `squeeze_campaigns` ADD `flg_funnel` TINYINT(1) NOT NULL DEFAULT '0'" );
		$data = Core_Sql::getAssoc( 'SELECT * FROM `squeeze_campaigns`' );
		foreach ($data as $key => $value){
			$value['settings'] = unserialize( base64_decode( $value['settings'] ) );
			if( !is_null( $value['settings']['flg_funnel'] ) ){
				Core_Sql::setExec( 'UPDATE `squeeze_campaigns` SET `flg_funnel` = \'1\' WHERE `squeeze_campaigns`.`id` =' . $value['id'] );
			}
		}
	}

		// 2018.01.29 /services/updater.php?method=SqueezeTemplatesSettings
	public static function updateSqueezeTemplatesSettings(){
		Project_Squeeze::update();
	}

	// 2017.11.29 /services/updater.php?method=UserMessengerId
	public static function updateUserMessengerId(){
		Core_Sql::setExec("ALTER TABLE  `u_users` ADD  `fb_messenger_id` TEXT NULL DEFAULT NULL;");
	}
		// 2017.11.22 /services/updater.php?method=AddBotAi
	public static function updateAddBotAi(){
		Project_Bot::install();
		Project_Bot_Dialog::install();
	}
	
		// 2017.11.03 /services/updater.php?method=UpdateUser
	public static function updateUpdateUser(){
		Core_Sql::setExec("ALTER TABLE  `u_users` ADD  `settings` TEXT NULL DEFAULT NULL;");
	}

	// 2017.11.02 /services/updater.php?method=AddFacebookId
	public static function updateAddFacebookId(){
		Core_Sql::setExec( 'ALTER TABLE u_users ADD COLUMN fb_user_id VARCHAR(50) NULL DEFAULT NULL AFTER settings' );
	}

	public static function update02062017(){
		Core_Sql::setExec( 'ALTER TABLE u_users ADD COLUMN zonterest_limit INT(5) NOT NULL DEFAULT \'0\' AFTER added' );
	}
	
	public static function update26082016_2(){
		Core_Sql::setExec('DROP TABLE IF EXISTS squeeze_split');
		Core_Sql::setExec('DROP TABLE IF EXISTS squeeze_campaigns2split');
		Core_Sql::setExec('CREATE TABLE `squeeze_split` (
							`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
							`user_id` int(11) unsigned NOT NULL DEFAULT \'0\',
							`flg_closed` int(1) unsigned NOT NULL DEFAULT \'0\',
							`flg_duration` int(1) unsigned NOT NULL DEFAULT \'0\',
							`flg_pause` int(11) NOT NULL DEFAULT \'0\',
							`duration` int(11) unsigned NOT NULL DEFAULT \'0\',
							`title` varchar(255) NOT NULL DEFAULT \' \',
							`url` varchar(255) NOT NULL,
							`edited` int(11) unsigned NOT NULL DEFAULT \'0\',
							`added` int(11) unsigned NOT NULL DEFAULT \'0\',
							PRIMARY KEY (`id`)
						) ENGINE=MyISAM  DEFAULT CHARSET=utf8');


		Core_Sql::setExec('CREATE TABLE `squeeze_campaigns2split` (
							`split_id` int(11) unsigned NOT NULL DEFAULT \'0\',
							`campaign_id` int(11) unsigned NOT NULL DEFAULT \'0\',
							`flg_winner` int(1) unsigned NOT NULL DEFAULT \'0\',
							`shown` int(11) unsigned NOT NULL DEFAULT \'0\',
							`clicks` int(11) NOT NULL DEFAULT \'0\',
							PRIMARY KEY (`split_id`,`campaign_id`)
						) ENGINE=MyISAM DEFAULT CHARSET=utf8;');
	}

	public static function update26082016(){
		self::updateUpdateMoOptin();
		self::updateAddTwilioTable();
	}

	public static function updateUpdateMoOptin(){
		Project_Mooptin::install();
		Project_Mooptin_Autoresponders::install();
		
		Project_Widget_Adapter_Copt_Parts::install();
	}
	
	public static function updateAddTwilioTable(){
		Project_Squeeze_Twilio::install();
	}
	
	public static function updateCBB(){
		Project_Contentbox::install();
	}
	
	public static function updateLPBAddTags(){
		Core_Sql::setExec( "ALTER TABLE `squeeze_campaigns`  ADD `tags` TEXT AFTER `url`" );
	}
	
	// ^ not runed on prod
	public static function updateIamSitesAddUsers(){
		Core_Sql::setExec( "ALTER TABLE `iam_project_sites` ADD COLUMN user_id INT(11) UNSIGNED NOT NULL DEFAULT '0' AFTER settings" );
	}
	
	public static function updateTask152(){
		Core_Sql::setExec( "ALTER TABLE `co_parts` ADD COLUMN clicks_limit INT(11) UNSIGNED NOT NULL DEFAULT '0' AFTER views" );
		Core_Sql::setExec( "ALTER TABLE `co_parts` ADD COLUMN views_limit INT(11) UNSIGNED NOT NULL DEFAULT '0' AFTER clicks_limit" );
	}
	
	public static function updateIamManager(){
		Project_Iam_Sites::install();
	}
	
	public static function updateTask139(){
		Core_Sql::setExec( "ALTER TABLE `iam_users` ADD COLUMN sid VARCHAR(255) NOT NULL DEFAULT '' AFTER clickbank_id" );
	}

	public static function updateIAMUpdate(){
		$_model=new Project_Iam_Users();
		$_model->getList( $_arrUsers );
		Core_Sql::setExec("drop table if exists iam_users2form");
		Core_Sql::setExec( "CREATE TABLE `iam_users2form` (
			`user_id` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			`form_id` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			PRIMARY KEY (`user_id`, `form_id`)
		);" );
		foreach( $_arrUsers as $_user ){
			$_model->addLink( $_user['id'], $_user['form_id'] );
		}
		Core_Sql::setExec("ALTER TABLE `iam_users`
			DROP COLUMN `email_verify`,
			DROP COLUMN `flg_active`,
			DROP COLUMN `form_id`;
		");
	}
	
	public static function updateSqueezeTemplatesAccess(){
		Project_Squeeze_Templates::install();
	}
	
	public static function updateCentili(){
		Core_Sql::setExec("drop table if exists billing_aggregator");

		Core_Sql::setExec( "CREATE TABLE `billing_aggregator` (
			`id` INT(11) NOT NULL AUTO_INCREMENT,
			`aggregator` VARCHAR(255) NULL DEFAULT NULL,
			`status` VARCHAR(8) NULL DEFAULT NULL,
			`errormessage` VARCHAR(255) NULL DEFAULT NULL,
			`event_type` VARCHAR(256) NULL DEFAULT NULL,
			`clientid` VARCHAR(255) NULL DEFAULT NULL,
			`revenuecurrency` DECIMAL(10,4) NULL DEFAULT NULL,
			`phone` VARCHAR(15) NULL DEFAULT NULL,
			`service` VARCHAR(32) NULL DEFAULT NULL,
			`transactionid` INT(11) NULL DEFAULT NULL,
			`enduserprice` DECIMAL(10,3) NULL DEFAULT NULL,
			`country` VARCHAR(2) NULL DEFAULT NULL,
			`mno` VARCHAR(8) NULL DEFAULT NULL,
			`mnocode` VARCHAR(64) NULL DEFAULT NULL,
			`revenue` DECIMAL(10,4) NULL DEFAULT NULL,
			`amount` DECIMAL(10,0) NULL DEFAULT NULL,
			`interval` VARCHAR(16) NULL DEFAULT NULL,
			`opt_in_channel` VARCHAR(16) NULL DEFAULT NULL,
			`sign` VARCHAR(32) NULL DEFAULT NULL,
			`userid` VARCHAR(255) NULL DEFAULT NULL,
			`added` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			UNIQUE INDEX `id` (`id`)
		)
		COLLATE='utf8_general_ci'
		ENGINE=InnoDB;" );
	}

	public static function updateSqueezeButtonsTags(){
		Core_Sql::setExec("drop table if exists lpb_buttontags");

		Core_Sql::setExec( "CREATE TABLE `lpb_buttontags` (
			`id` VARCHAR(32) NOT NULL,
			`tags` VARCHAR(255) NULL DEFAULT '',
			UNIQUE INDEX `id` (`id`)
		)
		COLLATE='utf8_general_ci'
		ENGINE=InnoDB;" );
	}
	
	public static function updateRemoveSqueezeSubscribers(){
		Core_Sql::setExec("drop table if exists squeeze_subscribers");
	}

	public static function updateSqueezeSubscribers(){
		Core_Sql::setExec("drop table if exists lpb_restrictions");
		Core_Sql::setExec( "CREATE TABLE `lpb_restrictions` (
			`id` INT(11) NOT NULL AUTO_INCREMENT,
			`restrictions` INT(11) NULL DEFAULT NULL,
			UNIQUE INDEX `id` (`id`)
		)
		COLLATE='utf8_general_ci'
		ENGINE=InnoDB;" );
		Core_Sql::setExec("drop table if exists lpb_subscribers");
		Core_Sql::setExec( "CREATE TABLE `lpb_subscribers` (
			`id` INT(11) NOT NULL AUTO_INCREMENT,
			`squeeze_id` INT(11) NULL DEFAULT NULL,
			`ip` VARCHAR(255) NULL DEFAULT NULL,
			`added` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			UNIQUE INDEX `id` (`id`)
		)
		COLLATE='utf8_general_ci'
		ENGINE=InnoDB;" );
	}

	// /services/updater.php?method=SqueezeTemplates 2015.02.06
	public static function updateSqueezeTemplates(){
		Core_Sql::setExec( 'ALTER TABLE squeeze_campaigns ADD COLUMN flg_template TINYINT(1) UNSIGNED NULL DEFAULT \'0\' AFTER user_id' );
	}
	
	public static function updateAddIAM(){
		Core_Sql::setExec( "CREATE TABLE `iam_users` (
		`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
		`email` VARCHAR(255) NOT NULL,
		`email_verify` VARCHAR(255) NOT NULL DEFAULT '0',
		`flg_active` INT(1) UNSIGNED NOT NULL DEFAULT '0',
		`form_id` INT(11) NOT NULL DEFAULT '0',
		`clickbank_id` VARCHAR(255) NOT NULL,
		`edited` INT(11) UNSIGNED NOT NULL DEFAULT '0',
		`added` INT(11) UNSIGNED NOT NULL DEFAULT '0',
		 PRIMARY KEY (`id`)
		)
		COLLATE='utf8_general_ci'
		ENGINE=MyISAM" );
		Core_Sql::setExec( "CREATE TABLE `iam_sites` (
			`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
			`category_id` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			`name` VARCHAR(255) NOT NULL,
			`url` TEXT NULL,
			`edited` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			`added` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			 PRIMARY KEY (`id`)
			)
			COLLATE='utf8_general_ci'
			ENGINE=MyISAM" );
		Core_Sql::setExec( "CREATE TABLE `iam_forms` (
			`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
			`name` VARCHAR(255) NOT NULL,
			`sites_settings` TEXT NULL,
			`secret_id` VARCHAR(255) NOT NULL,
			`activations_limit` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			`edited` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			`added` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			 PRIMARY KEY (`id`)
			)
			COLLATE='utf8_general_ci'
			ENGINE=MyISAM" );
		Core_Sql::setExec( "CREATE TABLE `iam_users2sites` (
			`user_id` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			`site_id` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			PRIMARY KEY (`user_id`, `site_id`)
		)
		COLLATE='utf8_general_ci'
		ENGINE=MyISAM" );
	}

	public static function updateAddPublisherLog(){
		Core_Sql::setExec( 'ALTER TABLE pub_project ADD COLUMN error_log TEXT NULL AFTER `flg_status`' );
	}
	
	public static function updateAddMemberMouse(){
		Core_Sql::setExec( 'ALTER TABLE u_users ADD COLUMN mm_id VARCHAR(255) NULL DEFAULT NULL AFTER nickname' );
	}
	
	public static function updateExquisiteTables(){
		Core_Sql::setExec("drop table if exists ulp_layers");
		Core_Sql::setExec( "CREATE TABLE `ulp_layers` (
			`id` INT(11) NOT NULL AUTO_INCREMENT,
			`user_id` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			`popup_id` INT(11) NULL DEFAULT NULL,
			`title` VARCHAR(255) NULL DEFAULT NULL,
			`content` LONGTEXT NULL,
			`zindex` INT(11) NULL DEFAULT '5',
			`details` LONGTEXT NULL,
			`edited` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			`added` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			UNIQUE INDEX `id` (`id`)
		)
		COLLATE='utf8_general_ci'
		ENGINE=InnoDB;" );
		Core_Sql::setExec("drop table if exists ulp_options");
		Core_Sql::setExec( "CREATE TABLE `ulp_options` (
			`id` INT(11) NOT NULL AUTO_INCREMENT,
			`user_id` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			`options_key` VARCHAR(255) NOT NULL,
			`options_value` TEXT NOT NULL,
			UNIQUE INDEX `id` (`id`)
		)
		COLLATE='utf8_unicode_ci'
		ENGINE=MyISAM;" );
		Core_Sql::setExec("drop table if exists ulp_popups");
		Core_Sql::setExec( "CREATE TABLE `ulp_popups` (
			`id` INT(11) NOT NULL AUTO_INCREMENT,
			`user_id` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			`str_id` VARCHAR(31) NULL DEFAULT NULL COLLATE 'latin1_general_cs',
			`title` VARCHAR(255) NULL DEFAULT NULL,
			`width` INT(11) NULL DEFAULT '640',
			`height` INT(11) NULL DEFAULT '400',
			`options` LONGTEXT NULL,
			`edited` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			`added` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			`blocked` INT(11) NULL DEFAULT '0',
			`flg_default` INT(11) NULL DEFAULT '0',
			UNIQUE INDEX `id` (`id`)
		)
		COLLATE='utf8_general_ci'
		ENGINE=InnoDB;
		" );
		Core_Sql::setExec("drop table if exists ulp_subscribers");
		Core_Sql::setExec( "CREATE TABLE `ulp_subscribers` (
			`id` INT(11) NOT NULL AUTO_INCREMENT,
			`popup_id` INT(11) NULL DEFAULT NULL,
			`name` VARCHAR(255) NULL DEFAULT NULL,
			`email` VARCHAR(255) NULL DEFAULT NULL,
			`phone` VARCHAR(255) NULL DEFAULT NULL,
			`message` LONGTEXT NULL,
			`added` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			UNIQUE INDEX `id` (`id`)
		)
		COLLATE='utf8_general_ci'
		ENGINE=InnoDB;" );
	}

	public static function updateEndSynndCampaigns(){
		Core_Sql::setExec( "UPDATE synnd_campaigns SET flg_pause='1'" );
		Core_Sql::setExec( "UPDATE synnd_reports SET flg_status='".Project_Synnd_Reports::$promotionStatus['completed']."' WHERE flg_status='".Project_Synnd_Reports::$promotionStatus['in_queue']."'" );
	}

	public static function updateSqueezeTable(){
		Core_Sql::setExec( "CREATE TABLE `squeeze_campaigns` (
			`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
			`user_id` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			`url` TEXT NULL,
			`settings` TEXT NULL,
			`added` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			`edited` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			PRIMARY KEY (`id`)
		)
		COLLATE='utf8_general_ci'
		ENGINE=MyISAM" );
	}
	
	public static function updateUsersAmount(){
		Core_Sql::setExec( 'ALTER TABLE u_users CHANGE COLUMN amount amount FLOAT UNSIGNED NOT NULL DEFAULT \'0\' AFTER flg_unsubscribe' );
	}

	public static function updateCcsPrice(){
		$_fields=Core_Sql::getField( 'DESCRIBE ccs_sms' );
		if ( in_array( 'cost', $_fields ) ){
			return false;
		}
		Core_Sql::setExec( 'ALTER TABLE ccs_sms ADD COLUMN cost FLOAT UNSIGNED NOT NULL DEFAULT \'0\' AFTER user_id' );
		Core_Sql::setExec( 'ALTER TABLE ccs_voice ADD COLUMN cost FLOAT UNSIGNED NOT NULL DEFAULT \'0\' AFTER user_id' );
	}

	public static function updateFlgPhone(){
		$_fields=Core_Sql::getField( 'DESCRIBE u_users' );
		if ( in_array( 'flg_phone', $_fields ) ){
			return false;
		}
		Core_Sql::setExec( 'ALTER TABLE u_users ADD COLUMN flg_phone TINYINT(1) UNSIGNED NULL DEFAULT \'0\' AFTER flg_expire' );
	}

	public static function updateUserFlgRgigts(){
		Core_Sql::setExec( 'ALTER TABLE u_users ADD COLUMN flg_rights TINYINT(2) UNSIGNED NULL DEFAULT \'0\' AFTER flg_expire' );
	}

	public static function updateUserCounters(){
		Core_Sql::setExec( 'ALTER TABLE u_users
			ADD COLUMN domains_parked INT(11) UNSIGNED NOT NULL DEFAULT \'0\' AFTER popup_height,
			ADD COLUMN domains_ordered INT(11) UNSIGNED NOT NULL DEFAULT \'0\' AFTER domains_parked' );
	}

	public static function updatePaySubscribe(){
		Core_Sql::setExec( 'ALTER TABLE p_subscription
			ADD COLUMN transaction_id VARCHAR(255) NULL DEFAULT NULL AFTER counter,
			ADD COLUMN payment_type TINYINT(2) NULL DEFAULT \'0\' AFTER transaction_id' );
	}

	public static function updateSynndReport(){
		Core_Sql::setExec( 'ALTER TABLE synnd_reports ADD COLUMN error_code TINYINT(3) UNSIGNED NOT NULL DEFAULT \'0\' AFTER flg_status' );
	}

	public static function updatePackageTab(){
		$_fields=Core_Sql::getField( 'DESCRIBE p_package' );
		if ( in_array( 'recurring_credits', $_fields ) ){
			return false;
		}
		Core_Sql::setExec('ALTER TABLE p_package
		ADD COLUMN recurring_credits INT(11) UNSIGNED NOT NULL DEFAULT \'0\' AFTER credits,
		ADD COLUMN recurring_cost FLOAT UNSIGNED NOT NULL DEFAULT \'0\' AFTER cost');
		Core_Sql::setExec('ALTER TABLE p_subscription ADD COLUMN counter INT(11) UNSIGNED NOT NULL DEFAULT \'0\' AFTER cycles_remain');
	}

	public static function updateDirsNCSB(){
		$_arrSites=Core_Sql::getAssoc('SELECT * FROM es_ncsb WHERE ftp_directory LIKE \'%//\'');
		foreach($_arrSites as $_site ){
			$_site['ftp_directory']=($_site['ftp_directory']=='//')?'/':$_site['ftp_directory'];
			Core_Sql::setExec( "UPDATE es_ncsb SET ftp_directory='{$_site['ftp_directory']}' WHERE id={$_site['id']}" );
		}
	}
	public static function updateUrlsNCSB(){
		$_arrSites=Core_Sql::getAssoc('SELECT * FROM es_ncsb WHERE url LIKE \'http://%//%\'');
		foreach($_arrSites as $_site ){
			$_site['url']=rtrim($_site['url'],'//').'/';
			$_site['ftp_directory']=($_site['ftp_directory']=='//')?'/':$_site['ftp_directory'];
			Core_Sql::setExec( "UPDATE es_ncsb SET url='{$_site['url']}' WHERE id={$_site['id']}" );
		}
	}

	public static function updateMaint(){
		Core_Sql::setExec('update u_users set flg_maintenance=1 where id in (select user_id from p_subscription where package_id>34 group by user_id)');
		$_arrIds=Core_Sql::getField('select id from u_users where id in (select user_id from p_subscription where package_id>34 group by user_id)');
		$_group=new Core_Acs_Groups();
		foreach( $_arrIds as $_id ){
			$_group->withIds($_id)->getGroupByUserId( $_arrGroups );
			$_arrGroups+=array( 'Maintenance' );
			$_arrGroups=array_unique( $_arrGroups );
			$_group->withIds($_id)->setGroupByName($_arrGroups);
		}
	}

	public static function updateTraking(){
		Core_Sql::setExec('ALTER TABLE es_ncsb ADD COLUMN flg_traking TINYINT(1) UNSIGNED NOT NULL AFTER damas_ids, ADD COLUMN traking_code TEXT  NOT NULL AFTER flg_traking');
		Core_Sql::setExec('ALTER TABLE es_nvsb ADD COLUMN flg_traking TINYINT(1) UNSIGNED NOT NULL AFTER damas_ids, ADD COLUMN traking_code TEXT  NOT NULL AFTER flg_traking');
	}
	
	public static function updateAdmin(){
		Core_Sql::setExec("DELETE FROM u_users WHERE email IN ('root@root.dev','admin12@cnmbeta.info')");
		Core_Sql::setExec("UPDATE u_users SET id=2 WHERE email='cadmin@cnm.info'");
	}

	public static function updateLinks(){
		Core_Sql::setExec("drop table if exists u_link2template");
		Core_Sql::setExec(" CREATE TABLE u_link2template (
		  template_id INT(11) UNSIGNED NOT NULL,
		  group_id INT(11) UNSIGNED NOT NULL,
		  flg_type TINYINT(1) UNSIGNED NOT NULL
		) ENGINE=MyISAM COMMENT='Link to template in Sites Builder'");
		Core_Sql::setExec("drop table if exists u_link2source");
		Core_Sql::setExec("CREATE TABLE  u_link2source (
		  source_id int(11) unsigned NOT NULL,
		  group_id int(11) unsigned NOT NULL
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Link to source in Content Publisher'");
		$_group=new Core_Acs_Groups();
		$_group->getList($arrGroups);
		$_sourceAcs=new Project_Acs_Source();
		$_templateAcs=new Project_Acs_Template();
		$_ncsb=new Project_Sites_Templates(Project_Sites::NCSB);
		$_nvsb=new Project_Sites_Templates(Project_Sites::NVSB);
		foreach( $arrGroups as $_item ){
			$_sourceIds=array();
			if( !in_array($_item['sys_name'],array('Advertiser','CNM1.0','NVSB Hosted Pro','NVSB Hosted','Blog Fusion','Campaign Optimizer','email test group','Maintenance'))){
				continue;
			}
			foreach( Project_Content::toLabelArray(false) as $_source ){
				if( $_item['sys_name']=='CNM1.0'&&in_array($_source['flg_source'],array(10,4,8,13))){
					$test=1;
					continue;
				}
				$_sourceIds[]=$_source['flg_source'];
			}
			$_sourceAcs->addLink($_item['id'],$_sourceIds);
			$_ncsb->onlyCommon()->onlyIds()->getList( $ncsbIds );
			$_templateAcs->setType( Project_Sites::NCSB )->addLink($_item['id'],$ncsbIds );
			$_nvsb->onlyCommon()->onlyIds()->getList( $nvsbIds );
			$_templateAcs->setType( Project_Sites::NVSB )->addLink($_item['id'],$nvsbIds );
		}
	}
	
	public static function updatePlacement(){
		$fields=Core_Sql::getField( 'DESCRIBE site_placement' );
		if ( !in_array( 'flg_passive', $fields ) ){
			Core_Sql::setExec("ALTER TABLE site_placement ADD COLUMN flg_passive TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 AFTER flg_type");
		}
	}

	public static function updateUsersMaintenance(){
		$fields=Core_Sql::getField( 'DESCRIBE u_users' );
		if ( !in_array( 'flg_maintenance', $fields ) ){
			Core_Sql::setExec("ALTER TABLE u_users ADD COLUMN flg_maintenance TINYINT(1) UNSIGNED NOT NULL DEFAULT '1' AFTER flg_sended");
		}
		$arrRes=Core_Sql::getAssoc('SELECT * FROM u_users WHERE flg_expire=0');
		$_groups=new  Core_Acs_Groups();
		foreach( $arrRes as $_user ){
			$_groups->withIds( $_user['id'] )->addGroupByName(Core_Acs::$maintenance);
		}
	}

	public static function updateUsersApprove(){
		Core_Sql::setExec('ALTER TABLE u_users ADD COLUMN approve INT(11) UNSIGNED NOT NULL DEFAULT 0 AFTER edited');
	}
	public static function updateUsersUnsubscribe(){
		Core_Sql::setExec("ALTER TABLE u_users ADD COLUMN flg_unsubscribe TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER flg_sended");
	}//добавлен 27.06.2012 /services/updater.php?method=UsersUnsubscribe

	public static function updatePackage(){
		Core_Sql::setExec('ALTER TABLE p_package  ADD COLUMN image INT(11) UNSIGNED NOT NULL DEFAULT \'0\' AFTER length');
	}

	public static function updateContent2site(){
		Core_Sql::setExec('ALTER TABLE cs_content2site  CHANGE COLUMN backlink backlink VARCHAR(255) NULL AFTER flg_status');
	}
	public static function updateHIcampaignAddField(){
		Core_Sql::setExec("ALTER TABLE hi_campaign  ADD COLUMN close_text VARCHAR(255) NOT NULL DEFAULT ' ' AFTER border_color");
		Core_Sql::setExec("ALTER TABLE hi_campaign  ADD COLUMN close_color VARCHAR(255) NOT NULL DEFAULT ' ' AFTER border_color");
		Core_Sql::setExec("ALTER TABLE hi_campaign  ADD COLUMN delay INT(11) UNSIGNED NOT NULL DEFAULT '0' AFTER file_sound");
	}
	public static function updateHIcampaign(){
		Core_Sql::setExec("ALTER TABLE hi_campaign  ADD COLUMN flg_lightbox INT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER flg_window");
	}

	public static function updateBfPropSettings(){
		Core_Sql::setExec("ALTER TABLE bf_blogs ADD COLUMN prop_settings TEXT NULL");
	}// добавлен 21.09.11

	public static function updateClickbankLongDescription(){
		Core_Sql::setExec("ALTER TABLE content_clickbank CHANGE COLUMN long_desctiption long_description TEXT NULL AFTER title");
	}// добавлен 13.09.2011 - на дев и продакшн

	public static function updateWidgetSorter(){
		Core_Sql::setExec("ALTER TABLE content_widget ADD COLUMN sort_by TEXT NULL");
	}// добавлен 04.08.2011 - на дев и продакшн

	public static function updateContentProjectId(){
		Core_Sql::setExec("ALTER TABLE es_content ADD COLUMN project_id INT(11) UNSIGNED NOT NULL");
	}// добавлен 28.07.2011
	
	public static function updateProjectAllAtOne(){
		Core_Sql::setExec("ALTER TABLE pub_project ADD COLUMN flg_run TINYINT(2) UNSIGNED NOT NULL DEFAULT '0'");
	}// добавлен 27.07.2011

	public static function updateContentFlgSource(){
		Core_Sql::setExec("ALTER TABLE es_content ADD COLUMN flg_source TINYINT(2) UNSIGNED NOT NULL DEFAULT '0'");
	}// добавлен 26.07.2011

	public static function updateBFpost(){
		Core_Sql::setExec("ALTER TABLE bf_ext_posts ADD COLUMN flg_from TINYINT(1) UNSIGNED NOT NULL DEFAULT '1' AFTER blog_id");
	}

	public static function updatePlrCategory(){
		$arr=Core_Sql::getAssoc('SELECT * FROM category_plr_tree');
		foreach( $arr as $item ){
			$item['title']=str_replace('_',' ',$item['title']);
			Core_Sql::setInsertUpdate('category_plr_tree',$item);
		}
	}

	public static function updateEzineArticles(){
		$arrData=Core_Sql::getAssoc("SELECT category_main AS p, category_secondary AS c FROM articles GROUP BY category_main, category_secondary ORDER BY category_main, category_secondary");
		foreach ($arrData as $value){
			if ( !empty($value['c']) )
				$arrOut[]=array(
					"p" => $value['p'],
					"c" => $value['c']
				);
		}
		print_r(json_encode($arrOut));
		//p(json_encode($arrOut));
	}


	public static function updateContentVideo(){
		Core_Sql::setExec('ALTER TABLE content_video CHANGE embed_code body TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL');
	}

	/**
	 * Добавление в БД таблицы content_setting
	 */
	public static function updateToTask456(){

		Core_Sql::setExec("CREATE TABLE content_setting (id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,user_id INT(11) UNSIGNED NOT NULL,flg_source TINYINT(1) UNSIGNED NOT NULL,settings TEXT NULL,PRIMARY KEY (id))COLLATE='utf8_general_ci' ENGINE=MyISAM ROW_FORMAT=DEFAULT AUTO_INCREMENT=0");
	}


	/**
	 * зачистка от ненужных значений
	 */
	public static function updateCleanerClicbankTypesCategorys(){
		Core_Sql::setExec( "TRUNCATE lng_storage" );
		Core_Sql::setExec( "DELETE FROM category_types WHERE title='Clickbank'");
		Core_Sql::setExec( "DROP TABLE category_clickbank_tree" );
	}
	/**
	 * добавление added, edited to clickbank
	 */
	public static function updateClickbank(){
		Core_Sql::setExec( "ALTER TABLE content_clickbank ADD edited INT UNSIGNED NOT NULL DEFAULT '0',ADD added INT UNSIGNED NOT NULL DEFAULT '0'" );
	}
	
	/**
	 * добавление дэфолтного значения для поля tags.
	 */
	public static function updatePubTable(){
		Core_Sql::setExec( "ALTER TABLE pub_project CHANGE COLUMN tags tags TEXT NULL DEFAULT NULL" );
		Core_Sql::setExec( "ALTER TABLE pub_project  CHANGE COLUMN keywords_random keywords_random INT(10) UNSIGNED NOT NULL DEFAULT '0',  CHANGE COLUMN keywords_first keywords_first INT(10) UNSIGNED NOT NULL DEFAULT '0';" );

	}
	
	/**
	 * два новых поля для системы категорий, для поддержки в них мультиязычности
	 */
	public static function updateCategoryTypeTable(){
		$fields=Core_Sql::getField( 'DESCRIBE category_types' );
		if ( !in_array( 'flg_multilng', $fields ) ){
			Core_Sql::setExec( 'ALTER TABLE category_types ADD flg_multilng tinyint(1) unsigned NOT NULL DEFAULT 0 AFTER flg_typelink' );
		}
		if ( !in_array( 'flg_deflng', $fields ) ){
			Core_Sql::setExec( 'ALTER TABLE category_types ADD flg_deflng tinyint(2) unsigned NOT NULL DEFAULT 0 AFTER flg_multilng' );
		}
	}

	/**
	 * добавление дэфолтного значения для поля.
	 */
	public static function updateCnbTable(){
		Core_Sql::setExec( "ALTER TABLE es_cnb CHANGE parent_id parent_id INT( 11 ) UNSIGNED NOT NULL DEFAULT '0'" );
	}

	/**
	 * два новых поля для системы артиклов дата добавления и редактирования в юникстайм
	 */
	public static function updateArticlesTableDatas(){
		$fields=Core_Sql::getField( 'DESCRIBE hct_am_article' );
		if ( !in_array( 'edited', $fields ) ){
			Core_Sql::setExec( 'ALTER TABLE hct_am_article ADD edited INT(11) UNSIGNED NOT NULL DEFAULT "0" AFTER user_id' );
		}
		if ( !in_array( 'added', $fields ) ){
			Core_Sql::setExec( 'ALTER TABLE hct_am_article ADD added INT(11) UNSIGNED NOT NULL DEFAULT "0" AFTER edited' );
		}
	}

	/**
	 * добавляет все сайты системы в синдикейшн
	 * нужно выполнять один раз до релиза синдикейшена
	 */
	public static function updateDBallSiteToSyndication(){
		foreach( Project_Syndication::$tables as $k=>$v ){
			if ( $k=='badwords' ){
				continue;
			}
			Core_Sql::setExec( 'TRUNCATE TABLE '.$v );
		}
		$_arrSql=array();
		foreach( Project_Sites::$tables as $k=>$v ){
			$_arrSql[]='(SELECT user_id, id, '.$k.' FROM '.$v.')';
		}
		$_strSql='INSERT INTO '.Project_Syndication::$tables['sites'].' (user_id,site_id,flg_type) SELECT * FROM ('.join( ' UNION ', $_arrSql ).') tmp';
		Core_Sql::setExec( $_strSql );
		$indexes=Core_Sql::getAssoc( 'SHOW INDEXES FROM '.Project_Syndication::$tables['sites'] );
		foreach( $indexes as $v ){
			if ( in_array( $v['Key_name'], array( 's_idx', 't_idx', 'u_idx' ) ) ){
				return;
			}
		}
		Core_Sql::setExec( 'ALTER TABLE '.Project_Syndication::$tables['sites'].' ADD INDEX s_idx (site_id),  ADD INDEX t_idx (flg_type),  ADD INDEX u_idx (user_id)' );
	}

	/**
	 * добавить поле catedit во все 5 таблиц содержащих сайты
	 *
	 */
	public static function updateDBsite_tables392(){
		foreach( Project_Sites::$tables as $table ){
			$fields=Core_Sql::getAssoc( 'DESCRIBE '.$table );
			$_flg=$_flg2=false;
			foreach( $fields as $v ){
				if ( $v['Field']=='catedit' ){
					$_flg=true;
				}
				if ( $v['Field']=='user_id'&&empty( $v['Key'] ) ){
					$_flg2=true;
				}
			}
			if ( $_flg2 ){
				Core_Sql::setExec( 'ALTER TABLE '.$table.' ADD INDEX u_idx (user_id),  ADD INDEX c_idx (category_id)' );
			}
			if ( $_flg ){
				continue;
			}
			Core_Sql::setExec( 'ALTER TABLE '.$table.' ADD catedit INT(11) UNSIGNED NOT NULL DEFAULT "0" AFTER added' );
		}
		$indexes=Core_Sql::getAssoc( 'SHOW INDEXES FROM bf_theme2blog_link' );
		foreach( $indexes as $v ){
			if ( in_array( $v['Key_name'], array( 'b_idx', 't_idx' ) ) ){
				return;
			}
		}
		Core_Sql::setExec( 'ALTER TABLE bf_theme2blog_link  ADD INDEX b_idx (blog_id),  ADD INDEX t_idx (theme_id)' );
	}

	/**
	 * обновить поле flg_type для проектов блогфюжен. переход на типы сайтов Project_Sites
	 *
	 */
	public static function updateDBpub_project(){
			Core_Sql::setExec('ALTER TABLE pub_project DROP flg_generate , DROP keywords_random , DROP keywords_first');
		$fields=Core_Sql::getField( 'DESCRIBE pub_project' );
		if ( !in_array( 'keywords_first', $fields ) ){
			Core_Sql::setExec('ALTER TABLE pub_project ADD flg_generate tinyint(1) unsigned NOT NULL DEFAULT 0  AFTER flg_schedule');
			Core_Sql::setExec('ALTER TABLE pub_project ADD keywords_random INT unsigned NOT NULL AFTER flg_generate, ADD keywords_first INT unsigned NOT NULL AFTER keywords_random');
			Core_Sql::setExec('ALTER TABLE pub_schedule ADD keyword VARCHAR( 255 ) NOT NULL');
			Core_Sql::setExec("ALTER TABLE pub_schedule CHANGE blog_id site_id INT( 11 ) UNSIGNED NOT NULL DEFAULT '0'");
			Core_Sql::setExec("ALTER TABLE pub_rsscache CHANGE blog_id site_id INT( 11 ) UNSIGNED NOT NULL DEFAULT '0'");
			Core_Sql::setExec("ALTER TABLE pub_rssblogs CHANGE blog_id site_id INT( 11 ) UNSIGNED NOT NULL DEFAULT '0'");
			Core_Sql::setExec('UPDATE pub_project SET flg_type=5 WHERE flg_type=1');
		}		
	}

	/**
	 * Добавление статей в nvsb
	 *
	 */
	public static function updateDBes_nvsb(){
		$fields=Core_Sql::getField( 'DESCRIBE es_nvsb' );
		if ( !in_array( 'flg_articles', $fields ) ){
			Core_Sql::setExec('ALTER TABLE es_nvsb ADD flg_articles tinyint(1) unsigned NOT NULL DEFAULT 0 AFTER flg_damas');
		}		
	}

	/**
	 * замена старых типов сайтов на новые из Ptoject_Sites
	 *
	 */
	public static function updateSiteType_articles_links(){
		Core_Sql::setExec('UPDATE hct_articles_links SET site_type='.Project_Sites::NCSB.' WHERE site_type='.Project_Articles_Links::Type_NCSB );
		Core_Sql::setExec('UPDATE hct_articles_links SET site_type='.Project_Sites::PSB.' WHERE site_type='.Project_Articles_Links::Type_PSB  );
	}
	
	/**
	 * добавление поля flg_update и added (BlogFusion) Спринт [bf ncsb psb fixes & continue develop syndication feature (20 Apr — 03 May)]
	 *
	 * @param Core_Updater $obj
	 * @return void
	 */
	public static function updateDBbf_blog2update2history308( Core_Updater $obj ){
		$fields=Core_Sql::getField( 'DESCRIBE bf_blog2update' );
		if ( !in_array( 'flg_update', $fields ) ){
			Core_Sql::setExec('ALTER TABLE bf_blog2update ADD flg_update tinyint(1) unsigned NOT NULL DEFAULT 0 AFTER updater_id');
		}
		if ( !in_array('added', $fields) ){
			Core_Sql::setExec('ALTER TABLE bf_blog2update ADD added int(11) unsigned NOT NULL DEFAULT 0 AFTER flg_update');
		}
	}

	/**
	 * добавление поля category_id в NCSB. Спринт [psb refactoring & update blogfusion (01 Apr — 14 Apr)]
	 *
	 * @param Core_Updater $obj
	 * @return void
	 */
	public static function updateDBhct_ncsbsites2sprint52106( Core_Updater $obj  ){
		$fields=Core_Sql::getField("DESCRIBE hct_ncsbsites");
		if ( !in_array( 'category_id', $fields ) ){
			Core_Sql::setExec('ALTER TABLE hct_ncsbsites ADD category_id int(11) unsigned NOT NULL DEFAULT 0 AFTER id');
		}
		if ( !in_array('flg_damas', $fields) ){
			Core_Sql::setExec('ALTER TABLE hct_ncsbsites ADD flg_damas tinyint(1) unsigned NOT NULL DEFAULT 0 AFTER temp_id');
			Core_Sql::setExec('UPDATE hct_ncsbsites SET flg_damas=IF(damas_type="split",2,IF(damas_type="single",1,0))');
			Core_Sql::setExec( 'ALTER TABLE hct_ncsbsites DROP COLUMN damas_type' );
		}
		if ( in_array( 'source_type', $fields ) ){
			Core_Sql::setExec( 'ALTER TABLE hct_ncsbsites DROP COLUMN source_type' );
		}
	}

	/**
	 * Добавление тэгов в публикацию постов через Content Publishing. Спринт [psb refactoring & update blogfusion (01 Apr — 14 Apr)]
	 *
	 * @param Core_Updater $obj
	 * @return void
	 */
	public static function updateDBpub_project2sprint52106( Core_Updater $obj  ){
		$fields=Core_Sql::getField("DESCRIBE pub_project");
		if (!in_array('tags', $fields) ){		
			Core_Sql::setExec('ALTER TABLE pub_project ADD tags TEXT NOT NULL ');
		}
	}
	
	/**
	 * добавление фичи для BlogFusion похожей на прежний master blog 
	 * для подстановки настроек при создании нового блога
	 *
	 * @param Core_Updater $obj
	 * @return void
	 */
	public static function updateUpdateNewBlogFusionTable( Core_Updater $obj ){
		$fields=Core_Sql::getField("DESCRIBE bf_blogs");
		if (!in_array('flg_settings', $fields) ){
			Core_Sql::setExec( 'ALTER TABLE bf_blogs ADD COLUMN flg_settings tinyint(1) unsigned NOT NULL DEFAULT 0 AFTER flg_status' );
		}
	}

	/**
	 * добавление поля flg_status в новый блогфьюжн
	 *
	 * @param Core_Updater $obj
	 * @return void
	 */
	public static function updateNewBlogFusionField( Core_Updater $obj ){
		Core_Sql::setExec( 'ALTER TABLE bf_blogs ADD COLUMN flg_status tinyint(1) unsigned NOT NULL DEFAULT 0 AFTER flg_type' );
	}

	/**
	 * Изменение связки с таблицей u_users (было связано по id будет по parent_id (ethnicashe id))
	 *
	 * @param Core_Updater $obj
	 * @return void
	 */
	public static function updateVideoManager( Core_Updater $obj ){
		Core_Sql::setExec( 'UPDATE cnm_vm_item SET user_id=(SELECT parent_id FROM u_users WHERE id=user_id)' );
		Core_Sql::setExec( 'UPDATE tc_categories SET user_id=(SELECT parent_id FROM u_users WHERE id=user_id) WHERE user_id>0' );
	}

	/**
	 * Удаление дублированых аккаунтов (если человек менял пароль в ethnicashe то ему создавало новый аккаунт)
	 * select GROUP_CONCAT(id SEPARATOR ' - '), nickname, count(*) test, parent_id from u_users group by parent_id HAVING test>1 ORDER BY test;
	 *
	 * @param Core_Updater $obj
	 * @return void
	 */
	public static function updateUusers( Core_Updater $obj ){
		$_arrIds=array( '321', '314', '249', '355', '186', '475', '376', '270', '147', '102', '322', '239', '236', '492', '367', '103', '204', '296', '484', '403', '415', '499', '416', '411' );
		Core_Sql::setExec( '
			DELETE u, group_link
			FROM u_users u
			LEFT JOIN u_link group_link ON group_link.user_id=u.id
			WHERE u.id IN("'.join( '", "', $_arrIds ).'")
		' );
	}

	/**
	 * Добавление поля mod_type в таблицу hct_affiliate_compaign для совместимости Affiliate Profit Booster и Covert Conversion Pro
	 *
	 * @param Core_Updater $obj
	 * @return unknown
	 */
	public static function updateDbHctAffiliateCompaign( Core_Updater $obj ){
		$fields=Core_Sql::getField("DESCRIBE hct_affiliate_compaign");
		if (in_array('mod_type', $fields) ){
			$obj->logger->info( 'This field already exist' );
			return false;
		}
		$sql="ALTER TABLE hct_affiliate_compaign ADD mod_type SET( 'cpp', 'affiliate' ) NOT NULL DEFAULT 'affiliate'";
		if ( Core_Sql::setExec($sql) ){
			$obj->logger->info( 'Update successfully' );
		}
	}
	/**
	 * Добавление поля cloaked в hct_ccp_trackingpages для модуля Covert Conversion Pro 
	 *
	 * @param Core_Updater $obj
	 * @return unknown
	 */
	public static function updateDbHctCcpTrackingpages( Core_Updater $obj ){
		$fields=Core_Sql::getField("DESCRIBE hct_ccp_trackingpages");
		if (in_array('cloaked', $fields) ){
			$obj->logger->info( 'This field already exist' );
			return false;
		}
		$sql="ALTER TABLE hct_ccp_trackingpages ADD cloaked VARCHAR( 255 ) NOT NULL , ADD title VARCHAR( 255 ) NOT NULL , ADD keywords TEXT NOT NULL ";
		if ( Core_Sql::setExec($sql) ){
			$obj->logger->info( 'Update successfully' );
		}
	}
		
	/**
	 * Обновление таблицы hct_spots для совместимости с новыми опшинсами
	 *
	 * @param Core_Updater $obj
	 */
	public static function updateDbHctSpots( Core_Updater $obj ){
		$fields=Core_Sql::getField("DESCRIBE hct_spots");
		if (!in_array('spot_id', $fields) ){
			$sql="ALTER TABLE hct_spots ADD spot_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST ";
			Core_Sql::setExec($sql);
		}
		if (!in_array('spot_video', $fields) ){
			$sql="ALTER TABLE hct_spots ADD spot_video INT NOT NULL DEFAULT '0'";
			Core_Sql::setExec($sql);
		}	
				
		if (!in_array('spot_video_title', $fields) ){
			$sql="ALTER TABLE hct_spots ADD spot_video_title INT NOT NULL DEFAULT '0'";
			Core_Sql::setExec($sql);
		}	
		if (!in_array('spot_position', $fields) ){
			$sql="ALTER TABLE hct_spots ADD spot_position VARCHAR( 255 ) NOT NULL ";
			Core_Sql::setExec($sql);
		}	
		$sql="DELETE FROM hct_spots WHERE site_id > (SELECT id FROM hct_ncsbsites ORDER BY id DESC LIMIT 1) AND site_type='ncsb' ";
		Core_Sql::setExec($sql);
	}
	
	/**
	 * Перенос данных из старых опшинсов в новые.
	 *
	 */
	public static function updateDbHctSpotsLink (){
		$arrData=Core_Sql::getAssoc("SELECT spot_id,spot_saved_selections,spot_snippets FROM hct_spots");
		$arSnippets=array();
		foreach ($arrData as $value){
			$tempSnippetsId=explode(",",$value['spot_snippets']);
			$snippetsId=array();
			foreach ($tempSnippetsId as $i){
				if ($i && $i != 1){
					$snippetsId[]=intval(Project_Options_Encode::decode($i));
				}
			}
			foreach ($snippetsId as $id)
			$arSnippets[]=array(
				"link_spot_id" => $value['spot_id'],
				"link_spot_type" => "snippets",
				"link_data_id"	=>	$id
			);
		}
		if (count($arSnippets))
		Core_sql::setMassInsert("hct_spots_link",$arSnippets);
		foreach ($arrData as $value){
			$tempSavedSelectionsId=explode(",",$value['spot_saved_selections']);
			$SavedSelectionsId=array();
			foreach ($tempSavedSelectionsId as $i){
				if ($i && $i != 1){
					$SavedSelectionsId[]=intval(Project_Options_Encode::decode($i));
				}
			}
			foreach ($SavedSelectionsId as $id)
			$arSavedSelections[]=array(
				"link_spot_id" => $value['spot_id'],
				"link_spot_type" => "savedselections",
				"link_data_id"	=>	$id
			);
		}
		if (count($arSavedSelections))
		Core_sql::setMassInsert("hct_spots_link",$arSavedSelections);			
	}

	/**
	 * Обновление БД 02.10.2009
	 *
	 */
	public function updateToSprint5642(){
		/**
		 * 
		 * 2009_09_08_09_27_43
		 */
		Core_Sql::setExec("DROP TABLE IF EXISTS hct_articles_links");
		Core_Sql::setExec("CREATE TABLE hct_articles_links (site_id int(11) NOT NULL,article_id int(11) NOT NULL, site_type int(10) NOT NULL DEFAULT '1') ENGINE=MyISAM DEFAULT CHARSET=utf8");
		
		/**
		 * 2009_08_03_17_25_57.sql
		 */
		Core_Sql::setExec("DROP TABLE IF EXISTS hct_spots_link");
		Core_Sql::setExec("CREATE TABLE hct_spots_link (link_spot_id int(11) NOT NULL, link_spot_type varchar(20) NOT NULL, link_data_id int(11) NOT NULL) ENGINE=MyISAM DEFAULT CHARSET=utf8");
	}
	
	/**
	 * Обновление таб. hct_snippet_parts для добавления функции Reset CSS 
	 *  а так же возможности сохранения типа ввода данных TEXT or HTML
	 *
	 */
	public function updateDBHctSnippetParts(Core_Updater $obj){
		$obj->logger->info('Start');
//		Core_Sql::setExec("ALTER TABLE hct_snippet_parts ADD reset_css INT NOT NULL DEFAULT '0'");	// отработал 
//		Core_Sql::setExec("ALTER TABLE hct_dams_adcampaigns ADD reset_css INT NOT NULL DEFAULT '0';"); // отработал 
		
		$fields=Core_Sql::getField("DESCRIBE hct_snippet_parts");
		if (!in_array('inputmode', $fields) ){
			Core_Sql::setExec("ALTER TABLE hct_snippet_parts ADD inputmode SET( 'text', 'html' ) NOT NULL DEFAULT 'text'");
		}	
			
		$obj->logger->info('End');
		
	}
}
?>