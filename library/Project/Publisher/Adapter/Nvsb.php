<?php
// $_adapter->setProject( $this->_project );
// $_adapter->setSite( $siteId )->setContent( $v['posts'] )->upload();
class Project_Publisher_Adapter_Nvsb extends Project_Sites_Adapter_Nvsb implements Core_Singleton_Interface, Project_Publisher_Adapter_Interface {

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
		$_arrSite=Core_Sql::getRecord( 'SELECT * FROM es_nvsb WHERE id='.$arr['site_id'] );
		return '<br/><a href="'.$_arrSite['url'].'article/'.Core_String::getInstance( strtolower( strip_tags( $arr['title'] ) ) )->str2filename().'.html">'.$_arrSite['title'].'</a>';
	}

	// заглушка
	protected function prepareData() {
		return true;
	}

	protected function prepareSource() {
		$this->_dir='Project_Publisher_Adapter_Nvsb@prepareSource';
		if ( !Zend_Registry::get( 'objUser' )->prepareTmpDir( $this->_dir ) ) {
			return $this->_error->setError( 'Can\'t create dir '.$this->_dir );
		}
		if ( !$this->generateContent() ) {
			return $this->_error->setError( 'Can\'t generate content' );
		}
		return true;
	}

	protected function afterUpload() {
		Core_Sql::reconnect();
		$_arrTmp=$this->_content;
		$this->_content=array();
		foreach( $_arrTmp as $v ) {
			$this->_content[]=array(
				'title'=>$v['title'],
				'added'=>time(),
				'site_id'=>$this->_siteId,
			);
		}
		$_content=new Project_Sites_Content( Project_Sites::NVSB );
		return $_content
			->withFlgFrom( Project_Sites_Content::$type['publisher'] )
			->withProjectId( $this->_project['id'] )
			->withSourceIndex( $this->_project['flg_source'] )
			->withSiteId( $this->_siteId )
			->setContent( $this->_content )
			->set();
	}
}
?>
