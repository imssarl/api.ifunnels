<?php


/**
 * News administration
 */

class Project_Iam{
/*
CREATE TABLE `iam_users2sites` (
	`user_id` INT(11) UNSIGNED NOT NULL DEFAULT '0',
	`site_id` VARCHAR(23) NOT NULL,
	`added` INT(11) UNSIGNED NOT NULL DEFAULT '0',
	`flg_active` INT(1) UNSIGNED NOT NULL DEFAULT '0',
	PRIMARY KEY (`user_id`, `site_id`)
)
COLLATE='utf8_general_ci'
ENGINE=MyISAM;
*/
	protected $_fields=array(
		'user_id', 'site_id','added','flg_active'
	);
	
	protected $_table='iam_users2sites';
	
	protected $_withUserId=false;
	protected $_withSiteIdAndType=false;

	protected function init(){
		$this->_withUserId=false;
		$this->_withSiteIdAndType=false;
	}
	
	public function addLinks( $_sitesIdAndType=array(), $_forUsersIds=array() ){ // add new
		$this->checkUsersAndSitesData( $_sitesIdAndType, $_forUsersIds );
		$_time=time();
		$this->getLinks( $arrOldLinks );
		foreach( $_sitesIdAndType as $_siteIdAndType ){
			foreach( $_forUsersIds as $_userId ){
				$_flgHave=false;
				foreach( $arrOldLinks as $_oldList ){
					if( $_oldList['user_id'] == $_userId && $_oldList['site_id'] == $_siteIdAndType ){
						$_flgHave=true;
					}
				}
				if( !$_flgHave ){
					$_arrLink[]=array( 
						'user_id'=>$_userId,
						'site_id'=>$_siteIdAndType,
						'added'=>$_time
					);
				}
			}
		}
		if( !empty( $_arrLink ) ){
			Core_Sql::setMassInsert( $this->_table, $_arrLink );
		}
		return true;
	}
	
	public function updateLinks( $_sitesIdAndType=array(), $_forUsersIds=array() ){ // remove old (only for selected users) and add new
		$arrSites=$this->checkUsersAndSitesData( $_sitesIdAndType, $_forUsersIds );
		$_time=time();
		$this->getLinks( $arrOldLinks );
		foreach( $_forUsersIds as $_userId ){
			foreach( $_sitesIdAndType as $_siteIdAndType ){
				$_removeLinks=array();
				$_flgHave=false;
				foreach( $arrOldLinks as $_oldList ){
					if( $_oldList['user_id'] == $_userId && $_oldList['site_id'] == $_siteIdAndType ){
						$_flgHave=true;
					}
					if( $_oldList['user_id'] == $_userId && !in_array( $_oldList['site_id'], array_filter( $_sitesIdAndType ) ) ){
						$_removeLinks[]='user_id='.Core_Sql::fixInjection( $_userId ).' AND site_id='.Core_Sql::fixInjection( $_oldList['site_id'] );
					}
				}
				if( !$_flgHave ){
					$_arrLink[]=array(
						'user_id'=>$_userId,
						'site_id'=>$_siteIdAndType,
						'added'=>$_time
					);
				}
			}
		}
		if( !empty( $_removeLinks ) ){
			Core_Sql::setExec( 'DELETE FROM '.$this->_table.' WHERE '.implode( ' OR ', $_removeLinks ) );
		}
		if( !empty( $_arrLink ) ){
			Core_Sql::setMassInsert( $this->_table, $_arrLink );
		}
		foreach( $arrSites as $_site ){
			$this->updateSiteFile( $_site );
		}
		return true;
	}
	
	public function removeLinks( $_sitesIdAndType=array(), $_forUsersIds=array() ){ // remove selected
		$arrSites=$this->checkUsersAndSitesData( $_sitesIdAndType, $_forUsersIds );
		$_arrSqlWhere=array();
		foreach( $_sitesIdAndType as $_siteIdAndType ){
			foreach( $_forUsersIds as $_userId ){
				$_arrSqlWhere[]='user_id='.Core_Sql::fixInjection($_userId).' AND site_id='.Core_Sql::fixInjection($_siteIdAndType['template_id']);
			}
		}
		
		
		
		if( !empty( $_arrSqlWhere ) ){
			Core_Sql::setExec( 'DELETE FROM '.$this->_table.' WHERE '.implode(' OR ',$_arrSqlWhere) );
		}
		foreach( $arrSites as $_site ){
			$this->updateSiteFile( $_site );
		}
		return true;
	}

	public function checkUsersAndSitesData( &$_sitesIdAndType, &$_forUsersIds ){
		$arrSites=array();
		if( empty( $_sitesIdAndType ) ){
			$_sites=new Project_Iam_Sites();
			$_sites
				->getList( $arrSites );
			foreach( $arrSites as $_site ){
				$_sitesIdAndType[]=$_site['id'].'_'.$_site['flg_type'];
			}
		}else{
			$_sitesIdAndType=array_filter( $_sitesIdAndType );
			foreach( $_sitesIdAndType as $_siteIdAndType ){
				$_arrSiteIdAndType=explode( '_', $_siteIdAndType );
				$arrSites[]=array(
					'id'=>$_arrSiteIdAndType[0],
					'flg_type'=>$_arrSiteIdAndType[1]
				);
			}
		}
		if( empty( $_forUsersIds ) ){
			$_users=new Project_Iam_Users();
			$_users
				->onlyIds()
				->getList( $_forUsersIds );
		}
		return $arrSites;
	}

	public function updateSiteFile( $_site ){
		if( isset( $_SERVER['HTTP_HOST'] ) && $_SERVER['HTTP_HOST'] == 'cnm.local' ){
			return true;
		}
		if( !isset( $_site ) || empty( $_site ) 
			|| !isset( $_site['flg_type'] ) || !isset( $_site['id'] )
		){
			return false;
		}
		if( $_site['flg_type'] == Project_Sites::NCSB ){
			$_driver=new Project_Sites_Adapter_Ncsb();
			$_newUserId=Core_Sql::getCell( 'SELECT user_id FROM '.$_driver->table.' WHERE id='.Core_Sql::fixInjection( $_site['id'] ) );
			if( !empty( $_newUserId ) ){
				$_admin=Core_Users::$info['id'];
				Core_Users::getInstance()->setById( $_newUserId );
			}
			$_model=new Project_Sites( $_site['flg_type'] );
			$_model->getSite( $_arr, $_site['id'] );
			if( !empty( $_newUserId ) ){
				Core_Users::getInstance()->setById( $_admin );
			}
			$arrSite=$_arr['arrNcsb'];
		}elseif( $_site['flg_type'] == Project_Sites::NVSB ){
			$_driver=new Project_Sites_Adapter_Nvsb();
			$_newUserId=Core_Sql::getCell( 'SELECT user_id FROM '.$_driver->table.' WHERE id='.Core_Sql::fixInjection( $_site['id'] ) );
			if( !empty( $_newUserId ) ){
				$_admin=Core_Users::$info['id'];
				Core_Users::getInstance()->setById( $_newUserId );
			}
			$_model=new Project_Sites( $_site['flg_type'] );
			$_model->getSite( $_arr, $_site['id'] );
			if( !empty( $_newUserId ) ){
				Core_Users::getInstance()->setById( $_admin );
			}
			$arrSite=$_arr['arrNvsb'];
		}elseif( $_site['flg_type'] == Project_Sites::BF ){
			return false;
		}
		$_placement=new Project_Placement();
		$_placement->withIds( $arrSite['placement_id'] )->onlyOne()->getList( $arrSite['domen'] );
		if( isset( $arrSite['placement_id'] ) && !empty( $arrSite['placement_id'] ) ){
			$this->_withSiteIdAndType=$_site['id'].'_'.$_site['flg_type'];
			$this->getLinks( $_arrLinks );
			if( !empty( $_arrLinks ) ){
				$_usersIds=array();
				foreach( $_arrLinks as $_link ){
					$_usersIds[]=$_link;
				}
				$_transport=new Project_Placement_Transport();
				$_transport->setInfo( $arrSite );
				if( !empty( $_usersIds ) ){
					$_users=new Project_Iam_Users();
					$_users
						->onlyCBIDs()
						->onlyIds()
						->withIds( $_usersIds )
						->getList( $_arrCBIDs );
					foreach( $_arrCBIDs as $_id=>&$_cbid ){
						if( strpos($_cbid, 'http:' ) !== false || strpos($_cbid, 'https:') !== false || strpos($_cbid, '.') !== false || strpos($_cbid, ' ') !== false ){
							unset( $_arrCBIDs[$_id] );
						}
					}
					$_arrCBIDs=array_unique( $_arrCBIDs );
					$_strCBIDs=implode( ':', $_arrCBIDs );
					$_transport->saveFile( $_strCBIDs, 'cbids.txt');
				}else{
					$_transport->removeFile( 'cbids.txt' );
				}
			}
		}
		return true;
	}

	public function activateLinks(){
		set_time_limit(0);
		ignore_user_abort(true);
		error_reporting( E_ALL );
		$this->getLinks( $_arrLinks );
		$_site2user=array();
		foreach( $_arrLinks as $_link ){
			$_site2user[$_link['site_id']][]=$_link['user_id'];
		}
		$_obj=new Project_Iam_Sites();
		$_obj->getList( $arrSites );
		foreach( $_site2user as $_strSiteIdAndType=>$_usersIds ){
			foreach( $arrSites as $_site ){
				$_siteIdAndType=explode( '_', $_strSiteIdAndType );
				if( $_site['id'] == $_siteIdAndType[0] && $_site['flg_type'] == $_siteIdAndType[1] ){
					if( $_site['flg_type'] == Project_Sites::NCSB ){
						$_driver=new Project_Sites_Adapter_Ncsb();
						$_newUserId=Core_Sql::getCell( 'SELECT user_id FROM '.$_driver->table.' WHERE id='.Core_Sql::fixInjection( $_site['id'] ) );
						if( !empty( $_newUserId ) ){
							$_admin=false;
							if( isset( Core_Users::$info['id'] ) && !empty( Core_Users::$info['id'] ) ){
								$_admin=Core_Users::$info['id'];
							}
							Core_Users::getInstance()->setById( $_newUserId );
						}
						$_model=new Project_Sites( $_site['flg_type'] );
						$_model->getSite( $_arr, $_site['id'] );
						if( !empty( $_newUserId ) && !empty( $_admin ) ){
							Core_Users::getInstance()->setById( $_admin );
						}
						$arrSite=$_arr['arrNcsb'];
					}elseif( $_site['flg_type'] == Project_Sites::NVSB ){
						$_driver=new Project_Sites_Adapter_Nvsb();
						$_newUserId=Core_Sql::getCell( 'SELECT user_id FROM '.$_driver->table.' WHERE id='.Core_Sql::fixInjection( $_site['id'] ) );
						if( !empty( $_newUserId ) ){
							$_admin=Core_Users::$info['id'];
							Core_Users::getInstance()->setById( $_newUserId );
						}
						$_model=new Project_Sites( $_site['flg_type'] );
						$_model->getSite( $_arr, $_site['id'] );
						if( !empty( $_newUserId ) ){
							Core_Users::getInstance()->setById( $_admin );
						}
						$arrSite=$_arr['arrNvsb'];
					}elseif( $_site['flg_type'] == Project_Sites::BF ){
						return false;
					}
					$_placement=new Project_Placement();
					$_placement->withIds( $arrSite['placement_id'] )->onlyOne()->getList( $arrSite['domen'] );
					$_transport=new Project_Placement_Transport();
					$_transport->setInfo( $arrSite );
					$_users=new Project_Iam_Users();
					$_users
						->onlyCBIDs()
						->onlyIds()
						->withIds( $_usersIds )
						->getList( $_arrCBIDs );
					$_strCBIDs=implode( ':', $_arrCBIDs );
					if( $_transport->saveFile( $_strCBIDs, 'cbids.txt') ){
						Core_Sql::setExec( 'UPDATE '.$this->_table.' SET flg_active=1 WHERE site_id='.Core_Sql::fixInjection( $_strSiteIdAndType ).' AND user_id IN ('.Core_Sql::fixInjection( $_usersIds ).')' );
					}
				}
			}
		}
	}

	public function getLinks( &$arrList ){
		$_arrWhere=array();
		$_elements='*';
		if( !empty( $this->_withUserId ) ){
			$_arrWhere[]='user_id IN ('.Core_Sql::fixInjection( $this->_withUserId ).')';
			$_elements='site_id';
		}
		if( !empty( $this->_withSiteIdAndType ) ){
			$_arrWhere[]='site_id IN ('.Core_Sql::fixInjection( $this->_withSiteIdAndType ).')';
			$_elements='user_id';
		}
		$_query='SELECT '.$_elements.' FROM '.$this->_table;
		if( !empty( $_arrWhere ) ){
			$_query.=' WHERE '.implode(' ',$_arrWhere);
		}
		if( count( $_arrWhere ) == 1 ){
			$arrList=Core_Sql::getField( $_query );
		}else{
			$arrList=Core_Sql::getAssoc( $_query );
		}
		$this->init();
		return true;
	}

	public function withUserId( $_id ){
		if( isset( $_id ) && !empty( $_id ) ){
			$this->_withUserId=$_id;
		}
		return $this;
	}
	
	public function withSiteIdAndType( $_id ){
		if( isset( $_id ) && !empty( $_id ) ){
			$this->_withSiteIdAndType=$_id;
		}
		return $this;
	}

	public function deleteLinks(){
		if( !empty( $this->_withUserId ) && Core_Sql::setExec('DELETE FROM '.$this->_table.' WHERE user_id IN ('.Core_Sql::fixInjection( $this->_withUserId ).')') ){
			$this->init();
			return true;
		}
		if( !empty( $this->_withSiteIdAndType ) && Core_Sql::setExec('DELETE FROM '.$this->_table.' WHERE site_id IN ('.Core_Sql::fixInjection( $this->_withSiteIdAndType ).')') ){
			$this->init();
			return true;
		}
		return false;
	}
}
?>