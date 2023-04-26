<?php

class Project_Users_Free {

	private $_package=false;
	private $_user=false;

	public function setPackage( $_intPackageId ){
		$_package=new Core_Payment_Package();
		if( !$_package->withIds( $_intPackageId )->onlyOne()->getList( $this->_package )->checkEmpty() ){
			throw new Project_Users_Exception('Can\'t find package');
		}
		if( $this->_package['cost']>0 ){
			throw new Project_Users_Exception('The package is not free');
		}
		return $this;
	}

	public function setUser( $_userId ){
		$_users=new Project_Users_Management();
		if( !$_users->withIds( $_userId )->onlyOne()->getList($this->_user)->checkEmpty() ){
			throw new Project_Users_Exception('Can\'t find user');
		}
		return $this;
	}

	public function add(){
		if( empty($this->_package) ){
			throw new Project_Users_Exception('Package was not set');
		}
		if( empty($this->_user) ){
			throw new Project_Users_Exception('User was not set');
		}
		Project_Statistics_Package::add( $this->_package['id'],Project_Statistics_Package::TYPE_SALE );
		sleep(2);
		$_purse=new Core_Payment_Purse();
		$_purse
			->setAmount( $this->_package['credits'] )
			->setUserId( $this->_user['id']);
		$_group=new Core_Acs_Groups();
		$_group->withIds( $this->_user['id'] )->getGroupByUserId( $_currentUsersGroups );
		if( !empty($_currentUsersGroups[$this->_package['group_id']]) ){
			return true;
		}
		$_currentUsersGroups[$this->_package['group_id']]=true;
		$_group->withIds( $this->_user['id'] )->setGroupByIds( $_currentUsersGroups );
		$_subscr=new Core_Payment_Subscription();
		$cycles_remain=($this->_package['cycles']<=0)?0:$this->_package['cycles']-1;
		$_expiry=time()+Core_Payment_Package::getLengthInSeconds( $this->_package );
		if( $_expiry>2147483647 ){
			$_expiry=2147483647;
		}
		$_subscr->onlyOne()->withPackage( $this->_package['id'] )->onlyOwner()->getList( $arrSubsc );
		$_subscr->setEntered(array(
			'id'=>((!empty($arrSubsc))?$arrSubsc['id']:''),
			'package_id'=>$this->_package['id'],
			'user_id'=>$this->_user['id'],
			'flg_lifetime'=>($cycles_remain==0&&$this->_package['cycles']>0)?1:0,
			'cycles_remain'=>$cycles_remain,
			'expiry'=>$_expiry,
			'flg_auto'=>1
		))->set();
		if( Zend_Registry::isRegistered( 'translate' ) ){
			$_purse->setMessage(Zend_Registry::get( 'translate' )->_('Successfully added the package '.$this->_package['title'].'. ').sprintf( Zend_Registry::get( 'translate' )->plural('%d credit have been added to account balance.','%d credits have been added to account balance.',$this->_package['credits'] ),$this->_package['credits']) );
		} else {
			$_purse->setMessage( 'Successfully added the package '.$this->_package['title'].'. '.sprintf( (($this->_package['credits']==1)?'%d credit have been added to account balance.':'%d credits have been added to account balance.'),$this->_package['credits']) );
		}
		if( $this->_user['flg_confirm']==1 ){
			$this->notification();
		}
		$_reggi=new Project_Users_Registration();
		if( $this->_user['flg_confirm']==0&&!$_reggi->setEntered( array('user'=>$this->_user), 'user')->complete() ){
			return false;
		}
		$_purse->receipts();
		$_service=new Core_Payment_Service();
		$_service->setEntered(array('package_id'=>$this->_package['id'],'amount'=>$this->_package['cost'],'flg_confirm'=>1))->set();
		return true;
	}

	private function notification(){
		if(empty($this->_user)){
			return false;
		}
		$this->_user['arrPkg']=$this->_package;
		// отправляем пароль пользователю
		$_mailer=new Core_Mailer();
		if ( !$_mailer
			->setVariables( $this->_user )
			->setTemplate( 'api_registration_complete' )
			->setSubject( 'Your Account Has Been Created' )
			->setPeopleTo( array( 'email'=>$this->_user['email'], 'name'=>((empty($this->_user['nickname']))?'User':$this->_user['nickname']) ) )
			->setPeopleFrom( array( 'name' => Zend_Registry::get('config')->engine->project_sysemail->name, 'email' => Zend_Registry::get('config')->engine->project_sysemail->email ) 
			->sendOneToMany() ) {
			return $this->setHeadError( 'email don\'t send' );
		}
	}
}
?>