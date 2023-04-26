<?php
class Project_Nicheresearch extends Core_Data_Storage {

	protected $_table='content_niche';
	protected $_fields=array('id','flg_type','word');

	private $_withWord=false;
	private $_onlyTop=false;
	private $_withRandom=false;

	public function withWord( $_str ){
		if(!empty($_str)){
			$this->_withWord=$_str;
		}
		return $this;
	}

	public function onlyTop(){
		$this->_onlyTop=true;
		return $this;
	}

	public function withRandom(){
		$this->_withRandom=true;
		return $this;
	}

	protected function init(){
		parent::init();
		$this->_withWord=false;
		$this->_onlyTop=false;
		$this->_withRandom=false;
		$this->_withOrder='';
	}

	protected function assemblyQuery(){
		if(!empty($this->_withRandom)){
			$this->_withOrder='';
		}
		parent::assemblyQuery();
		if(!empty($this->_withWord)){
			$this->_crawler->set_where('d.word LIKE '. Core_Sql::fixInjection('%'.$this->_withWord.'%'));
		}
		if(!empty($this->_onlyTop)){
			$this->_crawler->set_where('d.flg_type=1');
		} else {
			$this->_crawler->set_where('d.flg_type=0');
		}
		if(!empty($this->_withRandom)){
			$this->_crawler->set_order_sort('rand');
			$this->_crawler->set_limit('5');
		}
	}
}
?>