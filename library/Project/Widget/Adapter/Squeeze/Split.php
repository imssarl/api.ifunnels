<?php
/**
 * WorkHorse Framework
 *
 * @category Project
 * @package Project_Widget_Hiam
 * @copyright Copyright (c) 2011, web2innovation
 * @author Slepov Viacheslav <shadowdwarf@mail.ru>
 * @date 17.08.2011
 * @version 2.0
 */


/**
 * Управление split test
 *
 * @category Project
 * @package Project_Widget_Hiam
 * @copyright Copyright (c) 2009-2011, web2innovation
 */
class Project_Widget_Adapter_Squeeze_Split extends Core_Data_Storage {

	protected $_table='squeeze_split'; // сплит тест компаний
	protected $_fields=array('id','user_id','flg_closed','flg_duration','duration','title','url','added','edited');
	private $_tableLink='squeeze_campaigns2split';
	private $_winnerId=false;
	private $_link=null;

	public function __construct() {
		$this->_link=new Project_Widget_Adapter_Squeeze_Split_Link();
	}

	/*
	 * До сохранения теста
	 *
	 * @return bool
	 */
	public function beforeSet(){
		if ( !$this->_data->setFilter( array( 'trim', 'clear' ) )->setChecker( array(
			'title'=>empty( $this->_data->filtered['title'] ),
			'campaigns'=>count( $this->_data->filtered['arrCom'] )<2
		))->check() ) {
			$this->_data->getErrors( $this->_errors );
			return false;
		}
		return true;
	}

	/*
	 * После сохранения теста
	 *
	 * @return bool
	 */
	protected function afterSet() {
		return $this->_link->onlyOwner()->setLink( $this->_data->filtered['id'], $this->_data->filtered['arrCom'] ); // вставка списка компаний в этом тесте
	}

	/**
	*	удалить запись split test и связаные с ним компании
	*
	* @param 
	*		$_arr - ids split test
	*	return bool
	*/
	public function del( $_arr=array() ){
		if ( $this->_link->onlyOwner()->withSplitIds($_arr)->delLink() ){
			$this->withIds($_arr);
			return parent::del();
		}
		return false;
	}

	/**
	*	дублирование записи split test
	*
	* @param 
	*		$_winnerId - ids split test
	*	return bool
	*/
	public function changeFields( &$arrRes ){
		$_arrIds = array();
		foreach ($arrRes['arrCom'] as $key => $value) {
			$_arrIds[] = $value['id'];
		}
		$arrRes['arrCom'] = $_arrIds;
		unSet($arrRes['edited']);
		unSet($arrRes['added']);
		$arrRes['flg_closed'] = 0; 
		$arrRes['title'] = 'dup_'.$arrRes['title']; 
	}

	public function withWinnerId( $id ) {
		$this->_winnerId=$id;
		return $this;
	}

	protected function init(){
		$this->_winnerId=false;
		parent::init();
	}

	public function getList( &$arrRes ){
		$_onlyOne=$this->_onlyOne;
		parent::getList( $arrRes );
		if ( $_onlyOne ) {
			$this->_link->withSplitIds( $arrRes['id'] )->getList( $arrRes['arrCom'] );
			return $this;
		}
		foreach( $arrRes as &$_item ){
			$this->_link->withSplitIds( $_item['id'] )->getList( $_item['arrCom'] );
		}
		return $this;
	}

	public static function urlPaused(){
		try {
			Core_Sql::setConnectToServer( 'syndication.qjmpz.com' );
			$_url = Core_Sql::getCell("SELECT url FROM squeeze_split WHERE flg_pause=1 AND url <>  ''");
			Core_Sql::renewalConnectFromCashe();
			if(!empty($_url)) {
				return $_url;	
			} 
		} catch(Exception $e) {
			Core_Sql::renewalConnectFromCashe();
		}
	}

	/**
	 * Устанавливаем flg_winner для campaign и flg_close=1 для split test
	 *
	 * @return void
	 */
	public function request(){
		if( !$this->_withIds || !$this->_winnerId ){
			return false;
		}
		try {
			Core_Sql::setConnectToServer( 'syndication.qjmpz.com' );
			Core_Sql::setExec('UPDATE '.$this->_tableLink.' SET flg_winner=0 WHERE split_id='.Core_Sql::fixInjection($this->_withIds) );
			Core_Sql::setExec('UPDATE '.$this->_tableLink.' SET flg_winner=1 WHERE campaign_id='.Core_Sql::fixInjection($this->_winnerId).' AND split_id='.Core_Sql::fixInjection($this->_withIds) );
			Core_Sql::setExec('UPDATE '.$this->_table.' SET flg_closed=1 WHERE id='.Core_Sql::fixInjection($this->_withIds) );		
			Core_Sql::renewalConnectFromCashe();
		} catch(Exception $e) {
			Core_Sql::renewalConnectFromCashe();
		}	
		return true;
	}

	/**
	 * Возвращает код для вставки в удаленные сайты. Данный код будет дергать сервис CNM
	 *
	 * @static
	 * @param  $winnerId
	 * @return array
	 */
	public static function getCode( $_arrId ){
		if( empty($_arrId) ){
			return false;
		}
		if( !is_array($_arrId) ){
			$_arrId=array($_arrId);
		}
		Project_Widget_Mutator::encodeArray( $_arrId );
		//'Zend_Registry::get( 'config' )->engine->project_domain.Core_Module_Router::getCurrentUrl( array('name' => 'site1_squeeze', 'action' => 'splittest_check') )'
		$_arr['get']='http'.( ( empty( $_SERVER['HTTPS'] ) || $_SERVER['HTTPS']=='off' )?'':'s' ).'://'.'fasttrk.net/page/'.'?id='.join('-',$_arrId);
		return $_arr;
	}

	/**
	 * Возвращает id кампании для показа и при необходимости останавливает кампанию
	*
	 * @param  $id - id split test'а из которого выбираем кампанию
	 * @return $id кампании с меньшим просмотром, или победителя теста
	 */
	public function getCampaign( $_splitId ){
		$this->onlyOwner()->getList($_arrSplit);

		foreach ($_arrSplit as $key => $value) {
			if($value['flg_duration'] == 2 && ((int)$value['duration'] <= (int)Core_Sql::getCell('SELECT SUM(shown) FROM '.$this->_tableLink.' WHERE split_id='.Core_Sql::fixInjection($value['id'])))) {
				Core_Sql::setExec('UPDATE '.$this->_table.' SET flg_closed=1 WHERE id='.Core_Sql::fixInjection($value['id']));
				$_idWinner = Core_Sql::getAssoc('SELECT campaign_id, (clicks/shown) as crt FROM '.$this->_tableLink.' WHERE split_id='.Core_Sql::fixInjection($value['id']));
				$_max = $_idWinner[0];
				foreach ($_idWinner as $k => $v) {
					if(floatval($_max['crt']) < floatval($v['crt'])){
						$_max = $_idWinner[$k];
					}
				}
				if(floatval($_max['crt']) > 0) {
					Core_Sql::setExec('UPDATE '.$this->_tableLink.' SET flg_winner=1 WHERE split_id='.Core_Sql::fixInjection($value['id']).' AND campaign_id='.Core_Sql::fixInjection($_max['campaign_id']));
				}
			}
			if($value['flg_duration'] == 1 && ($value['added'] + $value['duration']*60*60*24) <= time()) {
				Core_Sql::setExec('UPDATE '.$this->_table.' SET flg_closed=1 WHERE id='.Core_Sql::fixInjection($value['id']));
				$_idWinner = Core_Sql::getAssoc('SELECT campaign_id, (clicks/shown) as crt FROM '.$this->_tableLink.' WHERE split_id='.Core_Sql::fixInjection($value['id']));
				$_max = $_idWinner[0];
				foreach ($_idWinner as $k => $v) {
					if(floatval($_max['crt']) < floatval($v['crt'])){
						$_max = $_idWinner[$k];
					}
				}
				if(!empty($_max) && floatval($_max['crt']) > 0) {
					Core_Sql::setExec('UPDATE '.$this->_tableLink.' SET flg_winner=1 WHERE split_id='.Core_Sql::fixInjection($value['id']).' AND campaign_id='.Core_Sql::fixInjection($_max['campaign_id']));
				}
			}
		}
	}

	public function setPause($_splitId){
		try {
			Core_Sql::setConnectToServer( 'syndication.qjmpz.com' );
			Core_Sql::setExec('UPDATE ' . $this->_table . ' SET flg_pause=1 WHERE id='.Core_Sql::fixInjection($_splitId));
			Core_Sql::renewalConnectFromCashe();
		} catch(Exception $e) {
			Core_Sql::renewalConnectFromCashe();
		}
		return true;
	}

	public function setResume($_splitId){
		try {
			Core_Sql::setConnectToServer( 'syndication.qjmpz.com' );
			Core_Sql::setExec('UPDATE ' . $this->_table . ' SET flg_pause=0 WHERE id='.Core_Sql::fixInjection($_splitId));
			Core_Sql::renewalConnectFromCashe();
		} catch(Exception $e) {
			Core_Sql::renewalConnectFromCashe();
		}
		return true;
	}
}
?>