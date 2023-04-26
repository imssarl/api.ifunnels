<?php


/**
 * Project_Traffic_Subscribers
 */

class Project_Traffic_Subscribers{

	protected $_table='traffic_subscribers';
	protected $_fields=array('id', 'campaign_id', 'ip', 'referer', 'added');

	protected $_withCampaignId=array(); // c данными popup id
	protected $_withIP=array(); // c данными popup id
	protected $_onlyCount=false; // только количество
	protected $_onlyOne=false; // только одна запись
	
	public function clearOld() {
		// Core_Sql::setExec( 'DELETE FROM '.$this->_table.' WHERE added<'.( time()-60*60*24*30 ) );
		return $this;
	}
	
	public function withCampaignId( $_arrIds=array() ) {
		$this->_withCampaignId=$_arrIds;
		return $this;
	}

	public function withIP( $_arrIPs=array() ) {
		$this->_withIP=$_arrIPs;
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
		if ( !empty( $this->_withCampaignId ) ) {
			$this->_crawler->set_where( 'd.campaign_id IN ('.Core_Sql::fixInjection( $this->_withCampaignId ).')' );
		}
		if ( !empty( $this->_withIP ) ) {
			$this->_crawler->set_where( 'd.ip IN ('.Core_Sql::fixInjection( $this->_withIP ).')' );
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
		$this->_withIP=array();
		$this->_withCampaignId=array();
	}

	public function setEntered( $_mix=array() ) {
		$this->_data=is_object( $_mix )? $_mix:new Core_Data( $_mix );
		return $this;
	}

	public function set() {
		$this->_data->setFilter();
		if( empty( $this->_data->filtered['id'] ) ) {
			$this->_data->setElement( 'added', time() );
		}
		$this->_data->setElement( 'id', Core_Sql::setInsertUpdate( $this->_table, $this->_data->setMask( $this->_fields )->getValid() ) );
	}
	
}
?>