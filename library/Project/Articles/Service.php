<?php


/**
 * логика сервисов которые дёргаются с внешних сайтов
 */
class Project_Articles_Service {
	
	public static function getSavedSelection( &$arrPg ) {
		Zend_Registry::get( 'objUser' )->getId( $user_id );
		$crawler = new Core_Sql_Qcrawler();
		$crawler->set_select( '*' );
		$crawler->set_from( 'hct_am_savedcode' );
		$crawler->set_where( "user_id = {$user_id} " );
		$sql = $crawler->set_paging( $arrPg )->get_sql( $strSql, $arrPg );
		$count = $crawler->get_result_counter( $strSql );
		$result = Core_Sql::getAssoc( $sql );
		return count( $result ) ? $result : false ;
	}
	
	public static function deleteSavedSelectionById( $id ) {
		Zend_Registry::get( 'objUser' )->getId( $user_id );
		$result = Core_Sql::setExec( "DELETE FROM hct_am_savedcode WHERE id = '{$id}' AND user_id = '{$user_id}' " );
		return $result;
	}
	
	public static function  getSavedSelectionById( $id ) {
		$item = Core_Sql::getRecord("SELECT * FROM hct_am_savedcode WHERE id = {$id}");
		$item['code'] = html_entity_decode($item['code']);
		return $item;
	}
	
	public static function  setSavedSelectionById( $id, $arrData ) {
		
		$data = array(
			"id" => $id,
			"name" => htmlentities($arrData['name']),
			"description" => htmlentities($arrData['description']),
			"code" => htmlentities($arrData['code'])
		);
		$result = Core_Sql::setInsertUpdate("hct_am_savedcode", $data, "id");
		return $result ? true : false ;
	}	

}
?>