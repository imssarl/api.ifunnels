<?php

//extends Project_Widget_Adapter_Squeeze_Campaign
class Project_Widget_Adapter_Squeeze_Split_Link extends Core_Data_Storage {

	//protected $_table='squeeze_campaigns';
	protected $_table='squeeze_campaigns2split';
	private $_tableLink='squeeze_campaigns';
	private $_withSplitId=false;
	private $_onlyWinner=false;
	private $_onlySortShownDown=false;
	//protected $_withOrder='d.shown--up';

	/**
	*	добаление связей с компаниями для split test'а
	*
	*	param
	*	$_splitId - id split test
	*	$_arrIds - array из id split test campaigns
	*	return bool
	*/
	public function setLink( $_splitId=0, $_arrIds=array() ){
		//p($_arrIds);
		$url = null;
		if ( !$this->withSplitIds($_splitId)->getList($arrList) ) {
			return false;
		}
		foreach ( $_arrIds as $id ) {
			try {
				Core_Sql::renewalConnectFromCashe();
				$url = Core_Sql::getCell('SELECT url FROM '.$this->_tableLink.' WHERE id IN ('.Core_Sql::fixInjection($id). ')');
				Core_Sql::setConnectToServer( 'syndication.qjmpz.com' );
			} catch(Exception $e) {
				Core_Sql::setConnectToServer( 'syndication.qjmpz.com' );
			}

			$haveInArray=false;
			foreach ( $arrList as $campaign){
				if ( $campaign['id'] == $id ) {
					$arrIns[]=array(
						'split_id'=>$_splitId,
						'campaign_id'=>$id,
						'url'=>$campaign['url'],
						'flg_winner'=>$campaign['flg_winner'],
						'shown'=>$campaign['shown'],
						'clicks' => $campaign['clicks']
					);
					$haveInArray=true;
					break;
				}
			}
			if (!$haveInArray) {
				$arrIns[]=array(
					'split_id'=>$_splitId,
					'campaign_id'=>$id,
					'url'=>$url,
					'flg_winner'=>0,
					'shown'=>0,
					'clicks' => 0
				);
			}
		}
		$this->withSplitIds($_splitId)->delLink();
		Core_Sql::setMassInsert( $this->_table, $arrIns );
		return true;
	}

	public function setLink2( $_splitId=0, $_arrIds=array() ){
		$url = null;
		if ( !$this->withSplitIds($_splitId)->withIds($_arrIds)->getList($arrList) ) {
			return false;
		}
		foreach ( $_arrIds as $id ) {
			try {
				Core_Sql::renewalConnectFromCashe();
				$url = Core_Sql::getCell('SELECT url FROM '.$this->_tableLink.' WHERE id IN ('.Core_Sql::fixInjection($id). ')');
				Core_Sql::setConnectToServer( 'syndication.qjmpz.com' );
			} catch(Exception $e) {
				Core_Sql::setConnectToServer( 'syndication.qjmpz.com' );
			}
			$haveInArray=false;
			foreach ( $arrList as $campaign){
				if ( $campaign['id'] == $id ) {
					$arrIns[]=array(
						'split_id'=>$_splitId,
						'campaign_id'=>$id,
						'url'=>$campaign['url'],
						'flg_winner'=>$campaign['flg_winner'],
						'shown'=>$campaign['shown'],
						'clicks' => $campaign['clicks']
					);
					$haveInArray=true;
					break;
				}
			}
			if (!$haveInArray) {
				$arrIns[]=array(
					'split_id'=>$_splitId,
					'campaign_id'=>$id,
					'url'=>$url,
					'flg_winner'=>0,
					'shown'=>0,
					'clicks' => 0
				);
			}
		}
		Core_Sql::setMassInsert( $this->_table, $arrIns );
		return true;
	}
	
	/**
	*	увеличение колличества показов для split test'а
	*/
	public function updateLink(){
		if ( !($this->_withIds) || !($this->_withSplitIds) ) {
			return false;
		}
		return Core_Sql::setExec('UPDATE '.$this->_table.' SET shown=shown+1 WHERE split_id='.Core_Sql::fixInjection($this->_withSplitIds).' AND campaign_id='.Core_Sql::fixInjection($this->_withIds));
	}

	public function updateClick(){
		if ( !($this->_withIds) || !($this->_withSplitIds) ) {
			return false;
		}
		return Core_Sql::setExec('UPDATE '.$this->_table.' SET clicks=clicks+1 WHERE split_id='.Core_Sql::fixInjection($this->_withSplitIds).' AND campaign_id='.Core_Sql::fixInjection($this->_withIds));	
	}
	
	public function delLink() {
		if ( !empty($this->_withSplitIds) ) {
			return Core_Sql::setExec('DELETE FROM '.$this->_table.' WHERE split_id IN ('.Core_Sql::fixInjection($this->_withSplitIds).')');
		}
		return false;
	}
	
	public function withSplitIds( $_arr=array() ) {
		$this->_withSplitIds=$_arr;
		return $this;
	}
	
	public function withSortShownDown() {
		$this->_onlySortShownDown=true;
		return $this;
	}
	
	public function onlyWinner() {
		$this->_onlyWinner=true;
		return $this;
	}
	
	protected function init(){
		$this->_onlySortShownDown=false;
		$this->_withSplitIds=false;
		$this->_onlyWinner=false;
		parent::init();
	}

	public function getList( &$arrRes ){
		$arrRes = Core_Sql::getAssoc('SELECT d.* FROM '.$this->_table.' AS d WHERE d.split_id IN ('.Core_Sql::fixInjection($this->_withSplitIds).')');
		try {
			Core_Sql::renewalConnectFromCashe();
			foreach ($arrRes as $key => $value) {
				$arrRes[$key] = array_merge(Core_Sql::getRecord('SELECT d.* FROM '.$this->_tableLink.' AS d WHERE d.id IN ('.Core_Sql::fixInjection($value['campaign_id']).')'), $value);
			}
			Core_Sql::setConnectToServer( 'syndication.qjmpz.com' );
		} catch(Exception $e) {
			
			Core_Sql::setConnectToServer( 'syndication.qjmpz.com' );
		}
		$arrRes=array_diff($arrRes, array(''));
		foreach ($arrRes as $key => $value) {
			if(!empty($arrRes[$key]['id']))
			$arrRes[$key]['stat'] = array(
				'goal1' => Core_Sql::getCell('SELECT COUNT(*) FROM lpb_conversionpixel WHERE squeeze_id='.$arrRes[$key]['id'].' AND flg_pixeltype=1'),
				'goal2' => Core_Sql::getCell('SELECT COUNT(*) FROM lpb_conversionpixel WHERE squeeze_id='.$arrRes[$key]['id'].' AND flg_pixeltype=2'),
				'goal3' => Core_Sql::getCell('SELECT COUNT(*) FROM lpb_conversionpixel WHERE squeeze_id='.$arrRes[$key]['id'].' AND flg_pixeltype=3')
			);
		}
		$maxcrt = 0;
		foreach ($arrRes as $key => $value) {
			$arrRes[$key]['crt'] = sprintf("%01.2f", $value['clicks']/$value['shown']*100);
		}
		$maxcrt = $this->maxValueInArray($arrRes, 'crt');
		foreach ($arrRes as $key => $value) {
			$arrRes[$key]['maxcrt'] = $maxcrt;
		}
		return $this;
	}

	protected function maxValueInArray($array, $keyToSearch){
	    $currentMax = NULL;
	    foreach($array as $arr)
	    {
	        foreach($arr as $key => $value) {
	            if ($key == $keyToSearch && ($value >= $currentMax)) {
	                $currentMax = $value;
	            }
	        }
	    }

	    return $currentMax;
	}

	protected function assemblyQuery() {
		if ( $this->_onlyWinner ) {
			$this->_crawler->set_from( $this->_tableLink );
			$this->_crawler->set_select( 'campaign_id' );
			$this->_crawler->set_where('split_id='.Core_Sql::fixInjection($this->_withSplitIds).' AND flg_winner=2');
			return;
		}
		parent::assemblyQuery();
		if ( !$this->_onlyIds ) {
			$this->_crawler->set_select( 'a.*' );
		}
		$this->_crawler->set_from('INNER JOIN '.$this->_tableLink.' a ON a.campaign_id=d.id');
		if ( $this->_onlySortShownDown ) {
			$this->_crawler->set_order_sort('shown--dn');
		}
		if ( !empty( $this->_withSplitIds ) ) {
			$this->_crawler->set_where( 'a.split_id IN ('.Core_Sql::fixInjection($this->_withSplitIds).')' );
		}
	}


}
?>