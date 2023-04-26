<?php


/**
 * Абстрактный класс описывающий стандартную процедуру логина на сайт
 */
abstract class Core_Users_Auth_Abstract {

	private $_withGroups=array();

	public function withGroups( $_mixed=array() ) {
		if ( !empty( $_mixed ) ) {
			$this->_withGroups=is_array( $_mixed )? $_mixed:array( $_mixed );
		}
		return $this;
	}

	/**
	 * создаёт объект Core_Data по введённым данным
	 *
	 * @param array $_arr in - массив данных из вне
	 * @param string $_key in - ключ в масиве, при его наличии данные беруться из подмассива
	 * @return object
	 */
	public function setEntered( $_arr=array(), $_key='' ) {
		$this->_data=new Core_Data( (empty( $_arr[$_key] )? array():$_arr[$_key]) );
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

	public function getErrors( &$arrRes ) {
		$arrRes=$this->_errors;
		return $this;
	}
	
	public function setError( $_strError='' ) {
		$this->_errors[]=$_strError;
		return false;
	}

	/*
	1.если нету данных в посте то проверяем куки
	2.если в куках есть флаг о том что прошлый логин запомнили то логиним через куки
	3.возвращаем тру или фальш
	4.если данные есть то проверяем логин
	5.возвращаем тру или фальш
	*/
	public function authorize() {
		$this->_data->setFilter();
		if ( empty( $this->_data->filtered ) ) {
			$this->setEntered( $_COOKIE, Core_Users_Cookie::$_cookieName );
			$this->_data->setFilter();
			if ( empty( $this->_data->filtered ) ) {
				return false; // нет поста и нет кук
			}
		}
		return $this->check();
	}

	protected function checkGroups( &$arrProfile ) {
		if ( !empty( $this->_withGroups ) ) {
			$_arr=array_intersect( $this->_withGroups, $arrProfile['groups'] );
			if ( empty( $_arr ) ) {
				return false; // чтобы залогинится через данный логин у пользователя нехватает групп
			}
		}
		return true;
	}

	// добавить тут проверку флагов которые не позволяют логинится в систему (например что аккаунт неактивен) TODO!!! 05.12.2011
	protected function checkFlags( &$arrProfile ) {
		if( $arrProfile['flg_active']==0 ){
			return false;
		}
		return true;
	}

	protected function login( &$arrProfile ) {
		if ( !empty( $this->_data->filtered['rem'] ) ) {
			Core_Users_Cookie::write( $arrProfile );
		}
		return Zend_Registry::get( 'objUser' )->setByProfile( $arrProfile );
	}

	abstract protected function check();
}
?>