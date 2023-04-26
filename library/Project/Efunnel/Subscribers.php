<?php
class Project_Efunnel_Subscribers extends Core_Data_Storage{
	protected $_withEmail=array();
	protected $_withoutMessages=false;
	protected $_withSmtpId=false;
	protected $_withLead=false;
	protected $_withTags=false;
	protected $_withoutTags=false;
	protected $_withStatus=false;
	protected $_withTagsHeat=false;
	protected $_noEvents=false;
	protected $_withStatusMessage=false;
	protected $_withoutEfunnelIs=false;
	protected $_withValidation=false;
	protected $_getRandom2k=false;
	protected $_arrTagsHeat=false;
	protected $_withoutFlgGlobalUnsubscribe=false;
	protected $_onlyFlgGlobalUnsubscribe=false;
	const TIME_TODAY=1,TIME_YESTERDAY=2,TIME_LAST_7_DAYS=3,TIME_THIS_MONTH=4,TIME_LAST_MONTH=5,TIME_ALL=7, TIME_MINUTE=8, TIME_CUSTOM=9;
	protected $_userId=false;

	public function __construct( $_uid=false ){
		if( $_uid !== false ){
			$this->_userId=$_uid;
		}
	}
	
	public function setEmailSettings( $_emailId, $_defaultValues, $_settings=array() ){
		try {
			Core_Sql::setConnectToServer( 'lpb.tracker' );
			//========
			Core_Sql::setExec( 'UPDATE s8rs_'.Core_Users::$info['id'].' SET name='.Core_Sql::fixInjection( $_defaultValues['name'] ).', email='.Core_Sql::fixInjection( $_defaultValues['email'] ).', settings="'.base64_encode( serialize( $_settings ) ).'" WHERE id IN ('.Core_Sql::fixInjection( $_emailId ).')' );
			//========
			Core_Sql::renewalConnectFromCashe();
		} catch(Exception $e) {
			Core_Sql::renewalConnectFromCashe();
			return false;
		}
		return false;
	}
	
	public function cronResendMessage( $_settings=array() ){
		if( !isset( $_settings['page'] ) ){
			return false;
		}
		$_model=new Project_Efunnel_Subscribers( Core_Users::$info['id'] );
		if( isset( $_settings['arrData']['withTags'] ) ){
			$_model->withTags( $_settings['arrData']['withTags'] );
		}
		if( !empty( $_settings['arrData']['withEF'] ) ){
			$_model->withEfunnelIds( $_settings['arrData']['withEF'] );
		}	
		if( !empty( $_settings['arrData']['withStatus'] ) ){
			$_model->withStatusMessage( $_settings['arrData']['withStatus'] );
		}
		$_model
			->withPaging( array(
				'url'=>array( 'page'=>$_settings['page'] ),
				'reconpage'=>1000
			) )
			->noEvents()
			->onlyValid();
		if( isset( $_settings['arrData']['update_all'] ) && $_settings['arrData']['update_all']=='1' ){
			$_model
				->getList( $_arrSubscribers );
		}else{
			$_model
				->withIds( $_settings['arrData']['subscribers'] )
				->getList( $_arrSubscribers );
		}
		if( count( $_arrSubscribers ) == 0 ){
			return false;
		}
		$_allTags=array();
		foreach($_arrSubscribers as &$item){
			if( isset( $_settings['arrData']['email_funnels'] ) && !empty( $_settings['arrData']['email_funnels'] ) ){
				$item['ef_id'] = $_settings['arrData']['email_funnels'];
			}
			if( isset( $_settings['arrData']['send'] ) && $_settings['arrData']['send']==1 && !empty( $_settings['arrData']['start'] ) ){
				$item['start'] = $_settings['arrData']['start']+date("O")*60*60+$_settings['arrData']['timezone']; // расчитываем рассылку по времени сервера, исходя из часовового пояса клиента
			}
			if( isset( $item['tags'] ) && is_array( $item['tags'] ) ){
				$_allTags=array_merge( $_allTags, $item['tags'] );
			}
			unSet( $item['settings'] );
			unSet( $item['id'] );
		}
		unset( $item );
		$_allTags=array_unique( $_allTags );
		foreach( $_allTags as $_tkey=>$_tag ){
			if( ctype_digit( $_tag ) ){
				unset( $_allTags[$_tkey] );
			}
		}
		$_arrNewTags=array();
		if( isset( $_settings['arrData']['tags'] ) && !empty( $_settings['arrData']['tags'] ) ){
			foreach( explode( ',', $_settings['arrData']['tags'] ) as $_strNewTagName ){
				$_arrNewTags[]=$_allTags[]=trim( $_strNewTagName );
			}
		}
		$_allTags=array_unique( $_allTags );
		$_allTagsIds=Project_Tags::set( $_allTags );
		$_allTags=array_flip( Project_Tags::get( $_allTagsIds ) );
		foreach( $_arrSubscribers as &$item ){
			if( isset( $item['tags'] ) && is_array( $item['tags'] ) ){
				foreach( $item['tags'] as $_tkey=>$_tag ){
					if( ctype_digit( $_tag ) ){
						unset( $item['tags'][$_tkey] ); // id убираем, т.к. их не расшифйровало на предыдущем этапе, значит таких тэгов нет
					}else{
						$item['tags'][$_tkey]=$_allTags[$_tag]; // буквы заменяем на id, для сохранения
					}
				}
				foreach( $_arrNewTags as $_addNewTag ){
					$item['tags'][]=$_allTags[$_addNewTag]; // добавляем id новых тэгов
				}
				if( !empty( $item['tags'] ) ){
					$item['tags']=','.implode( ',', array_unique( $item['tags'] ) ).',';
				}else{
					$item['tags']='';
				}
			}
		}
		unset( $item );
		if( $_model->setEntered( $_arrSubscribers )->setMass() ){
			return true;
		}
		return false;
	}

	public $cronErrorLog=array();

	public function cronUpdateContacts( $_settings=array() ){
		if( !isset( $_settings['page'] ) ){
			$this->cronErrorLog[]='Empty page data';
			return false;
		}
		$_model=new Project_Efunnel_Subscribers( Core_Users::$info['id'] );
		if( !empty( $_settings['search'] ) ){
			$_model->withTags( $_settings['search'] );
		}
		if( !empty( $_settings['email'] ) ){
			$_model->withEmail( $_settings['email'] );
		}
		if( !empty( $_settings['arrFilter']['email_funnels'] ) ){
			if( $_settings['arrFilter']['email_funnels'] == 'ns' ){
				$_funnel=new Project_Efunnel();
				$_funnel->onlyOwner()->onlyIds()->getList( $_EFids );
				$_model->withoutEfunnelIs($_EFids);
			}else{
				$_model->withEfunnelIds( $_settings['arrFilter']['email_funnels'] );
			}
		}
		if( !empty( $_settings['arrFilter']['lead_channels'] ) ){
			$_model->withLead( $_settings['arrFilter']['lead_channels'] );
		}	
		if( !empty( $_settings['arrFilter']['status'] ) && $_settings['arrFilter']['status'] != 'unsubscribe' ){
			$_model->withStatusMessage( $_settings['arrFilter']['status'] );
		}
		if( !empty( $_settings['arrFilter']['status'] ) && $_settings['arrFilter']['status'] == 'unsubscribe' ){
			$_model->onlyFlgGlobalUnsubscribe();
		}else{
			$_model->withoutFlgGlobalUnsubscribe();
		}
		if( !empty( $_settings['arrFilter']['tags'] ) ){
			$_model->withTags( $_settings['arrFilter']['tags'] );
		}
		if( !empty( $_settings['arrFilter']['validation'] ) ){
			$_model->withValidation( $_settings['arrFilter']['validation'] );
		}
		if( !empty( $_settings['arrFilter']['time'] ) ){
			$_model->withTime( $_settings['arrFilter']['time'], $_settings['arrFilter']['time_start'], $_settings['arrFilter']['time_end'] );
		}
		if( !empty( $_settings['arrData']['withTags'] ) ){
			$_model->withTags( $_settings['arrData']['withTags'] );
		}
		if( !empty( $_settings['arrData']['withEF'] ) ){
			$_model->withEfunnelIds( $_settings['arrData']['withEF'] );
		}	
		if( !empty( $_settings['arrData']['withStatus'] ) ){
			$_model->withStatusMessage( $_settings['arrData']['withStatus'] );
		}
		$_model
			->withPaging( array(
				'url'=>array( 'page'=>$_settings['page'] ),
				'reconpage'=>1000
			) )
			->noEvents()
			->onlyValid()
			->withTagsHeat();
		if( isset( $_settings['arrData']['update_all'] ) && $_settings['arrData']['update_all']=='1' ){
			$_model
				->getList( $_arrSubscribers );
		}else{
			$_model
				->withIds( $_settings['arrData']['subscribers'] )
				->getList( $_arrSubscribers );
		}
		if( count( $_arrSubscribers ) == 0 ){
			$this->cronErrorLog[]='No Subscribers Found';
			return false;
		}
		$_arrNewTag=$_allTags=array();
		if( isset( $_settings['arrData']['email_funnels'] ) && !empty( $_settings['arrData']['email_funnels'] ) ){
			$_funnel=new Project_Efunnel();
			$_funnel->onlyOne()->withIds( $_settings['arrData']['email_funnels'] )->getList( $_arrEFunnel );
			if( isset( $_arrEFunnel['options']['tags'] ) && !empty( $_arrEFunnel['options']['tags'] ) ){
				$_arrNewTag=explode( ',', $_arrEFunnel['options']['tags'] );
			}
		}
		if( isset( $_settings['arrData']['tags'] ) && !empty( $_settings['arrData']['tags'] ) ){
			foreach( explode( ',', $_settings['arrData']['tags'] ) as $_strNewTagName ){
				$_arrNewTag[]=trim( $_strNewTagName );
			}
		}
		array_unique( $_arrNewTag );
		foreach($_arrSubscribers as &$item){
			foreach( $item['tags'] as &$_tagV ){
				$_tagV=trim( $_tagV );
			}
			$item['tags']=array_unique( $item['tags'] );
			if( isset( $_settings['arrData']['email_funnels'] ) && !empty( $_settings['arrData']['email_funnels'] ) ){
				$item['ef_id'] = $_settings['arrData']['email_funnels'];
			}
			if( isset( $_settings['arrData']['send'] ) && $_settings['arrData']['send']==1 && !empty( $_settings['arrData']['start'] ) ){
				$item['start'] = $_settings['arrData']['start']+date("O")*60*60+$_settings['arrData']['timezone']; // расчитываем рассылку по времени сервера, исходя из часовового пояса клиента
			}
			if( isset( $item['tags'] ) && is_array( $item['tags'] ) ){
				$_allTags=array_merge( $_allTags, $item['tags'], $_arrNewTag );
			}else{
				$_allTags=array_merge( $_allTags, $_arrNewTag );
			}
			$item['tags']=array_unique( array_merge( $item['tags'], $_arrNewTag ) );
			unSet( $item['settings'] );
			unSet( $item['id'] );
		}
		unset( $item );
		$_allTags=array_unique( $_allTags );
	//	foreach( $_allTags as $_tkey=>$_tag ){
	//		if( ctype_digit( $_tag ) ){
	//			unset( $_allTags[$_tkey] );
	//		}
	//	}
	//	$_allTags=array_unique( $_allTags );
		$_allTagsIds=Project_Tags::set( $_allTags );
		$_removeTagsIds=array();
		if( isset( $_settings['arrData']['tags_remove'] ) && !empty( $_settings['arrData']['tags_remove'] ) ){
			$_removeTagsIds=Project_Tags::set( $_settings['arrData']['tags_remove'] );
		}
		$_allTags=array_flip( Project_Tags::get( $_allTagsIds ) );
		foreach( $_arrSubscribers as &$item ){
			if( isset( $item['tags'] ) && is_array( $item['tags'] ) ){
				foreach( $item['tags'] as $_tkey=>$_tag ){
					$flgRemoved=false;
					if( isset( $_settings['arrData']['tags_remove'] ) && !empty( $_settings['arrData']['tags_remove'] ) ){
						foreach( explode( ',', $_settings['arrData']['tags_remove'] ) as $_strTagName ){
							if( trim( $_strTagName ) == $_tag ){
								Project_Automation::setEvent( Project_Automation_Event::$type['REMOVE_TAG'] , $_tag, $item['email'], array('user_id'=>Core_Users::$info['id']) );
								unset( $item['tags'][$_tkey] );
								$flgRemoved=true;
							}
						}
					}
					if( !$flgRemoved ){
						if( ctype_digit( $_tag ) ){
							unset( $item['tags'][$_tkey] ); // id убираем, т.к. их не расшифйровало на предыдущем этапе, значит таких тэгов нет
						}else{
							$item['tags'][$_tkey]=$_allTags[$_tag]; // буквы заменяем на id, для сохранения
						}
					}
				}
				if( !empty( $item['tags'] ) ){
					$item['tags']=','.implode(',', array_filter( array_unique( $item['tags'] ) ) ).',';
				}else{
					$item['tags']=',,';
				}
			}
		}
		unset( $item );
		if( $_model->setEntered( $_arrSubscribers )->setMass() ){
			return true;
		}
		$this->cronErrorLog[]='Set Mass not work';
		return false;
	}
	
	public function cronDeleteContacts( $_settings=array() ){
		if( !isset( $_settings['page'] ) ){
			return false;
		}
		$_model=new Project_Efunnel_Subscribers( Core_Users::$info['id'] );
		if( !empty( $_settings['search'] ) ){
			$_model->withTags( $_settings['search'] );
		}
		if( !empty( $_settings['email'] ) ){
			$_model->withEmail( $_settings['email'] );
		}
		if( !empty( $_settings['arrFilter']['email_funnels'] ) ){
			if( $_settings['arrFilter']['email_funnels'] == 'ns' ){
				$_funnel=new Project_Efunnel();
				$_funnel->onlyOwner()->onlyIds()->getList( $_EFids );
				$_model->withoutEfunnelIs($_EFids);
			}else{
				$_model->withEfunnelIds( $_settings['arrFilter']['email_funnels'] );
			}
		}
		if( !empty( $_settings['arrFilter']['lead_channels'] ) ){
			$_model->withLead( $_settings['arrFilter']['lead_channels'] );
		}	
		if( !empty( $_settings['arrFilter']['status'] ) ){
			$_model->withStatusMessage( $_settings['arrFilter']['status'] );
		}
		if( !empty( $_settings['arrFilter']['tags'] ) ){
			$_model->withTags( $_settings['arrFilter']['tags'] );
		}
		if( !empty( $_settings['arrFilter']['validation'] ) ){
			$_model->withValidation( $_settings['arrFilter']['validation'] );
		}
		if( !empty( $_settings['arrFilter']['time'] ) ){
			$_model->withTime( $_settings['arrFilter']['time'], $_settings['arrFilter']['time_start'], $_settings['arrFilter']['time_end'] );
		}
		if( !empty( $_settings['arrData']['withTags'] ) ){
			$_model->withTags( $_settings['arrData']['withTags'] );
		}
		if( !empty( $_settings['arrData']['withEF'] ) ){
			$_model->withEfunnelIds( $_settings['arrData']['withEF'] );
		}	
		if( !empty( $_settings['arrData']['withStatus'] ) ){
			$_model->withStatusMessage( $_settings['arrData']['withStatus'] );
		}
		/*
		if( isset( $_settings['arrFilter']['tags'] ) ){
			$_model->withTags( $_settings['arrFilter']['tags'] );
		}
		if( !empty( $_settings['arrFilter']['email_funnels'] ) ){
			if( $_settings['arrFilter']['email_funnels'] == 'ns' ){
				$_model->withoutEfunnelIs();
			}else{
				$_model->withEfunnelIds( $_settings['arrFilter']['email_funnels'] );
			}
		}	
		if( !empty( $_settings['arrFilter']['status'] ) ){
			$_model->withStatusMessage( $_settings['arrFilter']['status'] );
		}
		if( !empty( $_settings['arrFilter']['validation'] ) ){
			$_model->withValidation( $_settings['arrFilter']['validation'] );
		}
		*/
		$_model
			->withPaging( array(
				'url'=>array( 'page'=>1 ), // всегда запрашивать на удаление только 1 страницу
				'reconpage'=>1000
			) )
			->noEvents();
		if( isset( $_settings['arrData']['update_all'] ) && $_settings['arrData']['update_all']=='1' ){
			$_model
				->getList( $_arrSubscribers );
		}else{
			$_model
				->withIds( $_settings['arrData']['subscribers'] )
				->getList( $_arrSubscribers );
		}
		if( count( $_arrSubscribers ) == 0 ){
			return false;
		}
		foreach($_arrSubscribers as $item){
			$_s8rIds[]=$item['id'];
		}
		
			$_writer=new Zend_Log_Writer_Stream( Zend_Registry::get('config')->path->absolute->logfiles.'EF_Contacts_Remove.log' );
			$_writer->setFormatter( new Zend_Log_Formatter_Simple("%timestamp% %priorityName% (%priority%): %message%\r\n") );
			$_logger=new Zend_Log( $_writer );
			$_logger->info('-------------Project_Efunnel_Subscribers 1---------------');
			$_logger->info(serialize($_SERVER));
			$_logger->info('DELETE FROM s8rs_'.Core_Users::$info['id'].' WHERE id IN ('.Core_Sql::fixInjection( $_s8rIds ).')');
			$_logger->info('-------------Project_Efunnel_Subscribers 1---------------');
			
		try {
			Core_Sql::setConnectToServer( 'lpb.tracker' );
			//========
			Core_Sql::setExec( 'DELETE FROM s8rs_'.Core_Users::$info['id'].' WHERE id IN ('.Core_Sql::fixInjection( $_s8rIds ).')' );
			//========
			Core_Sql::renewalConnectFromCashe();
		} catch(Exception $e) {
			Core_Sql::renewalConnectFromCashe();
			return false;
		}
		return true;
	}
	
	public function withStatus(){
		$this->_withStatus=true;
		return $this;
	}
	
	public function withTagsHeat(){
		$this->_withTagsHeat=true;
		$this->_arrTagsHeat=array();
		return $this;
	}
	
	public function noEvents(){
		$this->_noEvents=true;
		return $this;
	}
	
	public function withLead( $_arrIds=array() ){
		$this->_withLead=$_arrIds;
		return $this;
	}
	
	public function withoutEfunnelIs( $_arrIds=array() ){
		$this->_withoutEfunnelIs=$_arrIds;
		return $this;
	}
	
	public function withEfunnelIds( $_arrIds=array() ){
		$this->_withEfunnelIds=$_arrIds;
		return $this;
	}
	
	public function withEmail( $_arrIds=array() ){
		$this->_withEmail=$_arrIds;
		return $this;
	}
	
	public function withSmtpId( $_varIds ){
		$this->_withSmtpId = $_varIds;
		return $this;
	}
	
	public function withoutMessages(){
		$this->_withoutMessages=true;
		return $this;
	}

	public function withTags( $_tags ){
		$this->_withTags = array_filter( explode( ',', trim( Project_Tags::check( $_tags ), ',' ) ) );
		return $this;
	}

	public function withoutTags( $_tags ){
		$this->_withoutTags=true;
		return $this;
	}

	public function getRandom2k(){
		$this->_getRandom2k=true;
		return $this;
	}

	public function onlyValid(){
		$this->_onlyValid=true;
		return $this;
	}

	public function withStatusMessage( $status ){
		if( !empty( $status ) ){
			$this->_withStatusMessage=$status;			
		}
		return $this;
	}

	public function withValidation( $status ){
		if( !empty( $status ) ){
			$this->_withValidation=$status;			
		}
		return $this;
	}

	public function withTime( $_type, $_start=false, $_end=false ){
		$this->_withTime=$_type;
		if( $_start !== false && $_end !== false && $_type==self::TIME_CUSTOM ){
			$this->_withTimeStart=$_start;
			$this->_withTimeEnd=$_end;
		}
		return $this;
	}
	
	public function withoutFlgGlobalUnsubscribe(){
		$this->_withoutFlgGlobalUnsubscribe=true;
		return $this;
	}

	public function onlyFlgGlobalUnsubscribe(){
		$this->_onlyFlgGlobalUnsubscribe=true;
		return $this;
	}

	protected function init(){
		$this->_withEfunnelIds=array();
		$this->_withLead=array();
		$this->_withTime=false;
		$this->_withTimeStart=false;
		$this->_withTimeEnd=false;
		$this->_withEmail=false;
		$this->_withoutMessages=false;
		$this->_withSmtpId=false;
		$this->_withTags=false;
		$this->_withoutTags=false;
		$this->_withStatus=false;
		$this->_withTagsHeat=false;
		$this->_getRandom2k=false;
		$this->_onlyValid=false;
		$this->_withValidation=false;
		$this->_noEvents=false;
		$this->_withoutEfunnelIs=false;
		$this->_withoutFlgGlobalUnsubscribe=false;
		$this->_onlyFlgGlobalUnsubscribe=false;
	}

	private static $_code="ABCDEFGHIJKLMNOPQRSTUVWXYZ|abcdefghijklmnopqrstuvwxyz0123456789_.-~!*'();:@&=+$,/?#[]";
	private static $_decode="LfopqrsAlW8PQ:@&cXYZa]gSD67dEuv,/?#TUbm9_.-~!*'();+$=R5eBMw|xyz012FGHIJKt34hijkC[VNOn";
	
	/**
	Только для кодирования ссылок почтовиков
	 */
	public static function encode( $_array ){
		$_codeStr='';
		if( isset( $_array['smtpid'] ) && !empty( $_array['smtpid'] ) ){
			$_codeStr.=$_array['smtpid'].'|';
		}
		if( isset( $_array['email'] ) && !empty( $_array['email'] ) ){
			$_codeStr.=$_array['email'].'|';
		}
		if( isset( $_array['user_id'] ) && !empty( $_array['user_id'] ) ){
			$_codeStr.=$_array['user_id'].'|';
		}
		if( isset( $_array['event'] ) && !empty( $_array['event'] ) ){
			$_codeStr.=$_array['event'][0].'|';
		}
		if( isset( $_array['link'] ) && !empty( $_array['link'] ) ){
			$_codeStr.=$_array['link'];
		}
		if( strlen( base64_encode( $_codeStr ) ) > 250 ){
			return Project_Coder::encode( $_codeStr );
		}else{
			$_codedStr='';
			for( $i=0; $i<strlen( $_codeStr ); $i++ ){
				$_codedStr.=self::$_decode[ strpos( self::$_code, $_codeStr[$i] ) ];
			}
			if( strlen( base64_encode( $_codedStr ) ) == 32 ){
				return Project_Coder::encode( $_codeStr );
			}
			return str_replace( '/', '^', base64_encode( $_codedStr ) );
		}
	}

	/**
	 * Decode str to array
	 * @static
	 * @param $_str
	 * @return array
	 */
	public static function decode( $_str ) {
		if( strlen( $_str ) == 32 ){
			$_parse=Project_Coder::decode( $_str );
		}else{
			$_codedStr=base64_decode( str_replace( '^', '/', $_str ) );
			$_decodedStr='';
			for( $i=0; $i<strlen( $_codedStr ); $i++ ){
				$_decodedStr.=self::$_code[ strpos( self::$_decode, $_codedStr[$i] ) ];
			}
			$_parse=$_decodedStr;
		}
		$_parse=explode( '|', $_parse );
		$_return=array();
		if( isset( $_parse[0] ) ){
			$_return['smtpid']=$_parse[0];
		}
		if( isset( $_parse[1] ) ){
			$_return['email']=$_parse[1];
		}
		if( isset( $_parse[2] ) ){
			$_return['user_id']=$_parse[2];
		}
		if( isset( $_parse[3] ) ){
			$_return['event']=( $_parse[3] == 'c' ? 'click' : 'open' );
		}
		if( isset( $_parse[4] ) ){
			$_return['link']=$_parse[4];
			// remove bug
			if( $_return['link']=='https://www.crowdcast.io/e/going-from-zero-to-on' ){
				$_return['link']='https://www.crowdcast.io/e/going-from-zero-to-one';
			}
			//
		}
		return $_return;
	}
	
	public function set() {
		$this->_data->setFilter( array( 'clear' ) );
		$_s8rObj=new Project_Subscribers( $this->_userId );
		// не забыть обработать тэги
		if( isset( $this->_data->filtered['tags'] ) && !empty( $this->_data->filtered['tags'] ) ){
			if( !is_array( $this->_data->filtered['tags'] ) && strpos( $this->_data->filtered['tags'], ',' ) !== false ){
				$this->_data->filtered['tags']=explode( ',', $this->_data->filtered['tags'] );
			}
			if( !is_array( $this->_data->filtered['tags'] ) && !empty( $this->_data->filtered['tags'] ) ){
				$this->_data->filtered['tags']=array( $this->_data->filtered['tags'] );
			}
			if(Core_Acs::haveAccess( array( 'Automate' ) )){
				Project_Automation::setEvent( Project_Automation_Event::$type['CONTACT_TAGGED'] , $this->_data->filtered['tags'], $this->_data->filtered['email'], array() );
			}
			$_s8rObj->withEmails( $this->_data->filtered['email'] )->onlyOne()->getList( $_arrSubscriber );
			$this->_data->filtered['tags']=array_merge( ( is_array( $this->_data->filtered['tags'] )?$this->_data->filtered['tags']:array() ), ( is_array( $_arrSubscriber['tags'] )?$_arrSubscriber['tags']:array() ) );
			$this->_data->setElement( 'tags', Project_Tags::set( $this->_data->filtered['tags'] ) );
			$this->_data->setFilter( array( 'clear' ) );
		}
		$_s8rObj->setEntered( $this->_data->filtered )->set();
		$_s8rObj->getEntered( $this->_data->filtered );
		
		
		
		if(Core_Acs::haveAccess( array( 'Automate' ) )){
			Project_Automation::setEvent( Project_Automation_Event::$type['CONTACT_CREATED'] , 1, $this->_data->filtered['email'], array() );
			Project_Automation::setEvent( Project_Automation_Event::$type['CONTACT_ADDED_EF'] , $this->_data->filtered['sender_id'], $this->_data->filtered['email'], array() );
		}
		$_eventObj=new Project_Subscribers_Events( $this->_userId );
		$_arrEvent=array(
			'sub_id'=>$this->_data->filtered['id'],
			'event_type'=>Project_Subscribers_Events::EMIAL_FUNNEL,
			'added'=>$this->_data->filtered['added'],
			'param'=>array(
				'ef_id' => $this->_data->filtered['ef_id']
			),
		);
		if( isset( $this->_data->filtered['sender_id'] ) ){
			$_arrEvent['param']['ef_id']=$this->_data->filtered['sender_id'];
		}
		if( isset( $this->_data->filtered['message_id'] ) ){
			$_arrEvent['param']['message_id']=$this->_data->filtered['message_id'];
		}
		if( isset( $this->_data->filtered['smtp'] ) ){
			$_arrEvent['param']['smtp']=$this->_data->filtered['smtp'];
		}
		if( isset( $this->_data->filtered['smtpid'] ) ){
			$_arrEvent['param']['smtpid']=$this->_data->filtered['smtpid'];
		}
		if( isset( $this->_data->filtered['subject'] ) ){
			$_arrEvent['param']['subject']=$this->_data->filtered['subject'];
		}
		if( isset( $this->_data->filtered['smtp_message_id'] ) ){
			$_arrEvent['param']['smtp_message_id']=$this->_data->filtered['smtp_message_id'];
		}
		if( isset( $this->_data->filtered['delivered'] ) ){
			$_arrEvent['param']['delivered']=$this->_data->filtered['delivered'];
		}
		if( isset( $this->_data->filtered['bounced'] ) ){
			$_arrEvent['param']['bounced']=$this->_data->filtered['bounced'];
		}
		if( isset( $this->_data->filtered['spam'] ) ){
			$_arrEvent['param']['spam']=$this->_data->filtered['spam'];
		}
		if( isset( $this->_data->filtered['opened'] ) ){
			$_arrEvent['param']['opened']=$this->_data->filtered['opened'];
		}
		if( isset( $this->_data->filtered['clicked'] ) ){
			$_arrEvent['param']['clicked']=$this->_data->filtered['clicked'];
		}
		if( isset( $this->_data->filtered['options'] ) ){
			$_arrEvent['param']['options']=$this->_data->filtered['options'];
		}
		$_eventObj->setEntered( $_arrEvent )->set();
//		$_eventObj->getEntered( $_arrData );
//		$_s8rOrder=new Project_Efunnel_Order();
//		$_s8rOrder->setEntered(array( 'user_id'=>$this->_userId, 'ef_id'=>$_arrData['param']['ef_id'], 'email_id'=>$_arrData['id'], 'added'=>$_arrData['added'] ))->set();
		return $this;
	}
	
	protected $_withSortOrder=' ORDER BY d.added DESC';
	
	public function withOrder( $_str='' ){
		if ( !empty( $_str ) ){
			$this->_withSortOrder=$_str;
		}else{
			return $this;
		}
		$this->_cashe['order']=$this->_withSortOrder;
		if ( !is_array( $this->_withSortOrder ) ) {
			$_arrOrd=array( $this->_withSortOrder );
		}
		foreach( $_arrOrd as $v ) {
			if ( $v=='rand' ) {
				$this->_withSortOrder=' ORDER BY RAND()';
			}else{
				$_arrPrt=explode( '--', $v );
				$this->_withSortOrder=' ORDER BY '.$_arrPrt[0].' '.( ( $_arrPrt[1]=='up' ) ? 'DESC':'ASC' );
			}
		}
		return $this;
	}

	public function withPaging( $_arr=array() ){
		$this->_withPaging=$_arr;
		if( !isset( $this->_withPaging['reconpage'] ) || empty( $this->_withPaging['reconpage'] ) ){
			$this->_withPaging['reconpage']=Zend_Registry::get( 'config' )->database->paged_select->row_in_page;
		}
		if( !isset( $this->_withPaging['numofdigits'] ) || empty( $this->_withPaging['numofdigits'] ) ){
			$this->_withPaging['numofdigits']=Zend_Registry::get( 'config' )->database->paged_select->num_of_digits;
		}
		if( !isset( $this->_withPaging['url']['page'] ) || empty( $this->_withPaging['url']['page'] ) ){
			$this->_withPaging['url']['page']=1;
		}
		return $this;
	}

	public function getPaging( &$arrRes ){
		$arrRes=array();
		if ( $this->page>1 ) { // у нас не первая страница
			$this->_withPaging['rec_from']=( ( $this->page-1 )*$this->_withPaging['reconpage'] )+1;
			$_intTest=$this->_withPaging['rec_from']+$this->_withPaging['reconpage']-1;
			$this->_withPaging['rec_to']=$this->_withPaging['rowtotal']>$_intTest?$_intTest:$this->_withPaging['rowtotal'];
		} else { // первая страница
			$this->_withPaging['rec_from']=1;
			$this->_withPaging['rec_to']=$this->_withPaging['rowtotal']>$this->_withPaging['reconpage']?$this->_withPaging['reconpage']:$this->_withPaging['rowtotal'];
		}
		$this->_withPaging['maxpage']=ceil( $this->_withPaging['rowtotal']/$this->_withPaging['reconpage'] );
		$arrRes['curpage']=$this->_withPaging['url']['page'];
		$arrRes['recall']=$this->_withPaging['rowtotal'];
		$arrRes['recfrom']=$this->_withPaging['rec_from'];
		$arrRes['recto']=$this->_withPaging['rec_to'];
		if ( !( $this->_withPaging['rowtotal']>$this->_withPaging['reconpage'] ) ) {
			return $this;
		}
		if( empty( array_diff_key( $this->_withPaging['url'], array( 'page' => '' ) ) ) ){
			$this->_withPaging['href'] = '?page=';
		} else {
			$this->_withPaging['href'] = '?' . http_build_query( array_diff_key( $this->_withPaging['url'], array( 'page' => '' ) ) ) .'&page=';
		}
		// calculate diapazon refaktoring TODO 04.12.2008
		$_intStart=$this->_withPaging['url']['page']-$this->_withPaging['numofdigits']/2;
		$_intEnd=$this->_withPaging['url']['page']+$this->_withPaging['numofdigits']/2;
		if ( $_intStart<1 ) {
			$_intStart=1;
			$_intEnd=$_intStart+$this->_withPaging['numofdigits'];
		}
		$_intEnd1=intVal( ( $this->_withPaging['rowtotal']-1 )/$this->_withPaging['reconpage'] );
		$_intEnd1++;
		if ( $_intEnd>$_intEnd1&&$_intStart>$_intEnd-$_intEnd1 ) {
			$_intEnd=$_intEnd1;
			$_intStart=$_intEnd-$this->_withPaging['numofdigits'];
		} elseif ( $_intEnd>$_intEnd1 ) {
			$_intEnd=$_intEnd1;
			$_intStart=1;
		}
		$arrRes['urlmin']=$this->_withPaging['href'].'1';
		if ( $this->_withPaging['url']['page']>$_intStart ){
			$arrRes['urlminus']=$this->_withPaging['href'].( $this->_withPaging['url']['page']-1 );
		}
		$b=0;
		for ( $a=intVal( $_intStart ); $a<=$_intEnd; $a++ ) {
			if ( $a==$this->_withPaging['url']['page'] ) $arrRes['num'][$b]['sel']=1;
			$arrRes['num'][$b]['url']=$this->_withPaging['href'].$a;
			$arrRes['num'][$b]['number']=$a;
			$b++;
		}
		if ( $this->_withPaging['url']['page']<$_intEnd ){
			$arrRes['urlplus']=$this->_withPaging['href'].( $this->_withPaging['url']['page']+1 );
		}
		$arrRes['urlmax']=$this->_withPaging['href'].$this->_withPaging['maxpage'];
		$arrRes['maxpage']=$this->_withPaging['maxpage'];
		$arrRes['href']=$this->_withPaging['href'];
		return $this;
	}
	
	public function getList( &$mixRes ){
		if( empty( $this->_userId ) ){
			$this->_userId=Core_Users::$info['id'];
		}
		$page='';
		if( !empty( $this->_withPaging ) ){
			$page=' LIMIT '.( $this->_withPaging['url']['page']>1?( ( $this->_withPaging['url']['page']-1 )*$this->_withPaging['reconpage'] ).','.$this->_withPaging['reconpage'] : $this->_withPaging['reconpage'] );
		}
		/** For delimiter parts */
		if( !empty( $this->_getRandom2k ) ){
			$this->_withSortOrder=' ORDER BY id ASC';
			if(file_exists(Zend_Registry::get('config')->path->absolute->logfiles . 'part' . DIRECTORY_SEPARATOR . 'delim_' . $this->_userId . '.txt')){
				$_limit = explode(':', file_get_contents(Zend_Registry::get('config')->path->absolute->logfiles . 'part' . DIRECTORY_SEPARATOR . 'delim_' . $this->_userId . '.txt'));
				$page=' LIMIT ' . $_limit[0] . ',500';
				$_limit[0] += 500;
				file_put_contents(Zend_Registry::get('config')->path->absolute->logfiles . 'part' . DIRECTORY_SEPARATOR . 'delim_' . $this->_userId . '.txt', implode(':', $_limit));
			} else {
				$page=' LIMIT 0,500';
			}
		}
		$_whereMass=$_tagsCounter=$_where=array();
		try{
			Core_Sql::setConnectToServer( 'lpb.tracker' );
			//========
			if ( !empty( $this->_withTags ) ) {
				$_moreLikes=array();
				foreach( $this->_withTags as $_tagN ){
					if( !empty( $_tagN ) ){
						$_moreLikes[]= 'd.tags LIKE \'%,'.$_tagN.',%\'';
					}
				}
				if( !empty( $_moreLikes ) ){
					$_whereMass[]='('.implode( ' OR ', $_moreLikes ).')';
				}
			}
			if( !empty( $this->_withEfunnelIds ) ){
				$_where[]='( name="ef_id" AND value IN ('.Core_Sql::fixInjection( $this->_withEfunnelIds ).') )';
			}
			if( !empty( $this->_withoutEfunnelIs ) ){
				$_arrEventIds=Core_Sql::getField( 'SELECT event_id FROM s8rs_parameters_'.$this->_userId.' WHERE name="ef_id"' );
				$_arrNotUserIds=Core_Sql::getField( 'SELECT d.id AS id FROM s8rs_'.$this->_userId.' d JOIN s8rs_events_'.$this->_userId.' e ON d.id=e.sub_id WHERE e.id IN ("'.implode( '","', $_arrEventIds).'") GROUP BY d.email' );
				$_whereMass[]='d.id NOT IN ('.Core_Sql::fixInjection( $_arrNotUserIds ).')';
			}
			switch( $this->_withTime ){
				case self::TIME_MINUTE :
					$_whereMass[]="DATE_FORMAT(FROM_UNIXTIME(d.added),'%Y-%m-%d-%H-%i')='".date('Y-m-d-H-i',time())."'";
					break;
				case self::TIME_TODAY :
					$_whereMass[]="DATE_FORMAT(FROM_UNIXTIME(d.added),'%Y-%m-%d')='".date('Y-m-d',time())."'";
					break;
				case self::TIME_YESTERDAY :
					$_whereMass[]="DATE_FORMAT(FROM_UNIXTIME(d.added),'%Y-%m-%d')='".date('Y-m-d',strtotime("yesterday"))."'";
					break;
				case self::TIME_LAST_7_DAYS :
					$_whereMass[]="d.added>'".( time()-(60*60*24*7) )."'";
					break;
				case self::TIME_THIS_MONTH :
					$_whereMass[]="DATE_FORMAT(FROM_UNIXTIME(d.added),'%Y-%m')='".date('Y-m',time())."'";
					break;
				case self::TIME_LAST_MONTH :
					$_whereMass[]="DATE_FORMAT(FROM_UNIXTIME(d.added),'%Y-%m')='".date('Y-m',strtotime('-1 month'))."'";
					break;
				case self::TIME_CUSTOM :
					$_whereMass[]="d.added>".$this->_withTimeStart." AND d.added<".$this->_withTimeEnd;
					break;
				case self::TIME_ALL :
				default:
				break;
			}
			if( !empty( $this->_withLead ) ){
				$_where[]='( name="lead_id" AND value IN ('.Core_Sql::fixInjection( $this->_withLead ).') )';
			}
			if( !empty( $this->_withStatusMessage ) ){
				if( in_array( $this->_withStatusMessage, array( 'notopened', 'notclicked' ) ) ){
					$_arrHaveEventIds=Core_Sql::getField( 'SELECT event_id FROM s8rs_parameters_'.$this->_userId.' WHERE ( name="'.str_replace('not','',$this->_withStatusMessage).'" AND value<>"" )' );
					$_haveEmails=Core_Sql::getField( 'SELECT d.email FROM s8rs_'.$this->_userId.' d JOIN s8rs_events_'.$this->_userId.' e ON d.id=e.sub_id WHERE e.id IN ("'.implode( '","', $_arrHaveEventIds).'") GROUP BY d.email' );
					$_arrAllEFEventIds=Core_Sql::getField( 'SELECT event_id FROM s8rs_parameters_'.$this->_userId.' WHERE name="ef_id"' );
					$_allEFEmails=Core_Sql::getField( 'SELECT d.email FROM s8rs_'.$this->_userId.' d JOIN s8rs_events_'.$this->_userId.' e ON d.id=e.sub_id WHERE e.id IN ("'.implode( '","', $_arrAllEFEventIds).'") GROUP BY d.email' );
					$_whereMass[]='( d.email IN ("'.implode( '","', array_diff( $_allEFEmails, $_haveEmails )).'") )';
				}else{
					$_where[]='( name="'.$this->_withStatusMessage.'" AND value<>"" )';
				}
			}
			if( !empty( $this->_withSmtpId ) ){
				$_where[]='( ( name="smtp" OR name="smtpid" ) AND value IN ('.Core_Sql::fixInjection( $this->_withSmtpId ).') )';
			}
			if ( !empty( $this->_withEmail ) ){
				$_whereMass[]='d.email IN ('.Core_Sql::fixInjection( $this->_withEmail ).')';
			}
			if ( !empty( $this->_withIds ) ){
				$_whereMass[]='d.id IN ('.Core_Sql::fixInjection( $this->_withIds ).')';
			}
			if ( $this->_withoutFlgGlobalUnsubscribe ){
				$_whereMass[]='d.flg_global_unsubscribe <> 1';
			}
			if ( $this->_onlyFlgGlobalUnsubscribe ){
				$_whereMass[]='d.flg_global_unsubscribe = 1';
			}
			if ( !empty( $this->_withValidation ) ){
				$_whereMass[]='d.status IN ('.Core_Sql::fixInjection( $this->_withValidation ).')';
			}elseif ( !empty( $this->_onlyValid ) ){
				$_whereMass[]='d.status<>"not_valid"';
			}
			$_arrEvents=array();

			$fields=Core_Sql::getField( 'DESCRIBE s8rs_'.$this->_userId );
			if ( !in_array( 'status', $fields ) ){
				Core_Sql::setExec('ALTER TABLE s8rs_'.$this->_userId.' ADD COLUMN status VARCHAR(15) NOT NULL DEFAULT \'\'');
				Core_Sql::setExec('ALTER TABLE s8rs_'.$this->_userId.' ADD COLUMN status_data INT(11) UNSIGNED NOT NULL DEFAULT \'0\'');
			}

			if( !empty( $_where ) ){
				$_arrEventIds=array();
				foreach( $_where as $_swr ){
					$_arrNewEventIds=Core_Sql::getField( 'SELECT event_id FROM s8rs_parameters_'.$this->_userId.' WHERE '.$_swr );
					if( empty( $_arrEventIds ) ){
						$_arrEventIds=$_arrNewEventIds;
					}else{
						$_arrEventIds=array_intersect($_arrEventIds, $_arrNewEventIds);
					}
				}
				if( !empty( $_arrEventIds ) ){
					$_whereMass[]='e.id IN ("'.implode( '","', $_arrEventIds).'")';
				}
			}
			$this->_withPaging['rowtotal']=count( Core_Sql::getField( 'SELECT 1 FROM s8rs_'.$this->_userId.' d JOIN s8rs_events_'.$this->_userId.' e ON d.id=e.sub_id'.(!empty($_whereMass)?' WHERE ':'').implode( ' AND ', $_whereMass ).' GROUP BY d.email' ) );
			
			$_strStatus='';
			if ( !empty( $this->_withStatus ) ){
				$_strStatus=' ,d.status, d.status_data';
			}
			$mixRes=Core_Sql::getAssoc( 'SELECT d.id, d.email, d.ip, d.tags, d.name, d.settings, d.added, d.flg_global_unsubscribe'.$_strStatus.' FROM s8rs_'.$this->_userId.' d JOIN s8rs_events_'.$this->_userId.' e ON d.id=e.sub_id'.(!empty($_whereMass)?' WHERE ':'').implode( ' AND ', $_whereMass ).' GROUP BY d.email'.(!empty($this->_withSortOrder)?$this->_withSortOrder:'').$page );

			if( !$this->_noEvents ){
				$_subIds=array();
				foreach( $mixRes as $_data ){
					$_subIds[$_data['id']]=$_data['id'];
				}
				$_arrEvents=Core_Sql::getAssoc( 'SELECT d.id, d.sub_id, d.added, e.name, e.value FROM s8rs_events_'.$this->_userId.' d JOIN s8rs_parameters_'.$this->_userId.' e ON d.id=e.event_id WHERE d.sub_id IN ("'.implode( '","', $_subIds ).'")' );
			}
			
			if( $this->_withTagsHeat && !$this->_withoutTags ){
				$_tagsCounter=Core_Sql::getAssoc( 'SELECT COUNT(*) as counter, tags FROM s8rs_'.$this->_userId.' GROUP BY tags' );
			}

			/** For delimiter parts */
			if($this->_getRandom2k){
				if(!file_exists(Zend_Registry::get('config')->path->absolute->logfiles . 'part' . DIRECTORY_SEPARATOR . 'delim_' . $this->_userId . '.txt')){
					file_put_contents(Zend_Registry::get('config')->path->absolute->logfiles . 'part' . DIRECTORY_SEPARATOR . 'delim_' . $this->_userId . '.txt', '500:' . $this->_withPaging['rowtotal']);
				} else {
					$_limit = explode(':', file_get_contents(Zend_Registry::get('config')->path->absolute->logfiles . 'part' . DIRECTORY_SEPARATOR . 'delim_' . $this->_userId . '.txt'));
					$_limit[1] = $this->_withPaging['rowtotal'];
					file_put_contents(Zend_Registry::get('config')->path->absolute->logfiles . 'part' . DIRECTORY_SEPARATOR . 'delim_' . $this->_userId . '.txt', implode(':', $_limit));
					if($_limit[0] >= $_limit[1]) {
						unlink(Zend_Registry::get('config')->path->absolute->logfiles . 'part' . DIRECTORY_SEPARATOR . 'delim_' . $this->_userId . '.txt');
					}
				}
			}

			//========
			Core_Sql::renewalConnectFromCashe();
		}catch(Exception $e) {
			file_put_contents(Zend_Registry::get('config')->path->absolute->logfiles.'errors_'.$this->_userId.'.txt', serialize($e->getMessage()));
			if( $e->getMessage() == 'SQLSTATE[HY000]: General error: 2006 MySQL server has gone away' ){
				Core_Sql::renewalConnectFromCashe();
				return $this;
			}
			$obj1=new Project_Subscribers( $this->_userId );$obj1->install();
			$obj2=new Project_Subscribers_Events( $this->_userId );$obj2->install();
			$obj3=new Project_Subscribers_Parameters( $this->_userId );$obj3->install();
			Core_Sql::renewalConnectFromCashe();
			return $this;
		}
		$_tagsIds=$_combineEvents=array();
		foreach( $_arrEvents as $_key=>$_event ){
			if( !isset( $_combineEvents[$_event['sub_id']] ) ){
				$_combineEvents[$_event['sub_id']]=array($_event['id']=>array('added'=>$_event['added']));
			}
			if( !isset( $_combineEvents[$_event['sub_id']][$_event['id']] ) ){
				$_combineEvents[$_event['sub_id']][$_event['id']]=array('added'=>$_event['added']);
			}
			$_combineEvents[$_event['sub_id']][$_event['id']][$_event['name']]=$_event['value'];
			unset( $_arrEvents[$_key] );
		}
		unset( $_arrEvents );
		foreach( $mixRes as &$_data ){
			foreach( $_combineEvents as $_subId=>$_events ){
				if( $_data['id'] == $_subId ){
					$_data['efunnel_events']=$_events;
					unset( $_combineEvents[$_subId] );
				}
			}
			if( strpos( $_data['tags'], ',' ) !== false ){
				$_data['tags']=array_filter( explode( ',', trim( $_data['tags'], ',' ) ) );
			}
			if( !empty( $_data['tags'] ) && !in_array( $_data['tags'], $_tagsIds ) ){
				foreach( $_data['tags'] as $_tagId ){
					$_tagsIds[$_tagId]=$_tagId;
				}
			}
		}

		unset( $_data );
		$_arrTag2Count=array();
		if( !$this->_withoutTags ){
			/*
			$_tags = Project_Tags::get( implode( ',', $_tagsIds ) );
			foreach( $_tagsCounter as &$_data ){
				if( strpos( $_data['tags'], ',' ) !== false ){
					$_data['tags']=array_filter( explode( ',', trim( $_data['tags'], ',' ) ) );
				}
				foreach( $_data['tags'] as $_tagIdCount ){
					foreach( $_tags as $_tagId=>$_tagName ){
						if( $_tagId == $_tagIdCount ) {
							if( empty( $_tagName ) ){
								$_tagName=$_tagId;
							}
							if( !isset( $this->_arrTagsHeat[$_tagName] ) ){
								$this->_arrTagsHeat[$_tagName]=0;
							}
							$this->_arrTagsHeat[$_tagName]+=$_data['counter'];
						}
					}
				}
			}
			*/

			$_allTags=array();
			foreach( $_tagsCounter as $_tdata ){
				if( strpos( $_tdata['tags'], ',' ) !== false ){
					$_allTags=array_merge( $_allTags, array_filter( explode( ',', $_tdata['tags'] ) ) );
				}
			}
			unset( $_tdata );

			$_tags = Project_Tags::get( implode( ',', array_unique( $_allTags ) ) );

			foreach( $_tagsCounter as &$_data ){
				if( strpos( $_data['tags'], ',' ) !== false ){
					$_data['tags']=array_filter( explode( ',', trim( $_data['tags'], ',' ) ) );
				}
				foreach( $_data['tags'] as $_tagIdCount ){
					foreach( $_tags as $_tagId=>$_tagName ){
						if( $_tagId == $_tagIdCount ) {
							if( empty( $_tagName ) ){
								$_tagName=$_tagId;
							}
							if( !isset( $this->_arrTagsHeat[$_tagName] ) ){
								$this->_arrTagsHeat[$_tagName]=0;
							}
							$this->_arrTagsHeat[$_tagName]+=$_data['counter'];
						}
					}
				}
			}
			foreach( $mixRes as &$item ){
				foreach( $item['tags'] as &$_tagGetName ){
					foreach( $_tags as $_tagId=>$_tagName ){
						if( $_tagId == $_tagGetName ) {
							$_tagGetName=$_tagName;
						}
					}
				}
			}
			unset( $item );
		}
		$this->init();
		return $this;
	}

	public function setMass( $_flgEnd=false ){
		$this->_data->setFilter();
		$_addTime=$_efId=false;
		$_allTagsIds=array();
		$_arrSend=$_arrEmails=$_arrValues=array();
		foreach( $this->_data->filtered as $_send ){
			if( isset( $_send['email'] ) && !empty( $_send['email'] ) ){
				$_arrEmails[$_send['email']]=$_send['email'];
			}
			foreach( array_keys( $_send ) as $_name ){
				if( $_name == 'ef_id' ){
					$_efId=$_send[$_name];
					continue;
				}
				if( $_name == 'start' ){
					$_addTime=$_send[$_name];
					continue;
				}
				if( !isset( $_arrValues[$_name] ) ){
					$_arrValues[$_name]='';
				}
				if( $_name == 'email' ){
					$_arrEmails[$_name]=$_name;
				}
				if( $_name == 'tags' ){
					if( is_string( $_send[$_name] ) ){
						$_addNewTags=array_filter( explode( ',', $_send[$_name] ) );
					}elseif( is_array( $_send[$_name] ) ){
						$_addNewTags=array_filter( $_send[$_name] );
					}
					if( is_array( $_addNewTags ) && !empty( $_addNewTags ) ){
						$_allTagsIds=array_merge( $_addNewTags, $_allTagsIds );
					}
				}
			}
		}
		unset( $_send );
		$_allTags=Project_Tags::get( array_unique( $_allTagsIds ) );
		try {
			Core_Sql::setConnectToServer( 'lpb.tracker' );
			//========
			$_arrOldEmails=Core_Sql::getAssoc( 'SELECT d.id, d.email FROM s8rs_'.$this->_userId.' d WHERE email IN ('.Core_Sql::fixInjection( $_arrEmails ).')' );
			$_oldEmails=$_cronUpdate=array();
			foreach( $_arrOldEmails as $_data ){
				$_oldEmails[$_data['id']]=$_data['email'];
			}
			foreach( $this->_data->filtered as $_send ){
				$_arrSender=$_arrValues;
				if( !in_array( $_send['email'], $_oldEmails ) ){
					foreach( array_keys( $_arrValues ) as $_checkKey ){
						if( isset( $_send[$_checkKey] ) ){
							$_arrSender[$_checkKey]=$_send[$_checkKey];
						}
					}
					$_arrSend[$_send['email']]=implode( '","', $_arrSender );
				}
			}
			unset( $_send );
			if( !empty( $_oldEmails ) ){
				if( count( $_oldEmails ) > 1010 ){ // сформировать запрос на крон если данных больше 1000
					$_cronUpdate['arrData']['subscribers']=array_keys( $_oldEmails );
					$_cronUpdate['arrData']['update_selected']=1;
					if( !empty( $_efId ) ){
						$_cronUpdate['arrData']['email_funnels']=$_efId;
					}
					if( !empty( $_allTags ) ){
						$_cronUpdate['arrData']['tags']=implode(',', $_allTags); // должны пройти на сохранение только новые добавленные тэги
					}
					$_cronUpdate['user_id']=Core_Users::$info['id'];
					file_put_contents(
						Zend_Registry::get('config')->path->absolute->crontab.'mass_updater'.DIRECTORY_SEPARATOR.microtime(true).'.mu', 
						Core_Users::$info['id'].PHP_EOL.'Project_Efunnel_Subscribers'.PHP_EOL.'cronUpdateContacts'.PHP_EOL.serialize( array_filter( $_cronUpdate ) ) 
					);
				}else{
					$_arrTagsMass=array();
					if( !empty( $_allTags ) ){
						foreach( $_arrOldEmails as $_data ){
							$_moveUserTags=false;
							foreach( $this->_data->filtered as $_updateUsers ){
								if( $_updateUsers['email'] == $_data['email'] ){
									if( is_string( $_updateUsers['tags'] ) ){
										$_arrTagsMass[$_updateUsers['tags']][]=$_data['id'];
									}elseif( is_array( $_updateUsers['tags'] ) ){
										$_arrTagsMass[','.implode( ',', array_unique( $_updateUsers['tags'] ) ).','][]=$_data['id'];
									}
								}
							}
						}
					}
					foreach( $_arrTagsMass as $_keyTags=>$_arrUpdateIds ){
						Core_Sql::setExec( 'UPDATE s8rs_'.$this->_userId.' SET tags="'.$_keyTags.'" WHERE id IN ("'.implode( '","', $_arrUpdateIds).'")' );
					}
				}
			}
			$_intContactsLimit=Core_Sql::getRecord( 'SELECT COUNT(*) FROM s8rs_'.$this->_userId );
			if( $_intContactsLimit['COUNT(*)']+count( $_arrSend ) >= Core_Users::$info['contact_limit'] && !empty( Core_Users::$info['contact_limit'] ) ){
				$_intSlice=$_intContactsLimit['COUNT(*)']-Core_Users::$info['contact_limit'];
				if( $_intSlice >= 0 ){
					$_arrSend=array();
				}else{
					if( -$_intSlice < count( $_arrSend ) ){
						$_arrSend=array_slice($_arrSend, 0, -$_intSlice, true);
					}
				}
			}
			if( !empty( $_arrSend ) ){
				Core_Sql::setExec( 'INSERT INTO s8rs_'.$this->_userId.' (`'.implode( '`,`', array_keys( $_arrValues  ) ).'`) VALUES ("'.implode( '"),("', $_arrSend ).'")' );
			}
			$_arrNewEmailsIds=Core_Sql::getField( 'SELECT d.id FROM s8rs_'.$this->_userId.' d WHERE email IN ('.Core_Sql::fixInjection( $_arrEmails ).') AND d.flg_global_unsubscribe <> 1' );
			if( $_addTime === false ){
				$_addTime=time();
			}
			$_arrSend=array();
			foreach( $_arrNewEmailsIds as $_newId ){
				$_arrSend[]='("'.$_newId.'","'.Project_Subscribers_Events::FROM_ADMIN.'","'.$_addTime.'")';
			}
			Core_Sql::setExec( 'INSERT INTO s8rs_events_'.$this->_userId.' (`sub_id`,`event_type`,`added`) VALUES '.implode( ',', $_arrSend ) );
			if( $_efId!==false ){
				sleep( 2 );
				$_arrNewEventsIds=Core_Sql::getField( 'SELECT id FROM s8rs_events_'.$this->_userId.' WHERE added='.$_addTime );
				$_arrSend=array();
				foreach( $_arrNewEventsIds as $_newId ){
					$_arrSend[]='("'.$_newId.'","ef_id","'.$_efId.'")';
				}
				Core_Sql::setExec( 'INSERT INTO s8rs_parameters_'.$this->_userId.' (`event_id`,`name`,`value`) VALUES '.implode( ',', $_arrSend ) );
			}
			//========
			Core_Sql::renewalConnectFromCashe();
		}catch(Exception $e){
			$this->cronErrorLog[]=$e->getMessage();
			file_put_contents(Zend_Registry::get('config')->path->absolute->logfiles.'errors_'.$this->_userId.'.txt', serialize($e->getMessage()));
			$obj1=new Project_Subscribers( $this->_userId );$obj1->install();
			$obj2=new Project_Subscribers_Events( $this->_userId );$obj2->install();
			$obj3=new Project_Subscribers_Parameters( $this->_userId );$obj3->install();
			Core_Sql::renewalConnectFromCashe();
			$this->init();
			if( $_flgEnd ){
				$this->setMass( true );
				exit;
			}
			// return false;
		}
		return true;
	}
	
	public function getTagsHeat( &$_tagsHeat ){
		if( isset( $this->_arrTagsHeat ) && !empty( $this->_arrTagsHeat ) ){
			$_tagsHeat=$this->_arrTagsHeat;
		}
		$this->_arrTagsHeat=array();
	}
	
	public function del(){
		$_strWith=array();
		if ( !empty( $this->_withEfunnelIds ) ){
			$_strWith[]='sender_id IN ('.Core_Sql::fixInjection( $this->_withEfunnelIds ).')';
		}
		if( $this->_withEmail == array( '' ) ){
			$_strWith[]='email IS NULL';
		}elseif ( !empty( $this->_withEmail ) ){
			$_strWith[]='email IN ('.Core_Sql::fixInjection( $this->_withEmail ).')';
		}
		if( empty( $_strWith ) ){
			$this->init();
			return false;
		}
			$_writer=new Zend_Log_Writer_Stream( Zend_Registry::get('config')->path->absolute->logfiles.'EF_Contacts_Remove.log' );
			$_writer->setFormatter( new Zend_Log_Formatter_Simple("%timestamp% %priorityName% (%priority%): %message%\r\n") );
			$_logger=new Zend_Log( $_writer );
			$_logger->info('-------------Project_Efunnel_Subscribers 2---------------');
			$_logger->info(serialize($_SERVER));
			$_logger->info('DELETE FROM s8rs_'.$this->_userId.' WHERE '.implode( ' and ', $_strWith ));
			$_logger->info('-------------Project_Efunnel_Subscribers 2---------------');

		try {
			Core_Sql::setConnectToServer( 'lpb.tracker' );
			//========
			Core_Sql::setExec( 'DELETE FROM s8rs_'.$this->_userId.' WHERE '.implode( ' and ', $_strWith ) );
			//========
			Core_Sql::renewalConnectFromCashe();
		} catch(Exception $e) {
			Core_Sql::renewalConnectFromCashe();
			$this->init();
			return false;
		}
		$this->init();
		return true;
	}
}
?>