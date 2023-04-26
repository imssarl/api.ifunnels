<?php

class Project_Wizard_Adapter_Zonterest implements Project_Wizard_Adapter_Interface{

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

	private $_start=0;
	private $_withLogger=true;
	private $_logger=false;
	
	function __destruct() {
		if( $this->_withLogger && $this->_logger!=false ){
			$this->_logger->info('End -----------------------------------------------------------------------------------------------------' );
		}
	}
	
	/**
	 * Проверяет заполнил ли текущий пользователь персональные данные, настройки источника контента
	 * и хавтает ли у него денег на хостинг и домен.
	 * @return bool
	 */
	public function check( $_flgCheckBuns=true ){
		$_settings=new Project_Content_Settings();
		$_settings->onlyOne()->withFlgDefault()->onlySource( self::$_flgSource )->getContent( $_arrSettings );
		if( empty($_arrSettings['settings']['affiliate'])||
			empty($_arrSettings['settings']['api_key']) ||
			empty($_arrSettings['settings']['secret_key']) ||
//			empty($_arrSettings['settings']['skip']) ||
			empty($_arrSettings['settings']['length']) ||
			empty($_arrSettings['settings']['site'])
		){
			return Core_Data_Errors::getInstance()->setError('empty_settings');
		}
		if( $_flgCheckBuns===false ){
			return true;
		}
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
		return $this->_urls;
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
			'category'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' )
		) )->isValid() ){
			return Core_Data_Errors::getInstance()->setError('Incorrect entered data.');
		}
		$_arrKeywords=array();
		if( !is_array( $this->_data->filtered['main_keyword'] ) ){
			$_arrKeywords=array( $this->_data->filtered['main_keyword'] );
		}else{
			$_arrKeywords=$this->_data->filtered['main_keyword'];
		}
		$_defaultDir=$this->_data->filtered['ftp_directory'];
		foreach( $_arrKeywords as $_keyword ){
			if( empty( $_keyword ) ){
				continue;
			}
			if( count( $_arrKeywords ) > 1 ){
				$this->_data->setElement( 'ftp_directory', '/'.$this->randomDirName().'/' );
			}else{
				$this->_data->setElement( 'ftp_directory', $_defaultDir );
			}
			$this->_data->setElement( 'main_keyword', $_keyword );
			$this->_data->setFilter();
			/*---------------------------------------------*/
			if( $this->_withLogger ){
				$_logerTiming=time();
				$_writer=new Zend_Log_Writer_Stream( Zend_Registry::get('config')->path->absolute->logfiles.'Zonterest/log_'.$_logerTiming.'.log' );
				$_writer->setFormatter( new Zend_Log_Formatter_Simple("%timestamp% %priorityName% (%priority%): %message%\r\n") );
				$this->_logger=new Zend_Log( $_writer );
				$this->_start=microtime(true);
				$this->_logger->info( serialize( $this->_data->filtered ).'-----------------------------------------------------------------------------------------------------' );
			}
			/*---------------------------------------------*/
			if( !$this->prepare() ){
				return Core_Data_Errors::getInstance()->setError('Can\'t prepare data.');
			}
			/*---------------------------------------------*/
			if( $this->_withLogger ){
				$this->_start=microtime(true)-$this->_start;
				$this->_logger->info('prepare '.$this->_start );
				$this->_start=microtime(true);
			}
			/*---------------------------------------------*/
			if( !$this->registerDomain() ){
				return Core_Data_Errors::getInstance()->setError('Can\'t create domain & hosting.');
			}
			/*---------------------------------------------*/
			if( $this->_withLogger ){
				$this->_start=microtime(true)-$this->_start;
				$this->_logger->info('registerDomain '.$this->_start );
				$this->_start=microtime(true);
			}
			/*---------------------------------------------*/

			if( !$this->createNcsbSite() ){
				return Core_Data_Errors::getInstance()->setError('Can\'t create NCSB site.');
			}
			/*---------------------------------------------*/
			if( $this->_withLogger ){
				$this->_start=microtime(true)-$this->_start;
				$this->_logger->info('createNcsbSite '.$this->_start );
				$this->_start=microtime(true);
			}
			/*---------------------------------------------*/
			if( !$this->createAllAtOnce() ){
				return Core_Data_Errors::getInstance()->setError('Can\'t create all at once project.');
			}
			/*---------------------------------------------*/
			if( $this->_withLogger ){
				$this->_start=microtime(true)-$this->_start;
				$this->_logger->info('createAllAtOnce '.$this->_start );
				$this->_start=microtime(true);
			}
			/*---------------------------------------------*/
			sleep(1);
			if( !$this->createSchedule() ){
				return Core_Data_Errors::getInstance()->setError('Can\'t create schedule project.');
			}
			/*---------------------------------------------*/
			if( $this->_withLogger ){
				$this->_start=microtime(true)-$this->_start;
				$this->_logger->info('createSchedule '.$this->_start );
				$this->_start=microtime(true);
			}
			/*---------------------------------------------*/
			if( !$this->createSYNND() ){
				return Core_Data_Errors::getInstance()->setError('Can\'t create SYNND.');
			}
			/*---------------------------------------------*/
			if( $this->_withLogger ){
				$this->_start=microtime(true)-$this->_start;
				$this->_logger->info('createSYNND '.$this->_start );
				$this->_start=microtime(true);
			}
			/*---------------------------------------------*/
		}
		return true;
	}

	/**
	 * Подготовка необходимых данных
	 * @return bool
	 */
	private function prepare(){
		$this->_templateId=Core_Sql::getCell('SELECT id FROM es_templates WHERE flg_type='.Project_Sites::NCSB.' AND filename LIKE \'Default%\'' );
		$_templates=new Project_Sites_Templates( Project_Sites::NCSB );
		$_templates->withRight()->toSelect()->getList( $arrTemplates );
		$_flgHaveDefault=$_flgHaveFree=false;
		foreach( $arrTemplates as $templateId=>$templateName ){
			if( $templateName=='Default' ){
				$_flgHaveDefault=$templateId;
			}
			if( $templateName=='Default_free' ){
				$_flgHaveFree=$templateId;
			}
		}
		if( $_flgHaveFree !== false ){
			$this->_templateId=$_flgHaveFree;
		}
		if( $_flgHaveDefault !== false ){
			$this->_templateId=$_flgHaveDefault;
		}
		if( empty($this->_templateId) ){
			return false;
		}
		$_category=new Core_Category( 'Blog Fusion' );
		$_category->getLevel( $arrCategories, @$_GET['pid'] );
		foreach( $arrCategories as $_item ){
			if( $_item['title']=='Zonterest' ){
				$_category->getLevel( $arrChild, $_item['id'] );
				break;
			}
		}
		foreach( $arrChild as $_item ){
			if( $_item['title']=='Zonterest' ){
				$this->_category=$_item;
				break;
			}
		}
		if( empty($this->_category) ){
			return Core_Data_Errors::getInstance()->setError('Can\'t find category');
		}
		self::$_contentCount=0;
		$_settings=new Project_Content_Settings();
		$_settings->onlyOne()->withIds( $this->_data->filtered['setting'] )->getContent( $this->_settings );
		if( empty( $this->_settings ) ){
			$_settings->withFlgDefault()->getContent( $this->_settings );
		}
		$this->_settings['settings']['category']=$this->_data->filtered['category'];
		$this->_settings['settings']['keywords']=$this->_data->filtered['main_keyword'];
		$this->_settings['settings']['template']=2;
		$_content=new Project_Content_Adapter_Amazon();
		if( !$_content->withPaging(array('page'=>1))->setSettings( $this->_settings['settings'] )->getList( $_temp1 )->checkEmpty() ){
			$_arrErrors=Core_Data_Errors::getInstance()->getErrors();
			if( empty($_arrErrors['errFlow']) ){
				return Core_Data_Errors::getInstance()->setError('There is no relevant content found for your keyword.');
			}
			Core_Data_Errors::getInstance()->setError('Please re-check your Amazon affiliate parameters to make sure those are correct.');
			return false;
		}
		foreach($_temp1 as $_item ){
			$_arrIndex[]=$_item['asin'];
		}
		self::$_contentCount=count($_arrIndex);
		self::$_jsonKeys=json_encode($_arrIndex);
		if( !$_content->withPaging( array('page'=>2) )->setSettings( $this->_settings['settings'] )->getList( $_temp2 )->checkEmpty() ){
			return true;
		}
		foreach($_temp2 as $_item ){
			$_arrIndex[]=$_item['asin'];
		}
		if( count($_arrIndex)>15 ){
			$_arrIndex=array_splice($_arrIndex,0,15);
		}
		self::$_contentCount=count($_arrIndex);
		self::$_jsonKeys=json_encode($_arrIndex);
		return true;
	}

	
	function randomDirName( $n=1 ){
		$letters="qwertyuiopasdfghjklzxcvbnm";
		$numbers="1234567890";
		$dirName='';
		$dirName.=$letters[mt_rand(0,strlen($letters)-1)];
		$dirName.=$letters[mt_rand(0,strlen($letters)-1)];
		$dirName.=$letters[mt_rand(0,strlen($letters)-1)];
		$dirName.=$numbers[mt_rand(0,strlen($numbers)-1)];
		$dirName.=$numbers[mt_rand(0,strlen($numbers)-1)];
		$_test=get_headers( 'http://amazideas.net/'.$dirName );
		if( $n>10 ){
			throw new Project_Placement_Exception( Core_Errors::DEV.'|no empty dir names' );
		}
		if( $_test[0]== 'HTTP/1.1 200 OK' ){
			$n++;
			$dirName=$this->randomDirName( $n );
		}
		return $dirName;
	}
	/**
	 * Покупка домена, создание хостинга.
	 * @return bool
	 */
	private function registerDomain(){
		if( isset( $this->_data->filtered['domain_http'] ) && $this->_data->filtered['domain_http'] == 'amazideas.net' ){
			$this->_place['id']=8484; // это наш amazideas.net раньше было 
			return !empty($this->_place);
		}else{
			$this->_place['id']=$this->_data->filtered['placement_id'];
			return !empty($this->_place);
		}
		//====================================
		if( Core_Users::$info['email']=='cnmtest2@cnmbeta.info' ){
			$_placement=new Project_Placement();
			if( !$_placement->setEntered(array(
				'domain_http'=>$this->_data->filtered['domain_http'],
				'flg_type'=>Project_Placement::LOCAL_HOSTING,
				'flg_auto'=>0,
			))->set() ){
				return false;
			}
		} else {
			$_placement=new Project_Placement();
			if( !$_placement->setEntered(array(
				'domain_http'=>$this->_data->filtered['domain_http'],
				'flg_type'=>Project_Placement::LOCAL_HOSTING_DOMEN,
				'flg_auto'=>0,
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
			'ftp_directory'=>(!empty($this->_data->filtered['ftp_directory'])&&$this->_data->filtered['ftp_directory']!='/')?$this->_data->filtered['ftp_directory']:'/error/',
			'ftp_root'=>(!empty($this->_data->filtered['ftp_directory'])&&$this->_data->filtered['ftp_directory']!='/')?0:1,
			'template_id'=>$this->_templateId,
			'category_id'=>$this->_category['id'],
			'main_keyword'=>ucwords($this->_data->filtered['main_keyword']),
			'google_analytics'=>Core_Users::$info['adsenseid'],
			'navigation_length'=>7,
			'flg_snippet'=>0,
			'zonterest'=>Core_Sql::getCell('SELECT id FROM co_snippets WHERE title=\'Amazon Zonterest AD\'')
		))->setAmazonSettings( $this->_settings['settings'] )->set() ){
			return false;
		}
		$_ncsb->getEntered( $this->_site );
		$this->_urls[]=$this->_site['url'];
		return !empty($this->_site);
	}

	/**
	 * Создание All at once проекта.
	 * @return bool
	 */
	public function createAllAtOnce(){
		$_publisching=new Project_Publisher();
		unset( $this->_settings['settings']['asin'] );
		if( !$_publisching->setData( array(
			'flg_type'=>2,
			'flg_source'=>self::$_flgSource,
			'flg_mode'=>1,
			'flg_rewriting'=>0,
			'selectdepth'=>0,
			'title'=>'Zonterest Wizard: Content for site '. $this->_site['main_keyword'],
			'category_id'=>$this->_category['id'],
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
		unset( $this->_settings['settings']['asin'] );
		if( !$_publisching->setData( array(
			'flg_type'=>2,
			'flg_source'=>self::$_flgSource,
			'flg_mode'=>0,
			'flg_status'=>0,
			'flg_rewriting'=>0,
			'selectdepth'=>1,
			'title'=>'Zonterest Wizard: Content for site '. $this->_site['main_keyword'],
			'category_id'=>$this->_category['id'],
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