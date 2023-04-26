<?php
class Project_Validations_Realtime extends Core_Data_Storage {

	protected $_table='email_validations_realtime';
	protected $_fields=array('id','user_id','type','status','added');
	private $_bySysName=false;

	const USER='u', MOOPTIN='m', EMAIL_FUNNEL='e', FUNNEL='f';
	
	public function install(){
		Core_Sql::setExec( "CREATE TABLE IF NOT EXISTS `".$this->_table."` (
			`id` INT(11) NOT NULL AUTO_INCREMENT,
			`user_id` int(11) unsigned NOT NULL DEFAULT '0',
			`type` VARCHAR(20) NULL DEFAULT NULL COLLATE 'utf8_unicode_ci',
			`status` INT(1) NOT NULL DEFAULT '0',
			`added` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			UNIQUE INDEX `id` (`id`)
		);");
	}
	
	protected $_withType=false;
	protected $_withUser=false;

	public function withType( $_var=false ){
		$this->_withType=$_var;
		return $this;
	}

	public function withUser( $_var=false ){
		$this->_withUser=$_var;
		return $this;
	}
	
	protected function assemblyQuery(){
		parent::assemblyQuery();
		if ( !empty( $this->_withType ) ){
			$this->_crawler->set_where( 'd.type IN ('.Core_Sql::fixInjection( $this->_withType ).')' );
		}
		if ( !empty( $this->_withUser ) ){
			$this->_crawler->set_where( 'd.user_id='.Core_Sql::fixInjection( $this->_withUser ).'' );
		}
	}

	protected function init(){
		$this->_withType=false;
		$this->_withUser=false;
		parent::init();
	}
	
	private function getUserId( &$intRes ){
		if ( !empty( $this->_data->filtered['user_id'] ) ){
			$intRes=$this->_data->filtered['user_id'];
			return false; // переустанавливать user_id ненадо он уже указан
		}
		if ( !( Zend_Registry::isRegistered( 'objUser' )||in_array( 'user_id', $this->_fields ) ) ){
			return false;
		}
		Zend_Registry::get( 'objUser' )->getId( $intRes );
		return !empty( $intRes );
	}
	
	public function set(){
		if ( !$this->beforeSet() ){
			return false;
		}
		if ( empty( $this->_data->filtered['id'] ) ){
			$this->_data->setElement( 'added', time() );
			if ( $this->getUserId( $_intId ) ){
				$this->_data->setElement( 'user_id', $_intId );
			}
		}
		if( $this->_data->filtered['type'] == 'u' ){
			Core_Sql::setExec( 'DELETE FROM '.$this->_table.' WHERE user_id='.$this->_data->filtered['user_id'] );
		}else{
			Core_Sql::setExec( 'DELETE FROM '.$this->_table.' WHERE user_id='.$this->_data->filtered['user_id'].' AND type="'.$this->_data->filtered['type'].'"' );
		}
		$this->_data->setElement( 'id', Core_Sql::setInsertUpdate( $this->_table, $this->_data->setMask( $this->_fields )->getValid() ) );
		return $this->afterSet();
	}
	
	public static function check( $_userId, $_type, $_id=false ){
		$_obj=new Project_Validations_Realtime();
		if( $_type!=self::USER ){
			$_obj->withUser( $_userId )->withType( $_type.$_id )->onlyOne()->getList( $_valueData );
		}
		$_obj->withUser( $_userId )->withType( 'u' )->onlyOne()->getList( $_userData );
		if( isset( $_userData ) && $_userData['status']==1 && ( !isset( $_valueData ) || ( isset( $_valueData ) && $_valueData['status']==1 || empty( $_valueData ) ) ) ){
			return true;
		}
		return false;
	}
	
	public static function setValue( $_type, $_id, $_value ){
		$_obj=new Project_Validations_Realtime();
		$_setData=array(
			'type'=>$_type.($_type!=self::USER?$_id:''),
			'status'=>$_value
		);
		if( $_type==self::USER ){
			$_setData['user_id']=$_id;
		}
		$_obj->setEntered($_setData)->set();
		return false;
	}
}
?>