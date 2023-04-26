<?php

/**
 * Приложение для создания сайтов
 */
class Project_Ccs_Twilio_Apps_CreateSites extends Project_Ccs_Twilio_Apps_Abstract {

	/* проверка ключевого слова после ожидания  */
	public function verifyKeyword(){
		if(!isset($this->_settings['SpeechResult'])){
			return false;
		}
		$_client=new Project_Ccs_Twilio_Client();
		$_arrReturn=array('returnText'=>'');
		if( $this->_settings['SpeechResult']=='failed' ){
			$_arrReturn['returnText']='Keyword Transcription Status is failed';
		}elseif( $this->_settings['SpeechResult']=='(blank)' ){
			$_arrReturn['returnText']='Keyword Transcription is blank';
		}else{
			$_arrReturn['keyword']=$this->_settings['SpeechResult'];
			$_arrReturn['returnText']='Your keyword is '.$this->_settings['SpeechResult'];
		}
		$arrCall=$this->updateCall($_arrReturn);
		$gather=$this->_response->gather( array(
			'input' => 'speech',
			'timeout'=>3,
			'action'=>self::prepareUrl(array('app'=>'CreateSites','action'=>'createSitesGetCategory'))
		) );
		$gather->say( $arrCall['commands']['returnText'].'. If the keyword is correct, say Yes, if you would like to say another keyword, say No.', array('voice'=>$this->_voice) );
	}

	public function createSitesGetCategory(){
		if(!isset($this->_settings['SpeechResult'])){
			return false;
		}
		if( !in_array( $this->_settings['SpeechResult'], array( 'yes', 'Yes', 'Yes.', 'yes.') ) || $this->_settings['SpeechResult']=='now' ){
			$this->say4keyword();
			return true;
		}
		$gather=$this->_response->gather(array(
			'input' => 'speech',
			'action' => self::prepareUrl(array('app'=>'CreateSites','action'=>'check4category')),
			'timeout'=>3,
		));
		$gather->say( 'Do you want to use a specific category for your website? Say Yes to proceed with selecting the category. Say No if you would like to use all categories and create your website now.', array('voice'=>$this->_voice) );
	}
	
	public function say4keyword(){
		$_lngBCP47='en-US';
		Core_Users::getInstance()->setById( Core_Users::$info['id'] );
		$_usersettings=new Project_Content_Settings();
		$_usersettings->onlyOne()->withFlgDefault()->onlySource( '9' )->getContent( $_arrsettings );
		switch ( $_arrsettings['settings']['site'] ){
			case 'CN':$_lngBCP47='en-US';break;
			case 'ES':$_lngBCP47='es-ES';break;
			case 'IT':$_lngBCP47='it-IT';break;
			case 'FR':$_lngBCP47='fr-FR';break;
			case 'JP':$_lngBCP47='ja-JP';break;
			case 'DE':$_lngBCP47='de-DE';break;
			case 'BR':$_lngBCP47='pt-br';break;
			case 'MX':$_lngBCP47='es-mx';break;
			case 'IN':$_lngBCP47='hi';break;
			case 'CA':
			case 'UK':
			case 'US':
			default:$_lngBCP47='en-US';break;
		}
		$gather=$this->_response->gather(array(
			'input' => 'speech',
			'language' => $_lngBCP47,
			'action' => self::prepareUrl(array('app'=>'CreateSites','action'=>'verifyKeyword')),
			'timeout'=>4,
		));
		$gather->say( 'Say your keyword after the beep.', array('voice'=>$this->_voice) );
		$gather->play( 'https://www.soundjay.com/button/sounds/beep-01a.mp3' );
	}
	
	public function check4category(){
		if(!isset($this->_settings['SpeechResult'])){
			return false;
		}
		if( !in_array( $this->_settings['SpeechResult'], array( 'yes', 'Yes', 'Yes.', 'yes.') ) || $this->_settings['SpeechResult']=='now' ){
			$this->updateCall(array('category'=>'All'));
			$this->createSite();
			return true;
		}
		$this->say4category();
	}
	
	public function say4category(){
		$gather=$this->_response->gather(array(
			'input' => 'speech',
			'action' => self::prepareUrl(array('app'=>'CreateSites','action'=>'verifyCategory')),
			'timeout'=>4,
		));
		$gather->say( 'Say your category after the beep.', array('voice'=>$this->_voice) );
		$gather->play( 'https://www.soundjay.com/button/sounds/beep-01a.mp3' );
	}

	public function verifyCategory(){
		if(!isset($this->_settings['SpeechResult'])){
			return false;
		}
		if( $this->_settings['SpeechResult']=='failed' || $this->_settings['SpeechResult']=='(blank)' ){
			$this->updateCall(array('category'=>'All'));
		}else{
			$_categoryName='';
			$_updateText=$this->_settings['SpeechResult'];
			if( strtolower( substr( $_updateText, 0, 10 ) ) == 'category' ){
				$_updateText=trim( str_replace( array('category', 'Category'), '', $_updateText ) );
			}
			$_categoryName=str_replace( ' ', '', strtolower( $_updateText ) );
			Core_Users::getInstance()->setById( Core_Users::$info['id'] );
			$_usersettings=new Project_Content_Settings();
			$_usersettings->onlyOne()->withFlgDefault()->onlySource( '9' )->getContent( $_arrsettings );
			$_categoryNames=Project_Content_Adapter_Amazon::getCategory( $_categoryName, $_arrsettings['settings']['site'] );
			if( isset( $_categoryNames['remote_id'] ) ){
				$_flgHaveCategory=$_categoryNames['title'].'::'.$_categoryNames['remote_id'];
			}else{
				$_flgHaveCategory=$_categoryNames['title'];
			}
			$this->updateCall(array('category'=>$_flgHaveCategory));
		}
		$gather=$this->_response->gather( array( 
			'input' => 'speech',
			'timeout'=>3,
			'action'=>self::prepareUrl(array('app'=>'CreateSites','action'=>'get4category')) 
		) );
		if( $_flgHaveCategory == 'All' ){
			$gather->say( 'Your specific category is '.$_categoryName.'. The category you specified is not a valid category. Product search will be performed in All categories instead. If the category is correct, say Yes, if you would like to say another category, say No.', array('voice'=>$this->_voice) );
		}else{
			$gather->say( 'Your specific category is '.$_categoryName.'.  If the category is correct, say Yes, if you would like to say another category, say No.', array('voice'=>$this->_voice) );
		}
	}

	public function get4category(){
		if(!isset($this->_settings['SpeechResult'])){
			return false;
		}
		if( !in_array( $this->_settings['SpeechResult'], array( 'yes', 'Yes', 'Yes.', 'yes.') ) || $this->_settings['SpeechResult']=='now' ){
			$this->say4category();
			return true;
		}
		$this->createSite();
		return;
	}
	
	public function createSite(){
		$arrCall=$this->getCall();
		$_zonterest20='';
		if( isset( $arrCall['commands']['flg_amazideas'] ) && $arrCall['commands']['flg_amazideas'] ){
			$_zonterest20=' zonterest20';
		}
		$_command=$arrCall['commands']['keyword'].'['.$arrCall['commands']['category'].']';
		shell_exec( '/usr/bin/nohup /usr/bin/php -f /data/www/'.Zend_Registry::get( 'config' )->domain->host.'/html/services/create_zonterest.php "'.$_command.'" '.Core_Users::$info['id'].$_zonterest20.' >> /data/www/'.Zend_Registry::get( 'config' )->domain->host.'/html/services/create_zonterest.log 2>&1 &' );
		$this->complete();
		return;
	}
	
	public function complete(){
		$this->_response->say( 'Thank you. We are now creating your website and you will receive a text message in a few seconds with your site URL.',array('voice'=>$this->_voice) );
	}
}
?>