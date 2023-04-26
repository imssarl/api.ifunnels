<?php
class Project_Updater_Articles extends Core_Updater_Abstract {

	public function update( Core_Updater $obj ) {
		$obj->logger->info( 'start Project_Updater_Articles' );
		$this->dbPrepare();
		if ( !$this->reassignCategory() ) {
			$obj->logger->err( 'reassignCategory false' );
			return;
		}
		$this->idsTransfer();
		$this->dbClean();
		$obj->logger->info( 'end Project_Updater_Articles' );
	}

	/**
	 * добавляем поля и индексы
	 * чистим hct_am_article от статей которые вне категорий
	 * переносим инфу из status в flg_status
	 */
	private function dbPrepare() {
		Core_Sql::setExec("DROP TABLE IF EXISTS category_cat2flag");
		Core_Sql::setExec("CREATE TABLE `category_cat2flag` ( `cat_id` int(11) unsigned NOT NULL DEFAULT '0', `flag_id` int(11) unsigned NOT NULL DEFAULT '0', PRIMARY KEY (`cat_id`,`flag_id`)) ENGINE=MyISAM DEFAULT CHARSET=utf8");

		Core_Sql::setExec("DROP TABLE IF EXISTS category_category");
		Core_Sql::setExec("CREATE TABLE `category_category` ( `id` int(11) unsigned NOT NULL AUTO_INCREMENT, `user_id` int(11) unsigned NOT NULL DEFAULT '0', `type_id` int(11) unsigned NOT NULL DEFAULT '0', `priority` smallint(3) unsigned NOT NULL DEFAULT '0', `title` varchar(255) NOT NULL DEFAULT ' ', PRIMARY KEY (`id`), KEY `user_id` (`user_id`), KEY `type_id` (`type_id`), KEY `user_type_idx` (`user_id`,`type_id`)) ENGINE=MyISAM DEFAULT CHARSET=utf8");
		
		Core_Sql::setExec("DROP TABLE IF EXISTS category_flags");
		Core_Sql::setExec("CREATE TABLE `category_flags` ( `id` int(11) unsigned NOT NULL AUTO_INCREMENT, `type_id` int(11) unsigned NOT NULL DEFAULT '0', `title` varchar(255) NOT NULL DEFAULT ' ', `description` longtext, `added` int(11) NOT NULL DEFAULT '0', PRIMARY KEY (`id`), KEY `type_id` (`type_id`)) ENGINE=MyISAM DEFAULT CHARSET=utf8");
				
		Core_Sql::setExec("DROP TABLE IF EXISTS category_links");
		Core_Sql::setExec("CREATE TABLE `category_links` ( `item_id` int(11) unsigned NOT NULL DEFAULT '0', `cat_id` int(11) unsigned NOT NULL DEFAULT '0', `type_id` int(11) unsigned NOT NULL DEFAULT '0', `added` int(11) unsigned NOT NULL DEFAULT '0', PRIMARY KEY (`item_id`,`cat_id`,`type_id`)) ENGINE=MyISAM DEFAULT CHARSET=utf8");
		
		Core_Sql::setExec("DROP TABLE IF EXISTS category_types");
		Core_Sql::setExec("CREATE TABLE `category_types` ( `id` int(11) unsigned NOT NULL AUTO_INCREMENT, `flg_sort` tinyint(1) NOT NULL DEFAULT '0', `flg_user` tinyint(1) NOT NULL DEFAULT '0', `flg_typelink` tinyint(1) NOT NULL DEFAULT '0', `title` varchar(255) NOT NULL DEFAULT ' ', `description` text, `added` int(11) NOT NULL DEFAULT '0', PRIMARY KEY (`id`)) ENGINE=MyISAM DEFAULT CHARSET=utf8");

		$_arrTypes=array(
			array( 'flg_user'=>0, 'title'=>'Article Manager Source', 'description'=>'owner of users article in CNM Article Manager module (simple)', 'added'=>time() ),
			array( 'flg_user'=>1, 'title'=>'Article Manager', 'description'=>'users category for CNM Article Manager module (flagged)', 'added'=>time() ),
		);
		Core_Sql::setMassInsert( 'category_types', $_arrTypes );

		$_arrFlags=array(
			array( 'type_id'=>2, 'title'=>'active', 'description'=>'if inactive - category shows only in category management page', 'added'=>time() ),
		);
		Core_Sql::setMassInsert( 'category_flags', $_arrFlags );

		$_arrCats=array(
			array( 'type_id'=>1, 'title'=>'PLR' ),
			array( 'type_id'=>1, 'title'=>'Free Reprint rights' ),
			array( 'type_id'=>1, 'title'=>'Own' ),
			array( 'type_id'=>1, 'title'=>'Partner' ),
		);
		Core_Sql::setMassInsert( 'category_category', $_arrCats );

		Core_Sql::setExec( '
			ALTER TABLE `hct_am_article`
			  ADD COLUMN `source_id` int(11) unsigned NOT NULL DEFAULT 0 AFTER `category_id`,
			  ADD COLUMN `flg_status` tinyint(1) unsigned NOT NULL DEFAULT 0 AFTER `source_id`
		' );
		Core_Sql::setExec( 'ALTER TABLE hct_am_article ADD INDEX (category_id)' );
		// kill articles with -1 or 0 category_id
		Core_Sql::setExec( 'DELETE FROM hct_am_article WHERE category_id IN(-1,0) OR user_id=0' );
		// reassign status to flg_status
		Core_Sql::setExec( 'UPDATE hct_am_article SET flg_status=1 WHERE status="Active"' );
	}

	/**
	 * переназначить source_id из hct_am_article.source в source_id через систему Core_Category
	 * категории перевести из hct_am_categories в систему Core_Category
	 * переназначить category_id
	 */
	private function reassignCategory() {
		// reassign source to source_id
		$source=new Core_Category( 'Article Manager Source' );
		$source->toSelect()->get( $_arrSource, $_arrTmp );
		foreach( $_arrSource as $k=>$v ) {
			Core_Sql::setExec( 'UPDATE hct_am_article SET source_id="'.$k.'" WHERE source="'.$v.'"' );
		}
		// move all category from hct_am_categories to Core_Category
		$_intTypeId=Core_Sql::getCell( 'SELECT id FROM category_types WHERE title="Article Manager"' );
		$_arrOldCats=Core_Sql::getAssoc( 'SELECT id, category, status, user_id FROM hct_am_categories' );
		foreach( $_arrOldCats as $v ) {
			$_intId=Core_Sql::setInsert( 'category_category', array( 
				'user_id'=>$v['user_id'],
				'type_id'=>$_intTypeId,
				'title'=>$v['category'],
			) );
			if ( empty( $_intId ) ) {
				continue;
			}
			if ( $v['status']=='Active' ) {
				Core_Sql::setInsert( 'category_cat2flag', array( 'cat_id'=>$_intId, 'flag_id'=>1 ) );
			}
			Core_Sql::setExec( 'UPDATE hct_am_article SET category_id="'.$_intId.'" WHERE category_id="'.$v['id'].'"' );
		}
		return true;
	}

	/**
	 * Перенос линков из таблицы hct_ncsbsites в новую таб. hct_articles_links
	 *
	 */
	private function idsTransfer(){
		$arrArticles = Core_Sql::getAssoc("SELECT id,article_ids FROM hct_ncsbsites");
		foreach ($arrArticles as $value) {
			$tempArticlesId = explode(",",$value['article_ids']);
			foreach ($tempArticlesId as $id) {
				if (!empty($value['article_ids']))
				$arrInsert[] = array(
				"site_id" 		=> $value['id'],
				"site_type" 	=> Project_Sites::NCSB,
				"article_id"	=> $id
				);
			}
		}
		if (!empty($arrInsert))  {
			Core_sql::setMassInsert("hct_articles_links",$arrInsert);
		}
	}

	private function dbClean() {
		// kill source & status fields
		Core_Sql::setExec( 'ALTER TABLE hct_am_article DROP COLUMN status' );
		Core_Sql::setExec( 'ALTER TABLE hct_am_article DROP COLUMN source' );
	}
}
?>