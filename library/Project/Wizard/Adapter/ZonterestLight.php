<?php

class Project_Wizard_Adapter_ZonterestLight  implements Project_Wizard_Adapter_Interface{

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
	 * Адреса сайтов которые создали
	 * @var array
	 */
	private $_urls=array();

	/**
	 *
	 * Настройки amazon для проектов
	 * @var array
	 */
	private $_settings=false;

	/**
	 * Id  источника котента Amazon см. Project_Content::$source
	 * @var int
	 */
	private static $_flgSource=9;

	private static $_contentCount=0;

	private static $_jsonKeys='["0","1","2","3","4","5","6","7","8","9","10","11","12","13","14"]';

	const DOWNLOAD=3;

	/**
	 * Проверяет заполнил ли текущий пользователь персональные данные, настройки источника контента
	 * и хавтает ли у него денег на хостинг и домен.
	 * @return bool
	 */
	public function check(){
		$_settings=new Project_Content_Settings();
		$_settings->onlyOne()->withFlgDefault()->onlySource( self::$_flgSource )->getContent( $_settings );
		if( empty($_settings['settings']['affiliate'])||
			empty($_settings['settings']['api_key']) ||
			empty($_settings['settings']['secret_key']) ||
			empty($_settings['settings']['skip']) ||
			empty($_settings['settings']['length']) ||
			empty($_settings['settings']['site'])
		){
			return Core_Data_Errors::getInstance()->setError('empty_settings');
		}
		return true;
	}

	public function getSiteUrl(){
		return $this->_urls;
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

	public function getContentCount(){
		return self::$_contentCount;
	}
	/**
	 * Запускает процесс покупки домена/хостинга, создания NCSB сайта, Content Publishing проектов
	 * @return bool
	 */
	public function run(){
		if ( !Core_Data_Errors::getInstance()->setData( $this->_data->setFilter('trim','clear') )->setValidators( array(
			'main_keyword'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ),
			'category'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ),
			'site'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ),
		) )->isValid() ) {
			return Core_Data_Errors::getInstance()->setError('Incorrect entered data.');
		}
		$_arrKeywords=$this->_data->filtered['main_keyword'];
		foreach($_arrKeywords as $_keyword ){
			$this->_place=false;
			$this->_data->filtered['main_keyword']=$_keyword;
			if( !$this->prepare() ){
				return Core_Data_Errors::getInstance()->setError('Can\'t prepare data.');
			}
			if( $this->_data->filtered['type_create']==self::DOWNLOAD  ){
				if( !$this->downloadNcsbSite() ){
					return Core_Data_Errors::getInstance()->setError('Can\'t create NCSB site.');
				}
				return true;
			}
		}
		return true;
	}

	/**
	 * Подготавливает _place если тип MULTI_DOMAIN
	 * @return bool
	 */
	private function generateDomain(){}

	/**
	 * Подготовка необходимых данных
	 * @return bool
	 */
	private function prepare(){
		switch( $this->_data->filtered['site'] ){
			case 'FR': $this->_templateId=Core_Sql::getCell('SELECT id FROM es_templates WHERE flg_type='.Project_Sites::NCSB.' AND filename LIKE \'amazontemplate_fr.%\'' ); break;
			case 'DE': $this->_templateId=Core_Sql::getCell('SELECT id FROM es_templates WHERE flg_type='.Project_Sites::NCSB.' AND filename LIKE \'amazontemplate_de.%\'' ); break;
			case 'ES': $this->_templateId=Core_Sql::getCell('SELECT id FROM es_templates WHERE flg_type='.Project_Sites::NCSB.' AND filename LIKE \'amazontemplate_es.%\'' ); break;
			case 'IT': $this->_templateId=Core_Sql::getCell('SELECT id FROM es_templates WHERE flg_type='.Project_Sites::NCSB.' AND filename LIKE \'amazontemplate_it.%\'' ); break;
			case 'CN': $this->_templateId=Core_Sql::getCell('SELECT id FROM es_templates WHERE flg_type='.Project_Sites::NCSB.' AND filename LIKE \'amazontemplate_cn.%\'' ); break;
			case 'JP': $this->_templateId=Core_Sql::getCell('SELECT id FROM es_templates WHERE flg_type='.Project_Sites::NCSB.' AND filename LIKE \'amazontemplate_jp.%\'' ); break;
			case 'US':
			default: $this->_templateId=Core_Sql::getCell('SELECT id FROM es_templates WHERE flg_type='.Project_Sites::NCSB.' AND filename LIKE \'Default.%\'' ); break;
		}
		if( empty($this->_templateId) ){
			return false;
		}
		self::$_contentCount=0;
		$_settings=new Project_Content_Settings();
		$_settings->onlyOne()->withFlgDefault()->onlySource( self::$_flgSource )->getContent( $this->_settings );
		$this->_settings['settings']['site']=$this->_data->filtered['site'];
		$this->_settings['settings']['category']=$this->_data->filtered['category'];
		$this->_settings['settings']['keywords']=$this->_data->filtered['main_keyword'];
		$this->_settings['settings']['marketplacedomain']=$this->_data->filtered['marketplacedomain'];
		$this->_settings['settings']['flg_source']=self::$_flgSource;
		$this->_settings['settings']['template']=2;
		$_content=new Project_Content_Adapter_Amazon();
		if( !$_content->withPaging(array('page'=>1))->setSettings( $this->_settings['settings'] )->getList( $_temp1 )->checkEmpty() ){
			$_arrErrors=Core_Data_Errors::getInstance()->getErrors();
			if( empty($_arrErrors['errFlow']) ){
				return Core_Data_Errors::getInstance()->setError('There is no relevant content found for your keyword.');
			}
			return Core_Data_Errors::getInstance()->setError('Please re-check your Amazon affiliate parameters to make sure those are correct.');
		}
		foreach($_temp1 as $_item ){
			$_arrIndex[]=$_item['id'];
		}
		self::$_contentCount=count($_arrIndex);
		self::$_jsonKeys=json_encode($_arrIndex);
		if( !$_content->withPaging( array('page'=>2) )->setSettings( $this->_settings['settings'] )->getList( $_temp2 )->checkEmpty() ){
			return true;
		}
		foreach($_temp2 as $_item ){
			$_arrIndex[]=$_item['id'];
		}
		if( count($_arrIndex)>16 ){
			$_arrIndex=array_splice($_arrIndex,0,16);
		}
		self::$_contentCount=count($_arrIndex);
		self::$_jsonKeys=json_encode($_arrIndex);
		return true;
	}

	/**
	 * Покупка домена, создание хостинга.
	 * @return bool
	 */
	private function registerDomain(){}

	/**
	 * Создание NCSB сайта
	 * @return bool
	 */
	private function createNcsbSite(){}

	/**
	 * Создание NCSB сайта
	 * @return bool
	 */
	private function downloadNcsbSite(){
		$_ncsb=new Project_Sites( Project_Sites::NCSB_DOWNLOAD );
		if(!$_path2archiv=$_ncsb->setEntered(array(
			'template_id'=>$this->_templateId,
			'main_keyword'=>ucwords($this->_data->filtered['main_keyword']),
			'google_analytics'=>Core_Users::$info['adsenseid'],
			'navigation_length'=>7,
			'flg_snippet'=>0,
			'contentIds'=>json_decode(self::$_jsonKeys),
			'zonterest'=>Core_Sql::getCell('SELECT id FROM co_snippets WHERE title=\'Amazon Zonterest AD\'')
		))->setAmazonSettings( $this->_settings['settings'] )->set()){
			return false;
		}
		$this->_urls[]=trim($_path2archiv,'.');
		return true;
	}

	/**
	 * Создание All at once проекта.
	 * @return bool
	 */
	public function createAllAtOnce(){}

	/**
	 * Создание проекта с рассписанием: каждый день по 1 продукту на 3 месяца
	 * @return bool
	 */
	public function createSchedule(){}

	/**
	 * Интеграция с SYNND
	 * @return bool
	 */
	public function createSYNND(){}
}
?>