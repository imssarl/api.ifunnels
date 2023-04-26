<?php


/**
 * Project_Synnd_Reports
 */

class Project_Synnd_Reports extends Core_Data_Storage{

	protected $_table='synnd_reports';
	protected $_fields=array('id', 'campaign_id', 'flg_type', 'flg_status', 'error_code', 'promote_count', 'added');

	public static $promotionStatus=array(
		'in_queue'=>0,
		'completed'=>1,
		'error'=>2,
	);
	
	public static $_errorCode=array(
		0=>'Promotion is broken',
		1=>'A description must have at least 10 words!',
		2=>'A maximum of 5 tags can be added to a campaign!',
		3=>'Tags must not contain more than three words!',
		4=>'A tag must have at least 3 characters!',
		5=>'A title must have at least 4 words!',
		6=>'You can add only up to 5 tags!',
		7=>'No money. Purchase extra credits',
	);
	
	protected function beforeSet() {
		$this->_data->setFilter( array( 'clear' ) );
		if( !$this->_withCampaignId ) {
			return Core_Data_Errors::getInstance()->setError('Empty campaign id');
		}
		$this->_data->setElement('campaign_id', $this->_withCampaignId );
		return true;
	}

	public function set() {
		if ( !$this->beforeSet() ) {
			return false;
		}
		if ( empty( $this->_data->filtered['id'] ) ) {
			$this->_data->setElement( 'added', ( (isset($this->_data->filtered['added']))?$this->_data->filtered['added']:time() ) );
		}
		$this->_data->setElement( 'id', Core_Sql::setInsertUpdate( $this->_table, $this->_data->setMask( $this->_fields )->getValid() ) );
		return true;
	}
	
	public function setRestart( $arrData, $_flgTime=0 ) {
		if( $_flgTime != 0 ){
			$this->withCampaignId( $arrData['campaign_id'] )->setEntered( array( 'flg_type'=>$arrData['flg_type'], 'promote_count'=>$arrData['promote_count'], 'added'=>$arrData['added']+Project_Synnd::$promotionPeriod[$_flgTime]['amount'] ) )->set();
		}
		return $this;
	}

	public function setStatus( $_flgStatus, $_mix=null ) {
		if( !in_array( self::$promotionStatus, $_flgStatus ) && empty( $this->_withIds ) ) {
			return Core_Data_Errors::getInstance()->setError('Data is not correct');
		}
		$_error='';
		if( self::$promotionStatus['error'] == $_flgStatus ){
			$_mix=array_keys( array_intersect( self::$_errorCode, array( $_mix ) ) );
			if( !empty( $_mix ) )
				$_error=', error_code='.Core_Sql::fixInjection( $_mix[0] );
			Core_Data_Errors::getInstance()->setError( self::$_errorCode[ intval($_mix) ] );
		}
		Core_Sql::setExec('UPDATE '.$this->_table.' SET flg_status='.Core_Sql::fixInjection( $_flgStatus ).$_error.' WHERE id='.Core_Sql::fixInjection( $this->_withIds ) );
		$this->init();
	}

	protected $_onlyActive=false;
	protected $_withCampaignId=false;

	public function onlyActive() {
		$this->_onlyActive=true;
		return $this;
	}

	public function withCampaignId( $_id ) {
		if( !empty( $_id ) ) {
			$this->_withCampaignId=$_id;
		}
		return $this;
	}

	protected function assemblyQuery() {
		parent::assemblyQuery();
		if( $this->_onlyActive ) {
			$this->_crawler->set_where('d.flg_status=0 and d.added<'.time() );
		}
		if( $this->_withCampaignId ) {
			$this->_crawler->set_where('d.campaign_id IN ('.Core_Sql::fixInjection( $this->_withCampaignId ).')' );
		}
	}

	protected function init() {
		parent::init();
		$this->_onlyActive=false;
		$this->_withCampaignId=false;
	}

	public function delCorrupted() {
		if ( empty( $this->_withCampaignId ) ) {
			$_bool=false;
		} else {
			Core_Sql::setExec( 'DELETE FROM '.$this->_table.' WHERE campaign_id IN('.Core_Sql::fixInjection( $this->_withCampaignId ).')'.' AND flg_status=2' );
			$_bool=true;
		}
		$this->init();
		return $_bool;
	}

	public function del() {
		if ( empty( $this->_withCampaignId ) ) {
			$_bool=false;
		} else {
			Core_Sql::setExec( 'DELETE FROM '.$this->_table.' WHERE campaign_id IN('.Core_Sql::fixInjection( $this->_withCampaignId ).')'.' AND flg_status=0' );
			$_bool=true;
		}
		$this->init();
		return $_bool;
	}

}
?>