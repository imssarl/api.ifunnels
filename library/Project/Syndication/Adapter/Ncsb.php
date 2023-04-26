<?php
// ->setSite( siteId )->setPlan( $v )->setContent( $arrContent )->upload();
class Project_Syndication_Adapter_Ncsb extends Project_Sites_Adapter_Ncsb/* implements Project_Syndication_Sites_Interface */ {

	private $_plan=array();

	public function setPlan( $data ) {
		if ( empty( $data ) ) {
			// error todo
		}
		$this->_plan=$data;
		return $this;
	}

	private static $_lastUrls=array();

	public static function getLastUrls() {
		return self::$_lastUrls;
	}

	protected function getFileContent( &$arrItem ) {
		return $arrItem['title']."\n".$arrItem['body'].$arrItem['statlink'].'<br />'.$arrItem['backlink'];
	}

	protected function prepareData() {
		// инициализация сайта
		$_ncsb=new Project_Sites( Project_Sites::NCSB );
		if ( !$_ncsb->getSite( $_arrSite, $this->_siteId ) ) {
			return false;
		}
		$this->_data=new Core_Data( $_arrSite );
		// подготовка контента для конкретного сайта
		foreach( $this->_plan as $v ) {
			$_arrContent[]=array(
				'shedule_id'=>$v['id'],
				'title'=>$this->_content[$v['content_id']]['title'],
				'body'=>$this->_content[$v['content_id']]['body'],
				'statlink'=>$v['statlink'],
				'backlink'=>$v['backlink'],
			);
		}
		$this->_content=$_arrContent;
		return !empty( $this->_content );
	}

	protected function prepareSource() {
		$this->_dir='Project_Syndication_Adapter_Ncsb@prepareSource';
		if ( !Zend_Registry::get( 'objUser' )->prepareTmpDir( $this->_dir ) ) {
			$this->_errors[]='Process Aborted. Can\'t create dir Project_Syndication_Adapter_Ncsb@prepareSource';
			return false;
		}
		if ( !$this->generateContent() ) {
			$this->_errors[]='Process Aborted. Can\'t generate content';
			return false;
		}
		return true;
	}

	protected function afterUpload() {
		Core_Sql::reconnect();
		foreach( $this->_content as $v ) {
			self::$_lastUrls[]=array(
				'shedule_id'=>$v['shedule_id'], 
				'url'=> $this->_data->filtered['url'].Core_String::getInstance( strtolower( strip_tags( $v['title'] ) ) )->str2filename().'.html' 
			);
		}
		return true;
		// либо используем сохранение по общей схеме (что возможно, только следует добавить видимо id проекта и возможно тип сурса)
		// либо при удалении контента он должен быть в формате es_content
		$_content=new Project_Sites_Content( Project_Sites::NCSB );
		return $_content
			->withFlgFrom( Project_Sites_Content::$type['syndication'] )
			->withProjectId( $this->_project['id'] )
			->withSourceIndex( $this->_project['flg_source'] )
			->withSiteId( $this->_siteId )
			->setContent( $this->_content )
			->set();
	}
}
?>