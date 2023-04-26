<?php
class Project_Efunnel_Sender extends Core_Data_Storage {

	protected $_table='lpb_efunnels_sender';
	protected $_fields=array( 'id', 'unique_id', 'user_id', 'smtp_id', 'title', 'description', 'type', 'options', 'flg_pause', 'edited', 'added' );

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
	
	public function getList( &$mixRes ){
		$this->_crawler=new Core_Sql_Qcrawler();
		$this->assemblyQuery();
		if ( !empty( $this->_withPaging ) ){
			$this->_withPaging['rowtotal']=Core_Sql::getCell( $this->_crawler->get_result_counter( $_strTmp ) );
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

	protected $_withUserId=false;
	
	public function withUserId( $_arrIds=array() ){
		$this->_withUserId=$_arrIds;
		return $this;
	}

	public function activate( $sender_id, $flg_pause ){
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
				Project_Automation::setEvent( Project_Automation_Action::$type['PAUSE_EF'], $sender_id, $_arrEmails, array() );
			} else {
				Project_Automation::setEvent( Project_Automation_Action::$type['RESUME_EF'], $sender_id, $_arrEmails, array() );
			}
		}
		Core_Sql::setExec( 'UPDATE '.$this->_table.' SET `flg_pause` = "'.$flg_pause.'" WHERE `id`="'.$sender_id.'";' );
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
	}

	protected function init(){
		parent::init();
		$this->_onlyStarted=array();
		$this->_onlyTemplates=false;
	}
	
	
	protected function beforeSet(){
		$this->_data->setFilter( array( 'clear' ) );
		if( isset( $this->_data->filtered['id'] ) ){
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
	
	public function create($folder){
		if (file_exists($folder)) {
			return is_writable($folder);
		}
		$folderParent = dirname($folder);
		if($folderParent != '.' && $folderParent != '/' ) {
			if(!$this->create(dirname($folder))) {
				return true;
			}
		}
		if ( is_writable($folderParent) ) {
			if ( mkdir($folder, 0777, true) ) {
				return true;
			}
		}
		return false;
	}
	
	public static function code( $_str='' ){
		$_code='';
		for( $i=0; $i<strlen( $_str ); $i++ ){
			$_code.=sprintf("%02s",dechex( ord( $_str[$i] ) ) );
		}
		return $_code;
	}
	public static function decode( $_str='' ){
		$_decode='';
		$_arrCode=str_split($_str,2);
		foreach( $_arrCode as $_char ){
			$_decode.=chr( hexdec( $_char ) );
		}
		return $_decode;
	}
	
	private $_dTime=3600; // 1 час = 3600 период между запросами по часу
	
	public function save(){
		$_dirName=Zend_Registry::get('config')->path->absolute->mailpool.'users';
		Core_Files::dirScan( $arrUsers, $_dirName, true );
		$_setData=false;
		try {
			Core_Sql::setConnectToServer( 'lpb.tracker' );
			//========
			foreach( $arrUsers[$_dirName] as $_fileName ){
				$userId=str_replace( '.ef', '', $_fileName );
				$_s8r=new Project_Efunnel_Subscribers($userId);
				if( $_fileObj=fopen(Zend_Registry::get('config')->path->absolute->mailpool.'users'.DIRECTORY_SEPARATOR.$_fileName,"r")){
					while( !feof( $_fileObj ) ){
						$_setData=unserialize( fgets( $_fileObj ) );
						if( isset( $_setData['status'] ) && !empty( $_setData['email'] ) ){
							//========
							Core_Sql::setConnectToServer( 'lpb.tracker' );
							//========
							Core_Sql::setExec( 'UPDATE s8rs_'.$_setData['user_id'].' SET `status`="'.$_setData['status'].'", `status_data`="2147483648" WHERE email IN ('.Core_Sql::fixInjection(  $_setData['email'] ).')' );
						}elseif( !empty( $_setData ) && is_array( $_setData ) && isset( $_setData['email'] ) ){
							unset( $_setData['user_id'] );
							$_s8r->setEntered( $_setData )->set();
						}
					}
					fclose( $_fileObj ); 
				}
				unlink( $_dirName.DIRECTORY_SEPARATOR.$_fileName );
			}
			//========
			Core_Sql::renewalConnectFromCashe();
		}catch(Exception $e) {
echo date(DATE_RFC822).': '.$e->getMessage()."\n";
echo date(DATE_RFC822).': '.serialize( $_setData )."\n";
//echo date(DATE_RFC822).': '.'UPDATE s8rs_'.$_setData['user_id'].' SET `status`="'.$_setData['status'].'", `status_data`="2147483648" WHERE email IN ('.Core_Sql::fixInjection(  $_setData['email'] ).')'."\n";
			Core_Sql::renewalConnectFromCashe();
		}
	}
	
	public function errHandler($errno, $errstr, $errfile, $errline){
		echo 'SMTP Error: #'.$errno.' '.mb_convert_encoding( $errstr, "UTF-8" ).'\n\r';
		
	}
	
	public function send(){
		Core_Files::dirScan( $arrDirs, Zend_Registry::get('config')->path->absolute->mailpool.'servers', false );
		foreach( array_keys( $arrDirs ) as $_dirName ){
			$_serverData=explode( '.', basename( $_dirName ) );
			$_serverUrl=self::decode( $_serverData[0] );
			$_serverPort=$_serverData[1];
			if( isset( $_SERVER ) && isset( $_SERVER['SERVER_NAME'] ) && $_SERVER['SERVER_NAME'] == 'cnm.local' ){
				$_serverUrl='test.local';
				$_serverPort='80';
			}
			Core_Files::dirScan( $arrFiles, $_dirName, true );
			if( !isset( $arrFiles[$_dirName] ) || empty( $arrFiles[$_dirName] ) ){
				continue;
			}
			$_badConnections=array();
			foreach( $arrFiles[$_dirName] as $_fileName ){
				$_fileData=file_get_contents( $_dirName.DIRECTORY_SEPARATOR.$_fileName );
				if( $_fileData === false ){
					continue;
				}
				preg_match( '/(.*)\s(EHLO(.*))/s', $_fileData, $_match );
				$_strSave=unserialize( $_match[1] );
				if( !isset( $_strSave['user_id'] ) ){
					continue;
				}
				if( isset( $_badConnections[$_strSave['ef_id']] ) && count( $_badConnections[$_strSave['ef_id']] )>=2 ){
					$_model=new Project_Efunnel();
					$_model->activate( $_strSave['ef_id'], 3 )->setLog( $_strSave['ef_id'], $_badConnections[$_strSave['ef_id']][0] );
					unlink( $_dirName.DIRECTORY_SEPARATOR.$_fileName );
					continue;
				}
				if( isset( $_SERVER ) && isset( $_SERVER['SERVER_NAME'] ) && $_SERVER['SERVER_NAME'] == 'cnm.local' ){
					$_c=fopen( Zend_Registry::get('config')->path->absolute->mailpool.'users.tpl', 'a+' );
				}else{
					$_c=fsockopen( $_serverUrl, $_serverPort, $errno, $errstr, 30 );
				}
				if( !empty( $errstr ) ){
					fclose($_c);
					unlink( $_dirName.DIRECTORY_SEPARATOR.$_fileName );
					$_model=new Project_Efunnel();
					$_model->activate( $_strSave['ef_id'], 3 )->setLog( $_strSave['ef_id'], 'SMTP Connect Error: #'.$errno.' '.htmlspecialchars( mb_convert_encoding( $errstr, "UTF-8" ) ) );
echo "\n".serialize($_strSave)."\n".'SMTP Error: #'.$errno.' '.mb_convert_encoding( $errstr, "UTF-8" );
					continue;
				}
				$_strSend=$_match[2];
				$_arrSend=explode( "\r\n", $_strSend );
				$_return=@fgets( $_c, 9999 );
//echo 'S:'. htmlspecialchars( $_return ) .'<br/>';
				$_flgTlsStart=false;
				$flgAnswer=true;
				$_smtpMessage='';
				foreach( $_arrSend as $key=>&$_sendStr ){
					$_sendStr.="\r\n";
//	echo 'C:'.htmlspecialchars( $_sendStr ) .'<br/>';
					@fputs($_c, $_sendStr);
					$_start=microtime(true);
					if( $flgAnswer ){
						$_return=$this->getAnswer( $_c );
						$_smtpMessage.=$_return;
//	echo 'E:'. htmlspecialchars( $_return ).' '.microtime(true) );echo "<br/>";
					}
					if( strpos( $_sendStr, 'EHLO ' ) !== false && $_return[0] == 5 ){
						continue;
					}
					if( $_sendStr === "DATA\r\n" ){
						$flgAnswer=false;
					}
					if( $_sendStr === ".\r\n" ){
						$flgAnswer=true;
					}
					if( !$_flgTlsStart && strpos( $_return, 'STARTTLS' )!==false ){
						@fputs($_c, "STARTTLS\r\n");
//	echo 'C: STARTTLS'.'<br/>';
						$_return=$this->getAnswer( $_c );
						$_smtpMessage.=$_return;
//	echo 'E:'. htmlspecialchars( $_return ).' '.microtime(true) .'<br/>';
						$cryptoMethod = STREAM_CRYPTO_METHOD_TLS_CLIENT;
						if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
							$cryptoMethod |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
							$cryptoMethod |= STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT;
						}
						set_error_handler(array($this, 'errHandler'));
						$flgCrypto=stream_socket_enable_crypto( $_c, true, $cryptoMethod );
						restore_error_handler();
//	echo 'Crypto:'.$flgCrypto.'<br/>';
						@fputs($_c, "EHLO ifunnels.com\r\n");
//	echo 'C: EHLO ifunnels.com<br/>';
						$_return=$this->getAnswer( $_c );
						$_smtpMessage.=$_return;
//	echo 'E:'. htmlspecialchars( $_return ).' '.microtime(true).'<br/>';
						$_flgTlsStart=true;
					}
					if( $_return[0] == 5 ){ // остановка, пауза
						unlink( $_dirName.DIRECTORY_SEPARATOR.$_fileName );
						$_model=new Project_Efunnel();
						$_model->activate( $_strSave['ef_id'], 3 )->setLog( $_strSave['ef_id'], 'SMTP Send Error: '.$_smtpMessage );
echo "\n".serialize($_strSave)."\n".$_strSend."\n".$_smtpMessage;
						continue 2;
					}
					
					if( $_return[0] == 4 ){ // 2 повторные попытки отправить
						unlink( $_dirName.DIRECTORY_SEPARATOR.$_fileName );
						$_badConnections[$_strSave['ef_id']][]='SMTP Error: '.trim( substr( $_return, strrpos( $_return, ':' )+1 ) );
echo "\n".serialize($_strSave)."\n".$_strSend."\n".$_smtpMessage;
						continue 2;
					}
					
//echo "\n".serialize($_strSave)."\n".$_smtpMessage;
					
				}
				fclose($_c);
				
//echo "\nSEND COMPLITE:".serialize($_strSave)."\n".$_strSend."\n".$_smtpMessage;
				
				unlink( $_dirName.DIRECTORY_SEPARATOR.$_fileName );
				if( !is_dir( Zend_Registry::get('config')->path->absolute->mailpool.'users' ) ){
					$this->create( Zend_Registry::get('config')->path->absolute->mailpool.'users' );
				}
				if( isset( $_strSave['email'] ) ){
					file_put_contents( Zend_Registry::get('config')->path->absolute->mailpool.'users'.DIRECTORY_SEPARATOR.$_strSave['user_id'].'.ef', serialize($_strSave)."\r\n", FILE_APPEND );
				}
			}
		}
	}

	private function getAnswer( $smtp ){
		if( !is_resource($smtp) ){
			return false;
		}
		$data='';
		while( !feof( $smtp ) ){
			$str=@fgets( $smtp, 515 );
			$data.=$str;
			// If response is only 3 chars (not valid, but RFC5321 S4.2 says it must be handled),
			// or 4th character is a space, we are done reading, break the loop,
			// string array access is a micro-optimisation over strlen
			if( !isset($str[3]) or ( isset($str[3]) and $str[3] == ' ' ) ){
				break;
			}
		}
		return $data;
	}

	public static function replaceData( $_content='', $_replace=array() ){
		preg_match_all( '/\{(.*?)::(.*?)\}/', $_content, $_match );
		$_contentCash=$_content;
		foreach( $_match[0] as $_keyR=>$_replaceString ){
			if( isset( $_replace[$_match[1][$_keyR]] ) ){
				$_contentCash=str_replace( $_match[0][$_keyR], $_replace[$_match[1][$_keyR]], $_contentCash );
			}else{
				$_contentCash=str_replace( $_match[0][$_keyR], $_match[2][$_keyR], $_contentCash );
			}
		}
		return $_contentCash;
	}

	public function combine($_userId=false){
		//=======
		$_withLogger=true;
		$_firstStart=$_start=$_memoryStart=0;
		if( $_withLogger ){
			$_writer=new Zend_Log_Writer_Stream( Zend_Registry::get('config')->path->absolute->logfiles.'Project_Efunnels_3line.log' );
			$_writer->setFormatter( new Zend_Log_Formatter_Simple("%timestamp% %priorityName% (%priority%): %message%\r\n") );
			$_logger=new Zend_Log( $_writer );
			$_firstStart=$_start=microtime(true);
			$_logger->info('Start -----------------------------------------------------------------------------------------------------' );
			$_memoryStart=memory_get_usage();
		}
		//=======
		$_objEF=new Project_Efunnel();
	//	$_objEF->withUserId( '100009514' )->withIds( '628' );
		if( $_userId !== false ){
			$_objEF->withUserId( $_userId );
		}else{
			$_objEF->withoutUserId('1');
		}
		$_objEF->onlyStarted()->getList( $_arrCampaigns );

		$_arrCampaignIds=array();
		$_arrUsersIds=array();
		foreach( $_arrCampaigns as $_campaign ){
			$_arrCampaignIds[$_campaign['id']]=$_campaign['id'];
			$_arrUsersIds[$_campaign['user_id']]=true;
		}

		if( $_withLogger && $_memoryStart!==0 && memory_get_usage()-$_memoryStart > 200000000 ){
			$_logger->info('№1 Memory limit 200Mb' );
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
		if( $_withLogger && $_memoryStart!==0 && memory_get_usage()-$_memoryStart > 200000000 ){
			$_logger->info('№2 Memory limit 200Mb' );
			$_logger->info('End--------------------------------------------------------------------------------------' );
			return;
		}
	/*0*/do{// обрабатываем последовательно пользователей
			$userId=array_rand( $_arrUsersIds );
			if( is_file( Zend_Registry::get('config')->path->absolute->mailpool.'users'.DIRECTORY_SEPARATOR.$userId.'.ef' ) ){
				// сбрасываем пользователя, если его не обработал сборщик статистики
				unset( $_arrUsersIds[$userId] );
				continue;
			}
echo "\nui".$userId;
			$_massSubscribersFull=$_massSubscribers=array();
			
			/** Получение всего списка SMTP серверов???? */
			$_settings=new Project_Efunnel_Settings();
			$_settings->keyRecordForm()->getList( $_campaignSMTP );

			$_subs=new Project_Efunnel_Subscribers($userId);
			$_subs->withEfunnelIds( $_arrCampaignIds )->withoutTags()->getRandom2k()->onlyValid()->getList( $_arrSubscribers );
			if( empty( $_arrSubscribers ) ){ // перезапрашиваем один раз, мало ли конец списка
				$_subs=new Project_Efunnel_Subscribers($userId);
				$_subs->withEfunnelIds( $_arrCampaignIds )->withoutTags()->getRandom2k()->onlyValid()->getList( $_arrSubscribers );
			}
			$_arrCampaignsIds=$_arrUpdatedSubscribers=$_arrMessageSubjectOpenRate=array();
			$_arrUnsubscribed=array();
			foreach( $_arrSubscribers as &$_subscribe ){ // собираем всех подписчиков для обработки
				if( !filter_var( $_subscribe['email'], FILTER_VALIDATE_EMAIL) || strpos( $_subscribe['email'], ' ' ) !== false ){
echo "\nError email: ".$_subscribe['email'];
					$_statsData=array(
						'user_id'=>$userId,
						'email'=>$_subscribe['email'],
						'status'=>'not_valid',
					);
					file_put_contents( Zend_Registry::get('config')->path->absolute->mailpool.'users'.DIRECTORY_SEPARATOR.$userId.'.ef',serialize( $_statsData )."\r\n", FILE_APPEND );
					continue;
				}
				foreach( $_subscribe['efunnel_events'] as $_efEvent ){
					foreach( $_efEvent as $_eventName=>$_eventValue ){
						if( strpos($_eventName, 'mo2ar_request_')!==false ){
							foreach( unserialize( base64_decode( $_eventValue ) ) as $_dataName=>$_dataValue ){
								if( !empty( $_dataValue ) && !in_array( $_dataName, array('email', 'ip', 'userAgent','id','callback','-','_') ) ){
									$_subscribe['s8rData'][$_dataName]=$_dataValue;
								}
							}
						}
					}
				}
				foreach( unserialize( base64_decode( $_subscribe['settings'] ) ) as $_sdataName=>$_sdataValue ){
					if( !empty( $_sdataName ) && !empty( $_sdataValue ) ){
						$_subscribe['s8rData'][$_sdataName]=$_sdataValue;
					}
				}
				if( !empty( $_subscribe['name'] ) && $_subscribe['name']!='( )' ){
					$_subscribe['s8rData']['name']=$_subscribe['name'];
				}
				foreach( $_subscribe['efunnel_events'] as $_efEvent ){
					if( isset( $_efEvent['ef_unsubscribe_id'] ) ){
						$_arrUnsubscribed[$_subscribe['email'].'_'.$_efEvent['ef_unsubscribe_id']]=true;
						continue;
					}
					if( empty( $_efEvent['ef_id'] ) ){
						continue;
					}
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
							'ef_id'=>$_efEvent['ef_id'],
							'status'=>$_subscribe['status'],
							'data'=>$_subscribe['s8rData']
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
						if(!isset( $_arrUpdatedSubscribers[$_subscribe['email'].'_'.$_efEvent['ef_id']]['message_clicked'] ) ){
							$_arrUpdatedSubscribers[$_subscribe['email'].'_'.$_efEvent['ef_id']]['message_clicked']=array();
						}
						if( isset( $_efEvent['clicked'] ) && !empty( $_efEvent['clicked'] ) ){
							$_arrUpdatedSubscribers[$_subscribe['email'].'_'.$_efEvent['ef_id']]['message_clicked'][$_efEvent['message_id']]=$_efEvent['clicked'];
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
			foreach( $_arrUpdatedSubscribers as $_email2efs=>$_tmp ){
				if( isset( $_arrUnsubscribed[$_email2efs] ) ){
					unset( $_arrUpdatedSubscribers[$_email2efs] );
				}
			}
			//=======
			if( $_withLogger ){
				$_start=microtime(true)-$_start;
				$_logger->info('Get user '.$userId.' campaigns '.implode(', ', array_keys($_arrCampaignsIds)).' data count '.count($_arrUpdatedSubscribers).' time: '.$_start );
				$_start=microtime(true);
				if( $_memoryStart!==0 && memory_get_usage()-$_memoryStart > 200000000 ){
					$_logger->info('№3 Memory limit 200Mb' );
					$_logger->info('End--------------------------------------------------------------------------------------' );
					return; // выходим есть проблемы со списками, ресурсов не достаточно
				}
				if( $_start - $_firstStart > 600 ){
					$_logger->info('Time limit 600s' );
					$_logger->info('End--------------------------------------------------------------------------------------' );
					break; // выходим есть проблемы со списками, ресурсов не достаточно
				}
			}
			//=======
			$_fullLimit=1000;
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
				if( $_memoryStart!==0 && memory_get_usage()-$_memoryStart > 200000000 ){
					if( $_withLogger ){
						$_logger->info('№4 Memory limit 200Mb' );
						$_logger->info('End--------------------------------------------------------------------------------------' );
					}
					break 2;
				}
			/*2*/foreach( $_arrUpdatedSubscribers as $_subscriber ){
					if($_subscriber['ef_id'] == $_campaign['id']){
						$_flgValidateCampaign=Project_Validations_Realtime::check( $_campaign['user_id'], Project_Validations_Realtime::EMAIL_FUNNEL, $_subscriber['ef_id'] );
						
						if( $_flgValidateCampaign && in_array( $_subscriber['status'], array('deliverable', 'risky', null) ) || !$_flgValidateCampaign ){
							if( !isset( $_campaign['subscribers'][$_subscriber['email']] ) ){
								$_campaign['subscribers'][$_subscriber['email']]=array();
							}
							$_campaign['subscribers'][$_subscriber['email']][time()-$_subscriber['added']]=$_subscriber;
						}
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
				if( empty( $_campaign['smtp']['smtp_server'] ) ){
					continue;
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
						
						$_resenderTimeChecker=@$_campaign['options']['resender_time'];
						if( empty( $_resenderTimeChecker ) ){
							$_resenderTimeChecker=24;
						}
						$_deltaSubTime=0;
					/*4*/foreach( $_arrMes2SubCount as $_check2 ){
							if( $_check2 >= 2 ){
								$_deltaSubTime+=$_resenderTimeChecker*$this->_dTime;
							}
						}
						// логика перехода на следующее сообщение, если предыдущее не открыто пользователем
						$_flgSendMsg=false;

						if( $_subscriber['ef_id'] == $_campaign['id']
							&& $_campaign['options']['flg_resender'] == 1
							&& $_arrMes2SubCount[$_updMessage['id']] < 2 // второе сообщние resender не отправляли
							&& $_subscriber['message_opened'][$_updMessage['id']] == 0 // ни одно из сообщений resender не открывали
							&& isset( $_subscriber['added'] )
						){
							if( array_search($_updMessage['id'], $_subscriber['message_id'])!==false && array_search($_updMessage['id'], $_subscriber['message_id']) > time()-$_resenderTimeChecker*$this->_dTime ){ // время после отправки первого сообщения менее 24 часов
								// ждем дальше... не ранее чем 24 часа отправка
							}elseif( $_period+$_deltaSubTime <= time()-$_subscriber['added'] ){
								// посылаем текущее сообщение
								if( !isset( $_updMessage['subscribers'] ) ){
									$_updMessage['subscribers']=array();
								}
								$_flgSendMsg=true;
								$_updMessage['subscribers'][]=array( 'email'=>$_subscriber['email'], 'data'=>(is_array($_subscriber['data'])?$_subscriber['data']:array())+array( 'email'=>$_subscriber['email'] ) );
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
							$_flgSendMsg=true;
							$_updMessage['subscribers'][]=array( 'email'=>$_subscriber['email'], 'data'=>(is_array($_subscriber['data'])?$_subscriber['data']:array())+array( 'email'=>$_subscriber['email'] ) );
						}
						// тут проверка на resend если нет в отправке этого сообщения
						// проверка на resender
						$_sendMsgValue=self::array_search_last( $_updMessage['id'], $_subscriber['message_id'] );
						if( !$_flgSendMsg
							&& $_subscriber['ef_id'] == $_campaign['id']
							&& $_sendMsgValue !== false
							&& $_sendMsgValue < intval( $_updMessage['resend']['start_time'] )
							&&( $_updMessage['resend']['select'] == 'all'
								|| ( $_updMessage['resend']['select'] == 'open' && isset( $_subscriber['message_opened'][$_updMessage['id']] ) )
								|| ( $_updMessage['resend']['select'] == 'nonopen' && !isset( $_subscriber['message_opened'][$_updMessage['id']] ) )
								|| ( $_updMessage['resend']['select'] == 'click' && isset( $_subscriber['message_clicked'][$_updMessage['id']] ) ) 
							)
							&& $_updMessage['resend']['start_time'] <= time()
						){
							if( !isset( $_updMessage['subscribers'] ) ){
								$_updMessage['subscribers']=array();
							}
							$_updMessage['subscribers'][]=array( 'email'=>$_subscriber['email'], 'data'=>(is_array($_subscriber['data'])?$_subscriber['data']:array())+array( 'email'=>$_subscriber['email'] ) );
						}
					}
				}
				$_campaign['message']=$_arrMessagesByPeriod;
				unset( $_arrMessagesByPeriod );
				$_return=array();
				$_port=25;
				if( !empty( $_campaign['smtp']['smtp_port'] ) ){
					$_port=(int)$_campaign['smtp']['smtp_port'];
				}
				$this->create(Zend_Registry::get('config')->path->absolute->mailpool.DIRECTORY_SEPARATOR.'servers'.DIRECTORY_SEPARATOR.Project_Efunnel_Sender::code( $_campaign['smtp']['smtp_server'] ).'.'.$_port);
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
					// $_send['subscribers']=array_unique( $_send['subscribers'] ); не работает с массой данных
				/*3*/foreach( $_send['subscribers'] as $_emailData ){ // список подписчиков
						// ограничение по колличество у пользователя и по колличеству за вызов
						if( $_campaign['limit'] <= 0 || $_fullLimit <= 0 ){
							break 3; // надо выхзодить на запись рассылки в базу
						}
						$this->createSenderFile( $userId, $_campaign, $_send, $_emailData );

						//=======
						if( $_withLogger ){
							$_start=microtime(true)-$_start;
							$_logger->info('-------[ '.$_emailData['email'].' for '.$_campaign['id'].'.'.$_send['id'].' ]------' );
							if( $_start - $_firstStart > 600 ){
								$_logger->info('Time limit 600s' );
								$_logger->info('Go to saver' );
								break 3; // надо выхзодить на запись рассылки в базу
							}
						}
						//=======
					}
				}
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

	function createSenderFile($userId, $_campaign, $_send, $_emailData, $_flgSendSettings=true){
		$_port=25;
		if( !empty( $_campaign['smtp']['smtp_port'] ) ){
			$_port=(int)$_campaign['smtp']['smtp_port'];
		}
		$_subjectSend='';
		$_keys=$_names=array();
		foreach( $_campaign['options'] as $_key=>&$_name ){
			$_keys[]='%'.strtoupper( $_key ).'%';
			if( strpos( $_name, '|') !== false ){
				$_arrNames=explode( '|', $_name );
				$_name=array_filter( $_arrNames )[array_rand( array_filter( $_arrNames ) )];
			}
			$_names[]=$_name;
		}
		if( isset( $_send['subject'] ) && !empty( $_send['subject'] ) ){
			if( isset( $_send['openRateSubject'][$_emailData['email']] ) ){ // это пересылка сообщения с лучшим subject
				$_subjectSend=$_send['openRateSubject'][$_emailData['email']];
			}elseif( is_array( $_send['subject'] ) ){
				$_subjectSend=array_filter( $_send['subject'] )[array_rand( array_filter( $_send['subject'] ) )];
			}else{
				$_subjectSend=$_send['subject'];
			}
			$_subjectSend=str_replace( '%%%', '%', $_subjectSend );
			$_subjectSend=str_replace( '%%', '%', $_subjectSend );
			$_subjectSend=str_ireplace( $_keys, $_names, $_subjectSend );
		}
echo "\nSend: ".$_emailData['email']." ".$_campaign['id'].".".$_send['id'];
		try{
			$_strLen=$_campaign['id'].'a'.$_send['id'].'b';
			$_boundary=$_strLen.substr( md5($_emailData['email']), 32-strlen($_strLen) );
			$_saveData='';
			$_saveData.="EHLO ifunnels.com\r\n";
			$_saveData.="HELO ifunnels.com\r\n";
			if( isset( $_campaign['smtp']['smtp_user'] ) && !empty( $_campaign['smtp']['smtp_user'] ) ){
				$_saveData.="AUTH LOGIN\r\n";
				$_saveData.=base64_encode($_campaign['smtp']['smtp_user'])."\r\n";
			}
			if( isset( $_campaign['smtp']['smtp_pass'] ) && !empty( $_campaign['smtp']['smtp_pass'] ) ){
				$_saveData.=base64_encode($_campaign['smtp']['smtp_pass'])."\r\n";
			}
			if( isset( $_campaign['smtp']['replay_to'] ) && !empty( $_campaign['smtp']['replay_to'] ) ){
				$_saveData.="MAIL FROM: <".$_campaign['smtp']['replay_to'].">\r\n";
			}elseif( isset( $_campaign['smtp']['from_email'] ) && !empty( $_campaign['smtp']['from_email'] ) ){
				$_saveData.="MAIL FROM: <".$_campaign['smtp']['from_email'].">\r\n";
			}
			$_saveData.="RCPT TO: <".$_emailData['email'].">\r\n";
			$_saveData.="DATA\r\n";
			if( isset( $_campaign['smtp']['from_name'] ) && !empty( $_campaign['smtp']['from_name'] ) && isset( $_campaign['smtp']['from_email'] ) && !empty( $_campaign['smtp']['from_email'] ) ){
				$_saveData.="From: \"".$_campaign['smtp']['from_name']."\" <".$_campaign['smtp']['from_email'].">\r\n";
			}
			if( !empty( $_subjectSend ) ){
				$_saveData.="SUBJECT: ".self::replaceData( $_subjectSend, $_emailData['data'] )."\r\n";
			}
			$_saveData.="To: ".$_emailData['email']."\r\n";
			$_saveData.="MIME-Version: 1.0\r\n";
			$_saveData.="Content-Type: multipart/alternative; boundary=".$_boundary."\r\n";
			$_saveData.="\r\n";
			if( isset( $_send['body_plain_text'] ) && !empty( $_send['body_plain_text'] ) ){
				$_saveData.="--".$_boundary."\r\n";
				$_saveData.="Content-Type: text/plain; charset=\"utf-8\"\r\n";
				$_saveData.="Content-Transfer-Encoding: base64\r\n";
				$_saveData.="\r\n";
				$_sendText=str_replace( '%%%', '%', $_send['body_plain_text'] );
				$_sendText=str_replace( '%%', '%', $_sendText );
				$_sendText=self::replaceData( $_sendText, $_emailData['data'] );
				$_saveData.=rtrim(chunk_split(base64_encode( str_replace( array( "\r\n", "\r", "\n", "\t" ), ' ', str_ireplace( $_keys, $_names, $_sendText ) )." ".str_replace( array( "\r\n", "\r", "\n", "\t" ), ' ', self::replaceData( $_campaign['smtp']['smtp_footer'], $_emailData['data'] ) )." If you wish to stop receiving our emails, visit this link to unsubscribe: ".
					"https://fasttrk.net/email-funnels/unsubscribe/?c=".urlencode( Core_Payment_Encode::encode( array( 'email'=>$_emailData['email'], 'efunnel_id'=>$_campaign['id'], 'user_id'=>$_campaign['user_id'] ) ) ) )));
				$_saveData.="\r\n";
			}
			if( isset( $_send['body_html'] ) && !empty( $_send['body_html'] ) ){
				$_sendBody=str_replace( '%%%', '%', $_send['body_html'] );
				$_sendBody=str_replace( '%%', '%', $_sendBody );
				$_sendBody=str_ireplace( $_keys, $_names, $_sendBody );
				$_sendBody=self::replaceData( $_sendBody, $_emailData['data'] );
				preg_match_all( '/<a(.*)href="(.*?)"/', $_sendBody, $_arrHref );
				$_links=$_updates=array();
				foreach( $_arrHref[2] as $_link ){
					if( in_array( $_link, $_links ) ){
						continue;
					}
					if( $_link[0] == '#' ){
						continue;
					}
					$_links[]='href="'.$_link.'"';
					$_updates[]='href="'.'https://fasttrk.net/email-funnels/webhook/?code='.urlencode( Project_Efunnel_Subscribers::encode( array( 'smtpid' => $_boundary, 'email' => $_emailData['email'], 'link'=>preg_replace( '/((http)(s*)\:\/\/)+/im', '$1', $_link ), 'user_id'=>$_campaign['user_id'], 'event'=>'click' ) ) ).'"';
				}
				$_sendBody=str_ireplace( $_links, $_updates, $_sendBody );
				preg_match_all( '/<img(.*)>/', $_sendBody, $_arrImgs );
				$_attrs=$_updates=array();
				foreach( $_arrImgs[2] as $_attr ){
					if( in_array( $_attr, $_attrs ) ){
						continue;
					}
					$_attrs[]=$_attr;
					$_updates[]=' alt=""'.$_attr;
				}
				$_sendBody=str_ireplace( $_attrs, $_updates, $_sendBody );
				$_sendBody='<img alt="" src="https://fasttrk.net/email-funnels/webhook/?code='.urlencode( Project_Efunnel_Subscribers::encode( array( 'smtpid' => $_boundary, 'email' => $_emailData['email'], 'user_id'=>$_campaign['user_id'], 'subject'=>md5($_subjectSend), 'event'=>'open' ) ) ).'" width="1" height="1" />'.$_sendBody;
				if( !empty( $_send['header_title'] ) ){
					$_sendBody='<span class="preheader" style="color: transparent; display: none; height: 0; max-height: 0; max-width: 0; opacity: 0; overflow: hidden; mso-hide: all; visibility: hidden; width: 0;">'.$_send['header_title'].'</span>'.$_sendBody;
				}
				$_footer='';
				if( isset( $_campaign['smtp']['smtp_footer'] ) && !empty( $_campaign['smtp']['smtp_footer'] ) ){
					$_footer=str_replace( array( "\r", "\n", "\t" ), '', str_replace( "\r\n", "<br/>", self::replaceData( $_campaign['smtp']['smtp_footer'], $_emailData['data'] ) ) );
				}
				$_defaultReferralData=@file_get_contents( './services/referral_link.txt' );
				if( !empty( $_defaultReferralData ) ){
					$_defaultReferralData=unserialize( $_defaultReferralData );
					if( isset( $_defaultReferralData['referral_link'] ) ){
						if( !isset( $_campaign['smtp']['referral_link'] ) || empty( $_campaign['smtp']['referral_link'] ) ){
							$_campaign['smtp']['referral_link']=$_defaultReferralData['referral_link'];
						}
						if( !isset( $_campaign['smtp']['referral_image'] ) || empty( $_campaign['smtp']['referral_image'] ) ){
							$_campaign['smtp']['referral_image']=$_defaultReferralData['referral_image'];
						}
					}
					$_footer.='<br/><a href="'.$_campaign['smtp']['referral_link'].'" traget="_blank" ><img src="'.$_campaign['smtp']['referral_image'].'" width="200px" /></a>';
				}
				// add referal link
				$_saveData.="--".$_boundary."\r\n";
				$_saveData.="Content-Type: text/html; charset=\"utf-8\"\r\n";
				$_saveData.="Content-Transfer-Encoding: base64\r\n";
				$_saveData.="\r\n";
				$_saveData.=rtrim(chunk_split(base64_encode( str_replace( array( "\r", "\n", "\t" ), '', $_sendBody )."<br/>".$_footer.'<br/>If you wish to stop receiving our emails, please <a href="https://fasttrk.net/email-funnels/unsubscribe/?c='.urlencode( Core_Payment_Encode::encode( array( 'email'=>$_emailData['email'], 'efunnel_id'=>$_campaign['id'], 'user_id'=>$_campaign['user_id'] ) ) ).'" target="_blank">click here to unsubscribe</a>.' )));
				$_saveData.="\r\n";
			}
			$_saveData.="\r\n";
			$_saveData.="--".$_boundary."--\r\n";
			$_saveData.=".\r\n";
			$_saveData.="QUIT\r\n";
			$_returnSMTP='';
			$_smtpCheck=microtime(true);
			if( $_flgSendSettings ){
			$_statsData=array(
				'user_id'=>$userId,
				'email'=>$_emailData['email'],
				'ef_id'=>$_campaign['id'],
				'message_id'=>$_send['id'],
				'delivered'=>1,
			//	'smtp'=>$_smtpMessageId, // nrz8anmmRSSiaVusg1y1gw
				'smtpid'=>$_boundary, // nrz8anmmRSSiaVusg1y1gw
				'subject'=>$_subjectSend
			);
			}else{
				$_statsData=array(
					'user_id'=>$userId,
					'ef_id'=>$_campaign['id'],
				);
			}
			$_campaign['limit']--;
			$_fullLimit--;
			file_put_contents( Zend_Registry::get('config')->path->absolute->mailpool.DIRECTORY_SEPARATOR.'servers'.DIRECTORY_SEPARATOR.Project_Efunnel_Sender::code( $_campaign['smtp']['smtp_server'] ).'.'.$_port.DIRECTORY_SEPARATOR.$_boundary.'.ef', serialize($_statsData)."\r\n".$_saveData, LOCK_EX );
			if( !is_file( Zend_Registry::get('config')->path->absolute->mailpool.'users'.DIRECTORY_SEPARATOR.$userId.'.ef' ) ){
				file_put_contents( Zend_Registry::get('config')->path->absolute->mailpool.'users'.DIRECTORY_SEPARATOR.$userId.'.ef','', FILE_APPEND );
			}
		}catch( Exception $e ){
echo "\nError ".$e->getMessage();
		}
	}

	function array_search_last($needle, $array, $strict = false) {
		$keys = array_keys($array);
		//Not sure how smart PHP is, so I'm trying to avoid IF for every iteration
		if($strict) {
		  for($i=count($keys)-1; $i>=0; $i--) {
			//strict search
			if($array[$keys[$i]]===$needle)
			  return $keys[$i];
		  } 
		}
		else {
		  for($i=count($keys)-1; $i>=0; $i--) {
			//benevolent search
			if($array[$keys[$i]]==$needle)
			  return $keys[$i];
		  } 
		}
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