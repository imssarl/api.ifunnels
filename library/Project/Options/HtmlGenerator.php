<?php

class Project_Options_HtmlGenerator {
	private $_smarty;
	private $config;
	private $_model;
	private $templatePath = "";
	
	public function init($params){
		
		$this->config=Zend_Registry::get( 'config' );
		$this->templatePath = $this->config->path->relative->source.'advanced_options'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR;
		switch ($params['type_view']) {
			case "showarticles": // completed
				$this->_model = new Project_Options_SavedSelection();
				$this->viewSavedSelection($params);
				break;
				
			case "showarticlesnippets": // completed
				$this->_model = new Project_Options_SavedSelection();
				$this->viewSavedSelectionSnipets($params);
				break;	
							
			case "showvideo": // completed
				$this->viewVideo($params);
				break;

			default:
				break;
		}
	}

	/**
	 * Вывод видео
	 *
	 * @param array $params - $_GET
	 * @return false - если пустой id для видео
	 */
	private  function viewVideo($params = array()){

		$id = intval(Project_Options_Encode::decode($params['id']));
		$title = (isset($params['title']) && $params['title'] == 1)? 1:0;
		if (empty($id)){
			return false;
		}
		Project_Embed::getInstance()->withRights(array('services_@_video_view'))->onlyOne( $id )->withIds()->getList( $video );
		$templateData = array(
			"title" => $video['title'],
			"video" => $video['body'],
			"display_title" => $title,
		);
		$this->show( $templateData, 'view_video.tpl' );
	}

	
	/**
	 * Вывод статей для опции Saved Article Selection:
	 *
	 * @param array $params - $_GET
	 */
	private  function viewSavedSelection($params = array()) {
		$id = null;
		$category_id = null;
		$defcategory = null;

		if (isset($params['id']) && !is_numeric($params['id'])) {
			$id = intval(Project_Options_Encode::decode($params['id']));
		} elseif(isset($params['id'])) {
			$id = intval($params['id']);
		}
		if (isset($params['category_id']) && !is_numeric($params['category_id'])) {
			$category_id =  Project_Options_Encode::decode($params['category_id']) ;
		} elseif(isset($params['category_id'])) {
			$category_id =  $params['category_id'] ;
		}

		if (isset($params['defcategory']) && !is_numeric($params['defcategory'])) {
			$defcategory =  Project_Options_Encode::decode($params['defcategory']) ;
		} elseif(isset($params['defcategory'])) {
			$defcategory =  $params['defcategory'] ;
		}

		if (!empty($id)){ // вывод одной статьи

			$article = $this->_model->getArticleById($id);
			$templateData = array(
				"type_view" => "one",
				"article" => $article
			);
		}

		if (is_numeric($category_id)) { // вывод статей для одной категории
			$articles = $this->_model->getArticleByCategory($category_id);
			$templateData = array(
				"type_view" => "multi",
				"articles"  => $articles
			);
		}

		if ($category_id && isset($params['nb'])) { // вывод нескольких статей для одной категории рандомом
			$articles = $this->_model->getArticleByCategoryRandom($category_id,intval($params["nb"]));
			$templateData = array(
				"type_view" => "multi",
				"articles"  => $articles
			);
		}

		if ($defcategory && isset($params['keyword'])) { // вывод статьи для одной категории по ключевым словам
			$article = $this->_model->getArticleByKeywordAndCat($defcategory,$params["keyword"]);
			$templateData = array(
				"type_view" => "one",
				"article"  => $article
			);
		} elseif(isset($params['keyword'])){ // вывод статьи по ключевым словам
			$article = $this->_model->getArticleByKeywords($params["keyword"]);
			unset($article['body']);
			$templateData = array(
				"type_view" => "one",
				"article"  => $article,
				"no_body"  => 1
			);
		}

		$this->show( $templateData, 'view_article.tpl' );
	}


	/**
	 * Вывод статей для опции Saved Article Selection: по сниппетам
	 *
	 * @param unknown_type $params - $_GET
	 */
	private function viewSavedSelectionSnipets($params = array()){
		if (isset($params['category_id']) && !is_numeric($params['category_id'])) {
			$category_id =  Project_Options_Encode::decode($params['category_id']) ;
		} elseif(isset($params['category_id'])) {
			$category_id =  $params['category_id'] ;
		}

		if ($category_id && isset($params['nb']) && !isset($params['source'])) {
			$articles = $this->_model->getArticleByCategoryRandom($category_id,intval($params["nb"]));
			$templateData = array(
				"type_view" => "multi",
				"articles" 	=> $articles,
				"path" 		=> $this->config->engine->project_domain
			);
		}

		if ($category_id && isset($params['nb']) && isset($params['source'])) {
			$articles = $this->_model->getArticleBySource($category_id,intval($params["nb"]), intval($params['source']));
			$templateData = array(
				"type_view" => "multi",
				"articles" 	=> $articles,
				"path" 		=> $this->config->engine->project_domain
			);
		}

		$this->show( $templateData, 'view_article_snippets.tpl' );
	}

	private function show( $_arrData, $_strTpl ) {
		while( @ob_end_clean() );
		Core_View::factory( Core_View::$type['one'] )
			->setTemplate( $this->templatePath.$_strTpl )
			->setHash( $_arrData )
			->parse()
			->show();
		exit;
	}
}
?>