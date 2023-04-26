<?php
/**
 * WorkHorse Framework
 *
 * @category WorkHorse
 * @package ProjectSource
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2005-2009, Rodion Konnov
 * @author Rodion Konnov <kindzadza@mail.ru>
 * @date 02.06.2009
 * @version 2.0
 */


/**
 * Usrer management module
 *
 * @category WorkHorse
 * @package ProjectSource
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2005-2009, Rodion Konnov
 */
class accounts extends Core_Module implements Project_Users_Sample {

	public final function set_cfg() {
		$this->inst_script=array(
			'module'=>array(
				'title'=>'Users management',
			),
			'actions'=>array(
				array( 'action'=>'user_list', 'title'=>'Users' ),
				array( 'action'=>'set_profile', 'title'=>'Set account' ),
				array( 'action'=>'change_password', 'title'=>'Change own password' ),
				array( 'action'=>'logoff', 'title'=>'Logoff', 'flg_tpl'=>2 ),
				array( 'action'=>'change_owner_popup', 'title'=>'Change Owner popup', 'flg_tpl'=>1 ),
			),
		);
	}

	private function post_creation_process() {
		$this->objStore->get( $_arrOneStep );
		switch( $_arrOneStep['stencil_name'] ) {
			case 'needed_stencil': break;
		}
	}

	public function set_additional( &$arrErr, &$arrRes, $_arrSet=array() ) {
		$_arrDat=Core_A::array_check( $_arrSet, $this->post_filter );
		$this->objStore->get( $_arrOneStep );
		// для разных масок разные чекеры (дополнительные чекеры помимо тех что в прописаны в стенсиле)
		$arrChk=array();
		switch( $_arrOneStep['stencil_name'] ) {
			case 'needed_stencil': break;
		}
		return $this->error_check( $arrRes, $arrErr, $_arrDat, $arrChk );
	}

	private function step_two() {
		$this->objStore->get( $this->out['arrOneStep'] );
		$this->objProfile=new Project_Users_Profile( 'full' );
		$this->objProfile->set_account_defult_activate();
		$this->objProfile->set_user( @$_GET['id'] );
		$this->objProfile->set_groups( $this->out['arrOneStep']['groups'] );
		if ( !empty( $_POST['arrU']['b']['dont_check'] ) ) { // отключаем чекер полей в стенсиле
			$this->objProfile->set_item_checker();
		}
		$this->objProfile->set_stencil( @$this->out['arrOneStep']['stencil_name'] );
		if ( empty( $_POST['arrU'] ) ) {
			$this->objProfile->get_profile( $this->out['arrU']['b'], $this->out['arrU']['a'] );
			return false;
		}
		$_bool1=$_bool2=true;
		if ( !empty( $_POST['arrU']['b'] )&&!$this->objProfile->set_base( $this->out['arrErr']['b'], $this->out['arrU']['b'], $_POST['arrU']['b'] ) ) {
			$_bool1=false;
		}
		$this->objProfile->set_files( $_arrTmp, $_FILES['files'] );
		if ( !empty( $_POST['arrItem']['arrFields'] ) ) { // это хак. вообще надо переписывать пользователей TODO!!!01.06.2009
			foreach( $_POST['arrItem']['arrFields'] as $k=>$v ) {
				$_POST['arrU']['a'][$k]=$v['content'];
			}
		}
		if ( !empty( $_POST['arrU']['a'] )&&!$this->objProfile->set_additional( $this, $this->out['arrErr']['a'], $_arrItemResult, $_POST['arrU']['a'] ) ) {
			foreach( $_arrItemResult as $k=>$v ) { // тоже хак
				$this->out['arrU']['a']['arrFields'][$k]['content']=$v;
			}
			$_bool2=false;
		}
		if ( !empty( $_POST['arrU']['a'] ) ) {
			return $_bool1&&$_bool2;
		}
		return $_bool1;
	}

	private function step_one() {
		if ( empty( $_POST['arrOneStep'] ) ) {
			if ( !$this->objStore->get( $this->out['arrOneStep'] )&&!empty( $_GET['id'] ) ) {
				$this->objUser->getProfileById( $arrInfo, $_GET['id'] );
				$this->out['arrOneStep']=array( 
					'stencil_name'=>$arrInfo['stencil']['sys_name'], 
					'groups'=>$arrInfo['groups']
				);
			}
			return false;
		}
		if ( !$this->error_check( $this->out['arrOneStep'], $this->out['arrErr'], $_POST['arrOneStep'], array(
			'select_groups'=>empty( $_POST['arrOneStep']['groups'] )
		) ) ) {
			return false;
		}
		return $this->objStore->set( $_POST['arrOneStep'] );
	}

	// проверить создание профайла без выбора стенсила TODO!!! 25.06.2009
	public function set_profile() {
		if ( empty( $_GET['step'] ) ) {
			$this->objStore->clear();
		}
		if ( !empty( $_GET['step'] )&&$_GET['step']==2 ) {
			if ( $this->step_two() ) {
				$this->objProfile->save_profile();
				$this->post_creation_process();
				$this->objStore->clear(); // если мы хотим остаться после второго шага удалять сессию экшена ненадо
				$this->location( array( 'action'=>'user_list' ) );
			}
			// static data
			$this->objProfile->get_fields( $this->out['arrUfields']['b'] );
		} else {
			if ( $this->step_one() ) {
				$this->location( array( 'w'=>'step=2'.(empty( $_GET['id'] )? '':'&id='.@$_GET['id']) ) );
			}
			// static data
//			Core_Items_Stencil_Extension::get_stencil_byprefix_toselect( $this->out['arrStencils'], 'u' );
			Core_Acs::getInstance()->r_get_groups_without_visitor( $this->out['arrG'] );
		}
	}

	public function change_password() {
		if (isSet($_REQUEST['arrPassword']['save'])) {
			if ($_REQUEST['arrPassword']['passwd_n']=='') {
				$this->out['arrErr']['passwd_n']=true;
			}
			if ($_REQUEST['arrPassword']['passwd_n_c']=='') {
				$this->out['arrErr']['passwd_n_c']=true;
			} elseif ($_REQUEST['arrPassword']['passwd_n_c']!=$_REQUEST['arrPassword']['passwd_n']) {
				$this->out['arrErr']['passwd_n_c']=true;
			}
			if (empty( $this->out['arrErr'] )) {
				$this->objStore->get( $this->out['arrOneStep'] );
				$this->objProfile=new Project_Users_Profile( 'full' );
				$this->objProfile->set_account_defult_activate();
				$this->objProfile->set_user( $this->objUser->u_info['id'] );
				if( $this->objProfile->set_base( $this->out['arrErr'], $_none, array(
					'nickname' => $this->objUser->u_info['nickname'],
					'passwd' => $_REQUEST['arrPassword']['passwd_n'],
					'passwd_re' => $_REQUEST['arrPassword']['passwd_n_c'],
					'email' => $this->objUser->u_info['email'],
					))
				){
					$this->objProfile->save_profile();
					$this->post_creation_process();
					$this->objStore->clear(); // если мы хотим остаться после второго шага удалять сессию экшена ненадо
					//$this->location( array( 'action'=>'user_list' ) );
					$this->out['arrPassword']['ok']=true;
					unset($_REQUEST['arrPassword']['passwd_n'],$_REQUEST['arrPassword']['passwd_n_c']);
				}
			}
		}
	}

	public function logoff() {
		Core_Users::logout();
		$this->location();
	}

	public function change_owner_popup() {
		$this->user_list();
	}

	public function adm_login() {
		if ( !empty( $_POST['arrL'] ) ) {
			if ( $this->objUser->setData( $_POST['arrL'] )->authorizeByEmail() ) {
				$this->location( $this->objML->get() );
			}
			$this->objUser->getEntered( $this->out['arrL'] )->getErrors( $this->out['arrErr'] );
		} elseif ( $this->objUser->authorizeByCookie() ) {
			$this->location( $this->objML->get() );
		}
	}

	private function setList() {
		if ( empty( $_POST['arrList'] ) ) {
			return false;
		}
		$_arrSids=$_arrIds=array();
		foreach( $_POST['arrList'] as $k=>$v ) {
			if ( !empty( $v['del'] ) ) {
				$_arrIds[]=$k;
				continue;
			}
			$_arrSids[$v['id']]=empty( $v['flg_status'] )?0:1;
			// тут можно делать ещё какие-либо действия с пользователями
		}
		$this->objUser->switchStatus( $_arrSids );
		$this->objUser->del( $_arrIds );
		$this->location();
	}

	public function user_list() {
		$this->setList();
		$this->objUser
		->withGroups( @$_GET['with_groups'] )
		->withEmail( @$_GET['email'] )
		->withNickname( @$_GET['nickname'] )
		->withOrder( @$_GET['order'] )
		->withPaging( array( 'url'=>$_GET, 'reconpage'=>30, ) )
		->getList( $this->out['arrList'] )
		->getPaging( $this->out['arrPg'] )
		->getFilter( $this->out['arrFilter'] );
		Core_Acs::getInstance()->getGroupsWithoutVisitorList( $this->out['arrG'] );
	}
}
?>