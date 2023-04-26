<?php
 /**
 * коннектор к сайтам типа Blogfusion для размещения КТ на таких сайтах
 */
class Project_Syndication_Sites_Blogfusion implements Project_Syndication_Sites_Interface {

	public static $_instance=NULL;

	private static $_lastUrls=array();
	protected  $_plan, $_content;

	public function setData( &$arrPlan, &$arrContent ) {
		$this->_plan=&$arrPlan;
		$this->_content=&$arrContent;
		return $this;
	}
	
	// чтобы это убрать в Project_Syndication_Sites_Abstract нужена get_called_class()
	public static function getInstance() {
		if ( self::$_instance==NULL ) {
			self::$_instance=new Project_Syndication_Sites_Blogfusion();
		}
		return self::$_instance;
	}

	public function run() {
		$post=new Project_Wpress_Content_Posts();
		if ( !$post->setBlogById( $this->_plan[0]['site_realid'] ) ) {
			Project_Syndication_Content_Plan::setStatus( $this->_plan, Project_Syndication_Content_Plan::$stat['error'] ); // ошибки для КТ
			return false;
		}
		// подготовка данных
		foreach( $this->_plan as $v ) {
			$_arrPosts[]=array(
				'title'=>$this->_content[$v['content_id']]['title'],
				'content'=>$this->_content[$v['content_id']]['body'].$v['statlink'].'<br />'.$v['backlink'],
			);
		}
		// постинг
		$post->setData( $_arrPosts )->set();
		$_arrErr=$post->getErrors();
		if ( !empty( $_arrErr ) ) {
			// в случае ошибки для всех КТ выставляем 2 (error)
			Project_Syndication_Content_Plan::setStatus( $this->_plan, Project_Syndication_Content_Plan::$stat['error'] );
		}
		foreach ( $post->data->filtered as $v1 ) {
			foreach ( $this->_plan as $v2 ){
				if ( $this->_content[$v2['content_id']]['title'] == $v1['title'] && !empty( $v1['ext_id'] ) ){
					// для запощенного КТ выставляем ext_post_id и 1 (published)
					$arrIds=array($v2);
					$this->setLinks( $v2['id'], $post, $v1['ext_id'] );
					Project_Syndication_Content_Plan::setStatus( $arrIds, Project_Syndication_Content_Plan::$stat['published'] );
					Core_Sql::setExec( 'UPDATE cs_content2site SET ext_post_id='.$v1['ext_id'].' WHERE id='.$v2['id'] );
				} elseif ( $v1['title'] == $this->_content[$v2['content_id']]['title'] ) {
					//для незапощенного КТ выставляем 2 (error)
					$arrIds=array($v2);
					Project_Syndication_Content_Plan::setStatus( $arrIds, Project_Syndication_Content_Plan::$stat['error'] );
				}
			}
		}
		return true;
	}
	
	private function setLinks( $_sheduleId, $_objSite, $_postId ){
		self::$_lastUrls[] = array('shedule_id'=>$_sheduleId, 'url'=> $_objSite->blog->filtered['url'] .'?p='.$_postId );
	}

	public static function getLastUrls(){
		return self::$_lastUrls;
	}
		
	public function delete(){
		$post=new Project_Wpress_Content_Posts();
		if ( !$post->setBlogById( $this->_plan['site_realid'] ) ) {
			return false;
		}
		// подготавливаем пост.
		$_intPostId = Core_Sql::getCell('SELECT id FROM bf_ext_posts WHERE blog_id = '. $this->_plan['site_realid'].' AND ext_id = '.$this->_content['ext_post_id']);
		$_arrPost = $this->_content;
		$_arrPost['id'] = $_intPostId;
		$_arrPost['ext_id'] = $_arrPost['ext_post_id'];
		$_arrPost['del'] = 'on';
		$_arrPost['content'] = $_arrPost['body'];
		if ( !$post->setData( array( $_arrPost) )->set() ){
			return false;
		}
		Core_Sql::setExec('DELETE FROM cs_content2site WHERE id='.$this->_content['c2s_id']);
		// @TODO рейтинг
		return true;
	}
}
?>