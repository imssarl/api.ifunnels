<?php
/**
 * Project Users Extension
 * @category project
 * @package ProjectUsersExtension
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2008, Rodion Konnov
 * @author Rodion Konnov <kindzadza@mail.ru>
 * @date 21.11.2008
 * @version 1.0
 */


/**
 * Create and edit user account
 * @category project
 * @package ProjectUsersExtension
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2008, Rodion Konnov
 * @author Rodion Konnov <kindzadza@mail.ru>
 * @date 21.11.2008
 * @version 1.0
 */


class Project_Users_Profile extends Project_Users {
	public $u_minpassword_len=3;
	public $u_maxnick_len=40;


	private $new_base=array();
	private $new_stencil=array();
	private $new_additional=array();
	private $new_files=array();
	private $new_objI;

	private $old_base=array();
	private $old_stencil=array();
	private $old_additional=array();
	private $old_files=array();
	private $old_objI;

	// если нужно списки можно править (например добавить timezone)
	private $base_fields=array(
		'full'=>array( 'nickname', 'email', 'passwd', 'passwd_re', 'timezone' ),
		'generate_password'=>array( 'nickname', 'email', 'timezone' ),
		'without_nickname'=>array( 'email', 'passwd', 'passwd_re', 'timezone' ),
		'without_email'=>array( 'nickname', 'passwd', 'passwd_re', 'timezone' ),
	);
	private $form_factor=''; // смотрим на ключи $this->base_fields
	private $base_fields_mask=array(
		'nickname'=>array(
			'sys_name'=>'nickname',
			'title'=>'User Name',
			'type'=>'text',
		),
		'passwd'=>array(
			'sys_name'=>'passwd',
			'title'=>'Password',
			'type'=>'password',
		),
		'passwd_re'=>array(
			'sys_name'=>'passwd_re',
			'title'=>'Confirm password',
			'type'=>'password',
		),
		'email'=>array(
			'sys_name'=>'email',
			'title'=>'Email',
			'type'=>'text',
		),
		/*'timezone'=>array(
			'sys_name'=>'timezone',
			'title'=>'Location',
			'type'=>'select',
		),*/
		'cost_id_tmp'=>array(
			'sys_name'=>'cost_id_tmp',
			'title'=>'Тарифный план',
			'type'=>'select',
		),
	);

	private $fields_base=array();
	private $fields_additional=array();

	private $current_groups=array();
	private $check_additional_by_stencil=true;
	private $activate_account_by_default=false; // по умолчанию профайл неактивирован


	public $user_id=0;
	public $real_passwd=''; // пароль не в md5

	public function __construct( $_strFactor='' ) {
		parent::__construct();
		$this->set_form_factor( $_strFactor );
	}

	public function set_form_factor( $_strFactor='' ) {
		if ( empty( $_strFactor )||empty( $this->base_fields[$_strFactor] ) ) {
			return false;
		}
		$this->form_factor=$_strFactor;
		$this->fields_base=array_intersect_key( $this->base_fields_mask, array_flip( $this->base_fields[$this->form_factor] ) );
		return !empty( $this->fields_base );
	}

	public function set_account_defult_activate() {
		$this->activate_account_by_default=true;
	}

	public function set_groups( $_arrGrp=array() ) {
		if ( empty( $_arrGrp ) ) {
			return false;
		}
		$_intTmp=key( $_arrGrp );
		if ( is_int( $_intTmp )&&!empty( $_intTmp ) ) {
			$this->current_groups=$_arrGrp;
		} elseif ( !$this->objR->r_get_ids_by_title( $this->current_groups, $_arrGrp ) ) {
			trigger_error( ERR_PHP.'|Groups not exists: '.print_r( $_arrGrp, true ) );
			return false;
		}
		return true;
	}

	public function set_item_checker( $_bool=false ) {
		$this->check_additional_by_stencil=$_bool;
	}

	public function set_stencil( $_str='' ) {
		if ( empty( $_str ) ) {
			return false;
		}
		$this->new_objI=new Core_Items_Single();
		if ( !$this->new_objI->setStencil( $_str ) ) {
			return false;
		}
		$this->new_objI->set_field_checker( $this->check_additional_by_stencil );
		$this->fields_additional=$this->new_objI->objS->stencil['arrFields'];
		/*$this->new_objI=new Core_Items( $_strStencil );
		$this->new_objI->get_blank_item( $_arr );
		$this->new_objI->set_field_checker( $this->check_additional_by_stencil );
		$this->fields_additional=$_arr['arrFields'];
		unSet( $_arr['arrFields'] );
		$this->new_stencil=$_arr;*/
		return true;
	}

	public function set_user( $_intId=0 ) {
		if ( !$this->getProfileById( $_arrRes, $_intId ) ) {
			return false;
		}
		if ( !empty( $_arrRes['item_id'] ) ) {
			$this->old_stencil=$_arrRes['stencil'];
			$this->old_additional=$_arrRes['additional'];
			$this->old_objI=new Core_Items( $this->old_stencil['sys_name'] );
			/*$this->old_objI->get_item_fields_full( $this->old_additional_full, $_arrRes['item_id'] );
			foreach( $this->old_additional_full as $k=>$v ) {
				if ( empty( $v['file_info'] ) ) {
					continue;
				}
				$this->old_additional[$k]=array();
				$this->old_additional[$k]['file_info']=$v['file_info'];
			}*/
			$this->current_groups=$_arrRes['groups'];
		}
		unSet( $_arrRes['groups'], $_arrRes['right'], $_arrRes['right_parsed'], $_arrRes['additional'], $_arrRes['stencil'] );
		$this->old_base=$_arrRes;
		return true;
	}

	public function set_base( &$arrErr, &$arrRes, $_arrSet=array() ) {
		$this->base_errors=array();
		$this->base_post=Core_A::array_check( $_arrSet, $this->post_filter );
		foreach( $this->fields_base as $v ) {
			$f='check_'.$v['sys_name'];
			$this->$f();
		}
		$arrErr=$this->base_errors;
		$arrRes=$this->base_post;
		return empty( $arrErr )? $this->set_base_data():false;
	}

	private function check_exists( $_str='', $_strField='email' ) {
		$_str=Core_Sql::fixInjection( $_str );
		if ( empty( $this->old_base['id'] ) ) {
			$_intFlg=Core_Sql::getCell( 'SELECT 1 FROM u_users WHERE '.$_strField.'='.$_str.' LIMIT 1' );
		} else {
			$_intFlg=Core_Sql::getCell( 'SELECT 1 FROM u_users WHERE '.$_strField.'='.$_str.' AND id!="'.$this->old_base['id'].'" LIMIT 1' );
		}
		return !empty( $_intFlg );
	}

	private function check_email() {
		$return=true;
		if ( empty( $this->base_post['email'] )||!Core_Common::checkEmail( $this->base_post['email'] ) ) {
			$this->base_errors['email']=true;
			$return=false;
		}
		if ( $this->check_exists( $this->base_post['email'], 'email' ) ) {
			$this->base_errors['email']=true;
			$this->base_errors['email_exists']=true;
			$return=false;
		}
		if (isSet($this->base_post['email_re']) and $this->base_post['email_re']!=$this->base_post['email']) {
			$this->base_errors['email_re']=true;
			$return=false;
		}
		return $return;
	}

	private function check_passwd() {
		if ( empty( $this->base_post['passwd'] )&&!empty( $this->old_base['id'] ) ) {
			return true;
		}
		if ( empty( $this->base_post['passwd'] ) ) {
			$this->base_errors['passwd']=true;
			return false;
		}
		if ( strlen( $this->base_post['passwd'] )<$this->u_minpassword_len ) {
			$this->base_errors['passwd']=true;
			$this->base_errors['passwd_len']=true;
			return false;
		}
		return true;
	}

	private function check_timezone() {
		if ( empty( $this->base_post['timezone'] ) ) {
			$this->base_errors['timezone']=true;
			return false;
		}
		return true;
	}

	private function check_cost_id_tmp() {
		if ( empty( $this->old_base['id'] )&&empty( $this->base_post['cost_id_tmp'] ) ) {
			return false;
		}
		return true;
	}

	private function check_passwd_re() {
		if ( empty( $this->base_post['passwd'] )&&!empty( $this->old_base['id'] ) ) {
			return true;
		}
		if ( empty( $this->base_post['passwd_re'] ) ) {
			$this->base_errors['passwd_re']=true;
			return false;
		}
		if ( empty( $this->base_errors['passwd'] )&&$this->base_post['passwd_re']!=$this->base_post['passwd'] ) {
			$this->base_errors['passwd_re']=true;
			return false;
		}
		return true;
	}

	private function check_nickname() {
		if ( empty( $this->base_post['nickname'] )||preg_match( "/[^\w\d]+/i", $this->base_post['nickname'] ) ) {
			$this->base_errors['nickname']=true;
			return false;
		}
		if ( strlen( $this->base_post['nickname'] )>$this->u_maxnick_len ) {
			$this->base_errors['nickname']=true;
			$this->base_errors['nickname_len']=true;
			return false;
		}
		if ( $this->check_exists( $this->base_post['nickname'], 'nickname' ) ) {
			$this->base_errors['nickname']=true;
			$this->base_errors['nickname_exists']=true;
			return false;
		}
		return true;
	}

	// подготавливаем данные
	private function set_base_data() {
		$this->new_base=$this->base_post;
		if ( empty( $this->old_base['id'] ) ) {
			if ( $this->activate_account_by_default ) {
				$this->new_base['flg_status']=1;
			} else {
				$this->new_base['flg_status']=0;
				// если аккаунт неактивировали то у нас есть уникальный код для активации (по ссылке непример)
				$this->sv_get_uniq_code( $_strCode, 'reg_code', 'u_users' );
				$this->new_base['reg_code']=md5( $_strCode );
			}
			$this->new_base['added']=time();
		} else {
			$this->new_base['id']=$this->old_base['id'];
		}
		if ( !empty( $this->new_base['passwd'] ) ) {
			$this->real_passwd=$this->new_base['passwd'];
			$this->new_base['passwd']=md5( $this->new_base['passwd'] );
		} elseif ( empty( $this->old_base ) ) { // для нового профайла генерим пароль (т.к. все чекеры пройдены)
			$this->real_passwd=Core_A::rand_string(6);
			$this->new_base['passwd']=md5( $this->real_passwd );
		}
		return true;
	}

	public function set_no_change_additional( $_arrSet=array() ) {
		$this->no_change_additional=array_flip( $_arrSet );
	}

	public function set_files( &$arrErr, $_arrDat=array() ) {
		if ( empty( $_arrDat ) ) {
			return false;
		}
		$this->new_files=$_arrDat;
		return true;
	}

	public function set_additional( Project_Users_Sample &$objMod, &$arrErr, &$arrRes, $_arrDat=array() ) {
		if ( empty( $_arrDat ) ) {
			return false;
		}
		if ( !$objMod->set_additional( $arrErr, $arrRes, $_arrDat ) ) {
			return false;
		}
		$this->new_additional=array(
			'stencil_id'=>$this->new_objI->objS->stencil['id'],
			'id'=>'',
			'arrFields'=>array()
		);
		if ( !empty( $this->old_base['item_id'] ) ) {
			$this->new_additional['id']=$this->old_base['item_id'];
		}
		foreach( $this->fields_additional as $k=>$v ) {
			if ( isSet( $this->no_change_additional[$k] ) ) {
				$this->new_additional['arrFields'][$k]['content']=$this->old_additional['arrFields'][$k]['content'];
				continue;
			}
			/*if ( empty( $arrRes[$k] )&&!empty( $this->old_additional[$k] ) ) {
				$this->new_additional['arrFields'][$k]['content']=$this->old_additional[$k];
				$this->new_additional['arrFields'][$k]['del']=true;
			} else {*/
				if ( is_array( $arrRes[$k] ) ) {
					$this->new_additional['arrFields'][$k]=$arrRes[$k];
				} else {
					$this->new_additional['arrFields'][$k]['content']=$arrRes[$k];
				}
			//}
		}
		if ( !$this->new_objI->check_item( $this->new_additional, $this->new_files ) ) {
			$this->new_objI->get_fields_errors( $arrErr['arrFields'] );
			// добавляем данные по файлам в пост
			foreach( $arrRes as $k=>$v ) {
				if ( is_array( $arrRes[$k] ) ) {
					$arrRes[$k]=$this->old_additional['arrFields'][$k]['content'];
				}
			}
			return false;
		}
		return true;
	}

	// сохранение профайла
	public function save_profile() {
		if ( !$this->save_base_field() ) {
			return false;
		}
		if ( is_object( $this->old_objI )&&!is_object( $this->new_objI ) ) { // если убрали дополнительные поля
			$this->old_objI->del_item( $this->old_base['item_id'] );
			Core_Sql::setInsertUpdate( 'u_users', array( 'id'=>$this->user_id, 'item_id'=>0 ) );
		}
		if ( !empty( $this->new_additional ) ) { // сохраняем дополнительные поля
			$this->new_additional['user_id']=$this->user_id;
			if ( $this->new_objI->set_item( $_arrTmpItem, $_arrTmpErr, $this->new_additional, $this->new_files ) ) {
				Core_Sql::setInsertUpdate( 'u_users', array( 'id'=>$this->user_id, 'item_id'=>$this->new_objI->item_id ) );
			}
		}
		return true;
	}

	// сохранение основных полей
	private function save_base_field() {
		if ( empty( $this->new_base )&&empty( $this->old_base ) ) {
			trigger_error( ERR_PHP.'|no profile base data setted for new account' );
			return false;
		}
		if ( empty( $this->new_base ) ) { // в этом случае возможно редактирование только additional полей
			$this->user_id=$this->old_base['id'];
			return true;
		}
		if ( empty( $this->current_groups )&&empty( $this->old_base ) ) {
			trigger_error( ERR_PHP.'|no profile group selected for new account' );
			return false;
		}
		//$this->update_cookies();
		$_data=new Core_Data();
		$this->user_id=Core_Sql::setInsertUpdate( 'u_users', $_data->setMask( $this->fields )->getValidCurrent( $this->new_base ) );
		if ( !empty( $this->current_groups ) ) {
			$this->objManageAccess->setGroupsByIds( $this->user_id, $this->current_groups ); // наделяем правами
		}
		return !empty( $this->user_id );
	}

	private function update_cookies() {
		$_arr=$this->new_base+$this->old_base;
		if ( empty( $_arr['id'] ) ) { // для нового профайла куку не ставим
			return false;
		}
		return $this->write_cookies( $_arr );
	}

	public function get_new_profile( &$arrRes ) {
		if ( !$this->u_getprofile( $arrRes, $this->user_id ) ) {
			return false;
		}
		return true;
	}

	public function get_timezone( &$arrRes ) {
		Core_Datetime::getInstance()->get_timezone_to_select( $arrRes );
	}

	public function get_costs( &$arrRes ) {
		$_objTm=new Core_Tariff_Manage();
		$_objTm->get_costs_toselect( $arrRes );
	}

	public function get_fields( &$arrBase, &$arrAdd ) {
		$arrBase=$this->fields_base;
		$arrAdd=$this->fields_additional;
	}

	public function get_field_types( &$arrTypes ) {
		$arrTypes=$this->new_objI->objS->field_types;
		//$this->new_objI->get_filed_types( $arrTypes );
	}

	public function get_profile( &$arrBase, &$arrAdd ) {
		$arrBase=$this->old_base;
		$arrBase['passwd']=''; // всёравно там хэш
		$arrAdd=$this->old_additional;
	}

	// подтверждение оплаты аккаунта при редактировании профайла админом
	// назначение новой даты проплаты исходя из тарифного плана (next_payment)
	public function setPayed( $_intPlan=0 ) {
		if ( empty( $_intPlan ) ) {
			return false;
		}
		$_obj=new Core_Tariff( $_intPlan );
		if ( !$_obj->get_tariff( $_arrTariff ) ) {
			return false;
		}
		if ( !$this->get_new_profile( $_arrUser ) ) {
			return false;
		}
		Core_Sql::setUpdate( 'u_users', array( 
			'id'=>$_arrUser['id'], 
			'cost_id'=>$_intPlan,
			'next_payment'=>strtotime( '+ '.$_arrTariff['arrPrd']['year'].' year '.$_arrTariff['arrPrd']['year'].' month '.$_arrTariff['arrPrd']['day'].' day' ) 
		) );
		return true;
	}
}
?>