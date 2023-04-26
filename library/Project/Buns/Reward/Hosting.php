<?php


 /**
 * система вознаграждений/бонусов за купленный хостинг
 */
class Project_Buns_Reward_Hosting implements Core_Payment_Buns_Interface {

	private $_arrSettings=false;

	public function checkExpired( $arrCurrentBunSettings=array() ){
		$this->_arrSettings=$arrCurrentBunSettings;
		$_placement=new Project_Placement();
		$_users=new Project_Users_Management();
		$_users->onlyActive()->withGroups(array('Maintenance'))->getList( $arrUsers );
		foreach( $arrUsers as $_user ){
			Core_Users::getInstance()->setById( $_user['id'] );
			$_purse=new Core_Payment_Purse();
			if( !$_placement->onlyCount()->withType( array(Project_Placement::LOCAL_HOSTING,Project_Placement::LOCAL_HOSTING_DOMEN) )->onlyOwner()->getList( $count )->checkEmpty() ){
				continue;
			}
			if( $count<$this->_arrSettings['length'] ){
				continue;
			}
			$_purse->onlyOwner()->withType( Core_Payment_Purse::TYPE_REWARD_HOSTING )->getList( $arrRewards );
			if( round($count/$this->_arrSettings['length'])<=count($arrRewards) ){
				continue;
			}
			$_purse
					->setType( Core_Payment_Purse::TYPE_REWARD_HOSTING )
					->setAmount( $this->_arrSettings['credits'] )
					->setMessage( $this->_arrSettings['description'] )
					->setUserId( $_user['id'] )
					->receipts();
			$this->notification( $_user );
		}

	}

	private function notification( $_arrProfile ){
		$_mailer=new Core_Mailer();
		$_mailer->setVariables( array(
					'user'=>$_arrProfile,
					'settings'=>$this->_arrSettings,
				) )
				->setTemplate( 'reward_hosting' )
				->setSubject( 'Hosting rewards' )
				->setPeopleTo( array( 'email'=>$_arrProfile['email'], 'name'=>$_arrProfile['buyer_name'] ) )
				->setPeopleFrom( array( 'name' => Zend_Registry::get('config')->engine->project_sysemail->name, 'email' => Zend_Registry::get('config')->engine->project_sysemail->email ) )
				->sendOneToMany();
	}

}
?>