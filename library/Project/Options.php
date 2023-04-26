<?php
class Project_Options extends Core_Storage {
	public static $arrSpotsStruct = array(
										'psb' => array(
												1 => array(
														'caption' => 'Spot 1 (recommended width: 468px, max: 550px)',
														'preview' => true,
														'default' => true,
													)
											),
												
										'nvsb' => array(
												1 => array(
														'caption' => 'Spot 1 (recommended width: 468px, max: 550px)',
														'preview' => true,
														'default' => true,
													),
												2 => array(
														'caption' => 'Spot 2 (appears in sidebar, max widht: 160px)',
														'preview' => true,
														'default' => true,
													),
												3 => array(
														'caption' => 'Spot 3 (appears in sidebar, max widht: 160px)',
														'preview' => true,
														'default' => true,
													),
											),
											
										'ncsb' => array(
												1 => array(
														'caption' => 'Spot 1 (recommended width: 468px, max: 550px)',
														'preview' => true,
														'default' => true,
													),
												2 => array(
														'caption' => 'Spot 2 (appears in sidebar, max widht: 160px) ',
														'preview' => true,
														'default' => true,
													),
												3 => array(
														'caption' => 'Spot 3 (appears in sidebar, max widht: 160px)',
														'preview' => true,
														'default' => true,
													),
											),

										'cnb' => array(
												1 => array(
														'caption' => 'Spot1 (insert a link to your home page, portal page, blog...)',
														'preview' => true,
														'default' => true,
													),
												2 => array(
														'caption' => 'Spot2 (add links or banners here) ',
														'preview' => true,
														'default' => true,
													),
												3 => array(
														'caption' => 'Spot3 (add links or banners here) ',
														'preview' => true,
														'default' => true,
													),
												4 => array(
														'hide_saved'  => true,
														'caption' => 'Spot4 (replace the metatag title) ',
														'preview' => '#',
														'default' => true,
													),
												5 => array(
														'hide_saved'  => true,
														'caption' => 'Spot5 (replace the description metatag) ',
														'preview' => '#',
														'default' => true,
													),
												6 => array(
														'caption' => 'Spot6 (recommended: 728x90px, max width: 750px) ',
														'preview' => true,
														'default' => true,
													),
												7 => array(
														'caption' => 'Spot7 (recommended: 250x250px, max width: 350px) ',
														'preview' => true,
														'default' => true,
													),
												8 => array(
														'caption' => 'Spot8 (max width: 350px) ',
														'preview' => true,
														'default' => true,
													),
												9 => array(
														'caption' => 'Spot9 (recommended: 120x600px, max width: 120px) ',
														'preview' => true,
														'default' => true,
													),
												10 => array(
														'caption' => 'Spot10 (max width: 120px) ',
														'preview' => true,
														'default' => true,
													),
											),
								);
	private $_siteId=false;
	private $_siteType=false;
	private $_userId=false;
	public $tableDams = 'es_opt_dams';
	public $table = 'es_opt_spots';
	public $table2data = 'es_opt_data2spot';
	public $fields = array('id','user_id','site_id','flg_default','flg_type','site_type','type_order','spot_name');
	const ARTICLE=1, VIDEO=2, SNIPPET=3, CUSTOMER=4;
	public static $code=array(
		Project_Options::ARTICLE => 'articles',
		Project_Options::VIDEO => 'video',
		Project_Options::SNIPPET => 'snippets',
		Project_Options::CUSTOMER => 'customer',
	);
	private static $templateId=false;
	private $_spotStruct=array();
	
	public function __construct( $_siteType , $_siteId=false ){
		if ( empty( $_siteType ) ){
			throw new Exception( Core_Errors::DEV.'| siteType is null' );
			return false;
		}
		if ( !Zend_Registry::get( 'objUser' )->getId( $this->_userId ) ) {
			throw new Exception( Core_Errors::DEV.'| no userId' );
			return false;
		}	
		$this->setSiteId( $_siteId );
		$this->_siteType=$_siteType;
		if ( !$this->setSpotsStruct() ){
			throw new Exception( Core_Errors::DEV.'| can\'t get spots' );
			return false;
		}
	}
	
	public function setSiteId( $intId ){
		$this->_siteId=$intId;
		return $this;
	}
	
	public static function setTemplate2spots($templateId){
		self::$templateId=$templateId;
	}
	
	private function setSpotsStruct(){
	/*	if ( $this->_siteType == Project_Sites::PSB ) {
			if( empty($this->_siteId) ){
				$this->_spotStruct=self::$arrSpotsStruct[Project_Sites::$code[Project_Sites::PSB]];
				return true;
			}
			if (!self::$templateId && !empty( $this->_siteId ) ){
				$_model=new Project_Sites( Project_Sites::PSB );
				$_model->getSite( $_arrSite, $this->_siteId );
				self::$templateId=$_arrSite['arrPsb']['template_id'];
			}
			$_spots=new Project_Sites_Spots( Project_Sites::PSB );
			if ( !empty(self::$templateId)) {
				$_spots->getList( $_arrSpots, self::$templateId );
				$this->_spotStruct=array();
				foreach ( $_arrSpots as $k=>$v ) {
					$k++;
					$this->_spotStruct[$k] = array( 'caption' => "Spot $k (max width: {$v['width']}px, max height: {$v['height']}px)", 'preview' => false, 'default' => true);
				}
			}			
		} else {*/
			$this->_spotStruct = self::$arrSpotsStruct[ Project_Sites::$code[ $this->_siteType ] ];
		//}
		return !empty($this->_spotStruct);
	}

	public function getSpotStruct(){
		return $this->_spotStruct;
	}

	public function clearOptions(){
		if (empty($this->_siteId)||empty($this->_userId)){
			return false;
		}
		// clear spots and data for site;
		Core_Sql::setExec('DELETE s.*,d.* FROM '.$this->table.' as s LEFT JOIN '.$this->table2data .' as d ON s.id=d.spot_id WHERE s.site_id='.$this->_siteId.' AND s.user_id='.$this->_userId .' AND flg_type='.$this->_siteType );
		Core_Sql::setExec('DELETE FROM '.$this->tableDams.'  WHERE site_id='.$this->_siteId.' AND flg_type='.$this->_siteType );
		return true;
	}
	/**
	 * Save spots in database
	 * format: 
	 * array(
	 * 	array(
	 * 		'flg_default'=>0,
	 *		'articles'=>array(id1,id2,..idn),
	 *		'video'=>array(id1,id2,..idn),
	 * 		'flg_title'=>0,
	 *		'snippets'=>array(id1,id2,..idn),
	 *		'customer'=>'html code',
	 * 	)
	 *  ...
	 * )
	 *
	 * @return bool
	 */
	public function set(){
		$this->_data->setFilter('stripslashes', 'trim', 'clear');
		//p($this->_data);
		if (!$this->clearOptions()){
			return false;
		}
		// set dams
		if (!empty($this->_data->filtered['dams']['ids'])){
			foreach ( $this->_data->filtered['dams']['ids'] as $source_id ){
				$data[]=array(
					'flg_content'=>$this->_data->filtered['dams']['flg_content'],
					'flg_type'=>$this->_siteType,
					'site_id'=>$this->_siteId,
					'source_id'=>$source_id
				);
			}
			Core_Sql::setMassInsert($this->tableDams,$data);
		}
		// set new spots for site;
		foreach ( $this->_data->filtered['spots'] as $spot ){
			if ( empty($spot['spot_name'])){
				continue;
			}
			$data=array(
				'user_id'=>$this->_userId,	
				'site_id'=>$this->_siteId,	
				'flg_default'=>$spot['flg_default'],
				'flg_content'=>0,
				'flg_type'=>$this->_siteType,
				'flg_title'=>empty($spot['flg_title'])?0:$spot['flg_title'],
				'spot_name'=>$spot['spot_name'],
				'type_order'=>0
			);
			// set default and removed spot
			if ( $spot['flg_default']==0 || $spot['flg_default']==2 ){
				Core_Sql::setInsert( $this->table, $data );
				continue;
			}
			// set articles data
			if ( !empty($spot['articles']) ){
				$data['flg_content']=Project_Options::ARTICLE;
				$data['type_order']=$spot['type_order'][Project_Options::ARTICLE];
				if ( $_intSpotId=Core_Sql::setInsert( $this->table, $data ) ){
					$data2spot=array();
					foreach ($spot['articles'] as $source_id){
						$data2spot[]=array(
							'spot_id'=>$_intSpotId,
							'source_id'=>$source_id,
						);

					}
					Core_Sql::setMassInsert( $this->table2data, $data2spot );
				}
			}
			// set video data
			if ( !empty($spot['video']) ){
				$data['flg_content']=Project_Options::VIDEO;
				$data['type_order']=$spot['type_order'][Project_Options::VIDEO];
				if ( $_intSpotId=Core_Sql::setInsert( $this->table, $data ) ){
					$data2spot=array();
					foreach ( $spot['video'] as $source_id){
						$data2spot[]=array(
							'spot_id'=>$_intSpotId,
							'source_id'=>$source_id,
						);
					}
					Core_Sql::setMassInsert( $this->table2data, $data2spot );
				}
			}
			// set snippet data
			if ( !empty($spot['snippets']) ){
				$data['flg_content']=Project_Options::SNIPPET;
				$data['type_order']=$spot['type_order'][Project_Options::SNIPPET];
				if ( $_intSpotId=Core_Sql::setInsert( $this->table, $data ) ){
					$data2spot=array();
					foreach ( $spot['snippets'] as $source_id){
						$data2spot[]=array(
							'spot_id'=>$_intSpotId,
							'source_id'=>$source_id,
						);
					}
					Core_Sql::setMassInsert( $this->table2data, $data2spot );
				}
			}
			// set customer data
			if ( !empty($spot['customer'])){
				$data['flg_content']=Project_Options::CUSTOMER;
				$data['type_order']=$spot['type_order'][Project_Options::CUSTOMER];
				if ( $_intSpotId=Core_Sql::setInsert( $this->table, $data ) ){
					$data2spot=array(
					'spot_id'=>$_intSpotId,
					'source_id'=> 0,
					'customer_code'=>$spot['customer']
					);
					Core_Sql::setInsert($this->table2data, $data2spot);
				}
			}
			
		}
		return true;
	}
	
	public function get(&$arrRes, $_intId=0 ){
		$_arr=Core_Sql::getAssoc('SELECT * FROM '.$this->table.' WHERE site_id='.$this->_siteId.' AND flg_type='.$this->_siteType.' ORDER BY spot_name,type_order' );
		foreach ( $_arr as &$spot ){
			switch ( $spot['flg_content'] ){
				case Project_Options::ARTICLE :
					$spot[self::$code[Project_Options::ARTICLE]]=Core_Sql::getField('SELECT source_id FROM '.$this->table2data.' WHERE spot_id='.$spot['id']);
					break;
				case Project_Options::SNIPPET :
					$spot[self::$code[Project_Options::SNIPPET]]=Core_Sql::getField('SELECT source_id FROM '.$this->table2data.' WHERE spot_id='.$spot['id']);
					break;
				case Project_Options::VIDEO :
					$spot[self::$code[Project_Options::VIDEO]]=Core_Sql::getField('SELECT source_id FROM '.$this->table2data.' WHERE spot_id='.$spot['id']);
					break;
				case Project_Options::CUSTOMER :
					$spot[self::$code[Project_Options::CUSTOMER]]=Core_Sql::getCell('SELECT customer_code FROM '.$this->table2data.' WHERE spot_id='.$spot['id']);
					break;
			}
			$arr[$spot['spot_name']]=array_merge($spot,(!empty($arr[$spot['spot_name']]))?$arr[$spot['spot_name']]:array());
			$order[$spot['spot_name']][$spot['flg_content']]=$spot['type_order'];

		}
		foreach ($arr as $key=>&$spot){
			$spot['type_order']=$order[$key];
		}
		$arrRes['spots']=$arr;
		$arrRes['dams']=Core_Sql::getRecord('SELECT * FROM '.$this->tableDams.' WHERE site_id='.$this->_siteId .' AND flg_type='.$this->_siteType .' LIMIT 1' );
		$arrRes['dams']['ids']=Core_Sql::getField('SELECT source_id FROM '.$this->tableDams.' WHERE site_id='.$this->_siteId .' AND flg_type='.$this->_siteType );
	}
	
	public function getVideo(){
		$_model=new Project_Embed();
        $arr=array();
		$_model->getList( $arr['arrList'] );	
		$_model->getAdditional( $arr['arrSelect'] );
		return $arr;
	}
	public function getSavedSelection(){
		$arr=Core_Sql::getAssoc("SELECT id,user_id,disp_option,code,name,description FROM hct_am_savedcode WHERE user_id={$this->_userId} ORDER BY disp_option");
		return $arr;
	}
}
?>
