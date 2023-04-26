<?php
/**
 * WorkHorse Framework
 *
 * @category Project
 * @package Project_Widget_Adapter_Copt
 * @copyright Copyright (c) 2009-2011, web2innovation
 * @author Pavel Livinskij <pavel.livinskij@gmail.com>
 * @date 12.07.2011
 * @version 1.0
 */


/**
 * Управление частями сниппета
 *
 * @category Project
 * @package Project_Widget_Adapter_Copt
 * @copyright Copyright (c) 2009-2011, web2innovation
 */
class Project_Widget_Adapter_Copt_Parts extends Core_Storage {

	public $tableUrl='co_trackurls';
	public $tableClick='co_click';
	public $table='co_parts';
	public $fields=array('id','snippet_id','clicks','views','clicks_limit','views_limit','last_view','flg_enabled','flg_reset','flg_pause', 'flg_geo_location','content','parsed', 'geo_enabled','added');
	protected $_link=false;
	protected $_counter=0;
	protected $_limit=false;

	public static function install(){
		Core_Sql::setExec('ALTER TABLE  `co_parts` ADD  `flg_geo_location` INT NOT NULL DEFAULT  \'0\' AFTER  `flg_pause`');
		Core_Sql::setExec('ALTER TABLE  `co_parts` ADD  `geo_enabled` TEXT AFTER  `parsed`');
	}

	public function __construct() {}

	public static function getInstance() {}

	/**
	 * Сохранить часть.
	 * @return bool
	 */
	public function set(){
		if ( !$this->_data->setFilter( array( 'trim', 'clear' ) )->setChecker( array(
			'content'=>empty( $this->_data->filtered['content'] ),
			'snippet_id'=>empty( $this->_data->filtered['snippet_id'] )
		) )->check() ) {
			$this->_data->getErrors( $this->_errors );
			return false;
		}
		if ( empty($this->_data->filtered['id']) ){
			$this->_data->setElement( 'added', time() );
		}
		if( $this->_data->filtered['flg_reset'] ){
			$this->_data->setElement('content',preg_replace('@<style.*?>.*?</style>@si','',$this->_data->filtered['content'] ));
		}
		if( $this->_data->filtered['geo_enabled'] ) {
			$this->_data->setElement('geo_enabled', base64_encode( serialize( $this->_data->filtered['geo_enabled'] )));
		}
		$this->_data->setElement( 'parsed',$this->_data->filtered['content']);
		$this->_data->setElement( 'id', Core_Sql::setInsertUpdate( $this->table, $this->_data->setMask( $this->fields )->getValid() ) );
		$this->parser();

		return true;
	}

	public function getList( &$mixRes ) {
		parent::getList($mixRes);
		if( empty($mixRes) ){
			return $this;
		}

		if( array_key_exists( 0, $mixRes ) ) {
			foreach( $mixRes as &$_arrZeroData ) {
				$_oldSettings=$_arrZeroData['geo_enabled'];
				$_arrZeroData['geo_enabled']=unserialize( base64_decode( $_arrZeroData['geo_enabled'] ) );
				if( $_arrZeroData['geo_enabled']===false ){
					$_arrZeroData['geo_enabled']=unserialize( $_arrZeroData['geo_enabled'] );
				}
				if( $_arrZeroData['geo_enabled']===false ){
					$_arrZeroData['geo_enabled']=unserialize( json_decode( str_replace( '\r\n\n', '\r\n', json_encode( $_oldSettings ) ) ) );
				}
				if( $_arrZeroData['geo_enabled']===false ){
					$_arrZeroData['geo_enabled']=$_oldSettings;
				}
			}
		}else{
			$_oldSettings=$mixRes['geo_enabled'];
			$mixRes['geo_enabled']=unserialize( base64_decode( $mixRes['geo_enabled'] ) );
			if( $mixRes['geo_enabled']===false ){
				$mixRes['geo_enabled']=unserialize( $mixRes['geo_enabled'] );
			}
			if( $mixRes['geo_enabled']===false ){
				$mixRes['geo_enabled']=unserialize( json_decode( str_replace( '\r\n\n', '\r\n', json_encode( $_oldSettings ) ) ) );
			}
			if( $mixRes['geo_enabled']===false ){
				$mixRes['geo_enabled']=$_oldSettings;
			}
		}
		if($this->_withIp !== false) {
			foreach ($mixRes as $key => $value) {
				if($value['flg_geo_location'] == 1 && array_search($this->_withIp, $value['geo_enabled']) === false) {
					unSet($mixRes[$key]);
				}
			}
		}
		return $this; 
	}

	public function setViews( $intId ){
		if( empty($intId) ){
			return false;
		}
		$data=Core_Sql::getRecord('SELECT snippet_id, views, views_limit FROM '.$this->table.' WHERE id='.$intId);
		if( $data['views_limit'] != 0 && $data['views'] >= $data['views_limit']-1 ){
			$this->withIds( $intId )->pause(1);
		}
		Core_Sql::setExec('UPDATE '.$this->table.' SET view=0 WHERE id != '.$intId .' AND snippet_id='.$data['snippet_id'] );
		return Core_Sql::setExec('UPDATE '.$this->table.' SET last_view='.time().', views=views+1, view=view+1 WHERE id='.$intId );
	}

	public function setClick(){
		$this->_data->setFilter();
		if( empty( $this->_data->filtered['part_id'] ) ){ // почему тут может не быть ['part_id'] - проверить TODO!!! 09.09.2011
			return false;
		}
		$data=Core_Sql::getRecord('SELECT snippet_id, clicks, clicks_limit FROM '.$this->table.' WHERE id='.$this->_data->filtered['part_id']);
		if( $data['clicks_limit'] != 0 && $data['clicks'] >= $data['clicks_limit']-1 ){
			$this->withIds( $intId )->pause(1);
		}
		$this->_data->filtered['ip_address']=sprintf( "%u", ip2long($this->_data->filtered['ip_address']));
		Core_Sql::setExec('UPDATE '.$this->table.' SET clicks=clicks+1 WHERE id='.$this->_data->filtered['part_id'] );
		return Core_Sql::setInsert( $this->tableClick, $this->_data->filtered );
	}

	public function getStatistic( &$arrRes ){
		if( empty($this->_withIds) ){
			return false;
		}
		$_crawler=new Core_Sql_Qcrawler();
		$_crawler->set_from($this->tableClick .' d LEFT JOIN '.$this->tableUrl.' as t ON d.trackurl_id=t.id');
		$_crawler->set_select('d.*,t.url,t.txt,INET_NTOA(d.ip_address) as ip_address');
		$_crawler->set_where('d.part_id IN('.Core_Sql::fixInjection($this->_withIds).')');
		$_crawler->set_order_sort( $this->_withOrder );
		$_crawler->get_result_full( $_strSql );
		$arrRes=Core_Sql::getAssoc( $_strSql );
		return !empty($arrRes);
	}

	public static function getCountries(){
		$_countries = array();
		try {
			Core_Sql::setConnectToServer( 'syndication.qjmpz.com' );
			$_countries = Core_Sql::getAssoc('SELECT * FROM getip_countries ORDER BY name');
			Core_Sql::renewalConnectFromCashe();
		} catch(Exception $e) {
			Core_Sql::renewalConnectFromCashe();
		}
		return $_countries;
	}


	public function getTrackUrl( $_partId, $_trackId ){
		return Core_Sql::getRecord('SELECT * FROM '.$this->tableUrl.' WHERE part_id='.Core_Sql::fixInjection($_partId).' AND id='.Core_Sql::fixInjection($_trackId) );
	}
	
	public function getOwnerId(){}

	public function del( $_arr=array() ){
		Core_Sql::setExec('DELETE FROM '.$this->tableUrl.' WHERE part_id IN ('.Core_Sql::fixInjection($_arr).')');
		Core_Sql::setExec('DELETE FROM '.$this->tableClick.' WHERE part_id IN ('.Core_Sql::fixInjection($_arr).')');
		parent::del(  $_arr );
	}

	public function resetView( $intId ){
		if( empty($intId) ){
			return false;
		}
		return Core_Sql::setExec('UPDATE '.$this->table.' SET view=0 WHERE id='.$intId );
	}
	
	/**
	 * Поставить/снять с паузы. использовать так: $obj->withIds( array(id) )->pause();
	 * @param int $_status - 0 or 1
	 * @return void
	 */
	public function pause( $_status=0 ){
		if( !$this->_withIds ){
			return false;
		}
		Core_Sql::setExec('UPDATE '.$this->table.' SET flg_pause='.(($_status==1)?1:0).' WHERE id='.Core_Sql::fixInjection($this->_withIds) );
		return true;
	}

	/**
	 * Сбросить данные статистики. использовать так: $obj->withIds( array(id) )->reset();
	 * @return void
	 */
	public function reset(){
		if( !$this->_withIds ){
			return false;
		}
		Core_Sql::setExec('UPDATE '.$this->table.' SET clicks=0, views=0, last_view=0 WHERE id='.Core_Sql::fixInjection($this->_withIds));
		Core_Sql::setExec('DELETE FROM '.$this->tableClick.' WHERE part_id='.$this->_withIds);
		return true;
	}

	// дублирование строк
	public function duplicate( $_intId=0 ) {
		$snippet_id=$this->_onlySnippet;
		$this->_onlySnippet=false;
		if ( !$snippet_id || empty( $_intId ) || !$this->onlyOne()->withIds( $_intId )->getList( $arrRes )->checkEmpty() ) {
			return false;
		}
		unSet( $arrRes['id'] );
		$arrRes['snippet_id']=$snippet_id;
		$arrRes['clicks']=0;
		$arrRes['views']=0;
		$arrRes['last_view']=0;
		return $this->setData( $arrRes )->set();
	}

	public function onlySnippet( $ids ){
		$this->_onlySnippet=$ids;
		return $this;
	}

	public function withoutPause(){
		$this->_withoutPause=true;
		return $this;
	}

	public function withoutIds( $_arrIds ){
		$this->_withoutIds=$_arrIds;
		return $this;
	}

	public function setLimit( $_intLimit ){
		$this->_setLimit=$_intLimit;
		return $this;
	}

	public function onlyParsed(){
		$this->_onlyParsed=true;
		return $this;
	}

	public function withIp($_ip){
		try {
			Core_Sql::setConnectToServer( 'syndication.qjmpz.com' );
			$this->_withIp = Core_Sql::getCell('SELECT country_id FROM getip_countries2ip WHERE ip_start >= ' . ip2long($_ip) . ' AND ' . ip2long($_ip) . ' <= ip_end');
			Core_Sql::renewalConnectFromCashe();
		} catch(Exception $e) {
			Core_Sql::renewalConnectFromCashe();
		}
		return $this;
	}
	
	public function parser(){
		if(empty($this->_data->filtered)){
			$this->_data->setFilter( array( 'trim', 'clear' ) );
		}
		preg_match_all('@(?P<link><a.*?href="(?P<url>.*?)".*?>(?P<text>.*?)</a>)@si',$this->_data->filtered['parsed'],$_matches);
		Core_Sql::setExec('DELETE FROM '.$this->tableUrl.' WHERE part_id='.Core_Sql::fixInjection($this->_data->filtered['id']). ' AND id NOT IN (SELECT trackurl_id FROM '.$this->tableClick.' as c WHERE part_id= '.Core_Sql::fixInjection($this->_data->filtered['id']).' )');
		if(empty($_matches['link'])){
			return;
		}
		$_replace=array_keys($_matches['link']);
		$_strId=Project_Widget_Mutator::encode( $this->_data->filtered['id'] );
		foreach($_replace as &$v ){
			$trackurl_id=Core_Sql::setInsert( $this->tableUrl, array(
				'part_id'=>$this->_data->filtered['id'],
				'url'=>$_matches['url'][$v],
				'txt'=>$_matches['text'][$v]
			));
			$trackurl_id=Project_Widget_Mutator::encode($trackurl_id);
			$v=preg_replace('@href="(.*?)"@si','href="https://'.Zend_Registry::get( 'config' )->engine->project_domain.'/services/widgets.php?name=Copt&action=set&id='.$_strId.'-'.$trackurl_id.'"',$_matches['link'][$v]);
		}
		$this->_data->setElement( 'parsed',str_replace( $_matches['link'], $_replace, $this->_data->filtered['parsed'] ));
		Core_Sql::setInsertUpdate( $this->table, $this->_data->setMask( $this->fields )->getValid() );
	}

	private $_onlySnippet=false;// для одного сниппета;
	private $_withoutPause=false;// части не на паузе;
	private $_withoutIds=false;// все кроме этих;
	private $_setLimit=false;// Лимит выборки частей;
	private $_onlyParsed=false;// только поле parsed;
	private $_withIp = false;

	protected function init(){
		$this->_onlySnippet=false;
		$this->_setLimit=false;
		$this->_withoutPause=false;
		$this->_withoutIds=false;
		$this->_onlyParsed=false;
		//$this->_withIp=false;
		parent::init();
	}

	protected function assemblyQuery() {
		if ( $this->_onlyIds ) {
			$this->_crawler->set_select( 'd.id' );
		} elseif( $this->_onlyParsed ){
			$this->_crawler->set_select( 'd.parsed,d.id' );
		} else {
			$this->_crawler->set_select( 'd.*' );
			$this->_crawler->set_select( '(SELECT COUNT(*) FROM '.$this->tableUrl.' as l WHERE l.part_id = d.id) as count_link' );
			$this->_crawler->set_select( 'ROUND((d.clicks/d.views*100)) as ctr' );
		}
		$this->_crawler->set_from( $this->table.' d' );
		if( $this->_limit ){
			$this->_crawler->set_limit( $this->_counter.','.$this->_limit );
		}
		if ( !empty( $this->_withIds ) ) {
			$this->_crawler->set_where( 'd.id IN ('.Core_Sql::fixInjection( $this->_withIds ).')' );
		}
		if ( !empty( $this->_onlySnippet ) ) {
			$this->_crawler->set_where( 'd.snippet_id IN ('.Core_Sql::fixInjection( $this->_onlySnippet ).')' );
		}
		if( $this->_withoutIds ){
			$this->_crawler->set_where( 'd.id NOT IN ('.Core_Sql::fixInjection( $this->_withoutIds).')' );
		}
		if( $this->_withoutPause ){
			$this->_crawler->set_where( 'd.flg_pause=0' );
		}
		if( $this->_setLimit ){
			$this->_crawler->set_limit( $this->_setLimit );
		}
		if ( !( $this->_onlyOne||$this->_onlyCell||$this->_onlyParsed ) ) {
			$this->_crawler->set_order_sort( $this->_withOrder );
		}
	}

	public function setCounter( $_intCounter ){
		$this->_counter=$_intCounter;
		return $this;
	}

	public function setLimited( $_intLimited ){
		$this->_limit=$_intLimited;
		return $this;
	}
}
?>