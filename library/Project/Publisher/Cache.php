<?php
class Project_Publisher_Cache {

	private $_cashe=array();
	private $_projectId=0;
	private $_siteId=0;
	// сколько раз проверять уникальность контента для данного сайта
	private $_uniqDeep=3;

	public function __construct( $_intProjectId=0 ) {
		if ( empty( $_intProjectId ) ) {
			throw new Exception( Core_Errors::DEV.'|Project_Publisher_Cache->__construct( $_intProjectId=0 ) - empty project id set' );
		}
		$this->_projectId=$_intProjectId;
	}

	public function setSiteId( $_intSiteId=0 ) {
		if ( empty( $_intSiteId ) ) {
			throw new Exception( Core_Errors::DEV.'|Project_Publisher_Cache->setSiteId( $_intSiteId=0 ) - empty site id set' );
		}
		$this->_siteId=$_intSiteId;
		return $this;
	}

	public function setMass( &$arr ) {
		foreach( $arr as $post ) {
			$this->set( $post['body'] );
		}
	}

	// добавляет кэш на новый контент
	public function set( $_strContent='' ) {
		if ( empty( $this->_siteId )||empty( $_strContent ) ) {
			return false;
		}
		Core_Sql::setInsert( 'pub_cache', array(
			'project_id'=>$this->_projectId,
			'site_id'=>$this->_siteId,
			'hash'=>md5( $_strContent ),
		) );
		return true;
	}

	private function get() {
		$_arrRes=Core_Sql::getAssoc( 'SELECT site_id, hash FROM pub_cache WHERE project_id='.$this->_projectId );
		foreach( $_arrRes as $v ) {
			$this->_cashe[$v['site_id']][]=$v['hash'];
		}
	}

	// TODO!! 22.06.2011 в check каждый раз делается md5  надо заоптимизировать этот момент
	// проверка есть-ли данный контент на данном сайте для текущего проекта
	private function check( $_strContent='' ) {
		if ( empty( $_strContent ) ) {
			return false;
		}
		if ( empty( $this->_cashe ) ) {
			$this->get();
		}
		if ( empty( $this->_cashe[$this->_siteId] ) ) {
			return true;
		}
		$_strContent=md5( $_strContent );
		if ( in_array( $_strContent, $this->_cashe[$this->_siteId] ) ) {
			return false;
		}
		$this->_cashe[$this->_siteId][]=$_strContent; // пока в контент в кэше в бд отсутствует вставляем его в кэш руками
		return true;
	}

	public function setSiteList( &$arrSites ) {
		$this->_sites=& $arrSites;
	}

	// берём случайным образом сайт на котором данный контент ещё небыл опубликован в рамках данного проекта
	public function getUniqSite( &$v ) {
		$i=0;
		do {
			$_intKey=array_rand( $this->_sites, 1 );
			if ( $this->setSiteId( $this->_sites[$_intKey]['site_id'] )->check( $v['body'] ) ) {
				return true;
			}
			$i++;
		} while( $i==$this->_uniqDeep );
		return false;
	}

	public function getSiteId() {
		return $this->_siteId;
	}
}
?>