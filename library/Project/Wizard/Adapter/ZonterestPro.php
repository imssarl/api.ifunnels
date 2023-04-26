<?php

class Project_Wizard_Adapter_ZonterestPro  implements Project_Wizard_Adapter_Interface{

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

	const EXIST_DOMAIN=0,NEW_DOMAIN=1,MULTI_DOMAIN=2,DOWNLOAD=3,SUBFOLDERS=4,AMAZIDEAS=5;

	/**
	 * Проверяет заполнил ли текущий пользователь персональные данные, настройки источника контента
	 * и хавтает ли у него денег на хостинг и домен.
	 * @return bool
	 */
	public function check(){
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
		// отключена проерка кредитов т.к. в этом визарде можно создавать сайты на уже созданных доменах
		return true;
		$_buns=new Core_Payment_Buns();
		$_buns->withSysName('Project_Placement_Hosting')->onlyOne()->getList( $arrHosting );
		$_buns->withSysName('Project_Placement_Domen')->onlyOne()->getList( $arrDomain );
		$_purse=new Core_Payment_Purse();
		if( Core_Payment_Purse::getAmount()<($arrDomain['credits']+$arrHosting['credits']) ){
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
				'ncsb_site'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ),
			) )->isValid() ) {
				if ( !Core_Data_Errors::getInstance()->setData( $this->_data )->setValidators( array(
					'main_keyword'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ),
				) )->isValid() ) {
					return Core_Data_Errors::getInstance()->setError('Incorrect entered data.');
				}
			}
			$this->prepareKeywords();
		} else {
			if ( !Core_Data_Errors::getInstance()->setData( $this->_data )->setValidators( array(
				'main_keyword'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ),
				'category'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ),
				'site'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ),
			) )->isValid() ) {
				return Core_Data_Errors::getInstance()->setError('Incorrect entered data.');
			}
		}
		$_arrKeywords=$this->_data->filtered['main_keyword'];
		$_index=0;
		$_place=false;
		foreach($_arrKeywords as $_keyword ){
			$this->_place=false;
			$this->_data->setElement('main_keyword',$_keyword );
			if( !$this->prepare() ){
				return Core_Data_Errors::getInstance()->setError('Can\'t prepare data.');
			}
			switch( $this->_data->filtered['type_create'] ){
				case self::DOWNLOAD :
					if( !$this->downloadNcsbSite() ){
						return Core_Data_Errors::getInstance()->setError('Can\'t create NCSB site.');
					}
					return true;
				break;
				case self::EXIST_DOMAIN:
					$_place=new Project_Placement();
					$_place->withIds($this->_data->filtered['placement_id'])->onlyOne()->getList( $this->_place );
					break;
				case self::AMAZIDEAS:
					$this->_place['id']=8484; // это наш amazideas.net
					break;
				case self::MULTI_DOMAIN :
					$this->_data->filtered['domain_http']=$this->generateDomain();
					if( $this->_data->filtered['domain_http']===false||!$this->registerDomain() ){
						return Core_Data_Errors::getInstance()->setError('Can\'t create domain & hosting.');
					}
					break;
				case self::NEW_DOMAIN :
					if( !$this->registerDomain() ){
						return Core_Data_Errors::getInstance()->setError('Can\'t create domain & hosting.');
					}
					break;
				case self::SUBFOLDERS :
					$this->_synndFlgType=1;
					$this->_promoteCount=22;
					if( $_index==0 ){
						if( !$this->registerDomain() ){
							return Core_Data_Errors::getInstance()->setError('Can\'t create domain & hosting.');
						}
						$_place=$this->_place;
					} else {
						$this->_place=$_place;
						$this->_data->setElement('ftp_directory', ( empty( $this->_data->filtered['ftp_directory'] ) ? '/error/' : $this->_data->filtered['ftp_directory'] ).Core_String::getInstance( strtolower( strip_tags( $_keyword ) ) )->toSystem( '-' ) );
					}
					break;
			}
			$_index++;
			if( !$this->createNcsbSite() ){
				return Core_Data_Errors::getInstance()->setError('Can\'t create NCSB site.');
			}
			if( !$this->createAllAtOnce() ){
				return Core_Data_Errors::getInstance()->setError('Can\'t create all at once project.');
			}
			if( !$this->createSchedule() ){
				return Core_Data_Errors::getInstance()->setError('Can\'t create schedule project.');
			}
			if( $this->_data->filtered['promotion']==1&&!$this->createSYNND() ){
				return Core_Data_Errors::getInstance()->setError('Can\'t create Social Signals.');
			}
			Core_Sql::reconnect();
		}
		return true;
	}

	private function prepareKeywords(){
		if(!empty($this->_data->filtered['main_keyword'])){
			return true;
		}
		$_sites=new Project_Sites( Project_Sites::NCSB );
		$_sites->withIds( $this->_data->filtered['ncsb_site'])->onlyOne()->getList( $arrSite );
		$_sites->withPlacementId( $arrSite['placement_id'] )->getList( $arrSubSites );
		$_index=0;
		foreach( $arrSubSites as $_key=>$_site ){
			$_index++;
			if( $arrSite['id']==$_site['id'] ){
				unset($arrSubSites[$_key]);
				continue;
			}
			$_tmpArr[$_index]=$_site['main_keyword'];
		}
		if( empty($this->_data->filtered['category'])){
			$_category=new Core_Category( 'Amazon '.$this->_data->filtered['site'] );
			$_category->get( $arrTree, $_tmp );
			foreach( $arrTree as $_item ){
				if( $_item['title']=='Books' ){
					$this->_data->setElement('category',$_item['title'].'::'.$_item['remote_id'] );
				}
			}
		}
		$this->_data->setElements(array(
			'main_keyword'=>array(
				$arrSite['main_keyword'],
				)+$_tmpArr
		));
	}

	/**
	 * Подготавливает _place если тип MULTI_DOMAIN
	 * @return bool
	 */
	private function generateDomain(){
		$_domain=new Project_Wizard_Domain( Project_Wizard_Domain_Rules::R_ZONTEREST );
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
	private function registerDomain(){
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
			'ftp_directory'=>(!empty($this->_data->filtered['ftp_directory'])&&$this->_data->filtered['ftp_directory']!='/')?$this->_data->filtered['ftp_directory']:'/',
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
		$_ncsb->onlyIds()->withOrder('d.id--up')->getList( $arrIds );
		$_ncsb->withIds( $arrIds[0] )->onlyOne()->getList($this->_site );
		$this->_urls[]=$this->_site['url'];
		return !empty($this->_site);
	}

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
	public function createAllAtOnce(){
		$_publisching=new Project_Publisher();
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

	private $_promoteCount=50;
	private $_synndFlgType=0;
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
				'category_id'=>'15845632',// category: Shopping and Product Reviews
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
		$_str.=$_strKeyword.' store';
		return $_str;
	}

	public static function getDescription( $_strKeyword ){
		return "We've listed the best and most popular {$_strKeyword} for your pleasure";
	}

	public static function isZonterest( $_strDomain, &$arrRes ){
		$_site=new Project_Sites( Project_Sites::NCSB );
		if( !$_site->withCategory( 'Zonterest' )->withUrl($_strDomain)->onlyOne()->getList( $_arrSite )->checkEmpty() ){
			return false;
		}
		if($_arrSite['template_id']!=Core_Sql::getCell('SELECT id FROM es_templates WHERE flg_type='.Project_Sites::NCSB.' AND filename LIKE \'amazontemplate%\'' )){
			return false;
		}
		$arrRes['url']=$_arrSite['url'];
		$arrRes['title']=self::getTitle($_arrSite['main_keyword']);
		$arrRes['tags']=self::getTags($_arrSite['main_keyword']);
		$arrRes['description']=self::getDescription($_arrSite['main_keyword']);
		return true;
	}
}
?>