<?php
/**
 * WorkHorse Framework
 *
 * @category Project
 * @package Project_Publishing
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2005-2010, web2innovation
 * @author Rodion Konnov <kindzadza@mail.ru>
 * @date 02.02.2010
 * @version 0.1
 */


/**
 * Arrange rss content
 *
 * @category Project
 * @package Project_Wpress
 * @copyright Copyright (c) 2005-2010, web2innovation
 * @license http://opensource.org/licenses/ MIT License
 */
class Project_Publishing_Blogfusion_Arrange_Rss {

	public static function run( Zend_Log $logger, Core_Data $data ) {
		$_obj=new Project_Publishing_Blogfusion_Arrange_Rss( $logger, $data ); // опить же экономим память
		$_obj->update();
	}

	private $_logger;
	private $_project;
	private $_responce; // тут содержится полученный рсс
	private $_content; // тут контент из текущего рсс
	private $_blogs; // список блогов в которые постим
	private $_rssblog; // объект доступа

	public function __construct( Zend_Log $logger, Core_Data $data ) {
		$this->_logger=$logger;
		$this->_project=&$data->filtered;
		$this->_rssblog=new Project_Publishing_Blogfusion_Rss( $data );
	}

	private function getRss( $strUrl='' ) {
		if ( empty( $strUrl ) ) {
			return false;
		}
		$curl=Core_Curl::getInstance();
		if ( !$curl->getContent( $strUrl ) ) {
			return false;
		}
		$this->_responce=$curl->getResponce();
		$rss=@simplexml_load_string( $this->_responce );
		if ( $rss===false ) {
			return false;
		}
		$this->_content=array();
		foreach( $rss->channel->item as $item ) {
			$this->_content[]=array(
				'title'=> str_replace( '"', "'", str_replace( "'", "`", $item->title ) ),
				'content'=> str_replace('"',"'", $item->description.(empty( $this->_project['flg_rss_url'] )? "&nbsp;<a href='".$item->link."'>more</a>":'')),
				'tags'=> $this->_project['tags']
			);
		}
		return $this->getLimitedContent();
	}

	private function getLimitedContent() {
		if ( empty( $this->_content ) ) {
			return false;
		}
		if ( empty( $this->_project['rss_limit'] )||!is_numeric( $this->_project['rss_limit'] )||count( $this->_content )<=$this->_project['rss_limit'] ) {
			return true; // нет лимита на количество контента с одного рсс
		}
		$i=$this->_project['rss_limit'];
		while( $i>0 ) {
			$_intKey=array_rand( $this->_content, 1 );
			$_arrLimited[]=$this->_content[$_intKey];
			unSet( $this->_content[$_intKey] );
			$i--;
		}
		$this->_content=$_arrLimited;
		return true;
	}

	// тут начинаем работу с проектом
	public function update() {
		$_arrFeeds=array_unique( Core_String::getInstance( $this->_project['feeds'] )->separate( '\n' ) );
		if ( empty( $_arrFeeds )||!$this->_rssblog->getList( $this->_blogs ) ) {
			return;
		}
		foreach( $_arrFeeds as $v ) {
			if ( !$this->getRss( $v ) ) {
				$this->_logger->info( 'Not correct rss link (see "'.$strUrl.'")' );
				continue;
			}
			foreach( $this->_content as $v ) {
				$_intKey=array_rand( $this->_blogs, 1 );
				$this->_blogs[$_intKey]['posts'][]=$v;
			}
		}
		$this->publicate();
	}

	private function publicate() {
		$post = new Project_Wpress_Content_Posts();
		foreach( $this->_blogs as $k=>$v ) {
			$arrCache = Core_Sql::getField('SELECT title FROM pub_rsscache WHERE title IN('.Core_Sql::fixInjection( $v['posts']['title'] ).')' );
			foreach ( $v['posts'] as $key=>$item ) {
				$item['catIds'] = ( $v['ext_category_id'] != 0 ) ? array( $v['ext_category_id'] ) : array();
				if ( in_array( trim($item['title']), $arrCache) ) {
					unset( $this->_blogs[$k]['posts'][$key] );
				}
			}
			if ( empty( $this->_blogs[$k]['posts'] ) ) {
				continue;
			}
			if( !$post->setBlogById( $v['site_id'] ) ){
				continue;
			}
			$post->setData( $this->_blogs[$k]['posts'] );
			$this->_logger->info( 'start rss to post');
			if ( $post->set() ) {
				foreach ( $post->data->filtered as $i ) {
					if ( $i['ext_id'] == 0 ) {
						continue;
					}
					Core_Sql::setInsert('pub_rsscache', array(
						'project_id' 	=> $v['project_id'],
						'site_id'		=> $v['site_id'],
						'ext_post_id'	=> $i['ext_id'],
						'title'			=> $i['title']
					) );
					$this->_logger->info( 'publicated post ext_id : ['.$i['ext_id'].']' );
				}
			}
			$this->_logger->info( 'end rss to post');
		}

		if( $this->_project['rss_new'] != 0 ){
			Core_Sql::setUpdate('pub_project', array('id'=>$this->_project['id'], 'start' => ( $this->_project['start'] + ( 86400 *  $this->_project['rss_new'] ) ) ) );
			$this->_logger->info( 'project : ['.$this->_project['id'].'] updateed start time' );
		} else {
			Core_Sql::setUpdate('pub_project', array('id'=>$this->_project['id'], 'flg_status' => 2 ) );
			$this->_logger->info( 'project : ['.$this->_project['id'].'] completed' );
		}
	}
}
?>