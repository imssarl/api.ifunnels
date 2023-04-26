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
 * Forgot password 1
 * @internal автоматическая генерация нового пароля по уникальному коду в письме
 * @category project
 * @package ProjectUsersExtension
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2008, Rodion Konnov
 * @author Rodion Konnov <kindzadza@mail.ru>
 * @date 21.11.2008
 * @version 1.0
 */


class Project_Users_Forgotpassword extends Project_Users {

	function __construct() {}

	// определяет по nickname или email пользователя
	// если есть email у него или у его parent_id
	// возвращает массив вида array( 'forgot_code'=>string, 'account'=>array, 'email'=>string )
	// апдэйтит поле forgot_code сгенерированным кодом
	function u_forgot_passwd_send_code( &$arrRes, &$arrDat, &$arrErr, $_arrDta=array() ) {
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
		if ( empty( $_arrU['email'] ) ) {
			if ( empty( $_arrU['parent_id'] ) ) {
				$arrErr['no_email']=1;
				return false;
			}
			$_arrP=Core_Sql::getRecord( 'SELECT * FROM u_users WHERE id="'.$_arrU['parent_id'].'" AND flg_status=1 AND reg_code=""' );
		}
		if ( empty( $_arrU['email'] )&&empty( $_arrP['email'] ) ) {
			$arrErr['no_email']=1;
			return false;
		}
		if ( !$this->sv_get_uniq_code( $arrRes['forgot_code'], 'forgot_code', 'u_users' ) ) {
			$arrErr['no_code']=1;
			return false;
		}
		$arrRes['account']=$_arrU;
		$arrRes['forgot_code']=md5( $arrRes['forgot_code'] );
		$arrRes['email']=empty( $_arrU['email'] )? $_arrP['email']:$_arrU['email'];
		Core_Sql::setUpdate( 'u_users', array( 'id'=>$_arrU['id'], 'forgot_code'=>$arrRes['forgot_code'] ) );
		return true;
	}

	// определяет по forgot_code аккаунт
	// если есть email у него или у его parent_id
	// возвращает массив вида array( 'passwd'=>string, 'account'=>array, 'email'=>string )
	// апдэйтит поле passwd новым паролем в md5
	function u_forgot_passwd_reset( &$arrRes, $_strCode='' ) {
		if ( empty( $_strCode ) ) {
			$arrErr['no_code']=1;
			return false;
		}
		$_arrU=Core_Sql::getRecord( 'SELECT * FROM u_users WHERE forgot_code='.Core_Sql::fixInjection( $_strCode ) );
		if ( empty( $_arrU ) ) {
			$arrErr['wrong_code']=1;
			return false;
		}
		if ( empty( $_arrU['email'] ) ) {
			if ( empty( $_arrU['parent_id'] ) ) {
				$arrErr['no_email']=1;
				return false;
			}
			$_arrP=Core_Sql::getRecord( 'SELECT * FROM u_users WHERE id="'.$_arrU['parent_id'].'" AND flg_status=1 AND reg_code=""' );
		}
		if ( !$this->sv_get_uniq_code( $arrRes['passwd'], 'passwd', 'u_users' ) ) {
			$arrErr['no_code']=1;
			return false;
		}
		$arrRes['account']=$_arrU;
		$arrRes['email']=empty( $_arrU['email'] )? $_arrP['email']:$_arrU['email'];
		Core_Sql::setUpdate( 'u_users', array( 'id'=>$_arrU['id'], 'forgot_code'=>'', 'passwd'=>md5( $arrRes['passwd'] ) ) );
		return true;
	}
}
?>