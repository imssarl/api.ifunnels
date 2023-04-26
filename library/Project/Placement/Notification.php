<?php

/**
 * Notifications for user
 */
class Project_Placement_Notification {


	public static function hostingDelete( $_arrData ){
		$_users=new Project_Users_Management();
		$_placement=new Project_Placement();
		foreach( $_arrData as $_userId=>$_arrHosting ){
			if( !$_users->withIds( $_userId )->onlyOne()->getList( $_arrUser )->checkEmpty() ){
				continue;
			}
			$_mailer=new Core_Mailer();
			$_mailer->setVariables( array(
						'user'=>$_arrUser,
						'settings'=>$arrCurrentBunSettings,
						'arrList'=>$_arrHosting
					) )
					->setTemplate( 'hosting_delete_message' )
					->setSubject( 'Hosting(s) expire' )
					->setPeopleTo( array( 'email'=>$_arrUser['email'], 'name'=>$_arrUser['buyer_name'] ) )
					->setPeopleFrom( array( 'name' => Zend_Registry::get('config')->engine->project_sysemail->name, 'email' => Zend_Registry::get('config')->engine->project_sysemail->email ) )
					->sendOneToMany();
			foreach( $_arrHosting as &$_domain ){
				$_domain['flg_sended_hosting']++;
			}
			$_placement->setEntered( $_arrHosting )->setMass();
		}
	}

	public static function domainExpired( $_arrData, $arrCurrentBunSettings ){
		$_users=new Project_Users_Management();
		$_placement=new Project_Placement();
		foreach( $_arrData as $_userId=>$_arrDomains ){
			if( !$_users->withIds( $_userId )->onlyOne()->getList( $_arrUser )->checkEmpty() ){
				continue;
			}
			$_mailer=new Core_Mailer();
			$_mailer->setVariables( array(
						'user'=>$_arrUser,
						'settings'=>$arrCurrentBunSettings,
						'arrList'=>$_arrDomains
					) )
					->setTemplate( 'domain_expired_message' )
					->setSubject( 'Domain(s) expire' )
					->setPeopleTo( array( 'email'=>$_arrUser['email'], 'name'=>$_arrUser['buyer_name'] ) )
					->setPeopleFrom( array( 'name' => Zend_Registry::get('config')->engine->project_sysemail->name, 'email' => Zend_Registry::get('config')->engine->project_sysemail->email ) )
					->sendOneToMany();
			foreach( $_arrDomains as &$_domain ){
				$_domain['flg_sended_domain']++;
				$_placement->setEntered( $_domain )->set();
			}
//			$_placement->setEntered( $_arrDomains )->setMass();
		}

	}

	public static function hostingExpired( $_arrData, $arrCurrentBunSettings ){
		$_users=new Project_Users_Management();
		$_placement=new Project_Placement();
		foreach( $_arrData as $_userId=>$_arrHosting ){
			if( !$_users->withIds( $_userId )->onlyOne()->getList( $_arrUser )->checkEmpty() ){
				continue;
			}
			$_mailer=new Core_Mailer();
			$_mailer->setVariables( array(
						'user'=>$_arrUser,
						'settings'=>$arrCurrentBunSettings,
						'arrList'=>$_arrHosting
					) )
					->setTemplate( 'hosting_expired_message' )
					->setSubject( 'Hosting(s) expire' )
					->setPeopleTo( array( 'email'=>$_arrUser['email'], 'name'=>$_arrUser['buyer_name'] ) )
					->setPeopleFrom( array( 'name' => Zend_Registry::get('config')->engine->project_sysemail->name, 'email' => Zend_Registry::get('config')->engine->project_sysemail->email ) )
					->sendOneToMany();
			foreach( $_arrHosting as &$_domain ){
				$_domain['flg_sended_hosting']++;
				$_placement->setEntered( $_domain )->set();
			}
//			$_placement->setEntered( $_arrHosting )->setMass();
		}
	}

	public static function instructionDNS( $arrData ){
		$_mailer=new Core_Mailer();
		return $_mailer
			->setVariables( array(
				'dns1'=>Project_Placement_Domen_Availability::$_NS1,
				'dns2'=>Project_Placement_Domen_Availability::$_NS2,
				'data'=>$arrData,
				'user'=>Core_Users::$info
			) )
			->setTemplate( 'instruction_domain_park' )
			->setSubject( 'Instruction for parking domain' )
			->setPeopleTo( array( 'email'=>Core_Users::$info['email'], 'name'=>Core_Users::$info['buyer_name'] ) )
			->setPeopleFrom( array( 'name' => Zend_Registry::get('config')->engine->project_sysemail->name, 'email' => Zend_Registry::get('config')->engine->project_sysemail->email ) )
			->sendOneToMany();
	}

	public static function instructionDNSmass( $arrData ){
		$_mailer=new Core_Mailer();
		return $_mailer
			->setVariables( array(
				'dns1'=>Project_Placement_Domen_Availability::$_NS1,
				'dns2'=>Project_Placement_Domen_Availability::$_NS2,
				'data'=>$arrData,
				'user'=>Core_Users::$info
			) )
			->setTemplate( 'instruction_domains_park' )
			->setSubject( 'Instruction for parking domains' )
			->setPeopleTo( array( 'email'=>Core_Users::$info['email'], 'name'=>Core_Users::$info['buyer_name'] ) )
			->setPeopleFrom( array( 'name' => Zend_Registry::get('config')->engine->project_sysemail->name, 'email' => Zend_Registry::get('config')->engine->project_sysemail->email ) )
			->sendOneToMany();
	}

	public static function registredDomain( $arrData ){
		$_mailer=new Core_Mailer();
		return $_mailer
			->setVariables( array(
				'data'=>$arrData,
				'user'=>Core_Users::$info
			) )
			->setTemplate( 'registred_domain' )
			->setSubject( 'Registered domain' )
			->setPeopleTo( array( 'email'=>Core_Users::$info['email'], 'name'=>Core_Users::$info['buyer_name'] ) )
			->setPeopleFrom( array( 'name' => Zend_Registry::get('config')->engine->project_sysemail->name, 'email' => Zend_Registry::get('config')->engine->project_sysemail->email ) )
			->sendOneToMany();
	}
}
?>