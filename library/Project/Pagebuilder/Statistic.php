<?php
class Project_Pagebuilder_Statistic extends Core_Data_Storage {

	public function __construct($_uid = false){
		if( $uid === false ) $_uid = Core_Users::$info['id'];
		try {
			Core_Sql::setConnectToServer( 'lpb.tracker' );
			//========
			
			$_arrNulls=Core_Sql::getAssoc("SELECT NULL
				FROM INFORMATION_SCHEMA.COLUMNS
				WHERE table_name = 'pb_view_".$_uid."'
				AND column_name = 'pb_page';");
			if( count( $_arrNulls ) == 0 ){
				Core_Sql::setExec("ALTER TABLE `pb_view_".$_uid."` ADD `pb_page` VARCHAR(255) DEFAULT 'index' COLLATE 'utf8_unicode_ci';");
			}
			
			$_arrNulls=Core_Sql::getAssoc("SELECT NULL
				FROM INFORMATION_SCHEMA.COLUMNS
				WHERE table_name = 'pb_click_".$_uid."'
				AND column_name = 'pb_page';");
			if( count( $_arrNulls ) == 0 ){
				Core_Sql::setExec("ALTER TABLE `pb_click_".$_uid."` ADD `pb_page` VARCHAR(255) DEFAULT 'index' COLLATE 'utf8_unicode_ci';");
			}
			
			Core_Sql::setExec( "CREATE TABLE IF NOT EXISTS `pb_click_".$_uid."` (
				`id` INT(11) NOT NULL AUTO_INCREMENT,
				`pb_id` INT(11) NULL DEFAULT NULL,
				`pb_page` VARCHAR(255) DEFAULT 'index' COLLATE 'utf8_unicode_ci',
				`ip` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_unicode_ci',
				`country_id` INT(4) NOT NULL DEFAULT '0',
				`added` INT(11) UNSIGNED NOT NULL DEFAULT '0',
				UNIQUE INDEX `id` (`id`)
			)
			COLLATE='utf8_general_ci'
			ENGINE=InnoDB" );

			Core_Sql::setExec( "CREATE TABLE IF NOT EXISTS `pb_view_".$_uid."` (
				`id` INT(11) NOT NULL AUTO_INCREMENT,
				`pb_id` INT(11) NULL DEFAULT NULL,
				`pb_page` VARCHAR(255) DEFAULT 'index' COLLATE 'utf8_unicode_ci',
				`ip` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_unicode_ci',
				`country_id` INT(4) NOT NULL DEFAULT '0',
				`added` INT(11) UNSIGNED NOT NULL DEFAULT '0',
				UNIQUE INDEX `id` (`id`)
			)
			COLLATE='utf8_general_ci'
			ENGINE=InnoDB" );
			//========
			Core_Sql::renewalConnectFromCashe();
		} catch(Exception $e) {
			Core_Sql::renewalConnectFromCashe();
			return $this;
		}
	}

	public function withReportById($id){
		$this->_withReportById=$id;
		return $this;
	}

	private $_withTime=false;
	private $_withPbpage=false;
	
	public function withPbpage( $_pagestr ){
		$this->_withPbpage=Core_Sql::fixInjection( $_pagestr );
	}
	
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

	public function getList(&$mixRes){
		$_arrZeroData = array();
		$_order = $this->_withOrder;
		$_date = $this->_withTime;
		$_pbpage = $this->_withPbpage;

		$sites = new Project_Pagebuilder_Sites();
		
		$sites->withoutSort()->onlyOwner()->withIds($this->_withReportById)->getList( $_arrData );
		foreach( $_arrData as &$_item ){
			$_arrSqueezeIds[]= $_item['id'];
		}
		if( empty( $_arrSqueezeIds ) ){
			return $this;
		}

		$_oldSqueezeIds=$_arrSqueezeIds;	
		
		if( !empty( $this->_withPaging ) ){
			$this->_row_per_page = !empty(Core_Users::$info['arrSettings']['rows_per_page']) ? Core_Users::$info['arrSettings']['rows_per_page'] : 12;
			$_limit_a = (empty($this->_withPaging['page']) ? 0 : ((int)$this->_withPaging['page']) - 1) * $this->_row_per_page;
			$_limit_b = $_limit_a + $this->_row_per_page;
		}

		$_table='pb_view_' . Core_Users::$info['id']; $_sort='DESC'; $_limit='';
		if($_order == 'c.visitors--dn' || $_order == 'c.visitors--up')
		{
			$_table='pb_view_' . Core_Users::$info['id'];
			if($_order == 'c.visitors--dn')
			{
				$_sort='DESC';
			} 
			else 
			{
				$_sort='ASC';
			}
			if( !empty( $this->_withPaging ) )
			{
				$_limit=' LIMIT '.$_limit_a.','.$_limit_b;
			}
		}
		if($_order == 'v.subscribers--dn' || $_order == 'v.subscribers--up'){
			$_table='pb_click_'.Core_Users::$info['id'];
			if($_order == 'v.subscribers--dn'){
				$_sort='DESC';
			} else {
				$_sort='ASC';
			}
			if( !empty( $this->_withPaging ) ){
				$_limit=' LIMIT '.$_limit_a.','.$_limit_b;
			}
		}
		if($_order == 'cv.crt--up' || $_order == 'cv.crt--dn')
		{
			$_table = 'pb_view_' . Core_Users::$info['id'];
			$_limit='';
		}
		$_strOnlyPage = $_arrZeroDataFrom = $_arrZeroDataTo = ''; // AND c.added>=( UNIX_TIMESTAMP( )-60*60*24*30 )
		$_dDate = 60 * 60 * 24 * 30;
		if( !empty( $_date )){
			if( isset( $_date['from'] ) ){
				$_arrZeroDataFrom=' AND c.added >= ' . $_date['from'];
				$_dDate = time() - $_date['from'];
			}
			if( isset( $_date['to'] ) ){
				$_arrZeroDataTo = ' AND c.added <= ' . $_date['to'];
				if( isset( $_date['from'] ) ){
					$_dDate = $_date['to'] - $_date['from'];
				}
			}
		} else {
			$_arrZeroDataFrom = ' AND c.added >= ( UNIX_TIMESTAMP( ) - 60 * 60 * 24 * 30 )';
		}
		if( !empty( $_pbpage ) ){
			$_strOnlyPage=' AND c.pb_page='.$_pbpage;
		}
		Core_Sql::getInstance();
		try {
			Core_Sql::setConnectToServer( 'syndication.qjmpz.com' );
			// список стран
			//=========
			$_countries = Core_Sql::getKeyVal('SELECT id, name FROM getip_countries');
			//=========
			Core_Sql::renewalConnectFromCashe();
		} catch(Exception $e){
			Core_Sql::renewalConnectFromCashe();
		}
		try{
			Core_Sql::setConnectToServer( 'lpb.tracker' );
			if( !empty( $this->_withPaging ) ){
				$this->_crawler->page = (empty($this->_withPaging['page']) ? 1 : (int)$this->_withPaging['page']);
				$this->_crawler->rowtotal=count( Core_Sql::getAssoc('SELECT c.pb_id FROM '.$_table.' c WHERE c.pb_id IN('.Core_Sql::fixInjection( $_arrSqueezeIds ).') GROUP BY c.pb_id') );
				$this->_crawler->m_paged_bar( $this->_paging );
			}
			$this->_searchTags['order']=array($_order);
			// надо получить список всех LPB_ID пользователя по которым есть данные в статистике, а также суммарные данные по ним сразу
			$_arrZeroData = Core_Sql::getAssoc('SELECT c.pb_id, c.pb_page, COUNT(*) as rec_count  FROM ' . $_table . ' c WHERE c.pb_id IN(' . Core_Sql::fixInjection( $_arrSqueezeIds ) . ') ' . $_strOnlyPage . $_arrZeroDataFrom . $_arrZeroDataTo . ' GROUP BY c.pb_page, c.pb_id ' . $_sort . $_limit);
			$_arrSqueezeIds=array();
			$_tmp=array();
			foreach ($_arrZeroData as $key => $value){
				$_arrSqueezeIds[]=$value['pb_id'];
				$_tmp[$value['pb_id'].'/'.$value['pb_page']]=$value['rec_count'];
			}
			unset($key,$value);
			if( empty( $_arrSqueezeIds ) ){
				Core_Sql::renewalConnectFromCashe();
				return $this;
			}
			$_arrZeroData=$_tmp;
			// получаем первый список данных по всем LPB_ID сразу
			$_arrFirstData=Core_Sql::getAssoc('SELECT c.pb_id, c.pb_page, c.country_id, COUNT(*) as counter, '
				.(( $_dDate<=10*24*60*60 )? 'FROM_UNIXTIME(c.added, "%Y-%m-%d %h:00") as fadded ' : 'FROM_UNIXTIME(c.added, "%Y-%m-%d") as fadded ' )
				.'FROM '.$_table.' c 
				WHERE c.pb_id IN('.Core_Sql::fixInjection( $_arrSqueezeIds ).')'.$_strOnlyPage . $_arrZeroDataFrom.$_arrZeroDataTo.'
				GROUP BY fadded, c.pb_id, c.pb_page, c.country_id');
			$_tmp=array();
			$_writer=new Zend_Log_Writer_Stream( Zend_Registry::get('config')->path->absolute->logfiles.'ip.log' );
			$_writer->setFormatter( new Zend_Log_Formatter_Simple("%timestamp% %priorityName% (%priority%): %message%\r\n") );
			$_logger=new Zend_Log( $_writer );
			foreach ($_arrFirstData as $key => $value){
				if( !isset($_countries[$value['country_id']]) ){
					$_countries[0]='Undefined';
				}
				if( !isset($_tmp[$value['pb_id'].'/'.$value['pb_page']]) ){
					$_tmp[$value['pb_id'].'/'.$value['pb_page']]=array(
						'countries' => array( 
							$_countries[$value['country_id']] => $value['counter']
						),
						'date' => array(
							$value['fadded'] => $value['counter']
						),
					);
				}else{
					if( !isset( $_tmp[$value['pb_id'].'/'.$value['pb_page']]['countries'][ $_countries[$value['country_id']] ] ) ){
						$_tmp[$value['pb_id'].'/'.$value['pb_page']]['countries'][ $_countries[$value['country_id']] ]=$value['counter'];
					}else{
						$_tmp[$value['pb_id'].'/'.$value['pb_page']]['countries'][ $_countries[$value['country_id']] ]+=$value['counter'];
					}
					if( !isset( $_tmp[$value['pb_id'].'/'.$value['pb_page']]['date'][ $value['fadded'] ] ) ){
						$_tmp[$value['pb_id'].'/'.$value['pb_page']]['date'][ $value['fadded'] ]=$value['counter'];
					}else{
						$_tmp[$value['pb_id'].'/'.$value['pb_page']]['date'][ $value['fadded'] ]+=$value['counter'];
					}
				}
			}
			$_arrFirstData=$_tmp;
			// получаем второй список данных по всем LPB_ID сразу
			if($_table == 'pb_click_' . Core_Users::$info['id']) $_table2='pb_view_' . Core_Users::$info['id']; else $_table2 = 'pb_click_' . Core_Users::$info['id'];
			$_arrSecondData=Core_Sql::getAssoc('SELECT c.pb_id, c.pb_page, c.country_id, COUNT(*) as counter, '
				.(( $_dDate<=10*24*60*60 )? 'FROM_UNIXTIME(c.added, "%Y-%m-%d %h:00") as fadded ' : 'FROM_UNIXTIME(c.added, "%Y-%m-%d") as fadded ' )
				.'FROM '.$_table2.' c 
				WHERE c.pb_id IN('.Core_Sql::fixInjection( $_arrSqueezeIds ).')'.$_strOnlyPage . $_arrZeroDataFrom.$_arrZeroDataTo.'
				GROUP BY fadded, c.pb_page, c.pb_id, c.country_id');
			$_tmp=array();
			foreach ($_arrSecondData as $value){
				if( !isset($_countries[$value['country_id']]) ){
					$_countries[0]='Undefined';
				}
				if( !isset($_tmp[$value['pb_id'].'/'.$value['pb_page']]) ){
					$_tmp[$value['pb_id'].'/'.$value['pb_page']]=array(
						'countries' => array( 
							$_countries[$value['country_id']] => $value['counter']
						),
						'date' => array(
							$value['fadded'] => $value['counter']
						),
					);
				}else{
					if( !isset( $_tmp[$value['pb_id'].'/'.$value['pb_page']]['countries'][ $_countries[$value['country_id']] ] ) ){
						$_tmp[$value['pb_id'].'/'.$value['pb_page']]['countries'][ $_countries[$value['country_id']] ]=$value['counter'];
					}else{
						$_tmp[$value['pb_id'].'/'.$value['pb_page']]['countries'][ $_countries[$value['country_id']] ]+=$value['counter'];
					}
					if( !isset( $_tmp[$value['pb_id'].'/'.$value['pb_page']]['date'][ $value['fadded'] ] ) ){
						$_tmp[$value['pb_id'].'/'.$value['pb_page']]['date'][ $value['fadded'] ]=$value['counter'];
					}else{
						$_tmp[$value['pb_id'].'/'.$value['pb_page']]['date'][ $value['fadded'] ]+=$value['counter'];
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
			$_checkTable=Core_Sql::getAssoc('show tables like "pb_utm"');
			$_arrUtmData=array();
			if( !empty( $_checkTable ) ){
				$_arrUtmData=Core_Sql::getAssoc('SELECT c.pb_id, c.utm_source, c.utm_medium, c.utm_term, c.utm_content, c.utm_campaign, SUM(c.view) as visitors, SUM(c.click) as clicks FROM pb_utm c WHERE c.pb_id IN('.Core_Sql::fixInjection( $_oldSqueezeIds ).') '.$_strOnlyPage . $_arrZeroDataFrom.$_arrZeroDataTo.' GROUP BY c.pb_id, c.utm_source, c.utm_medium, c.utm_term, c.utm_content, c.utm_campaign ORDER BY visitors DESC');
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
				($_table == 'pb_view_'.Core_Users::$info['id'] ? 'visitors' : 'clicks') => $value,
				($_table2 == 'pb_click_'.Core_Users::$info['id'] ? 'clicks' : 'visitors') => $_intSecondDataSumm,
				($_table == 'pb_view_'.Core_Users::$info['id'] ? 'arr_visitors' : 'arr_clicks') => $_arrFirstData[$key],
				($_table2 == 'pb_click_'.Core_Users::$info['id'] ? 'arr_clicks' : 'arr_visitors') => $_arrSecondData[$key],
			);
			$mixRes[$key]['rate']=sprintf("%01.2f", $mixRes[$key]['clicks']/$mixRes[$key]['visitors']*100);
			$mixRes[$key]['key']=$key;
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
			$_arrSqueezeIds[]=substr($key, 0, strpos($key, '/'));
		}
		$sites->withIds( $_arrSqueezeIds )->withoutSort()->onlyOwner()->getList($_arrZeroData);
		foreach( $_arrZeroData as &$_item ){
			$_siteData=$sites->getSite($_item['id']);
			foreach( array_keys( $_siteData['pages'] ) as $_pageName ){
				if(isset($mixRes[$_item['id'].'/'.$_pageName])){
					$mixRes[$_item['id'].'/'.$_pageName]=array_merge( $_item, $mixRes[$_item['id'].'/'.$_pageName], array('page_name'=>$_pageName, 'parent_id'=>$_item['id']) );
				}
			}
		}
		if($_order == 'cv.crt--up' || $_order == 'cv.crt--dn'){
			$mixRes=array_slice($mixRes, $_limit_a, $_limit_b);
		}
		$mixRes = array_values($mixRes);
		$_newMix=array();
		if( count( $mixRes ) == 1 ){
			return $this;
		}
		foreach( $mixRes as $_data ){
			if( $_data['page_name']=='index' ){
				if( isset( $_newMix[$_data['id']]['child_pages'] ) ){
					$_newMix[$_data['id']]=array_merge( $_data, $_newMix[$_data['id']] );
					$_newMix[$_data['id']]['child_pages'][]=$_data;
				}else{
					$_newMix[$_data['id']]=$_data;
					$_newMix[$_data['id']]['child_pages']=array( $_data );
				}
				unset( $_newMix[$_data['id']]['visitors'], $_newMix[$_data['id']]['clicks'], $_newMix[$_data['id']]['arr_visitors'], $_newMix[$_data['id']]['arr_clicks'] );
			}else{
				$_newMix[$_data['parent_id']]['child_pages'][]=$_data;
			}
		}
		foreach( $_newMix as &$_data2 ){
			$_data2['visitors']=$_data2['clicks']=0; $_data2['arr_visitors']=$_data2['arr_clicks']=array('countries'=>array(), 'date'=>array());
			foreach( $_data2['child_pages'] as $_child ){
				foreach( $_child['arr_visitors']['countries'] as $_ccountry=>$_cvisitors ){
					if( !isset( $_data2['arr_visitors']['countries'][$_ccountry] ) ){
						$_data2['arr_visitors']['countries'][$_ccountry]=$_cvisitors;
					}else{
						$_data2['arr_visitors']['countries'][$_ccountry]=$_data2['arr_visitors']['countries'][$_ccountry]+$_cvisitors;
					}
				}
				foreach( $_child['arr_visitors']['date'] as $_ccountry=>$_cvisitors ){
					if( !isset( $_data2['arr_visitors']['date'][$_ccountry] ) ){
						$_data2['arr_visitors']['date'][$_ccountry]=$_cvisitors;
					}else{
						$_data2['arr_visitors']['date'][$_ccountry]=$_data2['arr_visitors']['date'][$_ccountry]+$_cvisitors;
					}
				}
				foreach( $_child['arr_clicks']['countries'] as $_cdate=>$_cvisitors ){
					if( !isset( $_data2['arr_clicks']['countries'][$_cdate] ) ){
						$_data2['arr_clicks']['countries'][$_cdate]=$_cvisitors;
					}else{
						$_data2['arr_clicks']['countries'][$_cdate]=$_data2['arr_clicks']['countries'][$_cdate]+$_cvisitors;
					}
				}
				foreach( $_child['arr_clicks']['date'] as $_cdate=>$_cvisitors ){
					if( !isset( $_data2['arr_clicks']['date'][$_cdate] ) ){
						$_data2['arr_clicks']['date'][$_cdate]=$_cvisitors;
					}else{
						$_data2['arr_clicks']['date'][$_cdate]=$_data2['arr_clicks']['date'][$_cdate]+$_cvisitors;
					}
				}
				if( !isset( $_data2['visitors'] ) ){
					$_data2['visitors']=$_child['visitors'];
				}else{
					$_data2['visitors']+=$_child['visitors'];
				}
				if( !isset( $_data2['clicks'] ) ){
					$_data2['clicks']=$_child['clicks'];
				}else{
					$_data2['clicks']+=$_child['clicks'];
				}
			}
		}
		$mixRes=$_newMix;
		return $this;
	}
}