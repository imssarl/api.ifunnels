<?php
class Project_Billing_Txtnation extends Core_Data_Storage {

	protected $_table='txtnation_sweepstakes';
	protected $_fields=array( 'id', 'userid', 'message_id', 'send_id', 'send', 'get_send', 'send_delivered', 'response', 'response_id', 'billing', 'get_billing', 'billing_delivered', 'added' );
	
	public static function install(){
		Core_Sql::setExec( "DROP TABLE IF EXISTS `txtnation_sweepstakes`" );
		Core_Sql::setExec("
			CREATE TABLE `txtnation_sweepstakes` (
				`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
				`userid` VARCHAR(12) NOT NULL DEFAULT '0',
				`message_id` INT(1) UNSIGNED NOT NULL DEFAULT '0',
				`send_id` INT(11) UNSIGNED NOT NULL DEFAULT '0',
				`send` TEXT NULL,
				`get_send` TEXT NULL,
				`send_delivered` TEXT NULL,
				`response` TEXT NULL,
				`response_id` INT(11) UNSIGNED NOT NULL DEFAULT '0',
				`billing` TEXT NULL,
				`get_billing` TEXT NULL,
				`billing_delivered` TEXT NULL,
				`added` INT(11) UNSIGNED NOT NULL DEFAULT '0',
				UNIQUE INDEX `id` (`id`)
			)
			COLLATE='utf8_general_ci'
			ENGINE=InnoDB;");
	}
	
	public static function update(){
		Core_Sql::setExec("ALTER TABLE `txtnation_sweepstakes`
			CHANGE COLUMN `id` `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
			CHANGE COLUMN `message_id` `message_id` INT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `userid`,
			ADD COLUMN `send_id` INT(11) UNSIGNED NOT NULL DEFAULT '0' AFTER `message_id`,
			ADD COLUMN `send_delivered` TEXT NULL AFTER `get_send`,
			ADD COLUMN `response_id` INT(11) UNSIGNED NOT NULL DEFAULT '0' AFTER `response`,
			CHANGE COLUMN `delivered` `billing_delivered` TEXT NULL AFTER `get_billing`;
		");
	}
	
	protected $_test=false;
	
	protected $_sendToUrl='http://client.txtnation.com/gateway.php';
	protected $_eKey='c7e45fb68a5677985e22189528c2818b';
	protected $_strMessageTitle='68899';
	protected $_strCompanyCode='IMS';

	private static $_sendEmailMessageTo=array( 
		array( 'email'=>'shadow-dwarf@yandex.ru', 'name'=>'Slava Slepov' ),
		array( 'email'=>'anna.fiadorchanka@gmail.com', 'name'=>'Admin' )
	);
	
	private $_strMessages=array(
		1=>'Your chance to win ₤1000 this week. Reply IMS to enter this draw.',
		2=>'Want an extra chance to win ₤1000 this week. Reply IMS to get an extra entry.',
		3=>'Want an extra chance to win ₤1000 this week. Reply IMS to get an extra entry.',
	);
	
	private static $_resender=4;
	private static $_resenders=array();
	
	public function sendMail( $_text='' ){
			$to=array();
		foreach( self::$_sendEmailMessageTo as $_admin ){
			$to[]=$_admin['name'];
		}
		@mail( implode( ', ', $to), 'TxtNation Sweepstakers', $_text, 'From: support@qjmpz.com' );
		/*
		Core_Mailer::getInstance()
			->setVariables( array(
				'text'=>$_text,
			) )
			->setTemplate( 'txtnation_sweepstakes' )
			->setSubject( 'TxtNation Sweepstakers' )
			->setPeopleTo( self::$_sendEmailMessageTo )
			->setPeopleFrom( 'support@qjmpz.com' )
			->sendOneToMany();
		*/
	}
	
	public function newSendId(){
		$_newSendId=mt_rand( 1000000000, 2147483647 );
		while( $this->withSendId( $_newSendId )->getList( $_tmp )->checkEmpty() ){ // !empty - true empty - false
			$_newSendId=mt_rand( 1000000000, 2147483647 );
		}
		return $_newSendId;
	}
	
	public function sendMessage( $_userId='', $_messageId='', $_network='international', $_amount=0 ){
		if( empty( $_userId ) && !isset( $this->_strMessages[ $_messageId ] ) ){
			return false;
		}
		$_getData=array();
		$this->withUserId( $_userId )->getList( $_arrUserMessages );
		if( count( $_arrUserMessages ) > 0 ){
			// check is_access message_id for this user
			foreach( $_arrUserMessages as $_arrData ){
				if( $_arrData['message_id'] == $_messageId ){
					$_getData=$_arrData;
					break;
				}
			}
		}
		if( empty( $_getData ) ){
			$_getData=array(
				'userid' => $_userId,
				'message_id' => $_messageId,
				'send_id' => $this->newSendId(),
				'added' => time()
			);
		}
		$_query=array(
			'number' => $_getData['userid'],
			'value' => $_amount,
			'currency' => 'GBP',
			'cc' => $this->_strCompanyCode,
			'ekey' => $this->_eKey,
			'network' => $_network
		);
		if( $_amount == 0 ){
			$_query=array(
				'reply' => 0,
				'id' => $_getData['send_id'],
				'message' => $this->_strMessages[ $_messageId ],
				'title' => $this->_strMessageTitle
			)+$_query;
			$_action='send';
		}else{
			$_query=array(
				'reply' => 1,
				'id' => $_getData['response_id'],
				'message' => '₤'.$_amount.' to enter.'
			)+$_query;
			$_action='billing';
		}
		$_getData[$_action]=serialize( $_query );
		
		if( !$this->_test ){
			$curl=Core_Curl::getInstance();
			if( !$curl->setPost( $_query )->getContent( $this->_sendToUrl ) ){
				return false;
			}
			$_request=$curl->getResponce();
			switch( $_request ){
				case "ERROR IR-101":
					// нужно обновить id т.к. такой уже посылали когда то
					if( !isset( self::$_resenders[$_userId."_".$_messageId] ) ){
						self::$_resenders[$_userId."_".$_messageId]=0;
					}
					self::$_resenders[$_userId."_".$_messageId]++;
					if( self::$_resenders[$_userId."_".$_messageId] > self::$_resender ){
						$_getData['get_'.$_action]="More that ".self::$_resender." sends!";
						$this->setEntered( $_getData )->set();
						return false;
					}
					$this->sendMessage( $_userId, $_messageId, $_network, $_amount );
					return true;
				break;
				case "NO CREDITS":
					$this->sendMail( 'Txtnation NO CREDITS' );
					echo 'NO CREDITS';
					exit; //полная остановка скрипта
				break;
				case "SUCCESS":
					$_getData['get_'.$_action]=$_request;
				break;
				default:
					$this->sendMail( 'Txtnation '.$_request.' answer on '.$_getData[$_action] );
					echo 'ERROR IR-104';
					exit; //полная остановка скрипта
				break;
			}
		}else{
			$_getData['get_'.$_action]='fake';
		}
		if( !$this->setEntered( $_getData )->set() ){
			return false;
		}
		return true;
	}
	
	private $_withUserId=false;
	private $_withDelivered=false;
	private $_withMessageID=false;
	private $_withSendId=false;
	private $_withResponseId=false;

	public function withUserId( $_str ){
		$this->_withUserId=$_str;
		return $this;
	}

	public function withDelivered(){
		$this->_withDelivered=true;
		return $this;
	}

	public function withMessageID( $_str ){
		$this->_withMessageID=$_str;
		return $this;
	}

	public function withSendId( $_str ){
		$this->_withSendId=$_str;
		return $this;
	}

	public function withResponseId( $_str ){
		$this->_withResponseId=$_str;
		return $this;
	}

	protected function init() {
		parent::init();
		$this->_withUserId=false;
		$this->_withDelivered=false;
		$this->_withMessageID=false;
		$this->_withSendId=false;
		$this->_withResponseId=false;
	}
	
	protected function assemblyQuery() {
		parent::assemblyQuery();
		if( $this->_withUserId ){
			$this->_crawler->set_where('d.userid IN ('.Core_Sql::fixInjection( $this->_withUserId ).')' );
		}
		if( $this->_withDelivered ){
			$this->_crawler->set_where('d.billing_delivered IS NOT NULL' );
		}
		if( $this->_withMessageID ){
			$this->_crawler->set_where('d.message_id IN ('.Core_Sql::fixInjection( $this->_withMessageID ).')' );
		}
		if( $this->_withSendId ){
			$this->_crawler->set_where('d.send_id IN ('.Core_Sql::fixInjection( $this->_withSendId ).')' );
		}
		if( $this->_withResponseId ){
			$this->_crawler->set_where('d.response_id IN ('.Core_Sql::fixInjection( $this->_withResponseId ).')' );
		}
	}
}
?>