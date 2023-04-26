<?php
class Project_Updater_Options extends Core_Updater_Abstract {

	public function update( Core_Updater $obj ) {
		$obj->logger->info( 'start Project_Updater_Options' );
		$this->run();
		$obj->logger->info( 'end Project_Updater_Options' );
	}
	private $table='es_opt_spots';
	
	private function run(){
		$this->clear();
		foreach ( Project_Sites::$code as $key=>$siteType ){
			$arr=Core_Sql::getAssoc("SELECT * FROM hct_spots WHERE site_type='{$siteType}' AND site_id IN (SELECT id FROM ".Project_Sites::$tables[$key].")");
			foreach ( $arr as $item ){
				$data=array(
					'user_id'=>$item['user_id'],
					'site_id'=>$item['site_id'],
					'flg_default'=>(($item['spot_type']=='D')? 0 : 1 ),
					'flg_title'=>$item['spot_video_title'],
					'spot_name'=>$item['spot_name'],
					'flg_type'=>$key
				);
				$item['spot_position']=unserialize($item['spot_position']);
				if ( !empty($item['spot_saved_selections']) ){
					$data['flg_content']=Project_Options::ARTICLE;
					$data['type_order']=(empty($item['spot_position']['position_article'])?1:$item['spot_position']['position_article']);
					$_arr=Core_Sql::getAssoc('SELECT * FROM hct_spots_link WHERE link_spot_id='.$item['spot_id'].' AND link_spot_type=\'savedselections\' GROUP BY link_spot_id');
					$_spotId=Core_Sql::setInsert($this->table,$data);
					if (!empty($_arr))
					$this->setData2Spot($_spotId,$spotData);
				}
				if ( !empty($item['spot_snippets']) ){
					$data['flg_content']=Project_Options::SNIPPET;
					$data['type_order']=(empty($item['spot_position']['position_snippets'])?2:$item['spot_position']['position_snippets']);
					$_arr=Core_Sql::getAssoc('SELECT * FROM hct_spots_link WHERE link_spot_id='.$item['spot_id'].' AND link_spot_type=\'snippets\' GROUP BY link_spot_id');
					$_spotId=Core_Sql::setInsert($this->table,$data);
					if (!empty($_arr))
					$this->setData2Spot($_spotId,$_arr);
				}
				if ( !empty($item['spot_video']) ){
					$data['flg_content']=Project_Options::VIDEO;
					$data['type_order']=(empty($item['spot_position']['position_video'])?3:$item['spot_position']['position_video']);
					$spotData=Core_Sql::getAssoc('SELECT * FROM hct_spots_link WHERE link_spot_id='.$item['spot_id'].' AND link_spot_type=\'video\' GROUP BY link_spot_id');
					$_spotId=Core_Sql::setInsert($this->table,$data);
					if (!empty($_arr))
					$this->setData2Spot($_spotId,$_arr);
				}
				if ( !empty($item['spot_customer_code']) ){
					$data['flg_content']=Project_Options::CUSTOMER;
					$data['type_order']=(empty($item['spot_position']['position_customer'])?4:$item['spot_position']['position_customer']);
					$_spotId=Core_Sql::setInsert($this->table,$data);
					$this->setData2Spot($_spotId,array(array('customer_code'=>$item['spot_customer_code'])));
				}
				
			}
		}
	}

	private function clear(){
		Core_Sql::setExec('TRUNCATE TABLE es_opt_data2spot');
		Core_Sql::setExec('TRUNCATE TABLE es_opt_spots');
	}
	
	private function setData2Spot( $spotId, $data ){
		foreach ( $data as $v ){
			
			try {
				Core_Sql::setInsert('es_opt_data2spot',array(
				'source_id' => (!empty($v['link_data_id']))?$v['link_data_id']:0,
				'spot_id'   => $spotId,
				'customer_code'=> $v['customer_code']
				));
			} catch(Exception $e){
				p($data);
			}
		}
	}
}
?>