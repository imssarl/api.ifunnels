<?php

class Project_Wizard_Adapter_ClickbankPro implements Project_Wizard_Adapter_Interface{

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
	 * Id  источника котента Clickbank см. Project_Content::$source
	 * @var int
	 */
	private static $_flgSource=10;

	private static $_contentCount=0;

	private static $_jsonKeys='[]';

	const EXIST_DOMAIN=0,NEW_DOMAIN=1,MULTI_DOMAIN=2;

	/**
	 * Проверяет заполнил ли текущий пользователь персональные данные, настройки источника контента
	 * и хавтает ли у него денег на хостинг и домен.
	 * @return bool
	 */
	public function check(){
		$_settings=new Project_Content_Settings();
		$_settings->onlyOne()->onlySource( self::$_flgSource )->getContent( $_settings );
		if( empty($_settings['settings']['affiliate_id']) ){
			return Core_Data_Errors::getInstance()->setError('empty_settings');
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
			'main_keyword'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ),
			'flg_language'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ),
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
			if( $this->_data->filtered['type_create']==self::EXIST_DOMAIN ){
				$_place=new Project_Placement();
				$_place->withIds($this->_data->filtered['placement_id'])->onlyOne()->getList( $this->_place );
			} elseif( $this->_data->filtered['type_create']==self::MULTI_DOMAIN ){
					$this->_data->filtered['domain_http']=$this->generateDomain();
					if( $this->_data->filtered['domain_http']===false||!$this->registerDomain() ){
						return Core_Data_Errors::getInstance()->setError('Can\'t create domain & hosting.');
					}
			} else {
				if( !$this->registerDomain() ){
					return Core_Data_Errors::getInstance()->setError('Can\'t create domain & hosting.');
				}
			}
			if( !$this->createNcsbSite() ){
				return Core_Data_Errors::getInstance()->setError('Can\'t create NCSB site.');
			}
			if( !$this->createAllAtOnce() ){
				return Core_Data_Errors::getInstance()->setError('Can\'t create all at once project.');
			}
			if( !$this->createSchedule() ){
				return Core_Data_Errors::getInstance()->setError('Can\'t create schedule project.');
			}
			if( !$this->createSYNND() ){
				return Core_Data_Errors::getInstance()->setError('Can\'t create SYNND.');
			}
		}
		return true;
	}

	/**
	 * Подготавливает _place если тип MULTI_DOMAIN
	 * @return bool
	 */
	private function generateDomain(){
		$_domain=new Project_Wizard_Domain( Project_Wizard_Domain_Rules::R_CLICKBANKPRO );
		$_arrDomains=$_domain->setWord( $this->_data->filtered['main_keyword'] )->get();
		if( !empty($_arrDomains['x8']) ){
			while(!empty($_arrDomains['x8'])){
				$_avalibalDomain=array_shift($_arrDomains['x8']);
				if( $_domain->check($_avalibalDomain) ){
					return $_avalibalDomain;
				}
			}
		}
		if( !empty($_arrDomains['x12']) ){
			while(!empty($_arrDomains['x12'])){
				$_avalibalDomain=array_shift($_arrDomains['x12']);
				if( $_domain->check($_avalibalDomain) ){
					return $_avalibalDomain;
				}
			}
		}
		if( !empty($_arrDomains['x36']) ){
			while(!empty($_arrDomains['x36'])){
				$_avalibalDomain=array_shift($_arrDomains['x36']);
				if( $_domain->check($_avalibalDomain) ){
					return $_avalibalDomain;
				}
			}
		}
		return false;
	}

	/**
	 * Подготовка необходимых данных
	 * @return bool
	 */
	private function prepare(){
		$this->_templateId=Core_Sql::getCell('SELECT id FROM es_templates WHERE flg_type='.Project_Sites::NCSB.' AND filename LIKE \'blogfeel-DarkRed%\' ORDER BY RAND()' );
		if( empty($this->_templateId) ){
			return false;
		}
		$_category=new Core_Category( 'Blog Fusion' );
		$_category->getLevel( $arrCategories, @$_GET['pid'] );
		foreach( $arrCategories as $_item ){
			if( $_item['title']=='Clickbank' ){
				$_category->getLevel( $arrChild, $_item['id'] );
				break;
			}
		}
		foreach( $arrChild as $_item ){
			if( $_item['title']=='Clickbank' ){
				$this->_category=$_item;
				break;
			}
		}
		if( empty($this->_category) ){
			return Core_Data_Errors::getInstance()->setError('Can\'t find category Clickbank');
		}
		self::$_contentCount=0;
		$_settings=new Project_Content_Settings();
		$_settings->onlyOne()->withFlgDefault()->onlySource( self::$_flgSource )->getContent( $this->_settings );
		$this->_settings['settings']['flg_language']=$this->_data->filtered['flg_language'];
		$this->_settings['settings']['category_pid']=$this->_data->filtered['category_pid'];
		$this->_settings['settings']['category_id']=$this->_data->filtered['category_id'];
		$this->_settings['settings']['tags']=$this->_data->filtered['tags'];
		$this->_settings['settings']['withthumb']=false;
		if( !empty($this->_data->filtered['content']) ){
			self::$_jsonKeys=json_encode($this->_data->filtered['content']);
			self::$_contentCount=count($this->_data->filtered['content']);
			return true;
		}
		$_content=new Project_Content_Adapter_Clickbank();
		if( !$_content->withPaging(array('page'=>1))->setFilter( $this->_settings['settings'] )->getList( $_temp1 )->checkEmpty() ){
			return Core_Data_Errors::getInstance()->setError('There is no relevant content found for your keyword.');
		}
		foreach($_temp1 as $_item ){
			$_arrIndex[]=$_item['id'];
		}
		self::$_contentCount=count($_arrIndex);
		self::$_jsonKeys=json_encode($_arrIndex);
		if( !$_content->withPaging( array('page'=>2) )->setFilter( $this->_settings['settings'] )->getList( $_temp2 )->checkEmpty() ){
			return true;
		}
		foreach($_temp2 as $_item ){
			$_arrIndex[]=$_item['id'];
		}
		if( count($_arrIndex)>6 ){
			$_arrIndex=array_splice($_arrIndex,0,6);
		}
		self::$_contentCount=count($_arrIndex);
		self::$_jsonKeys=json_encode($_arrIndex);
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
			'ftp_directory'=>(!empty($this->_data->filtered['ftp_directory'])&&$this->_data->filtered['ftp_directory']!='/')?$this->_data->filtered['ftp_directory']:'/',
			'ftp_root'=>(!empty($this->_data->filtered['ftp_directory'])&&$this->_data->filtered['ftp_directory']!='/')?0:1,
			'template_id'=>$this->_templateId,
			'category_id'=>$this->_category['id'],
			'main_keyword'=>ucwords($this->_data->filtered['main_keyword']),
			'google_analytics'=>Core_Users::$info['adsenseid'],
			'navigation_length'=>7,
			'flg_snippet'=>'no',
//			'zonterest'=>Core_Sql::getCell('SELECT id FROM co_snippets WHERE title=\'Amazon Zonterest AD\'')
		))->set() ){
			return false;
		}
		$_ncsb->onlyIds()->withOrder('d.id--up')->getList( $arrIds );
		$_ncsb->withIds( $arrIds[0] )->onlyOne()->getList($this->_site );
		$this->_urls[]=$this->_site['url'];
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
			'title'=>'Clickbank Wizard: Content for site '. $this->_site['main_keyword'],
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
		return true;
	}


	/**
	 * Интеграция с SYNND
	 * @return bool
	 */
	public function createSYNND(){
		$arrCampaign=array(
			'settings'=>array(
				'url'=>$this->_site['url'],
				'title'=>self::getTitle($this->_site['main_keyword']),
				'tags'=>self::getTags($this->_site['main_keyword']),
				'description'=>self::getDescription($this->_site['main_keyword']),
				'category_id'=>'15844834',// category: Book Reviews
				'promoteCount'=>array('3'=>'22'),
				'promoteTypes'=>array('3'=>'3'),
			),
			'flg_type'=>1
		);
		$_synnd=new Project_Synnd();
		return $_synnd->setEntered( $arrCampaign )->set();
	}

	public static function getTitle( $_strKeyword ){
		if(str_word_count($_strKeyword)==1){
			$_strKeyword ='Popular '.$_strKeyword.' Product Reviews';
		} elseif( str_word_count($_strKeyword)>1&&str_word_count($_strKeyword)<4 ){
			$_arr=array(
				'#keyword# Product Reviews',
				'Recommended #keyword# Products'
			);
			$_template=$_arr[array_rand($_arr)];
			$_strKeyword=str_replace('#keyword#',$_strKeyword,$_template);
		}
		return ucwords($_strKeyword);
	}

	public static function getTags( $_strKeyword ){
		$_strKeyword=strtolower($_strKeyword);
		$_strKeyword=trim(preg_replace('@\s[a-z|0-9]{0,2}\s@si',' ',' '.$_strKeyword.' '));
		$_arrWords=explode(' ', $_strKeyword);
		if(count($_arrWords)>3){
			$_arrWords=array_slice($_arrWords,0,3);
			$_strKeyword=implode(' ',$_arrWords);
		}
		if( count($_arrWords)>1 ){
			$_str=join(', ',$_arrWords).', ';
		}
		$_str.=$_strKeyword.', ';
		if(count($_arrWords)>2){
			$_arrWords=array_slice($_arrWords,0,2);
			$_strKeyword=implode(' ',$_arrWords);
		}
		$_str.=$_strKeyword.' reviews';
		return $_str;
	}

	public static function getDescription( $_strKeyword ){
		return "We've reviewed the best and most popular {$_strKeyword} for your pleasure";
	}
}
?>