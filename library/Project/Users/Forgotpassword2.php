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
 * Forgot password 2
 * @internal пользователь сам меняет пароль по ссылке с уникальным кодом
 * ссылка действительна n-дней
 * @category project
 * @package ProjectUsersExtension
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2008, Rodion Konnov
 * @author Rodion Konnov <kindzadza@mail.ru>
 * @date 21.11.2008
 * @version 1.0
 */


class Project_Users_Forgotpassword2 extends Project_Users {
	public $f_time_interval=4320; // код действителен в течении трёх дней

	public function __construct() {
		$this->f_clear_old_sended_code(); // повесить на крон TODO!!!
	}

	public function f_time_interval_set( $_int=0 ) {
		if ( empty( $_int ) ) {
			return false;
		}
		$this->f_time_interval=$_int;
		return true;
	}

	public function f_send_code( &$arrRes, &$arrDat, &$arrErr, $_arrDta=array() ) {
		$_arrDat=Core_A::array_check( $_arrDta, $this->post_filter );
		if ( !$this->error_check( $arrDat, $arrErr, $_arrDat, array(
			'no_data'=>empty( $_arrDat['nickname'] )&&empty( $_arrDat['email'] ),
		) ) ) {
			return false;
		}
		if ( !$this->get_user( $_arrU, $_arrDat+array( 'activated_and_enabled'=>1 ) ) ) {
			$arrErr['no_user']=1;
			return false;
		}
		if ( empty( $_arrDta['groups'] )||!$this->check_group_by_user_id( $_arrDta['groups'], $_arrU['id'] ) ) {
			$arrErr['user_group_no_valid']=1;
			return false;
		}
		if ( !empty( $_arrU['parent_id'] ) ) {
			$arrRes['kid_account']=$_arrU;
			$arrRes['par_account']=Core_Sql::getRecord( 'SELECT * FROM u_users WHERE id="'.$_arrU['parent_id'].'" AND flg_status=1' );
		} else {
			$arrRes['par_account']=$_arrU;
		}
		// мыльников нету ни у ребёнка ни у родителя (или ребёнок непривязан к родителю)
		if ( empty( $arrRes['par_account']['email'] )&&empty( $arrRes['kid_account']['email'] ) ) {
			$arrErr['no_email']=1;
			return false;
		}
		if ( !$this->sv_get_uniq_code( $arrRes['forgot_code'], 'forgot_code', 'u_users' ) ) {
			$arrErr['no_code']=1;
			return false;
		}
		$arrRes['par_account']['forgot_code']=md5( $arrRes['forgot_code'] );
		Core_Sql::setUpdate( 'u_users', array( 'id'=>($_arrU['parent_id'] == 0)?$_arrU['id']:$_arrU['parent_id'], 'forgot_code'=>$arrRes['par_account']['forgot_code'], 'forgot_added'=>time() ) );
		return true;
	}

	// проверяем соответствие кода пользователю
	public function f_get_profile_by_code( &$arrRes, &$arrErr, $_strCode='' ) {
		if ( !$this->error_check( $arrDat, $arrErr, $_arrDat, array(
			'no_code'=>empty( $_strCode ),
		) ) ) {
			return false;
		}
		$arrRes=Core_Sql::getRecord( 'SELECT * FROM u_users WHERE forgot_code='.Core_Sql::fixInjection( $_strCode ).' LIMIT 1' );
		if ( !$this->error_check( $arrDat, $arrErr, $_arrDat, array(
			'bad_code'=>empty( $arrRes ),
		) ) ) {
			return false;
		}
		return true;
	}

	// меняем пароль для этого пользователя
	public function f_set_new_password( &$arrRes, &$arrDat, &$arrErr, $_arrDta=array() ) {
		$_arrDat=Core_A::array_check( $_arrDta, $this->post_filter );
		if ( !$this->error_check( $arrDat, $arrErr, $_arrDat, array(
			'passwd'=>empty( $_arrDat['passwd'] ),
			'passwd_re'=>empty( $_arrDat['passwd_re'] ),
			'no_user'=>empty( $_arrDta['arrUser']['id'] )
		) ) ) {
			return false;
		}
		if ( !$this->error_check( $arrDat, $arrErr, $_arrDat, array(
			'passwd_len'=>strlen( $_arrDat['passwd'] )<$this->u_minpassword_len,
			'passwd_re'=>$_arrDat['passwd']!=$_arrDat['passwd_re'],
		) ) ) {
			return false;
		}
		$arrRes=$_arrDta['arrUser'];
		$arrRes['passwd']=$_arrDat['passwd'];
		Core_Sql::setUpdate( 'u_users', array( 'id'=>$arrRes['id'], 'forgot_code'=>'', 'forgot_added'=>0, 'passwd'=>md5( $arrRes['passwd'] ) ) );
		return true;
	}

	// чистим коды пользователей если прошло 3 дня со дня попытки напомнить пароль
	private function f_clear_old_sended_code() {
		Core_Sql::setExec( 'UPDATE u_users SET forgot_code="", forgot_added="0" WHERE '.(time()-$this->f_time_interval).'>forgot_added AND forgot_added>0' );
	}
}
?>