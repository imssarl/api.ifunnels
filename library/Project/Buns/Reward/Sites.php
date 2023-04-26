<?php


 /**
 * система вознаграждений/бонусов за созданные сайты
 */
class Project_Buns_Reward_Sites implements Core_Payment_Buns_Interface {

	/**
	 * Время с которого начинаем считать созданные сайты
	 * @var string
	 */
	private static $time='01.07.2012 12:00';
	private $_arrSettings=false;

	public function checkExpired( $arrCurrentBunSettings=array() ){
		$this->_arrSettings=$arrCurrentBunSettings;
		$_placement=new Project_Placement();
		$_users=new Project_Users_Management();
		$_users->onlyActive()->withGroups(array('Maintenance'))->getList( $arrUsers );
		foreach( $arrUsers as $_user ){
			Core_Users::getInstance()->setById( $_user['id'] );
			$_purse=new Core_Payment_Purse();
			if( !$_placement->forCreditsRewards(strtotime(self::$time))->onlyOwner()->getList( $count )->checkEmpty() ){
				continue;
			}
			if( $count<$this->_arrSettings['length'] ){
				continue;
			}
			$_purse->onlyOwner()->withType( Core_Payment_Purse::TYPE_REWARD_SITES )->getList( $arrRewards );
			if( round($count/$this->_arrSettings['length'])<=count($arrRewards) ){
				continue;
			}
			$_purse
					->setType( Core_Payment_Purse::TYPE_REWARD_SITES )
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
				->setTemplate( 'reward_sites' )
				->setSubject( 'Sites rewards' )
				->setPeopleTo( array( 'email'=>$_arrProfile['email'], 'name'=>$_arrProfile['buyer_name'] ) )
				->setPeopleFrom( array( 'name' => Zend_Registry::get('config')->engine->project_sysemail->name, 'email' => Zend_Registry::get('config')->engine->project_sysemail->email ) )
				->sendOneToMany();
	}

}
?>