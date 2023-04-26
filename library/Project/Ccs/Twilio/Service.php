<?php

/**
 * Входящие сообщения звонки от Twilio
 */
class Project_Ccs_Twilio_Service extends Project_Ccs_Twilio_Abstract {


	private function auth(){
		if( empty($this->_settings['From']) ){
			return false;
		}
		$_user=new Project_Users_Management();
		if( !$_user->withPhone( $this->_settings['From'] )->withConfirmPhone()->onlyOne()->getList( $arrUser ) ){
			return false;
		}
		if(empty($arrUser['id'])){
			return false;
		}
		$return=Core_Users::getInstance()->setById( $arrUser['id'] );
		if( Core_Acs::haveAccess( array('Zonterest Custom 2.0') ) ){
			return $return;
		}else{
			return false;
		}
	}

	/**
	 * Принимает входящие SMS, обрабатывает их и принимает решения что с ними дальше делать
	 * @throws Project_Ccs_Exception
	 */
	public function sms(){
		if(!$this->auth()){
			throw new Project_Ccs_Exception('Can not find user');
		}
		if( empty($this->_settings['SmsSid']) ){
			throw new Project_Ccs_Exception('SMS sid is empty');
		}
	//	/ *
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
		$_command=$_message->body;
		$_category='All';
		if( stripos($_command,'[') ){
			preg_match( '/(.*?)\[(.*?)\]/i', $_command, $_tmp );
			$_keyword=strtolower( trim( $_tmp[1] ) );
			$_category=$_tmp[2];
			if( strpos( $_category, ':' ) === false ){
				$_categoryName=str_replace( ' ', '', strtolower( $_category ) );
				$_usersettings=new Project_Content_Settings();
				$_usersettings->onlyOne()->withFlgDefault()->onlySource( '9' )->getContent( $_arrsettings );
				$_categoryNames=Project_Content_Adapter_Amazon::getCategory( $_categoryName, $_arrsettings['settings']['site'] );
				if( isset( $_categoryNames['remote_id'] ) ){
					$_flgHaveCategory=$_categoryNames['title'].'::'.$_categoryNames['remote_id'];
				}else{
					$_flgHaveCategory=$_categoryNames['title'];
				}
					$_category=$_flgHaveCategory;
				}
		}else{
			$_keyword=$_command;
		}
		$_command=$_keyword.'['.$_category.']';
		$_zonterest20='';
		if( isset( $this->_settings['flg_zonterest20'] ) && $this->_settings['flg_zonterest20'] ){
			$_zonterest20=' zonterest20';
		}
		shell_exec( '/usr/bin/nohup /usr/bin/php -f /data/www/'.Zend_Registry::get( 'config' )->domain->host.'/html/services/create_zonterest.php "'.$_command.'" '.Core_Users::$info['id'].$_zonterest20.' >> /data/www/'.Zend_Registry::get( 'config' )->domain->host.'/html/services/create_zonterest.log 2>&1 &' );
		exit;
	}

	public function voice(){
		if(!$this->auth()){
			$_obj=new Project_Ccs_Twilio_Apps();
			$_obj->setSettings( array('app'=>'Menu','action'=>'error') )->run();
			die();
		}
		if( empty($this->_settings['CallSid']) ){
			throw new Project_Ccs_Exception('Call sid is empty');
		}
		$_call=$this->_client->calls( $this->_settings['CallSid'] )->fetch();
		$_model=new Project_Ccs_Voice();
		$_model->clean( $_call->sid );
		$_model->setEntered(array(
			'CallSid'=>$_call->sid,
			'To'=>$_call->to,
			'From'=>$_call->from,
			'CallStatus'=>$_call->status,
			'Direction'=>$_call->direction
		))->set();
		$_obj=new Project_Ccs_Twilio_Apps();
		if( isset( $this->_settings['flg_zonterest20'] ) && $this->_settings['flg_zonterest20'] ){
			$_obj->setSettings( array('app'=>'Menu','action'=>'menu20')+$this->_settings );
		}else{
			$_obj->setSettings( array('app'=>'Menu','action'=>'menu')+$this->_settings );
		}
		$_obj->run();
	}
}
?>