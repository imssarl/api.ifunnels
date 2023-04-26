<?php

class Project_Placement_Hosting_Ssh {

	private static $_hostingMasterIP='/data/ip/master.txt';
	private static $_hostingSlaveIP='/data/ip/slave.txt';
	private $_ssh=null;
	private static $_sshUser='userdata';

	/**
	 * Try connect to master server, if master is down try connect to slave
	 */
	public function __construct( $_hostingType='master' ){
		try{
			$this->_ssh=new Core_Ssh(
				self::getMasterHost(), //	(($_hostingType=='master')?self::getMasterHost():self::getSlaveHost()),
				self::$_sshUser,
				Zend_Registry::get('config')->path->absolute->user_files.'.ssh'.DIRECTORY_SEPARATOR.'userdata.pub',
				Zend_Registry::get('config')->path->absolute->user_files.'.ssh'.DIRECTORY_SEPARATOR.'userdata.pem'
			);
		} catch ( Core_Ssh_Exception $e ){
				throw new Core_Ssh_Exception('Can\'t connect to master & slave server hosting. '.$e->getMessage());
		}
	}

	/**
	 * Return ssh connection
	 * @return Core_Ssh | null
	 */
	public function ssh(){
		return $this->_ssh;
	}

	/**
	 * Get IP for master server
	 * @return string
	 */
	private static function getMasterHost(){
		/**/if( @$_SERVER['HTTP_HOST'] != 'cnm.local' ){
		if(!is_file(self::$_hostingMasterIP)){
			throw new Core_Ssh_Exception('Can\'t get IP for master');
		}
		Core_Files::getContent( $_strIP, self::$_hostingMasterIP );
		/**/}else{
			$_strIP='192.168.1.1';
		}
		return $_strIP;
	}

	/**
	 * Get IP for slave server
	 * @return string
	 */
	private static function getSlaveHost(){
		/**/if( @$_SERVER['HTTP_HOST'] != 'cnm.local' ){
		if(!is_file(self::$_hostingSlaveIP)){
			throw new Core_Ssh_Exception('Can\'t get IP for slave');
		}
		Core_Files::getContent( $_strIP, self::$_hostingSlaveIP );
		/**/}else{
			$_strIP='192.168.1.1';
		}
		return $_strIP;
	}
}