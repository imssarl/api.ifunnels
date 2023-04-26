<?php
/**
 * Project_Mooptin_Autoresponders
 */
class Project_Mooptin_Autoresponders extends Core_Data_Storage{

	protected $_table='mo_autoresponders';
	protected $_fields=array('id', 'user_id', 'name', 'settings', 'edited', 'added');

	public static function install(){
		Core_Sql::setExec('DROP TABLE IF EXISTS mo_autoresponders');
		Core_Sql::setExec("CREATE TABLE `mo_autoresponders` (
			`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
			`user_id` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			`name` TEXT NULL,
			`settings` TEXT NULL,
			`added` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			`edited` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			PRIMARY KEY (`id`)
		)
		COLLATE='utf8_general_ci'
		ENGINE=MyISAM");
	}

	protected function beforeSet(){
		$this->_data->setFilter( array( 'clear' ) );
		$this->_data->setElement('settings', base64_encode( serialize( $this->_data->filtered['settings'] ) ) );
		return true;
	}

	protected function afterSet(){
		$this->_data->filtered['settings']=unserialize( base64_decode( $this->_data->filtered['settings'] ) );
		return true;
	}
	
	public function getList( &$mixRes ){
		parent::getList( $mixRes );
		if( array_key_exists( 0, $mixRes ) ){
			foreach( $mixRes as &$_arrZeroData ){
				$_arrZeroData['settings']=unserialize( base64_decode( $_arrZeroData['settings'] ) );
			}
		}elseif( isset( $mixRes['settings'] ) ){
			$mixRes['settings']=unserialize( base64_decode( $mixRes['settings'] ) );
		}
		return $this;
	}
	
	public static function sendAutorespond( $arrRequest=array(), &$arrCallback ){
		$jsonp_callback = @$_REQUEST['callback'];
		if (!isset($arrRequest['id']) && !isset($arrRequest['letterid']) ){
			$return_data['status'] = 'error';
			echo $jsonp_callback.'('.json_encode($return_data).')';
			return false;
		}
		$_object=new Project_Mooptin();
		if( isset( $arrRequest['id'] ) ){
			$_object->withIds( $arrRequest['id'] )->onlyOne()->getList( $_arrMoData );
		}
		$_rtValidation=array();
		if( isset( $_arrMoData['user_id'] ) && !empty( $_arrMoData['user_id'] ) ){
			Core_Users::getInstance()->setById( $_arrMoData['user_id'] );
		}
		if( Core_Acs::haveAccess( array( 'Validate' ) )
			&& Project_Validations_Realtime::check( $_arrMoData['user_id'], Project_Validations_Realtime::MOOPTIN, $arrRequest['id'] )
			&& isset( $arrRequest['email'] ) && !empty( $arrRequest['email'] )
		){
			$_status=2;
			$_valid=new Project_Validations();
			$_valid->withName( $arrRequest['email'] )->onlyLast()->onlyOne()->getList( $_checked );
			if( isset( $_checked['added'] ) && (int)$_checked['added'] < time() - 24*60*60 ){
				$_status=3; // только что проверяли
				$_return=$_checked['options'];
				$_rtValidation['status']=$_return['result'];
				$_rtValidation['status_data']=time();
			}
			if( $_status!=3 && $_valid->getPayment(1) ){
				$_status=1;
			}
			if( $_status == 1 ){
				$_obj=new Project_Thechecker();
				$_return=$_obj->checkOne( $arrRequest['email'] );
				if( !isset( $_return['message'] ) && isset( $_return['result'] ) ){
					$_valid->setEntered( array(
						'name'=>$arrRequest['email'],
						'options'=>$_return,
						'status'=>$_status,
						'type'=>Project_Validations::REAL_TIME
					) )->set();
					$_rtValidation['status']=$_return['result'];
					$_rtValidation['status_data']=time();
				}
			}
			if( $_status==2 || $_return['result'] == 'undeliverable' ){
				$return_data['status'] = 'error';
				$return_data['message'] = 'This email address is not valid. Please provide a valid email address.';
				echo $jsonp_callback.'('.json_encode($return_data).')';
				if( isset( $_arrMoData['user_id'] ) && !empty( $_arrMoData['user_id'] ) ){
					Core_Users::getInstance()->retrieveFromCashe();
				}
				return false;
			}
		}
		if( isset( $_arrMoData['settings']['type'] ) && ( $_arrMoData['settings']['type'] == 'email' || $_arrMoData['settings']['type'] == 'sms' ) && !isset( $arrRequest['letterid'] ) ){
			$_moemail=new Project_Mooptin_Moemail();
			if( !$_moemail->setEntered( array( 'settings'=>$arrRequest, 'mo_id'=>$arrRequest['id'] ))->set() ){
				$return_data['status'] = 'error';
				echo $jsonp_callback.'('.json_encode($return_data).')';
				if( isset( $_arrMoData['user_id'] ) && !empty( $_arrMoData['user_id'] ) ){
					Core_Users::getInstance()->retrieveFromCashe();
				}
				return false;
			}
			$_moemail->getEntered( $_data );
			$return_data = array();
			$return_data['status'] = 'OK';
			if( $_arrMoData['settings']['type'] == 'email' ){
				$return_data['return_url'] = 'mailto:'.$_data['hach'].'@consumertips.net?subject='.htmlspecialchars($_arrMoData['settings']['subject_line']).'&body='.htmlspecialchars($_arrMoData['settings']['subject_text']);
			}else{
				switch ( $arrRequest['userAgent'] ){
					case 'A': 
						$return_data['return_url'] = 'sms:'.$_arrMoData['settings']['sms_number'].'?body='.$_data['hach'].'%20Input%20your%20email%20here:%20';
					break;
					case 'I': 
						$return_data['return_url'] = 'sms:'.$_arrMoData['settings']['sms_number'].';?&body='.$_data['hach'].'%20Input%20your%20email%20here:%20';
					break;
					case 'W': 
					default: 
						$return_data['return_url'] = 'sms://'.$_arrMoData['settings']['sms_number'].'?body='.$_data['hach'].'%20Input%20your%20email%20here:%20';
					break;
				}
			}
			$return_data['close_delay'] = 0;//1000*intval($a8rOptions['settings']['options']['close_delay']);  close_delay-использовался только в Exquisite popups - его уже не используем,  a8rOptions - должно быть внутри цикла ниже
			echo $jsonp_callback.'('.json_encode($return_data).')';
			if( isset( $_arrMoData['user_id'] ) && !empty( $_arrMoData['user_id'] ) ){
				Core_Users::getInstance()->retrieveFromCashe();
			}
			return true;
		}
		$_messageData=array();$nameInBase='';
		if( isset( $arrRequest['letterid'] ) ){
			$_moemail=new Project_Mooptin_Moemail();
			$_moemail->withHach( $arrRequest['letterid'] )->onlyOne()->getList( $hashData );
			$arrRequest=(isset($hashData['settings'])&&is_array($hashData['settings'])?$hashData['settings']:array())+$arrRequest;
			$_object->withIds( $hashData['mo_id'] )->onlyOne()->getList( $_arrMoData );
			if( isset( $arrRequest['name'] ) && !empty( $arrRequest['name'] ) ) $_messageData['name']=@$arrRequest['name'];
			if( isset( $arrRequest['email'] ) && !empty( $arrRequest['email'] ) ) $_messageData['email']=@$arrRequest['email'];
			if( isset( $arrRequest['phone'] ) && !empty( $arrRequest['phone'] ) ) $_messageData['phone']=@$arrRequest['phone'];
			if( isset( $arrRequest['ip'] ) && !empty( $arrRequest['ip'] ) ) $_messageData['ip']=@$arrRequest['ip'];
			$_fnametxt='';
			foreach( array( 'fname', 'firstname', 'firstName' ) as $_fname ){
				if( isset( $arrRequest[ $_fname ] ) ){
					$_fnametxt=$arrRequest[ $_fname ];
				}
			}
			$_lnametxt='';
			foreach( array( 'lname', 'lastname', 'lastName' ) as $_lname ){
				if( isset( $arrRequest[ $_lname ] ) ){
					$_lnametxt=$arrRequest[ $_lname ];
				}
			}
			if( isset( $arrRequest['name'] ) && empty( $_lnametxt ) && empty( $_fnametxt ) ){
				$nameInBase=$arrRequest['name'];
			}else{
				$nameInBase=$arrRequest['name'].' ( '.$_fnametxt.(!empty($_lnametxt)?' '.$_lnametxt:'').' )';
			}
			if( $_arrMoData['settings']['flg_conformation'] == 1 & isset($arrRequest['email']) && !empty( $arrRequest['email'] ) ){
				try{ 
					mail( $arrRequest['email'], $_arrMoData['settings']['conformation_line'], $_arrMoData['settings']['conformation_text'], 'From: confirmation@consumertips.net' );
				}catch(Exception $e){
					$arrCallback[0]['callback']['mail_error']=$e->getMessage();
				}
			}
			if( $_arrMoData['settings']['sms_confirmation'] == 1 & isset($arrRequest['phone']) && !empty( $arrRequest['phone'] ) ){
				try{
					$twilio = new Project_Ccs_Twilio_Abstract();
					$twilio->_client
						->messages->create(
							@$arrRequest['phone'],
							array(
								'from' => $_arrMoData['settings']['sms_number'],
								'body' => $_arrMoData['settings']['sms_text'],
							)
						);
				}catch(Exception $e){
					$arrCallback[0]['callback']['twilio_error']=$e->getMessage();
				}
			}
		}elseif(isset( $_arrMoData['user_id'] )){
			if( isset( $arrRequest['name'] ) && !empty( $arrRequest['name'] ) ) $_messageData['name']=@$arrRequest['name'];
			if( isset( $arrRequest['email'] ) && !empty( $arrRequest['email'] ) ) $_messageData['email']=@$arrRequest['email'];
			if( isset( $arrRequest['phone'] ) && !empty( $arrRequest['phone'] ) ) $_messageData['phone']=@$arrRequest['phone'];
			if( isset( $arrRequest['ip'] ) && !empty( $arrRequest['ip'] ) ) $_messageData['ip']=@$arrRequest['ip'];
			$_fnametxt='';
			foreach( array( 'fname', 'firstname', 'firstName' ) as $_fname ){
				if( isset( $arrRequest[ $_fname ] ) ){
					$_fnametxt=$arrRequest[ $_fname ];
				}
			}
			$_lnametxt='';
			foreach( array( 'lname', 'lastname', 'lastName' ) as $_lname ){
				if( isset( $arrRequest[ $_lname ] ) ){
					$_lnametxt=$arrRequest[ $_lname ];
				}
			}
			if( isset( $arrRequest['name'] ) && empty( $_lnametxt ) && empty( $_fnametxt ) ){
				$nameInBase=$arrRequest['name'];
			}else{
				$nameInBase=$arrRequest['name'].' ( '.$_fnametxt.(!empty($_lnametxt)?' '.$_lnametxt:'').' )';
			}
			$arrCallback[0]['message']='Create Local';
		}
		if( empty( $_arrMoData ) ){
			$return_data['status'] = 'error';
			$return_data['message'] = 'Empty mo optin data!';
			echo $jsonp_callback.'('.json_encode($return_data).')';
			if( isset( $_arrMoData['user_id'] ) && !empty( $_arrMoData['user_id'] ) ){
				Core_Users::getInstance()->retrieveFromCashe();
			}
			return false;
		}
		$_intContactsLimit=0;
		Core_Sql::setConnectToServer( 'lpb.tracker' );
		try {
			$_intContactsLimit=Core_Sql::getRecord( 'SELECT COUNT(*) FROM s8rs_'.$_arrMoData['user_id'] );
			Core_Sql::renewalConnectFromCashe();
		}catch(Exception $e){
			Core_Sql::renewalConnectFromCashe();
		}
		if( $_intContactsLimit['COUNT(*)']+1 > Core_Users::$info['contact_limit'] ){
			Core_Users::getInstance()->retrieveFromCashe();
			return false;
		}
		if ( isset($_arrMoData['settings']['integrations']) && in_array( 'emailfunnels', $_arrMoData['settings']['integrations'] ) ){ // это другая интеграция, не переносить в цикл
			$_addTags=array();
			if( is_array( $_arrMoData['tags'] ) ){
				$_addTags=$_arrMoData['tags'];
			}elseif( strpos( $_arrMoData['tags'], ',') !== false ){
				$_addTags=explode( ',', $_arrMoData['tags'] );
			}
			$_funnel=new Project_Efunnel();
			$_funnel->withIds( $_arrMoData['settings']['options']['email_funnel_id']['emailfunnels'] )->getList( $_arrEFunnel );
			foreach( $_arrEFunnel as $_efD ){
				if( is_array( $_efD['options']['tags'] ) ){
					$_addTags=array_merge( $_addTags, $_efD['options']['tags'] );
				}else{
					$_addTags=array_merge( $_addTags, explode( ',', $_efD['options']['tags'] ) );
				}
			}
			foreach( $_addTags as &$_tagValue ){
				$_tagValue=trim( $_tagValue );
			}
			$_addTags=array_unique( $_addTags );
			$_obj=new Project_Efunnel_Subscribers($_arrMoData['user_id']);
			$_obj->setEntered( array(
				'email'=>$arrRequest['email'],
				'sender_id'=>$_arrMoData['settings']['options']['email_funnel_id']['emailfunnels'],
				'tags'=>$_addTags
			)+$_rtValidation)->set();
			$arrCallback['emailfunnels']['message']='Create Email Funnel ';
		}
		$_object=new Project_Mooptin_Autoresponders();
		$_object->withIds( $_arrMoData['settings']['integrations'] )->getList( $_arrA8rsData );
		foreach( $_arrA8rsData as $a8rOptions ){
			$_sendOptions=array();
			if( isset( $_arrMoData['settings']['form'][$a8rOptions['id']] ) ){
				foreach( $_arrMoData['settings']['form'][$a8rOptions['id']] as $_arrA8r ){
					if( isset( $arrRequest[$_arrA8r['hash']] ) ){
						if( isset( $_arrA8r['static_value'] ) && !empty( $_arrA8r['static_value'] ) ){
							if( isset( $_arrA8r['name'] ) ){
								$_sendOptions[$_arrA8r['name']]=$_arrA8r['static_value'];
							}else{
								$_sendOptions[$_arrA8r['new_name']]=$_arrA8r['static_value'];
							}
						}else{
							if( isset( $_arrA8r['name'] ) ){
								$_sendOptions[$_arrA8r['name']]=$arrRequest[$_arrA8r['hash']];
							}else{
								$_sendOptions[$_arrA8r['new_name']]=$arrRequest[$_arrA8r['hash']];
							}
						}
					}elseif( isset( $_arrA8r['name'] ) ){
						if( isset( $_arrA8r['static_value'] ) && !empty( $_arrA8r['static_value'] ) ){
							$_sendOptions[$_arrA8r['name']]=$_arrA8r['static_value'];
						}else{
							$_sendOptions[$_arrA8r['name']]=$arrRequest[$_arrA8r['name']];
						}
					}elseif( isset( $_arrA8r['new_name'] ) ){
						if( isset( $_arrA8r['static_value'] ) && !empty( $_arrA8r['static_value'] ) ){
							$_sendOptions[$_arrA8r['new_name']]=$_arrA8r['static_value'];
						}else{
							$_sendOptions[$_arrA8r['new_name']]=$arrRequest[$_arrA8r['new_name']];
						}
					}
				}
			}
			if ( isset($a8rOptions['settings']['integration']) && in_array( 'webhook', $a8rOptions['settings']['integration'] ) ){
				$webhook_url = $a8rOptions['settings']['options']['webhook_url'].'?'.http_build_query($_sendOptions);
				$curl = curl_init($webhook_url);
				curl_setopt($curl, CURLOPT_URL, $webhook_url);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($curl, CURLOPT_HEADER, 0);
				curl_setopt($curl, CURLOPT_POST, 1);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $_sendOptions);
				$arrCallback[$a8rOptions['id']]=array( 'callback'=>curl_exec($curl) );
				curl_close( $curl );
				$arrCallback[$a8rOptions['id']]['message']=$arrCallback[$a8rOptions['id']]['callback'];
			}
			if ( isset($a8rOptions['settings']['integration']) && in_array( 'html', $a8rOptions['settings']['integration'] ) ){
				$arrCallback[$a8rOptions['id']]=array();
				$curl = curl_init();
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
				if( !isset( $a8rOptions['settings']['options']['method'] ) || strtolower( $a8rOptions['settings']['options']['method'] ) != 'post' ){
					curl_setopt($curl, CURLOPT_URL, $a8rOptions['settings']['options']['action'].'?'.http_build_query($_sendOptions));
					$arrCallback[$a8rOptions['id']]['get']=$a8rOptions['settings']['options']['action'].'?'.http_build_query($_sendOptions);
				}else{
					curl_setopt($curl, CURLOPT_URL, $a8rOptions['settings']['options']['action']);
					curl_setopt($curl, CURLOPT_POST, true);
					curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
					curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($_sendOptions));
					$arrCallback[$a8rOptions['id']]['post']=$a8rOptions['settings']['options']['action'].'?'.http_build_query($_sendOptions);
				}
				curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($curl, CURLOPT_BINARYTRANSFER, 1);
				curl_setopt($curl, CURLOPT_HEADER, 0);
				$arrCallback[$a8rOptions['id']]['callback']=curl_exec($curl);
				curl_close( $curl );
				$arrCallback[$a8rOptions['id']]['message']=$arrCallback[$a8rOptions['id']]['callback'];
			}
			
			if ( isset($a8rOptions['settings']['integration']) && in_array( 'mailchimp', $a8rOptions['settings']['integration'] ) ){
				$server=explode( '-', $a8rOptions['settings']['options']['mailchimp_api_key'] );
				$server=$server[1];
				$post=array();
				$post['merge_fields']=array();
				if( isset( $_arrMoData['settings']['form'][$a8rOptions['id']] ) ){
					foreach( $_arrMoData['settings']['form'][$a8rOptions['id']] as $_arrA8r ){
						if( in_array( $_arrA8r['name'], array( 'email_address', 'language' ) ) && isset( $arrRequest[$_arrA8r['hash']] ) ){
							$post[$_arrA8r['name']]=$arrRequest[$_arrA8r['hash']];
						}elseif( isset( $arrRequest[$_arrA8r['hash']] ) ){
							$post['merge_fields'][strtoupper($_arrA8r['name'])]=$arrRequest[$_arrA8r['hash']];
						}
					}
				}
				$post['merge_fields']=(object)$post['merge_fields'];
				$post['status']="subscribed";
				$curl = curl_init( 'https://'.$server.'.api.mailchimp.com/3.0/lists/'.$_arrMoData['settings']['options']['mailchimp_list_id'][$a8rOptions['id']].'/members' );
				curl_setopt($curl, CURLOPT_HTTPHEADER, array( 'Content-Type: application/json' ) );
				curl_setopt($curl, CURLOPT_USERPWD, $a8rOptions['settings']['options']['mailchimp_user'].':'.$a8rOptions['settings']['options']['mailchimp_api_key']); //Your credentials goes here
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
				curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($post));
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
				$arrCallback[$a8rOptions['id']]=array( 'callback'=>curl_exec($curl) );
				curl_close($curl);
				$decodeJSON=json_decode( $arrCallback[$a8rOptions['id']]['callback'], true );
				if( isset( $decodeJSON['id'] ) ){
					$arrCallback[$a8rOptions['id']]['message']="Contact added with id ".$decodeJSON['id'];
				}else{
					$arrCallback[$a8rOptions['id']]['message']="Error check admin for more information";
				}
			}
			
			if ( isset($a8rOptions['settings']['integration']) && in_array( 'icontact', $a8rOptions['settings']['integration'] ) ){
				$arrCallback[$a8rOptions['id']]=$this->icontact_addcontact($a8rOptions['settings']['options']['icontact_appid'], $a8rOptions['settings']['options']['icontact_apiusername'], $a8rOptions['settings']['options']['icontact_apipassword'], $a8rOptions['settings']['options']['icontact_listid'], $nameInBase, $arrRequest['email']);
			}
			
			if ( isset($a8rOptions['settings']['integration']) && in_array( 'campaignmonitor', $a8rOptions['settings']['integration'] ) ){
				$post['EmailAddress'] = $arrRequest['email'];
				$post['Name'] = $nameInBase;
				$post['Resubscribe'] = 'true';
				$post['RestartSubscriptionBasedAutoresponders'] = 'true';
				$post = json_encode($post);
				$curl = curl_init('https://api.createsend.com/api/v3/subscribers/'.urlencode($a8rOptions['settings']['options']['campaignmonitor_list_id']).'.json');
				curl_setopt($curl, CURLOPT_POST, 1);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
				$header = array(
					'Content-Type: application/json',
					'Content-Length: '.strlen($post),
					'Authorization: Basic '.base64_encode($a8rOptions['settings']['options']['campaignmonitor_api_key'])
					);
				curl_setopt($curl, CURLOPT_PORT, 443);
				curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
				curl_setopt($curl, CURLOPT_TIMEOUT, 10);
				curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC ) ;
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1); // verify certificate
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); // check existence of CN and verify that it matches hostname
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
				curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
				$arrCallback[$a8rOptions['id']]=curl_exec($curl);
				curl_close($curl);
			}
			
			
			if ( isset($a8rOptions['settings']['integration']) && in_array( 'everwebinar', $a8rOptions['settings']['integration'] ) ){
				$params=array();
				if( isset( $_arrMoData['settings']['form'][$a8rOptions['id']] ) ){
					foreach( $_arrMoData['settings']['form'][$a8rOptions['id']] as $_arrA8r ){
						if( in_array( $_arrA8r['name'], array( 'first_name', 'last_name', 'email', 'phone' ) ) && isset( $arrRequest[$_arrA8r['hash']] ) ){
							$params[$_arrA8r['name']]=$arrRequest[$_arrA8r['hash']];
						}
					}
				}
				$params['ip_address'] = @$arrRequest['ip'];
				$params['webinar_id'] = $_arrMoData['settings']['options']['everwebinar_webinar_id'][$a8rOptions['id']];
				$params['schedule'] = 0;
				$params['real_dates'] = 1;
				$everwebinar=new Project_Mooptin_Everwebinar( $a8rOptions['settings']['options']['everwebinar_api_key'] );
				$arrCallback[$a8rOptions['id']]=array( 'callback'=>$everwebinar->addContact($params) );
				$decodeJSON=json_decode( $arrCallback[$a8rOptions['id']]['callback'], true );
				if( isset( $arrCallback[$a8rOptions['id']]['callback']->status ) && $arrCallback[$a8rOptions['id']]['callback']->status != 'success' ){
					$arrCallback[$a8rOptions['id']]['message']=$arrCallback[$a8rOptions['id']]['callback']->message;
				}else{
					$arrCallback[$a8rOptions['id']]['message']="Contact added";
				}
			}
			
			if ( isset($a8rOptions['settings']['integration']) && in_array( 'webinarjam', $a8rOptions['settings']['integration'] ) ){
				$params=array();
				if( isset( $_arrMoData['settings']['form'][$a8rOptions['id']] ) ){
					foreach( $_arrMoData['settings']['form'][$a8rOptions['id']] as $_arrA8r ){
						if( in_array( $_arrA8r['name'], array( 'first_name', 'last_name', 'email', 'phone' ) ) && isset( $arrRequest[$_arrA8r['hash']] ) ){
							$params[$_arrA8r['name']]=$arrRequest[$_arrA8r['hash']];
						}
					}
				}
				$params['ip_address'] = @$arrRequest['ip'];
				$params['webinar_id'] = $_arrMoData['settings']['options']['webinarjam_webinar_id'][$a8rOptions['id']];
				$params['schedule'] = 0;
				$params['real_dates'] = 1;
				$webinarjam=new Project_Mooptin_Webinarjam( $a8rOptions['settings']['options']['webinarjam_api_key'] );
				$arrCallback[$a8rOptions['id']]=array( 'callback'=>$webinarjam->addContact($params) );
				$decodeJSON=json_decode( $arrCallback[$a8rOptions['id']]['callback'], true );
				if( isset( $arrCallback[$a8rOptions['id']]['callback']->status ) && $arrCallback[$a8rOptions['id']]['callback']->status != 'success' ){
					$arrCallback[$a8rOptions['id']]['message']=$arrCallback[$a8rOptions['id']]['callback']->message;
				}else{
					$arrCallback[$a8rOptions['id']]['message']="Contact added";
				}
			}
			
			if ( isset($a8rOptions['settings']['integration']) && in_array( 'convertkit', $a8rOptions['settings']['integration'] ) ){
				$params=array();
				if( isset( $_arrMoData['settings']['form'][$a8rOptions['id']] ) ){
					foreach( $_arrMoData['settings']['form'][$a8rOptions['id']] as $_arrA8r ){
						if( in_array( $_arrA8r['name'], array( 'first_name' ) ) && isset( $arrRequest[$_arrA8r['hash']] ) ){
							$params[$_arrA8r['name']]=$arrRequest[$_arrA8r['hash']];
						}
					}
				}
				$params['ip_address'] = @$arrRequest['ip'];
				$params['webinar_id'] = $_arrMoData['settings']['options']['convertkit_webinar_id'][$a8rOptions['id']];
				$params['schedule'] = 0;
				$params['real_dates'] = 1;
				$convertkit=new Project_Mooptin_Convertkit( $a8rOptions['settings']['options']['convertkit_api_key'] );
				$arrCallback[$a8rOptions['id']]=array( 'callback'=>$convertkit->addContact($params) );
				$decodeJSON=json_decode( $arrCallback[$a8rOptions['id']]['callback'], true );
				if( isset( $arrCallback[$a8rOptions['id']]['callback']->status ) && $arrCallback[$a8rOptions['id']]['callback']->status != 'success' ){
					$arrCallback[$a8rOptions['id']]['message']=$arrCallback[$a8rOptions['id']]['callback']->message;
				}else{
					$arrCallback[$a8rOptions['id']]['message']="Contact added";
				}
			}
			
			if ( isset($a8rOptions['settings']['integration']) && in_array( 'perkzilla', $a8rOptions['settings']['integration'] ) ){
				$params=array(
					'campaign_id' => $_arrMoData['settings']['options']['perkzilla_campaign_id'][$a8rOptions['id']],
					'variables' => array(
						'subscriber_email' => $arrRequest['email'],
						'subscriber_IP' => @$arrRequest['ip']
					)
				);
				$_details=array();
				if( isset( $arrRequest['with_get']['ref'] ) ){
					$perkzilla=new Project_Mooptin_Perkzilla( $a8rOptions['settings']['options']['perkzilla_api_key'] );
					$_details=$perkzilla->getSubscriberDetails($arrRequest['with_get']['ref']);
					$params['variables']['referred_by_refID']=$arrRequest['with_get']['ref'];
					$params['variables']['referred_by_email']=$_details[0]['email'];
				}
				$perkzilla=new Project_Mooptin_Perkzilla( $a8rOptions['settings']['options']['perkzilla_api_key'] );
				$arrCallback[$a8rOptions['id']]=array( 'callback'=>$perkzilla->addContact($params), 'params'=>$params, 'details'=>$_details );
				$decodeJSON=json_decode( $arrCallback[$a8rOptions['id']]['callback'], true );
				if( isset( $arrCallback[$a8rOptions['id']]['callback']->message ) ){
					$arrCallback[$a8rOptions['id']]['message']=$arrCallback[$a8rOptions['id']]['callback']->message;
				}else{
					$arrCallback[$a8rOptions['id']]['message']="Contact added";
				}
			}
			
			if ( isset($a8rOptions['settings']['integration']) && in_array( 'getresponse', $a8rOptions['settings']['integration'] ) ){
				$_sendOptionsGR=$params=array();
				if( isset( $_arrMoData['settings']['form'][$a8rOptions['id']] ) ){
					foreach( $_arrMoData['settings']['form'][$a8rOptions['id']] as $_arrA8r ){
						if( in_array( $_arrA8r['name'], array( 'name', 'email' ) ) && isset( $arrRequest[$_arrA8r['hash']] ) ){
							$params[$_arrA8r['name']]=$arrRequest[$_arrA8r['hash']];
						}elseif( isset( $arrRequest[$_arrA8r['hash']] ) ){
							$_sendOptionsGR[]=array( 'customFieldId' => $_arrA8r['name'], 'value'=>array( $arrRequest[$_arrA8r['hash']] ) );
						}
					}
				}
				$params['campaign'] = array( 'campaignId'=>$_arrMoData['settings']['options']['getresponse_campaign_id'][$a8rOptions['id']] );
				if( !empty( $_sendOptionsGR ) ){
					$params['customFieldValues']=$_sendOptionsGR;
				}
				$params['ipAddress'] = @$arrRequest['ip'];
				$params['dayOfCycle'] = 0;
				$getresponse=new Project_Mooptin_Getresponse( $a8rOptions['settings']['options']['getresponse_api_key'] );
				$arrCallback[$a8rOptions['id']]=array( 'callback'=>$getresponse->addContact($params) );
				$decodeJSON=json_decode( $arrCallback[$a8rOptions['id']]['callback'], true );
				if( isset( $arrCallback[$a8rOptions['id']]['callback']->message ) ){
					$arrCallback[$a8rOptions['id']]['message']=$arrCallback[$a8rOptions['id']]['callback']->message;
				}else{
					$arrCallback[$a8rOptions['id']]['message']="Contact added";
				}
			}
			if ( isset($a8rOptions['settings']['integration']) && in_array( 'gotowebinar', $a8rOptions['settings']['integration'] ) ){
				$params = json_encode( $_sendOptions );
				$_access=json_decode( base64_decode( $a8rOptions['settings']['options']['activation'] ), true );
				$goto=new Project_Mooptin_Gotomeeting( 'call', $_access );
				$arrCallback[$a8rOptions['id']]=array( 'callback'=> 
					$goto->createRegistrants(
						$_arrMoData['settings']['options']['gotowebinar_webinar_id'][$a8rOptions['id']],
						$params
					)+array(
						'params'=>$params,
						'webid'=>$_arrMoData['settings']['options']['gotowebinar_webinar_id'][$a8rOptions['id']],
						'access'=>$_access,
						'http_opt'=>$goto->http_options,
						'send_options'=>$goto->getOptions()
					)
				);
				$_newAccess=$goto->getOptions();
				if( $_access['access_token'] != $_newAccess['access_token'] ){
					$_object=new Project_Mooptin_Autoresponders();
					$a8rOptions['settings']['options']['activation']=base64_encode( json_encode( $_newAccess ) );
					$_object->setEntered( $a8rOptions )->set();
				}
				if( isset( $arrCallback[$a8rOptions['id']]['callback']['registrantKey'] ) ){
					$arrCallback[$a8rOptions['id']]['message']="Contact added with registrantKey ".$arrCallback[$a8rOptions['id']]['callback']['registrantKey'];
				}elseif( isset( $arrCallback[$a8rOptions['id']]['callback']['description'] ) ){
					$arrCallback[$a8rOptions['id']]['message']=$arrCallback[$a8rOptions['id']]['callback']['description'];
				}
			}
//============================================



			if ( isset($a8rOptions['settings']['integration']) && in_array( 'aweber', $a8rOptions['settings']['integration'] ) ){
				$account = null;
				if (!class_exists('AWeberAPI')){
					require_once 'library/AWeberAPI/aweber.php';
				}
				$params=$_sendOptions;
				try {
					$aweber = new AWeberAPI($a8rOptions['settings']['options']['aweber_consumer_key'], $a8rOptions['settings']['options']['aweber_consumer_secret']);
					$account = $aweber->getAccount($a8rOptions['settings']['options']['aweber_access_key'], $a8rOptions['settings']['options']['aweber_access_secret']);
					$subscribers = $account->loadFromUrl('/accounts/'.$account->id.'/lists/'.$_arrMoData['settings']['options']['aweber_listid'][$a8rOptions['id']] . '/subscribers');
					$params['ip_address'] = @$arrRequest['ip'];
					$params['ad_tracking'] = 'Mo Optin';
					$arrCallback[$a8rOptions['id']]=array( 'callback'=>$subscribers->create($params), 'to_url'=>'/accounts/'.$account->id.'/lists/'.$_arrMoData['settings']['options']['aweber_listid'][$a8rOptions['id']] . '/subscribers', 'options'=>$params );
					if( isset( $arrCallback[$a8rOptions['id']]['callback']->data ) && isset( $arrCallback[$a8rOptions['id']]['callback']->data['id'] ) ){
						$arrCallback[$a8rOptions['id']]['message']="Contact added with id ".$arrCallback[$a8rOptions['id']]['callback']->data['id'];
					}
				}catch(Exception $e){
					$account = null;
					$arrCallback[$a8rOptions['id']]['message']=$e->getMessage()."<span data-request='".base64_encode(serialize($params))."'/></span>";
				}
			}
			//=============
			if ( isset($a8rOptions['settings']['integration']) && in_array( 'ontraport', $a8rOptions['settings']['integration'] ) ){
				$_params=$_sendOptions;
				$_params['objectID'] = 0;
				$_params['ip_addy'] = @$arrRequest['ip'];
				if( isset( $_arrMoData['settings']['options']['ontraport_contact_cat'][$a8rOptions['id']] ) ){
					$_params['contact_cat'] = implode( '*/*', $_arrMoData['settings']['options']['ontraport_contact_cat'][$a8rOptions['id']] );
				}
				if( isset( $_arrMoData['settings']['options']['ontraport_sequence'][$a8rOptions['id']] ) ){
					$_params['updateSequence'] = implode( '*/*', $_arrMoData['settings']['options']['ontraport_sequence'][$a8rOptions['id']] );
				}
				$ontraport_url = 'https://api.ontraport.com/1/objects';
				$curl = curl_init();
				curl_setopt($curl, CURLOPT_URL, $ontraport_url);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($curl, CURLOPT_HEADER, 0);
				curl_setopt($curl, CURLOPT_HTTPHEADER, array(
					'Api-Appid: '.$a8rOptions['settings']['options']['ontraport_app_id'],
					'Api-Key: '.$a8rOptions['settings']['options']['ontraport_api_key'],
					'reqType: add'
				));
				curl_setopt($curl, CURLOPT_POST, 1);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $_params);
				$arrCallback[$a8rOptions['id']]=array( 'callback'=>curl_exec($curl) );
				curl_close($curl);
				$decodeJSON=json_decode( $arrCallback[$a8rOptions['id']]['callback'], true );
				if( isset( $decodeJSON['data'] ) && isset( $decodeJSON['data']['id'] ) ){
					$arrCallback[$a8rOptions['id']]['message']="Contact added with id ".$decodeJSON['data']['id'];
				}else{
					$arrCallback[$a8rOptions['id']]['message']="Error check admin for more information";
				}
			}
//===============================================
			if ( isset($a8rOptions['settings']['integration']) && in_array( 'madmimi', $a8rOptions['settings']['integration'] ) ){
				$request = http_build_query(array(
					'email' => $arrRequest['email'],
					'first_name' => $nameInBase,
					'last_name' => '',
					'username' => $a8rOptions['settings']['options']['madmimi_login'],
					'api_key' => $a8rOptions['settings']['options']['madmimi_api_key']
				));
				$curl = curl_init('http://api.madmimi.com/audience_lists/'.$a8rOptions['settings']['options']['madmimi_list_id'].'/add');
				curl_setopt($curl, CURLOPT_POST, 1);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
				curl_setopt($curl, CURLOPT_TIMEOUT, 20);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
				curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
				curl_setopt($curl, CURLOPT_HEADER, 0);
				$arrCallback[$a8rOptions['id']]=curl_exec($curl);
				curl_close($curl);
			}
			
			if ( isset($a8rOptions['settings']['integration']) && in_array( 'sendy', $a8rOptions['settings']['integration'] ) ){
				$request = http_build_query(array(
					'email' => $arrRequest['email'],
					'name' => $nameInBase,
					'list' => $a8rOptions['settings']['options']['sendy_listid'],
					'boolean' => 'true'
				));
				$a8rOptions['settings']['options']['sendy_url'] = rtrim($a8rOptions['settings']['options']['sendy_url'], '/');
				$curl = curl_init($a8rOptions['settings']['options']['sendy_url'].'/subscribe');
				curl_setopt($curl, CURLOPT_POST, 1);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
				curl_setopt($curl, CURLOPT_TIMEOUT, 20);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
				curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
				curl_setopt($curl, CURLOPT_HEADER, 0);
				$arrCallback[$a8rOptions['id']]=curl_exec($curl);
				curl_close($curl);
			}
			
			if ( isset($a8rOptions['settings']['integration']) && in_array( 'benchmark', $a8rOptions['settings']['integration'] ) ){
				$request = http_build_query(array(
					'contacts' => array(
						'email' => $arrRequest['email'],
						'firstname' => $nameInBase,
						'lastname' => ''),
					'optin' => ($a8rOptions['settings']['options']['benchmark_double'] == 'on' ? 1 : 0),
					'listID' => $a8rOptions['settings']['options']['benchmark_list_id'],
					'token' => $a8rOptions['settings']['options']['benchmark_api_key']
				));
				$curl = curl_init('http://www.benchmarkemail.com/api/1.0/?output=php&method=listAddContacts');
				curl_setopt($curl, CURLOPT_POST, 1);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
				curl_setopt($curl, CURLOPT_TIMEOUT, 20);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
				curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
				curl_setopt($curl, CURLOPT_HEADER, 0);
				$arrCallback[$a8rOptions['id']]=curl_exec($curl);
				curl_close($curl);
			}
			
			if ( isset($a8rOptions['settings']['integration']) && in_array( 'activecampaign', $a8rOptions['settings']['integration'] ) ){
				$_params=$_sendOptions;
				if( isset( $_arrMoData['settings']['form'][$a8rOptions['id']] ) ){
					foreach( $_arrMoData['settings']['form'][$a8rOptions['id']] as $_name=>$_arrA8r ){
						if( isset( $arrRequest[$_arrA8r['hash']] ) ){
							$_params[$_arrA8r['new_name']]=$arrRequest[$_arrA8r['hash']];
						}
					}
				}
				$_params['ip4'] = @$arrRequest['ip'];
				$_params['p['.$_arrMoData['settings']['options']['activecampaign_list_id'][$a8rOptions['id']].']']=$_arrMoData['settings']['options']['activecampaign_list_id'][$a8rOptions['id']];
				$_params['api_output'] = 'serialize';
				$_params['api_key'] = $a8rOptions['settings']['options']['activecampaign_api_key'];
				$_params['api_action'] = 'contact_add';
				$request = http_build_query($_params);
				$url = str_replace('https://', 'http://', $a8rOptions['settings']['options']['activecampaign_url']);
				$curl = curl_init($url.'/admin/api.php?api_action=contact_add');
				curl_setopt($curl, CURLOPT_POST, 1);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
				curl_setopt($curl, CURLOPT_TIMEOUT, 20);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
				curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
				curl_setopt($curl, CURLOPT_HEADER, 0);
				$arrCallback[$a8rOptions['id']]=array( 'callback'=>curl_exec($curl) );
				curl_close($curl);
				if( isset( $arrCallback[$a8rOptions['id']]['callback']->message ) ){
					$arrCallback[$a8rOptions['id']]['message']=$arrCallback[$a8rOptions['id']]['callback']->message;
				}else{
					$arrCallback[$a8rOptions['id']]['message']="Contact added";
				}
			}
			
			if ( isset($a8rOptions['settings']['integration']) && in_array( 'interspire', $a8rOptions['settings']['integration'] ) ){
				try {
					$xml = '
<xmlrequest>
<username>'.$a8rOptions['interspire_username'].'</username>
<usertoken>'.$a8rOptions['interspire_token'].'</usertoken>
<requesttype>subscribers</requesttype>
<requestmethod>AddSubscriberToList</requestmethod>
<details>
<emailaddress>'.$arrRequest['email'].'</emailaddress>
<mailinglist>'.$a8rOptions['settings']['options']['interspire_listid'].'</mailinglist>
<format>html</format>
<confirmed>yes</confirmed>';
		if (!empty($a8rOptions['settings']['options']['interspire_nameid'])){
			$xml .= '
<customfields>;
<item>
	<fieldid>'.$a8rOptions['settings']['options']['interspire_nameid'].'</fieldid>
	<value>'.$nameInBase.'</value>
</item>
</customfields>';
		}
		$xml .= '
</details>
</xmlrequest>';
					$curl = curl_init($a8rOptions['settings']['options']['interspire_url']);
					curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
					curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
					curl_setopt($curl, CURLOPT_HEADER, 0);
					curl_setopt($curl, CURLOPT_POST, 1);
					curl_setopt($curl, CURLOPT_POSTFIELDS, $xml);
					$arrCallback[$a8rOptions['id']]=curl_exec($curl);
					curl_close($curl);
				} catch (Exception $e){
				}
			}
		}
		
		$_writer=new Zend_Log_Writer_Stream( Zend_Registry::get('config')->path->absolute->logfiles.'Autoresponders.log' );
		$_writer->setFormatter( new Zend_Log_Formatter_Simple("%timestamp% %priorityName% (%priority%): %message%\r\n") );
		$_logger=new Zend_Log( $_writer );
			
		if( isset( $_arrMoData['user_id'] ) || isset( $arrRequest['letterid'] ) ){
			$_addHiddenData=array();
			foreach( $_arrMoData['settings']['form']['add'] as $_arrElt ){
				if( isset( $_arrElt['flg_hidden'] ) && $_arrElt['flg_hidden']==1 && isset( $arrRequest[$_arrElt['tag']] ) && !empty( $arrRequest[$_arrElt['tag']] ) ){
					$_addHiddenData[$_arrElt['tag']]=$arrRequest[$_arrElt['tag']];
				}
			}
			$_subscriber=new Project_Squeeze_Subscribers($_arrMoData['user_id']);
			$_addDataToLog=array();
			foreach( $arrCallback as $_arId=>$_callback ){
				$_addDataToLog['mo2ar_request_'.$_arrMoData['id'].'_'.$_arId]=base64_encode( serialize( $arrRequest ) );
				$_addDataToLog['mo2ar_ansver_'.$_arrMoData['id'].'_'.$_arId]=base64_encode( serialize( $_callback['callback'] ) );
				$_addDataToLog['mo2ar_message_'.$_arrMoData['id'].'_'.$_arId]=base64_encode( serialize( $_callback['message'] ) );
				
				$_logger->info('-------------request---------------');
				$_logger->info(serialize( $arrRequest ));
				$_logger->info('-------------callback---------------');
				$_logger->info(serialize( $_callback ));
				
			}
			if( !empty( $_addHiddenData ) ){
				$_addDataToLog['mo2ar_hidden_'.$_arrMoData['id']]=base64_encode( serialize( $_addHiddenData ) );
			}
			$_subscriber->setEntered( array(
				'squeeze_id'=>$arrRequest['id'],
				'name'=>$nameInBase,
				'email'=>$arrRequest['email'],
				'phone'=>@$arrRequest['phone'],
				'ip'=>@$arrRequest['ip'],
				'message'=>base64_encode( serialize( $_messageData ) ),
				'param'=>$_addDataToLog,
				'tags'=>$_arrMoData['tags']
			)+$_rtValidation);
			if( !$_subscriber->set() ){
				$return_data['status'] = 'error';
				$return_data['message'] = 'Can\'t save subscriber!';
				echo $jsonp_callback.'('.json_encode($return_data).')';
				if( isset( $_arrMoData['user_id'] ) && !empty( $_arrMoData['user_id'] ) ){
					Core_Users::getInstance()->retrieveFromCashe();
				}
				return false;
			}
		}
		//? было вынесено из цикла - странно
		if( !isset( $arrRequest['letterid'] ) ){
			$return_data = array();
			$return_data['status'] = 'OK';
			// update url data
			preg_match_all( '|\[\[([a-zA-Z0-9: ]+)\]\]|ims', $a8rOptions['settings']['options']['return_url'], $_match);
			foreach( $_match[1] as $_data ){
				$_defaultValue=$_name='';
				if( strpos( $_data, ':' ) !== false ){
					$_defaultValue=explode( ':',  $_data );
					$_name=$_defaultValue[0];
					$_defaultValue=$_defaultValue[1];
				}else{
					$_name=$_data;
				}
				if( isset( $arrRequest[$_name] ) && !empty( $arrRequest[$_name] ) ){
					$_replace=htmlspecialchars( $arrRequest[$_name] );
				}else{
					$_replace=$_defaultValue;
				}
				$a8rOptions['settings']['options']['return_url']=preg_replace( '|\[\['.quotemeta($_data).'\]\]|ims', $_replace, $a8rOptions['settings']['options']['return_url'] );
			}
			$return_data['return_url'] = $a8rOptions['settings']['options']['return_url'];
			// -------
			$return_data['close_delay'] = 0;// 1000*intval($a8rOptions['settings']['options']['close_delay']);  close_delay-использовался только в Exquisite popups - его уже не используем
			echo $jsonp_callback.'('.json_encode($return_data).')';
		}
		// было вынесено из цикла - странно
		if( isset( $_arrMoData['user_id'] ) && !empty( $_arrMoData['user_id'] ) ){
			Core_Users::getInstance()->retrieveFromCashe();
		}
		return;
	} // end foreach
}
?>