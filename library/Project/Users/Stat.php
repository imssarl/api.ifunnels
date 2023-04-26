<?php


class Project_Users_Stat {

	private static  $_intNCSB=0;
	private static  $_intNVSB=0;
	private static  $_intBF=0;
	private static $_impressions=0;
	private static $_clicks=0;

	public static function updateLpbFull(){
		$_arrUpdate=array();
		$_arrList=Core_Sql::getField( 'SELECT d.id FROM u_users d JOIN squeeze_campaigns c ON d.id=c.user_id WHERE lpb_full_edited<='.time().' GROUP BY d.id LIMIT 0,10' );
		try{
			Core_Sql::setConnectToServer( 'lpb.tracker' );
			foreach( $_arrList as $_userId ){
				$_arrSqueezeSubscribers=$_arrSqueezeClicks=$_arrSqueezeView=0;
				$_arrTables=Core_Sql::getField( 'SELECT table_name FROM information_schema.tables WHERE table_name IN ( "lpb_click_'.$_userId.'", "s8rs_'.$_userId.'", "s8rs_events_'.$_userId.'", "lpb_view_'.$_userId.'" )' );
				if( !empty( $_arrTables ) && count( $_arrTables )==4 ){
					$_arrSqueezeClicks=Core_Sql::getCell( 'SELECT COUNT(*) rec_count FROM lpb_click_'.$_userId );
					$_arrSqueezeView=Core_Sql::getCell( 'SELECT COUNT(*) rec_count FROM lpb_view_'.$_userId );
					$_arrSqueezeSubscribers=Core_Sql::getCell( 'SELECT COUNT(*) rec_count FROM s8rs_'.$_userId.' d JOIN s8rs_events_'.$_userId.' e ON d.id=e.sub_id WHERE e.event_type='.Project_Subscribers_Events::LEAD_FORM );
				}
				$_arrUpdate[]='UPDATE u_users SET lpb_full_clicks='.$_arrSqueezeClicks.', lpb_full_views='.$_arrSqueezeView.', lpb_full_s8rs='.$_arrSqueezeSubscribers.', lpb_full_edited='.(time()+4*60*60).' WHERE id='.$_userId; // обновлять раз в 4 часа
			}
			Core_Sql::renewalConnectFromCashe();
		}catch(Exception $e){
			Core_Sql::renewalConnectFromCashe();
		}
		foreach( $_arrUpdate as $_update ){
			Core_Sql::setExec( $_update ); 
		}
	}
	
	public static function sitesCount(){
		self::$_intNCSB=Core_Sql::getCell('SELECT count(*) FROM es_ncsb WHERE user_id='.Core_Users::$info['id']);
		self::$_intNVSB=Core_Sql::getCell('SELECT count(*) FROM es_nvsb WHERE user_id='.Core_Users::$info['id']);
		self::$_intBF=Core_Sql::getCell('SELECT count(*) FROM bf_blogs WHERE user_id='.Core_Users::$info['id']);
		return self::$_intBF+self::$_intNCSB+self::$_intNVSB;
	}

	public static function getCountNCSB(){
		if( self::$_intNCSB>0){
			return self::$_intNCSB;
		}
		self::$_intNCSB=Core_Sql::getCell('SELECT count(*) FROM es_ncsb WHERE user_id='.Core_Users::$info['id']);
		return self::$_intNCSB;
	}

	public static function getCountNVSB(){
		if( self::$_intNVSB>0){
			return self::$_intNVSB;
		}
		self::$_intNVSB=Core_Sql::getCell('SELECT count(*) FROM es_nvsb WHERE user_id='.Core_Users::$info['id']);
		return self::$_intNVSB;
	}

	public static function getCountBF(){
		if( self::$_intBF>0){
			return self::$_intBF;
		}
		self::$_intBF=Core_Sql::getCell('SELECT count(*) FROM bf_blogs WHERE user_id='.Core_Users::$info['id']);
		return self::$_intBF;
	}

	private static $_LPBids=array();
	private static $_LPBvisitors=array();
	private static $_LPBclick=0;
	private static $_LPBview=0;
	
	public static function getCountLPB( $_action='count' ){
		if( empty( self::$_LPBids ) && isset( Core_Users::$info['id'] ) && !empty( Core_Users::$info['id'] ) ){
			self::$_LPBids=Core_Sql::getField( 'SELECT id FROM squeeze_campaigns WHERE user_id='.Core_Users::$info['id'] );
		}elseif( !isset( Core_Users::$info['id'] ) || empty( Core_Users::$info['id'] ) ){
			return 0;
		}
		switch( $_action ){
			case 'count':
				return count( self::$_LPBids ); 
			break;
			case 'view':
			case 'clicks':
				if( !empty( self::$_LPBids ) && self::$_LPBview==0 ){
					$_lpb=new Project_Squeeze();
					self::$_LPBvisitors=$_lpb->getVisitors( self::$_LPBids );
					foreach( self::$_LPBvisitors as $_v ){
						self::$_LPBclick+=$_v['conversions'];
						self::$_LPBview+=$_v['subscribers'];
					}
				}
				if( $_action == 'view' ){
					return self::$_LPBview;
				}
				if( $_action == 'clicks' ){
					return self::$_LPBclick;
				}
			break;
			case 'limits':
				return Project_Squeeze::getRestrictions( Core_Users::$info['id'] );
			break;
		}
	}

	public static function getCountHosting(){
		return Core_Sql::getCell('SELECT count(*) FROM site_placement WHERE flg_type IN ('. Project_Placement::LOCAL_HOSTING.','.Project_Placement::LOCAL_HOSTING_DOMEN.') AND user_id='.Core_Users::$info['id']);
	}

	public static function getCountPurchasedDomain(){
		return Core_Sql::getCell('SELECT count(*) FROM site_placement WHERE flg_type IN ('. Project_Placement::LOCAL_HOSTING_DOMEN.') AND user_id='.Core_Users::$info['id']);
	}

	public static function getCountRemoteDomains(){
		return Core_Sql::getCell('SELECT count(*) FROM site_placement WHERE flg_type='. Project_Placement::REMOTE_HOSTING.' AND user_id='.Core_Users::$info['id']);
	}

	public static function campaignCount(){
		return Core_Sql::getCell('SELECT count(*) FROM co_snippets WHERE user_id='.Core_Users::$info['id']);
	}

	public static function campaignCountParts(){
		$_snippets=new Project_Widget_Adapter_Copt_Snippets();
		$_snippets->withParts()->getList( $arrList );
		$_count=0;
		foreach( $arrList as $_item ){
			$_count+=$_item['parts'];
			self::$_impressions+=$_item['views'];
			self::$_clicks+=$_item['clicks'];
		}
		return $_count;
	}

	public static function campaignImpressions(){
		if(!empty(self::$_impressions)){
			return self::$_impressions;
		}
		$_snippets=new Project_Widget_Adapter_Copt_Snippets();
		$_snippets->withParts()->getList( $arrList );
		foreach( $arrList as $_item ){
			self::$_impressions+=$_item['views'];
		}
		return self::$_impressions;
	}

	public static function campaignClicks(){
		if(!empty(self::$_clicks)){
			return self::$_clicks;
		}
		$_snippets=new Project_Widget_Adapter_Copt_Snippets();
		$_snippets->withParts()->getList( $arrList );
		foreach( $arrList as $_item ){
			self::$_clicks+=$_item['clicks'];
		}
		return self::$_clicks;
	}

	public static function pubCount(){
		$_ncsb=Core_Sql::getCell('SELECT COUNT(*) FROM es_content WHERE flg_from=2 AND flg_type='. Project_Sites::NCSB .' AND site_id IN (SELECT id FROM es_ncsb WHERE user_id='.Core_Users::$info['id'].')');
		$_nvsb=Core_Sql::getCell('SELECT COUNT(*) FROM es_content WHERE flg_from=2 AND flg_type='. Project_Sites::NVSB .'  AND site_id IN (SELECT id FROM es_nvsb WHERE user_id='.Core_Users::$info['id'].')');
		$_bf=Core_Sql::getCell('SELECT COUNT(*) FROM es_content WHERE flg_from=2 AND flg_type='. Project_Sites::BF .'  AND site_id IN (SELECT id FROM bf_blogs WHERE user_id='.Core_Users::$info['id'].')');
		return $_ncsb+$_nvsb+$_bf;
	}

	public static function pub2dayCount(){
		$_res1=Core_Sql::getCell("SELECT count(*) FROM pub_schedule WHERE flg_status=0 AND DATE_FORMAT(FROM_UNIXTIME(start),'%Y-%m-%d')='".date('Y-m-d',time())."' AND project_id IN (SELECT project_id FROM pub_project WHERE flg_status IN (0,1) AND user_id=".Core_Users::$info['id'].")");
		$_res2=Core_Sql::getCell("SELECT SUM(post_num) FROM pub_project WHERE flg_status IN (0,1) AND flg_mode=0 AND DATE_FORMAT(FROM_UNIXTIME(start),'%Y-%m-%d')='".date('Y-m-d',time())."' AND user_id=".Core_Users::$info['id'] );
		return $_res1+$_res2;
	}
}
?>