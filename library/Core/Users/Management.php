<?php


/**
 * Users Management
 */
class Core_Users_Management extends Core_Data_Storage {

	public static $flags=array(
		'flg_active'=>'Active',
		'flg_confirm'=>'Confirm',
		'flg_approve'=>'Approve',
//		'flg_cancel'=>'Cancel',
//		'flg_expire'=>'Expire',
		'flg_maintenance'=>'Maintenance',
	);

	protected $_table='u_users';

	protected $_fields=array(
		'id',
		'parent_id',
		'package_id',
		'flg_confirm',
		'flg_approve',
		'flg_cancel',
		'flg_expire',
		'flg_rights',
		'dpa_agree_date', 'dpa_agree_ip',
		'amount',
		'email',
		'passwd',
		'nickname',
		'lang',
		'timezone',
		'code_confirm',
		'code_forgot',
		'code_payment',
		'forgot',
		'settings',
		'expiry',
		'added' 
	);

	private $_del=array();

	const WRITE_READ_RIGHT=0,READ_RIGHT=1;

	/**
	 * уникальный md5 хэш для полей указанных в $_strField
	 *
	 * @param array $arrProfile - массив с профайлом пользователя
	 * @param string $_strField - поле код для которого надо сгенерить
	 * @return boolean
	 */
	public function setCode( &$arrProfile, $_strField='' ) {
		if ( empty( $arrProfile['id'] )||empty( $_strField )||!in_array( $_strField, $this->_fields ) ) {
			return false;
		}
		$_code=new Core_Common_Code();
		$arrProfile[$_strField]=$_code->setTable( $this->_table )->setField( $_strField )->checkUniq()->getCode();
		Core_Sql::setUpdate( $this->_table, array( 'id'=>$arrProfile['id'], $_strField=>$arrProfile[$_strField] ) );
		return true;
	}

	/**
	 * текущее время для полей указанных в $_strField
	 *
	 * @param array $arrProfile - массив с профайлом пользователя
	 * @param string $_strField - поле врмя для которого надо указать
	 * @return boolean
	 */
	public function setTime( &$arrProfile, $_strField='' ) {
		if ( empty( $arrProfile['id'] )||empty( $_strField )||!in_array( $_strField, $this->_fields ) ) {
			return false;
		}
		$arrProfile[$_strField]=time();
		Core_Sql::setUpdate( $this->_table, array( 'id'=>$arrProfile['id'], $_strField=>$arrProfile[$_strField] ) );
		return true;
	}

	/**
	 * аспект кторый вызывается до выполнения set()
	 * после переназначения тут например можно организовать проверку полей
	 *
	 * @return boolean
	 */
	protected function beforeSet() {
		$this->_data->setFilter( array( 'trim', 'clear' ) );
		if ( !empty($this->_data->filtered['passwd']) ) { // например при редактировании пароль необязательно будет изменяться
			$this->_data->setElement( 'passwd', Zend_Crypt::hash( 'md5', $this->_data->filtered['passwd'] ).Zend_Registry::get( 'config' )->user->salt );
		}
		// корректируем флаги
		if ( $this->_withFlags ) {
			foreach( self::$flags as $k=>$v ) {
				$_arrFlags[$k]=empty( $this->_data->filtered[$k] )? 0:1;
			}
			$this->_data->setElements( $_arrFlags );
		}
		if(!empty($this->_data->filtered['popup_width'])&&isset($this->_data->filtered['flg_maintenance'])){
			$this->_data->setFilter( array( 'trim', 'clear' ) );
		}
		return true;
	}

	/**
	 * аспект кторый вызывается после выполнения set()
	 * после переназначения тут например можно сделать какие-либо действия после сохранения данных
	 *
	 * @return boolean
	 */
	protected function afterSet() {
		Core_Users::getInstance()->reload();
		if ( empty( $this->_withGroups ) ) {
			return true; // если группы не указаны то это прото обновление профайла
		}
		$_bool=true;
		// после создания пользователя добавляем его в указанные группы прав
		$_group=new Core_Acs_Groups();
		$_bool=$_group->withIds( $this->_data->filtered['id'] )->setGroupByName( $this->_withGroups );
		$this->init();
		return $_bool;
	}

	/**
	 * аспект кторый вызывается до выполнения каждого set() в setMass()
	 * после переназначения тут например можно организовать накапливание данных для пост обработки в afterSetMass()
	 *
	 * @return boolean
	 */
	protected function beforeSetMass( $k, $_arrRow=array() ) {
		if ( !empty( $_arrRow['del'] ) ) {
			$this->_del[]=$_arrRow['id'];
			return false;
		}
		return true;
	}

	/**
	 * аспект кторый вызывается после выполнения всех set() в setMass()
	 * после переназначения тут например можно сделать какие-либо действия с данными накопленными в beforeSetMass()
	 *
	 * @return boolean
	 */
	protected function afterSetMass() {
		if ( !empty( $this->_del ) ) {
			$this->withIds( $this->_del )->del();
			$this->_del=array();
		}
		return true;
	}

	/**
	 * удаление одной или нескольких записей
	 *
	 * @return boolean
	 */
	public function del() {
		if ( empty( $this->_withIds ) ) {
			return false;
		}
		Core_Sql::setExec( '
			DELETE u, l FROM '.$this->_table.' u
			LEFT JOIN '.Core_Acs_Groups::$tableLink.' l ON l.user_id=u.id
			WHERE u.id IN('.Core_Sql::fixInjection( $this->_withIds ).')
		' );
		$this->init();
		return true;
	}

	private $_withEmail=array(); // c данными email

	protected $_withGroups=array(); // группы прав пользователей

	private $_withPasswd=''; // c данными passwd

	private $_withNickname=''; // c данными nickname

	private $_withField=array(); // пара ключ - значение/я. например для проверки кодов или флагов у конкретного пользователя

	private $_withRights=array(); // список прав

	private $_withFlags=false; // обновлять или создавать профайл с обработкой флагов (нужно для админки)

	private $_forBackend=false;

	private $_onlyActive=false;

	private $_onlyConfirm=false;

	protected function init() {
		parent::init();
		$this->_withEmail=array();
		$this->_withGroups=array();
		$this->_withPasswd='';
		$this->_withNickname='';
		$this->_withField=array();
		$this->_withRights=array();
		$this->_withFlags=false;
		$this->_onlyActive=false;
		$this->_onlyConfirm=false;
		$this->__forBackend=false;
	}

	public function withFlags() {
		$this->_withFlags=true;
		return $this;
	}

	public function withField( $_strField='', $_mixValue=array() ) {
		if ( empty( $_strField )||empty( $_mixValue ) ) {
			return $this;
		}
		$this->_withField=array( 'field'=>$_strField, 'value'=>$_mixValue );
		return $this;
	}

	public function withEmail( $_mixed=array() ) {
		if ( !empty( $_mixed ) ) {
			$this->_withEmail=$_mixed;
		}
		$this->_cashe['email']=$this->_withEmail;
		return $this;
	}

	public function withNickname( $_mixed=array() ) {
		if ( !empty( $_mixed ) ) {
			$this->_withNickname=$_mixed;
		}
		return $this;
	}

	public function withPasswd( $_str='' ) {
		if ( !empty( $_str ) ) {
			$this->_withPasswd=$_str;
		}
		return $this;
	}

	public function withGroups( $_mixed=array() ) {
		if ( !empty( $_mixed ) ) {
			$this->_withGroups=is_array( $_mixed )? $_mixed:array( $_mixed );
		}
		return $this;
	}

	public function onlyActive(){
		$this->_onlyActive=true;
		return $this;
	}

	public function onlyConfirm(){
		$this->_onlyConfirm=true;
		return $this;
	}

	public function forBackend(){
		$this->_forBackend=true;
		return $this;
	}

	/**
	 * либо массив с правами, либо перечисление через запятую
	 * отсылать системные имена прав (см. u_rights.sys_name)
	 *
	 * @return object
	 */
	public function withRights() {
		$_mixArgs=func_get_args();
		if ( !empty( $_mixArgs[0] ) ) {
			$this->_withRights=is_array( $_mixArgs[0] )? $_mixArgs[0]:$_mixArgs;
		}
		return $this;
	}

	protected function assemblyQuery() {
		parent::assemblyQuery();
		if ( !empty( $this->_withField ) ) {
			$this->_crawler->set_where( 'd.'.$this->_withField['field'].' IN('.Core_Sql::fixInjection( $this->_withField['value'] ).')' );
		}
		if ( !empty( $this->_withPasswd ) ) {
			$this->_crawler->set_where( 'd.passwd="'.Zend_Crypt::hash( 'md5', $this->_withPasswd ).Zend_Registry::get( 'config' )->user->salt.'"' );
		}
		if ( !empty( $this->_withEmail ) ) {
			$this->_crawler->set_where( 'd.email IN('.Core_Sql::fixInjection( $this->_withEmail ).')' );
		}
		if ( !empty( $this->_withNickname ) ) {
			$this->_crawler->set_where( 'd.nickname IN('.Core_Sql::fixInjection( $this->_withNickname ).')' );
		}
		if(!empty($this->_withGroups)){
			$this->_crawler->set_where('d.id IN (SELECT user_id FROM u_link l LEFT JOIN u_groups g ON g.id=l.group_id WHERE g.sys_name IN('. Core_Sql::fixInjection($this->_withGroups) .'))');
		}
		if( !empty( $this->_withRights ) ) {
			$this->_crawler->set_where('d.id IN (
				SELECT l.user_id 
				FROM u_link l
				INNER JOIN u_groups g ON g.id=l.group_id
				INNER JOIN u_rights2group r2g ON r2g.group_id=g.id
				INNER JOIN u_rights r ON r.id=r2g.rights_id
				WHERE r.sys_name IN('. Core_Sql::fixInjection($this->_withRights) .')
			)');
		}
		if( $this->_onlyActive ){
			$this->_crawler->set_where('d.flg_active=1');
		}
		if( $this->_onlyConfirm ){
			$this->_crawler->set_where('d.flg_confirm=1');
		}
	}

	public function getList( &$mixRes ) {
		$_forBackend=$this->_forBackend;
		parent::getList( $mixRes );
		if ( !empty( $mixRes['id'] ) ) { // если выбрана одна запись то подмешиваем доп инфу в профайл
			$mixRes['forBackend']=$_forBackend;
			Core_Acs::getUserAccessRights( $mixRes ); // права пользователя
			if ( !empty( $mixRes['package_id'] ) ) { // тарифный план
				$_package=new Core_Payment_Package();
				$_package
					->onlyOne()
					->withHided() // эту инфу достаём даже если тарифный план скрыт
					->withIds( $mixRes['package_id'] )
					->getList( $mixRes['arrPkg'] );
			}
			if( isset( $mixRes['settings'] ) ) $mixRes['settings'] = unserialize(base64_decode($mixRes['settings']));
		} else {
			foreach( $mixRes as &$_user ){
				if( isset( $_user['settings'] ) ) $_user['settings'] = unserialize(base64_decode($_user['settings']));
			}
		}
		return $this;
	}
}
?>