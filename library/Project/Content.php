<?php

/**
 * Статический класс
 * Хранит таблицу источников данных и их доступности для разных сайтов
 * а так-же методы для получения списков источников и объектов источников
 */
class Project_Content {

	/**
	* типы контент источников по содержимому - видео, статьи, товары, остальное (например Keywords)
	* @var const
	*/
	const VIDEO=1, ARTICLE=2, GOODS=3, OTHERS=4;

/*
User's Content:
- Articles
- Videos
- Keywords
Pure Content:
- Articles
- Videos
- RSS
- PLR Articles
- Yahoo answers
Monetized Content:
- Amazon
- Clickbank
- Ebay
- Commision Junction
- LinkShare
- ShopZilla
*/
	public static $source=array(
		'User\'s Content'=>array(
			array( 'type'=>Project_Content::ARTICLE, 
				'flg_source'=>1, 'title'=>'Articles', 'label'=>'articles', 'class'=>'Project_Content_Adapter_Articles', 'availability'=>array( Project_Sites::BF,  Project_Sites::NCSB, Project_Sites::NVSB ) ),
			array( 'type'=>Project_Content::VIDEO, 
				'flg_source'=>2, 'title'=>'Videos', 'label'=>'videos', 'class'=>'Project_Content_Adapter_Videos', 'availability'=>array( Project_Sites::BF,  Project_Sites::NCSB, Project_Sites::NVSB ) ),
		/*	array( 'type'=>Project_Content::OTHERS, 
				'flg_source'=>3, 'title'=>'Keywords', 'label'=>'keywords', 'class'=>'Project_Content_Adapter_Keywords', 'availability'=>array( Project_Sites::CNB ) ),*/
		),
		'Pure Content'=>array(
			array( 'type'=>Project_Content::ARTICLE, 
				'flg_source'=>4, 'title'=>'Pure Articles', 'label'=>'purearticles', 'class'=>'Project_Content_Adapter_Purearticles', 'availability'=>array( Project_Sites::BF,  Project_Sites::NCSB, Project_Sites::NVSB ) ),
			array( 'type'=>Project_Content::VIDEO, 
				'flg_source'=>5, 'title'=>'Pure Videos', 'label'=>'purevideos', 'class'=>'Project_Content_Adapter_Purevideos', 'availability'=>array( Project_Sites::BF,  Project_Sites::NCSB, Project_Sites::NVSB ) ),
			array( 'type'=>Project_Content::ARTICLE, 
				'flg_source'=>6, 'title'=>'RSS', 'label'=>'rss', 'class'=>'Project_Content_Adapter_Rss', 'availability'=>array( Project_Sites::BF,  Project_Sites::NCSB, Project_Sites::NVSB ) ),
			array( 'type'=>Project_Content::ARTICLE, 
				'flg_source'=>7, 'title'=>'PLR Articles', 'label'=>'plr', 'class'=>'Project_Content_Adapter_Plr', 'availability'=>array( Project_Sites::BF,  Project_Sites::NCSB, Project_Sites::NVSB ) ),
			array( 'type'=>Project_Content::ARTICLE,
				'flg_source'=>20, 'title'=>'Exclusive', 'label'=>'exclusive', 'class'=>'Project_Content_Adapter_Exclusive', 'availability'=>array(  /*Project_Sites::BF, Project_Sites::NCSB, Project_Sites::NVSB */) ),
	//		array( 'type'=>Project_Content::OTHERS,
	//			'flg_source'=>8, 'title'=>'Yahoo answers', 'label'=>'yahooanswers', 'class'=>'Project_Content_Adapter_Yahooanswers', 'availability'=>array( Project_Sites::BF,  Project_Sites::NCSB, Project_Sites::NVSB ) ),
		),
		'Monetized Content'=>array(
			array( 'type'=>Project_Content::OTHERS, 
				'flg_source'=>9, 'title'=>'Amazon', 'label'=>'amazon', 'class'=>'Project_Content_Adapter_Amazon', 'availability'=>array( Project_Sites::BF,  Project_Sites::NCSB, Project_Sites::NVSB ) ),
	//		array( 'type'=>Project_Content::OTHERS, 
	//			'flg_source'=>10, 'title'=>'Clickbank', 'label'=>'clickbank', 'class'=>'Project_Content_Adapter_Clickbank', 'availability'=>array( Project_Sites::BF,  Project_Sites::NCSB, Project_Sites::NVSB )),
	//		array( 'type'=>Project_Content::OTHERS, 
	//			'flg_source'=>11, 'title'=>'Ebay', 'label'=>'ebay', 'class'=>'Project_Content_Adapter_Ebay', 'availability'=>array( Project_Sites::BF,  Project_Sites::NCSB, Project_Sites::NVSB ) ),
	//		array( 'type'=>Project_Content::OTHERS,
	//			'flg_source'=>12, 'title'=>'Commision Junction', 'label'=>'cj', 'class'=>'Project_Content_Adapter_Cj' , 'availability'=>array( Project_Sites::BF,  Project_Sites::NCSB, Project_Sites::NVSB )),
	//		array( 'type'=>Project_Content::OTHERS,
	//			'flg_source'=>13, 'title'=>'LinkShare', 'label'=>'linkshare', 'class'=>'Project_Content_Adapter_Linkshare', 'availability'=>array( Project_Sites::BF,  Project_Sites::NCSB, Project_Sites::NVSB ) ),
	//		array( 'type'=>Project_Content::OTHERS, 
	//			'flg_source'=>14, 'title'=>'ShopZilla', 'label'=>'shopzilla', 'class'=>'Project_Content_Adapter_Shopzilla','availability'=>array( Project_Sites::BF,  Project_Sites::NCSB, Project_Sites::NVSB ) ),
		)

		/*
		* flg_source 102 - работает напрямую с Project_Content_Settings
		*/
	);
	
	public static function toLabelArray( $withRight=true ) {
		$arrRes=array();
		foreach( Project_Content::$source as $k=>$type ) {
			foreach( $type as $source ) {
				if( $withRight&&!Project_Acs_Source::haveAccess($source['flg_source'])){
					continue;
				}
				$arrRes[$source['label']]=$source;
			}
		}
		return $arrRes;
	}

	public static function toOptgroupSelect( $_intSiteType=0 ) {
		$arrRes=array();
		foreach( Project_Content::$source as $k=>$type ) {
			$arrRes[$k]=array();
			foreach( $type as $source ) {
				if( Core_Acs::haveAccess('CNM1.0')&&in_array($source['flg_source'],array(10,4,8,13)) ){ // Ограничения по контенту для гр. CNM1.0
					continue;
				}
				if(!Project_Acs_Source::haveAccess($source['flg_source'])){
					continue;
				}
				if ( !empty( $_intSiteType )&&!in_array( $_intSiteType, $source['availability'] ) ) {
					continue;
				}
				$arrRes[$k][$source['flg_source']]=$source['title'];
			}
			if(empty($arrRes[$k])){
				unset($arrRes[$k]);
			}
		}
		return $arrRes;
	}

	public static function factory( $_intId=0 ) {
		if ( empty( $_intId ) ) {
			throw new Exception( Core_Errors::DEV.'|Project_Content::factory( $_intId=0 ) - empty source id' );
		}
		foreach( Project_Content::$source as $type ) {
			foreach( $type as $source ) {
				if ( $_intId==$source['flg_source'] ) {
					return new $source['class'];
				}
			}
		}
		throw new Exception( Core_Errors::DEV.'|unknown source type' );
	}
}
?>