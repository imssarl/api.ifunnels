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
 * Arrange local contents (Article&Video)
 *
 * @category Project
 * @package Project_Wpress
 * @copyright Copyright (c) 2005-2010, web2innovation
 * @license http://opensource.org/licenses/ MIT License
 */
class Project_Publishing_Blogfusion_Arrange_Content {

	public static function run( Zend_Log $logger, Core_Data $data ) {
		$_obj=new Project_Publishing_Blogfusion_Arrange_Content( $logger, $data ); // опить же экономим память
		$_obj->update();
	}

	private $_logger;
	private $_project;
	private $_content = false;

	public function __construct( Zend_Log $logger, Core_Data $data ) {
		$this->_logger=$logger;
		$this->_project=$data;
	}
	
	/**
	 * Set type content
	 *
	 */
	public function setContentType() {
		if( $this->_project->filtered['flg_source'] == 1 ) {
			$this->_content=Project_Articles::getInstance();
		} else {
			$this->_content=Project_Embed::getInstance();
		}
	}
	
	/**
	 * Posting content 
	 *
	 * @return unknown
	 */
	public function update() {
		$this->setContentType();
		$_objSchedule = new Project_Publishing_Blogfusion_Content( $this->_project );
		
		if ( !$_objSchedule->onlyNonPosted()->withOrder('s.site_id--dn')->_withDate( time() )->getList( $_arrSchedule ) ) {
			return false;
		}
		$arrContent = array();
		$_objPost = new Project_Wpress_Content_Posts();
		foreach ( $_arrSchedule as $_key => $_item ) {
			if ( !$this->_content->withIds( $_item['content_id'] )->onlyOne()->getContent( $tmpContent ) ) {
				$this->_logger->err( 'can not get content to project id : '.$_item['project_id'] );
				continue;
			}
			$arrContent[] = array(
			'title' 		=> $tmpContent['title'],
			'content' 		=> str_replace( '"', "'", $tmpContent['body'] ),
			'catIds'		=> ( $_item['ext_category_id'] ) ? array( $_item['ext_category_id'] ) : array(),
			'time'			=> date('Y-m-d H:i:s',$_item['start']),
			'tags'			=> $this->_project->filtered['tags'],
			'schedule_id' 	=> $_item['id'] 
			);
			unset($tmpContent);
			if ( $_item['site_id'] != $_arrSchedule[ $_key + 1 ]['site_id'] ) {
				$_blog_id = $_item['site_id'];
				$_objPost->setBlogById( $_blog_id );
				$_objPost->setData( $arrContent );
				if ( !$_objPost->set() ) {
					foreach ( $_objPost->data->filtered as $_i ) {
						Core_Sql::setUpdate( 'pub_schedule', array( 'id'=> $_i['schedule_id'], 'flg_status' => 2 ) );
						$this->_logger->err('public content ['.$_i['schedule_id'].'] ');
					}
				} else {
					foreach ( $_objPost->data->filtered as $_i ) {
						Core_Sql::setUpdate( 'pub_schedule', array( 'id'=> $_i['schedule_id'], 'flg_status' => 1, 'ext_post_id' => $_i['ext_id'] ) );
						$this->_logger->info( 'public content ['.$_i['schedule_id'].'] ext_post_id : ['.$_i['ext_id'].'] ');
					}
				}
				$arrContent = array();
			}
		}
		
		if ( !$_objSchedule->onlyNonPosted()->getList( $_arrSchedule ) ) {
			$this->_logger->info( 'Project was completed id : '. $this->_project->filtered['id'] .'. Set status "completed"' );
			Core_Sql::setUpdate('pub_project', array( 'id'=>$this->_project->filtered['id'], 'flg_status' => 2 ) );
			$this->setNetworking( $_objSchedule );
		}
	}
	/**
	 * Set netvorking
	 *
	 * @param Project_Publishing_Blogfusion_Content $obj
	 * @return unknown
	 */
	private function setNetworking( Project_Publishing_Blogfusion_Content $obj ) {
		if ( !$obj->setNetworking( $_arrSchedule ) ) {
			return false;
		}
		$obj->withOrder('s.site_id--dn')->onlyPosted()->_withDate( time() )->getList( $_arrSchedule );
		$_objPost = new Project_Wpress_Content_Posts();
		
		foreach ( $_arrSchedule as $_in ) {
			foreach ( $_arrSchedule as $_on ) {
				if( $_in['link_to'] == $_on['id'] ) {
					
					// пост на который ссылаемся
					$_objPost->setBlogById( $_on['site_id'] );
					$_objPost
					->onlyOne()
					->withIds( $_on['ext_post_id'] )
					->getList( $_onPost );
					$_strLink2blog = $_objPost->blog->filtered['url'];
					
					// пост куда вставляем ссылку
					$_objPost->setBlogById( $_in['site_id'] );
					$_objPost
					->onlyOne()
					->withIds( $_in['ext_post_id'] )
					->getList( $_inPost );
					
					$_inPost['content'] .= "<br/><a href='{$_strLink2blog}?p={$_onPost['ext_id']}'>{$_onPost['title']}</a>";
					$arrCircular[] = $_inPost;
				}
			}
			unset($_inPost);
		}		

		if ( !empty( $arrCircular ) ) {
			foreach ( $arrCircular as $_key => $_item ) {
				$posted[] = $_item;
				if ( $_item['site_id'] != $arrCircular[ $_key + 1 ]['site_id'] ) {
					$_objPost->setBlogById( $_item['site_id'] );
					$_objPost->setData( $posted );
					if ( !$_objPost->set() ) {
						foreach ( $_objPost->data->filtered as $_i ) {
							$this->_logger->err(' can not set Circular link in post ['.$_i['ext_id'].'] ');
						}
					} else {
						foreach ( $_objPost->data->filtered as $_i ) {
							$this->_logger->info(' set Circular link in post ['.$_i['ext_id'].'] ');
						}
					}
					$posted = array();
				}
			}
		}
		unset($arrCircular);				
		foreach ( $_arrSchedule as $_in ) {
			foreach ( $_arrSchedule as $_on ) {
				if( $_in['link_to_master'] == $_on['id'] ) {
					
					// пост на который ссылаемся
					$_objPost->setBlogById( $_on['site_id'] );
					$_objPost
					->onlyOne()
					->withIds( $_on['ext_post_id'] )
					->getList( $_onPost );
					$_strLink2blog = $_objPost->blog->filtered['url'];
					
					// пост куда вставляем ссылку
					$_objPost->setBlogById( $_in['site_id'] );
					$_objPost
					->onlyOne()
					->withIds( $_in['ext_post_id'] )
					->getList( $_inPost );
					
					$_inPost['content'] .= "<br/><a href='{$_strLink2blog}?p={$_onPost['ext_id']}'>{$_onPost['title']}</a>";
					$arrMaster[] = $_inPost;
				}				
			}
		}
		unset($_inPost);
		if ( empty( $arrMaster ) ) {
			return true;
		}
		foreach ( $arrMaster as $_key => $_item ) {
			$posted[] = $_item;
			if ( $_item['site_id'] != $arrMaster[ $_key + 1 ]['site_id'] ) {
				$_objPost->setBlogById( $_item['site_id'] );
				$_objPost->setData( $posted );
				if ( !$_objPost->set() ) {
					foreach ( $_objPost->data->filtered as $_i ) {
						$this->_logger->err(' can not set Master link in post ['.$_i['ext_id'].'] ');
					}
				} else {
					foreach ( $_objPost->data->filtered as $_i ) {
						$this->_logger->info(' set Master link in post ['.$_i['ext_id'].'] ');
					}					
				}
				$posted = array();
			}
		}
		return true;
	}
	
	
}
?>