<?php
class Project_Automation extends Core_Data_Storage {

	protected $_table='automation';
	protected $_fields=array( 'id', 'user_id', 'title', 'settings', 'flg_pause', 'edited', 'added' );

	public static function install(){
		Core_Sql::setExec("drop table if exists automation");
		Core_Sql::setExec( "CREATE TABLE `automation` (
			`id` INT(11) NOT NULL AUTO_INCREMENT,
			`user_id` INT(11) NOT NULL DEFAULT '0',
			`title` VARCHAR(100) NULL DEFAULT NULL,
			`settings` TEXT NULL,
			`flg_pause` TINYINT(1) NOT NULL DEFAULT '0',
			`edited` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			`added` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			UNIQUE INDEX `id` (`id`)
		)
		COLLATE='utf8_general_ci'
		ENGINE=InnoDB;" );
		return $this;
	}

	public static function setEvent( $_intEvtType, $_strEvtValue, $_varEmail=false, $_arrParams=array() ){
		$_userId=@Core_Users::$info['id'];
		if( isset( $_arrParams['user_id'] ) ){
			$_userId=$_arrParams['user_id'];
		}
		if( !isset( $_intEvtType ) || empty( $_intEvtType )
			|| !isset( $_strEvtValue ) || empty( $_strEvtValue )
			|| !isset( $_varEmail ) || empty( $_varEmail )
			|| !isset( $_userId ) || empty( $_userId )
		){
			return false;
		}
		Core_Users::getInstance()->setById( $_userId );
		$_obj=new Project_Automation_Event();
		$_obj
			->onlyOwner()
			->withEventType($_intEvtType)
			->withEventValue($_strEvtValue)
			->onlyAutoIds()
			->getList($_arrIds);
		if( empty( $_arrIds ) ){
			return false;
		}
		$_catch=new Project_Automation_Catcher();
		$_arrSend=array();
		if( is_array( $_varEmail ) ){
			foreach( $_varEmail as $_email ){
				$_arrSend[]=array(
					'user_id'=>Core_Users::$info['id'],
					'email'=>$_email,
					'auto_ids'=>implode( ',', $_arrIds ),
					'parameters'=>$_arrParams
				);
			}
		}else{
			$_arrSend=array(
				'user_id'=>Core_Users::$info['id'],
				'email'=>$_varEmail,
				'auto_ids'=>implode( ',', $_arrIds ),
				'parameters'=>$_arrParams
			);
		}
		$_catch->setEntered($_arrSend);
		if( is_array( $_varEmail ) ){
			$_catch->setMass();
		}else{
			$_catch->set();
		}
	}
	
	public static function runEvents(){
		$_obj=new Project_Automation_Catcher();
		$_obj->onlyLast()->getList( $_arrEvents ); // берем ближайшие 1000 записей для обработки
		$_idsClear=$_autoIds=$_eventEmails=$_auto4Users=array();
		foreach( $_arrEvents as &$_arrEvent ){
			$_idsClear[$_arrEvent['id']]=$_arrEvent['id'];
			if( strpos( $_arrEvent['auto_ids'], ',' )!==false ){
				$_arrEvent['auto_ids']=explode( ',', $_arrEvent['auto_ids'] );
			}else{
				$_arrEvent['auto_ids']=array( $_arrEvent['auto_ids'] );
			}
			foreach( $_arrEvent['auto_ids'] as $_aId ){
				$_autoIds[$_aId]=$_aId;
			}
			if( !isset( $_eventEmails[$_arrEvent['user_id']] ) ){
				$_eventEmails[$_arrEvent['user_id']]=array(
					'list'=>array(), // список email одного пользователя
					'full'=>array(), // полные данные по events одного email
				);
			}
			if( !isset( $_eventEmails[$_arrEvent['user_id']]['full'][$_arrEvent['email']]['email'] ) ){
				$_eventEmails[$_arrEvent['user_id']]['full'][$_arrEvent['email']]=array( 
					'email'=>$_arrEvent['email']
				);
			}
			$_eventEmails[$_arrEvent['user_id']]['list'][$_arrEvent['email']]=$_arrEvent['email'];
			if( !isset( $_eventEmails[$_arrEvent['user_id']]['full'][$_arrEvent['email']]['events'] ) ){
				$_eventEmails[$_arrEvent['user_id']]['full'][$_arrEvent['email']]['events']=array();
			}
			$_eventEmails[$_arrEvent['user_id']]['full'][$_arrEvent['email']]['events'][$_arrEvent['id']]=$_arrEvent;
		}
		$_obj2=new Project_Automation();
		$_obj2->withIds($_autoIds)->getList($_arrAutomat);
		foreach( $_arrAutomat as $_automat ){
			if( !isset( $_auto4Users[$_automat['user_id']] ) ){
				$_auto4Users[$_automat['user_id']]=array();
			}
			$_auto4Users[$_automat['user_id']][$_automat['id']]=$_automat;
		}
		$_removeCashEvents=array();
		foreach( $_eventEmails as $_userId=>&$_data ){
			$_funnel=new Project_Efunnel_Subscribers($_userId);
			shuffle($_data['list']);
			$_funnel->withEmail($_data['list'])->getList( $_arrS8r );
			$_s8rParsed=array();
			foreach( $_arrS8r as $_s8r ){
				$_s8rParsed[$_s8r['email']]=$_s8r;
			}
			foreach( $_data['full'] as $_email=>$_emailEvents ){
				foreach( $_emailEvents['events'] as $_arrEvent ){
					foreach( $_arrEvent['auto_ids'] as $_autoId ){
						// получаем настройки авто проекта
						// при условии что фильтр соответсвуюет выполняем действия
						if( $_obj2->checkFilter($_auto4Users[$_userId][$_autoId]['filters'], $_auto4Users[$_userId][$_autoId]['settings']['logical_diagram'], $_s8rParsed[$_email]) ){
							$_obj2->runActions($_auto4Users[$_userId][$_autoId]['actions'], $_s8rParsed[$_email], $_userId, $_auto4Users[$_userId][$_autoId]['id']);
						}
					}
					$_removeCashEvents[$_arrEvent['id']]=$_arrEvent['id'];
				}
			}
		}
		$_obj->withIds($_removeCashEvents)->del();
	}

	/*
	public static $type=array( //(ability to add AND / OR so we combine filters)
		'HAVE_TAGS'=>1, //Contact has / does not have tag
		'OPEN_EMAILS'=>2, //Has opened Email
		'CLICK_EMAIL_LINK'=>3, //Has clicked Email link
		'HAVE_EF'=>4, //Is / Is Not in Email Funnel
		'COMPLEAT_EF'=>5, //Has completed Email Funnel
		'PAUSE_EF'=>6, //Is paused in Email Funnel
	);
	*/
	protected function checkFilter($_filters, $_logic, $_s8r){
		if( !isset( $_filters ) || empty( $_filters ) || !isset( $_logic ) || empty( $_logic ) ){
			return true;
		}
		$_arrEfForFilter=$_arrEfIds=$_arrEfMessIds=array();
		foreach( $_filters as $_filterType=>$_arrFilters ){
			if( in_array( $_filterType, array(
				Project_Automation_Filter::$type['COMPLEAT_EF'],
				Project_Automation_Filter::$type['PAUSE_EF']
			) ) ){
				foreach( $_arrFilters as $_filter ){
					$_arrEfIds=$_arrEfIds+explode(',',$_filter['value']);
				}
			}
		}
		if( !empty( $_arrEfIds ) ){
			$_efunnel=new Project_Efunnel();
			$_efunnel
				->withIds( array_unique( $_arrEfIds ) )
				->getList(	$_arrEfunnels );
			foreach( $_arrEfunnels as $_ef ){
				$_arrEfForFilter[ $_ef['id'] ]=array( 'flg_pause'=> $_ef['flg_pause'], 'end_mess_id'=> end( $_ef['message'] )['id'] );
			}
			unset( $_arrEfunnels );
		}
		$_updateLogic=$_logic;
		foreach( $_filters as $_filterType=>$_arrFilters ){
			foreach( $_arrFilters as $_filter ){
				$_flgCheck=false;
				switch( $_filterType ){
					case Project_Automation_Filter::$type['HAVE_TAGS']: // +
						$_efTagsList=explode(',',$_filter['value']);
						if( count( array_intersect( $_efTagsList, $_s8r['tags'] ) ) == count( $_efTagsList ) ){
							$_flgCheck=true;
						}
					break;
					case Project_Automation_Filter::$type['OPEN_EMAILS']: // +
						$_efMessIdsList=explode(',',$_filter['value']);
						foreach( $_s8r['efunnel_events'] as $_event ){
							if( isset( $_event['message_id'] ) && in_array( $_event['message_id'], $_efMessIdsList ) && isset( $_event['opened'] ) && !empty( $_event['opened'] ) ){
								$_flgCheck=true;
							}
						}
					break;
					case Project_Automation_Filter::$type['CLICK_EMAIL_LINK']: // +
						$_efMessIdsList=explode(',',$_filter['value']);
						foreach( $_s8r['efunnel_events'] as $_event ){
							if( isset( $_event['message_id'] ) && in_array( $_event['message_id'], $_efMessIdsList ) && isset( $_event['clicked'] ) && !empty( $_event['clicked'] ) ){
								$_flgCheck=true;
							}
						}
					break;
					case Project_Automation_Filter::$type['HAVE_EF']: // +
						$_efIdsList=explode(',',$_filter['value']);
						foreach( $_s8r['efunnel_events'] as $_event ){
							if( isset( $_event['ef_id'] ) && in_array( $_event['ef_id'], $_efIdsList ) ){
								$_flgCheck=true;
							}
						}
					break;
					case Project_Automation_Filter::$type['COMPLEAT_EF']: //+
						$_efIdsList=explode(',',$_filter['value']);
						foreach( $_efIdsList as $_efId ){
							foreach( $_s8r['efunnel_events'] as $_event ){
								if( isset( $_event['message_id'] ) && in_array( $_event['message_id'], $_arrEfForFilter[$_efId]['end_mess_id'] ) && isset( $_event['delivered'] ) && !empty( $_event['delivered'] ) ){
									$_flgCheck=true;
								}
							}
						}
					break;
					case Project_Automation_Filter::$type['PAUSE_EF']: //+
						$_efIdsList=explode(',',$_filter['value']);
						foreach( $_efIdsList as $_efId ){
							foreach( $_s8r['efunnel_events'] as $_event ){
								if( isset( $_event['ef_id'] ) && in_array( $_event['ef_id'], $_efId ) && isset( $_arrEfForFilter[$_efId] ) && $_arrEfForFilter[$_efId]['flg_pause'] ){
									$_flgCheck=true;
								}
							}
						}
					break;
				}
				if( $_flgCheck ){
					$_updateLogic=preg_replace( '/(^|\s|\(\s*)+('.$_filter['name'].')+($|\s|\)\s*)+/', '$1true$3', $_updateLogic );
				}else{
					$_updateLogic=preg_replace( '/(^|\s|\(\s*)+('.$_filter['name'].')+($|\s|\)\s*)+/', '$1false$3', $_updateLogic );
				}
			}
		}
		$_updateLogic=str_replace( array( 'AND', 'XOR', 'OR', 'NOT' ), array( '&&', 'xor', '||', '!' ), $_updateLogic );
		$_flgTest=false;
	//	eval('if( '.$_updateLogic.' ) $_flgTest=true;');
		eval('try{ if( '.$_updateLogic.' ) $_flgTest=true; }catch(Exception $e){var_dump( $e );}');
	//	p( $_updateLogic );
		
		return $_flgTest;
	}


	/*
	public static $type=array( //ability to add AND so we have multiple actions
		'ADD_TAG'=>1, //Add tag
		'PAUSE_EF'=>2, //Pause from Email Funnel
		'REMOVE_EF'=>3, //Remove from Email Funnel
		'RESUME_EF'=>4, //Resume Email Funnel
		'ADD_EF'=>5, //Add to Email Funnel
		'UPDATE_CONTACT'=>6, //Update Contact (we might have some fields to update)
		'SEND_TO_LC'=>7, //Send to Lead Channel (for example to ping a url via Zapier, to add to a webinar…)
		'PING_URL'=>8, //Ping URL (https://screencast.com/t/LsQYCFlM33jg)
	);
	*/
	protected function runActions($_actions, $_s8r, $_userId, $_actionId){
		if( !isset( $_actions ) || empty( $_actions ) || !isset( $_s8r ) || empty( $_s8r )  || !isset( $_userId ) || empty( $_userId ) ){
			return false;
		}
		$_arrUpdateData=$_addEfList=$_addLcList=array();
		foreach( $_actions as $_actionType=>$_action ){
			$_addActionList[]=array(
				'email' => $_s8r['email'],
				'auto_id'=>$_actionId
			);
			switch( $_actionType ){
				case Project_Automation_Action::$type['ADD_TAG']:
					$_afterRemove=Project_Tags::set( $_action['value'] );
					$_arrUpdateData[]='UPDATE s8rs_'.$_userId.' SET `tags`="'.Project_Tags::set( array_unique( array_merge( explode(',', $_action['value']), ( is_array( $_s8r['tags'] )?$_s8r['tags']:array() ) ) ) ).'" WHERE id="'.$_s8r['id'].'"';
					Project_Automation::setEvent( Project_Automation_Event::$type['CONTACT_TAGGED'] , array_unique( array_merge( explode(',', $_action['value']), ( is_array( $_s8r['tags'] )?$_s8r['tags']:array() ) ) ), $_s8r['email'], array('user_id'=>$_userId) );
				break;
				case Project_Automation_Action::$type['REMOVE_TAG']:
					$_afterRemove=is_array( $_s8r['tags'] )?$_s8r['tags']:array();
					$_afterRemove=Project_Tags::get( $_afterRemove );
					foreach( $_afterRemove as $_keyR=>$_remTag ){
						if( in_array( $_remTag, explode(',', $_action['value']) ) ){
							unset( $_afterRemove[$_keyR] );
						}
					}
					$_afterRemove=Project_Tags::set( $_afterRemove );
					$_arrUpdateData[]='UPDATE s8rs_'.$_userId.' SET `tags`="'.$_afterRemove.'" WHERE id="'.$_s8r['id'].'"';
					Project_Automation::setEvent( Project_Automation_Event::$type['REMOVE_TAG'] , $_action['value'], $_s8r['email'], array('user_id'=>$_userId) );
				break;
				case Project_Automation_Action::$type['PAUSE_EF']:
					$_efIdsList=explode(',', $_action['value']);
					$_eventsIds=array();
					foreach( $_s8r['efunnel_events'] as $_eventId=>$_event ){
						if( isset( $_event['ef_id'] ) && in_array( $_event['ef_id'], $_efIdsList ) ){
							$_eventsIds[]=$_eventId;
						}
					}
					$_arrUpdateData[]='UPDATE s8rs_parameters_'.$_userId.' SET `name`="pause_ef_id" WHERE name="ef_id" AND value IN ('.Core_Sql::fixInjection( $_efIdsList ).') AND event_id IN ('.Core_Sql::fixInjection( $_eventsIds ).')';
				break;
				case Project_Automation_Action::$type['REMOVE_EF']:
					$_efIdsList=explode(',', $_action['value']);
					$_eventsIds=array();
					foreach( $_s8r['efunnel_events'] as $_eventId=>$_event ){
						if( isset( $_event['ef_id'] ) && in_array( $_event['ef_id'], $_efIdsList ) ){
							$_eventsIds[]=$_eventId;
						}
					}
					$_arrUpdateData[]='UPDATE s8rs_parameters_'.$_userId.' SET `name`="remove_ef_id" WHERE name="ef_id" AND value IN ('.Core_Sql::fixInjection( $_efIdsList ).') AND event_id IN ('.Core_Sql::fixInjection( $_eventsIds ).')';
				break;
				case Project_Automation_Action::$type['RESUME_EF']:
					$_efIdsList=explode(',', $_action['value']);
					$_eventsIds=array();
					foreach( $_s8r['efunnel_events'] as $_eventId=>$_event ){
						if( isset( $_event['ef_id'] ) && in_array( $_event['ef_id'], $_efIdsList ) ){
							$_eventsIds[]=$_eventId;
						}
					}
					$_arrUpdateData[]='UPDATE s8rs_parameters_'.$_userId.' SET `name`="ef_id" WHERE name="pause_ef_id" AND value IN ('.Core_Sql::fixInjection( $_efIdsList ).') AND event_id IN ('.Core_Sql::fixInjection( $_eventsIds ).')';
				break;
				case Project_Automation_Action::$type['ADD_EF']:
					$_efIdsList=explode(',', $_action['value']);
					foreach( $_efIdsList as $_efId ){
						$_addEfList[]=array(
							'email' => $_s8r['email'],
							'ef_id' => $_efId,
						);
					}
					Project_Automation::setEvent( Project_Automation_Event::$type['CONTACT_ADDED_EF'] , $_action['value'], $_s8r['email'], array('user_id'=>$_userId) );
				break;
				case Project_Automation_Action::$type['UPDATE_CONTACT']:
					$_userData=unserialize( base64_decode( $_s8r['settings'] ) );
					if( !is_array( $_userData ) ){
						$_userData=array();
					}
					$_userData=base64_encode( serialize( array_merge( array_combine( $_action['settings']['name'], $_action['settings']['value'] ), $_userData ) ) );
					$_arrUpdateData[]='UPDATE s8rs_'.$_userId.' SET `settings`="'.$_userData.'" WHERE id="'.$_s8r['id'].'"';
				break;
				case Project_Automation_Action::$type['SEND_TO_LC']:
					$_lcIdsList=explode(',', $_action['value']);
					foreach( $_lcIdsList as $_efId ){
						$_addLcList[]=array(
							'email' => $_s8r['email'],
							'lead_id' => $_efId,
						);
					}
					Project_Automation::setEvent( Project_Automation_Event::$type['CONTACT_ADDED_LC'] , $_action['value'], $_s8r['email'], array('user_id'=>$_userId) );
				break;
				case Project_Automation_Action::$type['PING_URL']:
					if( !empty( $_action['settings']['url'] ) && !empty( $_action['settings']['post_data'] ) ){
						Core_Curl::async( $_action['settings']['url'], $_action['settings']['post_data'], 'POST' );
					}
				break;
			}
		}
		try{
			Core_Sql::setConnectToServer( 'lpb.tracker' );
			//========
			foreach( $_arrUpdateData as $_sqlUpdate ){
				Core_Sql::setExec($_sqlUpdate);
			}
			// теперь добавляем events ef массово
			$_addTime=$_efId=false;
			$_arrSend=$_arrEmails=$_arrValues=array();
			foreach( $_addEfList as $_send ){
				if( isset( $_send['email'] ) && !empty( $_send['email'] ) ){
					$_arrEmails[$_send['email']]=$_send['email'];
				}
			}
			if( !empty( $_arrEmails ) ){
				// возможно надо дописать чистку дублированных ef для одного email
				$_haveEfIds=Core_Sql::getAssoc( 'SELECT a.id as u_id, b.id as e_id, c.id as p_id, a.email, c.value FROM s8rs_'.$_userId.' a JOIN s8rs_events_'.$_userId.' AS b ON b.sub_id=a.id JOIN s8rs_parameters_'.$_userId.' AS c ON c.event_id=b.id WHERE a.email IN ('.Core_Sql::fixInjection( $_arrEmails ).') AND c.name="ef_id";' );
				foreach( $_haveEfIds as $_have ){
					$_removeKey=array();
					foreach( $_addEfList as $_rKey=>$_send ){
						if( isset( $_send['ef_id'] )
							&& $_send['ef_id']==$_have['value']
							&& $_send['email']==$_have['email']
						){
							// проверяем есть ли такая подписка уже
							$_removeKey[]=$_rKey;
						}
					}
					if(!empty( $_removeKey )){
						foreach( $_removeKey as $_rKey ){
							// убираем из списка если уже подписан
							unset( $_addEfList[$_rKey] );
						}
					}
				}
				// обновляем список после чистки
				$_arrEmails=array();
				if( !empty( $_addEfList ) ){
					foreach( $_addEfList as $_send ){
						if( isset( $_send['email'] ) && !empty( $_send['email'] ) ){
							$_arrEmails[$_send['email']]=$_send['email'];
						}
					}
					$_arrNewEmailsIds=Core_Sql::getKeyVal( 'SELECT d.email, d.id FROM s8rs_'.$_userId.' d WHERE email IN ('.Core_Sql::fixInjection( $_arrEmails ).')' );
					if( $_addTime === false ){
						$_addTime=time();
					}
					$_arrSend=array();
					foreach( $_addEfList as $_send ){
						if( isset( $_send['email'] ) && !empty( $_send['email'] ) 
							&& isset( $_arrNewEmailsIds[$_send['email']] ) && !empty( $_arrNewEmailsIds[$_send['email']] )
						){
							$_arrSend[]='("'.$_arrNewEmailsIds[$_send['email']].'","'.Project_Subscribers_Events::EMIAL_FUNNEL.'","'.$_addTime.'")';
						}
					}
					Core_Sql::setExec( 'INSERT INTO s8rs_events_'.$_userId.' (`sub_id`,`event_type`,`added`) VALUES '.implode( ',', $_arrSend ) );
					$_arrNewEventsIds=Core_Sql::getKeyVal( 'SELECT b.id, b.sub_id FROM s8rs_'.$_userId.' a JOIN s8rs_events_'.$_userId.' AS b ON b.sub_id=a.id WHERE b.added='.$_addTime );
					$_arrSend=array();
					foreach( $_arrNewEventsIds as $_evId=>$_subId ){
						foreach( $_addEfList as &$_send ){
							if( isset( $_send['ef_id'] ) && !empty( $_send['ef_id'] ) 
								&& $_subId == $_arrNewEmailsIds[$_send['email']]
							){
								$_arrSend[]='("'.$_evId.'","ef_id","'.$_send['ef_id'].'")';
								$_send['ef_id']=false; // этот ef id добавляется пользователю, исключаем повторный вызов
							}
						}
					}
					Core_Sql::setExec( 'INSERT INTO s8rs_parameters_'.$_userId.' (`event_id`,`name`,`value`) VALUES '.implode( ',', $_arrSend ) );	
				}
			}
			// теперь добавляем events lc массово
			$_addTime=$_efId=false;
			$_arrSend=$_arrEmails=$_arrValues=array();
			foreach( $_addLcList as $_send ){
				if( isset( $_send['email'] ) && !empty( $_send['email'] ) ){
					$_arrEmails[$_send['email']]=$_send['email'];
				}
			}
			if( !empty( $_arrEmails ) ){
				// возможно надо дописать чистку дублированных ef для одного email
				$_haveEfIds=Core_Sql::getAssoc( 'SELECT a.id as u_id, b.id as e_id, c.id as p_id, a.email, c.value FROM s8rs_'.$_userId.' a JOIN s8rs_events_'.$_userId.' AS b ON b.sub_id=a.id JOIN s8rs_parameters_'.$_userId.' AS c ON c.event_id=b.id WHERE a.email IN ('.Core_Sql::fixInjection( $_arrEmails ).') AND c.name="lead_id";' );
				foreach( $_haveEfIds as $_have ){
					$_removeKey=array();
					foreach( $_addLcList as $_rKey=>$_send ){
						if( isset( $_send['lead_id'] )
							&& $_send['lead_id']==$_have['value']
							&& $_send['email']==$_have['email']
						){
							// проверяем есть ли такая подписка уже
							$_removeKey[]=$_rKey;
						}
					}
					if(!empty( $_removeKey )){
						foreach( $_removeKey as $_rKey ){
							// убираем из списка если уже подписан
							unset( $_addLcList[$_rKey] );
						}
					}
				}
				// обновляем список после чистки
				$_arrEmails=array();
				if( !empty( $_addLcList ) ){
					foreach( $_addLcList as $_send ){
						if( isset( $_send['email'] ) && !empty( $_send['email'] ) ){
							$_arrEmails[$_send['email']]=$_send['email'];
						}
					}
					$_arrNewEmailsIds=Core_Sql::getKeyVal( 'SELECT d.email, d.id FROM s8rs_'.$_userId.' d WHERE email IN ('.Core_Sql::fixInjection( $_arrEmails ).')' );
					if( $_addTime === false ){
						$_addTime=time();
					}
					$_arrSend=array();
					foreach( $_addLcList as $_send ){
						if( isset( $_send['email'] ) && !empty( $_send['email'] ) 
							&& isset( $_arrNewEmailsIds[$_send['email']] ) && !empty( $_arrNewEmailsIds[$_send['email']] )
						){
							$_arrSend[]='("'.$_arrNewEmailsIds[$_send['email']].'","'.Project_Subscribers_Events::LEAD_FORM.'","'.$_addTime.'")';
						}
					}
					Core_Sql::setExec( 'INSERT INTO s8rs_events_'.$_userId.' (`sub_id`,`event_type`,`added`) VALUES '.implode( ',', $_arrSend ) );
					$_arrNewEventsIds=Core_Sql::getKeyVal( 'SELECT b.id, b.sub_id FROM s8rs_'.$_userId.' a JOIN s8rs_events_'.$_userId.' AS b ON b.sub_id=a.id WHERE b.added='.$_addTime );
					$_arrSend=array();
					foreach( $_arrNewEventsIds as $_evId=>$_subId ){
						foreach( $_addLcList as &$_send ){
							if( isset( $_send['lead_id'] ) && !empty( $_send['lead_id'] ) 
								&& $_subId == $_arrNewEmailsIds[$_send['email']]
							){
								$_arrSend[]='("'.$_evId.'","lead_id","'.$_send['lead_id'].'")';
								$_send['lead_id']=false; // этот ef id добавляется пользователю, исключаем повторный вызов
							}
						}
					}
					Core_Sql::setExec( 'INSERT INTO s8rs_parameters_'.$_userId.' (`event_id`,`name`,`value`) VALUES '.implode( ',', $_arrSend ) );	
				}
			}
			// теперь добавляем events actiion массово
			$_addTime=false;
			$_arrSend=$_arrEmails=$_arrValues=$_arrUnique=array();
			foreach( $_addActionList as $_action ){
				$_arrUnique[md5(serialize($_action))]=$_action;
			}
			$_addActionList=$_arrUnique;
			foreach( $_addActionList as $_send ){
				if( isset( $_send['email'] ) && !empty( $_send['email'] ) ){
					$_arrEmails[$_send['email']]=$_send['email'];
				}
			}
			if( !empty( $_arrEmails ) ){
				// возможно надо дописать чистку дублированных ef для одного email
				$_haveEfIds=Core_Sql::getAssoc( 'SELECT a.id as u_id, b.id as e_id, c.id as p_id, a.email, c.value FROM s8rs_'.$_userId.' a JOIN s8rs_events_'.$_userId.' AS b ON b.sub_id=a.id JOIN s8rs_parameters_'.$_userId.' AS c ON c.event_id=b.id WHERE a.email IN ('.Core_Sql::fixInjection( $_arrEmails ).') AND c.name="auto_id";' );
				foreach( $_haveEfIds as $_have ){
					$_removeKey=array();
					foreach( $_addActionList as $_rKey=>$_send ){
						if( isset( $_send['auto_id'] )
							&& $_send['auto_id']==$_have['value']
							&& $_send['email']==$_have['email']
						){
							// проверяем есть ли такая подписка уже
							$_removeKey[]=$_rKey;
						}
					}
					if(!empty( $_removeKey )){
						foreach( $_removeKey as $_rKey ){
							// убираем из списка если уже подписан
							unset( $_addActionList[$_rKey] );
						}
					}
				}
				// обновляем список после чистки
				$_arrEmails=array();
				if( !empty( $_addActionList ) ){
					foreach( $_addActionList as $_send ){
						if( isset( $_send['email'] ) && !empty( $_send['email'] ) ){
							$_arrEmails[$_send['email']]=$_send['email'];
						}
					}
					$_arrNewEmailsIds=Core_Sql::getKeyVal( 'SELECT d.email, d.id FROM s8rs_'.$_userId.' d WHERE email IN ('.Core_Sql::fixInjection( $_arrEmails ).')' );
					if( $_addTime === false ){
						$_addTime=time();
					}
					$_arrSend=array();
					foreach( $_addActionList as $_send ){
						if( isset( $_send['email'] ) && !empty( $_send['email'] ) 
							&& isset( $_arrNewEmailsIds[$_send['email']] ) && !empty( $_arrNewEmailsIds[$_send['email']] )
						){
							$_arrSend[]='("'.$_arrNewEmailsIds[$_send['email']].'","'.Project_Subscribers_Events::AUTOMATION.'","'.$_addTime.'")';
						}
					}
					Core_Sql::setExec( 'INSERT INTO s8rs_events_'.$_userId.' (`sub_id`,`event_type`,`added`) VALUES '.implode( ',', $_arrSend ) );
					$_arrNewEventsIds=Core_Sql::getKeyVal( 'SELECT b.id, b.sub_id FROM s8rs_'.$_userId.' a JOIN s8rs_events_'.$_userId.' AS b ON b.sub_id=a.id WHERE b.added='.$_addTime );
					$_arrSend=array();
					foreach( $_arrNewEventsIds as $_evId=>$_subId ){
						foreach( $_addActionList as &$_send ){
							if( isset( $_send['auto_id'] ) && !empty( $_send['auto_id'] ) 
								&& $_subId == $_arrNewEmailsIds[$_send['email']]
							){
								$_arrSend[]='("'.$_evId.'","auto_id","'.$_send['auto_id'].'")';
								$_send['auto_id']=false; // этот ef id добавляется пользователю, исключаем повторный вызов
							}
						}
					}
					Core_Sql::setExec( 'INSERT INTO s8rs_parameters_'.$_userId.' (`event_id`,`name`,`value`) VALUES '.implode( ',', $_arrSend ) );	
				}
			}
			//========
			Core_Sql::renewalConnectFromCashe();
		}catch(Exception $e){
			Core_Sql::renewalConnectFromCashe();
		}
	}

	protected function beforeSet() {
		$this->_data->setFilter( array( 'clear' ) );
		$this->_data->setElements(array(
			'settings'=>base64_encode( serialize( $this->_data->filtered['settings'] ) ),
		));
		return true;
	}
	
	protected function afterSet() {
		$this->_data->filtered['settings']=unserialize( base64_decode( $this->_data->filtered['settings'] ) );
		$_id=$this->_data->filtered['id'];
		$_arrEvents=array();
		foreach( $this->_data->filtered['events'] as $_type=>$_arrData ){
			if( empty( $_arrData['value'] ) ){
				continue;
			}
			$_arrEvents[]=array(
				'auto_id'=>$_id,
				'user_id'=>Core_Users::$info['id'],
				'event_type'=>$_type,
				'event_values'=>$_arrData['value'],
				'settings'=>$_arrData['settings']
			);
		}
		$_objE=new Project_Automation_Event();
		$_objE->withAutoId( $_id)->del();
		$_objE->setEntered( $_arrEvents )->setMass();
		$_arrFilters=array();
		foreach( $this->_data->filtered['filters'] as $_arrType ){
			foreach( $_arrType as $_type=>$_arrData ){
				if( empty( $_arrData['value'] ) ){
					continue;
				}
				$_arrFilters[]=array(
					'auto_id'=>$_id,
					'name'=>$_arrData['name'],
					'filter_type'=>$_type,
					'filter_values'=>$_arrData['value'],
					'settings'=>$_arrData['settings']
				);
			}
		}
		$_objF=new Project_Automation_Filter();
		$_objF->withAutoId( $_id)->del();
		$_objF->setEntered( $_arrFilters )->setMass();
		$_arrActions=array();
		foreach( $this->_data->filtered['actions'] as $_type=>$_arrData ){
			if( empty( $_arrData['value'] ) ){
				continue;
			}
			$_arrActions[]=array(
				'auto_id'=>$_id,
				'action_type'=>$_type,
				'action_values'=>$_arrData['value'],
				'settings'=>$_arrData['settings']
			);
		}
		$_objA=new Project_Automation_Action();
		$_objA->withAutoId( $_id)->del();
		$_objA->setEntered( $_arrActions )->setMass();
		return true;
	}
	
	public function getList( &$mixRes ){
		parent::getList( $mixRes );
		if( !empty( $mixRes ) ){
			if( isset( $mixRes['id'] ) ){
				$mixRes['settings']=unserialize(base64_decode($mixRes['settings']));
				$_objE=new Project_Automation_Event();
				$_objE->withAutoId( $mixRes['id'] )->getList( $mixRes['events'] );
				$_objF=new Project_Automation_Filter();
				$_objF->withAutoId( $mixRes['id'] )->getList( $mixRes['filters'] );
				$_objA=new Project_Automation_Action();
				$_objA->withAutoId( $mixRes['id'] )->getList( $mixRes['actions'] );
			}else{
				$_arrEvents=$_arrFilters=$_arrActions=$_arIds=array();
				foreach( $mixRes as &$_res ){
					$_arIds[]=$_res['id'];
					$_res['settings']=unserialize(base64_decode($_res['settings']));
				}
				$_objF=new Project_Automation_Event();
				$_objF->withAutoId( $_arIds )->getList( $_arrEvents );
				$_objF=new Project_Automation_Filter();
				$_objF->withAutoId( $_arIds )->getList( $_arrFilters );
				$_objA=new Project_Automation_Action();
				$_objA->withAutoId( $_arIds )->getList( $_arrActions );
				foreach( $mixRes as &$_res ){
					foreach( $_arrEvents as $_event ){
						if( $_event['auto_id'] == $_res['id'] ){
							$_res['events'][]=array(
								'value'=>$_event['event_values'],
								'settings'=>$_event['settings'],
							);
						}
					}
					foreach( $_arrFilters as $_filter ){
						if( $_filter['auto_id'] == $_res['id'] ){
							$_res['filters'][$_filter['filter_type']]=array(
								$_filter['id']=>array(
									'name'=>$_filter['name'],
									'value'=>$_filter['filter_values'],
									'settings'=>$_filter['settings'],
								)
							);
						}
					}
					foreach( $_arrActions as $_action ){
						if( $_action['auto_id'] == $_res['id'] ){
							$_res['actions'][$_action['action_type']]=array(
								'value'=>$_action['action_values'],
								'settings'=>$_action['settings'],
							);
						}
					}
				}
			}
		}
		return $this;
	}
	
	public function del(){
		if ( empty( $this->_withIds ) ){
			$_bool=false;
		} else {
			Core_Sql::setExec( 'DELETE FROM '.$this->_table.' WHERE id IN('.Core_Sql::fixInjection( $this->_withIds ).')'.($this->_onlyOwner&&$this->getOwnerId( $_intId )? ' AND user_id='.$_intId:'') );
			$_objE=new Project_Automation_Event();
			$_objE->withAutoId( $this->_withIds )->del();
			$_objF=new Project_Automation_Filter();
			$_objF->withAutoId( $this->_withIds )->del();
			$_objA=new Project_Automation_Action();
			$_objA->withAutoId( $this->_withIds )->del();
			$_bool=true;
		}
		$this->init();
		return $_bool;
	}
}
?>