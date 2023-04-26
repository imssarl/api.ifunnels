<?php
class Project_Updater_Category extends Core_Updater_Abstract {

	private $obj;

	public function update( Core_Updater $obj ) {
		$this->obj=$obj;
		$this->obj->logger->info( 'start Project_Updater_Category' );
		$this->prepareDb();
		$this->importBlogfusionCategory();
		$this->obj->logger->info( 'end Project_Updater_Category' );
	}

	/*
	до
	CREATE TABLE `category_types` (
	  `id` int(11) unsigned NOT NULL auto_increment,
	  `flg_sort` tinyint(1) NOT NULL default '0',
	  `flg_user` tinyint(1) NOT NULL default '0',
	  `flg_typelink` tinyint(1) NOT NULL default '0',
	  `title` varchar(255) NOT NULL default ' ',
	  `description` text,
	  `added` int(11) NOT NULL default '0',
	  PRIMARY KEY  (`id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8;

	после
	CREATE TABLE `category_types` (
	  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
	  `flg_sort` tinyint(1) NOT NULL DEFAULT '0',
	  `flg_user` tinyint(1) NOT NULL DEFAULT '0',
	  `flg_typelink` tinyint(1) NOT NULL DEFAULT '0',
	  `type` varchar(255) NOT NULL DEFAULT '',
	  `storage` varchar(255) NOT NULL DEFAULT '',
	  `title` varchar(255) NOT NULL DEFAULT '',
	  `description` text,
	  `added` int(11) NOT NULL DEFAULT '0',
	  PRIMARY KEY (`id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8;
	*/
	private function prepareDb() {
		$this->obj->logger->info( 'start db fix' );
		// меняем category_types
		$fields = Core_Sql::getField("DESCRIBE category_types");
		if (!in_array('type', $fields) ){
			Core_Sql::setExec( 'ALTER TABLE category_types ADD COLUMN type varchar(255) NOT NULL DEFAULT "" AFTER flg_typelink' );
		}
		if (!in_array('storage', $fields) ){
			Core_Sql::setExec( 'ALTER TABLE category_types ADD COLUMN storage varchar(255) NOT NULL DEFAULT "" AFTER type' );
		}
		Core_Sql::setExec( 'ALTER TABLE category_types CHANGE COLUMN title title varchar(255) NOT NULL DEFAULT ""' );
		// добавим таблицу для хранения древовидных категорий
		Core_Sql::setExec( '
			CREATE TABLE IF NOT EXISTS category_blogfusion_tree (
			  id int(11) unsigned NOT NULL AUTO_INCREMENT,
			  pid int(11) unsigned NOT NULL DEFAULT "0",
			  level int(11) unsigned NOT NULL DEFAULT "0",
			  user_id int(11) unsigned NOT NULL DEFAULT "0",
			  priority smallint(3) unsigned NOT NULL DEFAULT "100",
			  title varchar(255) NOT NULL DEFAULT "",
			  PRIMARY KEY (id)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;
		' );
		$this->updateSettingInDb();
	}

	private function updateSettingInDb() {
		Core_Sql::setUpdate( 'category_types', array(
			'title'=>'Article Manager Source',
			'type'=>'simple',
			'description'=>'owner of users article in CNM Article Manager module'
		), 'title' );
		Core_Sql::setUpdate( 'category_types', array(
			'title'=>'Article Manager',
			'type'=>'flagged',
			'description'=>'users category for CNM Article Manager module'
		), 'title' );
		Core_Sql::setInsert( 'category_types', array(
			'title'=>'Blog Fusion',
			'storage'=>'category_blogfusion_tree',
			'type'=>'nested',
			'description'=>'blogs category for CNM Project_Wpress_Category'
		) );
		$this->obj->logger->info( 'end db fix' );
	}

	private function importBlogfusionCategory() {
		$this->obj->logger->info( 'start importBlogfusionCategory' );
		if ( !$this->getContent( $html, 'http://ezinearticles.com/' ) ) {
			$this->obj->logger->err( 'getContent return no data from http://ezinearticles.com/' );
		} else {
			preg_match_all('/<div[^>]*><a class="ctitle"[^>]*>([^>]*)<.*<div class="clist"[^>]*>(.*)<\/div>/im', $html, $matches);
			foreach( $matches[1] as $k=>$v ) {
				preg_match_all('/<a href=\"[^>]*>([^>]*)<\/a>/im', $matches[2][$k], $submatches);
				$_firstLevel[]=$v;
				$_subLevel[$v]=$submatches[1];
			}
			$_model=new Core_Category( 'Blog Fusion' );
			if ( !$_model->setPid()->setData( $_firstLevel )->setCategory() ) {
				$_model
					->getEntered( $out['firstLevel'] )
					->getErrors( $out['arrError'] );
				$this->obj->logger->info( print_r( $out, true ) );
			}
			$_model->getLevel( $arrCats );
			foreach( $arrCats as $v ) {
				if ( !$_model->setPid( $v['id'] )->setData( $_subLevel[$v['title']] )->setCategory() ) {
					$_model
						->getEntered( $out['subLevel'] )
						->getErrors( $out['arrError'] );
					$this->obj->logger->info( print_r( $out, true ) );
				}
			}
		}
		$this->obj->logger->info( 'end importBlogfusionCategory' );
	}

	private function getContent( &$strRes, $_strUrl='' ) {
		if( !function_exists("curl_init")||empty( $_strUrl ) ) {
			return false;
		}
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $_strUrl );
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.9.1.3) Gecko/20090824 Firefox/3.5.3' );
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			"Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
			"Cache-Control: max-age=0",
			"Connection: keep-alive",
			"Keep-Alive: 300",
			"Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7",
			"Accept-Language: en-us,en;q=0.5",
		));
		curl_setopt($ch, CURLOPT_REFERER, 'http://www.google.com' );
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		$strRes = curl_exec($ch);
		curl_close ($ch);
		return !empty( $strRes );
	}
}
?>