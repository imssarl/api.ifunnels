<?php


/**
 * News administration
 */

class Project_Iam_Forms extends Core_Data_Storage {
/*
CREATE TABLE `iam_forms` (
`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`name` VARCHAR(255) NOT NULL,
`sites_settings` TEXT NULL,
`secret_id` VARCHAR(255) NOT NULL,
`activations_limit` INT(11) UNSIGNED NOT NULL DEFAULT '0',
`edited` INT(11) UNSIGNED NOT NULL DEFAULT '0',
`added` INT(11) UNSIGNED NOT NULL DEFAULT '0',
 PRIMARY KEY (`id`)
)
COLLATE='utf8_general_ci'
ENGINE=MyISAM;
*/
	protected $_fields=array(
		'id','name','sites_settings','secret_id','activations_limit','edited','added'
	);
	protected $_table='iam_forms';
	
	protected $_links=array();

	public function setEntered( $_mix=array() ){
		if( !empty( $_mix['sites_settings'] ) ){
			$_mix['sites_settings']=serialize(array_filter($_mix['sites_settings']));
		}
		$this->_data=is_object( $_mix )? $_mix:new Core_Data( $_mix );
		return $this;
	}

	public static function getRemoveLink( $_secretId='##SECRET_ID##' ){
		return Zend_Registry::get( 'config' )->domain->url.'/iam/registration/?remove='.$_secretId.'&email=##user_email##';
	}

	public static function getActivateLink( $_secretId='##SECRET_ID##' ){
		return Zend_Registry::get( 'config' )->domain->url.'/iam/registration/?code='.$_secretId.'&cbid=##user_clickbank_id##&email=##user_email##';
	}

	public static function getRemoveForm( $_secretId='##SECRET_ID##' ){
		return '<form action="'.Zend_Registry::get( 'config' )->domain->url.'/iam/registration/" method="POST" enctype="multipart/form-data">'
			.'<input type="hidden" name="remove" value="'.$_secretId.'" />'
			.'<input type="email" name="email"  value="" />'
			.'<input type="submit" value="Remove Instant Affiliate Marketer" />'
		.'</form>';
	}

	public static function getForm( $_secretId='##SECRET_ID##' ){
		return '<form action="'.Zend_Registry::get( 'config' )->domain->url.'/iam/registration/" method="POST" enctype="multipart/form-data">'
			.'<input type="hidden" name="code" value="'.$_secretId.'" />'
			.'<input type="email" name="email"  value="" />'
			.'<input type="text" name="cbid"  value="" />'
			.'<input type="submit" value="Instant Affiliate Marketer" />'
		.'</form>';
	}

	public function getEntered( &$arrRes ){
		if( is_object( $this->_data ) ){
			$arrRes=$this->_data->getFiltered();
			if( !empty( $arrRes['sites_settings'] ) ){
				$arrRes['sites_settings']=unserialize($arrRes['sites_settings']);
			}
		}
		return $this;
	}
	
	public function getList( &$mixRes ){
		parent::getList( $mixRes );
		if( !empty( $mixRes ) ){
			if( isset( $mixRes['id'] ) ){
				$mixRes['sites_settings']=unserialize($mixRes['sites_settings']);
			}else{
				foreach( $mixRes as &$_res ){
					$_res['sites_settings']=unserialize($_res['sites_settings']);
				}
			}
		}
		return $this;
	}

	public function generateUniqueSecret(){
		$_secret = md5( rand(0,24*24*60) );
		$this->withSecretId( $_secret )->onlyOne()->getList( $_test );
		if( !empty( $_test ) ){
			$_secret=$this->generateUniqueSecret();
		}
		return $_secret;
	}
	
	protected $_withSecretId=false;
	
	public function withSecretId( $_id ){
		if( isset( $_id ) && !empty( $_id ) ){
			$this->_withSecretId=$_id;
		}
		return $this;
	}

	protected function init(){
		parent::init();
		$this->_withSecretId=false;
	}

	protected function assemblyQuery(){
		parent::assemblyQuery();
		if( $this->_withSecretId ){
			$this->_crawler->set_where('d.secret_id="'.$this->_withSecretId.'"' );
		}
	}
}
?>