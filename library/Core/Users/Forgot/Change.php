<?php


/**
 * Пользователь сам меняет пароль по ссылке с уникальным кодом
 */
class Core_Users_Forgot_Change {

	private $_profile=array();

	/**
	 * промежуток в течении которого действителен код
	 * возможно сдледует вынести в конфиг
	 * 4320 - три дня
	 *
	 * @var integer
	 */
	private $_interval=4320;

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
			$arrRes=$this->_data->getFiltered();
		}
		return $this;
	}

	private $_errors='';

	public function getErrors( &$arrRes ) {
		$arrRes=$this->_errors;
		return $this;
	}
	
	public function setError( $_strError='' ) {
		$this->_errors=$_strError;
		return false;
	}

	public function send() {
		if ( !is_object( $this->_data ) ) {
			return false;
		}
		$this->_data->setFilter();
		if ( empty( $this->_data->filtered['email'] )||!Core_Common::checkEmail( $this->_data->filtered['email'] ) ) {
			return $this->setError( 'wrong email' );
		}
		$_user=new Core_Users_Management();
		if ( !$_user->onlyOne()->withEmail( $this->_data->filtered['email'] )->getList( $arrProfile )->checkEmpty() ) {
			return $this->setError( 'wrong account' );
		}
		if ( !$_user->setCode( $arrProfile, 'code_forgot' ) ) {
			return $this->setError( 'code not set' );
		}
		if ( !$this->sendMailCode( $arrProfile ) ) {
			return $this->setError( 'email dont send' );
		}
		if ( !$_user->setTime( $arrProfile, 'forgot' ) ) {
			return $this->setError( 'code not set' );
		}
		return true;
	}

	protected function sendMailCode( &$arrProfile ) {
		return Core_Mailer::getInstance()
			->setVariables( $arrProfile )
			->setTemplate( 'access_forgot_codetochange' )
			->setSubject( Zend_Registry::get('config')->engine->project_title.': link to change password' )
			->setPeopleTo( array( 'email'=>$arrProfile['email'], 'name'=>$arrProfile['nickname'] ) )
			->setPeopleFrom( array( 'name' => Zend_Registry::get('config')->engine->project_sysemail->name, 'email' => Zend_Registry::get('config')->engine->project_sysemail->email ) )
			->sendOneToMany();
	}

	public function checkCode( $_str='' ) {
		if ( empty( $_str )||Core_String::getStrlen( $_str )<32 ) {
			return $this->setError( 'wrong code' );
		}
		$_user=new Core_Users_Management();
		if ( !$_user->onlyOne()->withField( 'code_forgot', $_str )->getList( $this->_profile )->checkEmpty() ) {
			return $this->setError( 'wrong account' );
		}
		if ( $this->_profile['forgot']+$this->_interval<time() ) {
			return $this->setError( 'forgot time is up' );
		}
		return true;
	}

	public function change() {
		if ( !is_object( $this->_data ) ) {
			return false;
		}
		$this->_data->setFilter();
		if ( empty( $this->_profile ) ) {
			return $this->setError( 'somthing wrong' );
		}
		if ( !$this->changePasswordCheck() ) {
			return false;
		}
		$this->_profile['passwd']=$this->_data->filtered['passwd'];
		$_user=new Core_Users_Management();
		if ( !$_user->setEntered( $this->_profile )->set() ) {
			return $this->setError( 'password dont updated' );
		}
		return $this->sendMailPasswd();
	}

	private $_minimalLenght=5;

	private function changePasswordCheck() {
		if ( empty( $this->_data->filtered['passwd'] )||empty( $this->_data->filtered['repasswd'] ) ) {
			return $this->setError( 'the passwords you entered did not match' );
		}
		if ( $this->_data->filtered['passwd']!=$this->_data->filtered['repasswd'] ) {
			return $this->setError( 'the passwords you entered did not match' );
		}
		if ( Core_String::getStrlen( $this->_data->filtered['passwd'] )<$this->_minimalLenght ) {
			return $this->setError( 'the passwords you entered did not match' );
		}
		return true;
	}

	protected function sendMailPasswd() {
		return Core_Mailer::getInstance()
			->setVariables( $this->_profile )
			->setTemplate( 'access_forgot_newpassword' )
			->setSubject( Zend_Registry::get('config')->engine->project_title.': congratulations - password changed' )
			->setPeopleTo( array( 'email'=>$this->_profile['email'], 'name'=>$this->_profile['nickname'] ) )
			->setPeopleFrom( array( 'name' => Zend_Registry::get('config')->engine->project_sysemail->name, 'email' => Zend_Registry::get('config')->engine->project_sysemail->email ) )
			->sendOneToMany();
	}
}
?>