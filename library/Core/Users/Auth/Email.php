<?php


/**
 * Логин с помощью полей email+password
 */
class Core_Users_Auth_Email extends Core_Users_Auth_Abstract {

	protected function check() {
		if ( !$this->_data->setChecker( array(
			'passwd'=>empty( $this->_data->filtered['passwd'] ),
			'email'=>!Core_Common::checkEmail( $this->_data->filtered['email'] ),
		) )->check() ) {
			return false; // некорректные данные
		}
		$_user=new Core_Users_Management();
		if ( !$_user->onlyOne()->withEmail( $this->_data->filtered['email'] )->withPasswd( $this->_data->filtered['passwd'] )->getList( $arrProfile )->checkEmpty() ) {
			return false; // недействительный аккаунт
		}
		if ( !$this->checkGroups( $arrProfile ) ) {
			return false; // разрешённые группы не найдены
		}
		return $this->login( $arrProfile );
	}
}
?>