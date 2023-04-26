<?php


/**
 * Blog posts remote management
 */
class Project_Wpress_Content_Posts extends Project_Wpress_Content_Abstract {

	private $_flgFrom=1;
	public static $from=array('self'=>1,'pub'=>2,'syn'=>3);

	public function setFrom( $_int ){
		$this->_flgFrom=$_int;
		return $this;
	}
	
	public function __construct() {
		$this->setTable( 'bf_ext_posts' )
			->setFields( array( 'id', 'ext_id', 'blog_id','flg_from', 'title', 'content', 'tags', 'added' ) )
			->setDefaultOrder();		
	}
	
	public function getList( &$mixRes ) {
		$_crawler=new Core_Sql_Qcrawler();
		$_crawler->set_select( 'c.*, p.content, p.tags, p.ext_id, p.id post_id' );
		$_crawler->set_select( '(SELECT COUNT(*) FROM bf_ext_comments com WHERE com.ext_post_id=c.link AND com.blog_id=c.site_id) comments' );
		$_crawler->set_from( 'es_content c' );
		$_crawler->set_from( 'LEFT JOIN bf_ext_posts p ON p.ext_id=c.link AND p.blog_id=c.site_id' );
		$_crawler->set_where( 'c.flg_type='.Project_Sites::BF.' AND c.site_id='.Core_Sql::fixInjection( $this->blog->filtered['id'] ) );
		$_crawler->set_where( 'c.flg_from IN('.Project_Sites_Content::$type['self'].', '.Project_Sites_Content::$type['publisher'].')' );
		if( !empty( $this->_byTitle ) ){
			$_crawler->set_where('c.title='.Core_Sql::fixInjection( $this->_byTitle ) );
		}
		if( !empty( $this->_withCategory ) ) {
			$ext_post_ids=Core_Sql::getField( 'SELECT ext_post_id FROM bf_ext_post2cat WHERE ext_cat_id='.Core_Sql::fixInjection( $this->_withCategory ) );
			if (!empty($ext_post_ids)) {
				$_crawler->set_where('c.link IN ('. join(',',$ext_post_ids) .')' );
			}
		}
		if( !empty( $this->_withIds ) ) {
			$_crawler->set_where( 'c.link IN ('.Core_Sql::fixInjection( $this->_withIds ).')' );
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
			if (!empty($this->_withCategory) && empty($ext_post_ids)) {
				return !empty( $mixRes );	 
			}
			$mixRes=Core_Sql::getAssoc( $_strSql );
			if ( $this->_withCategories ) {
				foreach ( $mixRes as &$i ) {
					$i['categories']= Core_Sql::getField( 'SELECT ext_cat_id FROM bf_ext_post2cat WHERE blog_id='.$i['site_id'].' AND ext_post_id='. $i['link'] );
				}
			}
		}
		$this->init();
		return !empty( $mixRes );
	}
	
	public function get( &$arrRes, $_intId=0 ) {}
	

	public function delete() {
		$this->data->setFilter();
		foreach( $this->data->filtered as $_array ){
			$extIds=$_array['ext_id'];
		}
		$this->withIds( $extIds )->getList($arr);
		if(empty($arr)){
			return true;
		}
		foreach( $arr as $_item ){
			$delIds[]=$_item['post_id'];
		}
		return $this->del( $delIds );
	}

	public function set() {
		// проверяем данные на ошибки
		$this->data->setFilter( array( 'stripslashes', 'trim', 'clear' ));
		foreach( $this->data->filtered as &$v ) {
			$v['flg_from']=$this->_flgFrom;
			if ( empty( $v['title'] ) ) {
				$this->errors[$v['id']]=true;
			}
		}
		if ( !empty( $this->errors ) ) {
			$this->errors[]='One post title is empty!';
			return false;
		}
		// добавление/изменение/удаление на серваке
		$_export=new Project_Sites_Adapter_Blogfusion_Export( $this->blog );
		if ( !$_export->post( $this ) ) {
			$this->errors[]='Can\' export post dat with errors: ';
			$_export->getErrors( $this->errors );
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
			if(!empty($v['post_id'])){
				$v['id']=$v['post_id'];
			}
			if ( !empty( $v['catIds'] ) ) {
				Project_Wpress_Content_Category::category2post( $this->blog->filtered['id'], $v );
			}
			$obj->setMask( $this->fields );
			Core_Sql::setInsertUpdate( $this->table, $obj->getValidCurrent( $v ) );
		}
		$this->del( $arrDel );
	}

	public function del( $_mixId=array() ) {
		if ( empty( $_mixId ) ) {
			return false;
		}
		Core_Sql::setExec( 'DELETE p,c,com FROM '.$this->table.' as p LEFT JOIN bf_ext_comments as com ON p.ext_id=com.ext_post_id LEFT JOIN bf_ext_post2cat as c ON p.ext_id=c.ext_post_id WHERE p.id IN('.Core_Sql::fixInjection( $_mixId ).') AND p.blog_id="'.$this->blog->filtered['id'].'"' );
		return true;
	}
}
?>