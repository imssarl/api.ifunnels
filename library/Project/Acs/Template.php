<?php

class Project_Acs_Template {

	private static $_table='u_link2template';
	private $_type=false;

	public function setType( $_int ){
		$this->_type=$_int;
		return $this;
	}

	public function addLink( $_groupId, $_templateIds ){
		if( empty($this->_type)||empty($_groupId)||empty($_templateIds) ){
			return false;
		}
		Core_Sql::setExec('DELETE FROM '.self::$_table.' WHERE group_id='.$_groupId.' AND flg_type='.$this->_type);
		foreach( $_templateIds as $_id ){
			Core_Sql::setInsert(self::$_table,array(
				'template_id'=>$_id,
				'group_id'=>$_groupId,
				'flg_type'=>$this->_type
			));
		}
		$this->init();
		return true;
	}

	private function init(){
		$this->_type=false;
	}

	public function get2group( $_groupId ){
		if( empty($this->_type)||empty($_groupId) ){
			throw new Exception('Empty type or group id');
		}
		$arrRes=Core_Sql::getField('SELECT template_id FROM '.self::$_table.' WHERE group_id='.$_groupId.' AND flg_type='.$this->_type );
		$this->init();
		return $arrRes;
	}

	public static function haveAccess( $_templateId,$_intType ){
		if( empty($_templateId)||empty($_intType) ){
			throw new Exception('Empty type or template id');
		}
		return Core_Sql::getCell('SELECT * FROM '. self::$_table .' WHERE source_id='.$_templateId.' AND group_id IN ('. Core_Sql::fixInjection(array_flip(Core_Users::$info['groups'])) .')');
	}

	public static function getAccessSql( $_intType ){
		if( empty($_intType) ){
			throw new Exception('Empty $_intType');
		}
		return "SELECT template_id FROM ".self::$_table.' WHERE group_id IN ('. Core_Sql::fixInjection(array_flip(Core_Users::$info['groups'])) .') AND flg_type='.$_intType;
	}
}
?>
