<?php
class Core_Category_Flag extends Core_Services {

	private $_table='category_flags';
	private $_fields=array( 'id', 'type_id', 'title', 'description', 'added' );

	private $_type; // текущий тип категорий

	private $_byTitle='';
	private $_byId=0;
	private $_toSelect=false;

	public function byTitle( $_str='' ) {
		$this->_byTitle=$_str;
		return $this;
	}

	public function byId( $_int=0 ) {
		$this->_byId=$_int;
		return $this;
	}

	public function toSelect() {
		$this->_toSelect=true;
		return $this;
	}

	// сброс настроек после выполнения get
	private function init() {
		$this->_byTitle='';
		$this->_byId=0;
		$this->_toSelect=false;
	}

	public function get( &$mixRes ) {
		$_crawler=new Core_Sql_Qcrawler();
		if ( $this->_toSelect ) {
			$_crawler->set_select( 'id, title' );
		} else {
			$_crawler->set_select( '*' );
		}
		$_crawler->set_from( $this->_table );
		$_crawler->set_where( 'type_id="'.$this->_type['id'].'"' );
		if ( !empty( $this->_byTitle ) ) {
			$_crawler->set_where( 'title='.Core_Sql::fixInjection( $this->_byTitle ) );
		} elseif ( !empty( $this->_byId ) ) {
			$_crawler->set_where( 'id='.Core_Sql::fixInjection( $this->_byId ) );
		}
		$_crawler->get_result_full( $_strSql );
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

	private function checkTitle( $_arr=array() ) {
		if ( empty( $_arr['title'] ) ) {
			return false;
		}
		if ( $this->byTitle( $_arr['title'] )->get( $_arrTmp ) ) {
			if ( !empty( $_arr['id'] )&&$_arr['id']!=$_arrTmp['id'] ) {
				return false;
			}
			if ( empty( $_arr['id'] ) ) {
				return false;
			}
		}
		return true;
	}

	public function setTypeFull( $_arr=array() ) {
		$this->_type=$_arr;
	}

	public function setTypeByTitle( $_title='' ) {
		if ( empty( $_title ) ) {
			return false;
		}
		$_obj=new Core_Category_Type();
		if ( !$this->byTitle( $_title )->get( $_arrRes ) ) {
			return false;
		}
		$this->_type=$_arrRes;
		return true;
	}

	public function setTypeById( $_id=0 ) {
		if ( empty( $_id ) ) {
			return false;
		}
		$_obj=new Core_Category_Type();
		if ( !$_obj->byId( $_id )->get( $_arrRes ) ) {
			return false;
		}
		$this->_type=$_arrRes;
		return true;
	}

	public function set( &$arrRes, &$arrErr, $_arrData=array() ) {
		if ( empty( $this->_type ) ) {
			return false;
		}
		$_arrData=Core_A::array_check( $_arrData, $this->post_filter );
		$_arrDel=array();
		foreach( $_arrData as $v ) {
			if ( !empty( $v['del'] ) ) {
				$_arrDel[]=$v['id'];
				continue;
			}
			if ( !$this->checkTitle( $v ) ) {
				continue;
			}
			if ( empty( $v['id'] ) ) {
				unSet( $v['id'] );
				$v['added']=time();
			}
			$v['type_id']=$this->_type['id'];
			$v['description']=empty( $v['description'] ) ? '':$v['description'];
			Core_Sql::setInsertUpdate( $this->_table, $this->get_valid_array( $v, $this->_fields ) );
		}
		$this->del( $_arrDel );
		return true;
	}

	public function del( $_mixId=array() ) {
		if ( empty( $_mixId ) ) {
			return false;
		}
		Core_Sql::setExec( '
			DELETE f, link
			FROM category_flags f
			LEFT JOIN category_cat2flag link ON link.flag_id=f.id
			WHERE f.id IN('.Core_Sql::fixInjection( $_mixId ).')
		' );
		return true;
	}
}
?>