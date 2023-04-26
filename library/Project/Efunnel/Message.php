<?php
class Project_Efunnel_Message extends Core_Data_Storage {

	protected $_table='lpb_efunnels_message';
	protected $_fields=array( 'id', 'efunnel_id', 'name', 'subject', 'body_html', 'body_plain_text', 'header_title', 'period_time', 'flg_period', 'flg_pause', 'position', 'resend', 'edited', 'added' );
	
	public static function install(){
		Core_Sql::setExec("drop table if exists lpb_efunnels_message");
		Core_Sql::setExec( "CREATE TABLE `lpb_efunnels_message` (
			`id` INT(11) NOT NULL AUTO_INCREMENT,
			`efunnel_id` INT(11) NOT NULL DEFAULT '0',
			`name` VARCHAR(100) NULL DEFAULT NULL,
			`subject` TEXT NULL,
			`body_html` TEXT NULL,
			`body_plain_text` TEXT NULL,
			`header_title` VARCHAR(100) NULL DEFAULT NULL,
			`flg_period` INT(1) NOT NULL DEFAULT '0',
			`period_time` INT(4) NOT NULL DEFAULT '0',
			`flg_pause` TINYINT(1) NOT NULL DEFAULT '0',
			`position` INT(2) NOT NULL DEFAULT '0',
			`added` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			`edited` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			UNIQUE INDEX `id` (`id`)
		)
		COLLATE='utf8_general_ci'
		ENGINE=InnoDB;" );
		return $this;
	}
	
	private $_withEfunnelId=false;
	
	protected function init(){
		parent::init();
		$this->_withEfunnelId=false;
	}
	
	public function withEfunnelId( $_arrIds=false ){
		$this->_withEfunnelId=$_arrIds;
		return $this;
	}

	public function activate( $message_id, $flg_pause ){
		Core_Sql::setExec( 'UPDATE '. $this->_table .' SET `flg_pause` = "' . $flg_pause . '" WHERE `id`="' . $message_id . '";' );
	}
	
	public function addResend( $arrResendSettings=array() ){
		$_id=$arrResendSettings['id'];
		unset( $arrResendSettings['id'] );
		Core_Sql::setExec( 'UPDATE '. $this->_table .' SET `resend`="'.base64_encode( serialize( $arrResendSettings ) ).'" WHERE `id`="'.$_id.'"' );
	}
	
	protected function assemblyQuery(){
		parent::assemblyQuery();
		if ( !empty( $this->_withEfunnelId ) ){
			$this->_crawler->set_where( 'd.efunnel_id IN ('.Core_Sql::fixInjection( $this->_withEfunnelId ).')' );
		}
	}
	
	public function del(){
		$_bool=false;
		if ( !empty( $this->_withIds ) ){
			Core_Sql::setExec( 'DELETE FROM '.$this->_table.' 
				WHERE id IN('.Core_Sql::fixInjection( $this->_withIds ).')' );
			$_bool=true;
		}
		if( !empty( $this->_withEfunnelId ) ){
			Core_Sql::setExec( 'DELETE FROM '.$this->_table.' 
				WHERE efunnel_id IN('.Core_Sql::fixInjection( $this->_withEfunnelId ).')' );
			$_bool=true;
		}
		$this->init();
		return $_bool;
	}
	
	protected function beforeSet(){
		$this->_data->setFilter(array('trim','clear'));
		foreach( $this->_data->filtered['subject'] as &$_subject ){
			$_subject=htmlentities( $_subject, ENT_QUOTES );
		}
		$_updateSubject=base64_encode( serialize( $this->_data->filtered['subject'] ) );
		$this->_data->setElement('subject', $_updateSubject );
		$_updateOptions=base64_encode( serialize( $this->_data->filtered['resend'] ) );
		$this->_data->setElement('resend', $_updateOptions );
		$this->_data->setFilter(array('trim','clear'));
		return true;
	}

	public function getList( &$mixRes ){
		parent::getList($mixRes);
		if( is_int( array_keys($mixRes)[0] ) ){
			foreach( $mixRes as &$_arrZeroData ){
				if( isset( $_arrZeroData['subject'] ) ){
					$_oldSubject=$_arrZeroData['subject'];
					$_arrZeroData['subject']=unserialize( base64_decode( $_arrZeroData['subject'] ) );
					if( $_arrZeroData['subject']===false || $_arrZeroData['subject']===NULL ){
						$_arrZeroData['subject']=$_oldSubject;
					}
				}
				if( isset( $_arrZeroData['resend'] ) ){
					$_oldSettings=$_arrZeroData['resend'];
					$_arrZeroData['resend']=unserialize( base64_decode( $_arrZeroData['resend'] ) );
					if( $_arrZeroData['resend']===false ){
						$_arrZeroData['resend']=$_oldSettings;
					}
				}
			}
		}elseif( isset( $mixRes['subject'] ) ){
			$_oldSubject=$mixRes['subject'];
			$mixRes['subject']=unserialize( base64_decode( $mixRes['subject'] ) );
			if( $mixRes['subject']===false || $mixRes['subject']===NULL ){
				$mixRes['subject']=$_oldSubject;
			}
			$_oldSettings=$mixRes['resend'];
			$mixRes['resend']=unserialize( base64_decode( $mixRes['resend'] ) );
			if( $mixRes['resend']===false ){
				$mixRes['resend']=$_oldSettings;
			}
		}
		$this->init();
		return $this;
	}
}
?>