<?php


/**
 * Blog comments remote management
 */
class Project_Wpress_Content_Comments extends Project_Wpress_Content_Abstract {


	public function __construct() {
		$this->setTable( 'bf_ext_comments' )
			->setFields( array( 'id', 'ext_id', 'blog_id', 'ext_post_id', 'content', 'added' ) )
			->setDefaultOrder();		
	}
	
	public function getList( &$mixRes ){
		$_crawler=new Core_Sql_Qcrawler();
		$_crawler->set_select( 'c.*' );
		$_crawler->set_from( $this->table.' c' );
		$_crawler->set_where( 'c.blog_id='.Core_Sql::fixInjection( $this->blog->filtered['id'] ) );
		if ( !empty( $this->_onlyPost ) ) {
			$_crawler->set_where('c.ext_post_id='.Core_Sql::fixInjection( $this->_onlyPost ) );
		}
		$_crawler->set_order_sort( $this->_withOrder );
		if ( !empty( $this->_withPagging ) ) {
			$this->_withPagging['rowtotal']=Core_Sql::getCell( $_crawler->get_result_counter( $_strTmp ) );
			$_crawler->set_paging( $this->_withPagging )->get_sql( $_strSql, $this->_paging );
		} elseif ( !$this->_onlyCount ) {
			$_crawler->get_result_full( $_strSql );
		}
		if ( $this->_onlyIds ) {
			$mixRes=Core_Sql::getField( $_strSql );
		} elseif ( $this->_onlyCount ) {
			$mixRes=Core_Sql::getCell( $_crawler->get_result_counter() );
		} elseif ( $this->_onlyOne||!empty( $this->_byTitle ) ) {
			$mixRes=Core_Sql::getRecord( $_strSql );
		} else {
			$mixRes=Core_Sql::getAssoc( $_strSql );
		}
		$this->init();
		return !empty( $mixRes );		
	}
	
	public function get( &$arrRes, $_intId=0 ) {}
	

	public function set() {
		// проверяем данные на ошибки
		$this->data->setFilter();
		foreach( $this->data->filtered as $v ) {
			if ( empty( $v['ext_post_id'] ) ) {
				$this->errors[$v['id']]=true;
			}
		}
		if ( !empty( $this->errors ) ) {
			return false;
		}
		// добавление/изменение/удаление на серваке
		$_export=new Project_Sites_Adapter_Blogfusion_Export( $this->blog );
		if ( !$_export->comment( $this ) ) {
			return false;
		}
		$this->setToDb( $this->data );
		return true;
	}

	// используется как при импорте категорий так и при управлении категориями
	public function setToDb( Core_Data $obj ) {
		if (empty($obj->filtered)) {
			$obj->setFilter(); // при иморте сделаем ещё раз
		}		
		$obj->setMask( $this->fields );
		foreach( $obj->filtered as $v ) {
			if ( !empty( $v['del'] ) ) {
				if ( !empty( $v['id'] ) ) {
					$arrDel[]=$v['id'];
				}
				continue;
			}
			if ( empty( $v['id'] ) ) {
				unSet( $v['id'] );
				$v['blog_id']=$this->blog->filtered['id'];
				$v['added']=time();
			}
			Core_Sql::setInsertUpdate( $this->table, $obj->getValidCurrent( $v ) );
		}
		$this->del( $arrDel );
	}

	public function del( $_mixId=array() ) {
		if ( empty( $_mixId ) ) {
			return false;
		}
		Core_Sql::setExec( 'DELETE FROM '.$this->table.' WHERE id IN('.Core_Sql::fixInjection( $_mixId ).') AND blog_id="'.$this->blog->filtered['id'].'"' );
		return true;
	}

}
?>