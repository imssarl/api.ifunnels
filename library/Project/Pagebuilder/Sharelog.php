<?php


/**
 * app\library\Project\Pagebuilder\Sharelog.php
 */

class Project_Pagebuilder_Sharelog extends Core_Data_Storage{

	protected $_table='pb_sharelog';
	protected $_fields=array('id', 's_user', 'i_user', 'pb_id', 'added');
	
	public function install (){
		Core_Sql::setExec("CREATE TABLE IF NOT EXISTS `pb_sharelog` (
			`id` INT(11) NOT NULL AUTO_INCREMENT,
			`s_user` INT(11) NULL DEFAULT NULL,
			`i_user` INT(11) NULL DEFAULT NULL,
			`pb_id` INT(11) NULL DEFAULT NULL,
			`added` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			UNIQUE INDEX `id` (`id`)
		);");
	}

	
}
?>