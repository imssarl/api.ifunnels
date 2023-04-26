<?php
class Project_Iam_Users extends Core_Data_Storage {
/*
CREATE TABLE `iam_users` (
`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`email` VARCHAR(255) NOT NULL,
`client_ip` VARCHAR(255) NOT NULL DEFAULT 'undefined',
`clickbank_id` VARCHAR(255) NOT NULL,
`edited` INT(11) UNSIGNED NOT NULL DEFAULT '0',
`added` INT(11) UNSIGNED NOT NULL DEFAULT '0',
 PRIMARY KEY (`id`)
)
COLLATE='utf8_general_ci'
ENGINE=MyISAM;

CREATE TABLE `iam_users2form` (
	`user_id` INT(11) UNSIGNED NOT NULL DEFAULT '0',
	`form_id` INT(11) UNSIGNED NOT NULL DEFAULT '0',
	PRIMARY KEY (`user_id`, `form_id`)
);
*/
	protected $_table='iam_users';
	protected $_tableUser2Form='iam_users2form';

	protected $_fields=array( 'id','email','client_ip','clickbank_id','sid','edited','added' );
	protected $_fieldsUser2Form=array( 'user_id','form_id' );
	
	public static function getTableName(){
		$_obj=new Project_Iam_Users();
		return $_obj->_table;
	}

	protected function beforeSet(){
		$this->_data->setFilter('trim');
		$_formId=0;
		if( isset( $this->_data->filtered['form_id'] ) ){
			$_formId=$this->_data->filtered['form_id'];
		}
		if( !Core_Data_Errors::getInstance()->setData( $this->_data->setFilter('trim','clear') )->setValidators( array(
			'email'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_EmailAddress' ),
			'clickbank_id'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' )
		) )->isValid() ){
			return Core_Data_Errors::getInstance()->setError('Incorrect entered data.');
		}
		if( empty( $this->_data->filtered['id'] ) && (int)Core_Sql::getCell( 'SELECT count(*) FROM '.$this->_table.' WHERE email='.Core_Sql::fixInjection( $this->_data->filtered['email'] ) ) != 0 ){
			$this->_data->setElement( 'id', Core_Sql::getCell( 'SELECT id FROM '.$this->_table.' WHERE email='.Core_Sql::fixInjection( $this->_data->filtered['email'] ) ) );
		}else{
			$this->_data->setElement( 'id', Core_Sql::setInsert( $this->_table, array( 'email'=>$this->_data->filtered['email'], 'added'=>time() )) );
		}
		if( !empty( $_formId ) ){
			$this->addLink( $this->_data->filtered['id'], $_formId );
		}
		return true;
	}

	public function getLinksByUser( $_userId=false ){
		$_return=array();
		if( !empty( $_userId ) ){
			$_return=Core_Sql::getField( 'SELECT form_id FROM '.$this->_tableUser2Form.' WHERE user_id='.Core_Sql::fixInjection( $_userId ) );
		}
		return $_return;
	}

	public function addLink( $_userId=false, $_formId=false ){
		if( empty( $_formId ) || empty( $_userId ) ){
			return true;
		}
		if( Core_Sql::getCell( 'SELECT count(*) FROM '.$this->_tableUser2Form.' WHERE user_id='.Core_Sql::fixInjection( $_userId ).' AND form_id='.Core_Sql::fixInjection( $_formId ) ) == 0 ){
			Core_Sql::setInsert( $this->_tableUser2Form, array(
				'user_id'=>$_userId,
				'form_id'=>$_formId
			));
		}
		return true;
	}

	public function removeLink( $_userId=false, $_formId=false ){
		if( empty( $_formId ) || empty( $_userId ) ){
			return true;
		}
		Core_Sql::setExec( 'DELETE FROM '.$this->_tableUser2Form.' WHERE user_id='.Core_Sql::fixInjection($_userId).' AND form_id='.Core_Sql::fixInjection($_formId) );
		return true;
	}
	
	public function activate(){
		if( !empty( $this->_withIds ) ){
			Core_Sql::setExec( 'UPDATE '.$this->_table.' SET `flg_active`=1 WHERE id IN ('.Core_Sql::fixInjection( $this->_withIds ).')' );
			return true;
		}
		return false;
	}
	
	public function getList( &$mixRes ){
		$_withLinks=false;
		if( $this->_withLinks ){
			$_withLinks=true;
		}
		parent::getList( $mixRes );
		if( !empty( $mixRes ) && $_withLinks ){
			$_iam=new Project_Iam();
			if( isset( $mixRes['id'] ) ){
				$_iam->withUserId( $mixRes['id'] )->getLinks( $mixRes['links_selected'] );
				$mixRes['forms']=$this->getLinksByUser( $mixRes['id'] );
			}else{
				foreach( $mixRes as &$_res ){
					$_iam->withUserId( $_res['id'] )->getLinks( $_res['links_selected'] );
					$_res['forms']=$this->getLinksByUser( $_res['id'] );
				}
			}
		}
		return $this;
	}

	public function getFormActivationsCount( $_id=0 ){
		return Core_Sql::getCell( 'SELECT count(*) FROM '.$this->_table.' WHERE form_id IN ('.Core_Sql::fixInjection( $_id ).')' );
	}

	protected $_withSiteId=false;
	protected $_withEmail=false;
	protected $_withLinks=false;
	protected $_onlyCBIDs=false;

	public function withCBID( $id=false ){
		$this->_withCBID=$id;
		return $this;
	}

	public function withSiteId( $id=false ){
		$this->_withSiteId=$id;
		return $this;
	}
	
	public function withEmail( $email=false ){
		$this->_withEmail=$email;
		return $this;
	}
	
	public function withLinks(){
		$this->_withLinks=true;
		return $this;
	}
	
	public function onlyCBIDs(){
		$this->_onlyCBIDs=true;
		return $this;
	}

	protected function init(){
		parent::init();
		$this->_withCBID=false;
		$this->_withSiteId=false;
		$this->_withEmail=false;
		$this->_onlyCBIDs=false;
		$this->_withLinks=false;
	}

	protected function assemblyQuery(){
		parent::assemblyQuery();
		if( $this->_withCBID ){
			$this->_crawler->set_where('d.clickbank_id='.Core_Sql::fixInjection( $this->_withCBID ) );
		}
		if( $this->_withSiteId ){
			$this->_crawler->set_from('JOIN iam_users2sites c ON d.id=c.user_id' );
			$this->_crawler->set_where('c.site_id='.(int)$this->_withSiteId );
		}
		if( $this->_withEmail ){
			$this->_crawler->set_where('d.email LIKE '.Core_Sql::fixInjection( '%'.$this->_withEmail.'%' ) );
		}
		if( $this->_onlyCBIDs ){
			$this->_crawler->clean_select();
			$this->_crawler->set_select( 'd.clickbank_id' );
		}
	}

	public function del(){
		if( !empty( $this->_withIds ) ){
			$_model=new Project_Iam();
			$_model
				->removeLinks( array(), $this->_withIds );
		}
		parent::del();
	}
}
?>