<?php

 /**
 * коннектор к сайтам типа NVSB для размещения КТ на таких сайтах
 */
class Project_Syndication_Sites_Nvsb extends Project_Sites_Type_Nvsb implements  Project_Syndication_Sites_Interface  {

	public static $_instance=NULL;

	protected  $_plan, $_content;

	public function setData( &$arrPlan, &$arrContent ) {
		$this->_plan=&$arrPlan;
		$this->_content=&$arrContent;
		return $this;
	}
		
	public static function getInstance() {
		if ( self::$_instance==NULL ) {
			self::$_instance=new Project_Syndication_Sites_Nvsb();
		}
		return self::$_instance;
	}

	public function run() {
		$object=new Project_Sites( Project_Sites::NVSB );
		if ( !$object->getSite($_arrSite, $this->_plan[0]['site_realid'] ) ) {
			Project_Syndication_Content_Plan::setStatus( $this->_plan, Project_Syndication_Content_Plan::$stat['error'] );
			return false;
		}
		// подготовка данных
		foreach( $this->_plan as $v ) {
			$_arrPosts[]=array(
				'shedule_id' => $v['id'],
				'title'=>$this->_content[$v['content_id']]['title'],
				'body'=>$this->_content[$v['content_id']]['body'].$v['statlink'].'<br />'.$v['backlink'], // а также добавить ссылку для тракинга (если кт не дёргали больше месяца то удаляем КТ из трэкера)
			);
		}
		$_arrSite['arrNvsb']['arrContent']=$_arrPosts;
		$object->setData( $_arrSite );
		// постинг
		if ( !$this->set( $object ) ){
			Project_Sites_Type_Nvsb::$_lastUrls=array();
			// в случае ошибки для всех КТ выставляем 2 (error)
			Project_Syndication_Content_Plan::setStatus( $this->_plan, Project_Syndication_Content_Plan::$stat['error'] );
			return false;
		}
		// для запощенного КТ выставляем 1 (published)
		Project_Syndication_Content_Plan::setStatus( $this->_plan, Project_Syndication_Content_Plan::$stat['published'] );
		return true;
	}
	
	public function delete(){
		$object=new Project_Sites( Project_Sites::NVSB );
		if ( !$object->getSite($_arrSite, $this->_plan['site_realid'] ) ) {
			return false;
		}
		$this->_content['del']=true;
		$_arrSite['arrNvsb']['arrContent']=array($this->_content);
		$object->setEntered( $_arrSite );
		if ( !$this->set($object) ){
			return false;
		}
		// TODO удаление из базы + рейтинг.
		Core_Sql::setExec('DELETE FROM cs_content2site WHERE id='.$this->_content['c2s_id']);
		return true;
	}
		
	// переопределение родительского метода
	public function set( Project_Sites  $object ) {
		$this->data=new Core_Data( $object->getDataObject()->setFilter( array( 'trim', 'clear' ) )->getRaw( 'arrNvsb' ) );
		$this->data->setFilter( array( 'trim', 'clear' ));
		$_oldArticle=json_decode($object->getDataObject()->filtered['strJson'],true);
		foreach( $_oldArticle as $item ) {
			$_arrIds[]=$item['id'];
		}		
		$this->data->setElement( 'arrArticleIds', $_arrIds);
		if ( !$this->upload() ) {
			return false;
		}
		return true;
	}

	// переопределение родительского метода
	public function prepareSource(){
		$this->_dir='Project_Syndication_Sites_Nvsb@prepareSource';
		if ( !Zend_Registry::get( 'objUser' )->prepareTmpDir( $this->_dir ) ) {
			$this->_errors[]='Process Aborted. Can\'t create dir Project_Syndication_Sites_Nvsb@prepareSource';
			return false;
		}
		mkdir($this->_dir .'articles'.DIRECTORY_SEPARATOR );
		if ( !$this->generateArticles() ) {
			$this->_errors[]='Process Aborted. Can\'t generate articles';
			return false;
		}
		return true;
	}
		
	// переопределение родительского метода
	protected function generateArticles() {
		$_arrContent=array();
		if( !empty($this->data->filtered['arrArticleIds'] ) ){
			if ( !Project_Articles::getInstance()->withIds( $this->data->filtered['arrArticleIds'] )->getContent( $_arrContent ) ) {
				return false;
			}
		}
		$_arrContent=array_merge($_arrContent,$this->data->filtered['arrContent']);		
		$_strDir=$this->_dir.'articles'.DIRECTORY_SEPARATOR;
		if ( !is_dir($_strDir) ){
			mkdir($_strDir,true);
		}		
		foreach( $_arrContent as $v ) {
			$_strContent=$v['title']."\n".$v['body'];
			$_strFileName=Core_String::getInstance( strtolower( strip_tags( $v['title'] ) ) )->toSystem( '-' ).'.txt';
			if ( !empty($v['id'] ) ) {
				$_arrFiels[]=$_strFileName;
			}
			if ( !empty( $v['shedule_id'] ) ){
				$this->setLinks($v['shedule_id'],$_strFileName);
			}			
			if ( !empty($v['del']) ){
				continue;
			}
			if ( !Core_Files::setContent( $_strContent, $_strDir.$_strFileName ) ) {
				return false;
			}
		}
		$_strFiles=serialize( ( !empty($_arrFiels) ? $_arrFiels : array(1) ) );
		Core_Files::setContent( $_strFiles, $this->_dir.'articles-list.txt');
		return true;
	}
		

}
?>