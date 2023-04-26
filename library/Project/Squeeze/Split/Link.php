<?php

//extends Project_Widget_Adapter_Squeeze_Campaign
class Project_Squeeze_Split_Link extends Core_Data_Storage {

	protected $_table='squeeze_campaigns2split';
	private $_tableLink='squeeze_campaigns';
	private $_withSplitId=false;
	private $_onlyWinner=false;
	private $_onlySortShownDown=false;

	
	public function withSplitIds( $_arr=array() ) {
		$this->_withSplitIds=$_arr;
		return $this;
	}
	
	protected function init(){
		$this->_onlySortShownDown=false;
		$this->_withSplitIds=false;
		$this->_onlyWinner=false;
		parent::init();
	}

	public function updateClicks(){
		if ( !($this->_withIds) || !($this->_withSplitIds) ) {
			return false;
		}
		return Core_Sql::setExec('UPDATE '.$this->_table.' SET clicks=clicks+1 WHERE split_id='.Core_Sql::fixInjection($this->_withSplitIds).' AND campaign_id='.Core_Sql::fixInjection($this->_withIds));
	}
	
	public function updateLink(){
		if ( !($this->_withIds) || !($this->_withSplitIds) ) {
			return false;
		}
		return Core_Sql::setExec('UPDATE '.$this->_table.' SET shown=shown+1 WHERE split_id='.Core_Sql::fixInjection($this->_withSplitIds).' AND campaign_id='.Core_Sql::fixInjection($this->_withIds));
	}

	public function getList( &$arrRes ){
		$arrRes = Core_Sql::getAssoc('SELECT d.* FROM '.$this->_table.' AS d WHERE d.split_id IN ('.Core_Sql::fixInjection($this->_withSplitIds).')');
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
}
?>