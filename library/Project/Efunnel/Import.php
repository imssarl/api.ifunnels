<?php
class Project_Efunnel_Import extends Core_Data_Storage {

	protected $_table='lpb_efunnels_imports';
	protected $_fields=array( 'id', 'user_id', 'email_list', 'post', 'added' );

	public static function install(){
		Core_Sql::setExec("drop table if exists lpb_efunnels_imports");
		Core_Sql::setExec( "CREATE TABLE `lpb_efunnels_imports` (
			`id` INT(11) NOT NULL AUTO_INCREMENT,
			`user_id` INT(11) NOT NULL DEFAULT '0',
			`email_list` LONGTEXT NULL DEFAULT NULL,
			`post` TEXT NULL DEFAULT NULL COLLATE 'utf8_unicode_ci',
			`added` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			UNIQUE INDEX `id` (`id`)
		)
		COLLATE='utf8_general_ci'
		ENGINE=InnoDB;" );
	}

	public function getList( &$mixRes ){
		parent::getList($mixRes);
		if( array_key_exists( 0, $mixRes ) ){
			foreach( $mixRes as &$_arrZeroData ){
				if( isset( $_arrZeroData['email_list'] ) ){
					$_oldSettings=$_arrZeroData['email_list'];
					$_arrZeroData['email_list']=unserialize( base64_decode( $_arrZeroData['email_list'] ) );
					if( $_arrZeroData['email_list']===false ){
						$_arrZeroData['email_list']=$_oldSettings;
					}
				}
				if( isset( $_arrZeroData['post'] ) ){
					$_oldSettings=$_arrZeroData['post'];
					$_arrZeroData['post']=unserialize( base64_decode( $_arrZeroData['post'] ) );
					if( $_arrZeroData['post']===false ){
						$_arrZeroData['post']=$_oldSettings;
					}
				}
			}
		}elseif( isset( $mixRes['email_list'] ) ){
			$_oldSettings=$mixRes['email_list'];
			$mixRes['email_list']=unserialize( base64_decode( $mixRes['email_list'] ) );
			if( $mixRes['email_list']===false ){
				$mixRes['email_list']=$_oldSettings;
			}
			$_oldSettings=$mixRes['post'];
			$mixRes['post']=unserialize( base64_decode( $mixRes['post'] ) );
			if( $mixRes['post']===false ){
				$mixRes['post']=$_oldSettings;
			}
		}
		$this->init();
		return $this;
	}
}
?>