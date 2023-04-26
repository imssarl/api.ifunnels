<?php

class Project_Acs_Source {

	private static $_table='u_link2source';

	public function addLink( $_groupId, $_sourceIds ){
		if( empty($_groupId)||empty($_sourceIds) ){
			throw new Exception('Empty group or source Ids');
		}
		Core_Sql::setExec('DELETE FROM '.self::$_table.' WHERE group_id='.$_groupId);
		foreach( $_sourceIds as $_id ){
			Core_Sql::setInsert(self::$_table,array(
				'source_id'=>$_id,
				'group_id'=>$_groupId
			));
		}
		return true;
	}

	public function get2group( $_groupId ){
		if( empty($_groupId) ){
			throw new Exception('Empty group Id');
		}
		return Core_Sql::getField('SELECT source_id FROM '.self::$_table.' WHERE group_id='.$_groupId );
	}

	public static function haveAccess( $_sourceId ){
		if( empty($_sourceId) ){
			throw new Exception('Empty source Id');
		}
		return Core_Sql::getCell('SELECT * FROM '. self::$_table .' WHERE source_id='.$_sourceId.' AND group_id IN ('. Core_Sql::fixInjection(array_flip(Core_Users::$info['groups'])) .')');
	}
}
?>
