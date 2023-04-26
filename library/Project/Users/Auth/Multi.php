<?php


/**
 * Логин с помощью полей email+password || nickname
 */
class Project_Users_Auth_Multi extends Core_Users_Auth_Abstract {

	/**
	 * служит для включения/отключения feedbackhq сайта
	 *
	 * @var boolean
	 */
	private $_feedbackServiceOn=false;

	/**
	 * т.к. для пользователей mediatrafficmeltdown нет емэйлов используем вместо них
	 *
	 * @var string
	 */
	private $_mediatrafficmeltdownDefEmail='noemail';

	private $_mediatrafficmeltdownGate='http://mediatrafficmeltdown.com/cnm_api.php?f=uc';
	/**
	 * ответ полученный при опросе гейта
	 *
	 * @var array
	 */
	private $_result=array();

	protected function check() {
		if ( !$this->_data->setChecker( array(
			'passwd'=>empty( $this->_data->filtered['passwd'] )||$this->_data->filtered['passwd']=='********',
			'username_empty'=>empty( $this->_data->filtered['username'] )||$this->_data->filtered['username']=='Email / Username',
			'username_noemail'=>!Core_Common::checkEmail( $this->_data->filtered['username'] ),
		) )->check() ) {
			$this->_data->getErrors( $_arrErr );
			if ( count( $_arrErr )>1||empty( $_arrErr['username_noemail'] ) ) {
				$this->_loginError=1; // введены невалидные данные
				return $this->setError('incorrect data');
			}
//			if ( !$this->checkMediatrafficmeltdownGate() ) { // возможно пользователь из mediatrafficmeltdown.com
//				return false;
//			}
		}
		$_user=new Project_Users_Management();
		if(!empty($this->_result)&&!$_user->onlyOne()->withIds( $this->_result['id'] )->getList( $arrProfile )->checkEmpty()){
			return $this->setError('cannot find account');
		} elseif ( !$_user->onlyOne()->withEmail( $this->_data->filtered['username'] )->withPasswd( $this->_data->filtered['passwd'] )->getList( $arrProfile )->checkEmpty() ) {
			return $this->setError('incorrect password or email'); // недействительный аккаунт
		}
		if ( !$this->checkFlags( $arrProfile ) || ( !empty($arrProfile['parent_id']) && $arrProfile['flg_allow_sub']!=1 ) ) {
			return $this->setError('access forbidden'); // есть запрещающие флаги
		}
		if ( !$this->checkGroups( $arrProfile ) ) {
			return $this->setError('cannot find groups'); // разрешённые группы не найдены
		}
		return $this->login( $arrProfile );
	}

	public function getHeadError( &$_str ){
		$this->getErrors( $_tmp );
		$_str=array_shift( $_tmp );
		return $this;
	}

	private function getEncryptedPassword() {
		$_str=md5( 'ph4zanaspaqeqAtuphenuge*u6raS7awr7rUrecredrutucheTHut9dufecudr' ).
			md5( $this->_data->filtered['passwd'] ).md5( $this->_data->filtered['username'] );
		return substr( $_str, 0, 80 );
	}

	private function checkMediatrafficmeltdownGate() {
		// Instantiate a client object
		$this->_data->filtered['username']=str_replace(array(' ','&'),'',$this->_data->filtered['username']);
		$client=new Zend_Http_Client(
			$this->_mediatrafficmeltdownGate.'&un='.$this->_data->filtered['username'].'&pw='.$this->getEncryptedPassword(),
			array(
				'timeout'=>30
			)
		);
		$response=$client->request();
		if ( $response->getStatus()!=200 ) {
			return $this->setError('server cannot answer');// ошибка при получении ответа от Mediatrafficmeltdown
		}
		$_arrStat=json_decode( $response->getBody(), true );
		if ( empty( $_arrStat['Status'] )||$_arrStat['Status']!='Yes' ) {
			return $this->setError('data not correct');
		}
		$this->_result=array(
			'id'=>$this->getIdToMediatrafficmeltdown(),
		);
		$_group=new Core_Acs_Groups();
		$_group->withIds( $this->_result['id'] )->setGroupByName( array( 'Blog Fusion' ) );
		return true;
	}

	private function getIdToMediatrafficmeltdown() {
		$_intRes=Core_Sql::getCell( 'SELECT id FROM u_users WHERE nickname='.Core_Sql::fixInjection( $this->_data->filtered['username'] ).' AND flg_source=2');
		return Core_Sql::setInsertUpdate( 'u_users', (empty( $_intRes )?
			array(
				'passwd'=>md5( $this->_data->filtered['passwd'] ),
				'email'=>$this->_mediatrafficmeltdownDefEmail,
				'nickname'=>$this->_data->filtered['username'],
				'flg_active'=>1,
				'flg_confirm'=>1,
				'flg_approve'=>1,
				'flg_source'=>2,
				'edited'=>time(),
				'added'=>time()
			)
		:
			array( // эти данные могут изменится на mediatrafficmeltdown.com
				'id'=>$_intRes,
				'passwd'=>md5( $this->_data->filtered['passwd'] ),
				'email'=>$this->_mediatrafficmeltdownDefEmail,
				'edited'=>time(),
				'nickname'=>$this->_data->filtered['username']
			)
		) );
	}

	protected function login( &$arrProfile ) {
		if ( !empty( $this->_data->filtered['rem'] ) ) {
			Core_Users_Cookie::write( $arrProfile );
		}
		Zend_Registry::get( 'objUser' )->setByProfile( $arrProfile );
		// это всё относится к поддержке старого кода
		$this->oldCodeActions($arrProfile); // внесение пользователей
		Core_Users::$info['feedbackServiceOn']=$this->_feedbackServiceOn;
		// old code vars depercated!!!
		$_SESSION['feedbackServiceOn']=$this->_feedbackServiceOn;
		$_SESSION['CP_SESS_sessionuserid'] = $arrProfile['id'];
		$_SESSION['CP_SESS_sessionusername'] = $arrProfile['nickname'];
		$_SESSION['CP_SESS_sessionuseremail'] = $arrProfile['email'];
		$_SESSION['CP_SESS_sessionuserpassword'] = $arrProfile['passwd']; // in md5 not recoverable но как я понял это нигде не используется
		return true;
	}

	private function oldCodeActions($arrProfile) {
		$intRes=Core_Sql::getCell( 'SELECT id FROM hct_admin_settings_tb WHERE user_id='.$arrProfile['id'] );
		/*
		snippet_part_1-2-3
		это параметр, который отвечает за то, чтобы campaign part, которая получает больше кликов, чаще показывалась на сайте
		параметр snippet show-ration (4:2:1) как раз с этим и связан - то есть скрипт автоматически меняет
		число показов для каждой части сниппета в зависимости от conversion rate данной части
		чем выше conversion rate, тем чаще эта часть будет показываться
		см. /snippets.php
		вощем раньше пользователи могли задавать сами
		теперь у всех одинаково
		*/
		Core_Sql::setInsertUpdate( 'hct_admin_settings_tb', (empty( $intRes )?
				array(
					'username'=>empty($arrProfile['nickname'])?$arrProfile['email']:$arrProfile['nickname'],
					'password'=>$arrProfile['passwd'],
					'email_address'=>$arrProfile['email'],
					'user_id'=>$arrProfile['id'],
					'rows_per_page'=>15,
					'snippet_part_1'=>4,
					'snippet_part_2'=>2,
					'snippet_part_3'=>1,
				)
			:
				array( // эти данные могут изменится на ethiccash.com
					'id'=>$intRes,
					'password'=>$arrProfile['passwd'],
					'email_address'=>$arrProfile['email'],
					'username'=>empty($arrProfile['nickname'])?$arrProfile['email']:$arrProfile['nickname'],
				)
			)
		);
	}
}
?>