<?php

class Project_Wizard_Adapter_ContentPro implements Project_Wizard_Adapter_Interface{

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
	 * Адреса сайтов которые создали
	 * @var array
	 */
	private $_urls=array();

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
	private static $_flgSource=20;

	private static $_contentCount=0;

	private static $_jsonKeys='["0","1","2","3","4","5","6"]';

	const EXIST_DOMAIN=0,NEW_DOMAIN=1,MULTI_DOMAIN=2, SUBFOLDERS=3;

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

	public static function checkMutliCost( $_arrData ){
		$_cost=0;
		if( $_arrData['promotion'] ){
			$_cost+=Project_Synnd::$promotionTypes[3]['amount']*50;
		}
		$_buns=new Core_Payment_Buns();
		$_buns->withSysName('Project_Placement_Hosting')->onlyOne()->getList( $arrHosting );
		$_buns->withSysName('Project_Placement_Domen')->onlyOne()->getList( $arrDomain );
		$_purse=new Core_Payment_Purse();
		$_cost+=$arrDomain['credits']+$arrHosting['credits'];
		if( Core_Payment_Purse::getAmount()>=$_cost*$_arrData['count'] ){
			return  true;
		}
		return intval(Core_Payment_Purse::getAmount()/$_cost);
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
		$this->_data->setFilter('trim','clear');
		if( $this->_data->filtered['type_create']==self::SUBFOLDERS ){
			if ( !Core_Data_Errors::getInstance()->setData( $this->_data )->setValidators( array(
				'category_pid'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ),
				'category_id'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ),
			) )->isValid() ) {
				return Core_Data_Errors::getInstance()->setError('Incorrect entered data');
			}
			$this->prepareKeywords();
		} else {
			if ( !Core_Data_Errors::getInstance()->setData( $this->_data )->setValidators( array(
				'main_keyword'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ),
//				'category_pid'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ),
			) )->isValid() ) {
				return Core_Data_Errors::getInstance()->setError('Incorrect entered data');
			}
		}
		$_arrKeywords=$this->_data->filtered['main_keyword'];
		$_index=0;
		$_place=false;
		foreach($_arrKeywords as $_categoryID=>$_keyword ){
			if( $this->_data->filtered['type_create']==self::SUBFOLDERS ){
				$this->_data->setElement('category_id',$_categoryID);
			}
			$this->_place=false;
			$this->_data->setElement('main_keyword',$_keyword);
			if( !$this->prepare() ){
				return Core_Data_Errors::getInstance()->setError('Can\'t prepare data');
			}
			switch( $this->_data->filtered['type_create'] ){
				case self::EXIST_DOMAIN:
					$_place=new Project_Placement();
					$_place->withIds($this->_data->filtered['placement_id'])->onlyOne()->getList( $this->_place );
					break;
				case self::MULTI_DOMAIN:
					$this->_data->filtered['domain_http']=$this->generateDomain();
					if( $this->_data->filtered['domain_http']===false||!$this->registerDomain() ){
						return Core_Data_Errors::getInstance()->setError('Can\'t create domain & hosting.');
					}
					break;
				case self::NEW_DOMAIN:
					if( !$this->registerDomain() ){
						return Core_Data_Errors::getInstance()->setError('Can\'t create domain & hosting.');
					}
					break;
				case self::SUBFOLDERS:
					if( $_index==0 ){
						if( !$this->registerDomain() ){
							return Core_Data_Errors::getInstance()->setError('Can\'t create domain & hosting.');
						}
						$_place=$this->_place;
					} else {
						$this->_place=$_place;
						$this->_data->setElement('ftp_directory',Core_String::getInstance( strtolower( strip_tags( $_keyword ) ) )->toSystem( '-' ));
					}
					break;
			}
			$_index++;
			if( !$this->createNcsbSite() ){
				return Core_Data_Errors::getInstance()->setError('Can\'t create NCSB site');
			}
			if( !$this->createAllAtOnce() ){
				return Core_Data_Errors::getInstance()->setError('Can\'t create all at once project');
			}
			if( !$this->createSchedule() ){
				return Core_Data_Errors::getInstance()->setError('Can\'t create schedule priject');
			}
			if( $this->_data->filtered['promotion']==1&&!$this->createSYNND() ){
				return Core_Data_Errors::getInstance()->setError('Can\'t create Social Signals.');
			}
		}
		return true;
	}

	/**
	 * Подготовка кейворда при типе создания SUBFOLDERS
	 *
	 */
	private function prepareKeywords(){
		$_category=new Core_Category( 'Exclusive' );
		$_category->getLevel( $arrSubCategories, $this->_data->filtered['category_pid'] );
		$_category->byId($this->_data->filtered['category_id'])->getList($arrCategory);
		foreach( $arrSubCategories as $_key=>$_item ){
			if( $_item['id']==$arrCategory['id'] ){
				unset($arrSubCategories[$_key]);

			}
		}
		if( !empty($this->_data->filtered['sub_categories']) ){
			foreach( $this->_data->filtered['sub_categories'] as $_id ){
				$_category->byId($_id)->getList($subCategory);
				$_tmpArr[$subCategory['id']]=$subCategory['title'];
			}
		} else {
			$_tmpArr=array();
			for( $i=0;$i<4;$i++ ){
				$_index=array_rand($arrSubCategories);
				$_tmpArr[$arrSubCategories[$_index]['id']]=$arrSubCategories[$_index]['title'];
				unset($arrSubCategories[$_index]);
			}
		}
		$this->_data->setElements(array(
			'main_keyword'=>array($arrCategory['id']=>$arrCategory['title'])+$_tmpArr
		));
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
		if( empty($this->_category) ){
			return Core_Data_Errors::getInstance()->setError('Can\'t find category');
		}
		self::$_contentCount=0;
		$_settings=new Project_Content_Settings();
		$_settings->onlyOne()->withFlgDefault()->onlySource( self::$_flgSource )->getContent( $this->_settings );
		$this->_settings['settings']['category_pid']=$this->_data->filtered['category_pid'];
		$this->_settings['settings']['category_id']=$this->_data->filtered['category_id'];
		$_content=new Project_Content_Adapter_Exclusive();
		if( !$_content->withRandom()->setLimited(7)->setFilter( $this->_settings['settings'] )->getList( $_temp )->checkEmpty() ){
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
	 * Подготавливает _place если тип MULTI_DOMAIN
	 * @return bool
	 */
	private function generateDomain(){
		$_domain=new Project_Wizard_Domain( Project_Wizard_Domain_Rules::R_CONTENTPRO );
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
			'main_keyword'=>ucwords(strtolower($this->_data->filtered['main_keyword'])),
			'google_analytics'=>Core_Users::$info['adsenseid'],
			'navigation_length'=>7,
			'flg_snippet'=>'no'
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
			'title'=>'Conetnt Wizard: Content for site '. $this->_site['main_keyword'],
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
				'category_id'=>'15845112',// category: News and Society 
				'promoteCount'=>array('3'=>$this->_data->filtered['promoteCount']),
				'promoteTypes'=>array('3'=>'3'),
			),
			'flg_type'=>$this->_data->filtered['promote_flg_type']
		);
		$_synnd=new Project_Synnd();
		return $_synnd->setEntered( $arrCampaign )->set();
	}

	public static function getTitle( $_strKeyword ){
		if(str_word_count($_strKeyword)==1){
			$_strKeyword ='News, Content and Resources on  '.$_strKeyword;
		} elseif( str_word_count($_strKeyword)>1&&str_word_count($_strKeyword)<4 ){
			$_arr=array(
				'News, Content and Resources on #keyword#'
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
		$_str.=$_strKeyword.' resources';
		return $_str;
	}

	public static function getDescription( $_strKeyword ){
		return "We've found this new website with a lot of great articles and tips about {$_strKeyword}";
	}
}
?>