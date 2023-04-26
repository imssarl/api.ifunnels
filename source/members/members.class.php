<?php
/**
 * WorkHorse Framework
 *
 * @category WorkHorse
 * @package ProjectSource
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2005-2012, Rodion Konnov
 * @author Rodion Konnov <kindzadza@mail.ru>
 * @date 16.02.2012
 * @version 1.0
 */


/**
 * Members administration
 *
 * @category WorkHorse
 * @package ProjectSource
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2005-2012, Rodion Konnov
 */
class members extends Core_Module {

	public final function set_cfg() {
		$this->inst_script=array(
			'module'=>array(
				'title'=>'Members',
			),
			'actions'=>array(
				array( 'action'=>'manage', 'title'=>'Manage' ),
				array( 'action'=>'import', 'title'=>'Import/Export' ),
				array( 'action'=>'set', 'title'=>'Add/Edit account' ),
				array( 'action'=>'broadcast','title'=>'Email Broadcast' ),
				array( 'action'=>'blacklist','title'=>'Blacklist' ),
				array( 'action'=>'logout','title'=>'Logout', 'flg_tpl'=>2 ),
			),
		);
	}

	public function import(){
		$_groups=new Core_Acs_Groups();
		$_groups->toSelect()->getList( $this->out['arrG'] );
		if( !empty($_POST['arr']) ){
			$_users=new Project_Users_Management();
			$_users->withGroups( $_POST['arr']['groups_ids'] )->import();
		}
	}

	public function manage() {
		$_users=new Project_Users_Management();
		$_groups=new Core_Acs_Groups();
		if ( !empty( $_POST['arrFilter']['action'] ) ) {
			switch( $_POST['arrFilter']['action'] ) {
				case 'delete': $_users->withIds( array_keys( $_POST['arrList'] ) )->del(); break;
			}
			$this->location();
		}
		if(!empty($_GET['auth'])){
			$_id=Core_Payment_Encode::decode($_GET['auth']);
			header('Location: /?a='.Core_Payment_Encode::encode($_id.Zend_Registry::get('config')->user->salt.time()));
		}
		if(!empty($_GET['resend'])&&$_users->changePassword( $_GET['resend'] )){
			$this->location();
		}
		if( !empty($_GET['arrFilter']['search']['nickname']) ){
			$_users->likeNickname($_GET['arrFilter']['search']['nickname']);
		}
		if( !empty($_GET['arrFilter']['search']['email']) ){
			$_users->withEmail($_GET['arrFilter']['search']['email']);
		}
		if(!empty($_GET['arrFilter']['package_id'])){
			$_users->withPackage($_GET['arrFilter']['package_id']);
		}
		if(!empty($_GET['arrFilter']['group_id'])){
			$_groups->withIds( $_GET['arrFilter']['group_id'] )->onlyOne()->getList( $_arrGroup );
			$_users->withGroups(array($_arrGroup['sys_name']));
		}
		$_groups->toSelect()->getList( $this->out['arrGroups'] );
		$_pack=new Core_Payment_Package();
		$_pack->withHided()->toSelect()->onlyTariffPkg()->getList( $this->out['arrPack'] );
		$this->objStore->getAndClear( $this->out );
		$_users
			->withPaging( array( 'url'=>$_GET ) )
			->withOrder( @$_GET['order'] )
			->getList( $this->out['arrList'] )
			->getPaging( $this->out['arrPg'] )
			->getFilter( $this->out['arrFilter'] );
		$this->out['arrActions']=array(
			0=>'-- Select an Action --',
			'assign'=>'Assign to Package',
			'remove'=>'Remove from Package',
			1=>'-------',
			'cancel'=>'Cancel from Package',
			'uncancel'=>'UnCancel from Package',
			2=>'-------',
			'approve'=>'Approve Registration',
			'unapprove'=>'UnApprove Registration',
			3=>'-------',
			'delete'=>'Delete Selected Users',
		);
	}

	public function set() {
		$this->objStore->getAndClear( $this->out );
		if( !empty($_POST) ){
			$_registration=new Project_Users_Registration();
			if( !isset( $_POST['arrData']['flg_unsubscribe'] ) ){
				$_POST['arrData']['flg_unsubscribe']=0;
			}
			if( !isset($_POST['arrData']['flg_expire']) ){
				$_POST['arrData']['flg_sended']=0;
				$_POST['arrData']['expiry']=0;
			}
			if( empty($_POST['arrData']['popup_width'] ) ){
				$_POST['arrData']['popup_width'] = 70;
			}
			if( empty($_POST['arrData']['popup_height'] ) ){
				$_POST['arrData']['popup_height'] = 70;
			}
			$_groups=new Core_Acs_Groups();
			$_groups->withIds( $_POST['arrData']['id'] )->getGroupByUserId( $arrHaveGroups ); // странно но это список групп по id пользователя
			if( !empty( $_POST['arrData']['id'] ) ){
				$_arrAddedGroup = array_diff( $_POST['arrData']['groups'], $arrHaveGroups );
			} else {
				$_arrAddedGroup = $_POST['arrData']['groups'];
				while( ( $i=array_search( 'Default', $_arrAddedGroup ) ) !== false ){
					unset($_arrAddedGroup[$i]);
					break;
				}
			}
			if ( $_registration->setEntered( $_POST, 'arrData' )->adminSet() ) {
				$_registration->getEntered( $_data );
				if( isset( $_data['id'] ) ){
					if( isset( $_POST['arrData']['lpb_limits'] ) ){
						Project_Squeeze::sendRestrictions( $_POST['arrData']['lpb_limits'], ( isset( $_POST['arrData']["lpb_limits_type"] )?(bool)$_POST['arrData']["lpb_limits_type"]:0 ), $_data['id'] );
					}
					if( isset( $_POST['arrData']['traffic_credits'] ) && $_POST['arrData']['traffic_credits']!=0 ){
						Project_Traffic::addCredits( 0, $_POST['arrData']['traffic_credits'], $_data['id'] );
					}
				}
				$_user=new Core_Users_Management();
				$_user->setCode( $_data, 'code_forgot' );
				while( ( $i=array_search( 'Default', $_POST['arrData']['groups'] ) ) !== false ){
					unset($_POST['arrData']['groups'][$i]);
					break;
				}
				if( empty( $_POST['arrData']['id'] ) && !in_array( 'Affiliate Funnels Starter' , $_arrAddedGroup ) && !in_array( 'Affiliate Funnels Free', $_arrAddedGroup ) ) {
					$_arrMail=array();
					$_arrMail['email']=$_POST['arrData']['email'];
					$_arrMail['password']=$_POST['arrData']['passwd'];
					$_arrMail['moduls']=implode( ', ', $_arrAddedGroup );
					$_arrMail['code_forgot'] = $_data['code_forgot'];
					$_mailer=new Core_Mailer();
					$_mailer
						->setVariables( $_arrMail )
						->setTemplate( 'api_registration_complete' )
						->setSubject( 'Your Lead Pro Systems Account Has Been Created' )
						->setPeopleTo( array( 'email'=>$_arrMail['email'], 'name'=>$_POST['arrData']['nickname'] ) )
						->setPeopleFrom( array( 'name' => Zend_Registry::get('config')->engine->project_sysemail->name, 'email' => Zend_Registry::get('config')->engine->project_sysemail->email ) )
						->sendOneToMany();
				}
				if( in_array( 'Affiliate Funnels Starter' , $_arrAddedGroup ) || in_array( 'Affiliate Funnels Free', $_arrAddedGroup ) ) {	
					$_arrMail=array();
					$_arrMail['email']=$_POST['arrData']['email'];
					$_arrMail['moduls']=implode( ', ', $_arrAddedGroup );
					$_arrMail['code_forgot']=$_data['code_forgot'];
					$_mailer=new Core_Mailer();
					$_mailer
						->setVariables( $_arrMail )
						->setTemplate( 'affiliatefunnels_complete' )
						->setSubject( 'Your Affiliate Funnels Account Has Been Created' )
						->setPeopleTo( array( 'email'=>$_arrMail['email'], 'name'=>$_POST['arrData']['nickname'] ) )
						->setPeopleFrom( array( 'name' => Zend_Registry::get('config')->engine->project_sysemail->name, 'email' => Zend_Registry::get('config')->engine->project_sysemail->email ) )
						->sendOneToMany();
				}
				$_tmp = array_diff( $_arrAddedGroup, array( 'Affiliate Funnels Starter', 'Affiliate Funnels Free' ) );
				if( !empty( $_tmp ) ) {
					$_arrMail=array();
					$_arrMail['email']=$_POST['arrData']['email'];
					$_arrMail['moduls']=implode( ', ', $_arrAddedGroup );
					$_arrMail['code_forgot']=$_data['code_forgot'];
					$_mailer=new Core_Mailer();
					$_mailer
						->setVariables( $_arrMail )
						->setTemplate( 'user_update_group' )
						->setSubject( 'Your Account Has Been Updated' )
						->setPeopleTo( array( 'email'=>$_arrMail['email'], 'name'=>$_POST['arrData']['nickname'] ) )
						->setPeopleFrom( array( 'name' => Zend_Registry::get('config')->engine->project_sysemail->name, 'email' => Zend_Registry::get('config')->engine->project_sysemail->email ) )
						->sendOneToMany();	
				}
				$_registration->getEntered( $_arrReg );
				if( isset( $_POST['arrData']['hosting_limit'] ) && $_POST['arrData']['hosting_limit']==0 && !empty( $_data['id'] ) ){
					$_placement=new Project_Placement();
					$_placement
						->onlyLimitedHosting()
						->withUserId( $_data['id'] )
						->getList( $arrRes );
					foreach( $arrRes as $_item ){
						$_placement->withIds( $_item['id'] )->del();
					}
				}
				$this->objStore->set( array( 'congratulations'=>true, 'arrReg'=>$_arrReg ) );
				$this->location( array( 'action'=>'set','wg'=>true ) );
			}
			$_registration->getEntered( $this->out['arrData'] )->getFieldsError( $this->out['arrErr'] )->getHeadError( $this->out['strError'] );
		}
		$_balance=new Core_Payment_Purse();
		$_groups=new Core_Acs_Groups();
		$_groups->getList( $this->out['arrG'] );
		if ( !empty( $_GET['id'] ) ) {
			$_user=new Project_Users_Management();
			$_user->forBackend()->onlyOne()->withIds( $_GET['id'] )->getList( $this->out['arrData'] );
			if ( !empty( $_GET['return'] ) ) {
				$_balance->withIds( $_GET['return'] )->onlyOne()->getList( $_arrPurse );
				$_balance->withIds( $_GET['return'] )->del();
				$this->out['arrData']['amount']=(int)$this->out['arrData']['amount']+(int)$_arrPurse['amount'];
				$_user->setEntered( $this->out['arrData'] )->set();
			}
			$this->out['arrData']['lpb_limits']=Project_Squeeze::getRestrictions( $_GET['id'] );
			$_traffic=new Project_Traffic();
			$this->out['arrData']['traffic_credits']=$_traffic->withUserId( $_GET['id'] )->getUserCredits();
			$_admin=Core_Users::$info['id'];
			Core_Users::getInstance()->setById( $_GET['id'] );
			$_balance
				->onlyOwner()
				->withPaging( array(
					'url'=>@$_GET,
					'reconpage'=>Core_Users::$info['arrSettings']['pagging_rows'],
					'numofdigits'=>Core_Users::$info['arrSettings']['pagging_links'],
					'inst'=>'balance'
				) )
				->getList($this->out['arrBalance'])
				->getPaging( $this->out['arrPgBalance'] );
			$this->out['onPageBalance']=$_balance->getAmount( $this->out['arrBalance'][0]['added'] );
			Core_Users::getInstance()->setById( $_admin );
		}
	}

	public function broadcast() {
		$this->objStore->getAndClear( $this->out );
		if(!empty($_POST['arrMessage'])){
			$_model=new Project_Users_Broadcast();
			if( $_model->setToByGroups( $_POST['groups'] )->setEntered( $_POST['arrMessage'] )->send() ){
				$this->objStore->set( array( 'send'=>'Message was sended') );
				$this->location();
			}
			$this->out['arrErrors']=$_model->getErrors();
			$this->out['arrMessage']=$_POST['arrMessage'];
			$this->out['groups']=$_POST['groups'];
		}
		$_groups=new Core_Acs_Groups();
		$_groups->getList( $this->out['arrGroups'] );
	}

	public function blacklist() {}

	public function login() {
		$_auth=new Core_Users_Auth_Email();
		if ( $_auth->setEntered( $_POST, 'arrL' )->authorize() ) {
			$this->location( $this->objML->get() );
		}
	}

	public function logout() {
		Core_Users::logout();
		setcookie( "adm", "", time()-42000, '/' );
		setcookie( "sid", "", time()-42000, '/' );
		$this->location( array( 'name'=>'members', 'action'=>'login' ));
	}
}
?>