<?php

class Project_Placement_Hosting_Pdns {

	/**
	 * @var Project_Placement_Hosting_Ssh object
	 */
	private static $_ssh=null;
	private static $_logger=null;

	private static function log(){
		$writer=new Core_Log_Writer_File( Zend_Registry::get('config')->path->absolute->logfiles.'pdns'.DIRECTORY_SEPARATOR );
		self::$_logger=new Zend_Log( $writer->setFormatter( new Zend_Log_Formatter_Simple() ) );
	}

	public static function changeH1(){
		if(is_file(Zend_Registry::get('config')->path->absolute->user_files.'.ssh'.DIRECTORY_SEPARATOR.'pdns.txt')){
			unlink(Zend_Registry::get('config')->path->absolute->user_files.'.ssh'.DIRECTORY_SEPARATOR.'pdns.txt');
		}
		self::$_ssh=new Project_Placement_Hosting_Ssh();
		self::$_ssh->ssh()->download( '/data/scripts/pdns/pdns.h1',Zend_Registry::get('config')->path->absolute->user_files.'.ssh'.DIRECTORY_SEPARATOR.'pdns.txt' );
		self::$_logger->info('Change pdns rds on h1');
		self::reload();
	}

	public static function changeH2(){
		if(is_file(Zend_Registry::get('config')->path->absolute->user_files.'.ssh'.DIRECTORY_SEPARATOR.'pdns.txt')){
			unlink(Zend_Registry::get('config')->path->absolute->user_files.'.ssh'.DIRECTORY_SEPARATOR.'pdns.txt');
		}
		self::$_ssh=new Project_Placement_Hosting_Ssh();
		self::$_ssh->ssh()->download( '/data/scripts/pdns/pdns.h2',	Zend_Registry::get('config')->path->absolute->user_files.'.ssh'.DIRECTORY_SEPARATOR.'pdns.txt');
		self::$_logger->info('Change pdns rds on h2');
		self::reload();
	}

	private static function reload(){
		Core_Files::getContent( $_strConfig,Zend_Registry::get('config')->path->absolute->user_files.'.ssh'.DIRECTORY_SEPARATOR.'pdns.txt' );
		self::$_ssh->ssh()->setContent2File($_strConfig,'/etc/powerdns/pdns.d/pdns.rds');
		self::$_ssh->ssh()->execCmd('/etc/init.d/pdns reload');
		self::$_logger->info('Reload...');
	}

}
?>