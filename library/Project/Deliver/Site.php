<?php

class Project_Deliver_Site extends Core_Data_Storage {

	protected $_table = 'deliver_site';
	private $_userFilePath = false;
	
	/**
	 * @param [id] ==> Record id in a table
	 * @param [user_id] ==> User Id in the iFunnel
	 * @param [name] ==> Site name
	 * @param [logo] ==> Path for logo file
	 * @param [currency] ==> Currency data
	 * @param [added] ==> Unixtime code when a record is created
	 * @param [edited] ==> Unixtime code when was record is edited
	 */
	protected $_fields = array( 'id', 'user_id', 'name', 'logo', 'currency', 'added', 'edited' );

	public static function getInstance() {
		return new self();
	}

	/** Installing */
	public static function install(){
		Core_Sql::setExec( "DROP TABLE IF EXISTS deliver_site" );
		Core_Sql::setExec( 
			"CREATE TABLE `deliver_site` (
				`id` INT(11) NOT NULL AUTO_INCREMENT,
				`user_id` INT(11) NOT NULL DEFAULT '0',
				`name` VARCHAR(100) NULL DEFAULT NULL,
				`logo` VARCHAR(255) NULL DEFAULT NULL,
				`currency` VARCHAR(10) NULL DEFAULT NULL,
				`added` INT(11) UNSIGNED NOT NULL DEFAULT '0',
				`edited` INT(11) UNSIGNED NOT NULL DEFAULT '0',
				UNIQUE INDEX `id` (`id`)
			)
			COLLATE='utf8_general_ci'
			ENGINE=InnoDB;" 
		);
	}


	public function beforeSet() {
		$this->_data->setFilter( array( 'clear' ) );
		
		/** Set field [user_id] */
		$this->_data->setElement( 'user_id', Core_Users::$info['id'] );

		/** Set field ['logo'] */
		if( ! empty( $_FILES['arrData']['name']['logo'] ) ) {
			$this->_data->setElement( 'logo', $this->saveLogo() );

			/** Remove current logo if was added */
			if( ! empty( $this->_data->filtered['id'] ) ) {
				$this
					->withIds( $this->_data->filtered['id'] )
					->onlyOne()
					->getList( $recData );

				if( ! empty( $recData['logo'] ) ) {
					unlink( Zend_Registry::get('config')->path->absolute->root . $recData['logo'] );
				}
			}
		}

		return true;
	}

	/**
	 * Return saved file path
	 * 
	 * @return null or string
	 */
	private function saveLogo() {
		$this->checkDir();

		if( ! empty( $_FILES ) ) {
			$tmpFile = $_FILES['arrData']['tmp_name']['logo'];
			$tmpFileInfo = pathinfo( $_FILES['arrData']['name']['logo'] );

			if( move_uploaded_file( $tmpFile, $this->_userFilePath['absolute'] . 'file_' . md5( time() ) . '.' . $tmpFileInfo['extension'] ) ) {
				return $this->_userFilePath['relative'] . 'file_' . md5( time() ) . '.' . $tmpFileInfo['extension'];
			}
		}

		return null;
	}

	/** 
	 * Check exist dir and create dir if not exist 
	 *
	 * @return array( 'absolute', 'relative' ) 
	 */
	private function checkDir() {
		if( ! file_exists( Zend_Registry::get('config')->path->absolute->user_data  . Core_Users::$info['id'] . DIRECTORY_SEPARATOR . 'diliver' ) ) {
			mkdir( Zend_Registry::get('config')->path->absolute->user_data  . Core_Users::$info['id'] . DIRECTORY_SEPARATOR . 'diliver' );
		}

		$this->_userFilePath = array(
			'absolute' => Zend_Registry::get('config')->path->absolute->user_data  . Core_Users::$info['id'] . DIRECTORY_SEPARATOR . 'diliver' . DIRECTORY_SEPARATOR,
			'relative' => Zend_Registry::get('config')->path->html->user_data  . Core_Users::$info['id'] . '/diliver/'
		); 
	}

	public function del(){
		$this
			->onlyOne()
			->getList( $recData );

		if( ! empty( $recData ) ) {
			if( ! empty( $recData['logo'] ) ){
				unlink( Zend_Registry::get('config')->path->absolute->root . $recData['logo'] );
			}

			$this->withIds( $recData['id'] );
			parent::del();
		}
	}


	private $_withUserId = false;
	public function withUserId( $user_id ) {
		if( ! empty( $user_id ) ) {
			$this->_withUserId = $user_id;
		}

		return $this;
	}

	protected function assemblyQuery() {
		parent::assemblyQuery();

		if( ! empty( $this->_withUserId ) ){
			$this->_crawler->set_where( 'd.user_id=' . Core_Sql::fixInjection( $this->_withUserId ) );
		}
	}

	protected function init() {
		$this->_withUserId = false;
	}

	public function getList( &$mixRes ){
		parent::getList( $mixRes );

		$this->init();
		return $this;
	}
}