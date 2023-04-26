<?php

 /**
 * управление категориями
 */
class Project_Syndication_Project_Category {

	public static function set( $_intPrjId=0, $_arrCats=array() ) {
		if ( empty( $_intPrjId ) ) {
			return false;
		}
		Core_Sql::setExec( 'DELETE FROM '.Project_Syndication::$tables['project2category'].' WHERE project_id="'.$_intPrjId.'"' );
		$arrIns=array();
		if ( empty( $_arrCats ) ) { // удаление категорий (при удалении проекта только)
			return true;
		}
		foreach( $_arrCats as $v ) {
			$arrIns[]=array( 'project_id'=>$_intPrjId, 'category_id'=>$v );
		}
		return Core_Sql::setMassInsert( Project_Syndication::$tables['project2category'], $arrIns );
	}

	public static function get( $_intPrjId=0, &$arrRes ) {
		if ( empty( $_intPrjId ) ) {
			return false;
		}
		$arrRes=Core_Sql::getField( 'SELECT category_id FROM '.Project_Syndication::$tables['project2category'].' WHERE project_id="'.$_intPrjId.'"' );
		return true;
	}

	public static function getWithTitle( $_intPrjId=0, &$arrRes ) {
		$arrRes=Core_Sql::getAssoc( '
			SELECT id, title 
			FROM '.Project_Sites::$category.' 
			WHERE id IN(
				SELECT category_id 
				FROM '.Project_Syndication::$tables['project2category'].' 
				WHERE project_id="'.$_intPrjId.'"
			)
		' );
	}

	public static function getNoempty() {
		$category = new Core_Category( 'Blog Fusion' );
		$category->getTree( $arrTree );
		// количество расшареных сайтов в категории
		$_sitesModel=new Project_Syndication_Sites();
		$_sitesModel->sitesInCat($arrSiteInCat);
		// убираем категории в которых нет сайтов
		$_idCategories = array_keys($arrSiteInCat);
		foreach ( $arrTree as $_keyParent=>$_parent ){
			foreach ( $_parent['node'] as $_keyChild=>$_node ){
				if ( !in_array($_node['id'],$_idCategories) ){
					unset($arrTree[$_keyParent]['node'][$_keyChild]);
				} else {
					$arrTree[$_keyParent]['node'][$_keyChild]['sites_num'] = $arrSiteInCat[$_node['id']];
				}
			}
			if ( empty($arrTree[$_keyParent]['node'])){
				unset($arrTree[$_keyParent]);
			}
		}
		return $arrTree;
	}
}
?>