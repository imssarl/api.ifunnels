<?php

class Project_Deliver_Member_Address extends Core_Data_Storage {

	protected $_table = 'deliver_member_address';

	protected $_fields = array( 'id', 'member_id', 'name', 'country', 'address', 'city', 'zip' );

	/** Installing */
	public static function install(){
		Core_Sql::setExec( "DROP TABLE IF EXISTS deliver_member_address" );
		Core_Sql::setExec( 
			"CREATE TABLE `deliver_member_address` (
				`id` INT(11) NOT NULL AUTO_INCREMENT,
				`member_id` INT(11) NULL DEFAULT NULL,
				`name` VARCHAR(255) NULL DEFAULT NULL,
				`country` VARCHAR(4) NULL DEFAULT NULL,
				`address` TEXT NULL DEFAULT NULL,
				`city` TEXT NULL DEFAULT NULL,
				`zip` VARCHAR(30) NULL DEFAULT NULL,
				UNIQUE INDEX `id` (`id`)
			)
			COLLATE='utf8_general_ci'
			ENGINE=InnoDB;" 
		);
	}

	public function beforeSet() {
		$this->_data->setFilter( array( 'clear' ) );

		return true;
	}

	protected function assemblyQuery() {
		parent::assemblyQuery();

		// $this->_crawler->get_sql( $_strSql, $this->_paging );
		// p( $_strSql );
	}

	protected function init() {}

	public function getList( &$mixRes ){
		parent::getList( $mixRes );

		$this->init();
		return $this;
	}
}