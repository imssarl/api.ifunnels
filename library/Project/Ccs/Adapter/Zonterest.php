<?php

/**
 * Создание сайтов AzonFunnels
 */
class Project_Ccs_Adapter_Zonterest {

	/**
	 * Object Core_Data
	 * @var Core_Data object
	 */
	private $_data=false;

	private $_category='All';

	private $_site='US';

	private $_adapter='';

	public function __construct(){
		$this->_adapter=new Project_Wizard( Project_Wizard::TYPE_ZONTEREST_PRO_NCSB );
	}

	public function setEntered( Core_Data $_data ){
		$this->_data=$_data;
		return $this;
	}

	public function run(){
		if( !$this->_adapter->check() ){
			if( Core_Data_Errors::getInstance()->getErrorFlowShift()=='empty_settings'){
				return Core_Data_Errors::getInstance()->setError('Sorry, we were not able to create a AzonFunnels site. Please fill in your personal details in Amazon Source Settings.');
			}
			return Core_Data_Errors::getInstance()->setError('Sorry, we were not able to create a AzonFunnels site. You don\'t have enough credits on your balance.');
		}
		if( !Core_Acs::haveRight( array('wizard'=>array('icon_zonterestpro_daschboard','icon_zonterest_daschboard')) ) ){
			return Core_Data_Errors::getInstance()->setError('Sorry, we were not able to create a AzonFunnels site. You don\'t have access.');
		}
		$_settings=new Project_Content_Settings();
		$_settings->onlyOne()->withFlgDefault()->onlySource( '9' )->getContent( $_settings );
		return $this->_adapter->setEntered(array(
			'type_create'=>Project_Wizard_Adapter_ZonterestPro::MULTI_DOMAIN,
			'main_keyword'=>$this->_data->filtered['keyword'],
			'site'=> $_settings['settings']['site'],
			'setting'=>$_settings['id'],
			'category'=>( isset( $this->_data->filtered['category'] ) ? $this->_data->filtered['category'] : $this->_category ),
		//	'promotion'=>((!empty($this->_data->filtered['promotion']))?$this->_data->filtered['promotion']:0),
		//	'promoteCount'=>((!empty($this->_data->filtered['promoteCount']))?$this->_data->filtered['promoteCount']:50),
		//	'promote_flg_type'=>((!empty($this->_data->filtered['promote_flg_type']))?$this->_data->filtered['promote_flg_type']:0)
		))->run();
	}

	public function getSiteUrl(){
		$_arrUrls= $this->_adapter->getSiteUrl();
		if( is_array( $_arrUrls ) ){
			return array_shift($_arrUrls);
		}else{
			return $_arrUrls;
		}
	}
}
?>