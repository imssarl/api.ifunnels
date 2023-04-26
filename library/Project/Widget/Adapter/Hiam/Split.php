<?php


/**
 * Управление split test
 */
class Project_Widget_Adapter_Hiam_Split extends Core_Data_Storage {

	protected $_table='hi_split'; // сплит тест компаний
	protected $_fields=array('id','user_id','flg_closed','flg_duration','duration','title','added','edited');
	private $_tableLink='hi_campaigns2split';
	private $_winnerId=false;
	private $_link=null;

	public function __construct() {
		$this->_link=new Project_Widget_Adapter_Hiam_Split_Link();
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
		$arrRes['title']=$arrRes['title'].'_'.time();
		unSet( $arrRes['flg_closed'] );
	}

	public function withWinnerId( $id ) {
		$this->_winnerId=$id;
		return $this;
	}

	protected function init(){
		$this->_winnerId=false;
		parent::init();
	}

	protected function assemblyQuery() {
		parent::assemblyQuery();
		if ( !empty($this->_withIds) ) {
			$this->_crawler->set_select('
			CASE d.flg_duration
				WHEN 1 THEN
					(SELECT ((added+duration*60*60*24)-UNIX_TIMESTAMP()<=0) FROM '. $this->_table.' WHERE id='.Core_Sql::fixInjection( $this->_withIds ).')
				WHEN 2 THEN
					(SELECT (SUM(shown)/COUNT(split_id)>=duration) FROM '. $this->_table.' b INNER JOIN '.$this->_tableLink.' a ON a.split_id=b.id WHERE b.id='.Core_Sql::fixInjection( $this->_withIds).')
				ELSE
					0
				END
			as status');// статус - split test завершился -1 или нет -0
		}
	}

	public function getList( &$arrRes ){
		$_onlyOne=$this->_onlyOne;
		parent::getList( $arrRes );
		if ( $_onlyOne ) {
			$this->_link->onlyIds()->withSplitIds( $arrRes['id'] )->getList( $arrRes['arrCom'] );
			return $this;
		}
		foreach( $arrRes as &$_item ){
			$this->_link->withSplitIds( $_item['id'] )->getList( $_item['arrCom'] );
		}
		return $this;
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
		Core_Sql::setExec('UPDATE '.$this->_tableLink.' SET flg_winner=0 WHERE split_id='.Core_Sql::fixInjection($this->_withIds) );
		Core_Sql::setExec('UPDATE '.$this->_tableLink.' SET flg_winner=1 WHERE campaign_id='.Core_Sql::fixInjection($this->_winnerId).' AND split_id='.Core_Sql::fixInjection($this->_withIds) );
		Core_Sql::setExec('UPDATE '.$this->_table.' SET flg_closed=1 WHERE id='.Core_Sql::fixInjection($this->_withIds) );			
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
		$_arr['get']='<script type="text/javascript" src="https://'.Zend_Registry::get( 'config' )->engine->project_domain.'/services/widgets.php?name=Hiam&action=get&split=1&id='.join('-',$_arrId).'"></script>';
		$_arr['effect']='<img src="https://'.Zend_Registry::get( 'config' )->engine->project_domain.'/services/widgets.php?name=Hiam&action=effective&split=1&id='.join('-',$_arrId).'" height="1" width="1" />';
		return $_arr;
	}

	/**
	 * Возвращает id кампании для показа и при необходимости останавливает кампанию
	*
	 * @param  $id - id split test'а из которого выбираем кампанию
	 * @return $id кампании с меньшим просмотром, или победителя теста
	 */
	public function getCampaign( $_splitId ){
		if( empty($_splitId) ){
			return false;
		}
		$this->onlyOne()->withIds( $_splitId )->getList( $_arrSplit );// получаем инф по сплит тесту со статусом
		if ( $_arrSplit['status']=='1' ) { // останавливаем тест
			$this->withWinnerId( $_arrSplit['arrCom']['0'] )->withIds( $_arrSplit['id'] )->request();
			$_arrSplit['flg_enabled']='1';
		}
		if ( $_arrSplit['flg_enabled']=='1' ) { // если тест остановлен - определяем победителя
			$this->_link->onlyWinner()->onlyCell()->withSplitIds( $_arrSplit['id'] )->getList( $intWinnerId );// получаем id компании победителя
			return $intWinnerId; // возвращает только номер id победившей кампании
		}
		$this->_link->onlyIds()->onlyCell()->withSortShownDown()->withSplitIds( $_arrSplit['id'] )->getList( $intWeakId );// получаем id компании с наименьшим показом
		$this->_link->onlyOwner()->withIds( $intWeakId )->withSplitIds( $_arrSplit['id'] )->updateLink();// обновляем show выбранной компании
		return $intWeakId; // возвращает только номер id победившей кампании
	}
}
?>