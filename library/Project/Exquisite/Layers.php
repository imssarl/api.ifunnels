<?php


/**
 * Project_Exquisite_Layers
 */

class Project_Exquisite_Layers extends Core_Data_Storage{

	protected $_table='ulp_layers';
	protected $_fields=array('id', 'user_id', 'popup_id', 'title', 'content', 'zindex', 'details', 'added', 'edited', 'deleted');

	public static $defaultOptions=array(
		"title" => "",
		"content" => "",
		"width" => "",
		"height" => "",
		"left" => 20,
		"top" => 20,
		"background_color" => "",
		"background_opacity" => 0.9,
		"background_image" => "",
		"content_align" => "left",
		"index" => 5,
		"appearance" => "fade-in",
		"appearance_delay" => "200",
		"appearance_speed" => "1000",
		"font" => "arial",
		"font_color" => "#000000",
		"font_weight" => "400",
		"font_size" => 14,
		"text_shadow_size" => 0,
		"text_shadow_color" => "#000000",
		"confirmation_layer" => "off",
		'inline_disable' => 'off',
		"style" => ""
	);

	protected $_withPopupId=array(); // c данными popup id
	
	public function withPopupId( $_arrIds=array() ) {
		$this->_withPopupId=$_arrIds;
		return $this;
	}
	
	protected function assemblyQuery() {
		parent::assemblyQuery();
		if ( !empty( $this->_withPopupId ) ) {
			$this->_crawler->set_where( 'd.popup_id IN ('.Core_Sql::fixInjection( $this->_withPopupId ).')' );
		}
		$this->_crawler->set_order( 'added ASC' );
	}
	
	protected function init() {
		parent::init();
		$this->_withPopupId=array();
	}
	
	public function beforeSet() {
		$this->_data->setFilter('trim','clear');
		return true;
	}

	public function del() {
		if ( empty( $this->_withIds ) && empty( $this->_withPopupId ) ) {
			$_bool=false;
		} else {
			if ( !empty( $this->_withPopupId ) ) {
				Core_Sql::setExec( 'DELETE FROM '.$this->_table.' 
					WHERE popup_id IN('.Core_Sql::fixInjection( $this->_withPopupId ).')'.($this->_onlyOwner&&$this->getOwnerId( $_intId )? ' AND user_id='.$_intId:'') );
			} else {
				Core_Sql::setExec( 'DELETE FROM '.$this->_table.' 
					WHERE id IN('.Core_Sql::fixInjection( $this->_withIds ).')'.($this->_onlyOwner&&$this->getOwnerId( $_intId )? ' AND user_id='.$_intId:'') );
			}
			$_bool=true;
		}
		$this->init();
		return $_bool;
	}
}
?>