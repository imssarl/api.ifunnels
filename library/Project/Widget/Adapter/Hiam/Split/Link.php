<?php


/**
 * Управление split test link
 */
class Project_Widget_Adapter_Hiam_Split_Link extends Project_Widget_Adapter_Hiam_Campaign {

	private $_tableLink='hi_campaigns2split';
	private $_withSplitId=false;
	private $_onlyWinner=false;
	private $_onlySortShownDown=false;

	/**
	*	добаление связей с компаниями для split test'а
	*
	*	param
	*	$_splitId - id split test
	*	$_arrIds - array из id split test campaigns
	*	return bool
	*/
	public function setLink( $_splitId=0, $_arrIds=array() ){
		if ( !$this->withSplitIds($_splitId)->withIds($_arrIds)->getList($arrList) ) {
			return false;
		}
		foreach ( $_arrIds as $id ) {
			$haveInArray=false;
			foreach ( $arrList as $campaign){
				if ( $campaign['id'] == $id ) {
					$arrIns[]=array(
						'split_id'=>$_splitId,
						'campaign_id'=>$id,
						'flg_winner'=>$campaign['flg_winner'],
						'shown'=>$campaign['shown'],
					);
					$haveInArray=true;
					break;
				}
			}
			if (!$haveInArray) {
				$arrIns[]=array(
					'split_id'=>$_splitId,
					'campaign_id'=>$id,
					'flg_winner'=>0,
					'shown'=>0,
				);
			}
		}
		$this->withSplitIds($_splitId)->delLink();
		Core_Sql::setMassInsert( $this->_tableLink, $arrIns );
		return true;
	}
	
	/**
	*	увеличение колличества показов для split test'а
	*/
	public function updateLink(){
		if ( !($this->_withIds) || !($this->_withSplitIds) ) {
			return false;
		}
		return Core_Sql::setExec('UPDATE '.$this->_tableLink.' SET shown=shown+1 WHERE split_id='.Core_Sql::fixInjection($this->_withSplitIds).' AND campaign_id='.Core_Sql::fixInjection($this->_withIds));
	}
	
	public function delLink() {
		if ( !empty($this->_withSplitIds) ) {
			return Core_Sql::setExec('DELETE FROM '.$this->_tableLink.' WHERE split_id IN ('.Core_Sql::fixInjection($this->_withSplitIds).')');
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

	protected function assemblyQuery() {
		if ( $this->_onlyWinner ) {
			$this->_crawler->set_from( $this->_tableLink );
			$this->_crawler->set_select( 'campaign_id' );
			$this->_crawler->set_where('split_id='.Core_Sql::fixInjection($this->_withSplitIds).' AND flg_winner=2');
			return;
		}
		parent::assemblyQuery();
		If ( !$this->_onlyIds ) {
			$this->_crawler->set_select( 'a.*' );
		}
		$this->_crawler->set_from('INNER JOIN '.$this->_tableLink.' a ON a.campaign_id=d.id');
		if ( $this->_onlySortShownDown ) {
			$this->_crawler->set_order_sort('shown--dn');
		}
		if ( !$this->_onlyIds ){
			$this->_crawler->set_select('(SELECT MAX(ROUND(c.clicks/c.views*100)) FROM '.$this->_table.' c INNER JOIN '.$this->_tableLink.' e ON e.campaign_id=c.id WHERE e.split_id='.Core_Sql::fixInjection($this->_withSplitIds).') as maxcrt');
			$this->_crawler->set_select('ROUND(d.clicks/d.views*100) as crt');
			$this->_crawler->set_order_sort('crt--up');
		}
		if ( !empty( $this->_withSplitIds ) ) {
			$this->_crawler->set_where( 'a.split_id IN ('.Core_Sql::fixInjection($this->_withSplitIds).')' );
		}
	}
}
?>