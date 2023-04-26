<?php
class Project_Syndication_Sites_Backend extends Project_Sites_History_Backend {

	public function getList( &$arrRes ) {
		$_union=new Core_Sql_Qcrawler();
		if ( !empty( $this->_withType ) ) {
			$_arrTbl=array( $this->_withType=>Project_Sites::$tables[$this->_withType] );
		} else {
			$_arrTbl=Project_Sites::$tables;
		}
		foreach( $_arrTbl as $k=>$v ) {
			$_crawler=new Core_Sql_Qcrawler();
			switch( $k ) {
				case Project_Sites::BF:
					$_crawler->set_select( 'id, category_id, '.$k.' site_type, url, title main_keyword, (
						SELECT et.title 
						FROM bf_themes et 
						INNER JOIN bf_theme2blog_link et2s ON et2s.theme_id=et.id
						WHERE et2s.blog_id=site.id
					) template_name, "" profile_name, 0 profile_id, edited, catedit' );
				break;
/*				case Project_Sites::PSB: 
					$_crawler->set_select( 'id, category_id, '.$k.' site_type, url, main_keyword, (
						SELECT et.title 
						FROM es_templates et 
						INNER JOIN es_template2site et2s ON et2s.template_id=et.id
						WHERE et2s.site_id=site.id AND et2s.flg_type='.$k.'
					) template_name, (
						SELECT p.profile_name 
						FROM hct_profiles p 
						WHERE p.id=site.profile_id
					) profile_name, profile_id, edited, catedit' );
				break;
				case Project_Sites::CNB: 
					$_crawler->set_select( 'id, category_id, '.$k.' site_type, url, title main_keyword, (
						SELECT et.title 
						FROM es_templates et 
						INNER JOIN es_template2site et2s ON et2s.template_id=et.id
						WHERE et2s.site_id=site.id AND et2s.flg_type='.$k.'
					) template_name, (
						SELECT p.profile_name 
						FROM hct_profiles p 
						WHERE p.id=site.profile_id
					) profile_name, profile_id, edited, catedit' );
				break;*/
				case Project_Sites::NVSB: 
				case Project_Sites::NCSB: 
					$_crawler->set_select( 'id, category_id, '.$k.' site_type, url, main_keyword, (
						SELECT et.title 
						FROM es_templates et 
						INNER JOIN es_template2site et2s ON et2s.template_id=et.id
						WHERE et2s.site_id=site.id AND et2s.flg_type='.$k.'
					) template_name, "" profile_name, 0 profile_id, edited, catedit' );
				break;
			}
			if ( !empty( $this->_withoutCategories ) ) {
				$_crawler->set_where( 'category_id=0' );
			} else {
				$_crawler->set_select( '(SELECT title FROM category_blogfusion_tree WHERE id=site.category_id) category' );
			}
			if ( !empty( $this->_userId ) ) { // это для Project_Sites_History_Backend - т.к. нужны все сайты
				$_crawler->set_where( 'user_id='.$this->_userId );
			}
			$_crawler->set_from( $v.' site' );
			// это чтобы показывать только те сайты который в синдикейшене вообще надо это дело перевести на Core_Storadge чтобы весь метод не переназначать TODO!!! 18.10.2010
			$_crawler->set_where( 'id IN(SELECT site_id FROM '.Project_Syndication::$tables['sites'].' WHERE flg_type='.$k.')' );
			$_union->set_union_select( $_crawler );
		}
		$_union->set_order_sort( $this->_withOrder );
		if ( !empty( $this->_withPaging ) ) {
			$_union->set_paging( $this->_withPaging )->get_union_sql( $_strSql, $this->_paging );
		} elseif ( !$this->_onlyCount ) {
			$_union->gen_union_full( $_strSql );
		}
		$arrRes=Core_Sql::getAssoc( $_strSql );
		$this->init();
		return $this;
	}
}
?>