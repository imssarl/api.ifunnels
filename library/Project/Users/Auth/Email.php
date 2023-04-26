<?php


/**
 * Логин с помощью полей email+password
 */
class Project_Users_Auth_Email extends Core_Users_Auth_Email {

	/**
	 * служит для включения/отключения feedbackhq сайта
	 *
	 * @var boolean
	 */
	private $_feedbackServiceOn=false;

	/**
	 * группы по убыванию доступных возможностей (уже не поубыванию давно 21.07.2010)
	 *
	 * gate_id=44&secret=letsbesmart - Unlimited
	 * gate_id=2232&secret=bfenginefor98743vip - Blog Fusion
	 * gate_id=2227&secret=sbppro2009vip - Site Profit Bot Pro
	 * gate_id=2228&secret=sbpadvancedfor98743vip - Campaign Optimizer
	 * gate_id=2235&secret=cnm2010ad - Advertiser
	 * gate_id=2234&secret=spb2010hosted - Site Profit Bot Hosted
	 * gate_id=2236&secret=nvsbhosted2y - NVSB Hosted
	 * gate_id=2237&secret=nvsbhostedpro3z - NVSB Hosted Pro
	 *
	 * @var array
	 */
	private $_ethiccashGates=array(
		'Unlimited'=>'http://sales.ethiccash.com/2/action/Jin/APIUser/authorize.txt?gate_id=44&secret=letsbesmart',
		'Blog Fusion'=>'http://sales.ethiccash.com/2/action/Jin/APIUser/authorize.txt?gate_id=2232&secret=bfenginefor98743vip',
		'Advertiser'=>'http://sales.ethiccash.com/2/action/Jin/APIUser/authorize.txt?gate_id=2235&secret=cnm2010ad',
		'NVSB Hosted'=>'http://sales.ethiccash.com/2/action/Jin/APIUser/authorize.txt?gate_id=2236&secret=nvsbhosted2y',
		'NVSB Hosted Pro'=>'http://sales.ethiccash.com/2/action/Jin/APIUser/authorize.txt?gate_id=2237&secret=nvsbhostedpro3z',
	);

	private $_mediatrafficmeltdownGate='http://mediatrafficmeltdown.com/cnm_api.php?f=uc';

	/**
	 * т.к. для пользователей mediatrafficmeltdown нет емэйлов используем вместо них
	 *
	 * @var string
	 */
	private $_mediatrafficmeltdownDefEmail='noemail';

	/**
	 * ошибки в процессе логина
	 *
	 * @var integer
	 */
	private $_loginError=0;

	/**
	 * ответ полученный при опросе гейта
	 *
	 * @var array
	 */
	private $_result=array();

	/**
	 * группы текущего пользовтаеля - проверенные по гейтам
	 *
	 * @var array
	 */
	private $_currentUserGroups=array();

	/**
	 * создаёт объект Core_Data по введённым данным
	 *
	 * @param array $_arr in - массив данных из вне
	 * @param string $_key in - ключ в масиве, при его наличии данные беруться из подмассива
	 * @return object
	 */
	public function setEntered( $_arr=array(), $_key='' ) {
		$this->_data=new Core_Data( $_arr );
		return $this;
	}

	public function getErrors( &$arrRes ) {
		$arrRes=$this->_loginError;
		return $this;
	}

	/*
	1.если нету данных в посте то проверяем куки
	2.если в куках есть флаг о том что прошлый логин запомнили то логиним через куки
	3.возвращаем тру или фальш
	4.если данные есть то проверяем логин
	5.возвращаем тру или фальш
	*/
	public function authorize() {
		if ( empty( $_POST ) ) { // в $this->_data у нас $_REQUEST но нужно проверить что был ещё и $_POST
			return false;
		}
		// по кукам не логиним, т.к. надо проверять на удалённых сервисах оплачен ли аккаунт
		// возможно если в проекте будут свои аккаунты нужно будет включить и этот момент TODO!!! 15.02.2012
		/*$this->_data->setFilter();
		if ( empty( $this->_data->filtered ) ) {
			$this->setEntered( $_COOKIE, Core_Users_Cookie::$_cookieName );
			$this->_data->setFilter();
			if ( empty( $this->_data->filtered ) ) {
				return false; // нет поста и нет кук
			}
		}*/
		return $this->check();
	}

	protected function check() {
		if ( !$this->_data->setFilter()->setChecker( array(
			'password'=>empty( $this->_data->filtered['password'] )||$this->_data->filtered['password']=='********',
			'username_empty'=>empty( $this->_data->filtered['username'] )||$this->_data->filtered['username']=='Email / Username',
			'username_noemail'=>!Core_Common::checkEmail( $this->_data->filtered['username'] ),
		) )->check() ) {
			$this->_data->getErrors( $_arrErr );
			if ( count( $_arrErr )>1||empty( $_arrErr['username_noemail'] ) ) {
				$this->_loginError=1; // введены невалидные данные
				return false;
			}
			if ( !$this->checkMediatrafficmeltdownGate() ) { // возможно пользователь из mediatrafficmeltdown.com
				return false;
			}
		}
		if ( !$this->checkEthiccashGates() ) {
			return false;
		}
		// проверка в системе
		$_user=new Project_Users_Management();
		$intRes=$_user->getIdByParent( $this->_result['id'] );
		// обновляем данные
		$_intId=Core_Sql::setInsertUpdate( 'u_users', (empty( $intRes )?
			array(
				'passwd'=>md5( $this->_result['password'] ),
				'email'=>$this->_result['email'],
				'parent_id'=>$this->_result['id'], // id в системе ethiccash.com
				'nickname'=>$this->_result['name'], // это всётаки не никнэйм, возможно надо хранить всё в айтемах TODO!!!
				'flg_status'=>1,
				'added'=>time(),
			)
		:
			array( // эти данные могут изменится на ethiccash.com
				'id'=>$intRes,
				'passwd'=>md5( $this->_result['password'] ),
				'email'=>$this->_result['email'],
				'nickname'=>$this->_result['name'],
			)
		) );
		$_group=new Core_Acs_Groups();
		$_group->withIds( $_intId )->setGroupByName( $this->_currentUserGroups ); // обновляем группы пользователя
		if ( !$_user->onlyOne()->withIds( $_intId )->getList( $arrProfile )->checkEmpty() ) {
			return $this->_loginError=1; // недействительный аккаунт
		}
		if ( !$this->checkFlags( $arrProfile ) ) {
			return $this->_loginError=1; // есть запрещающие флаги
		}
		if ( !$this->checkGroups( $arrProfile ) ) {
			return $this->_loginError=1; // разрешённые группы не найдены
		}
		$_user->getSettings( $arrProfile['arrSettings'] ); // настройки пользователя
		return $this->login( $arrProfile );
	}

	protected function login( &$arrProfile ) {
		if ( !empty( $this->_data->filtered['rem'] ) ) {
			Core_Users_Cookie::write( $arrProfile );
		}
		Zend_Registry::get( 'objUser' )->setByProfile( $arrProfile );
		// это всё относится к поддержке старого кода
		$this->oldCodeActions(); // внесение пользователей
		Core_Users::$info['feedbackServiceOn']=$this->_feedbackServiceOn;
		// old code vars depercated!!!
		$_SESSION['feedbackServiceOn']=$this->_feedbackServiceOn;
		$_SESSION['CP_SESS_sessionuserid'] = $this->_result['id']; // id в системе ethiccash.com
		$_SESSION['CP_SESS_sessionusername'] = $this->_result['name'];
		$_SESSION['CP_SESS_sessionuseremail'] = $this->_result['email'];
		$_SESSION['CP_SESS_sessionuserpassword'] = $this->_result['password']; // in md5 not recoverable но как я понял это нигде не используется
		// As all customer is paid customer,allways updated to '1'
		// $_SESSION[$this->_sessPrefix.'fusionstatus'] = $fusion_user_type;
		//$_SESSION['CP_SESS_fusionstatus'] = '1'; // ??
		//$_SESSION['user_type'] = "admin"; // ??
		//$_SESSION['sessionGen'] = $this->_result['email'];
		//$_SESSION['paymenturl'] = $this->_paymentUrl; // ??
		return true;
	}

	// for old code
	private function oldCodeActions() {
		$intRes=Core_Sql::getCell( 'SELECT id FROM hct_admin_settings_tb WHERE user_id='.$this->_result['id'] );
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
					'username'=>$this->_result['name'],
					'password'=>$this->_result['password'],
					'email_address'=>$this->_result['email'],
					'user_id'=>$this->_result['id'],
					'rows_per_page'=>15,
					'snippet_part_1'=>4,
					'snippet_part_2'=>2,
					'snippet_part_3'=>1,
				)
			:
				array( // эти данные могут изменится на ethiccash.com
					'id'=>$intRes,
					'password'=>$this->_result['password'],
					'email_address'=>$this->_result['email'],
					'username'=>$this->_result['name'],
				)
			)
		);
	}

	private function getEncryptedPassword() {
		$_str=md5( 'ph4zanaspaqeqAtuphenuge*u6raS7awr7rUrecredrutucheTHut9dufecudr' ).
			md5( $this->_data->filtered['password'] ).md5( $this->_data->filtered['username'] );
		return substr( $_str, 0, 80 );
	}

	// т.к. сервис не возвращает id будем назначать вручную начиная от 100 000 000-ого id (хранится в int unsigned - до 4294967295)
	// но сначала проверим пользователя по никнэйму и паролю
	private function getParentIdToMediatrafficmeltdown() {
		// в этой системе пароли могут менятся, поэтому ищем только по никнэйму но из пользователей больше 100 000 000
		$_intRes=Core_Sql::getCell( '
			SELECT parent_id 
			FROM u_users 
			WHERE 
				nickname='.Core_Sql::fixInjection( $this->_data->filtered['username'] ).' AND
				parent_id>=100000000'
		 );
		if ( !empty( $_intRes ) ) {
			return $_intRes;
		}
		$_intRes=Core_Sql::getCell( 'SELECT parent_id FROM u_users ORDER BY parent_id DESC LIMIT 1' );
		if ( $_intRes<100000000 ) {
			$_intRes=100000000;
		} else {
			$_intRes++;
		}
		return $_intRes;
	}

	private function checkMediatrafficmeltdownGate() {
		// Instantiate a client object
		$this->_data->filtered['username']=str_replace(array(' ','&'),'',$this->_data->filtered['username']);
		$client=new Zend_Http_Client( 
			$this->_mediatrafficmeltdownGate.'&un='.$this->_data->filtered['username'].'&pw='.$this->getEncryptedPassword(), 
			array(
				/*'adapter'=>'Zend_Http_Client_Adapter_Proxy',
				'proxy_host'=>'211.138.124.196',
				'proxy_port'=>80,*/
				'timeout'=>30
			)
		);
		$response=$client->request();
		if ( $response->getStatus()!=200 ) {
			$this->_loginError=6; // ошибка при получении ответа от Mediatrafficmeltdown
			return false;
		}
		$_arrStat=json_decode( $response->getBody(), true );
		if ( empty( $_arrStat['Status'] )||$_arrStat['Status']!='Yes' ) {
			$this->_loginError=7; // not correct
			return false;
		}
		$this->_result=array(
			'id'=>$this->getParentIdToMediatrafficmeltdown(),
			'email'=>$this->_mediatrafficmeltdownDefEmail,
			'password'=>$this->_data->filtered['password'],
			'name'=>$this->_data->filtered['username'],
		);
		$this->_currentUserGroups=array( 'Blog Fusion' ); // доступ только в Blog Fusion группу
		return true;
	}

	// проверка гейтов и вычисление активных/оплаченных групп пользователя
	private function checkEthiccashGates() {
		$this->_currentUserGroups=array();
		foreach( $this->_ethiccashGates as $k=>$v ) {
			if ( $this->checkGate( $k ) ) {
				$this->_currentUserGroups[]=$k;
				if ( $k=='Unlimited' ) { // этой группе доступен весь функционал, поэтому проверять дальше смысла не имеет
					break;
				}
			}
		}
		return !empty( $this->_currentUserGroups );
	}

	// проверка гейта и парсинг результатов в случае если гейт разрешён пользователю
	private function checkGate( $_strGroup='' ) {
		if ( empty( $_strGroup )||empty( $this->_ethiccashGates[$_strGroup] ) ) {
			return false;
		}
		// Instantiate a client object
		$client=new Zend_Http_Client( 
			$this->_ethiccashGates[$_strGroup].
			'&email='.urlencode( $this->_data->filtered['username'] ).
			'&password='.urlencode( $this->_data->filtered['password'] ), 
			array(
				/*'adapter'=>'Zend_Http_Client_Adapter_Proxy',
				'proxy_host'=>'211.138.124.196',
				'proxy_port'=>80,*/
				'timeout'=>30
			)
		);
		$response=$client->request();
		if ( $response->getStatus()!=200 ) {
			$this->_loginError=6; // ошибка при получении ответа от ethiccash
			return false;
		}
		$_arrRes=preg_split( '/[\n\r]+/i', $response->getBody(), -1, PREG_SPLIT_NO_EMPTY );
		if ( empty( $_arrRes )||$_arrRes[0]!='SUCCESS' ) {
			$this->_loginError=7; // not correct
			return false;
		}
		array_shift( $_arrRes );
		parse_str( join( '&', $_arrRes ), $this->_result ); // эти данные возможно надо сохранять в чистом виде в отдельной таблице 15.10.2009
		$this->_result['password']=$this->_data->filtered['password'];
		return true;
	}
}
?>