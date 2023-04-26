<?php

/**
 * Сообщает баланс пользователя(кредиты)
 */
class Project_Ccs_Twilio_Apps_Balance extends Project_Ccs_Twilio_Apps_Abstract {

	/**
	 * Say balance
	 */
	public function get(){
		$this->_response->say( 'You have '.Core_Users::$info['amount'].' credits available in the Creative Niche Manager.', array('voice'=>$this->_voice) );
		$this->_response->say( 'Thank you! Goodbye!',array('voice'=>$this->_voice) );
	}
}
?>