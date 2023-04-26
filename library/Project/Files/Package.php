<?php
class Project_Files_Package extends Core_Files_Info {

	/**
	 * путь по которому сохраняется исходный файл
	 *
	 * @var string
	 */
	protected $_destinationPath='package_logo';

	/**
	 * инициализация переменных учавствующих в set()
	 *
	 * @return string
	 */
	protected function initFileFields() {
		parent::initFileFields();
		$this->_destinationPath='package_logo';
	}

	protected function verify() {
		if ( !parent::verify() ) {
			return false;
		}
		$_validate=new Core_Files_Transfer();
		$_validate
			->setFiles( $this->getEnteredFile() )
			->addValidator( 'Size', false, array( 'min'=>'5kB', 'max'=>'2MB' ) )
			->addValidator( 'MimeType', false, 'image' )
			->addValidator( 'Extension', false, array( 'jpg', 'png', 'gif' ) );
		return $_validate->isValid();
	}
}
?>