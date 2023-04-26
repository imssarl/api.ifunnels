<?php


/**
 * Project_Exquisite_Popups
 */

class Project_Exquisite_Popups extends Core_Data_Storage{

	protected $_table='ulp_popups';
	protected $_fields=array('id', 'user_id', 'str_id', 'title', 'width', 'height', 'options', 'blocked', 'added', 'edited');
	
	public static $defaultOptions= array(
		"title" => "",
		"width" => "640",
		"height" => "400",
		"position" => 'middle-center',
		"overlay_color" => "#333333",
		"overlay_opacity" => 0.8,
		"enable_close" => "on",
		
		"display_mode" => "inline",
		"load_mode" => 'every-time',
		"onload_delay" => 0,
		"onexit_limits" => "off",
		"onscroll_offset" => 600,
		
		"social_url" => "",
		"social_facebook_like" => "on",
		"social_google_plusone" => "on",
		"social_twitter_tweet" => "on",
		"social_linkedin_share" => "on",
		"social_margin" => 5,
		
		"social2_facebook_color" => "#3b5998",
		"social2_facebook_label" => "Subscribe with Facebook",
		"social2_google_color" => "#d34836",
		"social2_google_label" => "Subscribe with Google",

		'name_placeholder' => 'Enter your name...',
		'email_placeholder' => 'Enter your e-mail...',
		'phone_placeholder' => 'Enter your phone number...',
		'message_placeholder' => 'Enter your message...',
		'name_mandatory' => 'off',
		'phone_mandatory' => 'off',
		'message_mandatory' => 'off',
		'button_label' => 'Subscribe',
		'button_label_loading' => 'Loading...',
		'button_color' => '#0147A3',
		'input_border_color' => '#444444',
		'input_background_color' => '#FFFFFF',
		'input_background_opacity' => 0.7,
		'return_url' => '',
		'close_delay' => 0,
		'button_icon' => 'fa-noicon',
		'button_border_radius' => 2,
		'button_gradient' => 'on',
		'button_inherit_size' => 'off',
		'input_border_width' => 1,
		'input_border_radius' => 2,
		'input_icons' => 'off',
		'button_css' => '',
		'button_css_hover' => '',
		'input_css' => '',
	);

	protected $_onlyActive=false;
	protected $_withDefault=false;
	protected $_noBlocked=false;
	protected $_withStrIds=array();
	
	public function onlyActive() {
		$this->_onlyActive=true;
		return $this;
	}
	
	public function withDefault() {
		$this->_withDefault=true;
		return $this;
	}
	
	public function noBlocked() {
		$this->_noBlocked=true;
		return $this;
	}
	
	public function withStrIds( $_strIds=array() ) {
		$this->_withStrIds=$_strIds;
		return $this;
	}
	
	protected function assemblyQuery() {
		parent::assemblyQuery();
		if ( !empty( $this->_onlyActive ) ) {
			$this->_crawler->set_where( 'd.title != ""' );
		}
		if ( $this->_withDefault&&Zend_Registry::get( 'objUser' )->getId( $_intId ) ) {
			$this->_crawler->set_where( 'd.user_id = '.$_intId.' OR d.user_id = 0' );
		}
		if ( !empty( $this->_noBlocked ) ) {
			$this->_crawler->set_where( 'd.blocked != 1' );
		}
		if ( !empty( $this->_withStrIds ) ) {
			$this->_crawler->set_where( 'd.str_id IN ('.Core_Sql::fixInjection( $this->_withStrIds ).')' );
		}
		$this->_crawler->set_order( 'added ASC' );
	}
	
	protected function init() {
		parent::init();
		$this->_onlyActive=false;
		$this->_withDefault=false;
		$this->_withStrIds=false;
		$this->_noBlocked=false;
	}
	
	public function del() {
		if ( empty( $this->_withIds ) ) {
			$_bool=false;
		} else {
			Core_Sql::setExec( 'DELETE FROM '.$this->_table.' 
				WHERE id IN('.Core_Sql::fixInjection( $this->_withIds ).')'.($this->_onlyOwner&&$this->getOwnerId( $_intId )? ' AND user_id='.$_intId:'') );
			$_layers=new Project_Exquisite_Layers();
			$_bool=$_layers->withPopupId( $this->_withIds )->del();
		}
		$this->init();
		return $_bool;
	}
}
?>