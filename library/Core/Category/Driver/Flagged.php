<?php
class Core_Category_Driver_Flagged extends Core_Category_Driver_Abstract {

	private $_linktable='category_cat2flag';
	private $_flags=array();

	private $_withFlags=array();
	private $_withoutFlags=array();

	public function __construct( $_arrRes=array() ) {
		parent::__construct( $_arrRes );
		$this->initFlags();
	}

	// array( flag_title1, flag_title2, ... )
	public function withFlags( $_arr=array() ) {
		$this->_withFlags=$_arr;
		return $this;
	}

	// array( flag_title1, flag_title2, ... )
	public function withoutFlags( $_arr=array() ) {
		$this->_withoutFlags=$_arr;
		return $this;
	}

	// сброс настроек после выполнения get
	protected function init() {
		$this->_withFlags=array();
		$this->_withoutFlags=array();
		parent::init();
	}

	public function get( &$mixRes, &$arrPg ) {
		$_crawler=new Core_Sql_Qcrawler();
		if ( $this->_toSelect ) {
			$_crawler->set_select( 'id, title' );
		} else {
			$_crawler->set_select( '*' );
			if ( !empty( $this->_flags ) ) {
				foreach( $this->_flags as $flag=>$name ) {
					$_crawler->set_select( 'IFNULL((SELECT 1 FROM '.$this->_linktable.' WHERE cat_id='.$this->_table.'.id AND flag_id="'.$flag.'"),0) flag'.$flag );
				}
			}
		}
		$_crawler->set_from( $this->_table );
		$_crawler->set_where( 'type_id="'.$this->_type['id'].'"' );
		$_crawler->set_where( ( empty( $this->_type['flg_user'] )? '':'user_id="'.$this->userId.'"' ) );
		if ( !empty( $this->_byTitle ) ) {
			$_crawler->set_where( 'title='.Core_Sql::fixInjection( $this->_byTitle ) );
		} elseif ( !empty( $this->_byId ) ) {
			$_crawler->set_where( 'id='.Core_Sql::fixInjection( $this->_byId ) );
		}
		if ( !empty( $this->_withFlags ) ) {
			foreach( $this->_withFlags as $v ) {
				$_crawler->set_where( 'IFNULL((SELECT 1 FROM '.$this->_linktable.' WHERE cat_id='.$this->_table.'.id AND flag_id="'.array_search($v,$this->_flags).'"),0)' );
			}
		}
		if ( !empty( $this->_withoutFlags ) ) {
			foreach( $this->_withoutFlags as $v ) {
				$_crawler->set_where( 'IFNULL((SELECT 0 FROM '.$this->_linktable.' WHERE cat_id='.$this->_table.'.id AND flag_id="'.array_search($v,$this->_flags).'"),1)' );
			}
		}
		$_crawler->set_order( ( empty( $this->_type['flg_sort'] )? 'title ASC':'priority DESC, title ASC' ) );
		if ( !empty( $this->_withPagging ) ) {
			$this->_withPagging['rowtotal']=Core_Sql::getCell( $_crawler->get_result_counter( $_strTmp ) );
			$_crawler->set_paging( $this->_withPagging )->get_sql( $_strSql, $arrPg );
		} else {
			$_crawler->get_result_full( $_strSql );
		}
		if ( $this->_toSelect ) {
			$mixRes=Core_Sql::getKeyVal( $_strSql );
		} elseif ( !empty( $this->_byTitle )||!empty( $this->_byId ) ) {
			$mixRes=Core_Sql::getRecord( $_strSql );
		} else {
			$mixRes=Core_Sql::getAssoc( $_strSql );
		}
		$this->init();
		return !empty( $mixRes );
	}

	public function getFlags( &$arrRes ) {
		$arrRes=$this->_flags;
	}

	public function setCategory() {}

	public function set( &$arrRes, &$arrErr, $_arrData=array() ) {
		$arrRes=$_arrData;
		unSet( $arrRes[0] );
		$_arrDel=$this->_flagsdel=$this->_flagsnew=array();
		$_arrData=Core_A::array_check( $_arrData, $this->post_filter );
		foreach( $_arrData as $v ) {
			if ( !empty( $v['del'] ) ) {
				$_arrDel[]=$v['id'];
				continue;
			}
			if ( !$this->checkTitle( $v ) ) {
				if ( !empty( $v['id'] ) ) {
					$arrErr[$v['id']]=true;
				}
				continue;
			}
			if ( empty( $v['id'] ) ) {
				unSet( $v['id'] );
				$v['user_id']=empty($this->userId)? 0:$this->userId;
			}
			$v['type_id']=$this->_type['id'];
			$v['id']=Core_Sql::setInsertUpdate( $this->_table, $this->get_valid_array( $v, $this->_fields ) );
			$this->parseFlags( $v );
		}
		$this->del( $_arrDel );
		$this->setFlags();
		return empty( $arrErr );
	}

	public function del( $_mixId=array() ) {
		if ( empty( $_mixId ) ) {
			return false;
		}
		Core_Sql::setExec( '
			DELETE c, link
			FROM category_category c
			LEFT JOIN category_cat2flag link ON link.cat_id=c.id
			WHERE c.id IN('.Core_Sql::fixInjection( $_mixId ).')'.
			(empty($this->_type['flg_user']) ?'':' AND c.user_id="'.$this->userId.'"') );
		return true;
	}

	private function parseFlags( $_arrCat=array() ) {
		if ( empty( $_arrCat['id'] ) ) {
			return;
		}
		$this->_flagsdel[]=$_arrCat['id'];
		foreach( $_arrCat as $k=>$v ) {
			// found flag25, flag2, etc fields
			if ( !is_numeric( ( $flag_id=substr( $k, 4 ) ) ) ) {
				continue;
			}
			$this->_flagsnew[]=array( 'cat_id'=>$_arrCat['id'], 'flag_id'=>$flag_id );
		}
	}

	private function setFlags() {
		if ( !empty( $this->_flagsdel ) ) {
			Core_Sql::setExec( 'DELETE FROM '.$this->_linktable.' WHERE cat_id IN('.Core_Sql::fixInjection( $this->_flagsdel ).')' );
		}
		if ( !empty( $this->_flagsnew ) ) {
			Core_sql::setMassInsert( $this->_linktable, $this->_flagsnew );
		}
	}

	private function initFlags() {
		if ( empty( $this->_type ) ) {
			return;
		}
		$_flag=new Core_Category_Flag();
		$_flag->setTypeFull( $this->_type );
		$_flag->toSelect()->get( $this->_flags );
	}
}
?>