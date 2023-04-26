<?php


/**
 * Blog category remote management
 */
class Project_Wpress_Content_Category extends Project_Wpress_Content_Abstract {

	public function __construct() {
		$this->setTable( 'bf_ext_category' )
			->setFields( array( 'id', 'ext_id', 'blog_id', 'flg_default', 'title', 'added' ) )
			->setDefaultOrder();
	}

	public function getList( &$mixRes ) {
		$_crawler=new Core_Sql_Qcrawler();
		if( $this->_toJs ){
			$_crawler->set_select( 'c.id,c.title,c.blog_id,c.ext_id' );
		} else {
			$_crawler->set_select( 'c.*' );
		}
		$_crawler->set_from( $this->table.' c' );
		if ( !$this->_all ) {
			$_crawler->set_where( 'c.blog_id='.Core_Sql::fixInjection( $this->blog->filtered['id'] ) );
		}
		if( !empty( $this->_byTitle ) ){
			$_crawler->set_where('c.title='.Core_Sql::fixInjection( $this->_byTitle ) );
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

	// заглушка для абстрактного метода, пока тут не нужен
	public function get( &$arrRes, $_intId=0 ) {}

	public function set() {
		// проверяем данные на ошибки
		$this->data->setFilter();
		foreach( $this->data->filtered as $v ) {
			if ( !$this->checkTitle( $v ) ) {
				if ( !empty( $v['id'] ) ) {
					$this->errors[$v['id']]=true;
				}
			}
		}
		if ( !empty( $this->errors ) ) {
			return false;
		}
		// добавление/изменение/удаление на серваке
		$_export=new Project_Sites_Adapter_Blogfusion_Export( $this->blog );
		// id, ext_id, flg_default, title приходят с формы
		if ( !$_export->category( $this ) ) {
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

	/**
	 * добавление линков
	 *
	 * @param int $blogId 
	 * @param array $arrData 
	 */
	public static function category2post( $blogId = 0, $arrData = array() ) {
		if( empty($arrData['ext_id']) ){
			return false;
		}
		Core_Sql::setExec( 'DELETE FROM bf_ext_post2cat WHERE ext_post_id = '. $arrData['ext_id'] );
		$link2category = array();
		foreach ( $arrData['catIds'] as $c ) {
			$link2category = array( 'blog_id' => $blogId, 'ext_cat_id' => $c, 'ext_post_id' => $arrData['ext_id'] );
			Core_Sql::setInsertUpdate( 'bf_ext_post2cat', $link2category );
		}
	}

	public function del( $_mixId=array() ) {
		if ( empty( $_mixId ) ) {
			return false;
		}
		Core_Sql::setExec( 'DELETE c,p FROM '.$this->table.' as c  LEFT JOIN bf_ext_post2cat as p ON c.ext_id = p.ext_cat_id WHERE c.id IN('.Core_Sql::fixInjection( $_mixId ).') AND c.blog_id="'.$this->blog->filtered['id'].'"' );
		return true;
	}

	// только надо сразу взять все ктегории блога чтобы не дёргать для каждой базу
	private function checkTitle( $_arr=array() ) {
		if ( empty( $_arr['title'] ) ) {
			return false;
		}
		if ( $this->byTitle( $_arr['title'] )->getList( $_arrTmp ) ) {
			if ( !empty( $_arr['id'] )&&$_arr['id']!=$_arrTmp['id'] ) {
				return false;
			}
			if ( empty( $_arr['id'] ) ) {
				return false;
			}
		}
		return true;
	}
}
?>