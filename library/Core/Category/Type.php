<?php
class Core_Category_Type extends Core_Services {

	private $_table='category_types';
	private $_fields=array( 'id', 'flg_sort', 'flg_user', 'flg_typelink', 'flg_multilng', 'flg_deflng', 'type', 'storage', 'title', 'description', 'added' );

	private $_byTitle='';
	private $_byId=0;
	private $_toSelect=false;

	private $_data;
	private $_errors;

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

	public function setData( $_arrData=array() ) {
		$this->_data=new Core_Data( $_arrData );
		return $this;
	}

	public function getEntered( &$arrRes ) {
		$arrRes=$this->_data->getFiltered();
		return $this;
	}

	public function getErrors( &$arrRes ) {
		$arrRes=$this->_errors;
		return $this;
	}

	public function set() {
		$this->_data->setFilter()->setMask( $this->_fields );
		if ( empty( $this->_data->filtered ) ) {
			return true;
		}
		$_arrDel=array();
		foreach( $this->_data->filtered as $k=>$v ) {
			if ( !empty( $v['del'] ) ) {
				$_arrDel[]=$v['id'];
				continue;
			}
			if ( !$this->checkTitle( $v ) ) {
				$this->_errors[$k]=true;
				continue;
			}
			if ( empty( $v['id'] ) ) {
				unSet( $v['id'] );
				$v['added']=time();
			}
			$v['description']=empty( $v['description'] ) ? '':$v['description'];
			$v['storage']=empty( $v['storage'] ) ? '':$v['storage'];
			$v['flg_user']=empty( $v['flg_user'] ) ? 0:1;
			$v['flg_multilng']=empty( $v['flg_multilng'] ) ? 0:1;
			$v['flg_deflng']=empty( $v['flg_deflng'] ) ? 0:$v['flg_deflng'];
			$this->createNestedCats( $v );
			$this->_data->filtered[$k]['id']=Core_Sql::setInsertUpdate( $this->_table, $this->_data->getValidCurrent( $v ) );
		}
		$this->del( $_arrDel );
		return !empty( $this->_errors );
	}

	private function checkTitle( $_arr=array() ) {
		if ( empty( $_arr['title'] )&&!empty( $_arr['id'] ) ) { // если постое title у уже существующих типов
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

	private function createNestedCats( $_arrType ) {
		if ( $_arrType['type']!='nested'||empty( $_arrType['storage'] ) ) {
			return false;
		}
		$_strRes=Core_Sql::getCell( 'SHOW TABLES LIKE "'.$_arrType['storage'].'"' );
		if ( !empty( $_strRes ) ) {
			return true;
		}
		Core_Sql::setExec( 'CREATE TABLE `'.$_arrType['storage'].'` (
		  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
		  `pid` int(11) unsigned NOT NULL DEFAULT "0",
		  `level` int(11) unsigned NOT NULL DEFAULT "0",
		  `user_id` int(11) unsigned NOT NULL DEFAULT "0",
		  `priority` smallint(3) unsigned NOT NULL DEFAULT "100",
		  `title` varchar(255) NOT NULL DEFAULT "",
		  PRIMARY KEY (`id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8' );
		return true;
	}

	// сделать удаление таблиц с категориями типа nested
	public function del( $_mixId=array() ) {
		if ( empty( $_mixId ) ) {
			return false;
		}
		Core_Sql::setExec( '
			DELETE t, c, l, f, link
			FROM category_types t
			LEFT JOIN category_category c ON c.type_id=t.id
			LEFT JOIN category_links l ON l.type_id=t.id
			LEFT JOIN category_flags f ON f.type_id=t.id
			LEFT JOIN category_cat2flag link ON link.flag_id=f.id
			WHERE t.id IN('.Core_Sql::fixInjection( $_mixId ).')
		' );
		return true;
	}
}
?>