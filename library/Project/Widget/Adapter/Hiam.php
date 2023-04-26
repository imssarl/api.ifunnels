<?php
/**
 * WorkHorse Framework
 *
 * @category Project
 * @package Project_Widget_Adapter
 * @copyright Copyright (c) 2009-2011, web2innovation
 * @author Pavel Livinskij <pavel.livinskij@gmail.com>
 * @date 16.08.2011
 * @version 1.0
 */


/**
 * Управление частями сниппета
 *
 * @category Project
 * @package Project_Widget_Adapter
 * @copyright Copyright (c) 2009-2011, web2innovation
 */

class Project_Widget_Adapter_Hiam implements Project_Widget_Adapter_Interface {

	private $_settings=array();
	private $_path='';
	private $_cookieTime=10;

	public function __construct() {
		$this->_path=Zend_Registry::get( 'config' )->path->relative->source.'widget'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR;
	}
	public function get(){
		if( empty($this->_settings['id']) ){
			return false;
		}
		$_arrId = explode('-',$this->_settings['id']);
		Project_Widget_Mutator::decodeArray( $_arrId );
		foreach($_arrId as $_id ){
			$this->_settings['id']=$_id;
			// TODO удалить после того как будет отключен старый сервис.
			if( $this->_settings['old'] ){
				$this->statOldServices();
			}
			$arrOut['host']='https://'.Zend_Registry::get( 'config' )->engine->project_domain;
			$arrOut['dir']=Zend_Registry::get( 'config' )->path->html->user_files . 'hiam/';
			$this->getItem( $arrOut['items'][] );
		}
		Core_View::factory( Core_View::$type['one'] )
				->setTemplate( $this->_path.'widget_hiam_view.tpl' )
				->setHash( $arrOut )
				->parse()
				->show();
		die();
	}

	/**
	 * Временный метод, для отслеживания статистики использования старого сервиса.
	 * Удалить после отключения старого сервиса.
	 * @return void
	 */
	public function statOldServices(){
		$_host='-//-';
		$_path='-//-';
		if(!empty($_SERVER['HTTP_REFERER'])){
			$_tmp=parse_url($_SERVER['HTTP_REFERER']);
			$_host=$_tmp['host'];
			$_path=$_tmp['path'];
		}
		$_ip=$_SERVER['REMOTE_ADDR'];
		Core_Sql::setExec('INSERT INTO stat_old_services ( flg_type, `count`,ip, host ,remote_url, added ) VALUES (1,1,\''.$_ip.'\','.Core_Sql::fixInjection($_host).','.Core_Sql::fixInjection($_path).','.time().') ON DUPLICATE KEY UPDATE `count`=`count`+1,remote_url='.Core_Sql::fixInjection($_path));
	}

	public function set(){
		if( empty($this->_settings['id']) ){
			return false;
		}
		$_tmp=explode('-',$this->_settings['id']);
		if( empty($_tmp[0]) || empty($_tmp[1]) ){
			return false;
		}
		$_campaignId=Project_Widget_Mutator::decode($_tmp[0]);
		$_trackId=Project_Widget_Mutator::decode($_tmp[1]);
		if( !empty($_COOKIE[ 'hiam-effect-'.$_campaignId ]) && $_COOKIE[ 'hiam-effect-'.$_campaignId ]==$_campaignId ){
			return false;
		}
		$_campaign=new Project_Widget_Adapter_Hiam_Campaign();
		$_arrUrl=$_campaign->getTrackUrl( $_campaignId, $_trackId );
		$_campaign->setEntered( array(
			'campaign_id'=> $_campaignId,
			'trackurl_id'=> $_trackId,
			'flg_type'	 => 1,
			'ip_address' => $_SERVER['REMOTE_ADDR'],
			'url_shown'	 => (empty($_SERVER['HTTP_REFERER']))?'':$_SERVER['HTTP_REFERER'],
			'added'		 => time()
		))->setClick();
		setcookie('hiam-set-'.$_campaignId,$_campaignId,time() + $this->_cookieTime );
		header( 'Location: '.$_arrUrl['url'] );
	}

	/**
	 * Подсчет эффективности с thenk you page
	 * @return bool
	 */
	public function effective(){
		if( empty($this->_settings['id']) ){
			return false;
		}
		$this->_settings['id']=Project_Widget_Mutator::decode($this->_settings['id']);
		if( !empty($_COOKIE[ 'hiam-effect-'.$this->_settings['id'] ]) && $_COOKIE[ 'hiam-effect-'.$this->_settings['id'] ]==$this->_settings['id'] ){
			return false;
		}
		if( !empty($this->_settings['split']) ){
			$_split=new Project_Widget_Adapter_Hiam_Split();
			$_campaignId=$_split->getCampaign( $this->_settings['id'] );
		} else {
			$_campaignId=$this->_settings['id'];
		}
		$_campaign=new Project_Widget_Adapter_Hiam_Campaign();
		if( !$_campaign->onlyOne()->withIds( $_campaignId )->getList( $_res )->checkEmpty() ){
			return false;
		}
		$_campaign->setEntered( array(
			'campaign_id'=> $_campaignId,
			'flg_type'	 => 2,
			'ip_address' => $_SERVER['REMOTE_ADDR'],
			'url_shown'	 => (empty($_SERVER['HTTP_REFERER']))?'':$_SERVER['HTTP_REFERER'],
			'added'		 => time()
		))->setEffectiveness();
		setcookie('hiam-effect-'.$_campaignId,$_campaignId,time() + $this->_cookieTime );
		return true;
	}


	public function checkKey( $_strKey ){
		return true;
	}

	public function setSettings( $_arrSettings ){
		$this->_settings=$_arrSettings;
		return $this;
	}

	private function getItem( &$arrItem ){
		if( !empty($this->_settings['split']) ){
			$_split=new Project_Widget_Adapter_Hiam_Split();
			$_id=$_split->getCampaign( $this->_settings['id'] );
		} else {
			$_id=$this->_settings['id'];
		}
		$_campaign=new Project_Widget_Adapter_Hiam_Campaign();
		if( !$_campaign->withRights(array('services_@_widgets_hiam_campaign'))->onlyStarted()->onlyOne()->withIds( $_id )->getList( $arrItem )->checkEmpty() ){
			return false;
		}
		$_files=new Project_Files_Hiam();
		$_files->getPaths( $arrItem );
		$_campaign->setViews( $_id );
		$arrItem['content_slide_parsed']=preg_replace(array("@\s@si","@\'@si"),array(' ','\\\''),$arrItem['content_slide_parsed']);
		$arrItem['content_fix_parsed']=preg_replace(array("@\s@si","@\'@si"),array(' ','\\\''),$arrItem['content_fix_parsed']);
		return true;
	}
}
?>