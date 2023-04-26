<?php
/**
 * WorkHorse Framework
 *
 * @category Project
 * @package Project_Widget_Adapter_Hiam
 * @copyright Copyright (c) 2009-2011, web2innovation
 * @author Pavel Livinskij <pavel.livinskij@gmail.com>
 * @date 12.07.2011
 * @version 1.0
 */


/**
 * Управление частями сниппета 
 *
 * @category Project
 * @package Project_Widget_Adapter_Hiam
 * @copyright Copyright (c) 2009-2011, web2innovation
 */
class Project_Widget_Adapter_Squeeze_Campaign extends Core_Data_Storage {

	protected $_table='squeeze_campaign';
	protected $_fields=array('id','user_id','clicks','views','effectiveness','flg_poss','flg_posc','flg_posf','flg_action','flg_corner_position',
		'flg_fix_position','flg_floating_eff','flg_slide_pos','flg_height','flg_width','flg_border_style','flg_border_width','flg_slide_content_type',
		'flg_fix_content_type','flg_reset','flg_display','flg_sound','flg_window','flg_lightbox','slide_pos','height','width','start_date','end_date','file_background',
		'file_corner','file_sound','title','background_color','border_color','delay','close_text','close_color','url','content_slide', 'content_slide_parsed','content_fix','content_fix_parsed','edited','added');
	private $_tableUrl='hi_trackurls';
	private $_tableRef='hi_referral';
	private $_onlyEffectiv=false;
	private $_onlyClicks=false;

	/**
	 * Возвращает код для вставки в удаленные сайты. Данный код будет дергать сервис CNM
	 * @static
	 * @param  $intId
	 * @return array
	 */
	public static function getCode( $arrId ){
		if( empty($arrId) ){
			return false;
		}
		if( !is_array($arrId) ){
			$arrId=array($arrId);
		}
		Project_Widget_Mutator::encodeArray( $arrId );
		$_arr['get']='<script type="text/javascript" src="https://'.Zend_Registry::get( 'config' )->engine->project_domain.'/services/widgets.php?name=Squeeze&action=get&id='.join('-',$arrId).'"></script>';
		$_arr['effect']='<img src="https://'.Zend_Registry::get( 'config' )->engine->project_domain.'/services/widgets.php?name=Squeeze&action=effective&id='.join('-',$arrId).'" height="1" width="1" />';
		return $_arr;
	}

	protected function beforeSet(){
		if ( !$this->_data->setFilter( array( 'trim' ) )->setChecker( array(
			'title' => empty( $this->_data->filtered['title'] ),
			'start_date' => empty( $this->_data->filtered['start_date'] ),
			'end_date' => empty( $this->_data->filtered['end_date'] ),
		))->check() ){
			$this->_data->getErrors( $this->_errors );
			return false;
		}
		if( $this->_data->filtered['flg_reset'] ){
			$this->_data->setElement('content_slide',preg_replace('@<style.*?>.*?</style>@si','',$this->_data->filtered['content_slide'] ));
			$this->_data->setElement('content_fix',preg_replace('@<style.*?>.*?</style>@si','',$this->_data->filtered['content_fix'] ));
		}
		if( !$this->_data->filtered['flg_height'] ){
			$this->_data->setElement('height',0);
		}
		if( !$this->_data->filtered['flg_width'] ){
			$this->_data->setElement('width',0);
		}
		if( !$this->_data->filtered['flg_slide_pos'] ){
			$this->_data->setElement('slide_pos',0);
		}
		if( !$this->_data->filtered['delay'] ){
			$this->_data->setElement('delay',0);
		}
		$this->_data->setElements(array(
			'slide_pos'=>intval($this->_data->filtered['slide_pos']),
			'height'=>intval($this->_data->filtered['height']),
			'width'=>intval($this->_data->filtered['width']),
			'file_background'=>intval($this->_data->filtered['file_background']),
			'file_corner'=>intval($this->_data->filtered['file_corner']),
			'file_sound'=>intval($this->_data->filtered['file_sound']),
			'content_slide_parsed'=>$this->_data->filtered['content_slide'],
			'content_fix_parsed'=>$this->_data->filtered['content_fix']
		));
//		p($this->_data->filtered);
		return true;
	}

	protected function afterSet(){
		Core_Sql::setExec('DELETE FROM '.$this->_tableUrl.' WHERE campaign_id='.Core_Sql::fixInjection($this->_data->filtered['id']). ' AND id NOT IN (SELECT trackurl_id FROM '.$this->_tableRef.' as c WHERE campaign_id= '.Core_Sql::fixInjection($this->_data->filtered['id']).' )');
		if( !empty($this->_data->filtered['content_slide_parsed']) ){
			$this->parser('content_slide_parsed');
		}
		if( !empty($this->_data->filtered['content_fix_parsed']) ){
			$this->parser('content_fix_parsed');
		}
		return true;
	}
	
	public function setViews( $_intId ){
		if( empty($_intId) ){
			return false;
		}
		return Core_Sql::setExec('UPDATE '.$this->_table.' SET views=views+1 WHERE id='.$_intId );
	}

	public function setEffectiveness(){
		$this->_data->setFilter();
		if( empty( $this->_data->filtered ) ){
			return false;
		}
		$this->_data->filtered['ip_address']= new Zend_Db_Expr('INET_ATON('.Core_Sql::fixInjection($this->_data->filtered['ip_address']).')');
		Core_Sql::setExec('UPDATE '.$this->_table.' SET effectiveness=effectiveness+1 WHERE id='.$this->_data->filtered['campaign_id'] );
		return Core_Sql::setInsert( $this->_tableRef, $this->_data->filtered );
	}

	public function setClick(){
		$this->_data->setFilter();
		if( empty( $this->_data->filtered ) ){
			return false;
		}
		$this->_data->filtered['ip_address']= new Zend_Db_Expr('INET_ATON('.Core_Sql::fixInjection($this->_data->filtered['ip_address']).')');
		Core_Sql::setExec('UPDATE '.$this->_table.' SET clicks=clicks+1 WHERE id='.$this->_data->filtered['campaign_id'] );
		return Core_Sql::setInsert( $this->_tableRef, $this->_data->filtered );
	}

	public function onlyEffectiv(){
		$this->_onlyEffectiv=true;
		return $this;
	}

	public function onlyClicks(){
		$this->_onlyClicks=true;
		return $this;
	}
	
	public function getStatistic( &$arrRes ){
		if( empty( $this->_withIds ) ){
			return false;
		}
		$_crawler=new Core_Sql_Qcrawler();
		$_crawler->set_from( $this->_tableRef .' d LEFT JOIN '. $this->_tableUrl .' as t ON d.trackurl_id=t.id' );
		$_crawler->set_select('d.*,t.url,t.txt,INET_NTOA(d.ip_address) as ip_address');
		$_crawler->set_where('d.campaign_id IN('. Core_Sql::fixInjection( $this->_withIds ) .')');
		if( $this->_onlyClicks ){
			$_crawler->set_where('d.flg_type=1');
		}
		if( $this->_onlyEffectiv ){
			$_crawler->set_where('d.flg_type=2');
		}
		$_crawler->set_order_sort( $this->_withOrder );
		$_crawler->get_result_full( $_strSql );
		$arrRes=Core_Sql::getAssoc( $_strSql );
		$this->init();
		return !empty($arrRes);
	}

	public function getTrackUrl( $_campaginId, $_trackId ){
		return Core_Sql::getRecord('SELECT * FROM '.$this->_tableUrl.' WHERE campaign_id='.Core_Sql::fixInjection( $_campaginId ).' AND id='.Core_Sql::fixInjection( $_trackId ) );
	}

	public function resetView( $_intId ){
		if( empty($_intId) ){
			return false;
		}
		return Core_Sql::setExec('UPDATE '.$this->_table.' SET view=0 WHERE id='.$_intId );
	}

	public function changeFields( &$arrRes ){
		$arrRes['clicks']=0;
		$arrRes['views']=0;
		$arrRes['effectiveness']=0;
		$arrRes['title']=$arrRes['title'].'_dup';
	}

	public function setFilter( $_arrFilter=array() ){
		if( empty($_arrFilter) ){
			return $this;
		}
		$this->_filter=$_arrFilter;
		return $this;
	}

	public function getFilter( &$arrRes ){
		parent::getFilter( $arrRes );
		$arrRes+=$this->_filter;
		return $this;
	}

	public function del(){
		Core_Sql::setExec('DELETE l,r,u FROM hi_trackurls as u LEFT JOIN hi_campaigns2split as l ON u.campaign_id=l.campaign_id LEFT JOIN hi_referral as r ON u.campaign_id=r.campaign_id WHERE'.
		' u.campaign_id IN('.Core_Sql::fixInjection( $this->_withIds ).')');
		return parent::del();
	}
	private $_filter=array();

	private function parser( $_field ){
		if( !empty($this->_data->filtered['url']) ){
			$this->_data->setElement($_field, '<a target="'.(($this->_data->filtered['flg_window']==0)?'_blank':'_self').'" href="'.$this->_data->filtered['url'].'">'.preg_replace('@<a.*?>(.*?)</a>@si',' $1 ',$this->_data->filtered[$_field]).'</a>');
		}
		preg_match_all('@(?P<link><a.*?href="(?P<url>.*?)".*?>(?P<text>.*?)</a>)@si',$this->_data->filtered[$_field],$_matches);
		if(empty($_matches['link'])){
			return;
		}
		$_replace=array_keys($_matches['link']);
		$_strId=Project_Widget_Mutator::encode($this->_data->filtered['id']);
		foreach($_replace as &$v ){
			$_trackurlId=Core_Sql::setInsert( $this->_tableUrl, array(
				'campaign_id'=>$this->_data->filtered['id'],
				'url'=>$_matches['url'][$v],
				'txt'=>$_matches['text'][$v]
			));
			$_trackurlId=Project_Widget_Mutator::encode( $_trackurlId );
			$v=preg_replace('@href="(.*?)"@si','href="https://'.Zend_Registry::get( 'config' )->engine->project_domain.'/services/widgets.php?name=Squeeze&action=set&id='.$_strId.'-'.$_trackurlId.'"',$_matches['link'][$v]);
		}
		$this->_data->setElement( $_field,str_replace( $_matches['link'], $_replace, $this->_data->filtered[$_field] ));
		Core_Sql::setInsertUpdate( $this->_table, $this->_data->setMask( $this->_fields )->getValid() );
	}
	
	protected function init(){
		$this->_onlyEffectiv=false;
		$this->_onlyClicks=false;
		$this->_withRights=false;
		parent::init();
	}

	public function onlyStarted(){
		$this->_onlyStarted=true;
		return $this;
	}

	private $_withRights=false;

	public function withRights( $_arr ){
		if(!empty($_arr)){
			$this->_withRights=$_arr;
		}
		return $this;
	}

	protected function assemblyQuery() {
		parent::assemblyQuery();
		if ( !$this->_onlyIds && !$this->_toSelect ) {
			$this->_crawler->set_select( 'ROUND((d.clicks/d.views*100)) as ctr' );
		}

		if( $this->_onlyStarted ){
			$_now=time();
			$this->_crawler->set_where('d.start_date < '.$_now .' AND d.end_date >'.$_now);
		}
		if(!empty($this->_withRights)){
			$this->_crawler->set_where('d.user_id IN ('. Core_Acs::haveRightAccess( $this->_withRights ) .')');
		}
		// filters
		if( !empty($this->_filter) ){
			if( !empty($this->_filter['flg_posc']) ){
				$this->_crawler->set_where( 'd.flg_posc=1' );
			}
			if( !empty($this->_filter['flg_posf']) ){
				$this->_crawler->set_where( 'd.flg_posf=1' );
			}
			if( !empty($this->_filter['flg_poss']) ){
				$this->_crawler->set_where( 'd.flg_poss=1' );
			}
			if( !empty($this->_filter['filter']) ){
				$_now=time();
				if( $this->_filter['filter']==1 ){
					$this->_crawler->set_where( 'd.start_date < '.$_now .' AND d.end_date >'.$_now );
				}
				if( $this->_filter['filter']==2 ){
					$this->_crawler->set_where( 'd.end_date < '.$_now );
				}
			}
		}
	}
}
?>