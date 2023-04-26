<?php

/**
 * Адаптер для приложений
 */
class Project_Ccs_Twilio_Apps {

	private $_settings=array();
	public static $appUrl='/services/twilio.php?app=#app#&action=#action#';

	public function setSettings( $_arrSettings ){
		if( empty($_arrSettings) ){
			throw new Project_Ccs_Exception('Empty data');
		}
		$this->_settings=$_arrSettings;
		return $this;
	}

	public function run(){
		if( empty($this->_settings['action']) ){
			throw new Project_Ccs_Exception('Incorrect entered data');
		}
		if( !$this->auth()&&$this->_settings['action']!='error' ){
			$this->setSettings( array('app'=>'Menu','action'=>'error') )->run();
			die();
		}
		$_class='Project_Ccs_Twilio_Apps_'.$this->_settings['app'];
		new $_class( $this->_settings['action'], $this->_settings );
	}

	private function auth(){
		if(!empty(Core_Users::$info['id'])){
			return true;
		}
		if( $this->_settings['Direction']=='inbound' ){
			$_number=$this->_settings['From'];
		} else {
			$_number=$this->_settings['Called'];
		}
		if( empty($_number) ){
			return false;
		}
		$_user=new Project_Users_Management();
		if( $this->_settings['app']!='ConfirmPhone' ){
			$_user->withConfirmPhone();
		}
		if( !$_user->withPhone( $_number )->onlyOne()->getList( $arrUser ) ){
			return false;
		}
		if( empty($arrUser['id']) ){
			return false;
		}
		return Core_Users::getInstance()->setById( $arrUser['id'] );
	}
}
?>