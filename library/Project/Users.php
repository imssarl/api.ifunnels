<?php


/**
 * Core user library extension
 */
class Project_Users extends Core_Users{

	public function setById( $_int=0 ) {
		if ( empty( $_int ) ) {
			throw new Exception( Core_Errors::DEV.'|User id can be setted' );
		}
		$_user=new Project_Users_Management();
		if ( !$_user->onlyOne()->withIds( $_int )->getList( $arrProfile )->checkEmpty() ) {
			return false;
		}
		return $this->setByProfile( $arrProfile );
	}

	public function regenerate() {
		parent::regenerate();
		$arrRes['redirect']=false;
		if( Core_Acs::changedAccess() ){
			Core_Users::getInstance()->reload();
			$arrRes['redirect']=1;
		}
		if( !empty($_POST['action']) && !Core_Acs::haveActionAccess(array('name'=>$_POST['name'],'action'=>$_POST['action'])) ){
			$arrRes['redirect']=2;
		}
		Core_View::factory( Core_View::$type['json'] )
			->setHash( $arrRes )
			->parse()
			->header()
			->show();
	}

	public static function setFlag2HiamLite(){
		Core_Users::$info['flg_hiam_view']=true;
	}
}
?>