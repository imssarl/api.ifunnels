<?php

/**
 * Приложение для подтверждения номера телефона пользователей
 */
class Project_Ccs_Twilio_Apps_ConfirmPhone extends Project_Ccs_Twilio_Apps_Abstract {

	public function start(){
		$_objGather=$this->_response->gather( array( 'numDigits' => 1, 'timeout'=>10, 'action'=>self::prepareUrl(array('app'=>'ConfirmPhone','action'=>'choice')) ) );
		$_objGather->say( 'If you would like to type your PIN code, press 1, if you would like to say it in voice, press 2.', array('voice'=>$this->_voice) );
	}

	public function choice(){
		if( $this->_settings['Digits']==1 ){
			$this->_response->redirect(self::prepareUrl(array('app'=>'ConfirmPhone','action'=>'confirmWithEnter')),array());
		} else {
			$this->_response->redirect(self::prepareUrl(array('app'=>'ConfirmPhone','action'=>'confirmWithRecord')),array());
		}
	}

	/**
	 * Confirm phone number
	 */
	public function confirmWithRecord(){
		$_user=new Project_Users_Management();
		//сбрасываем флаг
		$_user->withIds( Core_Users::$info['id'] )->setFlgPhone(0);
		$this->updateCall(array('confirm'=>Core_Users::$info['code_confirm']));
		$this->_response->say( 'Provide your PIN code at the beep. Press any key when finished.', array('voice'=>$this->_voice) );
		$this->_response->record(array(
			'transcribe'=>true,
			'timeout'=>30,
			'transcribeCallback'=>self::prepareUrl(array('app'=>'ConfirmPhone','action'=>'transcribe')),
			'action'=>self::prepareUrl(array('app'=>'ConfirmPhone','action'=>'confirmWithRecordTwo'))
		));
	}

	public function confirmWithRecordTwo(){
		$this->_response->say( 'Please wait.', array('voice'=>$this->_voice) );
		$this->_response->enqueue(array( // постановка в очередь
			'waitUrl'=>self::prepareUrl(array('app'=>'ConfirmPhone','action'=>'queue'))
		));
	}

	/**
	 * Очередь ожидания результата
	 */
	public function queue(){
		$this->updateCall(array('QueueSid'=>$this->_settings['QueueSid']));
		$this->_response->play('http://com.twilio.sounds.music.s3.amazonaws.com/ClockworkWaltz.mp3');
		// добавить выход из звонка, после первого цикла.
		$this->_response->redirect(array( 'action'=>self::prepareUrl(array('app'=>'ConfirmPhone','action'=>'queue')) ));
	}

	public function confirmWithRecordSuccess(){
		$this->_response->say( 'Your phone number was confirmed successfully. Thank you! Goodbye!', array('voice'=>$this->_voice) );
	}

	public function confirmWithRecordError(){
		$this->_response->say( 'We are sorry, but your phone number was not confirmed. Please try again later. Goodbye!', array('voice'=>$this->_voice) );
	}

	public function transcribe(){
		if( empty($this->_settings['TranscriptionStatus']) ){
			return false;
		}
		$arrCall=$this->updateCall(array('pin'=>intval($this->_settings['TranscriptionText'])));
		$_twilio=new Project_Ccs_Twilio_Client();
		$_QS=$arrCall['commands']['QueueSid'];
		if( isset( $arrCall['QueueSid'] ) ){
			$_QS=$arrCall['QueueSid'];
		}
		if( isset( $this->_settings['QueueSid'] ) ){
			$_QS=$this->_settings['QueueSid'];
		}
		$member=$_twilio->_client
			->queues($_QS)
			->members($arrCall['CallSid'])
			->fetch();
		$_user=new Project_Users_Management();
		if( empty($arrCall['commands']['pin'])||!$_user->confirmPhone( $arrCall['commands']['pin'] ) ){
			$member->dequeue(self::prepareUrl(array('app'=>'ConfirmPhone','action'=>'confirmWithRecordError')));
			return;
		}
		$member->dequeue(self::prepareUrl(array('app'=>'ConfirmPhone','action'=>'confirmWithRecordSuccess')));
	}

	public function confirmWithEnter(){
		$_objGather=$this->_response->gather( array( 'timeout'=>10, 'action'=>self::prepareUrl(array('app'=>'ConfirmPhone','action'=>'confirmWithEnterTwo')) ) );
		$_objGather->say( 'Enter your PIN code. Press # when finished.', array('voice'=>$this->_voice) );
	}

	public function confirmWithEnterTwo(){
		$_user=new Project_Users_Management();
		$this->updateCall(array('pin'=>$this->_settings['Digits']));
		$this->updateCall(array('user_id'=>Core_Users::$info['id']));
		if( !empty($this->_settings['Digits'])&&$_user->confirmPhone( $this->_settings['Digits'] ) ){
			$this->_response->say( 'Your phone number was confirmed successfully.',array('voice'=>$this->_voice) );
		} else {
			$this->_response->say( 'We are sorry, but your phone number was not confirmed. Please try again later.', array('voice'=>$this->_voice) );
		}
		$this->_response->say( 'Thank you! Goodbye!',array('voice'=>$this->_voice) );
	}
}
?>