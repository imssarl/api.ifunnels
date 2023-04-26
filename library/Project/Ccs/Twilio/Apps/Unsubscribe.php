<?php
/**
 * WorkHorse Framework
 *
 * @category Project
 * @package Project_Ccs_Twilio_Apps
 * @copyright Copyright (c) 2015, Web2Innovation
 * @author Slepov Viacheslav <shadowdwarf@mail.ru>
 * @date 12.03.2015
 * @version 0.1
 */

/**
 * Отписка от рассылки billing
 *
 * @category Project
 * @package Project_Ccs_Twilio_Apps
 * @copyright Copyright (c) 2015, Web2Innovation
 */
class Project_Ccs_Twilio_Apps_Unsubscribe extends Project_Ccs_Twilio_Apps_Abstract {

	/**
	 * Say unsubscribe
	 */
	public function unsubscribe(){
		if( !$this->checkBillingAccess() ){
			return false;
		}
		$gather=$this->_response->gather( '', array( 'timeout'=>10, 'action'=>self::prepareUrl(array('app'=>'Unsubscribe','action'=>'choice')) ) );
		$gather->say( 'Hi and welcome to Instaffiliate Customer Care Center. If you have not received access to the service, please press 1, if you would like to cancel your subscription, please press 2, if you have any other enquiries, please press 3.', array('voice'=>$this->_voice) );
	}

	public function choice(){
		$this->updateCall(array('choice'=>$this->_settings['Digits']));
		switch( $this->_settings['Digits']){
			case 1:
				$this->access();
				break;
			case 2:
				$this->cancel();
				break;
			case 3:
				$this->enquiries();
				break;
			default :
				$this->unsubscribe();
				break;
		}
		unset($this->_response);
		die();
	}

	public function access(){
		if( empty($this->_settings['From']) ){
			$gather=$this->_response->gather( '', array( 'timeout'=>10, 'action'=>self::prepareUrl(array('app'=>'Unsubscribe','action'=>'sendSms')) ) );
			$this->_response->say( 'Sorry, you don\'t have access to instaffiliate.net. If you woud like to join, please press 5 and we will send you a VIP access link', array('voice'=>$this->_voice) );
			return false;
		}
		$_billings=new Project_Billing();
		$_billings->withPhone( $this->_settings['From'] )->getList( $arrUserBillings );
		$_lastBills=Project_Ccs_Twilio_Billing::lastBillings( $arrUserBillings );
		if( count( $arrUserBillings )==0 || empty( $_lastBills ) ){
			$this->_response->say( 'We can\'t locate your mobile number in our database, please verify you\'re calling from the right phone, or if you think it\'s an error, please send an email to our second level suppor team at: success@instaffiliate.net', array('voice'=>$this->_voice) );
			return true;
		}
		$_flgOptIn=false;
		$_optOutDate=false;
		foreach( $arrUserBillings as $_arrService ){
			foreach( $_arrService as $_bills ){
				if( $_bills['event_type'] == 'opt_in' ){
					$_flgOptIn=true;
					break 2;
				}else{
					$_optOutDate=$_bills['added'];
				}
			}
		}
		if( $_flgOptIn ){
			$this->_response->say( 'Thanks, we\'ve now sent you a SMS with a link to the account registration page. Please follow this link and you\'ll get instant access. Thank you for your time. Goodbye.', array('voice'=>$this->_voice) );
			$this->sendSms();
		}else{
			$this->_response->say( 'You\'ve unsubscribed from the service on '.date("Y-m-d", $_optOutDate).', so you don\'t have access anymore to the service. If you think this is an error, please send an email at success@instaffiliate.net. Goodbye.', array('voice'=>$this->_voice) );
		}
	}

	public function checkSms(){
		$this->updateCall(array('choice'=>$this->_settings['Digits']));
		switch( $this->_settings['Digits']){
			case 5:
				$this->sendSms();
				$this->_response->say( 'Thanks for your time. We\'ve now sent your discounted link. goodbye.', array('voice'=>$this->_voice) );
				break;
			default :
				$this->unsubscribe();
				break;
		}
		unset($this->_response);
		die();
	}

	public function sendSms(){
		$_client=new Project_Ccs_Twilio_Client();
		$_client
			->setSettings(array('body'=>'Here is your discounted link https://instaffiliate.net/lp1/uk1c'))
			->setBuyerPhone( $this->_settings['From'] )
			->sendSMS();
		return true;
	}

	public function cancel(){
		if( !$this->checkBillingAccess( $arrUserBillings ) ){
			return false;
		}
		$this->_response->say( 'Please confirm you want to cancel your subscription by saying STOP after the bip. Press any key when finished.', array('voice'=>$this->_voice) );
		$this->_response->record('',array(
			'transcribe'=>true,
			'timeout'=>30,
			'transcribeCallback'=>self::prepareUrl(array('app'=>'Unsubscribe','action'=>'stop')),
			'action'=>self::prepareUrl(array('app'=>'Unsubscribe','action'=>'complete'))
		));
	}

	public function stop(){
		if(empty($this->_settings['TranscriptionStatus'])){
			return false;
		}
		$_client=new Project_Ccs_Twilio_Client();
		$arrCall=$this->updateCall(array('keyword'=>$this->_settings['TranscriptionText']));
		if( $this->_settings['TranscriptionStatus']=='failed' ){
			$this->_response->say( 'Stop keyword transcript is failed.', array('voice'=>$this->_voice) );
			$this->complete();
			return true;
		}
		if( $this->_settings['TranscriptionText']=='(blank)' ){
			$this->_response->say( 'We can not your stop keyword transcript.', array('voice'=>$this->_voice) );
			$this->complete();
			return true;
		}
		$this->_response->say( Project_Ccs_Twilio_Billing::unsubscribe( $arrUserBillings ), array('voice'=>$this->_voice) );
	}

	public function enquiries(){
		if( !$this->checkBillingAccess( $arrUserBillings ) ){
			return false;
		}
		$this->_response->say( 'We will kindly ask you to contact our second level support team via email at support@instaffiliate.net', array('voice'=>$this->_voice) );
		$this->complete();
	}
	
	private function checkBillingAccess( &$arrUserBillings ){
		if( empty($this->_settings['From']) ){
			$this->_response->say( 'Mobile number registered as this one does not exist.', array('voice'=>$this->_voice) );
			return false;
		}
		$_billings=new Project_Billing();
		$_billings->withPhone( $this->_settings['From'] )->getList( $arrUserBillings );
		if( count( $arrUserBillings )==0 ){
			$this->_response->say( 'Mobile number registered as this one does not exist.', array('voice'=>$this->_voice) );
			return false;
		}
		return true;
	}

	public function complete(){
		$this->_response->say( 'Thank you! Goodbye!',array('voice'=>$this->_voice) );
	}

}
?>