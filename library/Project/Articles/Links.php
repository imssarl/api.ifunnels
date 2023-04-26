<?php


/**
 * линковка статей к внешним сайтам
 */
class Project_Articles_Links {
	const Type_NCSB = 1;
	const Type_CNB = 2;
	const Type_PSB = 3;
	const Type_NVSB = 4;
	
	/**
	 * Сохранение выбранных статей
	 *
	 * @param array $arrIds - массив ids статей
	 * @param int $siteId - ID - сайта
	 * @param string $siteType - тип сайта, по умолчанию ncsb
	 * @return bool
	 */
	public static function saveIds( $arrIds, $siteId, $siteType = Ptoject_Sites::NCSB ) {
		if ( empty($arrIds) || empty($siteId) ) {
			return false;
		}
		
		if ( !Core_Sql::setExec("DELETE FROM hct_articles_links WHERE site_id = $siteId AND site_type = $siteType ") ) {
			return false;
		}
		$arrData = array();
		foreach ( $arrIds as $id ){
			$arrData[] = array(
				"site_id" 	=> $siteId,
				"article_id" 	=> $id,
				"site_type"	=> $siteType
			);
		}
		if ( !Core_Sql::setMassInsert('hct_articles_links', $arrData) ) {
			return false;
		}
		return true;
	}
	
	/**
	 * Удаление статей для сайта
	 *
	 * @param int $siteId - ID - сайта
	 * @param string $siteType - тип сайта, по умолчанию ncsb
	 * @return bool
	 */
	public static function delete(  $siteId, $siteType = Ptoject_Sites::NCSB ) {
		if ( !Core_Sql::setExec("DELETE FROM hct_articles_links WHERE site_id = $siteId AND site_type = $siteType ") ) {
			return false;
		}
		return true;
	}	

	public static function getIds( &$arrRes, $siteId, $siteType = Ptoject_Sites::NCSB ) {
		if ( empty( $siteId ) ) {
			return false;
		}
		$links = Core_Sql::getField("SELECT article_id FROM hct_articles_links WHERE site_id = {$siteId} AND site_type = {$siteType} ");
		if ( empty( $links ) ) {
			return false;
		}		
		$article = new Project_Articles();
		if ( !$article->withIds( $links )->toJs()->getList( $arrRes ) ){
			return false;
		}
		return true;
	}
	
	/**
	 * Обновление статей на сайте пользователя 
	 *
	 * @param array $arrId - Id статей
	 */
	public function updateArticles( $arrId ){
		// @TODO - update articles
	}
}
?>