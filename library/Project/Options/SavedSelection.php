<?php 

class Project_Options_SavedSelection {
	
	public function getArticleById($id) {
		$article = Core_Sql::getRecord("SELECT * FROM hct_am_article WHERE user_id IN (".  Core_Acs::haveRightAccess( array('services_@_articleSavedSelection') ) .") AND id='{$id}'");
		return $article;
	}

	public function getArticleByCategory($category_id) {
		if ( is_numeric($category_id) ) {
			$category_id = " WHERE user_id IN (".  Core_Acs::haveRightAccess( array('services_@_articleSavedSelection') ) .") AND category_id = '{$category_id}' ";
		} else {
			$category_id = " WHERE user_id IN (".  Core_Acs::haveRightAccess( array('services_@_articleSavedSelection') ) .") ";
		}		
		$articles = Core_Sql::getAssoc("SELECT * FROM  hct_am_article {$category_id} ORDER BY RAND()");
		return $articles;
	}

	public function getArticleByCategoryRandom($category_id,$nb) {
		if ( is_numeric($category_id) ) {
			$category_id = " WHERE user_id IN (".  Core_Acs::haveRightAccess( array('services_@_articleSavedSelection') ) .") AND category_id = '{$category_id}' ";
		} else {
			$category_id = " WHERE user_id IN (".  Core_Acs::haveRightAccess( array('services_@_articleSavedSelection') ) .") ";
		}
		$articles = Core_Sql::getAssoc("SELECT * FROM  hct_am_article  {$category_id} ORDER BY RAND() LIMIT 0,$nb");
		foreach ($articles as &$article) {
			$article['encode_id'] = Project_Options_Encode::encode(intval($article['id']));
		}
		return $articles;
	}

	public function getArticleByKeywordAndCat($category_id,$keywords){
		$keywords = str_replace(" ","%",$keywords);
		if ( is_numeric($category_id) ) {
			$category_id = " AND a.category_id='{$category_id}' ";
		} else {
			$category_id = "";
		}
		$article = Core_Sql::getRecord("SELECT * FROM hct_am_article_keywords as k LEFT JOIN hct_am_article as a ON a.id=k.article_id {$category_id}  WHERE a.user_id IN (".  Core_Acs::haveRightAccess( array('services_@_articleSavedSelection') ) .") AND k.keywords LIKE('%{$keywords}%') AND a.title != '' AND a.body != '' ORDER BY RAND() LIMIT 1 ");
		return $article;
	}

	public function getArticleByKeywords($keywords) {
		$keywords = str_replace(" ","%",$keywords);
		$article = Core_Sql::getRecord("SELECT * FROM hct_am_article_keywords as k LEFT JOIN hct_am_article as a ON a.id=k.article_id  WHERE a.user_id IN (".  Core_Acs::haveRightAccess( array('services_@_articleSavedSelection') ) .") AND k.keywords LIKE('%{$keywords}%') AND a.title != '' AND a.body != '' ORDER BY RAND()  LIMIT 1 ");
		return $article;
	}

	public function getArticleBySource($category_id,$no,$source){
		$articles = array();
		if($source==1) {
			$source="PLR";
		}
		if($source==2) {
			$source="Free reprint rights";
		}
		if($source==3) {
			$source="Own";
		}
		if($source==4) {
			$source="Partners";
		}

		$rs = Core_Sql::getRecord("SELECT * FROM  hct_am_categories WHERE category='{$category_id}' ");
		if (is_array($rs)){
			$articles = Core_Sql::getAssoc("SELECT * FROM  hct_am_article WHERE user_id IN (".  Core_Acs::haveRightAccess( array('services_@_articleSavedSelection') ) .") AND category_id = '{$rs['id']}' AND source='{$source}' LIMIT 0,{$no}");
		}
		return $articles;
	}	
	
	
}

?>