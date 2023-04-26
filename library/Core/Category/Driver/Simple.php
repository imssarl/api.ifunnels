<?php
class Core_Category_Driver_Simple extends Core_Category_Driver_Abstract {

	public function get( &$mixRes, &$arrPg ) {
		$_crawler=new Core_Sql_Qcrawler();
		if ( $this->_toSelect ) {
			$_crawler->set_select( 'id, title' );
		} else {
			$_crawler->set_select( '*' );
		}
		$_crawler->set_from( $this->_table );
		$_crawler->set_where( 'type_id="'.$this->_type['id'].'"' );
		$_crawler->set_where( ( empty( $this->_type['flg_user'] )? '':'user_id="'.$this->userId.'"' ) );
		if ( !empty( $this->_byTitle ) ) {
			$_crawler->set_where( 'title='.Core_Sql::fixInjection( $this->_byTitle ) );
		} elseif ( !empty( $this->_byId ) ) {
			$_crawler->set_where( 'id='.Core_Sql::fixInjection( $this->_byId ) );
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

	public function setCategory() {}

	public function set( &$arrRes, &$arrErr, $_arrData=array() ) {
		$arrRes=$_arrData;
		unSet( $arrRes[0] );
		$_arrDel=array();
		$_arrData=Core_A::array_check( $_arrData, $this->post_filter );
		foreach( $_arrData as $v ) {
			if ( !empty( $v['del'] ) ) {
				$_arrDel[]=$v['id'];
				continue;
			}
			if ( !$this->checkTitle( $v ) ) {
				if ( !empty( $v['id'] ) ) { // если у старой категории поменяли тайтл на такой же как у другой то ошибка, в остальных случаях пропускаем
					$arrErr[$v['id']]=true;
				}
				continue;
			}
			if ( empty( $v['id'] ) ) {
				unSet( $v['id'] );
				$v['user_id']=empty($this->userId)? 0:$this->userId;
				$v['type_id']=$this->_type['id'];
			}
			Core_Sql::setInsertUpdate( $this->_table, $this->get_valid_array( $v, $this->_fields ) );
		}
		$this->del( $_arrDel );
		return empty( $arrErr );
	}

	public function del( $_mixId=array() ) {
		if ( empty( $_mixId ) ) {
			return false;
		}
		Core_Sql::setExec( 'DELETE FROM '.$this->_table.' WHERE id IN('.Core_Sql::fixInjection( $_mixId ).')'.
			(empty($this->_type['flg_user']) ?'':' AND user_id="'.$this->userId.'"') );
		return true;
	}
}
?>