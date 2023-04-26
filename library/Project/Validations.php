<?php
class Project_Validations extends Core_Data_Storage {

	protected $_table='email_validations';
	protected $_fields=array('id','user_id','name','type','status','id_checker','options','added');
	private $_bySysName=false;

	const SINGLE=1, CNM_LIST=2, FILE_LIST=3, REAL_TIME=4;
	
	public function install(){
		Core_Sql::setExec( "CREATE TABLE IF NOT EXISTS `".$this->_table."` (
			`id` INT(11) NOT NULL AUTO_INCREMENT,
			`user_id` int(11) unsigned NOT NULL DEFAULT '0',
			`name` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_unicode_ci',
			`type` INT(1) NULL DEFAULT NULL,
			`status` INT(1) NOT NULL DEFAULT '0',
			`id_checker` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_unicode_ci',
			`options` TEXT NULL,
			`added` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			UNIQUE INDEX `id` (`id`)
		);");
	}
	
	protected $_onlyLast=false;
	protected $_withType=false;
	protected $_withName=false;
	protected $_withStatus=false;
	protected $_withChecker=false;

	public function onlyLast(){
		$this->_onlyLast=true;
		return $this;
	}

	public function withType( $_var=false ){
		$this->_withType=$_var;
		return $this;
	}

	public function withName( $_var=false ){
		$this->_withName=$_var;
		return $this;
	}

	public function withStatus( $_int=0 ){
		$this->_withStatus=$_int;
		return $this;
	}

	public function withChecker( $_str=false ){
		$this->_withChecker=$_str;
		return $this;
	}

	protected function assemblyQuery(){
		parent::assemblyQuery();
		if ( !empty( $this->_withName ) ){
			$this->_crawler->set_where( 'd.name='.Core_Sql::fixInjection( $this->_withName ) );
		}
		if ( !empty( $this->_withType ) ){
			$this->_crawler->set_where( 'd.type IN ('.Core_Sql::fixInjection( $this->_withType ).')' );
		}
		if ( $this->_withStatus!==false ){
			$this->_crawler->set_where( 'd.status='.Core_Sql::fixInjection( $this->_withStatus ) );
		}
		if ( !empty( $this->_withChecker ) ){
			$this->_crawler->set_where( 'd.id_checker='.Core_Sql::fixInjection( $this->_withChecker ) );
		}
		if ( $this->_onlyLast ){
			$this->_crawler->set_order_sort( 'd.added--up' );
		}
	}

	protected function beforeSet(){
		$this->_data->setFilter( array( 'clear' ) );
		$this->_data->setElement('options', base64_encode( serialize( $this->_data->filtered['options'] ) ));
		return true;
	}

	protected function afterSet(){
		$this->_data->filtered['options']= unserialize( base64_decode( $this->_data->filtered['options'] ) );
		return true;
	}

	protected function init(){
		$this->_withType=false;
		parent::init();
	}

	public function getList( &$mixRes ){
		parent::getList( $mixRes );
		if( empty($mixRes) ){
			return $this;
		}
		if( array_key_exists( 0, $mixRes ) ){
			foreach( $mixRes as &$_item ){
				$_item['options'] = unserialize( base64_decode( $_item['options'] ) );
			}
		}elseif( isset( $mixRes['options'] ) ){
			$mixRes['options'] = unserialize( base64_decode( $mixRes['options'] ) );
		}
		$this->init();
		return $this;
	}
	
	public function getPayment($_pay=0){
		if( $_pay == 0 ){
			return true;
		}
		$_fullAmount=Core_Payment_Purse::getAmount()*250+Core_Users::$info['validation_limit'];
		if( $_fullAmount>=$_pay ){
			$_credits=$_limit=0;
			if( Core_Users::$info['validation_limit'] >= $_pay ){
				$_limit=Core_Users::$info['validation_limit']-$_pay;
			}else{
				$_pay=$_pay-Core_Users::$info['validation_limit']; // оплата части из лимита, обнулили лимит пользователя
				$_limit=250-( $_pay%250 ); // столько лимитов должно остаться у пользователя после расчетов
				$_credits=(int)ceil( $_pay/250 ); // целая часть, это сняти кредитов за оставшееся
			}
			Core_Sql::setExec('UPDATE u_users SET validation_limit='.$_limit.' WHERE id="'.Core_Users::$info['id'].'"');
			if( $_credits!=0 ){
				$_purse=new Core_Payment_Purse();
				$_purse
				->setAmount( $_credits )
				->setUserId( Core_Users::$info['id'] )
				->setType( Core_Payment_Purse::TYPE_INTERNAL )
				->setMessage('Valudation limit update')
				->expenditure();
			}
			sleep( 3 );
			Core_Users::getInstance()->reload();
			return true;
		}
		return false;
	}
	
}
?>