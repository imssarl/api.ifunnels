<?php
/**
 * WorkHorse Framework
 *
 * @category Project
 * @package Project_Exquisite
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2005-2015, web2innovation
 * @author Slepov Viacheslav <shadowdwarf@mail.ru>
 * @date 23.02.2015
 * @version 1.0
 */


/**
 * Project_Traffic_Credits
 *
 * @category Project
 * @package Project_Traffic_Credits
 * @copyright Copyright (c) 2005-2015, web2innovation
 * @license http://opensource.org/licenses/ MIT License
 */

class Project_Traffic_Credits{

	protected $_table='traffic_credits';
	protected $_fields=array('id', 'credits');

	protected $_withIds=array(); // по id пользователя
	protected $_onlyCount=false; // только количество
	protected $_onlyOne=false; // только одна запись
	
	public function withIds( $_arrIds=array() ) {
		$this->_withIds=$_arrIds;
		return $this;
	}

	public function onlyCount() {
		$this->_onlyCount=true;
		return $this;
	}

	public function onlyOne() {
		$this->_onlyOne=true;
		return $this;
	}

	protected function assemblyQuery() {
		$this->_crawler->set_select( 'd.*' );
		$this->_crawler->set_from( $this->_table.' d' );
		if ( !empty( $this->_withIds ) ) {
			$this->_crawler->set_where( 'd.id IN ('.Core_Sql::fixInjection( $this->_withIds ).')' );
		}
	}

	public function getList( &$mixRes ) {
		$this->_crawler=new Core_Sql_Qcrawler();
		$this->assemblyQuery();
		if ( !$this->_onlyCount ) {
			$this->_crawler->get_result_full( $_strSql );
		}
		if ( $this->_onlyCount ) {
			$mixRes=Core_Sql::getCell( $this->_crawler->get_result_counter() );
		} elseif ( $this->_onlyOne ) {
			$mixRes=Core_Sql::getRecord( $_strSql );
		} else {
			$mixRes=Core_Sql::getAssoc( $_strSql );
		}
		$this->init();
		return $this;
	}

	protected function init() {
		$this->_onlyCount=false;
		$this->_onlyOne=false;
		$this->_withIds=array();
	}

	public function setEntered( $_mix=array() ) {
		$this->_data=is_object( $_mix )? $_mix:new Core_Data( $_mix );
		return $this;
	}

	public function set() {
		$this->_data->setFilter();
		$this->withIds( $this->_data->filtered['id'] )->onlyOne()->onlyCount()->getList( $_flgUpdate );
		if( $_flgUpdate == 1 ){
			return Core_Sql::setUpdate( $this->_table, $this->_data->setMask( $this->_fields )->getValid() );
		}else{
			return Core_Sql::setInsert( $this->_table, $this->_data->setMask( $this->_fields )->getValid() );
		}
	}
}
?>