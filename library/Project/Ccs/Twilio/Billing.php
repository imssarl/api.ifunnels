<?php

/**
 * Входящие сообщения и звонки от Twilio для Billing
 */
class Project_Ccs_Twilio_Billing extends Project_Ccs_Twilio_Abstract {

	/**
	 * Принимает входящие SMS, обрабатывает их и принимает решения что с ними дальше делать
	 * @throws Project_Ccs_Exception
	 */
	public function sms(){
		if( empty($this->_settings['From']) ){
			throw new Project_Ccs_Exception('Can not find user info');
		}
		$_billings=new Project_Billing();
		$_billings->withPhone( $this->_settings['From'] )->getList( $arrUserBillings );
		$_twilio=new Project_Ccs_Twilio_Client();
		if( count( $arrUserBillings )==0 ){
			$_twilio->setSettings(array('body'=>'Mobile number registered as this one does not exist.'))
				->setBuyerPhone( $this->_settings['From'] )
				->sendSMS();
			throw new Project_Ccs_Exception('Mobile number registered as this one does not exist.');
		}
		if( empty($this->_settings['SmsSid']) ){
			throw new Project_Ccs_Exception('SMS sid is empty');
		}
		$_message=$this->_client
			->messages( $this->_settings['SmsSid'] )
			->fetch();
		$_model=new Project_Ccs_Sms();
		$_model->setEntered(array(
			'SmsSid'=>$_message->sid,
			'To'=>$_message->to,
			'From'=>$_message->from,
			'SmsStatus'=>$_message->status,
			'Direction'=>$_message->direction,
			'Body'=>$_message->body,
		))->set();
		$_command=strtolower($_message->body);
		$_arrSettings=array();
		switch( $_command ){
			case 'stop':
			case 'texts stop':
			case 'unsubscribe':
			case 'cancel':
				$_arrBillings=self::parseBillings( $arrUserBillings );
				if( isset( $_arrBillings['centili'] ) && !empty( $_arrBillings['centili'] ) ){
					foreach( $_arrBillings['centili'] as $_centili ){
						$ch=curl_init();
						curl_setopt($ch, CURLOPT_URL, 'https://api.centili.com/api/payment/1_3/unsubscribe' );
						curl_setopt($curl, CURLOPT_POST, true);
						curl_setopt($curl, CURLOPT_POSTFIELDS, "apikey=32f70a647af9046e58316c5b5babe432&msisdn=".$_centili['phone'] );
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
						curl_setopt($ch, CURLOPT_HEADER, 0);
						/*$output = */curl_exec($ch);
						curl_close($ch);
					}
				}else{
					$_twilio->setSettings(array('body'=>'Your subscription is already cancelled. All the best. Instaffiliate Team'))
						->setBuyerPhone( $this->_settings['From'] )
						->sendSMS();
				}
				$_twilio->setSettings(array('body'=>'Your subscription has now been cancelled'))
					->setBuyerPhone( $this->_settings['From'] )
					->sendSMS();
			break;
			default :
				$_twilio->setSettings(array('body'=>'Code <'.$_command.'> is not recognized'))
					->setBuyerPhone( $this->_settings['From'] )
					->sendSMS();
				throw new Project_Ccs_Exception( 'Code <'.$_command.'>  is not recognized');
			break;
		}
	}

	public function voice(){
		if( empty($this->_settings['From']) ){
			throw new Project_Ccs_Exception('Can not find user info');
		}
		$_billings=new Project_Billing();
		$_billings->withPhone( $this->_settings['From'] )->getList( $arrUserBillings );
		if( count( $arrUserBillings )==0 ){
			throw new Project_Ccs_Exception('Mobile number registered as this one does not exist.');
		}
		if( empty($this->_settings['CallSid']) ){
			throw new Project_Ccs_Exception('Call sid is empty');
		}
		$_call=$this->_client->calls( $this->_settings['CallSid'] )->fetch();// ->calls->get($this->_settings['CallSid']);
		$_model=new Project_Ccs_Voice();
		$_model->setEntered(array(
			'CallSid'=>$_call->sid,
			'To'=>$_call->to,
			'From'=>$_call->from,
			'CallStatus'=>$_call->status,
			'Direction'=>$_call->direction
		))->set();
		$_twilio=new Project_Ccs_Twilio_Apps();
		$_twilio->setSettings( array('app'=>'Unsubscribe','action'=>'unsubscribe') )->run();
	}

	public static function parseBillings( $_billings=array() ){
		$_arrBillings=array();
		foreach( $_billings as $_billing ){
			if( !isset( $_arrBillings[$_billing['aggregator']] ) ){
				$_arrBillings[$_billing['aggregator']]=array();
			}
			if( $_billing['status'] == 'success' ){
				if( $_billing['event_type'] == 'opt_in' ){
					$_arrBillings[$_billing['aggregator']][$_billing['service']]=$_billing;
				}elseif( $_billing['event_type'] == 'opt_out' && isset( $_arrBillings[$_billing['aggregator']][$_billing['service']] ) ){
					unset( $_arrBillings[$_billing['aggregator']][$_billing['service']] );
				}
			}
			if( empty( $_arrBillings[$_billing['aggregator']] ) ){
				unset( $_arrBillings[$_billing['aggregator']] );
			}
		}
		return $_arrBillings;
	}

}
?>