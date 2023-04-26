<?php
class Project_Updater_Xedant extends Core_Updater_Abstract {

	public function update( Core_Updater $obj ) {
		$this->logger=$obj->logger;
		$this->logger->info( 'start Project_Updater_Xedant' );
		$this->settings=$obj->settings;
		$this->setDir();
		if ( !$this->tablePrepare() ) {
			$this->logger->err( 'tablePrepare false' );
		}
		if ( !$this->importData() ) {
			$this->logger->err( 'importData false' );
		}
		$this->logger->info( 'end Project_Updater_Xedant' );
	}

/*
Xedant.KeywordDatabase.English245.TXT (English245.KeywordsAndData.txt)
Xedant.KeywordDatabase.Spanish6.TXT
Xedant.KeywordDatabase.Italian16.TXT
Xedant.KeywordDatabase.German6.TXT
Xedant.KeywordDatabase.French7.TXT

Xedant.CloudDatabase.English115.TXT
Xedant.CloudDatabase.Spanish5.TXT
Xedant.CloudDatabase.Italian12.TXT
Xedant.CloudDatabase.German5.TXT
Xedant.CloudDatabase.French5.TXT
*/
	private function tablePrepare() {
		if ( empty( $this->settings['file'] ) ) {
			return false;
		}
		$this->settings['table']=str_replace( '.', '_', strToLower( $this->settings['file'] ) );
		$_arrTables=Core_Sql::getField( 'SHOW TABLES' );
		if ( !in_array( 'project_updater_xedant', $_arrTables ) ) { // таблица для хранения позиции в файле
			Core_Sql::setExec( '
				CREATE TABLE `project_updater_xedant` (
					`file_name` VARCHAR(256) NOT NULL,
					`position` VARCHAR(256) NOT NULL
				)
				COLLATE="utf8_general_ci"
				ENGINE=MyISAM
				ROW_FORMAT=DEFAULT
			' );
		}
		if ( in_array( $this->settings['table'], $_arrTables ) ) { // нужная таблица уже была создана
			return true;
		}
		if ( !$this->fileOpen() ) {
			return false;
		}
		if ( !$this->getLine() ) {
			return false;
		}
		$this->convertToArray();
		$_strFields='';
		foreach( $this->_line as $k=>$v ) {
			$_strFields.='`'.$k.'` TEXT NULL'.((count($this->_line)-1)==$k?'':',');
		}
		Core_Sql::setExec( '
			CREATE TABLE `'.$this->settings['table'].'` ('.$_strFields.')
			COLLATE="utf8_general_ci"
			ENGINE=MyISAM
		' );
		Core_Sql::setInsert( 'project_updater_xedant', array( 'file_name'=>$this->settings['file'], 'position'=>0 ) );
		return true;
	}

	private $_fp, $_line, $_path;

	private function fileOpen() {
		if ( is_resource( $this->_fp ) ) {
			return true;
		}
		$this->_fp=fopen( $this->_path.$this->settings['file'], "r" );
		return $this->_fp;
	}

	private function getLine() {
		$this->_line=fgets( $this->_fp );
		return $this->_line;
	}

	private function setDir() {
		if ( Zend_Registry::get( 'config' )->engine->project_domain=='cnm.dev' ) {
			$this->_path='d:\www\dev\cnm\databases\Xedant.KeywordDatabase.English245.TXT'.DIRECTORY_SEPARATOR;
		} else {
			$this->_path='/data/www/cnm.cnmbeta.info'.DIRECTORY_SEPARATOR;
		}
	}

	private function clearPosition() {
		Core_Sql::setExec( 'UPDATE project_updater_xedant SET position="all done" WHERE file_name="'.$this->settings['file'].'" LIMIT 1' );
	}

	private function seekPosition() {
		$seek=Core_Sql::getCell( 'SELECT position FROM project_updater_xedant WHERE file_name="'.$this->settings['file'].'" LIMIT 1' );
		if ( $seek=='all done' ) {
			return false;
		}
		return fseek( $this->_fp, $seek )==0;
	}

	private function storePosition() {
		Core_Sql::setExec( 'UPDATE project_updater_xedant SET position="'.ftell( $this->_fp ).'" WHERE file_name="'.$this->settings['file'].'" LIMIT 1' );
	}

	private function convertToArray() {
		$this->_line=explode( "\t", trim( $this->_line ) );
		return $this;
	}

	private function insertLine() {
		Core_Sql::setInsert( $this->settings['table'], $this->_line );
		return $this;
	}

	private function importData() {
		$this->fileOpen();
		if ( !$this->seekPosition() ) {
			return false;
		}
		$i=0;
		while( $this->getLine() ) {
			$this->convertToArray()->insertLine();
			$i++;
			if ( $i>100 ) {
				$this->storePosition();
				$i=0;
			}
		}
		$this->clearPosition();
		return fclose( $this->_fp );
	}
}
?>