<?php

class Project_Wizard_Adapter_Content implements Project_Wizard_Adapter_Interface{

	/**
	 * Данные от пользователя
	 * @var Core_Data object
	 */
	private $_data=false;

	/**
	 * Домен куда ставим сайт
	 * @var array
	 */
	private $_place=false;

	/**
	 * Шаблон для сайта
	 * @var int
	 */
	private $_templateId=false;

	/**
	 * Сайт с которым работаем
	 * @var array
	 */
	private $_site=false;

	/**
	 *
	 * Настройки amazon для проектов
	 * @var array
	 */
	private $_settings=false;

	/**
	 * Id  источника котента Pure Articles см. Project_Content::$source
	 * @var int
	 */
	private static $_flgSource=4;

	private static $_contentCount=0;

	private static $_jsonKeys='["0","1","2","3","4","5","6","7","8","9"]';

	/**
	 * Проверяет заполнил ли текущий пользователь персональные данные, настройки источника контента
	 * и хавтает ли у него денег на хостинг и домен.
	 * @return bool
	 */
	public function check(){
		$_buns=new Core_Payment_Buns();
		$_buns->withSysName('Project_Placement_Hosting')->onlyOne()->getList( $arrHosting );
		$_buns->withSysName('Project_Placement_Domen')->onlyOne()->getList( $arrDomain );
		$_purse=new Core_Payment_Purse();
		if(  Core_Payment_Purse::getAmount()<($arrDomain['credits']+$arrHosting['credits']) ){
			return  Core_Data_Errors::getInstance()->setError('empty_credits');
		}
		return true;
	}

	/**
	 * Устанавливает входные данные.
	 * @param Core_Data $data
	 * @return bool
	 */
	public function setEntered( Core_Data $data ){
		$this->_data=$data;
		return true;
	}

	public function getSiteUrl(){
		return $this->_site['url'];
	}

	public function getContentCount(){
		return self::$_contentCount;
	}

	/**
	 * Запускает процесс покупки домена/хостинга, создания NCSB сайта, Content Publishing проектов
	 * @return bool
	 */
	public function run(){
		if ( !Core_Data_Errors::getInstance()->setData( $this->_data->setFilter('trim','clear') )->setValidators( array(
			'domain_http'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ),
			'main_keyword'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ),
			'category_id'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ),
		) )->isValid() ) {
			return Core_Data_Errors::getInstance()->setError('Incorrect entered data');
		}
		if( !$this->prepare() ){
			return Core_Data_Errors::getInstance()->setError('Can\'t prepare data');
		}
		if( !$this->registerDomain() ){
			return Core_Data_Errors::getInstance()->setError('Can\'t create domain & hosting');
		}
		if( !$this->createNcsbSite() ){
			return Core_Data_Errors::getInstance()->setError('Can\'t create NCSB site');
		}
		if( !$this->createAllAtOnce() ){
			return Core_Data_Errors::getInstance()->setError('Can\'t create all at once project');
		}
		if( !$this->createSchedule() ){
			return Core_Data_Errors::getInstance()->setError('Can\'t create schedule priject');
		}
		if( !$this->createSYNND() ){
			return Core_Data_Errors::getInstance()->setError('Can\'t create SYNND');
		}
		return true;
	}

	/**
	 * Подготовка необходимых данных
	 * @return bool
	 */
	private function prepare(){
		$this->_templateId=Core_Sql::getCell('SELECT id FROM es_templates WHERE flg_type='.Project_Sites::NCSB.' AND filename LIKE \'blogfeel-Black%\' ORDER BY RAND()' );
		if( empty($this->_templateId) ){
			return false;
		}
		self::$_contentCount=0;
		$_settings=new Project_Content_Settings();
		$_settings->onlyOne()->withFlgDefault()->onlySource( self::$_flgSource )->getContent( $this->_settings );
		$this->_settings['settings']['keywords']=$this->_data->filtered['main_keyword'];
		$this->_settings['settings']['flg_language']=1;
		$this->_settings['settings']['category_pid']=false;
		$this->_settings['settings']['category_id']=false;
		$_content=new Project_Content_Adapter_Purearticles();
		if( !$_content->setLimited(10)->withPaging( array( 'page'=>1 ) )->setSettings( $this->_settings['settings'] )->getList( $_temp )->checkEmpty() ){
			return Core_Data_Errors::getInstance()->setError('Can\'t find content');
		}
		foreach( $_temp as $_item ){
			$_contentIds[]=$_item['id'];
		}
		self::$_jsonKeys=json_encode( $_contentIds );
		self::$_contentCount=count( $_contentIds );
		return true;
	}

	/**
	 * Покупка домена, создание хостинга.
	 * @return bool
	 */
	private function registerDomain(){
		if( Core_Users::$info['email']=='cnmtest2@cnmbeta.info' ){
			$_placement=new Project_Placement();
			if( !$_placement->setEntered(array(
				'domain_http'=>$this->_data->filtered['domain_http'],
				'flg_type'=>Project_Placement::LOCAL_HOSTING,
			))->set() ){
				return false;
			}
		} else {
			$_placement=new Project_Placement();
			if( !$_placement->setEntered(array(
				'domain_http'=>$this->_data->filtered['domain_http'],
				'flg_type'=>Project_Placement::LOCAL_HOSTING_DOMEN,
			))->set() ){
				return false;
			}
		}
		$_placement->getEntered( $this->_place );
		return !empty($this->_place);
	}

	/**
	 * Создание NCSB сайта
	 * @return bool
	 */
	private function createNcsbSite(){
		sleep(3); // ждем, если репликация будет работать медленно, то сайт не создастся
		$_ncsb=new Project_Sites( Project_Sites::NCSB );
		if( !$_ncsb->setEntered(array(
			'placement_id'=>$this->_place['id'],
			'ftp_directory'=>'/',
			'ftp_root'=>1,
			'template_id'=>$this->_templateId,
			'category_id'=>$this->_data->filtered['category_id'],
			'main_keyword'=>ucwords(strtolower($this->_data->filtered['main_keyword'])),
			'google_analytics'=>Core_Users::$info['adsenseid'],
			'navigation_length'=>7,
			'flg_snippet'=>'no'
		))->set() ){
			return false;
		}
		$_ncsb->onlyIds()->withOrder('d.id--up')->getList( $arrIds );
		$_ncsb->withIds( $arrIds[0] )->onlyOne()->getList($this->_site );
		return !empty($this->_site);
	}

	/**
	 * Создание All at once проекта.
	 * @return bool
	 */
	public function createAllAtOnce(){
		$_publisching=new Project_Publisher();
		if( !$_publisching->setData( array(
			'flg_type'=>2,
			'flg_source'=>self::$_flgSource,
			'flg_mode'=>1,
			'flg_rewriting'=>0,
			'selectdepth'=>0,
			'title'=>'Conetnt Wizard: Content for site '. $this->_site['main_keyword'],
			'category_id'=>$this->_data->filtered['category_id'],
			'flg_posting'=>3,
			'arrSiteIds'=>array( Project_Sites::NCSB=>array($this->_site['id']=>array('site_id'=>$this->_site['id'])) ),
			'flg_run'=>1,
			'start'=>time(),
			'end'=>time(),
			'mastersite_id'=>$this->_site['id'],
			'jsonContentIds'=>self::$_jsonKeys,
			'settings'=>$this->_settings['settings']
		) )->set() ){
			return false;
		}
		$_run=new Project_Publisher_Arrange();
		$_run->publishImmediately( $_publisching->getData() );
		return true;
	}

	/**
	 * Создание проекта с рассписанием: каждый день по 1 продукту на 3 месяца
	 * @return bool
	 */
	public function createSchedule(){
		$_publisching=new Project_Publisher();
		if( !$_publisching->setData( array(
			'flg_type'=>2,
			'flg_source'=>self::$_flgSource,
			'flg_mode'=>0,
			'flg_status'=>0,
			'flg_rewriting'=>0,
			'selectdepth'=>1,
			'title'=>'Content Wizard: Content for site '. $this->_site['main_keyword'],
			'category_id'=>$this->_data->filtered['category_id'],
			'flg_posting'=>3,
			'arrSiteIds'=>array( Project_Sites::NCSB=>array($this->_site['id']=>array('site_id'=>$this->_site['id'])) ),
			'post_every'=>1,
			'post_num'=>1,
			'start'=>time(),
			'end'=>time()+(60*60*24*30*3),
			'mastersite_id'=>$this->_site['id'],
			'settings'=>$this->_settings['settings'],
			'counter'=>self::$_contentCount+1
		) )->set() ){
			return false;
		}
		$_data=$_publisching->getData();
		Project_Publisher::status(Project_Publisher::$stat['inProgress'],$_data->filtered['id']);
		return true;
	}

	/**
	 * Интеграция с SYNND
	 * @return bool
	 */
	public function createSYNND(){
		return true;
	}
}
?>