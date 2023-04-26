<?php
class Project_Files_Hiam extends Core_Files_Info {

	/**
	 * путь по которому сохраняется исходный файл
	 *
	 * @var string
	 */
	protected $_destinationPath='hiam_files';

	/**
	 * инициализация переменных учавствующих в set()
	 *
	 * @return string
	 */
	protected function initFileFields() {
		parent::initFileFields();
		$this->_destinationPath='hiam_files';
	}

	protected $_onlyPaths=false;
	
	public function onlyPaths(){
		$this->_onlyPaths=true;
		return $this;
	}

	protected function init(){
		parent::init();
		$this->_onlyPaths=false;
	}

	protected function assemblyQuery() {
		parent::assemblyQuery();
		if( $this->_onlyPaths){
			$this->_crawler->clean_select();
			$this->_crawler->set_select('d.path_web,d.name_system');
		}
	}
	
	public function getPaths( &$arrData ){
		$_domain='https://'.Zend_Registry::get( 'config' )->engine->project_domain;
		if( isset($arrData['file_background']) && $arrData['file_background'] != '0' ){
			$this->onlyPaths()->withIds( $arrData['file_background'] )->onlyOne()->getList( $_arrRes );
			$arrData['file_background_path']=$_domain.$_arrRes['path_web'].$_arrRes['name_system'];
		}
		if( isset($arrData['file_sound']) && $arrData['file_sound'] != '0'){
			$this->onlyPaths()->withIds( $arrData['file_sound'] )->onlyOne()->getList( $_arrRes );
			$arrData['file_sound_path']=$_domain.$_arrRes['path_web'].$_arrRes['name_system'];
		}
		if( isset($arrData['file_corner']) && $arrData['file_corner'] != '0' ){
			$this->onlyPaths()->withIds( $arrData['file_corner'] )->onlyOne()->getList( $_arrRes );
			$arrData['file_corner_path']=$_domain.$_arrRes['path_web'].$_arrRes['name_system'];
		}
	}

	private function verifyImages() {
		$_validate=new Core_Files_Transfer();
		$_validate
			->setFiles( $this->getEnteredFile() )
			->addValidator( 'Size', false, array( 'min'=>'5kB', 'max'=>'2MB' ) )
			->addValidator( 'MimeType', false, 'image' )
			->addValidator( 'Extension', false, array( 'jpg', 'png', 'gif' ) );
		return $_validate->isValid();
	}

	private function verifySounds() {
		$_validate=new Core_Files_Transfer();
		$_validate
			->setFiles( $this->getEnteredFile() )
			->addValidator( 'Size', false, array( 'min'=>'5kB', 'max'=>'10MB' ) )
			->addValidator( 'MimeType', false, 'audio' )
			->addValidator( 'Extension', false, array( 'mp3' ) );
		return $_validate->isValid();
	}

	protected function verify() {
		if ( !parent::verify() ) {
			return false;
		}
		if ( $this->_mode==self::$mode['onlyDataEdit'] ) {
			return true;
		}
		if ( in_array( $this->_group['sysname'], array( 'hiam_default_backgrounds', 'hiam_default_corners', 'hiam_user_backgrounds', 'hiam_user_corners' ) ) ) {
			return $this->verifyImages();
		}
		if ( in_array( $this->_group['sysname'], array( 'hiam_user_sounds', 'hiam_default_sounds' ) ) ) {
			return $this->verifySounds();
		}
		return false;   
	}
}
?>