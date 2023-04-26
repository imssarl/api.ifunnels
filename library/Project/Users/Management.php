<?php
/**
 * Users Management
 */
class Project_Users_Management extends Core_Users_Management {


	protected $_table='u_users';

	protected $_fields=array( 'id', 'parent_id', 'package_id','flg_phone', 'flg_active', 'flg_confirm', 'flg_approve', 'flg_cancel', 'flg_expire','flg_rights','flg_source','flg_sended','flg_maintenance', 'dpa_agree_date', 'dpa_agree_ip',
		'amount', 'email', 'passwd', 'nickname', 'mm_id', 'lang', 'timezone', 'code_confirm', 'code_forgot', 'code_payment', 'adsenseid', 'phone', 'menu_settings',
		'pagging_rows', 'pagging_links',
		'popup_height', 'popup_width',
		'domains_parked', 'domains_ordered', 'forgot', 'expiry','buyer_name','buyer_surname','buyer_phone','buyer_country','buyer_province','buyer_city','buyer_address','buyer_zip', 'twilio', 'fb_user_id', 'fb_messenger_id', 'flg_allow_sub', 
		'validation_limit', 'validation_realtime', 'validation_mounthly', 'validation_global',
		'flg_unsubscribe', 
		'contact_limit', 'hosting_limit', 'automation_limit', 'subaccounts_limit', 'zonterest_limit',
		'settings',
		'edited', 'added' );

	private $_withPackage=false; // с названием пакета
	private $_forShutOf=false; // закончилася оплаченый период
	private $_lessThanExpiry=false; // за _time до окончания оплаченого периода
	private $_withSended=false; // Статус отправки писем
	private $_withMemberMouseId=false; // MemberMouse API
	private $_withFacebookId=false; // Facebook Messanger Id
	private $_withFacebookMessengerId=false; // Facebook Messanger Id
	private $_withoutUnsubscribe=false; // Пользователи с неотключенной подпиской
	private $_withPhone=false;
	private $_withConfirmPhone=false;
	private $_likeNickname=false;
	private $_onlyMaintenance=false;
	private $_onlyForValidation=false;
	private $_withCallInfo=false;
	private $_onlyExpiry=false;
	public static $sendedFlg=array('clear'=>0,'notify'=>1,'shutoff'=>2);

	// размещение блоков в главном меню
	private $_menuSettings=array( 'left_box'=>array( 1,2,3,4,5 ), 'right_box'=>array( 6,7,8,9,10 ) );


	public static function setDomainParked( $_userId ){
		if( empty($_userId) ){
			throw new Project_Users_Exception('Can\'t find user');
		}
		Core_Sql::setExec('UPDATE u_users SET domains_parked=domains_parked+1 WHERE id='.$_userId);
	}

	public static function setDomainOrdered( $_userId ){
		if( empty($_userId) ){
			throw new Project_Users_Exception('Can\'t find user');
		}
		Core_Sql::setExec('UPDATE u_users SET domains_ordered=domains_ordered+1 WHERE id='.$_userId);
	}

	/**
	 * Меняет пароль пользователю, и отправляет ему его по почте. Письмо приходит такое же как при регистрации
	 * @param $_userId
	 * @return bool
	 */
	public function changePassword( $_userId ){
		if(empty($_userId)||!$this->withIds( $_userId )->onlyOne()->getList( $arrProfile )->checkEmpty() ){
			return false;
		}
		$_passwd=Core_Users::generatePassword();
		$arrProfile['passwd']=$_passwd;
		$arrProfile['password']=$_passwd;
		if ( !$this->setEntered( $arrProfile )->set() ) {
			return Core_Data_Errors::getInstance()->setError( 'can not update profile' );
		}
		// отправляем пароль пользователю
		$_mailer=new Core_Mailer();
		if ( !$_mailer
			->setVariables( $this->_data->filtered )
			->setTemplate( 'api_registration_complete' )
			->setSubject( 'Your Account Has Been Created' )
			->setPeopleTo( array( 'email'=>$arrProfile['email'], 'name'=>$arrProfile['nickname'] ) )
			->setPeopleFrom( array( 'name' => Zend_Registry::get('config')->engine->project_sysemail->name, 'email' => Zend_Registry::get('config')->engine->project_sysemail->email ) )
			->sendOneToMany() ) {
			return Core_Data_Errors::getInstance()->setError( 'email don\'t send' );
		}
		return true;

	}

	/**
	 * Подтверждение телефона
	 * @param $_code
	 * @return bool
	 */
	public function confirmPhone( $_code ){
		if( Core_Users::$info['code_confirm']!=$_code ){
			return Core_Data_Errors::getInstance()->setError('Pin-code is not correct');
		}
		$_arrProfile=Core_Users::$info;
		$_arrProfile['flg_phone']=1;
		unset( $_arrProfile['passwd']);
		$this->setEntered( $_arrProfile )->set();
		Core_Users::getInstance()->reload();
		return true;
	}
	
	/**
	 * Добавление Twilio данных
	 * @param $_code
	 * @return bool
	 */
	public function updateTwilio( $_arrTwilio=array() ){
		if( !isset( $_arrTwilio['sid'] ) || !isset( $_arrTwilio['token'] ) ){
			return Core_Data_Errors::getInstance()->setError('Empty Data!');
		}
		if( !isset( Core_Users::$info['twilio'] ) ){
			$_arrNulls=Core_Sql::getAssoc("SELECT NULL FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = 'u_users' AND column_name = 'twilio';");
			if( count( $_arrNulls ) == 0 ){
				Core_Sql::setExec("ALTER TABLE `u_users` ADD `twilio` TEXT NULL;");
			}
		}
		$_arrProfile=Core_Users::$info;
		$_arrProfile['twilio']=base64_encode( json_encode( $_arrTwilio ) );
		unset( $_arrProfile['passwd']);
		$this->setEntered( $_arrProfile )->set();
		Core_Users::getInstance()->reload();
		return true;
	}

	/**
	 * Проверка активности подписок пользователей.
	 *
	 * @static
	 *
	 */
	public static function checkeExpiry(){
		$_obj=new self();
		$_obj
				->onlyActive()
				->onlyIds()
				->withIds( Core_Sql::getField(Core_Acs::haveRightAccess(array('payments_@_checkeexpiry'))) )
				->getList( $_arrIds );
		$writer=new Zend_Log_Writer_Stream( 'php://output' );
		$writer->setFormatter( new Zend_Log_Formatter_Simple("%timestamp% %priorityName% (%priority%): %message%<br/>\r\n") );
		$_logger=new Zend_Log( $writer );
		$_subscr=new Core_Payment_Subscription();
		$_package=new Core_Payment_Package();
		$_groups=new Core_Acs_Groups();
		$_groups->getIdsBySysName($_groupIds, array('Default') );
		$_defaultGroup[$_groupIds[0]]='Default';
		$_logger->info('START CHECK EXPIRY');
		foreach( $_arrIds as $_userId ){
			if( !$_subscr->withoutLifetime()->onlyIds()->onlyPackageIds()->onlyExpiry()->forUser( $_userId )->getList( $_arrPackageIds )->checkEmpty() ){
				continue;
			}
			$_package->onlyIds()->onlyGroupIds()->withIds( $_arrPackageIds )->getList( $_arrGroupIds );
			if(empty($_arrGroupIds)){
				continue;
			}
			$_acl=new Core_Acs_Groups();
			$_acl->withIds( $_userId )->getGroupByUserId( $_currentGroup );
			if( $_currentGroup==$_defaultGroup ){
				continue;
			}
			$_logger->info('user ['.$_userId.'] have '.count($_arrPackageIds).' expired subsribtion.');
			Core_Sql::setExec('DELETE FROM u_link WHERE group_id IN ('.join(',', $_arrGroupIds ).') AND user_id='.$_userId );
			if( !$_groups->withIds( $_userId )->getGroupByUserId( $_tmp )) {
				$_groups->withIds( $_userId )->setGroupByName( $_defaultGroup );
			}
		}
		$_logger->info('END CHECK EXPIRY');
	}

	public function import(){
		if(empty($this->_withGroups)){
			return false;
		}
		$_groups=new Core_Acs_Groups();
		$_groups->onlyIdAndSysNames()->withIds( $this->_withGroups )->getList( $_arrGroups );
		if( !$this->withGroups( $_arrGroups )->getList( $_arrUsers )->checkEmpty() ){
			return false;
		}
		$_package=new Core_Payment_Package();
		$_subscr=new Core_Payment_Subscription();
		$_content='';
		foreach( $_arrUsers as $_user ){
			$_tmpSIds=array();
			$_tmpG=array();
			$_tmpP=array();
			$_groups->withIds($_user['id'])->getGroupByUserId($_tmpG);
			$_subscr->onlyIds()->onlyPackageIds()->forUser( $_user['id'] )->getList( $_tmpSIds );
			$_package->toSelect()->withIds( $_tmpSIds )->getList( $_tmpP );
			$_content.=$_user['email'].';'.$_user['buyer_name'].';'.$_user['buyer_surname'].';'.date('m/d/Y',$_user['added']).';['.join('][',$_tmpG).'];['.join('][',$_tmpP )."];\n";
		}
		ob_end_clean();
		header( "Content-type: application/octet-stream" );
		header( "Content-disposition: attachment; filename=users-list".date('Y-m-d').".csv");
		echo $_content;
		die();
	}

	public function activate(){
		if( empty($this->_withIds) ){
			return false;
		}
		Core_Sql::setExec('UPDATE '.$this->_table.' SET flg_active=1 WHERE id IN ('.Core_Sql::fixInjection($this->_withIds).')');
		$_groups=new Core_Acs_Groups();
		if( is_array($this->_withIds) ){
			foreach( $this->_withIds as $_userId ){
				$_groups->withIds( $_userId )->removeGroupByName( Core_Acs::$inactive );
			}
		} else {
			$_groups->withIds( $this->_withIds )->removeGroupByName( Core_Acs::$inactive );
		}
		return true;
	}

	public function deactivate(){
		if( empty($this->_withIds) ){
			return false;
		}
		Core_Sql::setExec('UPDATE '.$this->_table.' SET flg_active=0 WHERE id IN ('.Core_Sql::fixInjection($this->_withIds).')');
		$_groups=new Core_Acs_Groups();
		if( is_array($this->_withIds) ){
			foreach( $this->_withIds as $_userId ){
				$_groups->withIds( $_userId )->addGroupByName( Core_Acs::$inactive );
			}
		} else {
			$_groups->withIds( $this->_withIds )->addGroupByName( Core_Acs::$inactive );
		}
		return true;
	}

	public function getSettings( &$arrRes ) {
		$arrRes['arrSettings']['pagging_rows']=$arrRes['pagging_rows'];
		$arrRes['arrSettings']['pagging_links']=$arrRes['pagging_links'];
		$arrRes['arrSettings']['popup_width']=$arrRes['popup_width'];
		$arrRes['arrSettings']['popup_height']=$arrRes['popup_height'];
		$arrRes['arrSettings']['menu_settings']=( empty( $arrRes['menu_settings'] )? $this->_menuSettings :unserialize( $arrRes['menu_settings'] ) );
		return !empty( $arrRes );
	}

	public function shutOffAccounts( $_arrIds ){
		if(empty($_arrIds)){
			return;
		}
		return Core_Sql::setExec('UPDATE '.$this->_table.' SET flg_expire=1 WHERE id IN ('. Core_Sql::fixInjection($_arrIds) .')');
	}

	/**
	 * Статус сообщений пользователям.
	 * 0 - письмо не отправлено.
	 * 1 - предупреждение за о отключении отправлено.
	 * 2 - сообщение о отключении отправлено.
	 * @param int $_flgSended
	 * @return bool
	 */
	public function setSended( $_flgSended=0 ){
		if( empty($this->_withIds) ){
			return false;
		}
		Core_Sql::setExec('UPDATE '.$this->_table.' SET flg_sended='.$_flgSended.' WHERE id IN ('. Core_Sql::fixInjection($this->_withIds) .')');
		return true;
	}

	/**
	 * Статус подтверждения телефона.
	 * 0 - не проверен.
	 * 1 - проверен.
	 * 2 - ввели не верный PIN.
	 * @param int $_flgPhone
	 * @return bool
	 */
	public function setFlgPhone( $_flgPhone=0 ){
		if( empty($this->_withIds) ){
			return false;
		}
		Core_Sql::setExec('UPDATE '.$this->_table.' SET flg_phone='.$_flgPhone.' WHERE id IN ('. Core_Sql::fixInjection($this->_withIds) .')');
		return true;
	}

	public function setApprove( $_flgApprove=0 ){
		if( empty($this->_withIds) ){
			return false;
		}
		Core_Sql::setExec('UPDATE '.$this->_table.' SET flg_approve='.intval($_flgApprove).',approve='.time().' WHERE id IN ('. Core_Sql::fixInjection($this->_withIds) .')');
		Core_Users::getInstance()->reload();
		$this->init();
		return true;
	}

	/**
	 * Включает выключает программу обслуживания для пользователей.
	 * @param bool $boolean
	 * @return bool
	 */
	public function setMaintenance( $boolean=false ){
		if( empty($this->_withIds) ){
			return false;
		}
		$ids=$this->_withIds;
		$_groups=new Core_Acs_Groups();
		if( $boolean===false ){
			Core_Sql::setExec('UPDATE '.$this->_table.' SET flg_maintenance=0 WHERE id='.Core_Sql::fixInjection($ids) );
			$_groups->withIds( $this->_withIds )->removeGroupByName(Core_Acs::$maintenance);
		} else {
			Core_Sql::setExec('UPDATE '.$this->_table.' SET flg_maintenance=1 WHERE id='. Core_Sql::fixInjection($ids) );
			$_groups->withIds( $this->_withIds )->addGroupByName(Core_Acs::$maintenance);
		}
//		$this->withIds( $ids )->setSended();
//		$this->withIds( $ids )->setExpiry(0,0);
//		$this->init();
		return true;
	}

	protected function beforeSet(){
		$this->_data->setFilter( array( 'trim', 'clear' ) );
		if( isset( $this->_data->filtered['settings'] ) ){
			$this->_data->setElement('settings', base64_encode( serialize( $this->_data->filtered['settings'] ) ) );
		}
		return parent::beforeSet();
	}

	protected function afterSet(){
		if( isset( $this->_data->filtered['flg_maintenance'] ) && $this->_data->filtered['flg_maintenance']===0 ){
			$this->withIds( $this->_data->filtered['id'] )->setMaintenance( false );
		}
		if( isset( $this->_data->filtered['zonterest_limit'] ) && $this->_data->filtered['zonterest_limit'] !=-1 ){
			$_oldUser = null;
			if( isset( Core_Users::$info['id'] ) ){
				$_oldUser=Core_Users::$info['id'];
			}
			Core_Users::getInstance()->setById( $this->_data->filtered['id'] );
			$model=new Project_Sites( Project_Sites::NCSB );
			if( $_oldUser != null ){
				Core_Users::getInstance()->setById( $_oldUser );
			}
			$model
				->withOrder( 'added--dn' )
				->withUserId( $this->_data->filtered['id'] )
				->withPlacementId( 8484 )
				->withCategory( 'Zonterest' ) // 641
				->getList( $_arrZonterestList );
			$_arrDel=array();
			$_counter=0;
			foreach( $_arrZonterestList as $_site ){
				if( $_counter < $this->_data->filtered['zonterest_limit'] ){
					$_counter++;
				}else{
					$_arrDel[]=$_site['id'];
				}
			}
			if ( !empty( $_arrDel ) ) {
				$model->delSites( $_arrDel );
			}
		}
		return parent::afterSet();
	}

	/**
	 * Set expiry
	 * @param int $_time - count month
	 * @param int $_flgExpire
	 * @return bool
	 */
	public function setExpiry( $_time=0,$_flgExpire=1 ){
		if( empty($this->_withIds) ){
			return false;
		}
		Core_Sql::setExec('UPDATE '.$this->_table.' SET expiry='.$_time.',flg_expire='.$_flgExpire.' WHERE id IN ('.Core_Sql::fixInjection($this->_withIds) .')');
	}

	public function withPackage( $_mixIds ){
		if( !empty($_mixIds)){
			$this->_withPackage=$_mixIds;
		}
		return $this;
	}

	public function forShutOf(){
		$this->_forShutOf=true;
		return $this;
	}

	public function lessThanExpiry( $_time ){
		$this->_lessThanExpiry=$_time;
		return $this;
	}

	public function withoutUnsubscribe(){
		$this->_withoutUnsubscribe=true;
		return $this;
	}

	public function withSended( $_flg ){
		$this->_withSended=$_flg;
		return $this;
	}

	public function withMemberMouseId( $_flg ){
		$this->_withMemberMouseId=$_flg;
		return $this;
	}

	public function withFacebookId( $_flg ){
		$this->_withFacebookId=$_flg;
		return $this;
	}

	public function withFacebookMessengerId( $_flg ){
		$this->_withFacebookMessengerId=$_flg;
		return $this;
	}

	private $_withActivationCode=false;
	
	public function withActivationCode( $_flg=false ){
		$this->_withActivationCode=$_flg;
		return $this;
	}

	public function likeNickname( $_str ){
		$this->_likeNickname=$_str;
		return $this;
	}

	public function onlyExpiry(){
		$this->_onlyExpiry=true;
		return $this;
	}

	public function withPhone( $_str ){
		$this->_withPhone=$_str;
		return $this;
	}

	public function onlyMaintenance(){
		$this->_onlyMaintenance=true;
		return $this;
	}

	public function onlyForValidation(){
		$this->_onlyForValidation=true;
		return $this;
	}

	public function withConfirmPhone(){
		$this->_withConfirmPhone=true;
		return $this;
	}

	public function withCallInfo(){
		$this->_withCallInfo=true;
		return $this;
	}

	protected function init() {
		parent::init();
		$this->_withPackage=false;
		$this->_forShutOf=false;
		$this->_lessThanExpiry=false;
		$this->_withSended=false;
		$this->_likeNickname=false;
		$this->_withoutUnsubscribe=false;
		$this->_onlyExpiry=false;
		$this->_onlyMaintenance=false;
		$this->_onlyForValidation=false;
		$this->_withPhone=false;
		$this->_withConfirmPhone=false;
		$this->_withCallInfo=true;
	}

	protected function assemblyQuery() {
		parent::assemblyQuery();
		if( $this->_withPackage ){
			$this->_crawler->set_where('d.id IN (SELECT ps.user_id FROM p_subscription ps WHERE ps.package_id IN ('. Core_Sql::fixInjection($this->_withPackage) .'))');
		}
		if ( $this->_toSelect ) {
			$this->_crawler->clean_select();
			$this->_crawler->set_select( 'd.id, d.email' );
		}
		if( $this->_withSended ){
			$this->_crawler->set_where('d.flg_sended='.Core_Sql::fixInjection( $this->_withSended ) );
		}
		if( $this->_withFacebookId ){
			$this->_crawler->set_where('d.fb_user_id='.Core_Sql::fixInjection( $this->_withFacebookId ) );
		}
		if( $this->_withFacebookMessengerId ){
			$this->_crawler->set_where('d.fb_messenger_id='.Core_Sql::fixInjection( $this->_withFacebookMessengerId ) );
		}
		if( $this->_withActivationCode ){
			$this->_crawler->set_where('d.passwd='.Core_Sql::fixInjection( str_replace( '-', '', $this->_withActivationCode ) ) );
		}
		if( $this->_withMemberMouseId ){
			$this->_crawler->set_where('d.mm_id='.Core_Sql::fixInjection( $this->_withMemberMouseId ) );
		}
		if( $this->_withPhone ){
			$this->_crawler->set_where('d.buyer_phone='.Core_Sql::fixInjection( $this->_withPhone) );
		}
		if( $this->_withConfirmPhone ){
			$this->_crawler->set_where('d.flg_phone=1');
		}
		if( $this->_withoutUnsubscribe ){
			$this->_crawler->set_where('d.flg_unsubscribe=0' );
		}
		if( $this->_forShutOf ){
			$this->_crawler->set_where('d.expiry<'.time().' AND d.flg_expire=0 AND d.flg_source<2');
		}
		if( $this->_lessThanExpiry ){
			$this->_crawler->set_where('d.flg_expire=0 AND d.expiry<'.Core_Sql::fixInjection($this->_lessThanExpiry).' AND d.expiry>'.time() );
		}
		if( $this->_onlyExpiry ){
			$this->_crawler->set_where('d.flg_expire=1');
		}
		if( $this->_likeNickname ){
			$this->_crawler->set_where("d.nickname LIKE ".Core_Sql::fixInjection('%'.$this->_likeNickname.'%')." OR d.buyer_name LIKE ".Core_Sql::fixInjection('%'.$this->_likeNickname.'%')." OR d.buyer_surname LIKE ".Core_Sql::fixInjection('%'.$this->_likeNickname.'%')."");
		}
		if( $this->_onlyForValidation ){
			$this->_crawler->set_where("( d.validation_mounthly<".time()." OR d.validation_global<".time()." ) AND d.validation_mounthly<>0 AND d.validation_global<>0");
		}
		if( $this->_onlyMaintenance ){
			$this->_crawler->set_where('d.flg_maintenance=1');
		}
		if( $this->_withCallInfo ){
			$this->_crawler->set_select('(SELECT COUNT(*) FROM ccs_voice v WHERE v.user_id=d.id AND v.Direction="outbound-api") as outbound_voice');
			$this->_crawler->set_select('(SELECT COUNT(*) FROM ccs_voice v WHERE v.user_id=d.id AND v.Direction="inbound-api") as inbound_voice');
			$this->_crawler->set_select('(SELECT COUNT(*) FROM ccs_sms s WHERE s.user_id=d.id AND s.Direction="outbound-api") as outbound_sms');
			$this->_crawler->set_select('(SELECT COUNT(*) FROM ccs_sms s WHERE s.user_id=d.id AND s.Direction="inbound-api") as inbound_sms');
			$this->_crawler->set_select('(SELECT ROUND(SUM(cost),2) FROM ccs_sms s1 WHERE s1.user_id=d.id) as sms_cost');
			$this->_crawler->set_select('(SELECT ROUND(SUM(cost),2) FROM ccs_voice v1 WHERE v1.user_id=d.id) as voice_cost');
		}
	}

	public function getList( &$arrRes ){
		parent::getList( $arrRes );
		if( isset( $arrRes['id'] ) && !empty( $arrRes['id'] ) ){
			$this->getSettings( $arrRes );
			if(!empty($arrRes['parent_id'])){
				$this->onlyOne()->withIds( $arrRes['parent_id'] )->getList($arrRes['parent']);
			}
			if( !empty( $arrRes['twilio'] ) ){
				$arrRes['twilio']=json_decode( base64_decode( $arrRes['twilio'] ), true );
			}
			$arrRes['settings'] = unserialize(base64_decode($arrRes['settings']));
		}elseif( is_array($arrRes[0])) {
			foreach( $arrRes as &$_user ){
				if( isset( $_user['settings'] ) && !empty( $_user['settings'] ) ){
					if(!empty($_user['parent_id'])){
						$this->onlyOne()->withIds( $_user['parent_id'] )->getList($_user['parent']);
					}
					if( !empty( $_user['twilio'] ) ){
						$_user['twilio']=json_decode( base64_decode( $_user['twilio'] ), true );
					}
					$_user['settings'] = unserialize(base64_decode($_user['settings']));
				}
			}
		}
		return $this;
	}

	public function del(){
		if(empty($this->_withIds)){
			return false;
		}
		if(!is_array($this->_withIds)){
			$this->_withIds=array($this->_withIds);
		}
		foreach( $this->_withIds as $_userId ){
			Core_Users::getInstance()->withCashe()->setById( $_userId );
			if( Core_Users::$info['id']<=0 ){
				continue;
			}
			$_dir=Core_Users::getInstance()->getDtaDirName();
			$_tmpDir=Core_Users::getInstance()->getTmpDirName();
			if( is_dir($_dir) ){
				Core_Files::rmDir( $_dir );
			}
			if( is_dir($_tmpDir) ){
				Core_Files::rmDir( $_tmpDir );
			}
		}
		self::deleteContent( $this->_withIds );
		Core_Users::getInstance()->retrieveFromCashe();
		return parent::del();
	}
	
	public static function deleteContent( $_arrIds ){
		set_time_limit(0);
		if( empty($_arrIds) ){
			return false;
		}
		// Delete all BF
		$_siteIds=Core_Sql::getField('SELECT id FROM bf_blogs WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		if( !empty($_siteIds) ){
			$_model=new Project_Sites(Project_Sites::BF);
			$_model->delSites( $_siteIds );
		}
		Core_Sql::setExec('DELETE FROM bf_blog2update WHERE blog_id IN (SELECT id FROM bf_blogs WHERE user_id IN ('.Core_Sql::fixInjection( $_arrIds).'))');
		Core_Sql::setExec('DELETE FROM bf_ext_category WHERE blog_id IN (SELECT id FROM bf_blogs WHERE user_id IN ('.Core_Sql::fixInjection( $_arrIds).'))');
		Core_Sql::setExec('DELETE FROM bf_ext_comments WHERE blog_id IN (SELECT id FROM bf_blogs WHERE user_id IN ('.Core_Sql::fixInjection( $_arrIds).'))');
		Core_Sql::setExec('DELETE FROM bf_ext_pages WHERE blog_id IN (SELECT id FROM bf_blogs WHERE user_id IN ('.Core_Sql::fixInjection( $_arrIds).'))');
		Core_Sql::setExec('DELETE FROM bf_ext_post2cat WHERE blog_id IN (SELECT id FROM bf_blogs WHERE user_id IN ('.Core_Sql::fixInjection( $_arrIds).'))');
		Core_Sql::setExec('DELETE FROM bf_ext_posts WHERE blog_id IN (SELECT id FROM bf_blogs WHERE user_id IN ('.Core_Sql::fixInjection( $_arrIds).'))');
		Core_Sql::setExec('DELETE FROM bf_plugin2blog_link WHERE blog_id IN (SELECT id FROM bf_blogs WHERE user_id IN ('.Core_Sql::fixInjection( $_arrIds).'))');
		Core_Sql::setExec('DELETE FROM bf_theme2blog_link WHERE blog_id IN (SELECT id FROM bf_blogs WHERE user_id IN ('.Core_Sql::fixInjection( $_arrIds).'))');
		Core_Sql::setExec('DELETE FROM bf_plugin2user_link WHERE user_id IN('.Core_Sql::fixInjection( $_arrIds).')');
		Core_Sql::setExec('DELETE FROM bf_theme2user_link WHERE user_id IN('.Core_Sql::fixInjection( $_arrIds).')');
		Core_Sql::setExec('DELETE FROM bf_updater WHERE user_id IN('.Core_Sql::fixInjection( $_arrIds).')');
		Core_Sql::setExec('DELETE FROM bf_blogs WHERE user_id IN('.Core_Sql::fixInjection( $_arrIds).')');


		Core_Sql::setExec('DELETE FROM category_articles_tree WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		Core_Sql::setExec('DELETE FROM category_articles_tree_fr WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		Core_Sql::setExec('DELETE FROM category_articles_tree_sp WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		Core_Sql::setExec('DELETE FROM category_articles_tree_sp WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		Core_Sql::setExec('DELETE FROM category_blogfusion_tree WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		Core_Sql::setExec('DELETE FROM category_category WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		Core_Sql::setExec('DELETE FROM category_clickbank_tree WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		Core_Sql::setExec('DELETE FROM category_plr_tree WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');


		Core_Sql::setExec('DELETE FROM content_organizer WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		Core_Sql::setExec('DELETE FROM content_setting WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		Core_Sql::setExec('DELETE FROM content_video WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');


		Core_Sql::setExec('DELETE p,c,t FROM co_parts p LEFT JOIN co_click c ON c.part_id=p.id LEFT JOIN co_trackurls t ON t.part_id=p.id WHERE p.snippet_id IN (SELECT id FROM co_snippets WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).'))');
		Core_Sql::setExec('DELETE FROM co_snippets WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');

		Core_Sql::setExec('DELETE c,s,co,b FROM cs_project2category c
		LEFT JOIN cs_content2site s ON c.project_id=s.project_id
		LEFT JOIN cs_content co ON c.project_id=co.project_id
		LEFT JOIN cs_bsites b ON c.project_id=b.project_id
		WHERE c.project_id IN (SELECT id FROM cs_project WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).'))');
		Core_Sql::setExec('DELETE FROM cs_project WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		Core_Sql::setExec('DELETE FROM cs_points WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		Core_Sql::setExec('DELETE FROM cs_statistic WHERE site_id IN (SELECT id FROM cs_sites WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).'))');
		Core_Sql::setExec('DELETE FROM cs_sites WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		
		
		Core_Sql::setExec('DELETE FROM es_cnb WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		Core_Sql::setExec('DELETE FROM es_psb WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');

		
		$_siteIds=Core_Sql::getField('SELECT id FROM es_ncsb WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		if( !empty($_siteIds) ){
			$_model=new Project_Sites(Project_Sites::NCSB);
			$_model->delSites( $_siteIds );
		}
		Core_Sql::setExec('DELETE FROM es_ncsb WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		
		$_siteIds=Core_Sql::getField('SELECT id FROM es_nvsb WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		if( !empty($_siteIds) ){
			$_model=new Project_Sites(Project_Sites::NVSB);
			$_model->delSites( $_siteIds );
		}
		Core_Sql::setExec('DELETE FROM es_nvsb WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		
		Core_Sql::setExec('DELETE s,d FROM es_opt_spots s LEFT JOIN es_opt_data2spot d ON s.id=d.spot_id  WHERE s.user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		Core_Sql::setExec('DELETE FROM es_template2user  WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		Core_Sql::setExec('DELETE FROM file_group  WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		Core_Sql::setExec('DELETE FROM file_info  WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		Core_Sql::setExec('DELETE c,s,r,t FROM hi_campaign c
		LEFT JOIN hi_campaigns2split s ON c.id=s.campaign_id
		LEFT JOIN hi_referral r ON r.campaign_id=c.id
		LEFT JOIN hi_trackurls t ON t.campaign_id=c.id
		 WHERE c.user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		Core_Sql::setExec('DELETE FROM hi_split WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		Core_Sql::setExec('DELETE p,s,c,a FROM pub_project p
		LEFT JOIN pub_schedule s ON s.project_id=p.id
		LEFT JOIN pub_cache c ON c.project_id=p.id
		LEFT JOIN pub_autosites a ON a.project_id=p.id
		WHERE p.user_id IN ('.Core_Sql::fixInjection($_arrIds).')');

		Core_Sql::setExec('DELETE FROM pa_domains  WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		Core_Sql::setExec('DELETE FROM p_orders  WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		Core_Sql::setExec('DELETE FROM p_history  WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		Core_Sql::setExec('DELETE FROM p_subscription  WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		$_placeIds=Core_Sql::getField('SELECT id FROM site_placement  WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		if( !empty($_placeIds) )
		foreach($_placeIds as $_id ){
			$_place=new Project_Placement();
			$_place->withIds($_id)->del();
		}
		
		//Core_Sql::setExec('DELETE FROM squeeze_campaigns  WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		$_lpbs=Core_Sql::getField('SELECT id FROM squeeze_campaigns  WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		if( !empty($_lpbs) ){
			foreach($_lpbs as $_id ){
				$_squeeze=new Project_Squeeze();
				$_squeeze->withIds( $_id )->del_squeeze();
			}
		}
		
		Core_Sql::setExec('DELETE FROM synnd_campaigns  WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		Core_Sql::setExec('DELETE FROM synnd_content_old  WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		Core_Sql::setExec('DELETE FROM tc_categories  WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		Core_Sql::setExec('DELETE FROM u_session  WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		Core_Sql::setExec('DELETE FROM u_item  WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		Core_Sql::setExec('DELETE FROM u_link  WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		Core_Sql::setExec('DELETE FROM u_link_old  WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');

		// Delete from old table.
		Core_Sql::setExec('DELETE FROM hct_admin_settings_tb WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		Core_Sql::setExec('DELETE FROM hct_affiliate_pages WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		Core_Sql::setExec('DELETE FROM hct_am_article WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		Core_Sql::setExec('DELETE FROM hct_am_article_snippets WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		Core_Sql::setExec('DELETE FROM hct_am_savedcode WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		Core_Sql::setExec('DELETE FROM hct_as_article WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		Core_Sql::setExec('DELETE FROM hct_as_category WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		Core_Sql::setExec('DELETE FROM hct_as_directory WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		Core_Sql::setExec('DELETE FROM hct_as_profile WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		Core_Sql::setExec('DELETE FROM hct_as_submission WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		Core_Sql::setExec('DELETE FROM hct_as_url WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		Core_Sql::setExec('DELETE FROM hct_ccp_ad WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		Core_Sql::setExec('DELETE FROM hct_ccp_admin_settings_tb WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		Core_Sql::setExec('DELETE FROM hct_ccp_affiliatenetwork WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		Core_Sql::setExec('DELETE FROM hct_ccp_affn_user_details WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		Core_Sql::setExec('DELETE FROM hct_ccp_campaign WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		Core_Sql::setExec('DELETE FROM hct_ccp_portals_sites_tb WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		Core_Sql::setExec('DELETE FROM hct_ccp_projects_tb WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		Core_Sql::setExec('DELETE FROM hct_ccp_salesdata WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		Core_Sql::setExec('DELETE FROM hct_ccp_site WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		Core_Sql::setExec('DELETE FROM hct_ftp_details_tb WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		Core_Sql::setExec('DELETE FROM hct_kwd_savedlist WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		Core_Sql::setExec('DELETE FROM hct_mail_send_tb WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		Core_Sql::setExec('DELETE FROM hct_profiles WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		Core_Sql::setExec('DELETE FROM hct_search WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		Core_Sql::setExec('DELETE FROM hi_campaign WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		Core_Sql::setExec('DELETE FROM hi_split WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		Core_Sql::setExec('DELETE FROM iam_users2sites WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		Core_Sql::setExec('DELETE FROM jin_user_lmem WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
		Core_Sql::setExec('DELETE FROM art_variants WHERE user_id IN ('.Core_Sql::fixInjection($_arrIds).')');
	}
}
?>