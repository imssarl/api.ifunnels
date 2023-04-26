<?php

/**
 * Tags Manage
 * @internal управление тэгами разных типов
 */

class Core_Tags_Management extends Core_Data_Storage {

	protected $_table='tag_content';
	protected $_fields=array('id','tag', 'added');
	private $_withTagName=false;
	private $_withTypes=false;

	protected function beforeSet(){
		$this->_data->setFilter();
		return true;
	}

	protected function beforeSetMass( $k, $_arrRow=array() ) {
		Core_Tags::encode( $_arr, $_arrRow['tag']);
		$_arrRow['tag']=$_arr[0];
		if ( !empty( $_arrRow['del'] ) ) {
			$this->_del[]=$_arrRow['id'];
			return false;
		}
		return true;
	}

	protected function afterSetMass() {
		// удаляем отмеченные
		if ( !empty( $this->_del ) ) {
			$this->withIds( $this->_del )->del();
			$this->_del=array();
		}
		return true;
	}

	public function del(){
		Core_Sql::setExec('DELETE FROM tag_link WHERE tags_id IN ('. join(',',$this->_withIds) .')');
		parent::del();
	}

	public function withTagName( $_tag ){
		if(!empty($_tag)){
			$this->_withTagName=$_tag;
		}
		return $this;
	}

	public function withTypes($_types){
		if(!empty($_types)){
			$this->_withTypes=(is_array($_types))?$_types:array($_types);
		}
		return $this;
	}

	protected function init(){
		parent::init();
		$this->_withTagName=false;
		$this->_withTypes=false;
	}

	protected function assemblyQuery(){
		parent::assemblyQuery();
		$this->_crawler->clean_select();
		$this->_crawler->set_select('d.id,d.tag,d.added, IF(INSTR(d.tag, "_"),REPLACE(d.tag,"_"," "),d.tag) decoded ');
		if( $this->_withTagName ){
			$this->_crawler->set_where('d.tag='.Core_Sql::fixInjection( $this->_withTagName) );
		}
		if( $this->_withTypes ){
			$this->_crawler->set_where('d.id IN (SELECT tags_id FROM tag_link l WHERE l.type_id IN('. Core_Sql::fixInjection($this->_withTypes) .'))');
		}
	}
}
?>