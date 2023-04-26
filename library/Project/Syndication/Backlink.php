<?php

 /**
 * шаринг сайтов
 */
class Project_Syndication_Backlink {

	/**
	* название таблицы с сайтами для бэклинков
	* @var string
	*/
	private static $_table='cs_bsites';

	/**
	* объект Core_Data
	* @var object
	*/
	private $_data;

	// используем при генерации плана
	public static $templates=array(
		'Read more on [CATEGORY]',
		'Get the latest news on [CATEGORY]',
		'Check our latest news on [CATEGORY]',
	);

	// cs_project.flg_backlinks
	public static $stat=array(
		'none'=>0,
		'category'=>1,
		'manual'=>2,
	);

	/**
	* конструктор
	* @return   void
	*/
	public function __construct( Core_Data $_data ) {
		$this->_data=$_data;
	}

	// $_arrBlink - array( array( site_id, flg_type ), ... )
	public static function set( $_intPrjId=0, $_arrBlink=array() ) {
		if ( empty( $_intPrjId ) ) {
			return false;
		}
		Core_Sql::setExec( 'DELETE FROM '.self::$_table.' WHERE project_id="'.$_intPrjId.'"' );
		$arrIns=array();
		if ( empty( $_arrBlink ) ) { // удаление бэклинков
			return true;
		}
		foreach( $_arrBlink as $v ) {
			$arrIns[]=array( 'project_id'=>$_intPrjId, 'site_id'=>$v['site_id'], 'flg_type'=>$v['flg_type'] );
		}
		return Core_Sql::setMassInsert( self::$_table, $arrIns );
	}

	// существующие бэклинки проекта выбранные вручную из сайтов пользователя
	public static function getBacklinks( $_intPrjId=0, &$arrRes ) {
		if ( empty( $_intPrjId ) ) {
			return false;
		}
//			IF(b.flg_type='.Project_Sites::PSB.',(SELECT main_keyword FROM '.Project_Sites::$tables[Project_Sites::PSB].' WHERE id=b.site_id),
		$arrRes=Core_Sql::getAssoc( 'SELECT b.site_id, b.flg_type, 
			IF(b.flg_type='.Project_Sites::NCSB.',(SELECT main_keyword FROM '.Project_Sites::$tables[Project_Sites::NCSB].' WHERE id=b.site_id),
			IF(b.flg_type='.Project_Sites::NVSB.',(SELECT main_keyword FROM '.Project_Sites::$tables[Project_Sites::NVSB].' WHERE id=b.site_id),
			IF(b.flg_type='.Project_Sites::BF.',(SELECT title FROM '.Project_Sites::$tables[Project_Sites::BF].' WHERE id=b.site_id),"no name"
		))) title FROM '.self::$_table.' b WHERE b.project_id="'.$_intPrjId.'"' );
		return true;
	}

	// сайты для синдикации (при генерации плана в Project_Syndication_Content_Plan)
	public function get( &$arrRes, $_intLimit=0 ) {
		if ( empty( $this->_data->filtered['flg_backlinks'] ) ) { // проект без бэклинков
			return false;
		}
		if ( $this->_data->filtered['flg_backlinks']==self::$stat['category'] ) { // from project category
//					(SELECT category_id, url, (SELECT title FROM '.Project_Sites::$category.' WHERE id=category_id) cat_name FROM '.Project_Sites::$tables[Project_Sites::PSB].')
//					UNION
			$arrRes=Core_Sql::getAssoc( '
				SELECT * FROM (
					(SELECT category_id, url, (SELECT title FROM '.Project_Sites::$category.' WHERE id=category_id) cat_name FROM '.Project_Sites::$tables[Project_Sites::NCSB].')
					UNION
					(SELECT category_id, url, (SELECT title FROM '.Project_Sites::$category.' WHERE id=category_id) cat_name FROM '.Project_Sites::$tables[Project_Sites::BF].')
				) result
				WHERE category_id IN(SELECT category_id FROM cs_project2category WHERE project_id="'.$this->_data->filtered['id'].'")
				ORDER BY RAND()
				LIMIT '.$_intLimit
			 );
		} elseif ( $this->_data->filtered['flg_backlinks']==self::$stat['manual'] ) { // manual select
//				(SELECT url, (SELECT title FROM '.Project_Sites::$category.' WHERE id=category_id) cat_name FROM '.Project_Sites::$tables[Project_Sites::PSB].'
//					WHERE id IN(SELECT site_id FROM '.self::$_table.' WHERE flg_type='.Project_Sites::PSB.' AND project_id="'.$this->_data->filtered['id'].'"))
//				UNION
			$arrRes=Core_Sql::getAssoc( '
				(SELECT url, (SELECT title FROM '.Project_Sites::$category.' WHERE id=category_id) cat_name FROM '.Project_Sites::$tables[Project_Sites::NCSB].'
					WHERE id IN(SELECT site_id FROM '.self::$_table.' WHERE flg_type='.Project_Sites::NCSB.' AND project_id="'.$this->_data->filtered['id'].'"))
				UNION
				(SELECT url, (SELECT title FROM '.Project_Sites::$category.' WHERE id=category_id) cat_name FROM '.Project_Sites::$tables[Project_Sites::BF].'
					WHERE id IN(SELECT site_id FROM '.self::$_table.' WHERE flg_type='.Project_Sites::BF.' AND project_id="'.$this->_data->filtered['id'].'"))
				ORDER BY RAND()
				LIMIT '.$_intLimit
			 );
		}
		$this->getFromText( $arrRes );
		return !empty( $arrRes );
	}

	// добавляем введённые вручную бэклинки - приводим к нужному формату
	private function getFromText( &$arrRes ) {
		$_arrSites=array_unique( Core_String::getInstance( $this->_data->filtered['backlinks'] )->separate() );
		if ( empty( $_arrSites ) ) {
			return;
		}
		Project_Syndication_Project_Category::get( $this->_data->filtered['id'], $_arrIds );
		$category=new Core_Category( 'Blog Fusion' );
		if ( !$category->byId( $_arrIds )->toSelect()->getList( $_arrCategories ) ) {
			return;
		}
		$_arrTmp=$_arrCategories;
		foreach( $_arrSites as $v ) {
			$arrRes[]=array(
				'url'=>$v,
				'cat_name'=>array_pop( $_arrTmp ),
			);
			if ( empty( $_arrTmp ) ) {
				$_arrTmp=$_arrCategories;
			}
		}
	}

	public static function checkLinkList( $_intFlg=0, $_strLinks='' ) {
		if ( empty( $_intFlg )||empty( $_strLinks ) ) {
			return '';
		}
		$_arrLinks=array_unique( Core_String::getInstance( $_strLinks )->separate() );
		foreach( $_arrLinks as $k=>$v ) {
			if ( !Zend_Uri::check( $v ) ) {
				unset( $_arrLinks[$k] );
			}
			// возможно надо будет делать через Zend_Uri_Http->getUri она и проверяет и возвращает валидный урл c http TODO!!!
		}
		if ( empty( $_arrLinks ) ) {
			return '';
		}
		return join( "\n", $_arrLinks );
	}
}
?>