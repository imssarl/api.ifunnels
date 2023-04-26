<?php

class Project_Wizard_Adapter_Video implements Project_Wizard_Adapter_Interface{

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

	private static $_contentCount=10;

	const EXIST_DOMAIN=1,MULTI_DOMAIN=2;

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
		return $this->_urls;
	}


	public function getContentCount(){
		return self::$_contentCount;
	}

	/**
	 * Запускает процесс покупки домена/хостинга, создания NVSB сайта
	 * @return bool
	 */
	public function run(){
		if ( !Core_Data_Errors::getInstance()->setData( $this->_data->setFilter('trim','clear') )->setValidators( array(
			'main_keyword'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ),
			'category_id'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ),
		) )->isValid() ) {
			return Core_Data_Errors::getInstance()->setError('Incorrect entered data');
		}
		if(is_array($this->_data->filtered['main_keyword'])){
			$_arrKeywords=$this->_data->filtered['main_keyword'];
		} else {
			$_arrKeywords=array($this->_data->filtered['main_keyword']);
		}
		foreach($_arrKeywords as $_keyword ){
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
				default:
					if( !$this->registerDomain() ){
						return Core_Data_Errors::getInstance()->setError('Can\'t create domain & hosting.');
					}
					break;
			}
			if( !$this->createSite() ){
				return Core_Data_Errors::getInstance()->setError('Can\'t create NVSB site');
			}
			if( $this->_data->filtered['promotion']==1&&!$this->createSYNND() ){
				return Core_Data_Errors::getInstance()->setError('Can\'t create Social Signals.');
			}
			return true;
		}
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
	 * Подготовка необходимых данных
	 * @return bool
	 */
	private function prepare(){
		$this->_templateId=Core_Sql::getCell('SELECT t.id FROM es_templates t LEFT JOIN es_template2user l ON l.template_id=t.id WHERE l.user_id='. Core_Users::$info['id'] .' AND t.flg_type='.Project_Sites::NVSB.' ORDER BY RAND()' );
		if( empty($this->_templateId) ){
			return false;
		}
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
	private function createSite(){
		sleep(3); // ждем, если репликация будет работать медленно, то сайт не создастся
		$_ncsb=new Project_Sites( Project_Sites::NVSB );
		if( !$_ncsb->setEntered(array(
			'placement_id'=>$this->_place['id'],
			'ftp_directory'=>'/',
			'ftp_root'=>1,
			'template_id'=>$this->_templateId,
			'category_id'=>$this->_data->filtered['category_id'],
			'main_keyword'=>ucwords(strtolower($this->_data->filtered['main_keyword'])),
			'google_analytics'=>Core_Users::$info['adsenseid'],
			'flg_usage'=>2,
			'flg_related_keywords'=>0,
			'flg_comments'=>0
		))->set() ){
			return false;
		}
		$_ncsb->onlyIds()->withOrder('d.id--up')->getList( $arrIds );
		$_ncsb->withIds( $arrIds[0] )->onlyOne()->getList($this->_site );
		$this->_urls[]=$this->_site['url'];
		return !empty($this->_site);
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
			$_strKeyword ='The Most Popular Videos on  '.$_strKeyword;
		} elseif( str_word_count($_strKeyword)>1&&str_word_count($_strKeyword)<4 ){
			$_arr=array(
				'The Most Popular Videos on #keyword#'
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
		$_str.=$_strKeyword.' videos, popular '.$_strKeyword;
		return $_str;
	}

	public static function getDescription( $_strKeyword ){
		return "We've found this new website with a lot of great videos on {$_strKeyword}";
	}
}
?>