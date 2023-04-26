<?php
 
/**
 * Контент сайтов
 */
class Project_Sites_Content extends Core_Data_Storage {

	public $_fields=array( 'id', 'site_id', 'flg_from', 'flg_type', 'title', 'link', 'added', 'flg_source','project_id' );
	public $_table='es_content';
	
	protected $_counter=1;
	protected $_limit=10;
	protected $_flgType=false;
	protected $_siteId=false;
	protected $_withOrder='added--up';
	protected $_flgFrom=false;
	protected $_link=false;
	protected $_onlyTitle=false;
	protected $_content=false;
	protected $_flgSource=false;
	protected $_withUrl=false;
	protected $_projectI=0;
	/**
	* типы постинаг проектов
	* @var array
	*/
	public static $type=array(
		'self'=>1,
		'publisher'=>2,
		'syndication'=>3
	);

	public function __construct( $_type=false ){
		if( !empty($_type) ){
			$this->_flgType=$_type;
		}
		if ( !Zend_Registry::get( 'objUser' )->prepareTmpDir( $this->_localDir ) ) {
			throw new Exception( Core_Errors::DEV."|Can'\t prepare template dir ".$this->_localDir."." );
		}
	}

	public function setFrom( $_int ){
		$this->_flgFrom=$_int;
		return $this;
	}

	 //  ->setContent( $arrContent )->set();
	public function set() {
		if ( empty( $this->_flgFrom )||empty( $this->_flgType ) ) {
			return false;
		}
		$_flgFrom=$this->_flgFrom;
		$_flgType=$this->_flgType;
		$_siteId=$this->_siteId;
		$_flgSource=$this->_flgSource;
		$_projectId=$this->_projectId;
		$this->onlyTitle()->getList($_arrData);
		foreach ( $this->_content as $v) {
			if ( empty($v['del']) && !in_array(array('title'=>$v['title']), $_arrData) ) {
//				unset( $v['id'] );//убирает id который тянется с shedule
				$this->setEntered( $v );
				$this->_data->setElements( array(
					'added'=>time(),
					'flg_type'=>$_flgType,
					'title'=>$v['title'],
					'flg_from'=>$_flgFrom,
					'flg_source'=>$_flgSource,
					'project_id'=>intval( $_projectId ),
					'link'=>( $_flgType!=Project_Sites::BF? Core_String::getInstance( strtolower( strip_tags( $v['title'] ) ) )->str2filename():(empty($v['ext_id'])?'0':$v['ext_id']) ),
					'site_id'=>empty( $v['site_id'] )? $_siteId:$v['site_id'],
				) );
				$this->_data->setElement( 'id', Core_Sql::setInsertUpdate( $this->_table, $this->_data->setFilter()->setMask( $this->_fields )->getValid() ) );
			}
		}
		return true;
	}

	protected function assemblyQuery() {
		if ( $this->_onlyTitle ) {
			$this->_crawler->set_select( 'title' );
		}
		else if ( $this->_withUrl ) {
			$this->_crawler->set_select( '*,
			CASE flg_type
				WHEN 1 THEN
						( SELECT url from es_psb WHERE id=site_id )
					WHEN 2 THEN
						( SELECT url from es_ncsb WHERE id=site_id )
					WHEN 3 THEN
						( SELECT url from es_nvsb WHERE id=site_id )
					WHEN 4 THEN
						( SELECT url from es_cnb WHERE id=site_id )
					ELSE
						( SELECT url from bf_blogs WHERE id=site_id )
				  END
			as url' );
		} else {
			$this->_crawler->set_select( '*' );
		}
		$this->_crawler->set_from( $this->_table .' d' );
		if( !empty( $this->_flgType ) ) {
			$this->_crawler->set_where( 'flg_type='.Core_Sql::fixInjection( $this->_flgType ) );
		}
		if ( !empty( $this->_siteId ) ) {
			$this->_crawler->set_where( 'site_id='.Core_Sql::fixInjection( $this->_siteId ) );
		}
		if ( !empty( $this->_flgFrom ) ) {
			$this->_crawler->set_where( 'flg_from='.Core_Sql::fixInjection( $this->_flgFrom ) );
		}
		if ( !empty( $this->_projectId ) ) {
			$this->_crawler->set_where( 'project_id='.Core_Sql::fixInjection( $this->_projectId ) );
		}
		if ( !empty( $this->_withIds ) ) {
			$this->_crawler->set_where( 'id IN ('.Core_Sql::fixInjection( $this->_withIds ).')' );
		}
		$this->_crawler->set_paging( $this->_withPaging );
		$this->_crawler->set_order_sort( $this->_withOrder );
		$this->_withPaging['rowtotal']=Core_Sql::getCell( $this->_crawler->get_result_counter( $_strTmp ) );
	}

	public function withSiteId( $_str ){
		if( empty($_str) ){
			return $this;
		}
		$this->_siteId=$_str;
		return $this;
	}

	public function withFlgFrom( $_str ){
		if( empty($_str) ){
			return $this;
		}
		$this->_flgFrom=$_str;
		return $this;
	}

	public function withUrl(){
		$this->_withUrl=true;
		return $this;
	}

	public function withProjectId($_str ) {
		if( empty($_str) ){
			return $this;
		}
		$this->_projectId=$_str;
		return $this;
	}

	public function onlyTitle() {
		$this->_onlyTitle=true;
		return $this;
	}

	public function withSourceIndex( $_str ) {
		if( empty($_str) ){
			return $this;
		}
		$this->_flgSource=$_str ;
		return $this;
	}

	protected function init(){
		$this->_flgFrom=false;
		$this->_siteId=false;
		$this->_onlyTitle=false;
		$this->_flgSource=false;
		$this->_withUrl=false;
		$this->_projectId=false;
		parent::init();
	}

	public function setContent( &$data ){
		$this->_content=&$data;
		return $this;
	}

	public function getPublicateResult(){
		return $this->_content;
	}

	/**
	 * Transport
	 * @var  Project_Placement_Transport object
	 */
	protected $_transport=null;
	
	protected $_remoteDirs=array(
		Project_Sites::NCSB => 'datas/articles/',
		Project_Sites::NVSB => 'articles/'
	);
	
	protected $_localDir='Project_Sites_Content@content';
	protected $_postId;
	
	public function editRemoteContent( $_arrData=array(), &$_arrOut ){
		if( !$this->connect2server( $_arrOut['arrSite'] ) ){
			return Core_Data_Errors::getInstance()->setError('Can not connect to server');
		}
		$_arrData['description']=preg_replace( "/(\r\n|\r|\n)/", '', $_arrData['description'] );
		$_arrData['link']=Core_String::getInstance( strtolower( strip_tags( $_arrData['title'] ) ) )->str2filename();
		$_strContent=$_arrData['title']."\n".$_arrData['poster']."\n".$_arrData['description'];
		Core_Files::setContent( $_strContent, $this->_localDir.$_arrData['link'].'.txt' );
		$_file=$this->_remoteDirs[$this->_flgType].$_arrData['link'].'.txt';
		$_oldFile=$this->_remoteDirs[$this->_flgType].$_arrData['old_file'].'.txt';
		$this->_transport->removeFile( $_oldFile );
		if( !$this->_transport->saveFile( $_strContent, $_file) ){
			return Core_Data_Errors::getInstance()->setError('Can not save content');
		}
		foreach( $_arrOut['arrContent'] as &$_data ){
			if( $_data['id'] == $this->_postId ){
				Core_Sql::setExec( 'UPDATE es_content SET title="'.$_arrData['title'].'", link= "'.$_arrData['link'].'" WHERE id='.$_data['id'] );
				$_data['title']=$_arrData['title'];
				$_data['link']=$_arrData['link'];
				continue;
			}
		}
		return true;
	}

	public function getRemoteContent( &$_arrOut ){
		if( !$this->connect2server($_arrOut['arrSite']) ){
			return Core_Data_Errors::getInstance()->setError('Can not connect to server');
		}
		foreach( $_arrOut['arrContent'] as &$_arrContent ){
			if ( $this->_postId == $_arrContent['id'] ){
				$_file=$this->_remoteDirs[$this->_flgType].$_arrContent['link'].'.txt';
				if( !$this->_transport->readFile($_strContent, $_file) ){
					return Core_Data_Errors::getInstance()->setError('Can not read content');
				}
				$_tmpArr=explode("\n",$_strContent);
				$_arrOut['arrEditContent']=array(
					'title'=>trim( strip_tags( array_shift( $_tmpArr ) ) ),
					'poster'=>trim( array_shift( $_tmpArr ) ),
					'description'=>trim( implode( '', $_tmpArr ) ),
					'old_file'=>$_arrContent['link'],
				);
			}
		}
		return true;
	}

	public function setPostId( $_int ){
		$this->_postId=$_int;
		return $this;
	}
	
	private function connect2server( $_place ){
		if( empty( $_place ) ){
			return false;
		}
		$this->_transport=new Project_Placement_Transport();
		$this->_transport->setInfo($_place);
		return true;
	}

	// удаление контента с сайтов
	public function deleteContent() {
		$this->getList($arrDelContent);
		foreach( $arrDelContent as $_item ){
			$_driver=Project_Sites_Adapter_Factory::get( $_item['flg_type'] );
			if( !$_driver->setSite( $_item['site_id'] )->setContent( array($_item) )->deleteContent() ){
				return false;
			}
			$this->withIds( $_item['id'] )->del();
		}
		return true;
	}

	/**
	 * Удаление контента с BF.
	 * @return bool
	 */
	public function deleteFromBf() {
		$arrIds=array();
		$this->_data->setFilter();
		foreach ( $this->_data->filtered as $v ) {
			if ( !empty( $v['del'] ) ) {
				$arrIds[]=$v['ext_id'];
			}
		}
		if ( empty( $arrIds ) ) {
			return false;
		}
		Core_Sql::setExec( 'DELETE FROM '.$this->_table.' WHERE link IN ('.Core_Sql::fixInjection( $arrIds ).') AND site_id='.$this->_siteId );
		return true;
	}

	public function getErrors( &$arrErrors ){
		$arrErrors=Core_Data_Errors::getInstance()->getErrors();
	}
}
?>