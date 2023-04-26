<?php

/**
 * Создание сайтов AzonFunnels
 */
class Project_Ccs_Adapter_Zonterest2 {

	/**
	 * Object Core_Data
	 * @var Core_Data object
	 */
	private $_data=false;

	private $_category='All';
	
	/**
	 * Id  источника котента Amazon см. Project_Content::$source
	 * @var int
	 */
	private static $_flgSource=9;
	
	private $_site='US';

	private $_adapter='';

	public function __construct(){
		$this->_adapter=new Project_Wizard( Project_Wizard::TYPE_ZONTEREST_NCSB );
	}

	public function setEntered( Core_Data $_data ){
		$this->_data=$_data;
		return $this;
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
	
	private $_place=false;
	
	public function setDomain( $_domainName ){
		if( isset( $_domainName ) ){
			$_placement=new Project_Placement();
			if( !$_placement->setEntered(array(
				'domain_http'=>$_domainName,
				'flg_type'=>Project_Placement::LOCAL_HOSTING_DOMEN,
				'flg_auto'=>1,
			))->set() ){
				if( @$_SERVER['HTTP_HOST'] == 'cnm.local' ){
					var_dump( 'Project_Ccs_Adapter_Zonterest2::setDomain' );
					$this->_place=array(
						'domain_http'=>$_domainName
					);
				}
				return false;
			}
			$_placement->getEntered( $this->_place );
			return !empty($this->_place);
		}
		return false;
	}
	
	public function run(){
		if( isset( Core_Users::$info['zonterest_limit'] ) && Core_Users::$info['zonterest_limit']>0 ){
			$model=new Project_Sites( Project_Sites::NCSB );
			$model
				->withCategory( 'Zonterest' ) // 641
				->withPlacementId( 8484 ) // 8484
				->onlyCount()
				->onlyOwner()
				->getList( $_countZonterestSites );
			if( $_countZonterestSites >= Core_Users::$info['zonterest_limit'] ){
				return Core_Data_Errors::getInstance()->setError('Sorry, we were not able to create a AzonFunnels site. Please check your site limit.');
			}
		}elseif( isset( Core_Users::$info['zonterest_limit'] ) && Core_Users::$info['zonterest_limit']==0 ){
			return Core_Data_Errors::getInstance()->setError('Sorry, we were not able to create a AzonFunnels site. Please check your site limit.');
		}
		if( !$this->_adapter->check() ){
			if( Core_Data_Errors::getInstance()->getErrorFlowShift()=='empty_settings'){
				return Core_Data_Errors::getInstance()->setError('Sorry, we were not able to create a AzonFunnels site. Please fill in your personal details in Amazon Source Settings.');
			}
			//return Core_Data_Errors::getInstance()->setError('Sorry, we were not able to create a AzonFunnels site. You don\'t have enough credits on your balance.');
		}
		if( !Core_Acs::haveAccess( array('Zonterest PRO 2.0','Zonterest 2.0','Zonterest Custom 2.0') ) ){
			return Core_Data_Errors::getInstance()->setError('Sorry, we were not able to create a AzonFunnels site. You don\'t have access.');
		}
		if( !empty($this->_place) && Core_Acs::haveAccess( array('Zonterest Custom 2.0') ) ){
			$_setting=array(
				'ftp_directory'=>'/',
				'flg_type'=> '1',
				'flg_passive'=> '1',
				'flg_checked'=> '2',
				'flg_sended_hosting'=> '0',
				'flg_sended_domain'=> '0',
				'flg_auto'=> '1',
				'domain_http'=> $this->_place['domain_http'],
				'url'=> $this->_place['url'],
				'placement_id'=> $this->_place['placement_id'],
				'domain_ftp'=> NULL,
				'username'=> NULL,
				'password'=> NULL,
				'db_host'=> NULL,
				'db_name'=> NULL,
				'db_username'=> NULL,
				'db_password'=> NULL,
			);
		}else{
			$_setting=array(
				'ftp_directory'=>'/'.$this->randomDirName().'/',
				'flg_type'=> '1',
				'flg_passive'=> '1',
				'flg_checked'=> '2',
				'flg_sended_hosting'=> '0',
				'flg_sended_domain'=> '0',
				'flg_auto'=> '1',
				'domain_http'=> 'amazideas.net',
				'placement_id'=> NULL,
				'domain_ftp'=> NULL,
				'username'=> NULL,
				'password'=> NULL,
				'db_host'=> NULL,
				'db_name'=> NULL,
				'db_username'=> NULL,
				'db_password'=> NULL,
			);
		}
		if( @$_SERVER['HTTP_HOST'] != 'cnm.local' ){
			try{
				$_strExtractDir='Project_Wizard@zonterest';
				if ( !Zend_Registry::get( 'objUser' )->prepareTmpDir( $_strExtractDir ) ) {
					return false;
				}
				$_transport=new Project_Placement_Transport();
				if( !$_transport
					->setInfo( $_setting )
					->setSourceDir( $_strExtractDir )
					->placeAndBreakConnect() 
				){
					// ошибка ?
				}
			}catch( Core_Ssh_Exception $e ){}
			ob_end_clean();
		}
		$_obj=new Project_Content_Settings();
		$_obj->onlyOne()->withFlgDefault()->onlySource( '9' )->getContent( $_contentSettings );
		if( !empty($this->_place) && Core_Acs::haveAccess( array('Zonterest Custom 2.0') ) ){
			$this->_adapter->setEntered(array(
				'main_keyword'=>$this->_data->filtered['keyword'],
				'category'=>( isset( $this->_data->filtered['category'] ) ? $this->_data->filtered['category'] : $this->_category ),
				'site'=> $_contentSettings['settings']['site'],
				'setting'=>$_contentSettings['id'],
				'flg_amazideas'=>1,
				'domain_url'=>$this->_place['domain_http'],
				'placement_id'=>$this->_place['id'],
				'domain_http'=>$this->_place['domain_http'],
				'ftp_directory'=>$_setting['ftp_directory']
			));
		}else{
			$this->_adapter->setEntered(array(
				'main_keyword'=>$this->_data->filtered['keyword'],
				'category'=>( isset( $this->_data->filtered['category'] ) ? $this->_data->filtered['category'] : $this->_category ),
				'site'=> $_contentSettings['settings']['site'],
				'setting'=>$_contentSettings['id'],
				'flg_amazideas'=>1,
				'domain_url'=>'http://amazideas.net'.$_setting['ftp_directory'],
				'domain_http'=>$_setting['domain_http'],
				'ftp_directory'=>$_setting['ftp_directory']
			));
		}
		return $this->_adapter->run();
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