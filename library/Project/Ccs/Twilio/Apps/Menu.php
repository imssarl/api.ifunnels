<?php

/**
 * Меню для входящих звонков, перенаправляет на нужные действия
 */
class Project_Ccs_Twilio_Apps_Menu extends Project_Ccs_Twilio_Apps_Abstract {

	/**
	 * Say menu
	 */
	public function menu(){
		$_buns=new Core_Payment_Buns();
		$_buns->withSysName('Project_Placement_Hosting')->onlyOne()->getList( $arrHosting );
		$_buns->withSysName('Project_Placement_Domen')->onlyOne()->getList( $arrDomain );
		$_purse=new Core_Payment_Purse();
		if( Core_Payment_Purse::getAmount()<($arrDomain['credits']+$arrHosting['credits']) ){
			$this->_response->say( 'You don\'t have sufficient amount of credits to create a website. Please add credits to your balance and try again.', array('voice'=>$this->_voice) );
		}else{
			$this->say4keyword();
		}
	}

	public function menu20(){
		$this->updateCall(array('flg_amazideas'=>1));
		sleep(3); // рассинхрон сервера
		$this->say4keyword();
	}

	public function say4keyword(){
		$gather=$this->_response->gather(array(
			'input' => 'speech',
			'action' => self::prepareUrl(array('app'=>'CreateSites','action'=>'verifyKeyword')),
			'timeout'=>4,
		));
		$gather->say( 'Hello! Say your keyword after the beep.', array('voice'=>$this->_voice) );
		$gather->play( 'https://www.soundjay.com/button/sounds/beep-01a.mp3' );
	}

	public function  error(){
		$this->_response->say( 'Sorry, your phone number is not authorized. Please complete verification process and try again. Thank you! Goodbye!', array('voice'=>$this->_voice) );
	}
}
?>