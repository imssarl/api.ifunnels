<?php
/**
 * WorkHorse Framework
 *
 * @category Project
 * @package Project_Exquisite
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2005-2015, web2innovation
 * @author Slepov Viacheslav <shadowdwarf@mail.ru>
 * @date 27.04.2015
 * @version 1.0
 */


/**
 * Project_Traffic_Campaigns
 *
 * @category Project
 * @package Project_Traffic_Campaigns
 * @copyright Copyright (c) 2005-2015, web2innovation
 * @license http://opensource.org/licenses/ MIT License
 */

class Project_Traffic_Campaigns extends Core_Data_Storage {

	protected $_table='traffic_campaigns';
	protected $_fields=array( 'id', 'url', 'category_id', 'credits', 'user_id', 'added' );
	
	private $_withUserId=false;

	public function withUserId( $_str ){
		$this->_withUserId=$_str;
		return $this;
	}

	protected function init() {
		parent::init();
		$this->_withUserId=false;
	}
	
	protected function assemblyQuery() {
		parent::assemblyQuery();
		if( $this->_withUserId ){
			$this->_crawler->set_where('d.user_id='.Core_Sql::fixInjection( $this->_withUserId );
		}
	}
}
?>