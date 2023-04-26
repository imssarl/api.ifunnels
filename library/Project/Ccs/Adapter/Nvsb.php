<?php

/**
 * Создание сайтов NVSB
 */
class Project_Ccs_Adapter_Nvsb {

	/**
	 * Object Core_Data
	 * @var Core_Data object
	 */
	private $_data=false;

	private $_category='';

	private $_adapter='';

	public function __construct(){
		$this->_adapter=new Project_Wizard( Project_Wizard::TYPE_VIDEO_NVSB );
	}

	public function setEntered( Core_Data $_data ){
		$this->_data=$_data;
		return $this;
	}

	public function run(){
		if( !$this->_adapter->check() ){
			return Core_Data_Errors::getInstance()->setError('Sorry, we were not able to create a NVSB site. You don\'t have enough credits on your balance.');
		}
		if( !Core_Acs::haveRight(array('site1_nvsb'=>array('create'))) ){
			return Core_Data_Errors::getInstance()->setError('Sorry, we were not able to create a NVSB site. You don\'t have access.');
		}
		$_category=new Core_Category( 'Blog Fusion' );
		$_category->getLevel( $arrCategories, @$_GET['pid'] );
		foreach( $arrCategories as $_item ){
			if( $_item['title']=='Exclusive' ){
				$_category->getLevel( $arrChild, $_item['id'] );
				break;
			}
		}
		foreach( $arrChild as $_item ){
			if( $_item['title']=='Exclusive' ){
				$this->_category=$_item;
				break;
			}
		}
		return $this->_adapter->setEntered(array(
			'type_create'=>Project_Wizard_Adapter_Video::MULTI_DOMAIN,
			'main_keyword'=>array($this->_data->filtered['keyword']),
			'category_id'=>$this->_category['id'],
			'promotion'=>((!empty($this->_data->filtered['promotion']))?$this->_data->filtered['promotion']:0),
			'promoteCount'=>((!empty($this->_data->filtered['promoteCount']))?$this->_data->filtered['promoteCount']:50),
			'promote_flg_type'=>((!empty($this->_data->filtered['promote_flg_type']))?$this->_data->filtered['promote_flg_type']:0)
		))->run();
	}

	public function getSiteUrl(){
		$_arrUrls= $this->_adapter->getSiteUrl();
		return array_shift($_arrUrls);
	}
}
?>