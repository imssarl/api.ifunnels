<?php


/**
 * Логин с помощью полей nickname+password
 */
class Core_Users_Auth_Nickname extends Core_Users_Auth_Abstract {

	protected function check() {
		if ( !$this->_data->setChecker( array(
			'passwd'=>empty( $this->_data->filtered['passwd'] ),
			'nickname'=>!Core_Common::checkEmail( $this->_data->filtered['nickname'] ),
		) )->check() ) {
			return false; // некорректные данные
		}
		$_user=new Core_Users_Management();
		if ( !$_user->onlyOne()->withNickname( $this->_data->filtered['nickname'] )->withPasswd( $this->_data->filtered['passwd'] )->getList( $arrProfile )->checkEmpty() ) {
			return false; // недействительный аккаунт
		}
		if ( !$this->checkFlags( $arrProfile ) ) {
			return false; // есть запрещающие флаги
		}
		if ( !$this->checkGroups( $arrProfile ) ) {
			return false; // разрешённые группы не найдены
		}
		return $this->login( $arrProfile );
	}
}
?>