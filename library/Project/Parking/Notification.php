<?php

/**
 * Notifications for user
 */
class Project_Parking_Notification {


	public static function startProject( $_arrData ){
		$_users=new Project_Users_Management();
		if( !$_users->withIds( $_arrData['user_id'] )->onlyOne()->getList( $_arrUser )->checkEmpty() ){
			return false;
		}
		$_mailer=new Core_Mailer();
		$_mailer->setVariables( array(
					'user'=>$_arrUser,
					'data'=>$_arrData
				) )
				->setTemplate( 'parking_start_project' )
				->setSubject( 'Project has started' )
				->setPeopleTo( array( 'email'=>$_arrUser['email'], 'name'=>$_arrUser['buyer_name'] ) )
				->setPeopleFrom( array( 'name' => Zend_Registry::get('config')->engine->project_sysemail->name, 'email' => Zend_Registry::get('config')->engine->project_sysemail->email ) )
				->sendOneToMany();
	}

	public static function endProject( $_arrData ){
		$_users=new Project_Users_Management();
		if( !$_users->withIds( $_arrData['user_id'] )->onlyOne()->getList( $_arrUser )->checkEmpty() ){
			return false;
		}
		$_mailer=new Core_Mailer();
		$_mailer->setVariables( array(
					'user'=>$_arrUser,
					'data'=>$_arrData
				) )
				->setTemplate( 'parking_end_project' )
				->setSubject( 'Project was completed' )
				->setPeopleTo( array( 'email'=>$_arrUser['email'], 'name'=>$_arrUser['buyer_name'] ) )
				->setPeopleFrom( array( 'name' => Zend_Registry::get('config')->engine->project_sysemail->name, 'email' => Zend_Registry::get('config')->engine->project_sysemail->email ) )
				->sendOneToMany();
	}
}
?>