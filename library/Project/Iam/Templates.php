<?php


/**
 * News administration
 */

class Project_Iam_Templates{
/*
CREATE TABLE `iam_templates` (
	`template_id` INT(11) UNSIGNED NOT NULL DEFAULT '0',
	`flg_type` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
	PRIMARY KEY (`template_id`,`flg_type`)
)
COLLATE='utf8_general_ci'
ENGINE=MyISAM;
*/
	protected $_table='iam_templates';
	
	public function updateLinks( $_arrIds=array() ){
		if( empty($_arrIds) ){
			return $this;
		}
		$_updateLinks=array();
		$this->getLinks( $_arrLinks );
		foreach( $_arrIds as $_type=>$_templateIds ){
			foreach( $_templateIds as $_templateId ){
				foreach( $_arrLinks as $_k=>$_link ){
					if( $_link['template_id']==$_templateId && $_link['flg_type']==$_type ){
						unset( $_arrLinks[$_k] );
					}
				}
				if( $_templateId!=0 ){
					$_updateLinks[]=array('template_id'=>$_templateId, 'flg_type'=>$_type);
				}
			}
		}
		if( !empty( $_arrLinks ) ){
			$_iam=new Project_Iam();
			$_iam->removeLinks( $_arrLinks, array() );
		}
		$this->deleteLinks();
		if( !empty( $_updateLinks ) ){
			Core_Sql::setMassInsert( $this->_table, $_updateLinks );
		}
		return true;
	}

	public function getLinks( &$arrList ){
		$arrList=Core_Sql::getAssoc( 'SELECT template_id, flg_type, CONCAT(template_id,"_",flg_type) as template2type FROM '.$this->_table );
		return true;
	}

	public function deleteLinks(){
		if( Core_Sql::setExec('DELETE FROM '.$this->_table) ){
			return true;
		}
		return false;
	}
}
?>