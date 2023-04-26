<?php
class Project_Sites_Adapter_Ncsb_Download implements Core_Singleton_Interface, Project_Sites_Adapter_Interface {

	private static $_instance=NULL;

	public static function getInstance(){
		if ( self::$_instance==NULL ) {
			self::$_instance= new self();
		}
		return self::$_instance;
	}

	public function __construct() {
		$this->_error=Core_Data_Errors::getInstance();
	}

	/**
	 * Errors object
	 *
	 * @var Core_Data_Errors object
	 */
	protected $_error;

	public function getErrors( &$arrErrors ) {
		$arrErrors=$this->_error->getErrors();
		return $this;
	}

	protected $_userId=0;

	public function setUser( $_int ) {
		return $this;
	}

	protected $_content=array();

	public function setContent( $data ) {
		return $this;
	}

	public function getContent() {}

	protected $_siteId=0;

	/**
	 * Core_Data object
	 *
	 * @var Core_Data object
	 */
	protected $_data;

	public function setSite( $_intId=0 ) {
		return $this;
	}

	public function get( &$arrRes, $_arrSite=array() ) {}

	public function set( Project_Sites $object ) {
		$this->_data=new Core_Data( $object->getDataObject()->setFilter( array( 'trim', 'clear' ) )->getRaw( 'arrNcsb' ) );
		$this->_data->setElement( 'contentIds', $object->getDataObject()->filtered['multibox_ids_content_wizard'] )->setFilter( array( 'trim', 'clear' ) );
		if ( !$this->_error->setData( $this->_data )->setValidators( array(
			'template_id'=>$this->_error->getValidator( 'Zend_Validate_NotEmpty' ),
			'main_keyword'=>$this->_error->getValidator( 'Zend_Validate_NotEmpty' ),
			'navigation_length'=>$this->_error->getValidator( 'Zend_Validate_NotEmpty' ),
		) )->isValid() ) {
			return false;
		}
		$this->_data->setElements( array(
			'edited'=>time(),
			'damas_ids'=>(!empty( $object->getDataObject()->filtered['dmascodetext'] )? $object->getDataObject()->filtered['dmascodetext']:''),
			'flg_damas'=>(!empty( $object->getDataObject()->filtered['headlines_spot1'] )? $object->getDataObject()->filtered['headlines_spot1']:0),
			'snippet_number'=>(!empty( $this->_data->filtered['snippet_number'] )? $this->_data->filtered['snippet_number']:5),
			'snippet_length'=>(!empty( $this->_data->filtered['snippet_length'] )? $this->_data->filtered['snippet_length']:250),
			'flg_traking'=>(!empty( $object->getDataObject()->filtered['arrOpt']['flg_traking'] )? $object->getDataObject()->filtered['arrOpt']['flg_traking']:0),
			'traking_code'=>(!empty( $object->getDataObject()->filtered['arrOpt']['traking_code'] )? $object->getDataObject()->filtered['arrOpt']['traking_code']:''),
			'flg_snippet'=>(( $this->_data->filtered['flg_snippet'] == 'no')? 0:1),
		) );
		$this->_optData=$object->getDataObject()->getRaw( 'arrOpt' );
		return $this->upload();
	}

	private $_dir='';
	private $_file='';

	public function upload() {
		if( empty($this->_data->filtered) ){
			return false;
		}
		if ( !$this->prepareData() ) {
			return false;
		}
		if ( !$this->prepareSource() ) {
			return false;
		}
		if( !$this->pack() ){
			return false;
		}
		return $this->_file;
	}

	private function pack(){
		$_dir='Project_Sites_Adapter_Ncsb_Download@pack';
		if ( !Zend_Registry::get( 'objUser' )->prepareTmpDir( $_dir ) ) {
			return $this->_error->setError( 'Can\'t create dir '.$_dir );
		}
		$this->_file=$_dir.'source.zip';
		if ( true!==Core_Zip::getInstance()->open( $this->_file, ZipArchive::CREATE ) ) {
			return $this->_error->setError( 'Can\'t create zip arhive' );
		}
		if ( !Core_Zip::getInstance()->addDirAndClose( $this->_dir ) ) {
			return $this->_error->setError( 'Can\'t add files to zip' );
		}
		return true;
	}

	protected function prepareData() {
		if( empty( $this->_data->filtered['contentIds'] ) ){
			return true;
		}
		$this->_content=array();
		Project_Content::factory( $this->_settings['flg_source'] )->withJson()->setFilter( $this->_settings )->withIds( $this->_data->filtered['contentIds'] )->getList( $this->_content );
		if(empty($this->_content)){
			return $this->_error->setError( 'There is no relevant content found for your settings.' );
		}
		foreach( $this->_content as &$_item ){
			$_item['body']=$_item['fields'];
		}
		Project_Content::factory( $this->_settings['flg_source'] )
			->setFilter( $this->_settings )
			->prepareBody( $this->_content );
		return true;
	}

	protected function prepareSource() {
		$this->_dir='Project_Sites_Adapter_Ncsb_Download@prepareSource';
		if ( !Zend_Registry::get( 'objUser' )->prepareTmpDir( $this->_dir ) ) {
			return $this->_error->setError( 'Can\'t create dir '.$this->_dir );
		}
		if ( !$this->getTemplate() ) {
			return $this->_error->setError( 'Can\'t get template' );
		}
		if ( !$this->patchFiles() ) {
			return $this->_error->setError( 'Can\'t patch files' );
		}
		if ( !$this->generateContent() ) {
			return $this->_error->setError( 'Can\'t generate content' );
		}
		return true;
	}

	// контент из статей при создании-редактировании сайта
	protected function getFileContent( &$arrItem ) {
		return $arrItem['title']."\n".$arrItem['author']."\n".$arrItem['body'];
	}

	protected function generateContent() {
		if ( empty( $this->_content ) ) {
			return true;
		}
		$_strDir=$this->_dir.'datas'.DIRECTORY_SEPARATOR.'articles'.DIRECTORY_SEPARATOR;
		if ( !is_dir( $_strDir ) ) {
			mkdir( $_strDir, 0755, true );
		}
		if ( !is_dir( $_strDir ) ) {
			return $this->_error->setError( 'Can\'t create dir '.$_strDir );
		}
		foreach( $this->_content as $v ) {
			$_strFileName=Core_String::getInstance( strtolower( strip_tags( $v['title'] ) ) )->str2filename().'.txt';
			$_str=$this->getFileContent( $v );
			if ( !Core_Files::setContent( $_str, $_strDir.$_strFileName ) ) {
				continue;
			}
		}
		return true;
	}


	private $_settings=array();
	// TODO! переименовать в setSettings 23.01.2013
	public function setAmazonSettings( $arrData=array() ) {
		if( !empty( $arrData ) ){
			$this->_settings=$arrData;
		}
		return $this;
	}

	private function patchFiles() {
		// dams
		$_strCode=Project_Options_GetCode::getDamsPhpCode( $this->_optData );
		$_strCode.=' '.Project_Options_GetCode::getTrakingCode( $this->_optData );
		$_strFile=file_get_contents( $this->_dir.'damscode.php' );
		$_arrFiles['damscode.php']=str_replace( '<damscode>', (empty( $_strCode )?'':$_strCode), $_strFile );
		// spots
		$_arrCode=Project_Options_GetCode::getSpotsCode( $this->_optData );
		if( empty( $this->_data->filtered['google_analytics'] ) ){
			$_arrCode=array(
				'spot1'=>isset($_arrCode['spot1'])?$_arrCode['spot1']:' ',
				'spot2'=>isset($_arrCode['spot2'])?$_arrCode['spot2']:' ',
				'spot3'=>isset($_arrCode['spot3'])?$_arrCode['spot3']:' ',
			);
		}
		// spot1
		$_strFile=file_get_contents( $this->_dir.'mainads.php' );
		if ( empty( $_arrCode['spot1'] ) ) { // if defult then $_arrCode['spot1']==false а также его может и не быть
			$_arrFiles['mainads.php']=str_replace( array( '<spot1>', '<default1>', '</default1>' ), array( '', '', '' ), $_strFile );
		} else {
			$_strDefaultCode=substr( $_strFile, stripos( $_strFile, '<default1>' ), stripos( $_strFile, '</default1>' ) + 3 );
			$_arrFiles['mainads.php']=str_replace( array( '<spot1>', $_strDefaultCode ), array( '', $_arrCode['spot1'] ), $_strFile );
		}
		// spot2
		$_strFile=file_get_contents( $this->_dir.'sideads.php' );
		if ( empty( $_arrCode['spot2'] ) ) {
			$_strFile=str_replace( array( '<spot2>', '<default2>', '</default2>' ), array( '', '', '' ), $_strFile );
		} else {
			$strposStart=stripos( $_strFile, '<default2>' );
			$strposEnd=stripos( $_strFile, '</default2>' );
			$_strDefaultCode=substr( $_strFile, $strposStart , $strposEnd - $strposStart + 11 );
			$_strFile=str_replace( array( '<spot2>', $_strDefaultCode ), array( '', $_arrCode['spot2'] ), $_strFile );
		}
		// spot3
		if ( empty( $_arrCode['spot3'] ) ) {
			$_strFile=str_replace( array( '<spot3>', '<default3>', '</default3>' ), array( '', '', '' ), $_strFile );
		} else {
			$strposStart=stripos( $_strFile, '<default3>' );
			$strposEnd=stripos( $_strFile, '</default3>' );
			$_strDefaultCode=substr( $_strFile,  $strposStart , $strposEnd - $strposStart + 11 );
			$_strFile=str_replace( array( '<spot3>', $_strDefaultCode ), array( '', $_arrCode['spot3'] ), $_strFile );
		}
		$_arrFiles['sideads.php']=$_strFile;
		// Zonterest ['zonterest'] - ID Copt
		if( !empty($this->_data->filtered['zonterest']) ){
			$_strFile=file_get_contents( $this->_dir.'index.php' );
			$_strFile=str_replace( '<zonterest>', Project_Widget_Adapter_Copt_Snippets::getCode( $this->_data->filtered['zonterest'] ),$_strFile );
			$_arrFiles['index.php']=$_strFile;
			$_strFile=file_get_contents( $this->_dir.'sitemap.php' );
			$_strFile=str_replace( '<zonterest>', Project_Widget_Adapter_Copt_Snippets::getCode( $this->_data->filtered['zonterest'] ),$_strFile );
			$_arrFiles['sitemap.php']=$_strFile;
			$_strFile=file_get_contents( $this->_dir.'disclaimer.php' );
			$_strFile=str_replace( '<zonterest>', Project_Widget_Adapter_Copt_Snippets::getCode( $this->_data->filtered['zonterest'] ),$_strFile );
			$_arrFiles['disclaimer.php']=$_strFile;
			$_strFile=file_get_contents( $this->_dir.'privacy.php' );
			$_strFile=str_replace( '<zonterest>', Project_Widget_Adapter_Copt_Snippets::getCode( $this->_data->filtered['zonterest'] ),$_strFile );
			$_arrFiles['privacy.php']=$_strFile;
		}
		// config
		$_strFile=file_get_contents( $this->_dir.'config.php' );
		$_bool=(empty( $this->_data->filtered['flg_snippet'] )||$this->_data->filtered['flg_snippet']=='no'); // выбрали Full Article (display random article ...
		$_arrFiles['config.php']=str_replace( array(
			'$$$snippet$$$',
			'$$$nrsnippet$$$',
			'$$$lsnippet$$$',
			'$$$navigation$$$',
			'$$$adsense$$$',
			'$$$keyword$$$',
		), array(
			( $_bool? '0':'1' ),
			( $_bool? '5':$this->_data->filtered['snippet_number'] ),
			( $_bool? '250':$this->_data->filtered['snippet_length'] ),
			$this->_data->filtered['navigation_length'],
			$this->_data->filtered['google_analytics'],
			addslashes($this->_data->filtered['main_keyword']),
		), $_strFile );
		if( !empty( $this->_settings ) ){
			if(stripos($this->_settings['category'],'::')!==false){
				$_tmp=explode('::',$this->_settings['category']);
				$this->_settings['category']=$_tmp[0];
				$this->_settings['BrowseNode']=$_tmp[1];
			}
			$_arrFiles['config.php']=str_replace( array(
				'$$$secret_key$$$',
				'$$$api_key$$$',
				'$$$affiliate$$$',
				'$$$site_lng$$$',
				'$$$category$$$',
				'$$$category_bn$$$',
			), array(
				$this->_settings['secret_key'],
				$this->_settings['api_key'],
				$this->_settings['affiliate'],
				$this->_settings['site'],
				$this->_settings['category'],
				$this->_settings['BrowseNode'],
			), $_arrFiles['config.php'] );
		}
		 // Шаблон не менялся, удаляем все файлы шаблона, и создаем только те которые меняются.
		if( !empty($this->_data->filtered['id'])&&($this->_data->filtered['old_template_id']==$this->_data->filtered['template_id'])&&empty($this->_data->filtered['update']) ){
			$this->_dir='Project_Sites_Adapter_Ncsb_Download@prepareSource';
			if ( !Zend_Registry::get( 'objUser' )->prepareTmpDir( $this->_dir ) ) {
				return $this->_error->setError( 'Can\'t create dir '.$this->_dir );
			}
		}
		// сохраняем
		return Core_Files::setContentMass( $_arrFiles, $this->_dir );
	}

	private function getTemplate() {
		$_template=new Project_Sites_Templates( Project_Sites::NCSB );
		if ( !$_template->onlyOne()->withIds( $this->_data->filtered['template_id'] )->getList( $_arrTemplate )->checkEmpty() ) {
			return false;
		}
		return Core_Zip::getInstance()
			->setDir( $this->_dir )
			->extractZip( $_arrTemplate['path'].$_arrTemplate['filename'] );
	}

	public function deleteSites( $_arrIds ) {}
	public function deleteContent(){}
	public function import( Project_Sites $object ){}
}
?>