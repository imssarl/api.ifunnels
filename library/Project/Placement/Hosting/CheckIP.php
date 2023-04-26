<?php

class Project_Placement_Hosting_CheckIP {

	private static $_hostingMasterIP='/data/ip/master.txt';
	private static $_hostingSlaveIP='/data/ip/slave.txt';
	private static $_remoteIP='10.151.20.89';//'107.20.247.159';

	public static function getMaster(){
		if(!is_file(self::$_hostingMasterIP)){
			throw new Project_Placement_Exception('Can\'t get IP for master');
		}
		Core_Files::getContent( $_strIP, self::$_hostingMasterIP );
		return $_strIP;
	}

	public static function getSlave(){
		if(!is_file(self::$_hostingSlaveIP)){
			throw new Project_Placement_Exception('Can\'t get IP for master');
		}
		Core_Files::getContent( $_strIP, self::$_hostingSlaveIP );
		return $_strIP;
	}

	public static function getRemoteIP(){
		return self::$_remoteIP;
	}
}
?>