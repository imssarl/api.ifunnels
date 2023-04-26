<?php


/**
 * Keywords контент функционал
 */

class Project_Content_Adapter_Keywords extends Core_Storage implements Project_Content_Interface {

	public $table="hct_kwd_savedlist";
	public $_contents = '';
	private $_settings=false;

	public function __construct( ){
		if ( !Zend_Registry::get( 'objUser' )->getId( $this->_userId ) ) {
			throw new Exception( Core_Errors::DEV.'|Zend_Registry::get( \'objUser\' )->getId( $_int ) is not return an User Id' );
			return;
		}
	}
	public function setFilter( $_arrFilter=array() ) {
		$this->_settings=$_arrFilter;
		return $this;
	}	
	
	public function setLimited( $_intLimit ) {
		return $this;
	}
	
	public function setCounter( $_intCounter ) {
		return $this;
	}

	protected function assemblyQuery() {
		$this->_crawler->set_select( 'list_title AS title, list_id AS id, user_id' );
		$this->_crawler->set_from( $this->table );
		$this->_crawler->set_where( 'user_id='.$this->_userId );
	}
	
	public static function getInstance () {}

 	public function setSettings( $arrSettings ){
		if( empty($arrSettings) ){
			return false;
		}
		$this->_settings=$arrSettings;
		return $this;
	}

	public function getAdditional( &$arrRes ) {
	
		$_model = new Project_Keywords_Generator();
		$_model->getSavedList($this->out['arrList']);
		if ( !empty( $_GET['keyword'] ) ) {
			$_arr = $_model->getKeywords( json_decode( $_GET['jsonIds'] , true) );
			header('Content-type: application/json;');
			echo Zend_Registry::get( 'CachedCoreString' )->php2json($_arr);
			exit;
		}
		return $this;
	}
	
	public function setPost( $_arrPost=array() ) {
		return $this;
	}
	
	public function setFile( $_arrFile=array() ) {
		$arrKeywords = array ();
			switch ( Core_Files::getExtension($_arrFile['file']['name']) ){
				case 'txt': 
					Core_Files::getContent( $arrKeywords, $_arrFile['file']['tmp_name'] );
					$this->_contents['keyword'] = preg_replace ( "/[\\r\\n{0,}]/s", " ", $arrKeywords );
				break;
				case 'csv':
					$_fp = fopen($_arrFile['file']['tmp_name'],'r');
					while ( ( $_arr = fgetcsv( $_fp, null, ';') ) ){
						$arrKeywords = array_merge( $arrKeywords, $_arr );
					}
					$this->_contents['keyword'] = implode( ' ', $arrKeywords );
					fclose($_fp);
				break;
			}
			$this->_contents['keyword'] = "\\n".$this->_contents['keyword'];
		return $this;
	}
	
	public function getResult( &$arrRes ) {
	
		if ( !empty($this->_contents) ) {
			$arrRes['setdata'] = 1;
			$arrRes['filekeywords'] = json_encode( $this->_contents );
		} else {
		$arrRes['setdata'] = 2;
		}
		return true;
	}

	public function getList( &$arrRes ){
		if( !empty( $this->_withIds ) ){
			$this->prepareKeywords( $arrRes );
		} else {
			parent::getList( $arrRes );
		}
		return $this;
	}

	private function prepareKeywords( &$arrRes ){
		$arr=explode( "\n", $this->_settings['keywords'] );
		if( $this->_settings['flg_generate'] == 2 ) {
			$arr=array_slice( $arr, 0, (!empty($this->_settings['keywords_first']))?$this->_settings['keywords_first']:1 );
		} elseif ( $this->_settings['flg_generate'] == 3 ){
			$arr=array_rand( $arr, (!empty($this->_settings['keywords_random']))?$this->_settings['keywords_random']:1 );
		}
		foreach( $arr as $index=>$item ){
			$arrRes[]=array('id'=>$index,'title'=>trim($item),'body'=>'');
		}
	}
}
?>