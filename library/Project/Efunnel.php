<?php
class Project_Efunnel extends Core_Data_Storage {

	protected $_table='lpb_efunnels';
	protected $_fields=array( 'id', 'flg_template', 'user_id', 'smtp_id', 'title', 'description', 'type', 'options', 'log_text', 'flg_pause', 'edited', 'added' );

	public static function install(){
		Core_Sql::setExec("drop table if exists lpb_efunnels");
		Core_Sql::setExec( "CREATE TABLE `lpb_efunnels` (
			`id` INT(11) NOT NULL AUTO_INCREMENT,
			`user_id` INT(11) NOT NULL DEFAULT '0',
			`smtp_id` INT(11) NOT NULL DEFAULT '0',
			`title` VARCHAR(100) NULL DEFAULT NULL,
			`description` TEXT NULL,
			`flg_template` INT(1) NOT NULL DEFAULT '0',
			`type` INT(1) NOT NULL DEFAULT '0',
			`options` TEXT NULL,
			`flg_pause` TINYINT(1) NOT NULL DEFAULT '0',
			`added` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			`edited` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			UNIQUE INDEX `id` (`id`)
		)
		COLLATE='utf8_general_ci'
		ENGINE=InnoDB;" );
		return $this;
	}


	public static function uploadTmp($_arrZeroData){
		$_dirTmp='Project_Efunnel@uploadTmp';
		if ( !Zend_Registry::get( 'objUser' )->checkTmpDir( $_tmpDir ) ){
			return false;
		}
		$_checkDirTmp=$_tmpDir.$_dirTmp.DIRECTORY_SEPARATOR;
		if ( !is_dir( $_checkDirTmp ) ){
			if( !Zend_Registry::get( 'objUser' )->prepareTmpDir( $_dirTmp ) ){
				return Core_Data_Errors::getInstance()->setError('Can\'t create dir '.$_dirTmp);
			}
		}else{
			$_dirTmp=$_checkDirTmp;
		}
		if( isset( $_arrZeroData['tmp_name'] ) && isset( $_arrZeroData['name'] ) && copy( $_arrZeroData['tmp_name'],$_dirTmp.$_arrZeroData['name'] ) ){
			return trim($_dirTmp.$_arrZeroData['name'],'.');
		}
		return false;
	}

	public function cronDeleteCampaigns( $_settings=array() ){
		if( !isset( $_settings['page'] ) ){
			return false;
		}
		try{
			Core_Sql::setConnectToServer( 'lpb.tracker' );
			//========
			$_arrEventIds=Core_Sql::getField( 'SELECT event_id FROM s8rs_parameters_'.Core_Users::$info['id'].' WHERE ( name="ef_id" AND value="'.Core_Sql::fixInjection( $_settings['delete'] ).'" ) LIMIT 1000' );
			Core_Sql::setExec( 'DELETE FROM s8rs_parameters_'.Core_Users::$info['id'].' WHERE event_id IN ('.Core_Sql::fixInjection( $_arrEventIds ).')' );
			Core_Sql::setExec( 'DELETE FROM s8rs_events_'.Core_Users::$info['id'].' WHERE id IN ('.Core_Sql::fixInjection( $_arrEventIds ).')' );
			//========
			Core_Sql::renewalConnectFromCashe();
		} catch(Exception $e) {
			echo $e->getMessage();
			Core_Sql::renewalConnectFromCashe();
		return true;
			return false;
		}
	}

	public function getList( &$mixRes ){
		$this->_crawler=new Core_Sql_Qcrawler();
		$this->assemblyQuery();	
		$this->_withPaging['rowtotal']=Core_Sql::getCell( $this->_crawler->get_result_counter( $_strTmp ) );
		if ( !empty( $this->_withPaging ) ){
			$this->_crawler->set_paging( $this->_withPaging )->get_sql( $_strSql, $this->_paging );
		} elseif ( !$this->_onlyCount ){
			$this->_crawler->get_result_full( $_strSql );
		}
		if ( $this->_onlyCell ){
			$mixRes=Core_Sql::getCell( $_strSql );
		} elseif ( $this->_onlyIds ){
			$mixRes=Core_Sql::getField( $_strSql );
		} elseif ( $this->_onlyCount ){
			$mixRes=Core_Sql::getCell( $this->_crawler->get_result_counter() );
		} elseif ( $this->_onlyOne ){
			$mixRes=Core_Sql::getRecord( $_strSql );
			if ( !empty( $mixRes['id'] ) ){
				$_model=new Project_Efunnel_Message();
				$_model->keyRecordForm()->withEfunnelId( $mixRes['id'] )->withOrder( 'd.position--dn' )->getList( $mixRes['message'] );
			}
		} elseif ( $this->_toSelect ){
			$mixRes=Core_Sql::getKeyVal( $_strSql );
		} elseif ( $this->_keyRecordForm ){
			$mixRes=Core_Sql::getKeyRecord( $_strSql );
		} else {
			$mixRes=Core_Sql::getAssoc( $_strSql );
			$_arrEfunnelIds=array();
			foreach( $mixRes as &$data ){
				$_arrEfunnelIds[$data['id']]=$data['id'];
			}
			unset( $data );
			if ( !empty( $_arrEfunnelIds ) ){
				$_model=new Project_Efunnel_Message();
				$_model->keyRecordForm()->withEfunnelId( $_arrEfunnelIds )->withOrder( 'd.position--dn' )->getList( $_arrData );
			}
			foreach( $mixRes as &$data ){
				foreach( $_arrData as $mess ){
					if( $data['id'] == $mess['efunnel_id'] ){
						$data['message'][]=$mess;
					}
				}
			}
			unset( $data );
		}
		$_tags = array();
		$this->_isNotEmpty=!empty( $mixRes );
		if( array_key_exists( 0, $mixRes ) ){
			foreach( $mixRes as &$_arrZeroData ){
				if( isset( $_arrZeroData['options'] ) ){
					$_oldSettings=$_arrZeroData['options'];
					$_arrZeroData['options']=unserialize( base64_decode( $_arrZeroData['options'] ) );
					if( $_arrZeroData['options']===false ){
						$_arrZeroData['options']=$_oldSettings;
					}
					if( preg_match( '/^[,0-9]+$/', $_arrZeroData['options']['tags']) ){
						$_tags = array();
						$_arrZeroData['options']['tags'] = implode( ', ', Project_Tags::get( $_arrZeroData['options']['tags'] ) );
					}
				}
			}
		}elseif( isset( $mixRes['options'] ) ){
			$_oldSettings=$mixRes['options'];
			$mixRes['options']=unserialize( base64_decode( $mixRes['options'] ) );
			if( $mixRes['options']===false ){
				$mixRes['options']=$_oldSettings;
			}
			if( preg_match( '/^[,0-9]+$/', $mixRes['options']['tags']) ){
				$mixRes['options']['tags'] = implode( ', ', Project_Tags::get( $mixRes['options']['tags'] ) );
			}
		}
		$this->init();
		return $this;
	}
	

	protected $_onlyTemplates=false; // c данными popup id
	
	public function onlyTemplates(){
		$this->_onlyTemplates=true;
		return $this;
	}

	protected $_onlyStarted=false; // c данными popup id
	
	public function onlyStarted(){
		$this->_onlyStarted=true;
		return $this;
	}

	protected $_withType=false;
	
	public function withType( $_str=false ){
		$this->_withType=$_str;
		return $this;
	}

	protected $_withTitleLike=false;
	
	public function withTitleLike( $_str=false ){
		$this->_withTitleLike=$_str;
		return $this;
	}

	protected $_withUserId=false;
	protected $_withoutUserId=false;
	
	public function withUserId( $_arrIds=array() ){
		$this->_withUserId=$_arrIds;
		return $this;
	}
	
	public function withoutUserId( $_arrIds=array() ){
		$this->_withoutUserId=$_arrIds;
		return $this;
	}
	
	public $_withTime=false;
	
	public function withTime( $_type, $from, $to ){
		$_now=time();
		switch ($_type){
			case Project_Statistics_Api::TIME_ALL: $this->_withTime=array( 'from'=>0, 'to'=>$_now); break;
			case Project_Statistics_Api::TIME_TODAY: $this->_withTime=array( 'from'=>strtotime('today'), 'to'=>$_now); break;
			case Project_Statistics_Api::TIME_YESTERDAY: $this->_withTime=array( 'from'=>strtotime('yesterday'), 'to'=>strtotime('today')); break;
			case Project_Statistics_Api::TIME_LAST_7_DAYS: $this->_withTime=array( 'from'=>$_now-60*60*24*7, 'to'=>$_now); break;
			case Project_Statistics_Api::TIME_THIS_MONTH: $this->_withTime=array( 'from'=>strtotime('first day of this month'), 'to'=>$_now); break;
			case Project_Statistics_Api::THIS_YEAR: $this->_withTime=array( 'from'=>strtotime('first day of January '.date('Y') ), 'to'=>$_now); break;
			case Project_Statistics_Api::TIME_LAST_YEAR: $this->_withTime=array( 'from'=>$_now-60*60*24*365, 'to'=>$_now); break;
			case 8:
				$_from=$from;
				if( !is_int( $from ) ){
					$_from=strtotime($from);
				}
				$_to=$to;
				if( !is_int( $to ) ){
					$_to=strtotime($to);
				}
				$this->_withTime=array( 'from'=>$_from, 'to'=>$_to );
			break;
		}
		return $this;
	}
	
	public function withFilter( $arrFilter ){
		if( !empty( $arrFilter['time'] ) ){
			$this->withTime( $arrFilter['time'], @$arrFilter['date_from'], @$arrFilter['date_to'] );
		}
		return $this;
	}

	public function setLog( $senderId, $_str='' ){
		if( empty( $senderId ) ){
			return $this;
		}
		Core_Sql::setExec( 'UPDATE '.$this->_table.' SET `log_text` = "'.$_str.'" WHERE `id`="'.$senderId . '";' );
		return $this;
	}

	public function activate( $senderId, $flg_pause ){
		if( empty( $senderId ) ){
			return $this;
		}
		if(Core_Acs::haveAccess( array( 'Automate' ) )){
			try{
				Core_Sql::setConnectToServer( 'lpb.tracker' );
				$_evIds=Core_Sql::getField( 'SELECT event_id FROM s8rs_parameters_'.Core_Users::$info['id'].' WHERE ( name="ef_id" AND value IN ('.Core_Sql::fixInjection( $_GET['id'] ).') )' );
				$_arrEmails=Core_Sql::getField( 'SELECT d.email FROM s8rs_'.Core_Users::$info['id'].' d JOIN s8rs_events_'.Core_Users::$info['id'].' e ON d.id=e.sub_id WHERE e.id IN ("'.implode( '","', $_evIds).'") GROUP BY d.email' );
				Core_Sql::renewalConnectFromCashe();
			} catch(Exception $e) {
				Core_Sql::renewalConnectFromCashe();
				return $this;
			}
			if($flg_pause == 1){
				Project_Automation::setEvent( Project_Automation_Action::$type['PAUSE_EF'], $senderId, $_arrEmails, array() );
			} else {
				Project_Automation::setEvent( Project_Automation_Action::$type['RESUME_EF'], $senderId, $_arrEmails, array() );
			}
		}
		Core_Sql::setExec( 'UPDATE '. $this->_table .' SET `flg_pause` = "' . $flg_pause . '" WHERE `id`="' . $senderId . '";' );
		return $this;
	}
	
	protected function assemblyQuery(){
		parent::assemblyQuery();
		if ( !empty( $this->_onlyStarted ) ){
			$this->_crawler->set_where( 'd.flg_pause=0' );
		}
		if ( !empty( $this->_onlyTemplates ) ){
			$this->_crawler->set_where( 'd.flg_template=1' );
		}
		if ( !empty( $this->_withUserId ) ){
			$this->_crawler->set_where( 'd.user_id IN ('.Core_Sql::fixInjection( $this->_withUserId ).')' );
		}
		if ( !empty( $this->_withoutUserId ) ){
			$this->_crawler->set_where( 'd.user_id NOT IN ('.Core_Sql::fixInjection( $this->_withoutUserId ).')' );
		}
		if ( !empty( $this->_withType ) ){
			$this->_crawler->set_where( 'd.type='.Core_Sql::fixInjection( $this->_withType ) );
		}
		if ( !empty( $this->_withTitleLike ) ){
			$this->_crawler->set_where(  'd.title LIKE '.Core_Sql::fixInjection( '%'.$this->_withTitleLike.'%' ) );
		}
	}

	protected function init(){
		parent::init();
		$this->_onlyStarted=array();
		$this->_onlyTemplates=false;
		$this->_withType=false;
		$this->_withUserId=false;
		$this->_withoutUserId=false;
		$this->_withTitleLike=false;
		$this->_withTime=false;
	}

	public function getStatistic( &$_arrData ){
		set_time_limit(0);
		$_datePeriod='';
		if( $this->_withTime['from'] ){
			$_datePeriod.=' AND e.added>='.$this->_withTime['from'];
		}
		if( $this->_withTime['to'] ){
			$_datePeriod.=' AND e.added<='.$this->_withTime['to'];
		}
		try{
			// SELECT COUNT(*) as counter, c.email FROM s8rs_parameters_1 a JOIN s8rs_events_1 b ON a.event_id=b.id JOIN s8rs_1 c ON c.id=b.sub_id WHERE a.name="message_id" AND a.value=3029 GROUP BY c.email ORDER BY counter DESC;
			// проверяет почему много сообщений отослано пользователю по message_id
			Core_Sql::setConnectToServer( 'lpb.tracker' );
			//========
				$_arrData=Core_Sql::getAssoc( "SELECT 
				COUNT( p.delivered ) as delivered, COUNT( p.bounced ) as bounced, COUNT( p.spam ) as spam, COUNT( p.opened ) as opened, COUNT( p.clicked ) as clicked, SUM( p.unsubscribe ) as unsubscribe, p.message_id, p.ef_id, p.subject 
				FROM (
					SELECT
					SUM(IF(f.name = 'delivered', f.value, NULL)) AS delivered,
					SUM(IF(f.name = 'bounced', f.value, NULL)) AS bounced,
					SUM(IF(f.name = 'spam', f.value, NULL)) AS spam,
					SUM(IF(f.name = 'opened', f.value, NULL)) AS opened,
					SUM(IF(f.name = 'clicked', f.value, NULL)) AS clicked,
					SUM(IF(f.name = 'ef_unsubscribe_id', 1, 0)) AS unsubscribe,
					MAX(IF(f.name = 'message_id', f.value, 0)) AS message_id,
					MAX(IF(f.name = 'ef_id' OR f.name = 'ef_unsubscribe_id', f.value, 0)) AS ef_id,
					MAX(IF(f.name = 'subject', f.value, 0)) AS subject
					FROM s8rs_".Core_Users::$info['id']." d 
						JOIN s8rs_events_".Core_Users::$info['id']." e ON d.id=e.sub_id
						JOIN s8rs_parameters_".Core_Users::$info['id']." f ON e.id=f.event_id
					WHERE f.name IN ('delivered', 'bounced', 'spam', 'opened', 'clicked', 'subject', 'message_id', 'ef_id', 'ef_unsubscribe_id' )".$_datePeriod."
					GROUP BY f.event_id ) p
				WHERE p.ef_id IN (".Core_Sql::fixInjection( $this->_withIds ).")
				GROUP BY p.message_id, p.ef_id, p.subject" );
/*
			$_eventIds=Core_Sql::getField( "SELECT f.event_id FROM s8rs_parameters_".Core_Users::$info['id']." f WHERE ( f.name='ef_id' OR f.name='ef_unsubscribe_id' ) AND f.value IN (".Core_Sql::fixInjection( $this->_withIds ).")" ); // ORDER BY f.event_id DESC LIMIT 200000
			if( !empty( $_eventIds ) ){
				$_arrData=Core_Sql::getAssoc( "SELECT 
				COUNT( p.delivered ) as delivered, COUNT( p.bounced ) as bounced, COUNT( p.spam ) as spam, COUNT( p.opened ) as opened, COUNT( p.clicked ) as clicked, SUM( p.unsubscribe ) as unsubscribe, p.message_id, p.ef_id, p.subject 
				FROM (
					SELECT
					SUM(IF(f.name = 'delivered', f.value, NULL)) AS delivered,
					SUM(IF(f.name = 'bounced', f.value, NULL)) AS bounced,
					SUM(IF(f.name = 'spam', f.value, NULL)) AS spam,
					SUM(IF(f.name = 'opened', f.value, NULL)) AS opened,
					SUM(IF(f.name = 'clicked', f.value, NULL)) AS clicked,
					SUM(IF(f.name = 'ef_unsubscribe_id', 1, 0)) AS unsubscribe,
					MAX(IF(f.name = 'message_id', f.value, 0)) AS message_id,
					MAX(IF(f.name = 'ef_id' OR f.name = 'ef_unsubscribe_id', f.value, 0)) AS ef_id,
					MAX(IF(f.name = 'subject', f.value, 0)) AS subject
					FROM s8rs_".Core_Users::$info['id']." d 
						JOIN s8rs_events_".Core_Users::$info['id']." e ON d.id=e.sub_id
						JOIN s8rs_parameters_".Core_Users::$info['id']." f ON e.id=f.event_id
					WHERE f.name IN ('delivered', 'bounced', 'spam', 'opened', 'clicked', 'subject', 'message_id', 'ef_id', 'ef_unsubscribe_id' )".$_datePeriod."
					GROUP BY f.event_id ) p
				WHERE p.ef_id IN (".Core_Sql::fixInjection( $this->_withIds ).")
				GROUP BY p.message_id, p.ef_id, p.subject" );
			}
*/
			//========
			Core_Sql::renewalConnectFromCashe();
		}catch(Exception $e){
			Core_Sql::renewalConnectFromCashe();
		}
	}
	
	protected function beforeSet(){
		$this->_data->setFilter( array( 'clear' ) );
		if( isset( $this->_data->filtered['id'] ) ){
			if( isset( $this->_data->filtered['options']['flg_override'] ) && $this->_data->filtered['options']['flg_override']==1 ){
				try {
					Core_Sql::setConnectToServer( 'lpb.tracker' );
					//========
					Core_Sql::setExec( 'UPDATE s8rs_parameters_'.Core_Users::$info['id'].' SET name="ef_id" WHERE ( name="ef_unsubscribe_id" AND value="'.Core_Sql::fixInjection( $this->_data->filtered['id'] ).'" )' );
					//========
					Core_Sql::renewalConnectFromCashe();
				} catch(Exception $e) {
					Core_Sql::renewalConnectFromCashe();
					return $this;
				}
			}
			$_model=new Project_Efunnel_Message();
			if( !empty( $this->_data->filtered['delete_message'] ) ){
				$_deleteMessagesIds = false;
				if( is_array( $this->_data->filtered['delete_message'] ) ){
					$_deleteMessagesIds = $this->_data->filtered['delete_message'];
				}elseif( is_string( $this->_data->filtered['delete_message'] ) ){
					$_deleteMessagesIds = explode( ',', $this->_data->filtered['delete_message'] );
				}
				if( $_deleteMessagesIds !== false ){
					$_model->withIds( $_deleteMessagesIds )->del();
					foreach ( $this->_data->filtered['message'] as $key => &$message ){
						if( in_array( $message['id'], $_deleteMessagesIds ) ){
							unSet( $message );
						}
					}
				}
			}
			
			
			$_model->withEfunnelId( $this->_data->filtered['id'] )->getList( $_arrMessages );
			$_updateMessages=$this->_data->filtered['message'];
			foreach( $_arrMessages as $_oldMessage ){
				if( isset( $_updateMessages[$_oldMessage['id']] ) ){
					$_id=$_oldMessage['id'];
					unset( $_oldMessage['id'] );
					unset( $_oldMessage['added'] );
					unset( $_oldMessage['edited'] );
					unset( $_oldMessage['efunnel_id'] );
					$_updateMessages[$_id]=$_updateMessages[$_id]+$_oldMessage;
				}
				$this->_data->setElement('message', $_updateMessages );
			}
		}
		
		if(	!empty( $this->_data->filtered['options']['tags'] ) ){
			$this->_data->filtered['options']['tags'] = Project_Tags::set( $this->_data->filtered['options']['tags'] );
		}
		$_updateOptions=base64_encode( serialize( $this->_data->filtered['options'] ) );
		$this->_data->setElement('options', $_updateOptions );
		return true;
	}
	
	protected function afterSet(){
		$_model=new Project_Efunnel_Message();
		if( isset( $this->_data->filtered['message'] ) && !empty( $this->_data->filtered['message'] ) ){
			foreach( $this->_data->filtered['message'] as &$_message ){
				$_model->setEntered( array( 'efunnel_id'=>$this->_data->filtered['id'] )+$_message )->set();
				$_model->getEntered( $_message );
			}
		}
		$this->_data->filtered['options']=unserialize( base64_decode( $this->_data->filtered['options'] ) );
		if( isset( $this->_data->filtered['options']['validation_realtime'] ) ){
			Project_Validations_Realtime::setValue( Project_Validations_Realtime::EMAIL_FUNNEL, $this->_data->filtered['id'], $this->_data->filtered['options']['validation_realtime'] );
		}
		return true;
	}

	public function duplicate( $_intId=0 ){
		if ( empty( $_intId )||!$this->onlyOne()->withIds( $_intId )->getList( $arrRes ) ){
			return false;
		}
		foreach( $arrRes['message'] as &$_mess ){
			unSet( $_mess['id'] );
		}
		unSet( $arrRes['id'] );
		$this->changeFields( $arrRes );
		return $this->setEntered( $arrRes )->set();
	}
	
	private $_flgTest=false;
	private $_dTime=3600; // 1 час = 3600 период между запросами по часу
	
	public function send(){
		//=======
		$_withLogger=true;
		$_firstStart=$_start=$_memoryStart=0;
		if( $_withLogger ){
			$_writer=new Zend_Log_Writer_Stream( Zend_Registry::get('config')->path->absolute->logfiles.'Project_Efunnels_timing.log' );
			$_writer->setFormatter( new Zend_Log_Formatter_Simple("%timestamp% %priorityName% (%priority%): %message%\r\n") );
			$_logger=new Zend_Log( $_writer );
			$_firstStart=$_start=microtime(true);
			$_logger->info('Start -----------------------------------------------------------------------------------------------------' );
			$_memoryStart=memory_get_usage();
		}
		//=======
		$this->onlyStarted()->getList( $_arrCampaigns );
		$_arrCampaignIds=array();
		$_arrUsersIds=array();
		foreach( $_arrCampaigns as $_campaign ){
			$_arrCampaignIds[$_campaign['id']]=$_campaign['id'];
			$_arrUsersIds[$_campaign['user_id']]=true;
		}
		if( $_withLogger && $_memoryStart!==0 && memory_get_usage()-$_memoryStart > 100000000 ){
			$_logger->info('№1 Memory limit 100Mb' );
			$_logger->info('End--------------------------------------------------------------------------------------' );
			return;
		}
		$_usersObj=new Project_Users_Management();
		$_usersObj->withIds( array_keys( $_arrUsersIds ) )->withGroups()->getList( $_arrUsers );
		$_users2Access=array();
		foreach( $_arrUsers as &$mixRes ){
			Core_Acs::getUserAccessRights( $mixRes );
			$_users2Access[$mixRes['id']]='default';
			if( in_array( 'Email Funnels Performance', $mixRes['groups'] ) ){
				$_users2Access[$mixRes['id']]='performance';
			}
		}
		unset( $_arrUsers );
		foreach( $_arrCampaigns as &$_campaign ){
			if( $_users2Access[$_campaign['user_id']] == 'performance' ){
				$_campaign['limit']=1000;
			}else{
				$_campaign['limit']=100;
			}
		}
		if( $_withLogger && $_memoryStart!==0 && memory_get_usage()-$_memoryStart > 100000000 ){
			$_logger->info('№2 Memory limit 100Mb' );
			$_logger->info('End--------------------------------------------------------------------------------------' );
			return;
		}

	/*0*/do{// обрабатываем последовательно пользователей
		$userId=array_rand( $_arrUsersIds );
		if( $userId == 39180 ){
			unset( $_arrUsersIds[$userId] );
			continue;
		}
echo "\nui".$userId;
			$_massSubscribersFull=$_massSubscribers=array();
			$_subs=new Project_Efunnel_Subscribers($userId);
			$_subs->withEfunnelIds( $_arrCampaignIds )->withoutTags()->getRandom2k()->getList( $_arrSubscribers );
			$_settings=new Project_Efunnel_Settings();
			$_settings->keyRecordForm()->getList( $_campaignSMTP );
			$_arrCampaignsIds=$_arrUpdatedSubscribers=$_arrMessageSubjectOpenRate=array();
			foreach( $_arrSubscribers as $_subscribe ){ // собираем всех подписчиков для обработки
				if( !filter_var( $_subscribe['email'], FILTER_VALIDATE_EMAIL) || strpos( $_subscribe['email'], ' ' ) !== false ){
echo "\nError email: ".$_subscribe['email'];
					continue;
				}
				foreach( $_subscribe['efunnel_events'] as $_efEvent ){
					if( !isset( $_arrMessageSubjectOpenRate[$_efEvent['message_id']] ) ){
						$_arrMessageSubjectOpenRate[$_efEvent['message_id']]=array();
					}
					if( !isset( $_arrMessageSubjectOpenRate[$_efEvent['message_id']][$_efEvent['subject']] ) ){
						$_arrMessageSubjectOpenRate[$_efEvent['message_id']][$_efEvent['subject']]=array();
					}
					if( isset( $_efEvent['opened'] ) ){
						$_arrMessageSubjectOpenRate[$_efEvent['message_id']][$_efEvent['subject']]['opened']+=$_efEvent['opened'];
					}
					if( isset( $_efEvent['delivered'] ) ){
						$_arrMessageSubjectOpenRate[$_efEvent['message_id']][$_efEvent['subject']]['delivered']+=$_efEvent['delivered'];
					}
					$_arrCampaignsIds[$_efEvent['ef_id']]=true;
					if( !isset( $_arrUpdatedSubscribers[$_subscribe['email'].'_'.$_efEvent['ef_id']] ) ){
						$_arrUpdatedSubscribers[$_subscribe['email'].'_'.$_efEvent['ef_id']]=array(
							'email'=>$_subscribe['email'],
							'ef_id'=>$_efEvent['ef_id']
						);
					}
					if( !isset( $_efEvent['message_id'] ) || empty( $_efEvent['message_id'] ) ){
						$_arrUpdatedSubscribers[$_subscribe['email'].'_'.$_efEvent['ef_id']]['added']=$_efEvent['added'];
					}else{
						if(!isset( $_arrUpdatedSubscribers[$_subscribe['email'].'_'.$_efEvent['ef_id']]['message_id'] ) ){
							$_arrUpdatedSubscribers[$_subscribe['email'].'_'.$_efEvent['ef_id']]['message_id']=array();
						}
						if( isset( $_arrUpdatedSubscribers[$_subscribe['email'].'_'.$_efEvent['ef_id']]['message_id'][$_efEvent['added']] ) ){
							$_added=$_efEvent['added'];
							do{
								$_added++;
							}while( isset( $_arrUpdatedSubscribers[$_subscribe['email'].'_'.$_efEvent['ef_id']]['message_id'][$_added] ) );
							$_arrUpdatedSubscribers[$_subscribe['email'].'_'.$_efEvent['ef_id']]['message_id'][$_added]=$_efEvent['message_id'];
						}else{
							$_arrUpdatedSubscribers[$_subscribe['email'].'_'.$_efEvent['ef_id']]['message_id'][$_efEvent['added']]=$_efEvent['message_id'];
						}
						if(!isset( $_arrUpdatedSubscribers[$_subscribe['email'].'_'.$_efEvent['ef_id']]['message_opened'] ) ){
							$_arrUpdatedSubscribers[$_subscribe['email'].'_'.$_efEvent['ef_id']]['message_opened']=array();
						}
						if( isset( $_efEvent['opened'] ) && !empty( $_efEvent['opened'] ) ){
							$_arrUpdatedSubscribers[$_subscribe['email'].'_'.$_efEvent['ef_id']]['message_opened'][$_efEvent['message_id']]=$_efEvent['opened'];
						}
					}
				}
			}
			foreach( $_arrMessageSubjectOpenRate as $_key=>&$_mess ){
				$_maxId=$_maxValue=false;
				foreach( $_mess as $_subjName=>$_subj ){
					if( isset( $_subj['opened'] ) && isset( $_subj['delivered'] ) ){
						$_subj=(int)( $_subj['opened']*100/$_subj['delivered'] );
						if( $_maxValue===false || $_maxValue <= $_subj ){
							$_maxId=$_subjName;
							$_maxValue=$_subj;
						}
					}
				}
				if( $_maxValue !== false ){
					$_mess=$_maxId;
				}else{
					unset( $_arrMessageSubjectOpenRate[$_key] );
				}
			}
			unset( $_arrSubscribers );
			//=======
			if( $_withLogger ){
				$_start=microtime(true)-$_start;
				$_logger->info('Get user '.$userId.' campaigns '.implode(', ', array_keys($_arrCampaignsIds)).' data count '.count($_arrUpdatedSubscribers).' time: '.$_start );
				$_start=microtime(true);
				if( $_memoryStart!==0 && memory_get_usage()-$_memoryStart > 100000000 ){
					$_logger->info('№3 Memory limit 100Mb' );
					$_logger->info('End--------------------------------------------------------------------------------------' );
					return; // выходим есть проблемы со списками, ресурсов не достаточно
				}
				if( $_start - $_firstStart > 30 ){
					$_logger->info('Time limit 30s' );
					$_logger->info('End--------------------------------------------------------------------------------------' );
					break; // выходим есть проблемы со списками, ресурсов не достаточно
				}
			}
			//=======
			$_fullLimit=100;
		/*1*/foreach( $_arrCampaigns as $_k=>&$_campaign ){ // список кампаний
				if( $_campaign['user_id'] != $userId ){
					continue;
				}
				// ограничение по колличество у пользователя и по колличеству за вызов
				if( $_campaign['limit'] <= 0 ){
					break;
				}
				if( $_fullLimit <= 0 ){
					break 2;
				}
				if( !isset( $_arrCampaignsIds[$_campaign['id']] ) ){
					continue;
				}
				if( $_memoryStart!==0 && memory_get_usage()-$_memoryStart > 100000000 ){
					if( $_withLogger ){
						$_logger->info('№4 Memory limit 100Mb' );
						$_logger->info('End--------------------------------------------------------------------------------------' );
					}
					break 2;
				}
			/*2*/foreach( $_arrUpdatedSubscribers as $_subscriber ){
					if( $_subscriber['ef_id'] == $_campaign['id'] ){
						if( !isset( $_campaign['subscribers'][$_subscriber['email']] ) ){
							$_campaign['subscribers'][$_subscriber['email']]=array();
						}
						$_campaign['subscribers'][$_subscriber['email']][time()-$_subscriber['added']]=$_subscriber;
					}
				}
				if( !isset( $_campaign['subscribers'] ) ){
					unset( $_arrCampaigns[$_k] );
					continue;
				}
				if( isset( $_campaignSMTP[$_campaign['smtp_id']] ) ){
					$_campaign['smtp']=$_campaignSMTP[$_campaign['smtp_id']];
					$_campaign['smtp']=unserialize( base64_decode( $_campaign['smtp']['settings'] ) );
				}
				$_arrMessagesByPeriod=array();
				$_deltaMessageTime=0;
			/*2*/foreach( $_campaign['message'] as &$_message ){
					if( $_message['flg_pause'] != 0 ){
						continue;
					}
					if( empty( $_message['subject'] ) || empty( $_message['body_html'] ) ){
						$_model=new Project_Efunnel_Message();
						$_model->withIds($_message['id'])->del();
						continue;
					}
					$_periodInHours=(int)$_message['period_time']*($_message['flg_period']==2?24:1)*$this->_dTime+$_deltaMessageTime;
					$_deltaMessageTime=$_periodInHours;
					if( !isset( $_arrMessagesByPeriod[$_periodInHours] ) ){
						$_arrMessagesByPeriod[$_periodInHours]=$_message+array( 'timing_sec'=>$_periodInHours );
					}else{
						for( $_p=$_periodInHours; $_p<$_periodInHours+$this->_dTime; $_p++ ){
							if( !isset( $_arrMessagesByPeriod[$_p] ) ){
								$_arrMessagesByPeriod[$_p]=$_message+array( 'timing_sec'=>$_p );
								break;
							}
						}
					}
				}
				ksort( $_arrMessagesByPeriod );
			/*2*/foreach( $_arrMessagesByPeriod as $_period=>&$_updMessage ){
					$_updMessage['openRateSubject']=array();
				/*3*/foreach( $_arrUpdatedSubscribers as $_sTemp=>$_subscriber ){
						if( $_subscriber['added'] > time() ){
							continue;
						}
						// считаем сколько раз уже отправлено это $_updMessage['id']
						$_arrMes2SubCount=array_count_values( $_subscriber['message_id'] );
						$_deltaSubTime=0;
					/*4*/foreach( $_arrMes2SubCount as $_check2 ){
							if( $_check2 >= 2 ){
								$_deltaSubTime+=24*$this->_dTime;
							}
						}
						// логика перехода на следующее сообщение, если предыдущее не открыто пользователем
						if( $_subscriber['ef_id'] == $_campaign['id']
							&& $_campaign['options']['flg_resender'] == 1
							&& $_arrMes2SubCount[$_updMessage['id']] < 2 // второе сообщние resender не отправляли
							&& $_subscriber['message_opened'][$_updMessage['id']] == 0 // ни одно из сообщений resender не открывали
							&& isset( $_subscriber['added'] )
						){
							if( array_search($_updMessage['id'], $_subscriber['message_id'])!==false && array_search($_updMessage['id'], $_subscriber['message_id']) > time()-24*$this->_dTime ){ // время после отправки первого сообщения менее 24 часов
								// ждем дальше... не ранее чем 24 часа отправка
							}elseif( $_period+$_deltaSubTime <= time()-$_subscriber['added'] ){
								// посылаем текущее сообщение
								if( !isset( $_updMessage['subscribers'] ) ){
									$_updMessage['subscribers']=array();
								}
								$_updMessage['subscribers'][]=$_subscriber['email'];
								if( isset( $_arrMessageSubjectOpenRate[$_updMessage['id']] ) ){
									$_updMessage['openRateSubject'][$_subscriber['email']]=$_arrMessageSubjectOpenRate[$_updMessage['id']];
								}
							}else{
								// первое и второе не могут быть отправлены по времени
							}
							unset( $_arrUpdatedSubscribers[$_sTemp] );
						}elseif( $_subscriber['ef_id'] == $_campaign['id']
							&& ( !isset( $_subscriber['message_id'] ) || !in_array( $_updMessage['id'], $_subscriber['message_id'] ) )
							&& isset( $_subscriber['added'] )
							&& $_period <= time()-$_subscriber['added']
						){ // посылаем следующее сообщение
							if( !isset( $_updMessage['subscribers'] ) ){
								$_updMessage['subscribers']=array();
							}
							$_updMessage['subscribers'][]=$_subscriber['email'];
						}
					}
				}
				$_campaign['message']=$_arrMessagesByPeriod;
				unset( $_arrMessagesByPeriod );
				$_return=array();
			/*2*/foreach( $_campaign['message'] as $_send ){ // список сообщений
					// ограничение по колличество у пользователя и по колличеству за вызов
					if( $_campaign['limit'] <= 0 || $_fullLimit <= 0 ){
						break 2; // надо выхзодить на запись рассылки в базу
					}
					if( !isset( $_send['subscribers'] ) 
						|| empty( $_send['subscribers'] ) 
						|| !isset( $_campaign['smtp']['smtp_server'] )
						|| empty( $_campaign['smtp']['smtp_server'] )
					){
						continue;
					}
					$_port=25;
					if( !empty( $_campaign['smtp']['smtp_port'] ) ){
						$_port=(int)$_campaign['smtp']['smtp_port'];
					}
				/*3*/foreach( $_send['subscribers'] as $_email ){ // список подписчиков
						// ограничение по колличество у пользователя и по колличеству за вызов
						if( $_campaign['limit'] <= 0 || $_fullLimit <= 0 ){
							break 3; // надо выхзодить на запись рассылки в базу
						}
						//=======
						if( $_withLogger ){
							$_start=microtime(true)-$_start;
							$_logger->info('-------[ '.$_email.' for '.$_campaign['id'].'.'.$_send['id'].' ]------' );
							if( $_start - $_firstStart > 40 ){
								$_logger->info('Time limit 40s' );
								$_logger->info('Go to saver' );
								break 3; // надо выхзодить на запись рассылки в базу
							}
						}
						//=======
						$_subjectSend='';
						$_keys=$_names=array();
						foreach( $_campaign['options'] as $_key=>$_name ){
							$_keys[]='%'.strtoupper( $_key ).'%';
							$_names[]=$_name;
						}
						if( isset( $_send['subject'] ) && !empty( $_send['subject'] ) ){
							if( isset( $_send['openRateSubject'][$_email] ) ){ // это пересылка сообщения с лучшим subject
								$_subjectSend=$_send['openRateSubject'][$_email];
							}elseif( is_array( $_send['subject'] ) ){
								$_subjectSend=array_filter( $_send['subject'] )[array_rand( array_filter( $_send['subject'] ) )];
							}else{
								$_subjectSend=$_send['subject'];
							}
							$_subjectSend=str_replace( '%%%', '%', $_subjectSend );
							$_subjectSend=str_replace( '%%', '%', $_subjectSend );
							$_subjectSend=str_ireplace( $_keys, $_names, $_subjectSend );
						}
						if( $this->_flgTest ){
							$_massSubscribers[]=array(
								'email'=>$_email,
								'ef_id'=>$_campaign['id'],
								'message_id'=>$_send['id'],
								'delivered'=>1,
								'smtp'=>'test_no_send',
								'subject'=>$_subjectSend
							);
							$_massSubscribersFull[]=array(
								'email'=>$_email,
								'user_id'=>$_campaign['user_id'],
								'smtp'=>'test_no_send'
							);
							//=======
							if( $_withLogger ){
								$_start=microtime(true)-$_start;
							//	$_logger->info('test: '.$_start );
							}
							//=======
							continue;
						}
echo "\nSend: ".$_email." ".$_campaign['id'].".".$_send['id'];
						try{
							$_c=fsockopen( $_campaign['smtp']['smtp_server'], $_port, $errno, $errstr, 30 );
							if( !empty( $errno ) || !empty( $errstr ) ){
echo "\nSMTP Error: ".serialize($errno)." str ".serialize($errstr);
							}
							if( !$_c ){
								$_return[]=json_encode( $errno );
echo "\nError: ".serialize($errno);
							}else{
								if( isset( $_campaign['smtp']['smtp_user'] ) && !empty( $_campaign['smtp']['smtp_user'] ) ){
									fputs($_c, "AUTH LOGIN\r\n");
									fputs($_c, base64_encode($_campaign['smtp']['smtp_user'])."\r\n");
								}
								if( isset( $_campaign['smtp']['smtp_pass'] ) && !empty( $_campaign['smtp']['smtp_pass'] ) ){
									fputs($_c, base64_encode($_campaign['smtp']['smtp_pass'])."\r\n");
								}
								if( isset( $_campaign['smtp']['replay_to'] ) && !empty( $_campaign['smtp']['replay_to'] ) ){
									fputs($_c, "MAIL FROM: <".$_campaign['smtp']['replay_to'].">\r\n");
								}elseif( isset( $_campaign['smtp']['from_email'] ) && !empty( $_campaign['smtp']['from_email'] ) ){
									fputs($_c, "MAIL FROM: <".$_campaign['smtp']['from_email'].">\r\n");
								}
//								foreach( $_send['subscribers'] as $_email ){
								fputs($_c, "RCPT TO: <".$_email.">\r\n");
//								}
								fputs($_c, "DATA\r\n"); // начало DATA
								if( isset( $_campaign['smtp']['from_name'] ) && !empty( $_campaign['smtp']['from_name'] ) && isset( $_campaign['smtp']['from_email'] ) && !empty( $_campaign['smtp']['from_email'] ) ){
									fputs($_c, "From: ".$_campaign['smtp']['from_name']." <".$_campaign['smtp']['from_email'].">\r\n");
								}
								if( !empty( $_subjectSend ) ){
									fputs($_c, "SUBJECT: ".$_subjectSend."\r\n");
								}
								fputs($_c, "To: ".$_email."\r\n");
								fputs($_c, "MIME-Version: 1.0\r\n");
								$_strLen=$_campaign['id'].'a'.$_send['id'].'b';
								$_boundary=$_strLen.substr( md5($_email), 32-strlen($_strLen) );
								fputs($_c, "Content-Type: multipart/alternative; boundary=".$_boundary."\r\n");
								fputs($_c, "\r\n");
								if( isset( $_send['body_plain_text'] ) && !empty( $_send['body_plain_text'] ) ){
									fputs($_c, "--".$_boundary."\r\n");
									fputs($_c, "Content-Type: text/plain; charset=\"utf-8\"\r\n");
									fputs($_c, "Content-Transfer-Encoding: base64\r\n");
									fputs($_c, "\r\n");
									$_sendText=str_replace( '%%%', '%', $_send['body_plain_text'] );
									$_sendText=str_replace( '%%', '%', $_sendText );
									fputs($_c, rtrim(chunk_split(base64_encode( str_ireplace( $_keys, $_names, $_sendText )."\r\n".$_campaign['smtp']['smtp_footer']."\r\nIf you wish to stop receiving our emails, visit this link to unsubscribe:\r\n".
										"https://fasttrk.net/email-funnels/unsubscribe/?c=".urlencode( Core_Payment_Encode::encode( array( 'email'=>$_email, 'efunnel_id'=>$_campaign['id'], 'user_id'=>$_campaign['user_id'] ) ) ) ))) );
									fputs($_c, "\r\n");
								}
								if( isset( $_send['body_html'] ) && !empty( $_send['body_html'] ) ){
									$_sendBody=str_replace( '%%%', '%', $_send['body_html'] );
									$_sendBody=str_replace( '%%', '%', $_sendBody );
									$_sendBody=str_ireplace( $_keys, $_names, $_sendBody );
									preg_match_all( '/<a(.*)href="(.*?)"/', $_sendBody, $_arrHref );
									$_links=$_updates=array();
									foreach( $_arrHref[2] as $_link ){
										if( in_array( $_link, $_links ) ){
											continue;
										}
										if( $_link[0] == '#' ){
											continue;
										}
										$_links[]=$_link;
										$_updates[]='https://fasttrk.net/email-funnels/webhook/?code='.urlencode( Project_Efunnel_Subscribers::encode( array( 'smtpid' => $_boundary, 'email' => $_email, 'link'=>preg_replace( '/((http)(s*)\:\/\/)+/im', '$1', $_link ), 'user_id'=>$_campaign['user_id'], 'event'=>'click' ) ) );
									}
									$_sendBody='<img src="https://fasttrk.net/email-funnels/webhook/?code='.urlencode( Project_Efunnel_Subscribers::encode( array( 'smtpid' => $_boundary, 'email' => $_email, 'user_id'=>$_campaign['user_id'], 'subject'=>md5($_subjectSend), 'event'=>'open' ) ) ).'" width="1" height="1" />'.str_ireplace( $_links, $_updates, $_sendBody );
									if( !empty( $_send['header_title'] ) ){
										$_sendBody='<span class="preheader" style="color: transparent; display: none; height: 0; max-height: 0; max-width: 0; opacity: 0; overflow: hidden; mso-hide: all; visibility: hidden; width: 0;">'.$_send['header_title'].'</span>'.$_sendBody;
									}
									$_footer='';
									if( isset( $_campaign['smtp']['smtp_footer'] ) && !empty( $_campaign['smtp']['smtp_footer'] ) ){
										$_footer=str_replace( "\r\n", "<br/>", $_campaign['smtp']['smtp_footer'] );
									}
									fputs($_c, "--".$_boundary."\r\n");
									fputs($_c, "Content-Type: text/html; charset=\"utf-8\"\r\n");
									fputs($_c, "Content-Transfer-Encoding: base64\r\n");
									fputs($_c, "\r\n");
									fputs($_c, rtrim(chunk_split(base64_encode($_sendBody."<br/>".$_footer.'<br/>If you wish to stop receiving our emails, please <a href="https://fasttrk.net/email-funnels/unsubscribe/?c='.urlencode( Core_Payment_Encode::encode( array( 'email'=>$_email, 'efunnel_id'=>$_campaign['id'], 'user_id'=>$_campaign['user_id'] ) ) ).'" target="_blank">click here to unsubscribe</a>.'."\r\n" ))) );
									fputs($_c, "\r\n");
								}
								fputs($_c, "\r\n");
								fputs($_c, "--".$_boundary."--\r\n");
								fputs($_c, ".\r\n"); // окончание DATA
								fputs($_c, "QUIT\r\n");
								$_returnSMTP='';
								$_smtpCheck=microtime(true);
								do{
									$_smtpMessage=fgets($_c);
									$_returnSMTP.=$_smtpMessage;
									if( strpos( $_smtpMessage, '250 Ok: queued as ' ) !== false ){
										$_smtpMessageId=str_replace( '250 Ok: queued as ', '', $_smtpMessage );
									}
									if( strpos( $_smtpMessage, '535 Authentication failed:' ) !== false ){
										$_logger->info('SMTP ERROR: '.$_smtpMessage );
										break 4;
									}
									if( microtime(true)-$_smtpCheck > 2 ){
										echo "\n".$_returnSMTP;
										break 4;
									}
								}while( strpos( $_smtpMessage, '221 ' ) === false );
									$_massSubscribers[]=array(
										'email'=>$_email,
										'ef_id'=>$_campaign['id'],
										'message_id'=>$_send['id'],
										'delivered'=>1,
										'smtp'=>$_smtpMessageId, // nrz8anmmRSSiaVusg1y1gw
										'smtpid'=>$_boundary, // nrz8anmmRSSiaVusg1y1gw
										'subject'=>$_subjectSend
									);
									if( $_campaign['smtp']['smtp_server'] == 'smtp.sendgrid.net' ){
										$_massSubscribersFull[]=array(
											'user_id'=>$_campaign['user_id'],
											'email'=>$_email,
											'smtp'=>$_smtpMessageId
										);
									}
									$_campaign['limit']--;
									$_fullLimit--;
							}
							fclose($_c);
						}catch( Exception $e ){
echo "\nError ".serialize($e);
							//=======
							if( $_withLogger ){
								$_start=microtime(true)-$_start;
								$_logger->info('error: '.$_start );
							//	$_logger->info(serialize($_return));
							}
							//=======
						}
					}
				}
			}
			if( $this->_flgTest ){
				var_dump( $_massSubscribers, $_massSubscribersFull );
			}
			try {
				Core_Sql::setConnectToServer( 'lpb.tracker' );
				//========
echo "\nSave ".count($_massSubscribers);
				$_s8r=new Project_Efunnel_Subscribers($userId);
				foreach( $_massSubscribers as $_s9rData ){ // это медленное сохранение при рассылке, надо пересмотреть
					$_s8r->setEntered( $_s9rData )->set();
				}
				unset( $_massSubscribers );
				if( !empty( $_massSubscribersFull ) ){
					$_s8r=new Project_Efunnel_Smtpids(); // тут пишем в общий список, т.к. от sendgrid не приходит user_id
					$_s8r->setEntered( $_massSubscribersFull )->setMass();
					unset( $_massSubscribersFull );
				}
				//========
				Core_Sql::renewalConnectFromCashe();
			} catch(Exception $e) {
				Core_Sql::renewalConnectFromCashe();
				$this->init();
				return false;
			}
			
			unset( $_arrUsersIds[$userId] );
		}while( count($_arrUsersIds) > 0 );
		//=======
		if( $_withLogger ){
			$_start=microtime(true)-$_start;
			$_logger->info('End time: '.$_start );
			$_logger->info('End transaction -----------------------------------------------------------------------------------------------------' );
		}
		//=======
	}
	
	public function del(){
		if ( empty( $this->_withIds ) ){
			$_bool=false;
		} else {
			Core_Sql::setExec( 'DELETE FROM '.$this->_table.' 
				WHERE id IN('.Core_Sql::fixInjection( $this->_withIds ).')'.($this->_onlyOwner&&$this->getOwnerId( $_intId )? ' AND user_id='.$_intId:'') );
			Core_Sql::setExec( 'DELETE FROM lpb_efunnels_message WHERE efunnel_id IN('.Core_Sql::fixInjection( $this->_withIds ).')' );
			try {
				Core_Sql::setConnectToServer( 'lpb.tracker' );
				//========
				Core_Sql::setExec( 'DELETE FROM lpb_efunnels_subscribers_'.$_intId.' WHERE sender_id IN('.Core_Sql::fixInjection( $this->_withIds ).')' );
				//========
				Core_Sql::renewalConnectFromCashe();
			} catch(Exception $e) {
				Core_Sql::renewalConnectFromCashe();
				return $this;
			}
			$_bool=true;
			if(Core_Acs::haveAccess( array( 'Automate' ) )){
				Project_Automation::setEvent( Project_Automation_Action::$type['REMOVE_EF'], $this->_withIds, false, array() ); 
			}
		}
		$this->init();
		return $_bool;
	}
	
}
?>