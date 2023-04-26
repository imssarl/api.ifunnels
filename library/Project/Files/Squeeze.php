<?php
class Project_Files_Squeeze extends Core_Files_Info {
	
	public $_fileId=false;
	/**
	 * путь по которому сохраняется исходный файл
	 *
	 * @var string
	 */
	protected $_destinationPath='squeeze_files';

	/**
	 * инициализация переменных учавствующих в set()
	 *
	 * @return string
	 */
	protected function initFileFields() {
		parent::initFileFields();
		$this->_destinationPath='squeeze_files';
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
			$this->_crawler->set_select('d.id, d.path_web, d.name_system');
		}
	}

	public function getPaths( &$arrData ){ // TODO 09.01.2013 - возможно ненадо проверить
		if( isset($arrData['file_sound']) && $arrData['file_sound'] != '0'){
			$this->onlyPaths()->withIds( $arrData['file_sound'] )->onlyOne()->getList( $_arrRes );
			$arrData['file_sound_path']='http://'.Zend_Registry::get( 'config' )->engine->project_domain.$_arrRes['path_web'].$_arrRes['name_system'];
		}
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

	/**
	 * аспект кторый вызывается после выполнения set()
	 * после переназначения тут например можно сделать какие-либо действия после сохранения данных
	 *
	 * @return boolean
	 */
	protected function afterSet() {
		if ( !parent::afterSet() ) {
			return false;
		}
		$this->_fileId=$this->_data->filtered['id'];
		return true;
	}

	protected function verify(){
		if ( !parent::verify() ) {
			return false;
		}
		if ( $this->_mode==self::$mode['onlyDataEdit'] ) {
			return true;
		}
		if ( $this->_mode==self::$mode['copy'] ) {
			return true;
		}
		return $this->verifySounds();
	}
}
?>