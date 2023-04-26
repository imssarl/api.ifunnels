<?php


/**
 * News administration
 */
class Project_Iam_Sites extends Project_Sites_History_Backend {
/*
CREATE TABLE `iam_sites` (
	`site_id` INT(11) UNSIGNED NOT NULL DEFAULT '0',
	`site_type` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
	PRIMARY KEY (`site_id`,`site_type`)
)
COLLATE='utf8_general_ci'
ENGINE=MyISAM;
*/
	public function getList( &$arrRes ){
		$_union=new Core_Sql_Qcrawler();
		if( !empty( $this->_withType ) ){
			$_arrTbl=array( $this->_withType=>Project_Sites::$tables[$this->_withType] );
		} else {
			$_arrTbl=Project_Sites::$tables;
		}
		foreach( $_arrTbl as $k=>$v ){
			$_crawler=new Core_Sql_Qcrawler();
			switch( $k ){
				case Project_Sites::BF:
					$_crawler->set_select( 'id, user_id, placement_id, title name, category_id, '.$k.' site_type, url, edited, catedit, iam2s.*, (SELECT COUNT(*) FROM '.$this->_linkTable.' WHERE site_type='.$k.' AND site_id=id) flg_iam' );
					$_innerJoin='INNER JOIN es_template2site et2s ON et2s.site_id=site.id
				INNER JOIN iam_templates iam2s ON iam2s.template_id=et2s.template_id AND et2s.flg_type='.$k;
				break;
				case Project_Sites::NVSB: 
				case Project_Sites::NCSB: 
					$_crawler->set_select( 'id, user_id, placement_id, main_keyword name, category_id, '.$k.' site_type, url, edited, catedit, iam2s.*, (SELECT COUNT(*) FROM '.$this->_linkTable.' WHERE site_type='.$k.' AND site_id=id) flg_iam' );
					$_innerJoin='INNER JOIN es_template2site et2s ON et2s.site_id=site.id
				INNER JOIN iam_templates iam2s ON iam2s.template_id=et2s.template_id AND et2s.flg_type='.$k;
				break;
			}
			if( !empty( $this->_userId ) ){
				$_crawler->set_where( 'user_id='.Core_Sql::fixInjection( $this->_userId ) );
			}
			if( !empty( $this->_withIds ) ){
				$_crawler->set_where( 'id IN ('.Core_Sql::fixInjection( $this->_withIds ).')' );
			}
			if( $this->_onlyActive ){
				$_crawler->set_where( '(SELECT COUNT(*) FROM '.$this->_linkTable.' WHERE site_type='.$k.' AND site_id=id)>0' );
			}
			$_crawler->set_from( $v.' site '.$_innerJoin );
			$_union->set_union_select( $_crawler );
		}
		$_union->set_order_sort( $this->_withOrder );
		if( !empty( $this->_withPaging ) ){
			$_union->set_paging( $this->_withPaging )->get_union_sql( $_strSql, $this->_paging );
		} elseif( !$this->_onlyCount ){
			$_union->gen_union_full( $_strSql );
		}
		if( $this->_onlyOne  ){
			$arrRes=Core_Sql::getRecord( $_strSql );
		}else{
			$arrRes=Core_Sql::getAssoc( $_strSql );
		}
		$this->init();
		return $this;
	}
	
	protected function init(){
		parent::init();
		$this->_onlyActive=false;
	}
	
	protected $_linkTable='iam_sites';
	
	private $_onlyActive=false;
	
	public function onlyActive(){
		$this->_onlyActive=true;
		return $this;
	}
	
	public function updateLinks( $_arrNewOn=array(), $_arrOldOn=array() ){
		$_forActivate=array_diff( $_arrNewOn, $_arrOldOn );
		if( count( $_forActivate ) > 0 ){
			foreach( $_forActivate as $_addLink ){
				$arrSiteIdType=explode('-',$_addLink);
				$_arrLinks[]=array( 'site_id'=>$arrSiteIdType['0'],'site_type'=>$arrSiteIdType['1'] );
			}
		}
		$_forDelete=array_diff( $_arrOldOn, $_arrNewOn );
		if( count( $_forDelete ) > 0 ){
			foreach( $_forDelete as $_removeLink ){
				$arrSiteIdType=explode('-',$_removeLink);
				$this->deleteLinks($arrSiteIdType['0'], $arrSiteIdType['1']);
			}
			$_iam=new Project_Iam();
			$_iam->removeLinks( $_forDelete, array() );
		}
		if( !empty( $_arrLinks ) ){
			Core_Sql::setMassInsert( $this->_linkTable, $_arrLinks );
		}
		return true;
	}

	public function deleteLinks( $siteId, $siteType ){
		if( empty( $siteId ) || empty( $siteType ) ){
			return false;
		}
		if( Core_Sql::setExec( 'DELETE FROM '.$this->_linkTable.' WHERE site_id='.$siteId.' AND site_type='.$siteType ) ){
			return true;
		}
		return false;
	}
}
?>