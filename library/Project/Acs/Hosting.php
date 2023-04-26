<?php

class Project_Acs_Hosting {

	private static $_table='u_link2hosting';
	/**
	 * Type hossing
	 */
	const REMOTE_ID=1,LOCAL_ID=2;

	public function addLink( $_groupId, $_hostingIds ){
		if( empty($_groupId)||empty($_hostingIds) ){
			throw new Exception('Empty group or hosting Ids');
		}
		Core_Sql::setExec('DELETE FROM '.self::$_table.' WHERE group_id='.$_groupId);
//		p($_hostingIds);
		foreach( $_hostingIds as $_id ){
			Core_Sql::setInsert(self::$_table,array(
				'hosting_id'=>$_id,
				'group_id'=>$_groupId
			));
		}
		return true;
	}

	public function get2group( $_groupId ){
		if( empty($_groupId) ){
			throw new Exception('Empty group Id');
		}
		return Core_Sql::getField('SELECT hosting_id FROM '.self::$_table.' WHERE group_id='.$_groupId );
	}

	public static function haveAccess( $_hostingId ){
		if( empty($_hostingId) ){
			throw new Exception('Empty hosting Id');
		}
		return Core_Sql::getCell('SELECT * FROM '. self::$_table .' WHERE hosting_id='.$_hostingId.' AND group_id IN ('. Core_Sql::fixInjection(array_flip(Core_Users::$info['groups'])) .')');
	}
}
?>
