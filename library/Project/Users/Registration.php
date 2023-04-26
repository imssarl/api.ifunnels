<?php


/**
 * Users Management
 */
class Project_Users_Registration {

	private $_withGroups=array();

	/**
	 * Object Project_Users_Management
	 * @var Project_Users_Management Object
	 */
	private $_user=false;

	public function withGroups( $_mixed=array() ) {
		if ( !empty( $_mixed ) ) {
			$this->_withGroups=is_array( $_mixed )? $_mixed:array( $_mixed );
		}
		return $this;
	}

	private $_data;

	/**
	 * создаёт объект Core_Data по введённым данным
	 *
	 * @param array $_arr in - массив данных из вне
	 * @param string $_key in - ключ в масиве, при его наличии данные беруться из подмассива
	 * @return object
	 */
	public function setEntered( $_arr=array(), $_key='' ) {
		if ( empty( $_arr[$_key] ) ) {
			return $this;
		}
		$this->_data=new Core_Data( $_arr[$_key] );
		return $this;
	}

	/**
	 * отдаёт отфильтрованные введённые данные
	 *
	 * @param array $arrRes out
	 * @return object
	 */
	public function getEntered( &$arrRes ) {
		if ( is_object( $this->_data ) ) {
			$arrRes=$this->_data->getRaw();
		}
		return $this;
	}

	private $_headError=false;

	private $_fieldsError=false;

	public function getHeadError( &$strRes ) {
		$strRes=$this->_headError;
		return $this;
	}

	public function getFieldsError( &$arrRes ) {
		$arrRes=$this->_fieldsError;
		return $this;
	}

	private function setHeadError( $_str='' ) {
		$this->_headError=$_str;
		return false;
	}

	private $_minimalLenght=5;

	private function checkPassword() {
		if ( empty( $this->_data->filtered['passwd'] )||empty( $this->_data->filtered['confirm_passwd'] ) ) {
			return $this->setHeadError( 'passwords do not match' );
		}
		if ( $this->_data->filtered['passwd']!=$this->_data->filtered['confirm_passwd'] ) {
			return $this->setHeadError( 'passwords do not match' );
		}
		if ( Core_String::getStrlen( $this->_data->filtered['passwd'] )<$this->_minimalLenght ) {
			return $this->setHeadError( 'passwords do not match' );
		}
		return true;
	}

	private function checkPasswordUser() {
		if ( empty( $this->_data->filtered['passwd'] ) ) {
			return true;
		}
		if ( empty( $this->_data->filtered['confirm_passwd'] )||$this->_data->filtered['passwd']!=$this->_data->filtered['confirm_passwd'] ) {
			return $this->setHeadError( 'passwords do not match' );
		}
		if ( Core_String::getStrlen( $this->_data->filtered['passwd'] )<$this->_minimalLenght ) {
			return $this->setHeadError( 'passwords do not match' );
		}
		return true;
	}

	private function phoneUnique(){
		if( empty($this->_data->filtered['buyer_phone']) ){
			return true;
		}
		if( Core_Users::$info['buyer_phone']==$this->_data->filtered['buyer_phone']){
			return true;
		}
		if( $this->_user->withPhone($this->_data->filtered['buyer_phone'])->onlyOne()->getList($arrRes)->checkEmpty() ){
			return false;
		}
		// Сбрасываем флаг если телефон поменялся.
		// генерируем пин-код для подтверждения номера.
		$this->_data->setElements(array(
			'flg_phone'=>0,
			'code_confirm'=>mt_rand( 100000, 999999 )
		));
		return true;
	}

	private function accountUnique() {
		if( $this->_data->filtered['email']=='noemail' ){
			if( $this->_user->withNickname($this->_data->filtered['nickname'])->onlyOne()->getList($arrProfileExists)->checkEmpty()&&
				(empty( $this->_data->filtered['id'] )||$this->_data->filtered['id']!=$arrProfileExists['id']) ){
				$this->_fieldsError['nickname']=true;
				return $this->setHeadError( 'user with <'.$arrProfileExists['nickname'].'>  nickname already exists' );
			}
			return true;
		}
		if ( $this->_user->onlyOne()->withEmail( $this->_data->filtered['email'] )->getList( $arrProfileExists )->checkEmpty() ) {
			if ( empty( $this->_data->filtered['id'] )||$this->_data->filtered['id']!=$arrProfileExists['id'] ) {
				// если аккаунт существует но не активирован нужно ещё раз выслать письмо об активации на этот адресс TODO!!!14.12.2011
				$this->_fieldsError['email']=true;
				return $this->setHeadError( 'user with <'.$arrProfileExists['email'].'> email address already exists' );
			}
		}
		return true;
	}

	public function adminSet() {
		if ( !is_object( $this->_data ) ) {
			return false;
		}
		if ( !$this->_data->setFilter()->setChecker( array(
			'email'=>($this->_data->filtered['email']!='noemail')&&(empty( $this->_data->filtered['email'] )||!Core_Common::checkEmail( $this->_data->filtered['email'] )),
			'passwd'=>!empty( $this->_data->filtered['passwd'] )&&Core_String::getStrlen( $this->_data->filtered['passwd'] )<$this->_minimalLenght,
			'lang'=>empty( $this->_data->filtered['lang'] ),
			'timezone'=>empty( $this->_data->filtered['timezone'] ),
		) )->check() ) {
			$this->_data->getErrors( $this->_fieldsError );
			return $this->setHeadError( 'the data you entered did not match' );
		}
		$this->_user=new Project_Users_Management();
		if ( !$this->accountUnique() ) {
			return false;
		}
		$this->_user->withIds( $this->_data->filtered['id'] )->onlyOne()->getList( $_oneUser );
		if( $_oneUser['amount'] != $this->_data->filtered['amount'] ){
			$_purse=new Core_Payment_Purse();
			$_purse
				->setType( Core_Payment_Purse::TYPE_REWARD_SITES )
				->setAmount( abs( $this->_data->filtered['amount']-$_oneUser['amount'] ) )
				->setMessage( "Manually added-edited credit" )
				->setUserId( $_oneUser['id'] );
			if( $_oneUser['amount']-$this->_data->filtered['amount'] < 0 ){
				$_purse->receipts();
			}else{
				$_purse->expenditure();
			}
		}
		if ( !$this->_user->setEntered( $this->_data )->withFlags()->withGroups( $this->_data->filtered['groups'] )->set() ) {
			return $this->setHeadError( 'profile dont created' );
		}
		if( $this->_data->filtered['flg_active']==0 ){
			$this->_user->withIds( $this->_data->filtered['id'] )->deactivate();
		} else {
			$this->_user->withIds( $this->_data->filtered['id'] )->activate();
		}
		if( $this->_data->filtered['flg_maintenance']==0 ){
			$this->_user->withIds( $this->_data->filtered['id'] )->setMaintenance( false );
		} else {
			$this->_user->withIds( $this->_data->filtered['id'] )->setMaintenance( true );
		}
		return true;
	}

	public function edit() {
		if ( !is_object( $this->_data ) ) {
			return false;
		}
		if ( !$this->_data->setFilter()->setChecker( array(
			'passwd'=>!$this->checkPasswordUser(),
		) )->check() ) {
			$this->_data->getErrors( $this->_fieldsError );
			return $this->setHeadError( 'passwords do not match' );
		}
		/*if ( !Core_Data_Errors::getInstance()->setData( $this->_data )->setValidators( array(
			'buyer_phone'=>Core_Data_Errors::getInstance()->getValidator( 'Core_Validate_E164' ),
		) )->isValid() ) {
			return Core_Data_Errors::getInstance()->setError('Incorrect entered data');
		}*/
		if ( !$this->_data->setFilter()->setChecker( array(
			'email'=>empty( $this->_data->filtered['email'] )||!Core_Common::checkEmail( $this->_data->filtered['email'] ),
			'timezone'=>empty( $this->_data->filtered['timezone'] ),
		) )->check() ) {
			$this->_data->getErrors( $this->_fieldsError );
			return $this->setHeadError( 'the data you entered did not match' );
		}
		$this->_user=new Project_Users_Management();
		if ( !$this->accountUnique() ) {
			return false;
		}
		if( !$this->phoneUnique() ){
			return $this->setHeadError( 'this phone <'.$this->_data->filtered['buyer_phone'].'> already used' );
		}
		if ( !$this->_user->setEntered( $this->_data )->set() ) {
			return $this->setHeadError( 'profile don\'t created' );
		}
		Core_Users::getInstance()->reload();
		return true;
	}

	public function make() {
		if ( !is_object( $this->_data ) ) {
			return false;
		}
		if ( !$this->_data->setFilter()->setChecker( array(
			'package_id'=>empty( $this->_data->filtered['package_id'] ),
			'email'=>empty( $this->_data->filtered['email'] )||!Core_Common::checkEmail( $this->_data->filtered['email'] ),
			'i_agree'=>empty( $this->_data->filtered['i_agree'] ),
		) )->check() ) {
			$this->_data->getErrors( $this->_fieldsError );
			if(empty( $this->_data->filtered['i_agree'] )){
				return $this->setHeadError( 'Please agree to the Terms before proceeding to Step 2' );
			} else {
				return $this->setHeadError( 'Please enter email before proceeding to Step 2' );
			}
		}
		Project_Statistics_Package::add( $this->_data->filtered['package_id'],Project_Statistics_Package::TYPE_CLICK );
		// Ставим временный пароль для аккаунта, после оплаты пароль меняется и отправляется пользователю на email
		$_passwd=Core_Users::generatePassword();
		$this->_data->setElement('passwd',$_passwd);
		$this->_data->setElement('password',$_passwd); // используем для автоматической авторизации сразу после регистрации
		$this->_data->setElement('pagging_links',5);
		$this->_data->setElement('pagging_rows',10);
		$this->_data->setElement('popup_width',70);
		$this->_data->setElement('popup_height',70);
		$this->_data->setElement('flg_confirm',0); // подтверждать аккаунт будем после того как придет оплата.
		$this->_data->setElement('flg_active',1); // активируем пользователя срузу.
		$this->_data->setElement('flg_approve',1); // Пользователь согласился с условиями.
		$this->_data->setElement('flg_maintenance',1); // Включаем пользователю тех. поддержку
		$this->_data->setElement('approve',time()); // Время когда пользователь согласился с условиями.
		return $this->process();
	}

	/**
	 * Вызывается после оплаты для завершения регистрации
	 */
	public function complete(){
		if ( !$this->_data->setFilter()->setChecker( array(
			'package_id'=>empty( $this->_data->filtered['package_id'] ),
			'id'=>empty( $this->_data->filtered['id'] ),
		) )->check() ) {
			$this->_data->getErrors( $this->_fieldsError );
			return $this->setHeadError( 'the data you entered did not match' );
		}
		$_passwd=Core_Users::generatePassword();
		$this->_data->setElement('passwd', $_passwd );
		$this->_data->setElement('password', $_passwd );
		$this->_data->setElement('flg_confirm',1);
		$_user=new Project_Users_Management();
		if ( !$_user->setEntered( $this->_data->filtered )->set() ) {
			return $this->setHeadError( 'profile don\'t edited' );
		}
		$_package=new Core_Payment_Package();
		$_package->withIds($this->_data->filtered['package_id'])->onlyOne()->getList( $this->_data->filtered['arrPkg'] );
		// отправляем пароль пользователю
		$_mailer=new Core_Mailer();
		if ( !$_mailer
			->setVariables( $this->_data->filtered )
			->setTemplate( 'api_registration_complete' )
			->setSubject( 'Your Account Has Been Created' )
			->setPeopleTo( array( 'email'=>$this->_data->filtered['email'], 'name'=>$this->_data->filtered['nickname'] ) )
			->setPeopleFrom( array( 'name' => Zend_Registry::get('config')->engine->project_sysemail->name, 'email' => Zend_Registry::get('config')->engine->project_sysemail->email ) )
			->sendOneToMany() ) {
			return $this->setHeadError( 'email don\'t send' );
		}
		return true;
	}

	private function process() {
		$_user=new Project_Users_Management();
		// проверим есть-ли пользователь с таким email
		if ( $_user->onlyOne()->withEmail( $this->_data->filtered['email'] )->getList( $arrProfileExists )->checkEmpty() ) {
			// если аккаунт существует но не активирован нужно ещё раз выслать письмо об активации на этот адресс TODO!!!14.12.2011
			$this->_fieldsError['email']=true;
			return $this->setHeadError( 'user with <'.$arrProfileExists['email'].'> email address already exists' );
		}
		// создаём профайл, добавляем в группу Default
		$_arrDefaultGroups=array('Default','Maintenance');
		$_package=new Core_Payment_Package();
		$_package->onlyOne()->withIds($this->_data->filtered['package_id'])->getList( $arrPack );
		$_groups=new Core_Acs_Groups();
		$_groups->onlyOne()->withIds($arrPack['group_id'])->getList( $arrGroup);
		if( in_array($arrGroup['sys_name'],array('Zonterest LIGHT','Blog Fusion CSP')) ){
			$_arrDefaultGroups=array('Default');
			$this->_data->setElements(array('flg_maintenance'=>0));
		}
		if ( !$_user->setEntered( $this->_data )->withGroups( $_arrDefaultGroups )->set() ) {
			return $this->setHeadError( 'profile dont created' );
		}
		$_user->getEntered( $arrProfile );
		if ( !$_user->onlyOne()->withIds( $arrProfile['id'] )->getList( $arrProfile )->checkEmpty() ) {
			return $this->setHeadError( 'wrong account' );
		}
		/*
		// оплата
		if ( !empty( $arrProfile['arrPkg'] ) ) {
			if( Core_Payment_Package::isFree($arrProfile['arrPkg']) ){ // Only for FREE tarif
				$_free=new Project_Users_Free();
				$_free->setPackage( $arrProfile['arrPkg']['id'] )->setUser( $arrProfile['id'] )->add();
			} else {
				$_subscr=new Core_Payment_Subscription();
				$_expiry=0; // изначально тарифный план неоплачен для платных тарифов
				$_subscr->setEntered(array(
					'package_id'=>$arrProfile['arrPkg']['id'],
					'user_id'=>$arrProfile['id'],
					'expiry'=>$_expiry,
				))->set();
			}
		}
		*/
		/*
		// создаём код активации, проверки email т.к. у нас аккаунт уже активен то это будет служить просто подтверждением email
		if ( !$_user->setCode( $arrProfile, 'code_confirm' ) ) {
			return $this->setHeadError( 'code not set' );
		}
		// отправляем его пользователю
		$_mailer=new Core_Mailer();
		if ( !$_mailer
			->setVariables( $arrProfile )
			->setTemplate( 'access_registration_confirm' )
			->setSubject( Core_Module_Router::$domain.': please confirm account registration' )
			->setPeopleTo( array( 'email'=>$arrProfile['email'], 'name'=>$arrProfile['nickname'] ) )
			->setPeopleFrom( 'support@'.Core_Module_Router::$domain )
			->sendOneToMany() ) {
			return $this->setHeadError( 'email dont send' );
		}*/
		return true;
	}

	public function checkCode( $_str='' ) {
		if ( empty( $_str )||Core_String::getStrlen( $_str )<32 ) {
			$this->setHeadError( 'wrong code' );
			return $this;
		}
		$_user=new Core_Users_Management();
		if ( !$_user->onlyOne()->withField( 'code_confirm', $_str )->getList( $_arrProfile )->checkEmpty() ) {
			$this->setHeadError( 'wrong account' );
			return $this;
		}
		// если аккаунт уже активирован наверно ненадо ещё раз это делать
		// и что делать если деактивировал аккаунт админ TODO!!! 15.12.2011
		// т.к. у нас админ не аппрувит аккаунты то руками выставляем все нужные флаги
		if ( !$_user->setEntered( array( 'id'=>$_arrProfile['id'], 'flg_active'=>1, 'flg_confirm'=>1, 'flg_approve'=>1 ) )->set() ) {
			$this->setHeadError( 'profile dont updated' );
		}
		return $this;
	}
}
?>