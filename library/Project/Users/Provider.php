<?php



/**
 * Users Providers
 */
class Project_Users_Provider extends Project_Users_Management {

	private $_flgNewSubUser=false;

	/**
	 * Логин в аккаунт SA через аккаунт SP. С сохранением родительского аккаунта в кэше, чтобы была возможность вернуться.
	 * @param $_intUserId
	 * @param $strCode
	 * @return bool
	 */
	public function loginAsSA( $_intUserId, $strCode ){
		if( empty($_intUserId)||empty($strCode) ){
			return false;
		}
		$this->withIds( $_intUserId )->onlyOne()->getList( $arrRes );
		$_code=sha1($arrRes['parent_id'].$arrRes['id'].date('Ymd',time()));
		if( $_code!=$strCode ){
			return false;
		}
		Core_Users::getInstance()->reload();
		Core_Users::getInstance()->withCashe()->setById( $_intUserId );
		return true;
	}

	/**
	 * Проверяет это SP пользователь или нет.
	 * @param $_userId
	 * @return bool
	 */
	public static function isSP( $_userId ){
		Core_Users::getInstance()->withCashe()->setById( $_userId );
		$_result=empty(Core_Users::$info['parent_id']);
		Core_Users::getInstance()->retrieveFromCashe();
		return $_result;
	}

	/**
	 * Обновляет права у всех суб. аккаунтов на текущие права родительского аккаунта.
	 * Запускается через крон-скрипт.
	 */
	public function updateRights(){
		$_users=new Core_Users_Management();
		$_users->withGroups(array('SP_USERS'))->getList( $arrUsers );
		$_groups=new Core_Acs_Groups();
		$_groups->getIdsBySysName( $arrRes, array('SA_USERS') );
		$_saGroupId=$arrRes[0];
		foreach( $arrUsers as $_user ){
			Core_Users::getInstance()->setById( $_user['id'] );
			$arrGroups=array();
			$arrSAUsers=array();
			if( $this->getList( $arrSAUsers )->checkEmpty() ){
				continue;
			}
			$_groups->withIds( $_user['id'] )->getGroupByUserId( $arrGroups );
			$arrGroups=array_flip($arrGroups);
			unset($arrGroups['SP_USERS']);
			unset($arrGroups['Maintenance']);
			$arrGroups['SA_USERS']=$_saGroupId;
			$arrGroups=array_flip($arrGroups);
			foreach( $arrSAUsers as $_account ){
				$tmpGroups=array();
				$_groups->withIds( $_account['id'] )->getGroupByUserId( $tmpGroups );
				if( $arrGroups==$tmpGroups ){ // Если права у родителя не менялись то и суб. аккам менять не нужно.
					continue;
				}
				$_groups->withIds( $_account['id'] )->setGroupByName( $arrGroups );
			}
		}
	}

	/**
	 * Возвращение в аккаунт SP из аккаунта SA.
	 * @return object
	 */
	public function loginAsSP(){
		return Core_Users::getInstance()->retrieveFromCashe();
	}

	protected function beforeSet(){
		$this->_data->setFilter( array('clear','trim') );
		if( !isset( $this->_data->filtered['id'] ) && !Core_Data_Errors::getInstance()->setData( $this->_data->setFilter( array('clear','trim') ) )->setValidators(array(
			'passwd'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ),
		))->isValid() ){
			return false;
		}elseif( !Core_Data_Errors::getInstance()->setData( $this->_data->setFilter( array('clear','trim') ) )->setValidators(array(
			'email'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ),
			'buyer_name'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ),
			'buyer_surname'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ),
			'buyer_address'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ),
			'buyer_city'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ),
			'buyer_province'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ),
			'buyer_country'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ),
			'buyer_zip'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ),
			'buyer_phone'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' )
		))->isValid() ){
			return false;
		}
		$_user=new Core_Users_Management();
		$tmpRes=array();
		$_user->withEmail( $this->_data->filtered['email'] )->onlyOne()->getList( $tmpRes );
		if( $tmpRes['parent_id'] != Core_Users::$info['id'] ){
			return Core_Data_Errors::getInstance()->setError('user with <'.$this->_data->filtered['email'].'> email address already exists');
		}
		if( empty(Core_Users::$info['id']) ){
			throw new Project_Users_Exception('Can\'t find user');
		}
		if(empty(Core_Users::$info['parent_id'])){
			$_setData=array(
				'parent_id'=>Core_Users::$info['id'],
				'popup_width'=>70,
				'popup_height'=>70,
				'pagging_links'=>5,
				'pagging_rows'=>10,
				'flg_confirm'=>1,
				'flg_active'=>1,
				'flg_approve'=>1,
				'flg_maintenance'=>0,
				'flg_rights'=>$this->_data->filtered['flg_rights'],
				'approve'=>time(),
			);
			if( isset( $tmpRes['id'] ) ){
				$_setData['id']=$tmpRes['id'];
			}
			$this->_data->setElements($_setData);
			$this->_flgNewSubUser=true;
		}
		return parent::beforeSet();
	}

	protected function afterSet(){
		if( $this->_flgNewSubUser ){ // при создании нового суб пользователя, добавляем ему группы родителя
			$_groups=new Core_Acs_Groups();
			$_groups->withIds( $this->_data->filtered['parent_id'] )->getGroupByUserId( $arrGroups );
			$_groups->getIdsBySysName($arrRes,array('SA_USERS'));
			$arrGroups=array_flip($arrGroups);
			unset($arrGroups['SP_USERS']);
			unset($arrGroups['Maintenance']);
			$arrGroups['SA_USERS']=$arrRes[0];
			$arrGroups=array_flip($arrGroups);
			$_groups->withIds( $this->_data->filtered['id'] )->setGroupByIds($arrGroups);
		}
		return true;
	}

	public function del(){
		$_user=new Core_Users_Management();
		$_user->withIds( $this->_withIds )->onlyOne()->getList( $arrProfile );
		if( $arrProfile['parent_id']!=Core_Users::$info['id'] ){
			return Core_Data_Errors::getInstance()->setError('You can\'t delete this user');
		}
		return parent::del();
	}

	protected function assemblyQuery(){
		parent::assemblyQuery();
		$this->_crawler->set_where('d.parent_id='.Core_Users::$info['id']);
	}

	public function getErrors( &$arrErrors ){
		$arrErrors=Core_Data_Errors::getInstance()->getErrors();
	}

	public function getList(&$arrRes){
		parent::getList( $arrRes );
		if( is_array($arrRes[0]) ){
			foreach( $arrRes as &$_account ){
				$_account['link']=sha1($_account['parent_id'].$_account['id'].date('Ymd',time()));
			}
		}
		return $this;
	}
}
?>