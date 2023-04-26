<?php
class Project_Squeeze extends Core_Data_Storage{
	
	/**
	 * @var Core_Data bool
	 */
	protected $_arrZeroData=false;
	private $_dir='';
	protected $_table='squeeze_campaigns';
	protected $_fields=array('id','user_id','flg_template','tags','settings','tpl_settings','network','url','flg_funnel','added','edited');

	public static function update(){
		$_arrNulls=Core_Sql::getAssoc("SELECT NULL
			FROM INFORMATION_SCHEMA.COLUMNS
			WHERE table_name = 'squeeze_campaigns'
			AND column_name = 'tpl_settings';");
		if( count( $_arrNulls ) == 0 ){
			Core_Sql::setExec("ALTER TABLE `squeeze_campaigns` ADD  `tpl_settings` TEXT NULL DEFAULT NULL;");
		}
	}
	
	protected function beforeSet(){
		$this->_data->setFilter( array( 'clear' ) );
		$_url='';
		if( $this->_data->filtered['settings']['publishing_options']!='download' ){
			$_url=$this->getGeneratedLink();
		}
		$_setArray=array(
			'url'=>$_url,
			'settings'=>base64_encode( serialize( $this->_data->filtered['settings'] ) )
		);
		if( isset( $this->_data->filtered['tpl_settings']['network'] )){
			$_setArray['network']=$this->_data->filtered['tpl_settings']['network'];
		}
		if( isset( $this->_data->filtered['tpl_settings'] ) && !empty( $this->_data->filtered['tpl_settings'] ) ){
			$_setArray['tpl_settings']=base64_encode( serialize( $this->_data->filtered['tpl_settings'] ) );
		}
		$this->_data->setElements($_setArray);
		return true;
	}

	protected function afterSet(){
		$this->_data->filtered['settings']=unserialize( base64_decode( $this->_data->filtered['settings'] ) );
		$this->_data->filtered['tpl_settings']=unserialize( base64_decode( $this->_data->filtered['tpl_settings'] ) );
		return true;
	}

	public function adminSet(){
		if ( !$this->beforeSet() ){
			return false;
		}
		//$this->_data->setElement( 'edited', time() );
		if ( empty( $this->_data->filtered['id'] ) ){
			$this->_data->setElement( 'added', $this->_data->filtered['edited'] );
			if ( $this->getOwnerId( $_intId ) ){
				$this->_data->setElement( 'user_id', $_intId );
			}
		}
		$this->_data->setElement( 'id', Core_Sql::setInsertUpdate( $this->_table, $this->_data->setMask( $this->_fields )->getValid() ) );
		return $this->afterSet();
	}

	private $_withoutSort=false;

	public function withoutSort (){
		$this->_withoutSort=true;
		return $this;
	}

	private $_withListFromTracker=false;

	public function withListFromTracker(){
		$this->_withListFromTracker=true;
		return $this;
	}

	public function withReportById($id){
		$this->_withReportById=$id;
		return $this;
	}
	
	private $_withTime=false;
	
	public function withTime( $_type, $_from, $_to ){
		$_now=time();
		switch ($_type){
			case Project_Statistics_Api::TIME_ALL: $this->_withTime=array( 'from'=>0, 'to'=>$_now); break;
			case Project_Statistics_Api::TIME_TODAY: $this->_withTime=array( 'from'=>strtotime('today'), 'to'=>$_now); break;
			case Project_Statistics_Api::TIME_YESTERDAY: $this->_withTime=array( 'from'=>strtotime('yesterday'), 'to'=>strtotime('today')); break;
			case Project_Statistics_Api::TIME_LAST_7_DAYS: $this->_withTime=array( 'from'=>$_now-60*60*24*7, 'to'=>$_now); break;
			case Project_Statistics_Api::TIME_THIS_MONTH: $this->_withTime=array( 'from'=>strtotime('first day of this month'), 'to'=>$_now); break;
			case Project_Statistics_Api::THIS_YEAR: $this->_withTime=array( 'from'=>strtotime('first day of January '.date('Y') ), 'to'=>$_now); break;
			case Project_Statistics_Api::TIME_LAST_YEAR: $this->_withTime=array( 'from'=>$_now-60*60*24*365, 'to'=>$_now); break;
			case 8: $this->_withTime=array( 'from'=>strtotime($_from), 'to'=>strtotime($_to) ); break;
		}
		return $this;
	}
	
	public function withFilter( $arrFilter ){
		if( !empty( $arrFilter['time'] ) ){
			$this->withTime( $arrFilter['time'], @$arrFilter['date_from'], @$arrFilter['date_to'] );
		}
		return $this;
	}
	
	public function getList( &$mixRes ){
		if($this->_withListFromTracker){
			$_arrZeroData=array();
			$_order=$this->_withOrder;
			$_date=$this->_withTime;
			$this->init();
			$this->withoutSort()->onlyOwner()->withIds($this->_withReportById)->getList( $_arrData );
			foreach( $_arrData as &$_item ){
				$_arrSqueezeIds[]= $_item['id'];
			}
			if( empty( $_arrSqueezeIds ) ){
				return $this;
			}
			$_oldSqueezeIds=$_arrSqueezeIds;	
			if( !empty( $this->_withPaging ) ){
				$this->_row_per_page=!empty(Core_Users::$info['arrSettings']['rows_per_page']) ? Core_Users::$info['arrSettings']['rows_per_page'] : 12;
				$_limit_a=(empty($this->_withPaging['page']) ? 0 : ((int)$this->_withPaging['page']) - 1) * $this->_row_per_page;
				$_limit_b=$_limit_a + $this->_row_per_page;
			}
			$_table='lpb_view_'.Core_Users::$info['id']; $_sort='DESC'; $_limit='';
			if($_order == 'c.visitors--dn' || $_order == 'c.visitors--up'){
				$_table='lpb_view_'.Core_Users::$info['id'];
				if($_order == 'c.visitors--dn'){
					$_sort='DESC';
				} else {
					$_sort='ASC';
				}
				if( !empty( $this->_withPaging ) ){
					$_limit=' LIMIT '.$_limit_a.','.$_limit_b;
				}
			}
			if($_order == 'v.subscribers--dn' || $_order == 'v.subscribers--up'){
				$_table='lpb_click_'.Core_Users::$info['id'];
				if($_order == 'v.subscribers--dn'){
					$_sort='DESC';
				} else {
					$_sort='ASC';
				}
				if( !empty( $this->_withPaging ) ){
					$_limit=' LIMIT '.$_limit_a.','.$_limit_b;
				}
			}
			if($_order == 'cv.crt--up' || $_order == 'cv.crt--dn'){
				$_table='lpb_view_'.Core_Users::$info['id'];
				$_limit='';
			}
			$_arrZeroDataFrom=$_arrZeroDataTo=''; // AND c.added>=( UNIX_TIMESTAMP( )-60*60*24*30 )
			$_dDate=60*60*24*30;
			if( !empty( $_date )){
				if( isset( $_date['from'] ) ){
					$_arrZeroDataFrom=' AND c.added>='.$_date['from'];
					$_dDate=time()-$_date['from'];
				}
				if( isset( $_date['to'] ) ){
					$_arrZeroDataTo=' AND c.added<='.$_date['to'];
					if( isset( $_date['from'] ) ){
						$_dDate=$_date['to']-$_date['from'];
					}
				}
			}else{
				$_arrZeroDataFrom=' AND c.added>=( UNIX_TIMESTAMP( )-60*60*24*30 )';
			}
			Core_Sql::getInstance();
			try{
				Core_Sql::setConnectToServer( 'syndication.qjmpz.com' );
				// список стран
				//=========
				$_countries=Core_Sql::getKeyVal('SELECT id, name FROM getip_countries');
				//=========
				Core_Sql::renewalConnectFromCashe();
			} catch(Exception $e){
				Core_Sql::renewalConnectFromCashe();
			}
			try{
				Core_Sql::setConnectToServer( 'lpb.tracker' );
				if( !empty( $this->_withPaging ) ){
					$this->_crawler->page=(empty($this->_withPaging['page']) ? 1 : (int)$this->_withPaging['page']);
					$this->_crawler->rowtotal=count( Core_Sql::getAssoc('SELECT c.squeeze_id FROM '.$_table.' c WHERE c.squeeze_id IN('.Core_Sql::fixInjection( $_arrSqueezeIds ).') GROUP BY c.squeeze_id') );
					$this->_crawler->m_paged_bar( $this->_paging );
				}
				$this->_searchTags['order']=array($_order);
				// надо получить список всех LPB_ID пользователя по которым есть данные в статистике, а также суммарные данные по ним сразу
				$_arrZeroData=Core_Sql::getAssoc('SELECT c.squeeze_id, COUNT(*) as rec_count  FROM '.$_table.' c WHERE c.squeeze_id IN('.Core_Sql::fixInjection( $_arrSqueezeIds ).') '.$_arrZeroDataFrom.$_arrZeroDataTo.' GROUP BY c.squeeze_id '.$_sort.$_limit);
				$_arrSqueezeIds=array();
				$_tmp=array();
				foreach ($_arrZeroData as $key => $value){
					$_arrSqueezeIds[]=$value['squeeze_id'];
					$_tmp[$value['squeeze_id']]=$value['rec_count'];
				}
				if( empty( $_arrSqueezeIds ) ){
					Core_Sql::renewalConnectFromCashe();
					return $this;
				}
				$_arrZeroData=$_tmp;
				// получаем первый список данных по всем LPB_ID сразу
				$_arrFirstData=Core_Sql::getAssoc('SELECT c.squeeze_id, c.country_id, COUNT(*) as counter, '
					.(( $_dDate<=10*24*60*60 )? 'FROM_UNIXTIME(c.added, "%Y-%m-%d %h:00") as fadded ' : 'FROM_UNIXTIME(c.added, "%Y-%m-%d") as fadded ' )
					.'FROM '.$_table.' c 
					WHERE c.squeeze_id IN('.Core_Sql::fixInjection( $_arrSqueezeIds ).')'.$_arrZeroDataFrom.$_arrZeroDataTo.'
					GROUP BY fadded, c.squeeze_id, c.country_id');
				$_tmp=array();
				$_writer=new Zend_Log_Writer_Stream( Zend_Registry::get('config')->path->absolute->logfiles.'ip.log' );
				$_writer->setFormatter( new Zend_Log_Formatter_Simple("%timestamp% %priorityName% (%priority%): %message%\r\n") );
				$_logger=new Zend_Log( $_writer );
				foreach ($_arrFirstData as $key => $value){
					if( !isset($_countries[$value['country_id']]) ){
						$_countries[0]='Undefined';
					}
					if( !isset($_tmp[$value['squeeze_id']]) ){
						$_tmp[$value['squeeze_id']]=array(
							'countries' => array( 
								$_countries[$value['country_id']] => $value['counter']
							),
							'date' => array(
								$value['fadded'] => $value['counter']
							),
						);
					}else{
						if( !isset( $_tmp[$value['squeeze_id']]['countries'][ $_countries[$value['country_id']] ] ) ){
							$_tmp[$value['squeeze_id']]['countries'][ $_countries[$value['country_id']] ]=$value['counter'];
						}else{
							$_tmp[$value['squeeze_id']]['countries'][ $_countries[$value['country_id']] ]+=$value['counter'];
						}
						if( !isset( $_tmp[$value['squeeze_id']]['date'][ $value['fadded'] ] ) ){
							$_tmp[$value['squeeze_id']]['date'][ $value['fadded'] ]=$value['counter'];
						}else{
							$_tmp[$value['squeeze_id']]['date'][ $value['fadded'] ]+=$value['counter'];
						}
					}
				}
				$_arrFirstData=$_tmp;
				// получаем второй список данных по всем LPB_ID сразу
				if($_table == 'lpb_click_'.Core_Users::$info['id']) $_table2='lpb_view_'.Core_Users::$info['id']; else $_table2='lpb_click_'.Core_Users::$info['id'];
				$_arrSecondData=Core_Sql::getAssoc('SELECT c.squeeze_id, c.country_id, COUNT(*) as counter, '
					.(( $_dDate<=10*24*60*60 )? 'FROM_UNIXTIME(c.added, "%Y-%m-%d %h:00") as fadded ' : 'FROM_UNIXTIME(c.added, "%Y-%m-%d") as fadded ' )
					.'FROM '.$_table2.' c 
					WHERE c.squeeze_id IN('.Core_Sql::fixInjection( $_arrSqueezeIds ).')'.$_arrZeroDataFrom.$_arrZeroDataTo.'
					GROUP BY fadded, c.squeeze_id, c.country_id');
				$_tmp=array();
				foreach ($_arrSecondData as $key => $value){
					if( !isset($_countries[$value['country_id']]) ){
						$_countries[0]='Undefined';
					}
					if( !isset($_tmp[$value['squeeze_id']]) ){
						$_tmp[$value['squeeze_id']]=array(
							'countries' => array( 
								$_countries[$value['country_id']] => $value['counter']
							),
							'date' => array(
								$value['fadded'] => $value['counter']
							),
						);
					}else{
						if( !isset( $_tmp[$value['squeeze_id']]['countries'][ $_countries[$value['country_id']] ] ) ){
							$_tmp[$value['squeeze_id']]['countries'][ $_countries[$value['country_id']] ]=$value['counter'];
						}else{
							$_tmp[$value['squeeze_id']]['countries'][ $_countries[$value['country_id']] ]+=$value['counter'];
						}
						if( !isset( $_tmp[$value['squeeze_id']]['date'][ $value['fadded'] ] ) ){
							$_tmp[$value['squeeze_id']]['date'][ $value['fadded'] ]=$value['counter'];
						}else{
							$_tmp[$value['squeeze_id']]['date'][ $value['fadded'] ]+=$value['counter'];
						}
					}
				}
				$_arrSecondData=$_tmp;
				Core_Sql::renewalConnectFromCashe();
			} catch(Exception $e){
				Core_Sql::renewalConnectFromCashe();
			}
			try {
				Core_Sql::setConnectToServer( 'syndication.qjmpz.com' );
				$_checkTable=Core_Sql::getAssoc('show tables like "lpb_utm"');
				$_arrUtmData=array();
				if( !empty( $_checkTable ) ){
					$_arrUtmData=Core_Sql::getAssoc('SELECT c.squeeze_id, c.utm_source, c.utm_medium, c.utm_term, c.utm_content, c.utm_campaign, SUM(c.view) as visitors, SUM(c.click) as clicks FROM lpb_utm c WHERE c.squeeze_id IN('.Core_Sql::fixInjection( $_oldSqueezeIds ).') '.$_arrZeroDataFrom.$_arrZeroDataTo.' GROUP BY c.squeeze_id, c.utm_source, c.utm_medium, c.utm_term, c.utm_content, c.utm_campaign ORDER BY visitors DESC');
				}
				Core_Sql::renewalConnectFromCashe();
			} catch(Exception $e){
				Core_Sql::renewalConnectFromCashe();
			}
			foreach ($_arrZeroData as $key => $value){
				// считаем только по второму, т.к. первое получили и посчитали в $_arrZeroData
				$_intSecondDataSumm=0;
				foreach( $_arrSecondData[$key]['countries'] as $_intCountryData ){
					$_intSecondDataSumm+=$_intCountryData;
				}
				$mixRes[$key]=array(
					($_table == 'lpb_view_'.Core_Users::$info['id'] ? 'visitors' : 'clicks') => $value,
					($_table2 == 'lpb_click_'.Core_Users::$info['id'] ? 'clicks' : 'visitors') => $_intSecondDataSumm,
					($_table == 'lpb_view_'.Core_Users::$info['id'] ? 'arr_visitors' : 'arr_clicks') => $_arrFirstData[$key],
					($_table2 == 'lpb_click_'.Core_Users::$info['id'] ? 'arr_clicks' : 'arr_visitors') => $_arrSecondData[$key],
				);
				$mixRes[$key]['rate']=sprintf("%01.2f", $mixRes[$key]['clicks']/$mixRes[$key]['visitors']*100);
			}
			if($_order == 'cv.crt--up'){
				function cmp($a, $b){
					return $a["rate"] >  $b["rate"];
				}
				uasort($mixRes, "cmp");
			} elseif ($_order == 'cv.crt--dn'){
				function cmp($a, $b){
					return $a["rate"] <  $b["rate"];
				}
				uasort($mixRes, "cmp");
			}
			$_arrSqueezeIds=array();
			foreach ($mixRes as $key => $value){
				$_arrSqueezeIds[]=$key;
			}
			$this->withIds( $_arrSqueezeIds )->withoutSort()->onlyOwner()->getList($_arrZeroData);
			foreach( $_arrZeroData as &$_item ){
				if(isset($mixRes[$_item['id']]))
					$mixRes[$_item['id']]=array_merge ($_item, $mixRes[$_item['id']]);
			}
			if($_order == 'cv.crt--up' || $_order == 'cv.crt--dn'){
				$mixRes=array_slice($mixRes, $_limit_a, $_limit_b);
			}
			$mixRes=array_values($mixRes);
		} else {
			parent::getList( $mixRes );
			if(array_key_exists( 0, $mixRes )){
				$_arrSqueezeIds=array();
				foreach( $mixRes as &$_item ){
					$_arrSqueezeIds[]= $_item['id'];
				}
				$_arrZeroData=$this->getVisitors($_arrSqueezeIds);
				foreach( $mixRes as &$_item ){
					$_item['visitors']=$_arrZeroData[(int)$_item['id']]['subscribers'];
					$_item['clicks']=$_arrZeroData[(int)$_item['id']]['conversions'];
					if( $_arrZeroData[(int)$_item['id']]['subscribers'] != 0 ){
						$_item['rate']=sprintf("%01.2f", $_arrZeroData[(int)$_item['id']]['conversions']/$_arrZeroData[(int)$_item['id']]['subscribers']*100 );
					}else{
						$_item['rate']='';
					}
				}
			}
		}

		if( empty($mixRes) ){
			return $this;
		}
		if( array_key_exists( 0, $mixRes ) ){
			foreach( $mixRes as &$_arrZeroData ){
				$_oldSettings=$_arrZeroData['settings'];
				$_arrZeroData['tpl_settings']=unserialize( base64_decode( $_arrZeroData['tpl_settings'] ) );
				$_arrZeroData['settings']=unserialize( base64_decode( $_arrZeroData['settings'] ) );
				if( $_arrZeroData['settings']===false ){
					$_arrZeroData['settings']=unserialize( $_arrZeroData['settings'] );
				}
				if( $_arrZeroData['settings']===false ){
					$_arrZeroData['settings']=unserialize( json_decode( str_replace( '\r\n\n', '\r\n', json_encode( $_oldSettings ) ) ) );
				}
				if( $_arrZeroData['settings']===false ){
					$_arrZeroData['settings']=$_oldSettings;
				}
				$_arrZeroData['tags']=htmlentities($_arrZeroData['tags']);
			}
		}else{
			$_oldSettings=$mixRes['settings'];
			$mixRes['tpl_settings']=unserialize( base64_decode( $mixRes['tpl_settings'] ) );
			$mixRes['settings']=unserialize( base64_decode( $mixRes['settings'] ) );
			if( $mixRes['settings']===false ){
				$mixRes['settings']=unserialize( $mixRes['settings'] );
			}
			if( $mixRes['settings']===false ){
				$mixRes['settings']=unserialize( json_decode( str_replace( '\r\n\n', '\r\n', json_encode( $_oldSettings ) ) ) );
			}
			if( $mixRes['settings']===false ){
				$mixRes['settings']=$_oldSettings;
			}
			$_arrZeroData['tags']=htmlentities($_arrZeroData['tags']);
		}
		if( !empty( $_arrUtmData ) ){
			foreach( $mixRes as &$_data ){
				foreach( $_arrUtmData as $key=>$_utm ){
					if( $_utm['squeeze_id'] == $_data['id'] ){
						if( !isset( $_data['utm_log'] ) ){
							$_data['utm_log']=array();
						}
						$_data['utm_log'][]=$_utm;
						unset( $_arrUtmData[$key] );
					}
				}
			}
		}
		return $this;
	}

	public function getVisitors( $_arrSqueeze=array() ){
		if( empty( $_arrSqueeze ) ){
			return false;
		}
		try{
			Core_Sql::setConnectToServer( 'lpb.tracker' );
			$_arrZeroData=array();
			$_arrSqueezeSubscribers=Core_Sql::getAssoc('SELECT c.squeeze_id, COUNT(*) rec_count  FROM lpb_view_'.Core_Users::$info['id'].' c WHERE c.squeeze_id IN('.Core_Sql::fixInjection( $_arrSqueeze ).') AND c.added>=(UNIX_TIMESTAMP()-60*60*24*30) GROUP BY c.squeeze_id');
			$_arrSqueezeConversions=Core_Sql::getAssoc('SELECT c.squeeze_id, COUNT(*) rec_count  FROM lpb_click_'.Core_Users::$info['id'].' c WHERE c.squeeze_id IN('.Core_Sql::fixInjection( $_arrSqueeze ).') AND c.added>=(UNIX_TIMESTAMP()-60*60*24*30) GROUP BY c.squeeze_id');
			Core_Sql::renewalConnectFromCashe();
		} catch(Exception $e){
			Core_Sql::renewalConnectFromCashe();
			return false;
		}
		if($_arrSqueezeSubscribers){
			foreach ($_arrSqueezeSubscribers as $key => $value){
				if(!isset($_arrZeroData[$value['squeeze_id']])){
					$_arrZeroData[$value['squeeze_id']]=array( 'subscribers' => $value['rec_count'] );
				}
			}
		}
		if($_arrSqueezeConversions){
			foreach ($_arrSqueezeConversions as $key => $value){
				if(!isset($_arrZeroData[$value['squeeze_id']])){
					$_arrZeroData[$value['squeeze_id']]=array( 'conversions' => $value['rec_count'] );;
				} else {
					$_arrZeroData[$value['squeeze_id']]['conversions']=$value['rec_count'];
				} 
			}
		}
		return $_arrZeroData;
	}

	public static function searchImageGoggle( $_word ){
		$_url='https://ajax.googleapis.com/ajax/services/search/images?v=1.0&imgsz=huge&q='.str_replace( ' ', '+', $_word);
		$_curl=new Core_Curl();
		$_curl->getContent($_url);
		$json=$_curl->getResponce();
		return $json;
	}

	public static function getImageFromLink( $_link ){
	//	if( isset( $_SERVER['HTTP_HOST'] ) && $_SERVER['HTTP_HOST'] == 'cnm.local' ){
	//		return '{"return":"false", "error":"localrun"}';
   	//	}
		$_linkContent=file_get_contents( $_link );
		if( $_linkContent === false || empty( $_linkContent ) ){
			return '{"return":"false", "error":"emptydata"}';
		}
		$URL2PNG_APIKEY="PAA11E0D3718E90";
		$URL2PNG_SECRET="S_4ED60B58B10F8";
		$options['force']='false';   # [false,always,timestamp] Default: false
		$options['fullpage']='false';   # [true,false] Default: false
		$options['thumbnail_max_width']='false';   # scaled image width in pixels; Default no-scaling.
		$options['viewport']="1280x1024";  # Max 5000x5000; Default 1280x1024
		$options['url']=urlencode( $_link ); # urlencode request target
		foreach($options as $key => $value){ $_parts[]="$key=$value"; } # create the query string based on the options
		$query_string=implode("&", $_parts); # create a token from the ENTIRE query string
		
		$_dirTmp='Project_Squeeze@genereteFromLink';
		if ( !Zend_Registry::get( 'objUser' )->checkTmpDir( $_tmpDir ) ){
			return '{"return":"false", "error":"cantcreatedir"}';
		}
		$_checkDirTmp=$_tmpDir.$_dirTmp.DIRECTORY_SEPARATOR;
		if ( !is_dir( $_checkDirTmp ) ){
			if( !Zend_Registry::get( 'objUser' )->prepareTmpDir( $_dirTmp ) ){
				return '{"return":"false", "error":"cantcreatetemplate"}';
			}
		}
		$_dirTmp=trim( $_checkDirTmp, '.' );
		$screen = new Project_Pagebuilder_Screenshot();
		$screenshot = $screen->make_screenshot($_link, md5($_link).".jpg", '1200x800', '.'.$_dirTmp);
		return '{"responseData":{"results":[{"url":"'.$_dirTmp.md5($_link).'.jpg","tbWidth":"150","tbHeight":"99"}]},"output":"","return":"true", "action":"from Pagebuilder"}';
		/*
		if( copy( "https://api.url2png.com/v6/".$URL2PNG_APIKEY."/".md5($query_string.$URL2PNG_SECRET)."/png/?".$query_string, $_dirTmp.md5($_link).".jpg" ) ){
			return '{"responseData":{"results":[{"url":"'.$_dirTmp.md5($_link).'.jpg","tbWidth":"150","tbHeight":"99"}]},"output":"'.stripslashes( serialize( $output ) ).'","return":"true", "action":"from url2png"}';
		}else{
			$_action="sudo -u members.cnm.info /usr/local/bin/wkhtmltoimage --width 1200 --height 800 --quality 100 --zoom 1 --enable-javascript --javascript-delay 1000 ".escapeshellarg($_link)." ".escapeshellarg($_dirTmp.md5($_link).".jpg");
			exec($_action, $output, $return);
			return '{"responseData":{"results":[{"url":"'.$_dirTmp.md5($_link).'.jpg","tbWidth":"150","tbHeight":"99"}]},"output":"'.stripslashes( serialize( $output ) ).'","return":"'.$return.'", "action":"'.str_replace( '"', "'", $_action ).'"}';
		}
		*/
	}

	public static function getButtons(){
		$_dir=Zend_Registry::get('config')->path->absolute->user_files.'squeeze'.DIRECTORY_SEPARATOR.'buttons'.DIRECTORY_SEPARATOR;
		Core_Files::dirScan( $arrTmp, $_dir );
		$_tags=new Project_Squeeze_Buttontags();
		$_tags->getList( $_arrAllTags );
		foreach( array_shift($arrTmp) as $_item ){
			$_flgHaveTags=false;
			foreach( $_arrAllTags as $_tags ){
				if( $_tags['id'] == md5($_item.'#button') ){
					$_flgHaveTags=true;
					$arrRes[]=array(
						'tags'=>$_tags['tags'],
						'name'=>$_item,
						'title'=>ucfirst(str_replace(array('_','-'),array(' ',' '),Core_Files::getFileName($_item))),
						'preview'=>Core_Files_Image_Thumbnail::generate( array('src'=>$_dir.$_item) )
					);
					break;
				}
			}
			if( !$_flgHaveTags ){
				$arrRes[]=array(
					'tags'=>'',
					'name'=>$_item,
					'title'=>ucfirst(str_replace(array('_','-'),array(' ',' '),Core_Files::getFileName($_item))),
					'preview'=>Core_Files_Image_Thumbnail::generate( array('src'=>$_dir.$_item) )
				);
			}
		}
		return $arrRes;
	}

	public static function getPhoneButtons(){
		$_dir=Zend_Registry::get('config')->path->absolute->user_files.'squeeze'.DIRECTORY_SEPARATOR.'phonebuttons'.DIRECTORY_SEPARATOR;
		Core_Files::dirScan( $arrTmp, $_dir );
		$_tags=new Project_Squeeze_Buttontags();
		$_tags->getList( $_arrAllTags );
		foreach( array_shift($arrTmp) as $_item ){
			$_flgHaveTags=false;
			foreach( $_arrAllTags as $_tags ){
				if( $_tags['id'] == md5($_item.'#phone') ){
					$_flgHaveTags=true;
					$arrRes[]=array(
						'tags'=>$_tags['tags'],
						'name'=>$_item,
						'title'=>ucfirst(str_replace(array('_','-'),array(' ',' '),Core_Files::getFileName($_item))),
						'preview'=>Core_Files_Image_Thumbnail::generate( array('src'=>$_dir.$_item) )
					);
					break;
				}
			}
			if( !$_flgHaveTags ){
				$arrRes[]=array(
					'tags'=>'',
					'name'=>$_item,
					'title'=>ucfirst(str_replace(array('_','-'),array(' ',' '),Core_Files::getFileName($_item))),
					'preview'=>Core_Files_Image_Thumbnail::generate( array('src'=>$_dir.$_item) )
				);
			}
		}
		return $arrRes;
	}

	public static function getCountries(){
		return json_decode( @file_get_contents('library/GeoIp/geoip_data.json'), true );
	}

	public static function getBackgrounds(){
		$_dir=Zend_Registry::get('config')->path->absolute->user_files.'squeeze'.DIRECTORY_SEPARATOR.'backgrounds'.DIRECTORY_SEPARATOR;
		Core_Files::dirScan( $arrTmp, $_dir );
		foreach( array_shift($arrTmp) as $_item ){
			$arrRes[]=array(
				'name'=>$_item,
				'title'=>ucfirst(str_replace(array('_','-'),array(' ',' '),Core_Files::getFileName($_item))),
				'preview'=>Core_Files_Image_Thumbnail::generate( array('src'=>$_dir.$_item,'w'=>200,'h'=>200) )
			);
		}
		return $arrRes;
	}

	public static function getAllButtons(){
		$_dir=Zend_Registry::get('config')->path->absolute->user_files.'squeeze'.DIRECTORY_SEPARATOR.'buttons'.DIRECTORY_SEPARATOR;
		Core_Files::dirScan( $arrTmp, $_dir );
		$_tags=new Project_Squeeze_Buttontags();
		$_tags->getList( $_arrAllTags );
		foreach( array_shift($arrTmp) as $_item ){
			$_md5Name=md5($_item.'#button');
			$_flgHaveTags=false;
			foreach( $_arrAllTags as $_tags ){
				if( $_tags['id'] == $_md5Name ){
					$_flgHaveTags=true;
					$arrRes[]=array(
						'hash'=>$_md5Name,
						'tags'=>$_tags['tags'],
						'flg_type'=>'button',
						'name'=>$_item,
						'title'=>ucfirst(str_replace(array('_','-'),array(' ',' '),Core_Files::getFileName($_item))),
						'preview'=>Core_Files_Image_Thumbnail::generate( array('src'=>$_dir.$_item,'w'=>200,'h'=>200) )
					);
					break;
				}
			}
			if( !$_flgHaveTags ){
				$arrRes[]=array(
					'hash'=>$_md5Name,
					'tags'=>'',
					'flg_type'=>'button',
					'name'=>$_item,
					'title'=>ucfirst(str_replace(array('_','-'),array(' ',' '),Core_Files::getFileName($_item))),
					'preview'=>Core_Files_Image_Thumbnail::generate( array('src'=>$_dir.$_item,'w'=>200,'h'=>200) )
				);
			}
		}
		$_dir=Zend_Registry::get('config')->path->absolute->user_files.'squeeze'.DIRECTORY_SEPARATOR.'phonebuttons'.DIRECTORY_SEPARATOR;
		Core_Files::dirScan( $arrTmp, $_dir );
		foreach( array_shift($arrTmp) as $_item ){
			$_md5Name=md5($_item.'#phone');
			$_flgHaveTags=false;
			foreach( $_arrAllTags as $_tags ){
				if( $_tags['id'] == $_md5Name ){
					$_flgHaveTags=true;
					$arrRes[]=array(
						'hash'=>$_md5Name,
						'tags'=>$_tags['tags'],
						'flg_type'=>'phone',
						'name'=>$_item,
						'title'=>ucfirst(str_replace(array('_','-'),array(' ',' '),Core_Files::getFileName($_item))),
						'preview'=>Core_Files_Image_Thumbnail::generate( array('src'=>$_dir.$_item,'w'=>200,'h'=>200) )
					);
					break;
				}
			}
			if( !$_flgHaveTags ){
				$arrRes[]=array(
					'hash'=>$_md5Name,
					'tags'=>'',
					'flg_type'=>'phone',
					'name'=>$_item,
					'title'=>ucfirst(str_replace(array('_','-'),array(' ',' '),Core_Files::getFileName($_item))),
					'preview'=>Core_Files_Image_Thumbnail::generate( array('src'=>$_dir.$_item,'w'=>200,'h'=>200) )
				);
			}
		}
		return $arrRes;
	}
	
	public static function getUserSounds(){
		$_dir=Zend_Registry::get('config')->path->absolute->user_files.'squeeze'.DIRECTORY_SEPARATOR.'sound'.DIRECTORY_SEPARATOR;
		Core_Files::dirScan( $arrTmp, $_dir );
		foreach( array_shift($arrTmp) as $_item ){
			$arrRes[]=array(
				'name'=>$_item,
				'title'=>ucfirst(str_replace(array('_','-'),array(' ',' '),Core_Files::getFileName($_item))),
				'preview'=>Core_Files_Image_Thumbnail::generate( array('src'=>$_dir.$_item,'w'=>200,'h'=>200) )
			);
		}
		return $arrRes;
	}

	public static function uploadSound( $_file, $_arrZeroData=array() ){
		if( !isset( $_arrZeroData['category_id'] ) || empty( $_arrZeroData['category_id'] ) ){
			$_sysname='squeeze_user_sounds';
		}else{
			$_sysname='squeeze_default_sounds';
		}
		if($_file['error']!=0){
			if( !empty( $_arrZeroData ) && $_file['error']==4 ){
				$_object=new Project_Files_Squeeze( $_sysname );
				if( $_object->setMode( Project_Files_Squeeze::$mode['onlyDataEdit'] )->setEntered( $_arrZeroData )->set() ){
					if( isset( $_arrZeroData['category_id'] ) && !empty( $_arrZeroData['category_id'] ) ){
						Core_Sql::setUpdate( 'category2file_squeeze_tree', array('category_id'=>$_arrZeroData['category_id'], 'file_id'=>$_object->_fileId ), 'file_id' );
					}
					return true;
				}
			}
			return false;
		}
		$_object=new Project_Files_Squeeze( $_sysname );
		if ( !empty( $_file['name'] ) ){
			if( Core_Files::getExtension( $_file['name'] )== 'zip' ){
				//mass insert
				set_time_limit(0);
				ignore_user_abort(true);
				error_reporting(E_ALL);
				if ( !Zend_Registry::get( 'objUser' )->checkTmpDir( $_userTmpDir ) ){
					throw new Exception( Core_Errors::DEV.'|Zend_Registry::get( \'objUser\' )->checkTmpDir( $_tmpDir ) no _userTmpDir set' );
					return false;
				}
				$_objFiles=new Core_Files();
				$_strExtractDir='Project_Squeeze@extractZip';
				if ( !Zend_Registry::get( 'objUser' )->prepareTmpDir( $_strExtractDir ) ){
					return false;
				}
				$zip=new Core_Zip();
				if ( true != $zip->open( $_file['tmp_name'] ) ){
					return false;
				}
				$_bool=$zip->extractTo( $_strExtractDir );
				$zip->close();
				if( !$_bool ){
					return false;
				}
				$_objFiles->dirScan( $files, $_strExtractDir);
				if ( empty( $files ) ){
					return false;
				}
				$arrFiles=self::dirTree2Array( $files );
				foreach( $arrFiles as $_oneFile ){
					if( $_object->setEntered( array( 'title'=>Core_Files::getFileName( $_oneFile ) , 'description'=>$_arrZeroData['description'] ) )->setMode( Core_Files_Info::$mode['copy'] )->setEnteredFile( array(
						'name'=>Core_Files::getFileName( $_oneFile ).'.'.Core_Files::getExtension( $_oneFile ),
						'type'=>'audio/mpeg',
						'tmp_name'=>$_oneFile,
						'error'=>0
					) )->set() ){
						if( isset( $_arrZeroData['category_id'] ) && !empty( $_arrZeroData['category_id'] ) ){
							Core_Sql::setInsert( 'category2file_squeeze_tree', array('category_id'=>$_arrZeroData['category_id'], 'file_id'=>$_object->_fileId ) );
						}
					}
				}
				return true;
			}else{
				//single insert
				if( $_object->setEntered( array( 'title'=>$_arrZeroData['title'], 'description'=>$_arrZeroData['description'] ) )->setEnteredFile( $_file )->set() ){
					if( isset( $_arrZeroData['category_id'] ) && !empty( $_arrZeroData['category_id'] ) ){
						Core_Sql::setInsert( 'category2file_squeeze_tree', array('category_id'=>$_arrZeroData['category_id'], 'file_id'=>$_object->_fileId ) );
					}
					return true;
				}
			}
			return false;
		}
		return false;
	}
	
	public static function dirTree2Array( $tree ){
		$newArray=array();
		foreach( $tree as $dir=>$array ){
			foreach( $array as $name ){
				$newArray[]=$dir.DIRECTORY_SEPARATOR.$name;
			}
		}
		return $newArray;
	}
	
	public static function deleteDefaultSounds( $id ){
		$_object=new Project_Files_Squeeze('squeeze_default_sounds');
		$_object->withIds( $id )->utilization();
		Core_Sql::setExec( 'DELETE FROM category2file_squeeze_tree WHERE file_id='.Core_Sql::fixInjection( $id ) );
		return true;
	}

	public static function getDefaultSounds(){
		$_object=new Project_Files_Squeeze('squeeze_default_sounds');
		$_object->getList( $arrFiles );
		foreach( $arrFiles as &$_file ){
			$_file['category_id']=Core_Sql::getCell( 'SELECT category_id FROM category2file_squeeze_tree WHERE file_id='.$_file['id'] );
		}
		return $arrFiles;
	}
	
	public static function getDefaultSound( $id ){
		$_object=new Project_Files_Squeeze('squeeze_default_sounds');
		$_object->onlyOne()->withIds( $id )->getList( $arrFile );
		if( isset( $arrFile['id'] ) ){
			$arrFile['category_id']=Core_Sql::getCell( 'SELECT category_id FROM category2file_squeeze_tree WHERE file_id='.$arrFile['id'] );
			return $arrFile;
		}
		return false;
	}

	public static function upload( $_arrZeroData, $_type='backgrounds', $tags=false ){
		if(!is_file($_arrZeroData['tmp_name'])){
			return false;
		}
		if ( !empty( $_arrZeroData['name'] ) ){
			if( Core_Files::getExtension( $_arrZeroData['name'] )== 'zip' ){
				//mass insert
				set_time_limit(0);
				ignore_user_abort(true);
				error_reporting(E_ALL);
				if ( !Zend_Registry::get( 'objUser' )->checkTmpDir( $_userTmpDir ) ){
					throw new Exception( Core_Errors::DEV.'|Zend_Registry::get( \'objUser\' )->checkTmpDir( $_tmpDir ) no _userTmpDir set' );
					return false;
				}
				$_objFiles=new Core_Files();
				$_strExtractDir='Project_Squeeze@extractZip';
				if ( !Zend_Registry::get( 'objUser' )->prepareTmpDir( $_strExtractDir ) ){
					return false;
				}
				$zip=new Core_Zip();
				if ( true != $zip->open( $_arrZeroData['tmp_name'] ) ){
					return false;
				}
				$_bool=$zip->extractTo( $_strExtractDir );
				unlink();
				$zip->close();
				if( !$_bool ){
					return false;
				}
				$_objFiles->dirScan( $files, $_strExtractDir);
				if ( empty( $files ) ){
					return false;
				}
				$arrFiles=self::dirTree2Array( $files );
				foreach( $arrFiles as $_oneFile ){
					$_name=Core_Files::getFileName( $_oneFile );
					$_name.='.'.Core_Files::getExtension( $_oneFile );
					copy($_oneFile,Zend_Registry::get('config')->path->absolute->user_files.'squeeze'.DIRECTORY_SEPARATOR.$_type.DIRECTORY_SEPARATOR.$_name);
					if( $tags !== false ){
						$_tags=new Project_Squeeze_Buttontags();
						$_tags->setEntered(array(
							'id'=>md5( $_name.( $_type=="buttons"?'#button':( $_type=="phonebuttons"?'#phone':'' ) ) ),
							'tags'=>$tags
						))->set();
					}
				}
				return true;
			}else{
				//single insert
				copy($_arrZeroData['tmp_name'],Zend_Registry::get('config')->path->absolute->user_files.'squeeze'.DIRECTORY_SEPARATOR.$_type.DIRECTORY_SEPARATOR.$_arrZeroData['name']);
				if( $tags !== false ){
					$_tags=new Project_Squeeze_Buttontags();
					$_tags->setEntered(array(
						'id'=>md5( $_arrZeroData['name'].( $_type=="buttons"?'#button':( $_type=="phonebuttons"?'#phone':'' ) ) ),
						'tags'=>$tags
					))->set();
				}
			}
			return false;

			return true;
		}
		return false;
	}

	public static function uploadTmp($_arrZeroData){
		$_dirTmp='Project_Squeeze@uploadTmp';
		if ( !Zend_Registry::get( 'objUser' )->checkTmpDir( $_tmpDir ) ){
			return false;
		}
		$_checkDirTmp=$_tmpDir.$_dirTmp.DIRECTORY_SEPARATOR;
		if ( !is_dir( $_checkDirTmp ) ){
			if( !Zend_Registry::get( 'objUser' )->prepareTmpDir( $_dirTmp ) ){
				return Core_Data_Errors::getInstance()->setError('Can\'t create dir '.$_dirTmp);
			}
		}else{
			$_dirTmp=$_checkDirTmp;
		}
		if( isset( $_arrZeroData['tmp_name'] ) && isset( $_arrZeroData['name'] ) && copy( $_arrZeroData['tmp_name'],$_dirTmp.$_arrZeroData['name'] ) ){
			return trim($_dirTmp.$_arrZeroData['name'],'.');
		}
		return false;
	}

	public static function delete( $_name ){
		$_dir=Zend_Registry::get('config')->path->absolute->user_files.'squeeze'.DIRECTORY_SEPARATOR.'backgrounds'.DIRECTORY_SEPARATOR;
		if(is_file($_dir.$_name)&&unlink($_dir.$_name)){
			return true;
		}
		return false;
	}

	public function hosting_squeeze( $_flgType='ssl' ){
		if ( empty( $this->_withIds ) ){
			return false;
		}
		$this->onlyOwner()->onlyOne()->getList( $_arrData );
		if( $_flgType == 'ssl' ){
			$_arrData['settings']['publishing_options']='local';
		}else{
			$_arrData['settings']['publishing_options']='local_nossl';
		}
		unset( $_arrData['settings']['domain_http'], $_arrData['settings']['ftp_directory'] );
		if( !$this->setEntered( $_arrData )->generate() ){
			return Core_Data_Errors::getInstance()->setError('Can\'t generate squeeze site');
		}
		$this->init();
		return true;
	}

	public function del_squeeze(){
		if ( empty( $this->_withIds ) ){
			return false;
		}
		$this->onlyOwner()->onlyOne()->getList( $arrList );
		if( empty( $arrList ) ){
			return false;
		}
		$_flgRemoved=false;
		if( $arrList['settings']['publishing_options']=='local' || $arrList['settings']['publishing_options']=='local_nossl' || ( in_array( $arrList['settings']['publishing_options'], array('remote', 'external' ) ) && isset( $arrList['settings']['placement_id'] ) ) ){
			$_setting=$arrList['settings'];
			if( $arrList['settings']['publishing_options']=='local' ){
				$_setting=array(
					'flg_type'=> '1',
					'flg_passive'=> '1',
					'flg_checked'=> '2',
					'flg_sended_hosting'=> '0',
					'flg_sended_domain'=> '0',
					'flg_auto'=> '1',
					'domain_http'=> 'onlinenewsletters.net',
					'placement_id'=> NULL,
					'domain_ftp'=> NULL,
					'username'=> NULL,
					'password'=> NULL,
					'db_host'=> NULL,
					'db_name'=> NULL,
					'db_username'=> NULL,
					'db_password'=> NULL,
				)+$_setting;
			}
			if( $arrList['settings']['publishing_options']=='local_nossl' ){
				$_setting=array(
					'flg_type'=> '1',
					'flg_passive'=> '1',
					'flg_checked'=> '2',
					'flg_sended_hosting'=> '0',
					'flg_sended_domain'=> '0',
					'flg_auto'=> '1',
					'domain_http'=> 'consumertips.net',
					'placement_id'=> NULL,
					'domain_ftp'=> NULL,
					'username'=> NULL,
					'password'=> NULL,
					'db_host'=> NULL,
					'db_name'=> NULL,
					'db_username'=> NULL,
					'db_password'=> NULL,
				)+$_setting;
			}
			$_hosting=new Project_Placement_Hosting();
			$_hosting->setHostingInfo( $_setting )->delete();
			/*
			$_transport=new Project_Placement_Transport();
			$_flgRemoved=$_transport
				->setInfo( $_setting )
				->removeDir( $arrList['settings']['ftp_directory'] );
			*/
		}else{
			$_flgRemoved=true;
		}
		$this->init();
		if( $_flgRemoved ){
			return $this->withIds( $arrList['id'] )->del();
		}
		return false;
	}

	public function duplicate_squeeze(){
		if ( empty( $this->_withIds ) ){
			return false;
		}
		$this->onlyOwner()->onlyOne()->getList( $_arrData );
		unset( $_arrData['id'], $_arrData['url'], $_arrData['settings']['domain_http'], $_arrData['settings']['publishing_options'], $_arrData['settings']['ftp_directory'], $_arrData['settings']['placement_id'], $_arrData['settings']['template_hash'], $_arrData['settings']['template_file_path'] );
		$_arrData['settings']['publishing_options']='download';//settings[publishing_options]" value="download
		if( !$this->setEntered( $_arrData )->set() ){
			return Core_Data_Errors::getInstance()->setError('Can\'t save squeeze data');
		}
		$this->init();
		return true;
	}

	public function check(){
		$this->_data->setFilter(array('trim','clear'));
//		if ( empty( $this->_data->setFilter(array('trim','clear'))->filtered['settings']['header'] ) ){
//			return Core_Data_Errors::getInstance()->setError('Incorrect entered Headline data.');
//		}
		return true;
	}

	public function resetStats(){
		if( empty( $this->_withIds ) ){
			return false;
		}
		try{
			Core_Sql::setConnectToServer( 'lpb.tracker' );
			Core_Sql::setExec( 'DELETE FROM lpb_click_'.Core_Users::$info['id'].' WHERE squeeze_id IN ('.Core_Sql::fixInjection( $data['id'] ).')' );
			Core_Sql::setExec( 'DELETE FROM lpb_view_'.Core_Users::$info['id'].' WHERE squeeze_id IN ('.Core_Sql::fixInjection( $data['id'] ).')' );
			Core_Sql::renewalConnectFromCashe();
		} catch(Exception $e){
			Core_Sql::renewalConnectFromCashe();
			return false;
		}
		return true;
	}
	
	public static function getQjmpzService(){
		$_arr=array_chunk( array_reverse( explode( '.', $_SERVER['HTTP_HOST'] ) ), 2 );
		$_strDomain=implode( '.', array_reverse( $_arr[0] ) );
		$_tail=substr( $_strDomain , strripos( $_strDomain, '.' )+1 );
		if ( $_tail!='local' ){
			return "http://qjmpz.com/services/lpb_restrictions.php";//Core_Module::getUrl( array( 'name'=>'site1_traffic', 'action'=>'client_trafic_exchange' ) );
		}elseif( $_tail=='local' ){
			return "http://qjmpz.local/services/lpb_restrictions.php";
		}
	}

	public function getDefaultPage(){
		return file_get_contents( self::getQjmpzService().'?action=geturl' );
	}

	public function setDefaultPage( $_page='' ){
		return file_get_contents( self::getQjmpzService().'?action=seturl&url='.htmlspecialchars( $_page ) );
	}
	
	public static function getRestrictions( $_userId=false ){
		if( $_userId===false && isset( Core_Users::$info['id'] ) ){
			$_userId=Core_Users::$info['id'];
		}
		return @file_get_contents( self::getQjmpzService().'?action=get_user_restrictions&uid='.$_userId );
	}

	public static function sendRestrictions( $_restrictions='', $_flgType=0, $_userId=false ){
		if( $_userId===false && isset( Core_Users::$info['id'] ) ){
			$_userId=Core_Users::$info['id'];
		}
		if( $_restrictions!=='' && $_restrictions!='0' ){
			@file_get_contents( self::getQjmpzService().'?uid='.$_userId.'&restrictions='.urlencode( $_restrictions ).'&flg_type='.(int)$_flgType  );
		}
	}

	public function generate(){
		if( !$this->check() ){
			return false;
		}
		$_flgSaveIfOnlyDownload=false;
		if( empty( $this->_data->filtered['id'] ) ){
			$_flgSaveIfOnlyDownload=true;
		}
		/*---------------------------------------------*/
		$_withLogger=true;
		if( $_withLogger ){
			$_writer=new Zend_Log_Writer_Stream( Zend_Registry::get('config')->path->absolute->logfiles.'Project_Squeeze_timing.log' );
			$_writer->setFormatter( new Zend_Log_Formatter_Simple("%timestamp% %priorityName% (%priority%): %message%\r\n") );
			$_logger=new Zend_Log( $_writer );
			$_start=microtime(true);
			$_logger->info('Start -----------------------------------------------------------------------------------------------------' );
		//	$_logger->info('Transaction: '.base64_encode( serialize($this->_data->filtered) ) );
		}
		/*---------------------------------------------*/
		if( $this->_data->filtered['settings']['publishing_options'] != 'preview' && empty( $this->_data->filtered['id'] ) ){
			$this->_data->setFilter( array( 'clear' ) );
			if( !$this->set() ){
				return Core_Data_Errors::getInstance()->setError('Can\'t save squeeze data');
			}
			$this->getEntered($_arrZeroData);
			$this->_data->setElements($_arrZeroData);
		}
		/*---------------------------------------------*/
		if( $_withLogger ){
			$_start=microtime(true)-$_start;
			$_logger->info('Save time: '.$_start );
			$_logger->info('Save id: '.serialize($this->_data->filtered['id']) );
			$_start=microtime(true);
		}
		$_tplFilePos=Zend_Registry::get('config')->path->absolute->user_files.'squeeze'.DIRECTORY_SEPARATOR.'screenshots'.DIRECTORY_SEPARATOR.md5( $this->_data->filtered['settings']['url'] ).'.jpg';
		if( isset( $this->_data->filtered['settings']['url'] )
			&& !empty( $this->_data->filtered['settings']['url'] )
			&& is_file( $_tplFilePos )
		){
			if( is_file( Zend_Registry::get( 'config' )->path->absolute->tumb_cache.md5( $_tplFilePos.filemtime( $_tplFilePos ).'95'.'60' ).'.pic' ) ){
				unlink( Zend_Registry::get( 'config' )->path->absolute->tumb_cache.md5( $_tplFilePos.filemtime( $_tplFilePos ).'95'.'60' ).'.pic' );
			}
			unlink( $_tplFilePos );
		}
		/*---------------------------------------------*/
		if( isset( $this->_data->filtered['settings']['button_type'] ) && $this->_data->filtered['settings']['button_type'] == 'upload'){
			$_dirButtons=Zend_Registry::get('config')->path->absolute->root;
		}else{
			$_dirButtons=Zend_Registry::get('config')->path->absolute->user_files.'squeeze'.DIRECTORY_SEPARATOR.'buttons'.DIRECTORY_SEPARATOR;
			if( $this->_data->filtered['settings']['type_page_through']== 2){
				$_dirButtons=Zend_Registry::get('config')->path->absolute->user_files.'squeeze'.DIRECTORY_SEPARATOR.'phonebuttons'.DIRECTORY_SEPARATOR;
			}
		}
		$_dirSource=Zend_Registry::get('config')->path->absolute->user_files.'squeeze'.DIRECTORY_SEPARATOR;
		$this->_dir='Project_Squeeze@generate';
		if ( !Zend_Registry::get( 'objUser' )->prepareTmpDir( $this->_dir ) ){
			return Core_Data_Errors::getInstance()->setError('Can\'t create dir '.$this->_dir);
		}
		if( !copy($_dirSource.'source.zip',$this->_dir.'source.zip') ){
			return Core_Data_Errors::getInstance()->setError('Cant copy source');
		}
		/*---------------------------------------------*/
		if( $_withLogger ){
			$_start=microtime(true)-$_start;
			$_logger->info('Copy template time: '.$_start );
			$_start=microtime(true);
		}
		/*---------------------------------------------*/
		Core_Zip::getInstance()->setDir( $this->_dir.'source'.DIRECTORY_SEPARATOR )->extractZip( $this->_dir.'source.zip');
		unlink($this->_dir.'source.zip');
		if( $this->_data->filtered['settings']['publishing_options'] == 'preview' ){
			$_parentSrc='//'.$_SERVER['HTTP_HOST'].str_replace(DIRECTORY_SEPARATOR,'/',trim($this->_dir.'source'.DIRECTORY_SEPARATOR,'.'));
		}
		// добавляем файл фона
		if( $this->_data->filtered['settings']['background_google']==1 && $this->_data->filtered['settings']['type_background']=='image' ){
			if( strpos( $this->_data->filtered['settings']['background'], 'usersdata' ) !== false ){
				$this->_data->filtered['settings']['background']='.'.$this->_data->filtered['settings']['background'];
			}
			if( !@copy($this->_data->filtered['settings']['background'],$this->_dir.'source'.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'background.jpg') ){
				if( !isset($this->_data->filtered['url']) 
					|| empty($this->_data->filtered['url']) 
					|| !@copy( $this->_data->filtered['url'].'/source/images/background.jpg', $this->_dir.'source'.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'background.jpg')
				){
					return Core_Data_Errors::getInstance()->setError('Can\'t copy google background');
				}
			}
		}elseif( $this->_data->filtered['settings']['type_background']=='image'){
			if( strpos( $this->_data->filtered['settings']['background'], 'usersdata' ) !== false ){
				$_dirBackgrounds='.';
			}else{
				$_dirBackgrounds=Zend_Registry::get('config')->path->absolute->user_files.'squeeze'.DIRECTORY_SEPARATOR.'backgrounds'.DIRECTORY_SEPARATOR;
			}
			if( !@copy($_dirBackgrounds.$this->_data->filtered['settings']['background'],$this->_dir.'source'.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'background.jpg') ){
				if( !isset($this->_data->filtered['url']) 
					|| empty($this->_data->filtered['url']) 
					|| !@copy( $this->_data->filtered['url'].'/source/images/background.jpg', $this->_dir.'source'.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'background.jpg')
				){
					return Core_Data_Errors::getInstance()->setError('Can\'t copy image background');
				}
			}
		}elseif($this->_data->filtered['settings']['type_background']=='upload' ){
			if( isset( $this->_data->filtered['settings']['upload'] ) &&
				false !== copy('.'.$this->_data->filtered['settings']['upload'], $this->_dir.'source'.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'background.jpg') 
			){
				$this->_data->filtered['settings']['background']=$_parentSrc.$this->_data->filtered['settings']['upload'];
			}else{
				if( isset($this->_data->filtered['url']) && !empty($this->_data->filtered['url']) 
					&& @copy( $this->_data->filtered['url'].'/source/images/background.jpg', $this->_dir.'source'.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'background.jpg')
				){
					$this->_data->filtered['settings']['background']=$this->_data->filtered['url'].'/source/images/background.jpg';
				}else{
					return Core_Data_Errors::getInstance()->setError('Can\'t copy upload background');
				}
			}
		}
		/*---------------------------------------------*/
		if( $_withLogger ){
			$_start=microtime(true)-$_start;
			$_logger->info('Add background time: '.$_start );
			$_start=microtime(true);
		}
		/*---------------------------------------------*/
		$_fileTail='png';
		if( in_array( $this->_data->filtered['settings']['type_page'], array( 1, 2, 3 ) ) ){
			$this->_data->filtered['settings']['button']=str_replace( array("/","\\"),DIRECTORY_SEPARATOR,$this->_data->filtered['settings']['button'] );
			$image=new Core_Files_Image_Thumbnail();
			$_array=explode(".", $this->_data->filtered['settings']['button']);
			$_fileTail=end($_array);
			$this->_data->filtered['settings']['button_settings']=@getimagesize( str_replace( DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR,DIRECTORY_SEPARATOR,$_dirButtons.$this->_data->filtered['settings']['button'] ) );
			$_proportion=$this->_data->filtered['settings']['button_settings'][0]/$this->_data->filtered['settings']['button_settings'][1];
			if( $this->_data->filtered['settings']['box_width']*80/100 <= $this->_data->filtered['settings']['button_settings'][0] ){
				$this->_data->filtered['settings']['button_settings'][0]=$this->_data->filtered['settings']['box_width']*80/100;
//				$this->_data->filtered['settings']['button_settings'][1]=$this->_data->filtered['settings']['button_settings'][0]/$_proportion;
			}
			$image
				->setSrc( $this->_dir.'source'.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'button.'.$_fileTail )
				->setSource( str_replace( DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR,DIRECTORY_SEPARATOR,$_dirButtons.$this->_data->filtered['settings']['button'] ) )
				->setDimension( (int)$this->_data->filtered['settings']['button_settings'][0] )
				->resize();
			$this->_data->filtered['settings']['button_settings']=@getimagesize( $this->_dir.'source'.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'button.'.$_fileTail );
		}
		/*---------------------------------------------*/
		if( $_withLogger ){
			$_start=microtime(true)-$_start;
			$_logger->info('Add button image time: '.$_start );
			$_start=microtime(true);
		}
		/*---------------------------------------------*/
		$arrFiles=array();
		Core_Files::getContent($arrFiles['index.php'],$this->_dir.'source'.DIRECTORY_SEPARATOR.'index.php');
		/*---------------------------------------------*/
		if( $_withLogger ){
			$_start=microtime(true)-$_start;
			$_logger->info('index.php get content time: '.$_start );
			$_start=microtime(true);
		}
		/*---------------------------------------------*/
		$_img=$_js=$backgroundColor=$imageBlur='';
		$bodyColor='#FFF';
		$backgroundTransparence=1;
		if( $this->_data->filtered['settings']['type_background']=='color'
			||$this->_data->filtered['settings']['type_background']=='image'
			||$this->_data->filtered['settings']['type_background']=='upload'
		){
			$bodyColor=$this->_data->filtered['settings']['body_color'];
			$backgroundTransparence=1-($this->_data->filtered['settings']['background_transparency']/100);
			if( $this->_data->filtered['settings']['type_background']=='color' ){
				$backgroundColor='background: '.$this->_data->filtered['settings']['background_color'].';';
			}else{
				$backgroundColor='background: '.$bodyColor.';';
			}
		}
		$fieldsStyles="margin-left: 0px;display:inline-block;";
		if( $this->_data->filtered['settings']['flg_fields_style']== 1 ){
			$fieldsStyles="margin-left:auto;margin-right:auto;display:block;";
		}
		if( $this->_data->filtered['settings']['type_background']=='color' ){
			$_img='<div class="body-bg">&nbsp;</div>';
		}elseif( $this->_data->filtered['settings']['type_background']=='image'||$this->_data->filtered['settings']['type_background']=='upload' ){
			$_img='<div class="body-bg"><img src="'.$_parentSrc.'images/background.jpg" alt="" /></div>';
			if( $this->_data->filtered['settings']['image_blur']==1 ){
				$imageBlur='filter: blur(3px);'
					.'-webkit-filter: blur(3px);'
					.'-moz-filter: blur(3px);'
					.'-o-filter: blur(3px);'
					.'-ms-filter: blur(3px);'
					.'filter: url(blur.svg#blur);'
					.'filter:progid:DXImageTransform.Microsoft.Blur(PixelRadius="3");';
			}
		}elseif($this->_data->filtered['settings']['type_background']=='mp4'){
			$_js="var BV=new $.BigVideo({defaultVolume:".((empty($this->_data->filtered['settings']['mp4_sound']))?0:1).",doLoop:".((!empty($this->_data->filtered['settings']['mp4_loop']))?'true':'false').",controls:".((!empty($this->_data->filtered['settings']['mp4_pause']))?'true':'false').",forceAutoplay:true});
			BV.init();";
			if( !empty( $this->_data->filtered['settings']['mp4_sound'] ) ){
				$_js.="BV.show('".$this->_data->filtered['settings']['mp4']."');";
			}else{
				$_js.="if (Modernizr.touch){
					BV.show('".$this->_data->filtered['settings']['mp4']."');
				}else{
					BV.show('".$this->_data->filtered['settings']['mp4']."',{ambient:true});
				}";
			}
		}elseif($this->_data->filtered['settings']['type_background']=='youtube'){
			$video_id=$this->_data->filtered['settings']['youtube'];
			if ( preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $video_id, $match) && isset($match[1]) ){
				$video_id=$match[1];
			}
			$_js="$('#video-bg').tubular({mute:".((empty($this->_data->filtered['settings']['youtube_sound']))?'true':'false').",videoId: '".$video_id."'});";
			if( $this->_data->filtered['settings']['youtube_pause'] ){
			$_js.="$('.tubular-pause').click(function(){
					if($(this).css('display')=='block'){
						$(this).css('display','none');
						$('.tubular-play').css('display','block');
					} else {
						$(this).css('display','block');
					}
				});
				$('.tubular-play').click(function(){
					if($(this).css('display')=='block'){
						$(this).css('display','none');
						$('.tubular-pause').css('display','block');
					} else {
						$(this).css('display','block');
					}
				});";
			}
		}elseif($this->_data->filtered['settings']['type_background']=='vimeo'){
			$video_id=$this->_data->filtered['settings']['vimeo'];
			if ( preg_match('/^.*(vimeo\.com\/)((channels\/[A-z]+\/)|(groups\/[A-z]+\/videos\/)|([A-z]+\/[A-z]+\/))?([0-9]+)/', $video_id, $match) && isset($match[6]) ){
				$video_id=$match[6];
			}
			$_jsonData=@json_decode( @file_get_contents( 'http://player.vimeo.com/video/'.$video_id.'/config' ), true );
			if( $_jsonData !== false ){
				if( isset( $_jsonData['request']['files']['h264']['sd']['url'] ) ){
					$video_id=$_jsonData['request']['files']['h264']['sd']['url'];
				}
				if( isset( $_jsonData['request']['files']['h264']['hd']['url'] ) ){
					$video_id=$_jsonData['request']['files']['h264']['hd']['url'];
				}
			}
			
			if( $video_id==$match[6] ){
				$video_id='//player.vimeo.com/moog/'.$video_id;
			}
			
			$_js="var BV=new $.BigVideo({defaultVolume:".((empty($this->_data->filtered['settings']['vimeo_sound']))?0:1).",doLoop:".((!empty($this->_data->filtered['settings']['vimeo_loop']))?'true':'false').",controls:".((!empty($this->_data->filtered['settings']['vimeo_pause']))?'true':'false').",forceAutoplay:true});
			BV.init();";
			if( !empty( $this->_data->filtered['settings']['vimeo_sound'] ) ){
				$_js.="BV.show('".$video_id."');";
			}else{
				$_js.="if (Modernizr.touch){
					BV.show('".$video_id."');
				}else{
					BV.show('".$video_id."',{ambient:true});
				}";
			}
		}
		$_fancyBox=$_buttonAction=$_content=$_toggleMode=$_jsOptinPage='';
		if( $this->_data->filtered['settings']['optin']['type'] == 'mooptin' 
			&& isset( $this->_data->filtered['settings']['mo_optin_id'] ) 
			&& !empty( $this->_data->filtered['settings']['mo_optin_id'] )
		){
			$_mooptin=new Project_Mooptin();
			$_mooptin->withIds( $this->_data->filtered['settings']['mo_optin_id'] )->onlyOne()->getList( $_arrMoData );
		}
		if( $this->_data->filtered['settings']['type_page_through']==1
			&& empty( $this->_data->filtered['settings']['popupOnActionId'] )
			&& ( !isset( $this->_data->filtered['settings']['optin']['type'] ) || $this->_data->filtered['settings']['optin']['type'] == 'optin' )
		){
			if( $this->_data->filtered['settings']['optin']['type'] == 'mooptin' 
				&& isset( $this->_data->filtered['settings']['mo_optin_id'] ) 
				&& !empty( $this->_data->filtered['settings']['mo_optin_id'] )
			){
				$form=$this->_data->filtered['settings']['form'];
			}else{
				$form=self::updateForm( $this->_data->filtered['settings']['form'] );
			}
			if($this->_data->filtered['settings']['type_triggered_mode'] == 0){
				$_js.='$(".fancybox").fancybox({helpers:{overlay:{css:{"background":"rgba(58, 42, 45, 0.35)"}}}';
				if( isset( $this->_data->filtered['settings']['flg_show_border'] ) && $this->_data->filtered['settings']['flg_show_border']==1 ){
					$_js.=', padding:0, margin:0';
				}
				$_js.=' });';
				$_fancyBox='<div id="fancybox-form" style="width:auto; display: none;">'.$form.'</div>';
			}
			if($this->_data->filtered['settings']['type_triggered_mode'] == 1){
				$_js .= '$("input.toggleMode").click(function(){ $("div.toggleMode").slideDown("fast"); $(this).hide(); });';
			}
			
		} elseif ( !empty($this->_data->filtered['settings']['popupOnActionId'] ) 
			&& ( !isset( $this->_data->filtered['settings']['optin']['type'] ) || $this->_data->filtered['settings']['optin']['type'] == 'optin' )
		){
			Project_Exquisite::getOnActionCampaign( $this->_data->filtered['settings']['popupOnActionId'], $_fancyBox, $_buttonAction );
		}else{
			if( $this->_data->filtered['settings']['optin']['type'] == 'mooptin' 
				&& isset( $this->_data->filtered['settings']['mo_optin_id'] ) 
				&& !empty( $this->_data->filtered['settings']['mo_optin_id'] )
			){
				$_jsOptinPageToButton='';
				if( $this->_data->filtered['settings']['optinButtonAction'] == 'redirect' && !empty( $this->_data->filtered['settings']['optinButtonActionURL'] ) ){
					$_jsOptinPage=$this->_data->filtered['settings']['optinButtonActionURL'];
				}
				$_moFormAction='';
				if( $this->_data->filtered['settings']['optinButtonAction'] == 'message' && !empty( $this->_data->filtered['settings']['optinButtonActionMessage'] ) ){
					$_jsOptinPageToButton='
		if(jQuery("form").size() > 0){
			var _keyStr="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
			var _utf8_decode=function (utftext){
				var string="";
				var i=0;
				var c=c1=c2=0;
				while ( i < utftext.length ){
					c=utftext.charCodeAt(i);
					if (c < 128){
						string += String.fromCharCode(c);
						i++;
					}
					else if((c > 191) && (c < 224)){
						c2=utftext.charCodeAt(i+1);
						string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
						i += 2;
					}else {
						c2=utftext.charCodeAt(i+1);
						c3=utftext.charCodeAt(i+2);
						string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
						i += 3;
					}
				}
				return string;
			}
			var base64decode=function (input){
				var output="";
				var chr1, chr2, chr3;
				var enc1, enc2, enc3, enc4;
				var i=0;
				input=input.replace(/[^A-Za-z0-9\+\/\=]/g, "");
				while (i < input.length){
					enc1=_keyStr.indexOf(input.charAt(i++));
					enc2=_keyStr.indexOf(input.charAt(i++));
					enc3=_keyStr.indexOf(input.charAt(i++));
					enc4=_keyStr.indexOf(input.charAt(i++));
					chr1=(enc1 << 2) | (enc2 >> 4);
					chr2=((enc2 & 15) << 4) | (enc3 >> 2);
					chr3=((enc3 & 3) << 6) | enc4;
					output=output + String.fromCharCode(chr1);
					if (enc3 != 64){
						output=output + String.fromCharCode(chr2);
					}
					if (enc4 != 64){
						output=output + String.fromCharCode(chr3);
					}
				}
				output=_utf8_decode(output);
				return output;
			}
			if( typeof global_email_status=="string" && global_email_status!="error" ){
				jQuery("form").hide();
				jQuery("form").after(base64decode("'.base64_encode( $this->_data->filtered['settings']['optinButtonActionMessage'] ).'"));
			}
		};
					';
				}
				$this->_data->filtered['settings']['form']=Project_Mooptin::generateForm( $_arrMoData['settings']['optin_form'], $_arrMoData['settings']['form'], $_arrMoData['id'] );
				$_jsMo=Project_Mooptin::generateJsFormAction( $_arrMoData['settings']['optin_form'], $_arrMoData['settings']['form'], $_arrMoData['id'], $_jsOptinPage, $_jsOptinPageToButton );
				$_jsOptinPageToButton='';
				$_js.=$_jsMo['function'];
				$_moFormAction.=$_jsMo['run'];
			}
		}
		$_strPoweredBy='';
		if( isset( $this->_data->filtered['settings']['flg_funnels_widget'] ) && $this->_data->filtered['settings']['flg_funnels_widget']==1 ){
			$_link='https://ethiccash.ethiccash.hop.clickbank.net'; // funnels_jvzoodid
			if( isset( $this->_data->filtered['settings']['funnels_clickbank'] ) && $this->_data->filtered['settings']['funnels_clickbank']!='' ){
				$_link=str_replace( '%CLICKBANKID%', $this->_data->filtered['settings']['funnels_clickbank'], 'https://%CLICKBANKID%.ethiccash.hop.clickbank.net' );
			}
			$_strPoweredBy='<div style="float:right;">Powered by <a href="'.$_link.'" target="_blank">AffiliateFunnels.io</a></div>';
		}
		if( !isset( $this->_data->filtered['settings']['flg_funnel'] ) && ( !isset( $this->_data->filtered['settings']['flg_ads_widget'] ) || $this->_data->filtered['settings']['flg_ads_widget']==1 ) ){
			$_strPoweredBy='<div style="float:right;"><script type="text/javascript" src="'.Zend_Registry::get( 'config' )->domain->url.'/services/widgets.php?name=Copt&action=get&id=VFZSQk0wNVJQVDA9K0E="></script></div>';
		}
		if( isset( $this->_data->filtered['settings']['flg_powered'] ) && $this->_data->filtered['settings']['flg_powered']==0 ){
			$_strPoweredBy='';
		}
		if( isset( $this->_data->filtered['settings']['flg_ads_widget'] ) && $this->_data->filtered['settings']['flg_ads_widget']==2 ){
			$_strPoweredBy='';
		}
		$_strExitPop='
		var runClickButtonAcivation=function(){};
		';
		if( $this->_data->filtered['settings']['flg_misc'] ){
		$_strExitPop.='
		var PreventExitPopup=false;
		jQuery( document ).ready(function(){
		var ExitPopupmessage="'.$this->_data->filtered['settings']['exit_pop_message'].'";
		var ExitPopuppage="'.$this->_data->filtered['settings']['exit_pop_url'].'";
		function addLoadEvent(func){
			var oldonload=window.onload; 
			if (typeof window.onload != "function"){ window.onload=func; } else { window.onload=function(){ if (oldonload){ oldonload(); } func(); }}
		}
		function addClickEvent(a,i,func){
			if (typeof a[i].onclick != "function"){ a[i].onclick=func; } 
		}
		var theDiv=\'<div style="display:block; width:100%; height:100%; position:absolute; background:#FFFFFF; margin-top:0px; margin-left:0px;" align="left">\';
		theDiv=theDiv + \'<div id="redirect_link_lpb">\'+ExitPopupmessage+\'</div>\';
		theDiv=theDiv + \'<iframe src="\'+ExitPopuppage+\'" width="100%" height="100%" align="middle" frameborder="0"></iframe>\';
		theDiv=theDiv + "</div>";
		theBody=document.body; 
		if (!theBody){theBody=document.getElementById("body"); 
		if (!theBody){theBody=document.getElementsByTagName("body")[0];}}
		function DisplayExitPopup(){
			if(PreventExitPopup== false){
				window.scrollTo(0,0);
				PreventExitPopup=true;
				var layerElement=document.getElementById("ExitPopupMainOuterLayer");
				if( typeof layerElement == "undefined" || layerElement == null ){
					divtag=document.createElement("div"); 
					divtag.setAttribute("id","ExitPopupMainOuterLayer" ); 
					divtag.style.position="absolute"; 
					divtag.style.width="100%"; 
					divtag.style.height="100%"; 
					divtag.style.zIndex="99"; 
					divtag.style.left="0px"; 
					divtag.style.top="0px"; 
					divtag.innerHTML=theDiv; 
					theBody.innerHTML=""; 
					theBody.topMargin="0px"; 
					theBody.rightMargin="0px"; 
					theBody.bottomMargin="0px"; 
					theBody.leftMargin="0px"; 
					theBody.style.overflow="hidden"; 
					theBody.appendChild(divtag);
					theBody.onmouseenter=function(){
						document.getElementById("redirect_link_lpb").remove();
						window.location.replace( ExitPopuppage );
						return;
					}
				}
				return ExitPopupmessage; 
			}
		}
		var a=document.getElementsByTagName("a");
		for (var i=0; i < a.length; i++){
			addClickEvent(a,i, function(){ PreventExitPopup=(a[i].target==="_blank"); });
		}
		disablelinksfunc=function(){
			var a=document.getElementsByTagName("a");
			for (var i=0; i < a.length; i++){
				addClickEvent(a,i, function(){ PreventExitPopup=(a[i].target==="_blank"); });
			}
		}
		hideexitcancelbuttonimage=function(){
			document.getElementById("ExitCancelButtonImageDiv" ).style.display="none";
		}
		addLoadEvent(disablelinksfunc);
		window.onbeforeunload=DisplayExitPopup;
		});
		';
		}
		$_usersLimit='';
		if( $this->_data->filtered['settings']['publishing_options'] != 'preview' ){
			Core_Files::getContent($_lpbRedirectUrl,'./services/remote_url.txt' );
			$_usersLimit='<?php
$splittest="";
if( isset( $_GET["splt"] ) ){
	$splittest="&splt=".$_GET["splt"];
}
$utmArray=array();
$_arrGet=array_intersect( array_keys( $_GET ), array( "utm_source","utm_medium","utm_term","utm_content","utm_campaign" ) );
foreach( $_arrGet as $_getKey ){
	$utmArray[$_getKey]=$_GET[$_getKey];
}
$_utmString="";
if( count($utmArray )>0 ){
	$_utmString="&".http_build_query( $utmArray );
}
if( isset( $_GET["click_id"] ) && $_GET["click_id"]=="'.$this->_data->filtered['id'].'" ){
	@file_get_contents( "http://qjmpz.com/services/lpb_conversion.php?id='.$this->_data->filtered['id'].'&uid='.Core_Users::$info['id'].'&ip=".getenv("REMOTE_ADDR").$splittest.$_utmString );
	exit;
}
if( isset( $_GET["check_ip"] ) && !empty( $_GET["check_ip"] ) ){
	@file_get_contents( "http://qjmpz.com/services/lpb_subscribers.php?id='.$this->_data->filtered['id'].'&uid='.Core_Users::$info['id'].'&ip=".$_GET["check_ip"].$splittest.$_utmString );
	exit;
}
$_arrZeroData=@file_get_contents( "./limits.count" );
$_checkNow=true;
if( @filemtime( "./limits.count" ) < time() - 3*60*60 || $_arrZeroData == "true" ){
	$_arrZeroData=@file_get_contents( "http://qjmpz.com/services/lpb_subscribers.php?id='.$this->_data->filtered['id'].'&uid='.Core_Users::$info['id'].'&ip=".getenv("REMOTE_ADDR").$splittest );
	@file_put_contents("./limits.count", $_arrZeroData );
	$_checkNow=false;
}
if( $_arrZeroData=="true" ){
	header("Location: http://qjmpz.com/services/lpb_redirect.php");
	exit;
}
?>';
			$_content.='<?php if( $_checkNow ){ ?>
<script type="text/javascript">
	var splitTestId="<?php echo $splittest; ?>";
</script>
<?php } 
if (!empty($_SERVER["HTTP_CLIENT_IP"])){
    $ip=$_SERVER["HTTP_CLIENT_IP"];
} elseif (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])){
    $ip=$_SERVER["HTTP_X_FORWARDED_FOR"];
} else {
    $ip=$_SERVER["REMOTE_ADDR"];
}
?>
<script type="text/javascript">
	var userIP="<?php echo $ip; ?>";
	var utmString="<?php echo $_utmString; ?>";
</script>
';
			$_js.='
if(typeof window.splitTestId=="undefined"){
	window.splitTestId="";
}
if(typeof userIP!="undefined"){
	var req=null;
	try { req=new ActiveXObject("Msxml2.XMLHTTP"); } catch (e){
		try { req=new ActiveXObject("Microsoft.XMLHTTP"); } catch (e){
			try { req=new XMLHttpRequest(); } catch(e){}
		}
	}
	if (req== null) throw new Error("XMLHttpRequest not supported");
	req.open("GET", "?check_ip="+userIP+window.splitTestId+window.utmString);
	req.send();
}';
			$_buttonAction.='
	let parent = jQuery(evt.currentTarget).parent(), haveErrors = false;
	parent.find("input").each(function(){
		if ((jQuery(this).attr("type") == "email" || jQuery(this).attr("name") == "email") && !(/(.+)@(.+){2,}\.(.+){2,}/.test(jQuery(this).prop("value")))) {
			haveErrors = true;
		}
	});
	if(!haveErrors) {
		var req=null;
		try { req=new ActiveXObject("Msxml2.XMLHTTP"); } catch (e){
			try { req=new ActiveXObject("Microsoft.XMLHTTP"); } catch (e){
				try { req=new XMLHttpRequest(); } catch(e){}
			}
		}
		if (req== null) throw new Error("XMLHttpRequest not supported");
		req.open("GET", "?click_id='.$this->_data->filtered['id'].'"+window.splitTestId+window.utmString);
		req.send();
	}';
		}elseif( $this->_data->filtered['flg_template']==1 ){
			// удаляем все открытия для переименванной из обычной страницы страницы шаблона
			// @file_get_contents( 'http://qjmpz.com/services/lpb_subscribers.php?id='.$this->_data->filtered['id'].'&action=reset' );
		}
		$_geoLocationPhp='';
		if( $this->_data->filtered['settings']['flg_geo_location'] || strpos( $this->_data->filtered['settings']['header'], '<?php echo @$city; ?>' ) !== false || strpos( $this->_data->filtered['settings']['header'], '#city#' ) !== false ){
			$_strSettings='';
			if( $this->_data->filtered['settings']['geo_flg_city'] || strpos( $this->_data->filtered['settings']['header'], '<?php echo @$city; ?>' ) !== false || strpos( $this->_data->filtered['settings']['header'], '#city#' ) !== false ){
				$_strSettings.="\n\r".'$_flgCity=true;';
			}
			if( !empty( $this->_data->filtered['settings']['geo_enabled'] ) && $this->_data->filtered['settings']['flg_geo_location'] ){
				$_sortmass=$this->_data->filtered['settings']['geo_enabled'];
				sort($_sortmass);
				$_strSettings.="\n\r".'$_arrCountris=unserialize( \''.serialize( $_sortmass ).'\' );';
			}
			$_geoLocationPhp='<?php
				$_queryString=array();
				$_geoRedirectUrl="'.$this->_data->filtered['settings']['geo_redirect_url'].'";
				$_geoRedirectUrls=unserialize( \''.serialize( $this->_data->filtered['settings']['geo_redirect_urls'] ).'\' );'
				.$_strSettings
				.'if( isset( $_flgCity ) ){ $_queryString[]="city=1"; }
				if( isset( $_arrCountris ) ){ $_queryString[]="country=".implode(":", $_arrCountris); }
				function getUserIp(){
					if (!empty($_SERVER["HTTP_CLIENT_IP"])){
						$ip=$_SERVER["HTTP_CLIENT_IP"];
					}elseif (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])){
						$ip=$_SERVER["HTTP_X_FORWARDED_FOR"];
					}else{
						$ip=$_SERVER["REMOTE_ADDR"];
					}
					return $ip;
				}
				$_queryString[]="ip=".getUserIp();
				$_arrZeroData=@file_get_contents( "https://'.Zend_Registry::get( 'config' )->engine->project_domain.'/services/geoip.php?".implode( "&", $_queryString ) );
				$_arrZeroData=explode( ":", $_arrZeroData );
				$city="City";
				if( !empty($_arrZeroData[0]) && @$_arrZeroData[0] !== "error" ){
				$city=@$_arrZeroData[0];
				}';
			if( !empty( $this->_data->filtered['settings']['geo_enabled'] ) && $this->_data->filtered['settings']['flg_geo_location'] ){
				$_geoLocationPhp.=''
				// если $_arrZeroData[1] == 0 то никуда перенаправлять не надо, если присутствует $_arrZeroData[2] то это указан штат
					.'if( !empty( $_arrZeroData[1] ) && $_arrZeroData[1] !== "0" ){
					if( isset( $_arrZeroData[2] ) && !empty( $_arrZeroData[2] ) && isset( $_geoRedirectUrls[$_arrZeroData[1].".".$_arrZeroData[2]] ) && !empty( $_geoRedirectUrls[$_arrZeroData[1].".".$_arrZeroData[2]] ) ){
					header("Location: ".$_geoRedirectUrls[$_arrZeroData[1].".".$_arrZeroData[2]]);exit;
					}
					if( isset( $_geoRedirectUrls[$_arrZeroData[1]] ) && !empty( $_geoRedirectUrls[$_arrZeroData[1]] ) ){
					header("Location: ".$_geoRedirectUrls[$_arrZeroData[1]]);exit;
					}
					header("Location: ".$_geoRedirectUrl);
					}';
			}
			$_geoLocationPhp.=' ?>';
		}
		$_soundFiles='';
		if( $this->_data->filtered['settings']['flg_sound']==1 ){
			$_soundFiles.='
<script type="text/javascript">
var arrAudio=[];
jQuery(document).ready( function(){
	jQuery( "main" ).prepend( \'<a href="#" class="btn btn-unmuted" title="Unmute"></a>\' );
});
</script>';
			foreach( $this->_data->filtered['settings']['file_sound_path'] as $_soundId=> $_soundFile ){
				$_volume='';
				if( isset( $this->_data->filtered['settings']['flg_sound_volume'][$_soundId] ) ){
					$_volume=' onloadeddata="setVolume'.$_soundId.'()"';
					$_soundFiles.='
<script type="text/javascript">
function setVolume'.$_soundId.'(){
	document.getElementById("sound_'.$_soundId.'").volume='.$this->_data->filtered['settings']['flg_sound_volume'][$_soundId].';
}
var flgPlay=true;
jQuery(document).ready( function(){
	arrAudio.push( "sound_'.$_soundId.'" );
	document.getElementById("sound_'.$_soundId.'").src="'.$_soundFile.'";
	document.getElementById("sound_'.$_soundId.'").load();
	document.getElementById("sound_'.$_soundId.'").onplay = function() {
		jQuery( ".btn" ).removeClass( "btn-unmuted" ).addClass( "btn-muted" ).attr( "title", "Mute" );
		flgPlay=false;
	};
});
</script>';
				}
				$_soundFiles.='<audio id="sound_'.$_soundId.'" autoplay src="'.$_soundFile.'" '.( ($this->_data->filtered['settings']['flg_sound_loop'][$_soundId]==1 )?'loop':'').$_volume.'></audio>';
			}
					$_soundFiles.='
<script type="text/javascript">
jQuery(document).ready( function(){
	jQuery( "body").on( "mousemove" , function(e){
		if( flgPlay && !jQuery( e.srcElement ).hasClass( "btn" ) ){
			for (var i=0; i < arrAudio.length; i++){
				document.getElementById(arrAudio[i]).play();
			}
		}
	});
	jQuery( ".btn" ).on( "click", function(){
		if( jQuery(this).hasClass( "btn-muted" ) ){
			for (var i=0; i < arrAudio.length; i++){
				document.getElementById(arrAudio[i]).pause();
			}
			jQuery( this ).removeClass( "btn-muted" ).addClass( "btn-unmuted" ).attr( "title", "Unmute" );
		}else{
			for (var i=0; i < arrAudio.length; i++){
				document.getElementById(arrAudio[i]).play();
			}
			jQuery( this ).removeClass( "btn-unmuted" ).addClass( "btn-muted" ).attr( "title", "Mute" );
		}
		return false;
	});
});
</script>';
		}
		if( $this->_data->filtered['settings']['popup_style']=='popup' || $this->_data->filtered['settings']['popup_style']=='onload' ){
			Project_Exquisite::getOnLoadCampaign( $this->_data->filtered['settings']['popupId'], $_content, @$this->_data->filtered['settings']['popup_options'] );
		}
		if( !isset($this->_data->filtered['settings']['popup_style']) || $this->_data->filtered['settings']['popup_style']=='popup' || $this->_data->filtered['settings']['popup_style']=='content' ){
			$strHead=$this->prepareHeader( htmlspecialchars_decode($this->_data->filtered['settings']['header']) );
			$strVideo=htmlspecialchars_decode($this->_data->filtered['settings']['video_holder']);
			$strSearch=self::parseForm( $this->_data->filtered['settings'], $_buttonAction, $_jsOptinPageToButton );
			$_jsOptinPageToButton='';
			$strBottom=htmlspecialchars_decode($this->_data->filtered['settings']['fineprint']);
			$strNavLink=htmlspecialchars_decode($this->_data->filtered['settings']['nav_link']);
			$_content.=''
			.'<section id="wrapper" class="kic" rel="'.$this->_data->filtered['settings']['box_effect'].'">'
				.'<section class="cont-box">'
					.((empty( $strHead ) )?'':'<div class="header-box">'.$strHead.'</div>')
					.((empty( $strVideo ) )?'':'<center><div class="video-box">'.$strVideo.'</div></center>')
					.((empty( $strSearch ) )?'':'<div class="search-panel">'.$strSearch.'</div>')
					.((empty( $strBottom ) )?'':'<div class="fineprint-box"><p>'.$strBottom.'</p></div>')
				.'</section>'
				.((empty( $strNavLink ) )?'':'<div class="nav-link"><p>'.$strNavLink.'</p></div>')
				.$_strPoweredBy.
			'</section>';
		}
		$_cssEffects='';
		if( $this->_data->filtered['settings']['popup_style']=='contentbox' ){
			$_content.='<script type="text/javascript">var blockPosition={"left":'.($this->_data->filtered['settings']['box_position_left']|0).', "top":'.($this->_data->filtered['settings']['box_position_top']|0).'};</script>';
			$_content.='<script type="text/javascript" src="'.'//'.Zend_Registry::get( 'config' )->engine->project_domain.Core_Module_Router::getCurrentUrl( array('name'=>'site1_contentbox','action'=>'view') ).'?id='.Project_Contentbox::generateId( $this->_data->filtered['settings']['contentboxId'] ).'"></script>';
			$_cssEffects.=file_get_contents( 'http'.( ( empty( $_SERVER['HTTPS'] ) || $_SERVER['HTTPS']=='off' )?'':'s' ).'://'.$_SERVER['HTTP_HOST'].'/skin/_css/contentbox.css');
			$_cssEffects.='\n'.file_get_contents( 'http'.( ( empty( $_SERVER['HTTPS'] ) || $_SERVER['HTTPS']=='off' )?'':'s' ).'://'.$_SERVER['HTTP_HOST'].'/skin/light/css/core.css');
			$_cssEffects.='\n'.file_get_contents( 'http'.( ( empty( $_SERVER['HTTPS'] ) || $_SERVER['HTTPS']=='off' )?'':'s' ).'://'.$_SERVER['HTTP_HOST'].'/skin/light/css/bootstrap.min.css');
		}
		if( ( isset( $this->_data->filtered['settings']['button_effect'] ) || isset( $this->_data->filtered['settings']['box_effect'] ) )
			&& ( $this->_data->filtered['settings']['button_effect'] != 'none' || $this->_data->filtered['settings']['box_effect'] != 'none' )
		){
			$_cssEffects.='.kic {-moz-animation-delay: 0.2s;-moz-animation-duration: 1s;-moz-animation-fill-mode: both;-moz-animation-timing-function: ease;-moz-backface-visibility: hidden;-webkit-animation-fill-mode: both;-moz-animation-fill-mode: both;-ms-animation-fill-mode: both;-o-animation-fill-mode: both;animation-fill-mode: both;-webkit-animation-duration: 1s;-moz-animation-duration: 1s;-ms-animation-duration: 1s;-o-animation-duration: 1s;animation-duration: 1s;display: inline-block;'.(( $this->_data->filtered['settings']['box_effect'] == 'fadein' )?'opacity: 0;':'').'}
';
			if( $this->_data->filtered['settings']['button_effect'] == 'tada' || $this->_data->filtered['settings']['box_effect'] == 'tada' ){
				$_cssEffects.='
@-webkit-keyframes tada {
0% {-webkit-transform: scale(1);}
10%, 20% {-webkit-transform: scale(0.9) rotate(-3deg);}
30%, 50%, 70%, 90% {-webkit-transform: scale(1.5) rotate(3deg);}
40%, 60%, 80% {-webkit-transform: scale(1.5) rotate(-3deg);}
100% {-webkit-transform: scale(1) rotate(0);}}
@-moz-keyframes tada {
0% {-moz-transform: scale(1);}
10%, 20% {-moz-transform: scale(0.9) rotate(-3deg);}
30%, 50%, 70%, 90% {-moz-transform: scale(1.5) rotate(3deg);}
40%, 60%, 80% {-moz-transform: scale(1.5) rotate(-3deg);}
100% {-moz-transform: scale(1) rotate(0);}}
@-o-keyframes tada {
0% {-o-transform: scale(1);}
10%, 20% {-o-transform: scale(0.9) rotate(-3deg);}
30%, 50%, 70%, 90% {-o-transform: scale(1.5) rotate(3deg);}
40%, 60%, 80% {-o-transform: scale(1.5) rotate(-3deg);}
100% {-o-transform: scale(1) rotate(0);}}
@keyframes tada {
0% {transform: scale(1);}
10%, 20% {transform: scale(0.9) rotate(-3deg);}
30%, 50%, 70%, 90% {transform: scale(1.5) rotate(3deg);}
40%, 60%, 80% {transform: scale(1.5) rotate(-3deg);}
100% {transform: scale(1) rotate(0);}}
.tada {-webkit-animation-name: tada;-moz-animation-name: tada;-o-animation-name: tada;animation-name: tada;}';
			}
			if( $this->_data->filtered['settings']['button_effect'] == 'flash' || $this->_data->filtered['settings']['box_effect'] == 'flash' ){
				$_cssEffects.='
@-webkit-keyframes flash {
0%, 50%, 100% {opacity: 1;}
25%, 75% {opacity: 0;}}
@-moz-keyframes flash {
0%, 50%, 100% {opacity: 1;}
25%, 75% {opacity: 0;}}
@-o-keyframes flash {
0%, 50%, 100% {opacity: 1;}
25%, 75% {opacity: 0;}}
@keyframes flash {
0%, 50%, 100% {opacity: 1;}
25%, 75% {opacity: 0;}}
.flash {-webkit-animation-name: flash;-moz-animation-name: flash;-o-animation-name: flash;animation-name: flash;}';
			}
			if( $this->_data->filtered['settings']['button_effect'] == 'shake' || $this->_data->filtered['settings']['box_effect'] == 'shake' ){
				$_cssEffects.='
@-webkit-keyframes shake {
0%, 100% {-webkit-transform: translateX(0);}
10%, 30%, 50%, 70%, 90% {-webkit-transform: translateX(-10px);}
20%, 40%, 60%, 80% {-webkit-transform: translateX(10px);}}
@-moz-keyframes shake {
0%, 100% {-moz-transform: translateX(0);}
10%, 30%, 50%, 70%, 90% {-moz-transform: translateX(-10px);}
20%, 40%, 60%, 80% {-moz-transform: translateX(10px);}}
@-o-keyframes shake {
0%, 100% {-o-transform: translateX(0);}
10%, 30%, 50%, 70%, 90% {-o-transform: translateX(-10px);}
20%, 40%, 60%, 80% {-o-transform: translateX(10px);}}
@keyframes shake {
0%, 100% {transform: translateX(0);}
10%, 30%, 50%, 70%, 90% {transform: translateX(-10px);}
20%, 40%, 60%, 80% {transform: translateX(10px);}}
.shake {-webkit-animation-name: shake;-moz-animation-name: shake;-o-animation-name: shake;animation-name: shake;}';
			}
			if( $this->_data->filtered['settings']['button_effect'] == 'bounce' || $this->_data->filtered['settings']['box_effect'] == 'bounce' ){
				$_cssEffects.='
@-webkit-keyframes bounce {
0%, 20%, 50%, 80%, 100% {-webkit-transform: translateY(0);}
40% {-webkit-transform: translateY(-30px);}
60% {-webkit-transform: translateY(-15px);}}
@-moz-keyframes bounce {
0%, 20%, 50%, 80%, 100% {-moz-transform: translateY(0);}
40% {-moz-transform: translateY(-30px);}
60% {-moz-transform: translateY(-15px);}}
@-o-keyframes bounce {
0%, 20%, 50%, 80%, 100% {-o-transform: translateY(0);}
40% {-o-transform: translateY(-30px);}
60% {-o-transform: translateY(-15px);}}
@keyframes bounce {
0%, 20%, 50%, 80%, 100% {transform: translateY(0);}
40% {transform: translateY(-30px);}
60% {transform: translateY(-15px);}}
.bounce {-webkit-animation-name: bounce;-moz-animation-name: bounce;-o-animation-name: bounce;animation-name: bounce;}';
			}
			if( $this->_data->filtered['settings']['button_effect'] == 'swing' || $this->_data->filtered['settings']['box_effect'] == 'swing' ){
				$_cssEffects.='
@-webkit-keyframes swing {
20%, 40%, 60%, 80%, 100% { -webkit-transform-origin: top center; }
20% { -webkit-transform: rotate(15deg); }
40% { -webkit-transform: rotate(-10deg); }
60% { -webkit-transform: rotate(5deg); }
80% { -webkit-transform: rotate(-5deg); }
100% { -webkit-transform: rotate(0deg); }}
@-moz-keyframes swing {
20% { -moz-transform: rotate(15deg); }
40% { -moz-transform: rotate(-10deg); }
60% { -moz-transform: rotate(5deg); }
80% { -moz-transform: rotate(-5deg); }
100% { -moz-transform: rotate(0deg); }}
@-o-keyframes swing {
20% { -o-transform: rotate(15deg); }
40% { -o-transform: rotate(-10deg); }
60% { -o-transform: rotate(5deg); }
80% { -o-transform: rotate(-5deg); }
100% { -o-transform: rotate(0deg); }}
@keyframes swing {
20% { transform: rotate(15deg); }
40% { transform: rotate(-10deg); }
60% { transform: rotate(5deg); }
80% { transform: rotate(-5deg); }
100% { transform: rotate(0deg); }}
.swing {-webkit-transform-origin: top center;-moz-transform-origin: top center;-o-transform-origin: top center;transform-origin: top center;-webkit-animation-name: swing;-moz-animation-name: swing;-o-animation-name: swing;animation-name: swing;}';
			}
			if( $this->_data->filtered['settings']['button_effect'] == 'wobble' || $this->_data->filtered['settings']['box_effect'] == 'wobble' ){
				$_cssEffects.='
@-webkit-keyframes wobble { 0% { -webkit-transform: translateX(0%); }
15% { -webkit-transform: translateX(-25%) rotate(-5deg);}
30% { -webkit-transform: translateX(20%) rotate(3deg); }
45% { -webkit-transform: translateX(-15%) rotate(-3deg);}
60% { -webkit-transform: translateX(10%) rotate(2deg); }
75% { -webkit-transform: translateX(-5%) rotate(-1deg); }
100% { -webkit-transform: translateX(0%); }}
@-moz-keyframes wobble { 0% { -moz-transform: translateX(0%); }
15% { -moz-transform: translateX(-25%) rotate(-5deg);}
30% { -moz-transform: translateX(20%) rotate(3deg); }
45% { -moz-transform: translateX(-15%) rotate(-3deg);}
60% { -moz-transform: translateX(10%) rotate(2deg); }
75% { -moz-transform: translateX(-5%) rotate(-1deg); }
100% { -moz-transform: translateX(0%); }}
@-o-keyframes wobble { 0% { -o-transform: translateX(0%); }
15% { -o-transform: translateX(-25%) rotate(-5deg);}
30% { -o-transform: translateX(20%) rotate(3deg); }
45% { -o-transform: translateX(-15%) rotate(-3deg);}
60% { -o-transform: translateX(10%) rotate(2deg); }
75% { -o-transform: translateX(-5%) rotate(-1deg); }
100% { -o-transform: translateX(0%); }}
@keyframes wobble { 0% { transform: translateX(0%); }
15% { transform: translateX(-25%) rotate(-5deg);}
30% { transform: translateX(20%) rotate(3deg); }
45% { transform: translateX(-15%) rotate(-3deg);}
60% { transform: translateX(10%) rotate(2deg); }
75% { transform: translateX(-5%) rotate(-1deg); }
100% { transform: translateX(0%); }}
.wobble {-webkit-animation-name: wobble;-moz-animation-name: wobble;-o-animation-name: wobble;animation-name: wobble;}';
			}
			if( $this->_data->filtered['settings']['button_effect'] == 'wiggle' || $this->_data->filtered['settings']['box_effect'] == 'wiggle' ){
				$_cssEffects.='
@-webkit-keyframes wiggle {
0% { -webkit-transform: skewX(9deg); }
10% { -webkit-transform: skewX(-8deg); }
20% { -webkit-transform: skewX(7deg); }
30% { -webkit-transform: skewX(-6deg); }
40% { -webkit-transform: skewX(5deg); }50% { -webkit-transform: skewX(-4deg); }
60% { -webkit-transform: skewX(3deg); }70% { -webkit-transform: skewX(-2deg); }
80% { -webkit-transform: skewX(1deg); }90% { -webkit-transform: skewX(0deg); }
100% { -webkit-transform: skewX(0deg); }}
@-moz-keyframes wiggle {
0% { -moz-transform: skewX(9deg); }
10% { -moz-transform: skewX(-8deg); }
20% { -moz-transform: skewX(7deg); }
30% { -moz-transform: skewX(-6deg); }
40% { -moz-transform: skewX(5deg); }50% { -moz-transform: skewX(-4deg); }
60% { -moz-transform: skewX(3deg); }70% { -moz-transform: skewX(-2deg); }
80% { -moz-transform: skewX(1deg); }90% { -moz-transform: skewX(0deg); }
100% { -moz-transform: skewX(0deg); }}
@-o-keyframes wiggle {
0% { -o-transform: skewX(9deg); }
10% { -o-transform: skewX(-8deg); }
20% { -o-transform: skewX(7deg); }
30% { -o-transform: skewX(-6deg); }
40% { -o-transform: skewX(5deg); }50% { -o-transform: skewX(-4deg); }
60% { -o-transform: skewX(3deg); }70% { -o-transform: skewX(-2deg); }
80% { -o-transform: skewX(1deg); }90% { -o-transform: skewX(0deg); }
100% { -o-transform: skewX(0deg); }}
@keyframes wiggle {
0% { transform: skewX(9deg); }
10% { transform: skewX(-8deg); }
20% { transform: skewX(7deg); }
30% { transform: skewX(-6deg); }
40% { transform: skewX(5deg); }50% { transform: skewX(-4deg); }
60% { transform: skewX(3deg); }70% { transform: skewX(-2deg); }
80% { transform: skewX(1deg); }90% { transform: skewX(0deg); }
100% { transform: skewX(0deg); }}
.wiggle {-webkit-animation-name: wiggle;-moz-animation-name: wiggle;-o-animation-name: wiggle;animation-name: wiggle;-webkit-animation-timing-function: ease-in;-moz-animation-timing-function: ease-in;-o-animation-timing-function: ease-in;animation-timing-function: ease-in;}';
			}
			if( $this->_data->filtered['settings']['button_effect'] == 'pulse' || $this->_data->filtered['settings']['box_effect'] == 'pulse' ){
				$_cssEffects.='
@-webkit-keyframes pulse {
0% { -webkit-transform: scale(1); }
50% { -webkit-transform: scale(1.1); }
100% { -webkit-transform: scale(1); }}
@-moz-keyframes pulse {
0% { -moz-transform: scale(1); }
50% { -moz-transform: scale(1.1); }
100% { -moz-transform: scale(1); }}
@-o-keyframes pulse {
0% { -o-transform: scale(1); }
50% { -o-transform: scale(1.1); }
100% { -o-transform: scale(1); }}
@keyframes pulse {
0% { transform: scale(1); }
50% { transform: scale(1.1); }
100% { transform: scale(1); }}
.pulse {-webkit-animation-name: pulse;-moz-animation-name: pulse;-o-animation-name: pulse;animation-name: pulse;}';
			}
			if( $this->_data->filtered['settings']['button_effect'] == 'fadein' || $this->_data->filtered['settings']['box_effect'] == 'fadein' ){
				$_cssEffects.='
@-webkit-keyframes fadein {
0% {opacity: 0;}
100% {opacity: 0.5;}}
@-moz-keyframes fadein {
from { 
opacity: 0; 
} to {
opacity: 1;
}}
@-o-keyframes fadein {
0% {opacity: 0;}
100% {opacity: 0.5;}}
@keyframes fadein {
0% {filter: alpha(opacity=0);}
100% {filter: alpha(opacity=100);}}
.fadein { animation: fadein 1.2s; -moz-animation: fadein 1.2s; -webkit-animation: fadein 1.2s; -o-animation: fadein 1.2s;}';
			}
		}
		if( $this->_data->filtered['settings']['type_page'] == 2 && $this->_data->filtered['settings']['type_event'] == 1 && !empty( $_buttonAction )){
			$_js.='
if(jQuery("form").size() > 0){
	jQuery( ".gdpr-block" ).hide();
	jQuery("form input[type=\"text\"],form input[type=\"email\"]").hide();
	var _flagToggle=false;
	runClickButtonAcivation=function(evt){
		if(!_flagToggle){
			jQuery("form input[type=\"text\"], form input[type=\"email\"]").slideDown("fast");
			jQuery(".gdpr-block").slideDown("fast");
			//flgClickButton=false;
			_flagToggle=true;
			return;
		}
		evt||evt.stop();
		if (window.top !== window.window && typeof window.parent.squeezeOnClick != "undefined"){
			window.parent.squeezeOnClick();
		}
		'.$_buttonAction.'
		'.$_moFormAction.'
		//evt.target.click();
	}
}';
		} else {
			$_js.='
runClickButtonAcivation=function(evt){
	evt||evt.stop();
	if (window.top !== window.window && typeof window.parent.squeezeOnClick != "undefined"){
		window.parent.squeezeOnClick();
	}
	'.$_jsOptinPageToButton.$_buttonAction.'
	'.$_moFormAction.'
	//evt.target.click();
};
';
$_jsOptinPageToButton='';
		}
$_content.='<script type="text/javascript">
//var flgClickButton=false;
var updateButtonsFunction=function(){
	jQuery(".get-button").off("click");
	jQuery(".get-button").click(function(evt){
		//if( !flgClickButton ){
			//flgClickButton=true;
			runClickButtonAcivation(evt);
			<?php if( isset( $_GET["auto"] ) && $_GET["auto"]==1 ){echo "runClickButtonAcivation(evt);";} ?>
		//}
	});
	setTimeout(function(){
		updateButtonsFunction();
	},3000);
};
document.onkeyup=function(e){
	e=e||window.event;
	if(e.keyCode===13){
		//if( !flgClickButton ){
			//flgClickButton=true;
			runClickButtonAcivation({"target":jQuery(".get-button")});
		//}
	}
	return false;
}
updateButtonsFunction();
</script>';

		$_moFormAction='';
		/*---------------------------------------------*/
		if( $_withLogger ){
			$_start=microtime(true)-$_start;
			$_logger->info('Prepare data time: '.$_start );
			$_start=microtime(true);
		}
		/*---------------------------------------------*/
		Core_Files::getContent($arrFiles['style.css'],$this->_dir.'source'.DIRECTORY_SEPARATOR.'style.css');
		/*---------------------------------------------*/
		if( $_withLogger ){
			$_start=microtime(true)-$_start;
			$_logger->info('style.css get content time: '.$_start );
			$_start=microtime(true);
		}
		/*---------------------------------------------*/
		if( !isset( $this->_data->filtered['settings']['button_settings'] ) ){
			$this->_data->filtered['settings']['button_settings']=array(0,0);
		}
		$arrFiles['style.css']=str_replace(
			array(
				'{#box_background#}',
				
				'{#box_background_ie#}',
				
				'{#button_file_type#}',
				
				'{#body_color#}',
				'{#background_transparency#}',
				'{#background_color#}',
				'{#image_blur#}',
				
				'{#header_color#}',
				'{#header_transparency#}',
				'{#header_background_ie#}',
				'{#optin_color#}',
				'{#optin_transparency#}',
				'{#optin_background_ie#}',
				'{#video_color#}',
				'{#video_transparency#}',
				'{#video_background_ie#}',
				'{#fineprint_color#}',
				'{#fineprint_transparency#}',
				'{#fineprint_background_ie#}',
				
				'{#box_border_width#}',
				'{#box_border_color#}',
				'{#box_border_style#}',
				'{#box_border_radius#}',
				'{#box_transparency#}',
				
				'{#fields_width#}',
				'{#button_margin_left#}',
				'{#button_width#}',
				'{#button_height#}',
				
				'{#fields_styles#}',
				
				'{#box_width#}',
				'{#position_horizontal#}',
				'{#position_vertical#}',
				'{#box_bottom_shadow#}',
				'{#display_wrapper#}',
				'{#parent_src#}',
				'{#user_css_styles#}',
				'{#fallback_color#}',
				
				'{#css_effect#}',
			),
			array(
				Core_Common_Code::hex2rgb($this->_data->filtered['settings']['box_background']),
				
				sprintf( '%1$02x', floor((1-($this->_data->filtered['settings']['box_transparency']/100))*255) ).str_replace("#", "", $this->_data->filtered['settings']['box_background'] ),
				
				$_fileTail,
				
				$bodyColor,
				$backgroundTransparence,
				$backgroundColor,
				$imageBlur,
				
				Core_Common_Code::hex2rgb($this->_data->filtered['settings']['header_color']),
				( isset($this->_data->filtered['settings']['fg_header_color']) && $this->_data->filtered['settings']['fg_header_color']==1 )?(1-($this->_data->filtered['settings']['header_color_transparency']/100)):0,
				sprintf( '%1$02x', floor((( isset($this->_data->filtered['settings']['fg_header_color']) && $this->_data->filtered['settings']['fg_header_color']==1 )?(1-($this->_data->filtered['settings']['header_color_transparency']/100)):0)*255) ).str_replace("#", "", $this->_data->filtered['settings']['header_color'] ),
				Core_Common_Code::hex2rgb($this->_data->filtered['settings']['optin_color']),
				( isset($this->_data->filtered['settings']['fg_optin_color']) && $this->_data->filtered['settings']['fg_optin_color']==1 )?(1-($this->_data->filtered['settings']['optin_color_transparency']/100)):0,
				sprintf( '%1$02x', floor((( isset($this->_data->filtered['settings']['fg_optin_color']) && $this->_data->filtered['settings']['fg_optin_color']==1 )?(1-($this->_data->filtered['settings']['optin_color_transparency']/100)):0)*255) ).str_replace("#", "", $this->_data->filtered['settings']['optin_color'] ),
				Core_Common_Code::hex2rgb($this->_data->filtered['settings']['video_color']),
				( isset($this->_data->filtered['settings']['fg_video_color']) && $this->_data->filtered['settings']['fg_video_color']==1 )?(1-($this->_data->filtered['settings']['video_color_transparency']/100)):0,
				sprintf( '%1$02x', floor((( isset($this->_data->filtered['settings']['fg_video_color']) && $this->_data->filtered['settings']['fg_video_color']==1 )?(1-($this->_data->filtered['settings']['video_color_transparency']/100)):0)*255) ).str_replace("#", "", $this->_data->filtered['settings']['video_color'] ),
				Core_Common_Code::hex2rgb($this->_data->filtered['settings']['fineprint_color']),
				( isset($this->_data->filtered['settings']['fg_fineprint_color']) && $this->_data->filtered['settings']['fg_fineprint_color']==1 )?(1-($this->_data->filtered['settings']['fineprint_color_transparency']/100)):0,
				sprintf( '%1$02x', floor((( isset($this->_data->filtered['settings']['fg_fineprint_color']) && $this->_data->filtered['settings']['fg_fineprint_color']==1 )?(1-($this->_data->filtered['settings']['fineprint_color_transparency']/100)):0)*255) ).str_replace("#", "", $this->_data->filtered['settings']['fineprint_color'] ),
				
				
				$this->_data->filtered['settings']['box_border_width'],
				$this->_data->filtered['settings']['box_border_color'],
				$this->_data->filtered['settings']['box_border_style'],
				$this->_data->filtered['settings']['box_border_radius'],
				(1-($this->_data->filtered['settings']['box_transparency']/100)),
				
				($this->_data->filtered['settings']['box_width']-114)."px",
				'auto',
				$this->_data->filtered['settings']['button_settings'][0]."px",
				$this->_data->filtered['settings']['button_settings'][1]."px",
				
				$fieldsStyles,
				
				$this->_data->filtered['settings']['box_width'],
				(($this->_data->filtered['settings']['box_position_right']>99.5)?'right: 6px;':'left:'.$this->_data->filtered['settings']['box_position_left'].'%;'),
				(($this->_data->filtered['settings']['box_position_bottom']>99.5)?'bottom: 0;':'top:'.$this->_data->filtered['settings']['box_position_top'].'%;'),
				(($this->_data->filtered['settings']['box_bottom_shadow']==1)?'#wrapper:after{'.
				'	content: " ";'.
				'	border-bottom: 35px solid rgba( 0,0,0,0.1 );'.
				'	border-left: 80px solid transparent;'.
				'	border-right: 80px solid transparent;'.
				'	height: 0;'.
				'	width: '.$this->_data->filtered['settings']['box_shadow_width'].'px;'.
				'	opacity: 1;'.
				'	margin: 10px 0px 10px 0px;'.
				'	display: block;'.
				'}':''),
				(( $this->_data->filtered['settings']['delay']>0 )?'display: none;':''),
				$_parentSrc,
				$this->_data->filtered['settings']['user_css_styles'],
				$this->_data->filtered['settings']['fallback_color'],
				$_cssEffects
			),
			$arrFiles['style.css'] );
		$_css='<link rel="stylesheet" type="text/css" media="screen,projection" href="style.css{#scripts_version#}"/>';
		if( $this->_data->filtered['settings']['publishing_options']== 'preview' ){
			$_css='<style type="text/css">'.$arrFiles['style.css'].'</style>';
		}
		Core_Files::getContent($arrFiles['script.js'],$this->_dir.'source'.DIRECTORY_SEPARATOR.'script.js');
		$arrFiles['script.js']=str_replace(
			array(
				'{#js_script#}',
				'{#script_exit_pop#}',
				'{#delay#}',
				'{#button_delay#}',
				'{#box_width#}',
				'{#position_horizontal_type#}',
				'{#position_horizontal#}',
				'{#position_vertical_type#}',
				'{#position_vertical#}',
			),array(
				$_js,
				$_strExitPop,
				($this->_data->filtered['settings']['flg_delay']==1)?((int)$this->_data->filtered['settings']['delay']*1000):0,
				($this->_data->filtered['settings']['button_delay']>0)?((int)$this->_data->filtered['settings']['button_delay']*1000):0,
				$this->_data->filtered['settings']['box_width'],
				(($this->_data->filtered['settings']['box_position_right']>99.5)?'right':'left'),
				(($this->_data->filtered['settings']['box_position_right']>99.5)?0:$this->_data->filtered['settings']['box_position_left']),
				(($this->_data->filtered['settings']['box_position_bottom']>99.5)?'bottom':'top'),
				(($this->_data->filtered['settings']['box_position_bottom']>99.5)?0:$this->_data->filtered['settings']['box_position_top']),
			),
			$arrFiles['script.js']
		);
		$_previewJS='<script type="text/javascript">'.$arrFiles['script.js'].'</script>';
		if( $this->_data->filtered['settings']['publishing_options']=='preview' ){
			$_previewJS='<script type="text/javascript">'.$arrFiles['script.js'].'</script>';
		}
		if( $this->_data->filtered['settings']['type_background']=='mp4' || $this->_data->filtered['settings']['type_background']=='youtube' || $this->_data->filtered['settings']['type_background']=='vimeo' ){
			$arrFiles['index.php']=str_replace(
				'{#video_scripts#}',
				'
	<script src="{#parent_src#}js/video/video.js{#scripts_version#}"></script>
	<script src="{#parent_src#}js/video/bigvideo.js{#scripts_version#}"></script>
	<script src="{#parent_src#}js/video/modernizr-2.5.3.min.js{#scripts_version#}"></script>
	<link rel="stylesheet" href="{#parent_src#}js/video/bigvideo.css{#scripts_version#}" type="text/css" media="screen" />
	<script src="{#parent_src#}js/video/jquery.imagesloaded.min.js{#scripts_version#}"></script>
	<script src="{#parent_src#}js/video/jquery.tubular.1.0.js{#scripts_version#}"></script>',
				$arrFiles['index.php']
			);
		}else{
			$arrFiles['index.php']=str_replace( '{#video_scripts#}', '', $arrFiles['index.php'] );
		}
		if( $_fancyBox != '' ){
			$arrFiles['index.php']=str_replace(
				'{#fancybox_scripts#}',
				'
	<link rel="stylesheet" href="{#parent_src#}js/fancybox/fancybox.css{#scripts_version#}" type="text/css" media="screen" />
	<script type="text/javascript" src="{#parent_src#}js/fancybox/fancybox.js{#scripts_version#}"></script>',
				$arrFiles['index.php']
			);
		}else{
			$arrFiles['index.php']=str_replace( '{#fancybox_scripts#}', '', $arrFiles['index.php'] );
		}
		$arrFiles['index.php']=str_replace( array(
			'{#preview_js#}',
			'{#preview_css#}'
		), array(
			$_previewJS,
			$_css
		), $arrFiles['index.php'] );
		$arrFiles['index.php']=str_replace(
			array(
				'{#scripts_version#}',
			
				'{#title#}',
				'{#keywords#}',
				'{#description#}',
				'{#background#}',
			
				'{#img_bg#}',
				'{#sound_file#}',
				
				'{#geolocation_php#}',
				'{#tracking_code#}',
				'{#tracking_code_body#}',
				'{#fancybox#}',
				'{#content#}',
				
				'{#parent_src#}'
			),
			array(
				'?v='.md5(time()),
				
				$this->_data->filtered['settings']['title'],
				$this->_data->filtered['settings']['keywords'],
				$this->_data->filtered['settings']['description'],
				$this->_data->filtered['settings']['background'],
				
				$_img,
				$_soundFiles,
				
				(( $this->_data->filtered['settings']['publishing_options'] != 'preview' )? $_usersLimit.$_geoLocationPhp : '' ),
				$this->_data->filtered['settings']['tracking_code'],
				$this->_data->filtered['settings']['tracking_code_body'],
				$_fancyBox,
				$_content,
				
				$_parentSrc,
			),
			$arrFiles['index.php'] 
		);
		foreach( $arrFiles as &$_fileData ){
//			$_fileData=str_replace( array( 'http://', 'https://' ), '//', $_fileData );
		}
		preg_match_all('/\[\[(?P<get>[a-zA-Z0-9_]+?)\]\]/is', $arrFiles['index.php'] ,$_match);
		if( isset( $_match['get'] ) ){
			foreach( $_match['get'] as $_key=>$_getPar ){
				if( $this->_data->filtered['settings']['publishing_options'] != 'preview' ){
					$arrFiles['index.php']=str_replace( $_match[0][$_key], '<?php echo @$_GET["'.$_getPar.'"]; ?>', $arrFiles['index.php'] );
				}else{
					$arrFiles['index.php']=str_replace( $_match[0][$_key], '', $arrFiles['index.php'] );
				}
			}
		}
		/*---------------------------------------------*/
		if( $_withLogger ){
			$_start=microtime(true)-$_start;
			$_logger->info('all files update content time: '.$_start );
			$_start=microtime(true);
		}
		/*---------------------------------------------*/
		Core_Files::setContentMass($arrFiles,$this->_dir.'source'.DIRECTORY_SEPARATOR);
		/*---------------------------------------------*/
		if( $_withLogger ){
			$_start=microtime(true)-$_start;
			$_logger->info('set mass time: '.$_start );
			$_start=microtime(true);
		}
		/*---------------------------------------------*/
		ob_clean();
		$_transport=new Project_Placement_Transport();
		if( $this->_data->filtered['flg_template']==1 && $this->_data->filtered['settings']['publishing_options'] != 'preview' ){
			$this->_data->filtered['settings']['publishing_options']='local';
		}
		if( $this->_data->filtered['settings']['publishing_options']=='download' ){
			Core_Zip::getInstance()->open( $this->_dir.'source.zip', ZipArchive::CREATE );
			Core_Zip::getInstance()->addDirAndClose( $this->_dir.'source'.DIRECTORY_SEPARATOR );
			if( empty( $this->_data->filtered['id'] ) ){
				$_fileName=time();
			}else{
				$_fileName=$this->_data->filtered['id'];
			}
			copy( $this->_dir.'source.zip', Zend_Registry::get('config')->path->absolute->user_temp.Core_Users::$info['id'].DIRECTORY_SEPARATOR.'lpb_'.$_fileName.'.zip' );
			$this->_downloadLink=Zend_Registry::get( 'config' )->domain->url.Zend_Registry::get('config')->path->html->user_temp.Core_Users::$info['id'].'/'.'lpb_'.$_fileName.'.zip';
			/*---------------------------------------------*/
			if( $_withLogger ){
				$_start=microtime(true)-$_start;
				$_logger->info('download time: '.$_start );
				$_start=microtime(true);
			}
			/*---------------------------------------------*/
			Core_Files::download( $this->_dir.'source.zip' );
		}elseif( in_array( $this->_data->filtered['settings']['publishing_options'], array( 'remote', 'external' ) ) && isset( $this->_data->filtered['settings']['placement_id'] ) ){
			$_placement=new Project_Placement();
			$_placement->withIds($this->_data->filtered['settings']['placement_id'])->onlyOne()->getList( $_place );
			$_newUrl='';
			if( $_place['flg_type']==Project_Placement::REMOTE_HOSTING ){
				$_newUrl=$this->_data->filtered['settings']['url'];
			}elseif( isset( $_place['domain_http'] ) ){
				$this->_data->filtered['settings']['ftp_directory']=str_replace('//','/',($this->_data->filtered['settings']['ftp_root']==1)?'/':'/'.trim($this->_data->filtered['settings']['ftp_directory'],'/').'/');
				$_newUrl='http://'.$_place['domain_http'].$this->_data->filtered['settings']['ftp_directory'];
			}
			if( !$_transport
				->setInfo( $this->_data->filtered['settings'] )
				->setSourceDir( $this->_dir.'source'.DIRECTORY_SEPARATOR )
				->placeAndBreakConnect() ){
				return Core_Data_Errors::getInstance()->setError('Cant upload source');
			}
			$this->_data->setElements( array( 'settings'=>$_transport->getInfo(), 'url'=>$_newUrl ) );
			/*---------------------------------------------*/
			if( $_withLogger ){
				$_start=microtime(true)-$_start;
				$_logger->info('placement other time: '.$_start );
				$_start=microtime(true);
			}
			/*---------------------------------------------*/
		}elseif( $this->_data->filtered['settings']['publishing_options']=='local' || $this->_data->filtered['settings']['publishing_options']=='local_nossl' ){
			// placement to onlinenewsletters.net Project_Placement_Transport->setInfo
			if( empty( $this->_data->filtered['settings']['ftp_directory'] ) || ( $this->_data->filtered['settings']['ftp_directory']=='/' ) ){
				$this->_data->filtered['settings']['ftp_directory']='/'.$this->randomDirName().'/';
			}
			unset( $this->_data->filtered['settings']['flg_type'] );
			$_setting=$this->_data->filtered['settings'];
			if( $this->_data->filtered['settings']['publishing_options']=='local' ){
				$_setting=array(
					'flg_type'=> '1',
					'flg_passive'=> '1',
					'flg_checked'=> '2',
					'flg_sended_hosting'=> '0',
					'flg_sended_domain'=> '0',
					'flg_auto'=> '1',
					'domain_http'=> 'onlinenewsletters.net',
					'placement_id'=> NULL,
					'domain_ftp'=> NULL,
					'username'=> NULL,
					'password'=> NULL,
					'db_host'=> NULL,
					'db_name'=> NULL,
					'db_username'=> NULL,
					'db_password'=> NULL,
				)+$_setting;
			}
			if( $this->_data->filtered['settings']['publishing_options']=='local_nossl' ){
				$_setting=array(
					'flg_type'=> '1',
					'flg_passive'=> '1',
					'flg_checked'=> '2',
					'flg_sended_hosting'=> '0',
					'flg_sended_domain'=> '0',
					'flg_auto'=> '1',
					'domain_http'=> 'consumertips.net',
					'placement_id'=> NULL,
					'domain_ftp'=> NULL,
					'username'=> NULL,
					'password'=> NULL,
					'db_host'=> NULL,
					'db_name'=> NULL,
					'db_username'=> NULL,
					'db_password'=> NULL,
				)+$_setting;
			}
			if( !$_transport
				->setInfo( $_setting )
				->setSourceDir( $this->_dir.'source'.DIRECTORY_SEPARATOR )
				->placeAndBreakConnect() ){
				return Core_Data_Errors::getInstance()->setError('Cant upload source');
			}
			$this->_data->setElements( array( 'settings'=>$_transport->getInfo() ) );
			if( $this->_data->filtered['flg_template'] == 1 ){
				$_link=$this->getGeneratedLink();
				if( !isset( $this->_data->filtered['settings']['template_hash'] ) ){
					$this->_data->filtered['settings']['template_hash']=md5($_link);
				}
				if( isset( $this->_data->filtered['settings']['template_reload_file'] ) ){
					$_newTemplateHash=md5($this->_data->filtered['settings']['template_reload_file']);
					if( is_file( Zend_Registry::get('config')->path->absolute->user_files.'squeeze'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.$this->_data->filtered['settings']['template_hash'].".jpg" ) ){
						unlink( Zend_Registry::get('config')->path->absolute->user_files.'squeeze'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.$this->_data->filtered['settings']['template_hash'].".jpg" );
					}
					$this->_data->filtered['settings']['template_file_path']=Zend_Registry::get('config')->path->absolute->user_files.'squeeze'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.$_newTemplateHash.".jpg";
					$this->_data->filtered['settings']['template_hash']=$_newTemplateHash;
					$this->_data->filtered['settings']['template_return']=copy( '.'.$this->_data->filtered['settings']['template_reload_file'], $this->_data->filtered['settings']['template_file_path'] );
				}elseif( !isset( $this->_data->filtered['settings']['template_file_path'] ) ){
					$this->_data->filtered['settings']['template_file_path']=Zend_Registry::get('config')->path->absolute->user_files.'squeeze'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.$this->_data->filtered['settings']['template_hash'].".jpg";
					exec("/bin/wkhtmltoimage --width 1200 --height 800 --quality 100 --zoom 1 ".escapeshellarg($_link)." ".$this->_data->filtered['settings']['template_file_path'], $this->_data->filtered['settings']['template_output'], $this->_data->filtered['settings']['template_return']);
				}
				$this->_data->setElements( array( 'settings'=>$this->_data->filtered['settings'] ) );
			}
			/*---------------------------------------------*/
			if( $_withLogger ){
				$_start=microtime(true)-$_start;
				$_logger->info('placement local time: '.$_start );
				$_start=microtime(true);
			}
			/*---------------------------------------------*/
		}elseif( $this->_data->filtered['settings']['publishing_options'] == 'preview' ){
			echo $arrFiles['index.php'];
			/*---------------------------------------------*/
			if( $_withLogger ){
				$_start=microtime(true)-$_start;
				$_logger->info('preview time: '.$_start );
				$_start=microtime(true);
			}
			/*---------------------------------------------*/
			exit;
		}
		if( $this->_data->filtered['settings']['publishing_options'] != 'download' || $_flgSaveIfOnlyDownload ){
			$this->_data->setFilter( array( 'clear' ) );
			Project_Squeeze::getImageFromLink( $this->_data->filtered['settings']['url'] );
			rename( Zend_Registry::get('config')->path->absolute->user_files.'squeeze'.DIRECTORY_SEPARATOR.'backgrounds'.DIRECTORY_SEPARATOR.md5( $this->_data->filtered['settings']['url'] ).".jpg", Zend_Registry::get('config')->path->absolute->user_files.'squeeze'.DIRECTORY_SEPARATOR.'screenshots'.DIRECTORY_SEPARATOR.md5( $this->_data->filtered['settings']['url'] ).".jpg" );
		}
		if( !$this->set() ){
			return Core_Data_Errors::getInstance()->setError('Can\'t save squeeze data');
		}
		/*---------------------------------------------*/
		if( $_withLogger ){
			$_start=microtime(true)-$_start;
			$_logger->info('Second save time: '.$_start );
			$_logger->info('End transaction -----------------------------------------------------------------------------------------------------' );
		}
		/*---------------------------------------------*/
		if( $this->_data->filtered['settings']['publishing_options']=='download' ){
			//die();
		}
		return true;
	}
	
	private $_downloadLink='';
	
	function getGeneratedLink(){
		if( $this->_data->filtered['settings']['publishing_options']=='download' ){
			return $this->_downloadLink;
		}
		if( $this->_data->filtered['settings']['publishing_options']=='local' ){
			return 'https://onlinenewsletters.net'.$this->_data->filtered['settings']['ftp_directory'];
		}
		if( $this->_data->filtered['settings']['publishing_options']=='local_nossl' ){
			return 'http://consumertips.net'.$this->_data->filtered['settings']['ftp_directory'];
		}
		if( in_array( $this->_data->filtered['settings']['publishing_options'], array( 'remote', 'external' ) ) ){
			return $this->_data->filtered['url'];
		}
		return '';
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
		$_test=get_headers( 'https://onlinenewsletters.net/'.$dirName );
		if( $n>10 ){
			throw new Project_Placement_Exception( Core_Errors::DEV.'|no empty dir names' );
		}
		if( $_test[0]== 'HTTP/1.1 200 OK' ){
			$n++;
			$dirName=$this->randomDirName( $n );
		}
		return $dirName;
	}
	
	public function prepareHeader( $str ){
		$str=preg_replace("/\n*|\r*/si",'',$str);
		$str=preg_replace("/<p>/si",'',$str);
		$str=preg_replace("/<\/p>/si",' ',$str);
		if( $this->_data->filtered['settings']['publishing_options'] != 'preview' ){
			$str=str_replace("#city#",'<?php echo @$city; ?>',$str);
		}else{
			$str=str_replace("#city#",'City',$str);
		}
		return trim($str);
	}

	public static function updateForm( $strForm='' ){
		$str=preg_replace("/\n*|\r*/si",'',htmlspecialchars_decode( $strForm ));
		preg_match_all('/(?P<forms><form.+?<\/form>)/is',$str,$_match);
		foreach( $_match['forms'] as $_key=>$_form ){
			preg_match_all('/(?<inputs><input.*?>)/si',$_form,$_match);
			preg_match_all('/(?<buttons><button.*?>)/si',$_form,$_matchB);
			if( !empty( $_matchB['buttons'] ) ){
				foreach( $_matchB['buttons'] as $_button ){
					$_match['inputs'][]=$_button;
				}
			}
			foreach( $_match['inputs'] as $_input ){
				if( ( stripos($_input,'type="submit"')||stripos($_input,'type=submit')||stripos($_input,"type='submit'") ) !== false ){
					if( stripos($_input,'onclick=') !== false ){
						$_addjs='PreventExitPopup=true;';
						$_replace=str_replace( 
							array(
								'onclick="',
								"onclick='"
							), 
							array(
								'onclick="'.$_addjs,
								"onclick='".$_addjs
							), 
							$_input
						);
						
					}else{
						$_replace=str_replace( array(
							'type="submit', 
							"type='submit", 
							'type=submit' 
						) , array(
							'type="submit" onclick="PreventExitPopup=true;',
							"type='submit' onclick='PreventExitPopup=true;",
							'type=submit onclick="PreventExitPopup=true;"'
						), $_input );
					}
					$strForm=str_replace( $_input, $_replace, $strForm );
				}
			}
		}
		return $strForm;
	}

	public static function parseForm( $arrData, &$_buttonAction, $_jsOptinPageToButton='' ){
		if( empty($arrData['view_button']) ){
			return '';
		}
		$_links=$arrData['link_url'];
		if( strpos( $arrData['link_url'], ',' ) !== false ){
			$_links=explode( ',', str_replace( ' ', '', $arrData['link_url'] ) );
		}
		if( is_array( $_links ) ){
			$_links='<?php $_newLinks='.var_export( $_links, true ).';echo $_newLinks[array_rand($_newLinks)]; ?>';
		}
		if( $arrData['type_page']==1 || $arrData['type_page']==3 ){
			$_option=$_class=array();
			$_html=$_toggleBlock='';
			if( $arrData['type_page']==3 && !empty($arrData['facebook_username']) ){
				$_html.='<script type="text/javascript">var actionUrl="https://m.me/'.$arrData['facebook_username'].'";</script>';
				$_buttonAction.="
PreventExitPopup=true;
setTimeout(function(){
	".$_jsOptinPageToButton."
	window.location.href=actionUrl;
}, 1000);
return false;
";
$_jsOptinPageToButton='';
			}
			if( $arrData['type_page_through']==0 && $arrData['type_page']!=3 ){
				if( $arrData['publishing_options'] != 'preview' ){
					$_html.='<script type="text/javascript">var actionUrl="'.$_links.'";</script>';
				}else{
					$_newLink=$_links;
					preg_match_all('#(?<php><\?php.*\?>?)#si',$_newLink,$_match);
					if( !empty( $_match['php'] ) ){
						foreach( $_match['php'] as $_remove ){
							$_newLink=str_replace( $_remove,'', $_newLink );
						}
					}
					$_html.='<script type="text/javascript">var actionUrl="'.$_newLink.'";</script>';
				}
				$_buttonAction.="
PreventExitPopup=true;
setTimeout(function(){
	".$_jsOptinPageToButton."
	window.location.href=actionUrl;
}, 1000);
return false;
";
$_jsOptinPageToButton='';
			}elseif( $arrData['type_page_through']==2 && $arrData['type_page']!=3 && !empty($arrData['phone_number']) ){
				$_buttonAction.="
setTimeout(function(){
	".$_jsOptinPageToButton."
	window.location.href=\"".$arrData['phone_number']."\";
}, 1000);
return false;
";
$_jsOptinPageToButton='';
			}elseif( $arrData['type_page_through']==1 && $arrData['type_page']!=3 ){
				if( empty( $arrData['popupOnActionId'] ) && $arrData['type_triggered_mode'] == 0){
					$_class[]='fancybox';
					$_option[]='href="#fancybox-form" id="redirect_link"';
				} elseif ( empty($arrData['popupOnActionId']) && $arrData['type_triggered_mode'] == 1){
					if( $arrData['optin']['type'] == 'mooptin' 
						&& isset( $arrData['mo_optin_id'] ) 
						&& !empty( $arrData['mo_optin_id'] )
					){
						$form=$arrData['form'];
					}else{
						$form=self::updateForm( $arrData['form'] );
					}
					$_class[]='toggleMode';
					$_toggleBlock='<div class="toggleMode" style="display:none;">'.$form.'</div>';
				}
			}
			$_class[]='get-button';
			if( $arrData['view_button']==1 ){
				$_tag="input";$_tagEnd=" /";
				$_option[]='type="button"';
				$_class[]='kic';
				$_option[]='rel="'.$arrData['button_effect'].'"';
				$_option[]='style="float:none;margin:0;cursor:pointer !important;"';
			}else{
				$_tag="a";$_tagEnd='>'.$arrData['link'].'</a';
				$_option[]='style="cursor: pointer !important; text-decoration: underline !important;"';
				$_option[]='href="'.$_links.'"';
			}
			if( !empty( $_class ) ){
				$_option[]='class="'.implode(' ',$_class).'"';
			}
			$_html.='<div align="center" style="overflow: visible;" id="action_form">'.$textBefore.'<'.$_tag.' '.implode(' ',$_option).$_tagEnd.'></div><div class="clear">&nbsp;</div>';
			if(!empty($_toggleBlock)){ $_html .= $_toggleBlock; }
			if( $arrData['publishing_options'] != 'preview' ){
				$_html.='<?php if( isset( $_GET[\'auto\'] ) && $_GET[\'auto\']==1 ){ ?>
					<script type="text/javascript">
						jQuery( document ).ready(function(){
							jQuery("div#action_form .get-button").click();
						});
					</script>
				<?php } ?>';
			}
			return $_html;
		}
		$str=preg_replace("/\n*|\r*/si",'',htmlspecialchars_decode($arrData['form']));
		preg_match_all('/(?P<forms><form.+?<\/form>)/is',$str,$_match);
		$_html='';
		foreach( $_match['forms'] as $_key=>$_form ){
			preg_match_all('#(?<inputs><input.*?[^?]>)#si',$_form,$_match);
			preg_match_all('#(?<buttons><button.*?[^?]>)#si',$_form,$_matchB);
			preg_match('/action=["|\'](?<action>.*?)["|\']/si',$_form,$_action);
			preg_match('/id=["|\'](?<formId>.*?)["|\']/si',$_form,$_formId);
			preg_match('#<div class="gdpr-block">(.*)</div>#', $_form, $_gdpr);
			$_strFormId='optin-form-'.$_key;
			if( !empty( $_formId['formId'] ) ){
				$_strFormId=$_formId['formId'];
			}
			preg_match('/method=["|\'](?<method>.*?)["|\']/si',$_form,$_method);
			$_html.='<form id="'.$_strFormId.'" action="'.$_action['action'].'" method="'.$_method['method'].'" >';
			if( !empty( $_matchB['buttons'] ) ){
				foreach( $_matchB['buttons'] as $_button ){
					$_match['inputs'][]=$_button;
				}
			}
			foreach( $_match['inputs'] as $_input ){
				if( stripos($_input,'submit')!==false ){
					if( $_gdpr ){
						if( $arrData['publishing_options'] == 'local_nossl' && stripos( $_gdpr[0], 'OnlineNewsletters.net' ) !== false){
							$_gdpr[0] = str_replace( array( 'OnlineNewsletters.net', 'https://onlinenewsletters.net' ), array( 'ConsumerTips.net', 'http://consumertips.net' ), $_gdpr[0] );
						}
						if( $arrData['publishing_options'] == 'local' && stripos( $_gdpr[0], 'OnlineNewsletters.net' ) !== false){
							$_gdpr[0] = str_replace( array( 'ConsumerTips.net', 'consumertips.net' ), array( 'OnlineNewsletters.net', 'onlinenewsletters.net' ), $_gdpr[0] );
						}
						$_html .= $_gdpr[0];
					}
					if( $arrData['view_button']==1 ){
						$_html.='<input type="button" class="get-button kic" rel="'.$arrData['button_effect'].'" data-formid="'.$_strFormId.'" /><div class="clear">&nbsp;</div>';
						$_buttonAction.="
PreventExitPopup=true;
if( typeof jQuery(evt.target).attr('data-formid') != 'undefined' 
	&& jQuery('#'+jQuery(evt.target).attr('data-formid')).length > 0 
	&& jQuery('#'+jQuery(evt.target).attr('data-formid'))[0].localName=='form' 
){
	setTimeout(function(){
		var checkEmail=jQuery('#'+jQuery(evt.target).attr('data-formid')+' input[name=\"email\"]');
		if(checkEmail.length > 0 ){
			checkEmail=checkEmail[0].value;
			if (!(/(.+)@(.+){2,}\.(.+){2,}/.test(checkEmail)) || checkEmail=='' || checkEmail==null){
				flgClickButton=false;
				alert('Please enter a valid email');
				return;
			}
		}".$_jsOptinPageToButton."
		if( jQuery(jQuery('#'+jQuery(evt.target).attr('data-formid'))[0]).attr('action') != '' ){
			jQuery(jQuery('#'+jQuery(evt.target).attr('data-formid'))[0]).submit();
		}
	},3000);
}
//return false;
";
$_jsOptinPageToButton='';
					}else{
						$_html.='<div align="center" style="overflow: visible;"><a style="cursor: pointer !important; text-decoration: underline !important;" class="get-button" href="">'.$arrData['link'].'</a></div><div class="clear">&nbsp;</div>';
						$_buttonAction.="
PreventExitPopup=true;
setTimeout( function(){
	var checkEmail=jQuery('#'+jQuery(evt.target).attr('data-formid')+' input[name=\"email\"]');
	if(checkEmail.length > 0 ){
		checkEmail=checkEmail[0].value;
		if (!(/(.+)@(.+){2,}\.(.+){2,}/.test(checkEmail)) || checkEmail=='' || checkEmail==null){
			flgClickButton=false;
			alert('Please enter a valid email');
			return;
		}
	}".$_jsOptinPageToButton."
	document.forms['".$_strFormId."'].submit();
},3000);
";
$_jsOptinPageToButton='';
					}
				}elseif( preg_match('/.*?email.*?/si',$_input)&&( preg_match('/type=["|\']text["|\']/si',$_input) || preg_match('/type=["|\']email["|\']/si',$_input) ) ){
					preg_match('/name=["|\'](?<name>.*?)["|\']/si',$_input,$_attr);
					preg_match('/placeholder=["|\'](?<placeholder>.*?)["|\']/si',$_input,$_attrPlaceholder);
					if( isset( $arrData['form_autoresponder_hide'][ md5( $_attr['name'].$_action['action'] ) ] ) && $arrData['form_autoresponder_hide'][ md5( $_attr['name'].$_action['action'] ) ] == 1 ){
						continue;
					}
					$_eltValue='Enter Your Best Email';
					if( isset( $arrData['form_autoresponder'][ md5( $_attr['name'].$_action['action'] ) ] ) ){
						$_eltValue=$arrData['form_autoresponder'][ md5( $_attr['name'].$_action['action'] ) ];
					}
					if( isset( $_attrPlaceholder['placeholder'] ) ){
						$_eltValue=$_attrPlaceholder['placeholder'];
					}
					$elementsUlp='';
					if( $arrData['optin']['type'] == 'mooptin' && $arrData['mo_optin']['options']['type'] == 'optin' ){
						$elementsUlp=' rel="ulp-email"';
					}
					//onclick="this.value=\'\';" onfocus="this.select()" onblur="this.value=!this.value?\''.$_eltValue.'\':this.value;"
					$_html.='<input type="email" id="best_email" name="'.$_attr['name'].'"'.$elementsUlp.' placeholder="'.$_eltValue.'" value="';
					if( $arrData['publishing_options'] != 'preview' ){
						$_html.='<?php 
	if( isset( $_GET[\'email\'] )&& !empty( $_GET[\'email\'] ) ){
		echo $_GET[\'email\'].\'" style="display:none;\';
	}
?>';
					}else{
						$_html.=$_eltValue;
					}
					$_html.='">';
				}elseif( preg_match('/type=["|\']text["|\']/si',$_input) ){
					preg_match('/name=["|\'](?<name>.*?)["|\']/si',$_input,$_attr);
					if( isset( $arrData['form_autoresponder_hide'][ md5( $_attr['name'].$_action['action'] ) ] ) && $arrData['form_autoresponder_hide'][ md5( $_attr['name'].$_action['action'] ) ] == 1 ){
						continue;
					}
					$_eltValue='';
					if( isset( $arrData['form_autoresponder'][ md5( $_attr['name'].$_action['action'] ) ] ) ){
						$_eltValue=$arrData['form_autoresponder'][ md5( $_attr['name'].$_action['action'] ) ];
					}else{
						preg_match('/value=["|\'](?<value>.*?)["|\']/si',$_input,$_attrValue);
						preg_match('/placeholder=["|\'](?<placeholder>.*?)["|\']/si',$_input,$_attrPlaceholder);
						$_nameTst=ucfirst($_attr['name']);
						$_eltValue=false;
						if( isset( $_attrValue['value'] ) ){
							$_eltValue=$_attrValue['value'];
						}
						if( strpos( strtolower( $_nameTst ), 'name' ) !== false ){
							$_eltValue='Enter Your Name';
							$elementsUlp='';
							if( $arrData['optin']['type'] == 'mooptin' && $arrData['mo_optin']['options']['type'] == 'optin' ){
								$elementsUlp=' rel="ulp-name"';
							}
						}
						if( strpos( strtolower( $_nameTst ), 'firstname' ) !== false ||  strpos( strtolower( $_nameTst ), 'fname' ) !== false ){
							$_eltValue='Enter Your First Name';
						}
						if( strpos( strtolower( $_nameTst ), 'lastname' ) !== false ||  strpos( strtolower( $_nameTst ), 'lname' ) !== false ){
							$_eltValue='Enter Your Last Name';
						}
						if( strpos( strtolower( $_nameTst ), 'street' ) !== false ){
							$_eltValue='Enter Your Street';
						}
						if( strpos( strtolower( $_nameTst ), 'zip' ) !== false ){
							$_eltValue='Enter Your Zip Code';
						}
						if( strpos( strtolower( $_nameTst ), 'city' ) !== false ){
							$_eltValue='Enter Your City';
						}
						if( strpos( strtolower( $_nameTst ), 'country' ) !== false ){
							$_eltValue='Enter Your Country';
						}
						if( strpos( strtolower( $_nameTst ), 'phone' ) !== false ){
							$_eltValue='Enter Your Phone';
							$elementsUlp='';
							if( $arrData['optin']['type'] == 'mooptin' && $arrData['mo_optin']['options']['type'] == 'optin' ){
								$elementsUlp=' rel="ulp-phone"';
							}
						}
						if( isset( $_attrPlaceholder['placeholder'] ) ){
							$_eltValue=$_attrPlaceholder['placeholder'];
						}
						if( $_eltValue === false ){
							$_eltValue=$_nameTst;
						}
						if( $arrData['publishing_options'] != 'preview' ){
							$_eltValue.='" value="<?php 
if( isset( $_GET[\''.$_attr['name'].'\'] ) && !empty( $_GET[\''.$_attr['name'].'\'] ) ){
	echo $_GET[\''.$_attr['name'].'\'];
}
?>';
						}
					}
					$_html.='<input type="text" name="'.$_attr['name'].'"'.$elementsUlp.' placeholder="'.$_eltValue.'">'; //  onclick="this.value=\'\';" onfocus="this.select()" onblur="this.value=!this.value?\''.$_eltValue.'\':this.value;"
				}elseif( preg_match('/type=["|\']checkbox["|\']/si',$_input) ){
					preg_match('/name=["|\'](?<name>.*?)["|\']/si',$_input,$_attr);
					$_html.='<input type="hidden" name="'.$_attr['name'].'" >';
				}elseif( preg_match('/type=["|\']radio["|\']/si',$_input) ){
					preg_match('/name=["|\'](?<name>.*?)["|\']/si',$_input,$_attr);
					$_html.='<input type="hidden" name="'.$_attr['name'].'" >';
				}elseif( preg_match('/type=["|\']hidden["|\']/si',$_input) ){
					preg_match('/name=["|\'](?<name>.*?)["|\']/si',$_input,$_attr);
					preg_match('/value=["|\'](?<value>.*?)["|\']/si',$_input,$_attrValue);
					$_eltValue='';
					if( isset( $_attrValue['value'] ) ){
						$_eltValue=$_attrValue['value'];
					}
					if( $arrData['publishing_options'] != 'preview' ){
						$_eltValue='value="<?php 
if( isset( $_GET[\''.$_attr['name'].'\'] ) && !empty( $_GET[\''.$_attr['name'].'\'] ) ){
	echo $_GET[\''.$_attr['name'].'\'];
}else{
	echo \''.$_eltValue.'\';
}
?>"';
					}
					$_html.='<input type="hidden" name="'.$_attr['name'].'" >';
				}else{
					$_html.=$_input;
				}
			}
			$_html.='</form>';
			if( $arrData['publishing_options'] != 'preview' ){
				$_html.='<?php if( isset( $_GET[\'auto\'] ) && $_GET[\'auto\']==1 ){ ?>
					<script type="text/javascript">
						jQuery( document ).ready(function(){ 
							jQuery("form#'.$_strFormId.' .get-button").click();
						});
					</script>
				<?php } ?>';
			}
		}
		return $_html;
	}
	
	public static function editFormValues( $_strForm ){
		$str=preg_replace( "/\n*|\r*/si",'',htmlspecialchars_decode( $_strForm ) );
		preg_match_all('/(?P<forms><form.+?<\/form>)/is',$str,$_match);
		$_html='';
		foreach( $_match['forms'] as $_key=>$_form ){
			preg_match_all('#(?<inputs><input.*?[^?]>)#si',$_form,$_match);
			preg_match_all('#(?<buttons><button.*?[^?]>)#si',$_form,$_matchB);
			preg_match('/action=["|\'](?<action>.*?)["|\']/si',$_form,$_action);
			if( !empty( $_matchB['buttons'] ) ){
				foreach( $_matchB['buttons'] as $_button ){
					$_match['inputs'][]=$_button;
				}
			}
			foreach( $_match['inputs'] as $_input ){
				if( preg_match('/.*?email.*?/si',$_input)&&( preg_match('/type=["|\']text["|\']/si',$_input) || preg_match('/type=["|\']email["|\']/si',$_input) ) ){
					preg_match('/name=["|\'](?<name>.*?)["|\']/si',$_input,$_attr);
					$_html.='<input type="text" class="form-control" name="settings[form_autoresponder_name]['.md5( $_attr['name'].$_action['action'] ).']" value="'.$_attr['name'].'" style="width: 240px;float: left;">';
					$_html.='<input type="text" class="form-control" name="settings[form_autoresponder]['.md5( $_attr['name'].$_action['action'] ).']" value="Enter Your Best Email" style="width: 340px;float: left;margin-left: 10px;">';
					$_html.='<div class="checkbox checkbox-primary" style="width: 100px;float: left;margin-left: 10px;"><input type="checkbox" name="settings[form_autoresponder_hide]['.md5( $_attr['name'].$_action['action'] ).']" value="1"><label>Hide</label></div>';
				}elseif( preg_match('/type=["|\']text["|\']/si',$_input) ){
					preg_match('/name=["|\'](?<name>.*?)["|\']/si',$_input,$_attr);
					preg_match('/value=["|\'](?<value>.*?)["|\']/si',$_input,$_attrValue);
					preg_match('/placeholder=["|\'](?<placeholder>.*?)["|\']/si',$_input,$_attrPlaceholder);
					$_nameTst=ucfirst($_attr['name']);
					$_value=false;
					if( isset( $_attrValue['value'] ) ){
						$_value=$_attrValue['value'];
					}
					if( isset( $_attrPlaceholder['placeholder'] ) ){
						$_value=$_attrPlaceholder['placeholder'];
					}
					if( strpos( strtolower( $_nameTst ), 'name' ) !== false ){
						$_value='Enter Your Name';
					}
					if( strpos( strtolower( $_nameTst ), 'firstname' ) !== false ||  strpos( strtolower( $_nameTst ), 'fname' ) !== false ){
						$_value='Enter Your First Name';
					}
					if( strpos( strtolower( $_nameTst ), 'lastname' ) !== false ||  strpos( strtolower( $_nameTst ), 'lname' ) !== false ){
						$_value='Enter Your Last Name';
					}
					if( strpos( strtolower( $_nameTst ), 'street' ) !== false ){
						$_value='Enter Your Street';
					}
					if( strpos( strtolower( $_nameTst ), 'zip' ) !== false ){
						$_value='Enter Your Zip Code';
					}
					if( strpos( strtolower( $_nameTst ), 'city' ) !== false ){
						$_value='Enter Your City';
					}
					if( strpos( strtolower( $_nameTst ), 'country' ) !== false ){
						$_value='Enter Your Country';
					}
					if( strpos( strtolower( $_nameTst ), 'phone' ) !== false ){
						$_value='Enter Your Phone';
					}
					if( $_value === false ){
						$_value=$_nameTst;
					}
					$_html.='<input type="text" class="form-control" name="settings[form_autoresponder_name]['.md5( $_attr['name'].$_action['action'] ).']" value="'.$_attr['name'].'" style="width: 240px;float: left;">';
					$_html.='<input type="text" class="form-control" name="settings[form_autoresponder]['.md5( $_attr['name'].$_action['action'] ).']" value="'.$_value.'" style="width: 340px;float: left;margin-left: 10px;">';
					$_html.='<div class="checkbox checkbox-primary" style="width: 100px;float: left;margin-left: 10px;"><input type="checkbox" name="settings[form_autoresponder_hide]['.md5( $_attr['name'].$_action['action'] ).']" value="1"><label>Hide</label></div>';
				}
			}
		}
		return $_html;
	}
	
	protected $_onlyTemplates=false;
	protected $_searchTags=array();
	protected $_withTags=false;
	protected $_withUrl=false;
	protected $_flgFunnel=false;
	
	public function onlyTemplates(){
		$this->_onlyTemplates=true;
		$this->_searchTags['where']=array('flg_template-1');
		return $this;
	}

	public function flgFunnel( $_bool=1 ){
		$this->_flgFunnel=$_bool;
		return $this;
	}

	protected function init(){
		parent::init();
		$this->_onlyTemplates=false;
		$this->_withTags=false;
		$this->_withoutSort=false;
		$this->_withListFromTracker=false;
		$this->_withTime=false;
		$this->_withUrl=false;
		$this->_flgFunnel=false;
		//$this->_withReportById=null;
	}

	public function withTags( $_tags ){
		if( isset( $_tags ) && !empty( $_tags ) ){
			
			$this->_searchTags['search']=array($_tags);
			$_tags=array_unique(array_merge(explode(' ', $_tags), explode(',', $_tags)));
			if($_tags){
				foreach ($_tags as $key => $value){
					$this->_withTags[]='d.tags LIKE "%'.trim(Core_Sql::fixInjection($value), "'").'%"';
				}
				$this->_withTags=implode(' OR ', $this->_withTags);
			}
		}
		return $this;
	}

	public function withUrl( $_url ){
		$this->_withUrl=$_url;
		return $this;
	}

	protected function assemblyQuery(){
		parent::assemblyQuery();
		if( $this->_onlyTemplates ){
			$this->_crawler->set_where('d.flg_template=1' );
		}
		if( $this->_withTags ){
			$this->_crawler->set_where($this->_withTags);
		}
		if( $this->_withUrl ){
			$this->_crawler->set_where( 'd.url LIKE "%' .$this->_withUrl .'%"' );
		}
		if( $this->_flgFunnel !== false ){
			$this->_crawler->set_where( 'd.flg_funnel='.(int)$this->_flgFunnel );
		}
		if($this->_withoutSort){
			$this->_crawler->q_order=array();
		}
	}

	public function getPaging( &$arrRes ){
		parent::getPaging($arrRes);
		if($this->_searchTags){
			if( strpos( $arrRes['urlmin'], '?' ) === false ){
				$_tail='?';
			} else {
				$_tail='&';
			}
			foreach ($this->_searchTags as $key => $value){
				if( strpos( $arrRes['urlmin'], $key.'=' ) === false ){
					$_urlParams='';
					if(count($value) == 1){ 
						$_urlParams=$key.'='.$value[0]; 
					} else {
						$_urlParams=array();
						foreach ($value as $k => $v) 
							$_urlParams[]=$key.'[]='.$v;
						$_urlParams=implode('&',$_urlParams);
					}
					$arrRes['urlmin']=$arrRes['urlmin'].$_tail.$_urlParams;
					$arrRes['urlplus']=$arrRes['urlplus'].$_tail.$_urlParams;
					$arrRes['urlmax']=$arrRes['urlmax'].$_tail.$_urlParams;
					foreach( $arrRes['num'] as &$_url ){
						$_url['url']=$_url['url'].$_tail.$_urlParams;
					}
				}
			}
		}
		$this->_searchTags=array();
		return $this;
	}
}
?>