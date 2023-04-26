<?php

class Project_Widget_Adapter_Hiam_Lite_Link {

	public static $table='hi_lite_link2group';

	public function addLink( $_groupIds, $_campaignId ){
		if(empty($_groupIds)){
			return false;
		}
		Core_Sql::setExec('DELETE FROM '.self::$table.' WHERE ad_id='.$_campaignId );
		foreach( $_groupIds as $_id ){
			$data[]=array('ad_id'=>$_campaignId,'group_id'=>$_id);
		}
		Core_Sql::setMassInsert( self::$table, $data );
		return true;
	}

	public function getGroups2Campaign( $_campaignId ){
		return Core_Sql::getField('SELECT group_id FROM '.self::$table.' WHERE ad_id='.$_campaignId );
	}

	public function del( $_campaignIds ){
		Core_Sql::setExec('DELETE FROM '.self::$table.' WHERE ad_id IN ('. Core_Sql::fixInjection($_campaignIds) .')' );
	}
}
?>