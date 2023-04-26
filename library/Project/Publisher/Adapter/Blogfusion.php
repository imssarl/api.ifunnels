<?php
// $_adapter->setProject( $this->_project );
// $_adapter->setSite( $siteId )->setContent( $v['posts'] )->upload();
class Project_Publisher_Adapter_Blogfusion extends Project_Sites_Adapter_Blogfusion implements Core_Singleton_Interface, Project_Publisher_Adapter_Interface {

	private static $_instance=NULL;

	public static function getInstance(){
		if ( self::$_instance==NULL ) {
			self::$_instance= new self();
		}
		return self::$_instance;
	}

	private $_project=0;

	public function setProject( $_arr=array() ) {
		if ( empty( $_arr ) ) {
			// error todo
		}
		$this->_project=$_arr;
		return $this;
	}

	protected $_sourceType=0;

	public function setSourceType( $_intId=0 ) {
		if ( empty( $_intId ) ) {
			// error todo
		}
		$this->_sourceType=$_intId;
		return $this;
	}

	// можно и статик но у нас несколько адаптеров через фактори используется. посему делаем дайнамик
	public function getLink( $arr ){
		$_arrSite=Core_Sql::getRecord( 'SELECT * FROM '.Project_Sites::$tables[Project_Sites::BF].' WHERE id='.$arr['site_id'] );
		return '<br/><a href="'.$_arrSite['url'].'?p='.$arr['ext_post_id'].'">'.$_arrSite['title'].'</a>';
	}

	protected function prepareData() {
		if ( empty( $this->_content ) ) {
			return false;
		}
		$_arrTmp=$this->_content;
		$this->_content=array();
		foreach( $_arrTmp as $v ) {
			$this->_content[]=array(
				'shedule_id'=>$v['id'],
				'title'=>$v['title'],
				'content'=>$v['body'],
				'catIds'=>(empty( $v['ext_category_id'] )? array():array( $v['ext_category_id'] )),
				'time'=>date('Y-m-d H:i:s',( ( !empty($v['start']) )? $v['start']:time() ) ),
				'tags'=>(( !empty($v['tags']) )?$v['tags']:''),
				'files'=>$v['files'],
				'thumb'=>$v['thumb']
			);
		}
		return true;
	}

	// заглушка
	protected function prepareSource() {
		return true;
	}

	protected function afterUpload() {
		Core_Sql::reconnect();
		$_arrTmp=$this->_content;
		$this->_content=array();
		foreach( $_arrTmp as $v ) {
			if ( empty( $v['ext_id'] ) ) { // что-то опубликовалось
				continue;
			}
			$this->_content[]=array(
				'title'=>$v['title'],
				'ext_id'=>$v['ext_id'],
				'added'=>time(),
				'site_id'=>$this->_siteId
			);
		}
		$_content=new Project_Sites_Content( Project_Sites::BF );
		return $_content
			->withFlgFrom( Project_Sites_Content::$type['publisher'] )
			->withProjectId( $this->_project['id'] )
			->withSourceIndex( $this->_project['flg_source'] )
			->withSiteId( $this->_siteId )
			->setContent( $this->_content )
			->set();
	}

	public function upload() {
		if ( !$this->prepareData() ) {
			$this->setError( 'No prepare data');
			return false;
		}
		$posts=new Project_Wpress_Content_Posts();
		if(empty($this->_data->filtered['id'])){
			$this->setError( 'Empty Wp post Id');
			return false;
		}
		$posts->setBlogById( $this->_data->filtered['id'] );
		if ( !$posts->setData( $this->_content )->setFrom( Project_Wpress_Content_Posts::$from['pub'] )->set() ) {
			$this->setError( 'Can\'t set wp post. WP Errors:  '.serialize( $posts->getErrors() ) );
			return false;
		}
		$this->_content=$posts->getData();
		return $this->afterUpload();
	}
	
	private $_arrErrors=array();
	
	public function getErrors( &$arrErrors ) {
		$arrErrors=$this->_arrErrors;
		return $this;
	}
	
	public function setError( $_strError ) {
		$this->_arrErrors[]=$_strError;
	}
	
}
?>