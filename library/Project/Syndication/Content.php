<?php

 /**
 * управление списком КТ в проектах
 */
class Project_Syndication_Content extends Core_Storage {

	public static $_instance=NULL;

	/**
	* список полей таблицы
	* @var array
	*/
	public $fields=array( 'id', 'project_id', 'content_id', 'flg_status', 'flg_type', 'flg_cause', 'blocked', 'sites_num', 'title', 'body', 'comment' );

	/**
	* название таблицы
	* @var string
	*/
	public $table='cs_content';

	protected $_link=false; // тут линк нам не нужен

	public static $stat=array(
		'draft'=>0,
		'rejected'=>1,
		'pending'=>2,
		'approved'=>3,
		'published'=>4,
		'error'=>5,
	);

	protected $_projectId=0;

	/**
	* массив запрещённых слов. слова из бд+$_ext
	* заполняется только один раз за проход
	* @var array
	*/
	private static $_badwords=array();

	private $_ext=array( '', 'ed', 'ing', 's' );

	/**
	* конструктор
	* @return   void
	*/
	public function __construct( $_intPrjId=0 ) {
		if ( empty( $_intPrjId ) ) {
			throw new Exception( Core_Errors::DEV.'|projectId not set' );
			return;
		}
		$this->_projectId=$_intPrjId;
	}

	// чтобы при удалении в Core_Storage не использовалось поле user_id
	public function getOwnerId() {
		return false;
	}

	protected $_withStatus=false; // c определённым статусом, можно указывать несколько в виде массива
	protected $_toJs=false; // контент для Json
	protected $_toSyndicate=false; // контент непосредственно для постинга

	protected function withStatus( $_arrStat=array() ) {
		$this->_withStatus=$_arrStat;
		return $this;
	}

	public function toJs(){
		$this->_toJs=true;
		return $this;
	}

	public function toSyndicate(){
		$this->_toSyndicate=true;
		return $this;
	}

	protected function init() {
		$this->_withStatus=false;
		$this->_toJs=false;
		$this->_toSyndicate=false;
		parent::init();
	}

	public function getProjectPostNum( &$intRes ) {
		$intRes=Core_Sql::getCell( 'SELECT SUM(sites_num) FROM '.$this->table.' WHERE project_id='.$this->_projectId );
		return !empty( $intRes );
	}

	protected function assemblyQuery() {
		if ( $this->_toJs ) {
			$this->_crawler->set_select( 'd.id, d.content_id, d.flg_status, d.flg_type, d.sites_num, d.title' );
		} elseif ( $this->_toSyndicate ) {
			$this->_crawler->set_select( 'd.id, d.title, d.body' );
		} elseif ( $this->_onlyIds ) {
			$this->_crawler->set_select( 'd.id' );
		}else {
			$this->_crawler->set_select( 'd.*' );
		}
		$this->_crawler->set_from( $this->table.' d' );
		if ( !empty( $this->_projectId ) ) { // пустое может быть только при переопределении конструктора
			$this->_crawler->set_where( 'd.project_id='.$this->_projectId ); // всегда отображаем только текущий проект
		}
		if ( !empty( $this->_withIds ) ) {
			$this->_crawler->set_where( 'd.id IN ('.Core_Sql::fixInjection( $this->_withIds ).')' );
		}
		if ( $this->_withStatus!==false ) {
			$this->_crawler->set_where( 'd.flg_status IN ('.Core_Sql::fixInjection( $this->_withStatus ).')' );
		}
		if ( !$this->_onlyOne ) {
			$this->_crawler->set_order_sort( $this->_withOrder );
		}
	}

	public function get( &$arrRes, $_intId=0 ) {
		return parent::get( $arrRes, $_intId );
	}

	public function getList( &$mixRes ) {
		return parent::getList( $mixRes );
	}
	
	/**
	 * Чужой контент запощеный на расшаренные сайты пользователя.
	 *
	 * @param array $_arrRes
	 * @return bool
	 */
	public static function getContent( &$_arrRes ){
		if ( !Zend_Registry::get( 'objUser' )->getId( $_intUserId ) ) {
			throw new Exception( Core_Errors::DEV.'|Zend_Registry::get( \'objUser\' )->getId( $_int ) is not return an User Id' );
			return;
		}
		$sites=new Project_Syndication_Sites();
		$sites->onlyOwner()->getList($_arrRes );
		if ( empty( $_arrRes ) ){
			return false;
		}
		foreach ( $_arrRes as &$_site ){
			$_site['arrContent']=Core_Sql::getAssoc( 'SELECT c2s.*,c.*,c2s.id as c2s_id FROM cs_content2site as c2s LEFT JOIN cs_content as c '
			.' ON c2s.content_id=c.id WHERE c2s.flg_status=1 AND c2s.site_id = '.$_site['id'] );
		}
		return !empty($_arrRes);
	}

	// рефакторинг TODO!!! 08.05.2012
	public static function deleteContent( $intId ){
		// чото непонятно как оно работает...
		$arrContent = Core_Sql::getRecord('SELECT c2s.*,c.*,c2s.id as c2s_id FROM cs_content2site as c2s LEFT JOIN cs_content as c '
			.' ON c2s.content_id=c.id WHERE c2s.id = '.$intId);
		$arrSite = Core_Sql::getRecord('SELECT *,site_id as site_realid FROM cs_sites WHERE id='.$arrContent['site_id'] );
		// удалять так, только надо собрать данные
		Project_Syndication_Adapter_Factory::get( $arrSite['flg_type'] )->setSite( $_intSiteId )->setContent( $arrContent )->deleteContent();
		/*switch( $arrSite['flg_type'] ) {
			case Project_Sites::BF: return Project_Syndication_Sites_Blogfusion::getInstance()->setData( $arrSite, $arrContent )->delete(); break;
			case Project_Sites::NCSB: return Project_Syndication_Sites_Ncsb::getInstance()->setData( $arrSite, $arrContent )->delete(); break;
			case Project_Sites::NVSB: return Project_Syndication_Sites_Nvsb::getInstance()->setData( $arrSite, $arrContent )->delete(); break;
		}*/
		// это было почему-то в адаптере - разобраться
		Core_Sql::setExec('DELETE FROM cs_content2site WHERE id='.$this->_content['c2s_id']);
	}

	public function delAllByProject() {
		Core_Sql::setExec( 'DELETE FROM '.Project_Syndication::$tables['content'].' WHERE project_id='.$this->_projectId );
	}

	public function set() {
		$this->_data->setFilter();
		$_arrDel=array();
		foreach( $this->_data->filtered as $k=>$v ) {
			if ( $v['del'] ) {
				$_arrDel[]=$v['id'];
				continue;
			}
			unSet( $v['del'] );
			if ( empty( $v['id'] ) ) {
				unSet( $v['id'] );
			}
			// после того как контент проверили и разрешили его можно изменить только удалив и добавив заново
			if ( $v['flg_status']==self::$stat['approved'] ) {
				unSet( $v['title'] );
			}
			$v['project_id']=$this->_projectId;
			Core_Sql::setInsertUpdate( $this->table, $v );
		}
		$this->del( $_arrDel );
	}

	protected function copyContent( Core_Data $_data ){
		if ( !Core_Sql::setUpdate($this->table, $_data->filtered ) ){
			return false;
		}
		return true;
	}

	// автоматическая проверка контента при сохранении проекта
	// контенту который не прошёл выставляем rejected (и по концовке return false) у остального остаётся draft
	public function autoFiltering( &$arrErr ) {
		// выбираем контент только добавленный и который не прошёл проверку (т.к. возможно исправили)
		if ( !$this->withStatus( array( self::$stat['draft'], self::$stat['rejected'] ) )->getList( $_arrContent ) ) {
			$arrErr['contentNotFound']=true;
			return false;
		}
		// для начала весь КТ под подозрением кроме уже проверенного КТ
		Core_Sql::setExec( '
			UPDATE '.$this->table.' SET flg_status='.self::$stat['rejected'].' 
			WHERE 
				project_id='.$this->_projectId.' AND 
				flg_status!='.self::$stat['approved'] 
		);
		// разделяем по типу контента
		$arrArticles=$arrVideo=array();
		foreach( $_arrContent as $v ) {
			if ( $v['flg_type']==1 ) {
				$arrArticles[$v['content_id']]=$v['id'];
			} elseif ( $v['flg_type']==2 ) {
				$arrVideo[$v['content_id']]=$v['id'];
			}
		}
		// проверяем КТ
		$arrErr['article']=$arrErr['video']=array();
		if ( !empty( $arrArticles ) ) { // проверяем статьи
			$this->checker( Project_Articles::getInstance(), $arrArticles, $_arrPendingReview, $arrErr['article'] );
		}
		if ( !empty( $arrVideo ) ) { // проверяем видео
			$this->checker( Project_Embed::getInstance(), $arrVideo, $_arrPendingReview, $arrErr['video'] );
		}
		// обновляем статусы КТ
		if ( empty( $arrErr['article'] )&&empty( $arrErr['video'] ) ) {
			// проект готов к ручной проверке. уже проверенные КТ на проверку не отправляем
			Core_Sql::setExec( '
				UPDATE '.$this->table.' SET flg_status='.self::$stat['pending'].' 
				WHERE 
					project_id='.$this->_projectId.' AND 
					flg_status!='.self::$stat['approved'] 
			);
			return true;
		}
		if ( !empty( $_arrPendingReview ) ) { // только часть КТ прошла проверку
			Core_Sql::setExec( '
				UPDATE '.$this->table.' SET flg_status='.self::$stat['draft'].' 
				WHERE id IN('.join( ', ', $_arrPendingReview ).')'
			);
		}
		return false;
	}

	private function initBadwords() {
		if ( !empty( self::$_badwords ) ) {
			return;
		}
		$_arrWords=Core_Sql::getField( 'SELECT word FROM cs_badwords' );
		// Add all combinations to the new array
		foreach( $_arrWords as $word ){
			foreach( $this->_ext as $ext ){
				self::$_badwords[]=$word.$ext;
			}
		}
	}

	private function filter( $_arrContent=array() ) {
		if ( empty( $_arrContent ) ) {
			return false;
		}
		// тут исполняем фильтр для полей title и body
		// возможно для видео надо будет убирать объект плеера для более корректной проверки TODO!!!26.05.2010
		foreach( $_arrContent as $k=>$v ) {
			if ( !in_array( $k, array( 'title', 'body' ) ) ) {
				continue;
			}
			$_arrTested=Core_String::getInstance( strtolower( strip_tags( $v ) ) )->separate();
			$_arrRes=array_intersect( $_arrTested, self::$_badwords );
			if ( !empty( $_arrRes ) ) {
				return false;
			}
		}
		return true;
	}

	private function checker( Project_Content_Interface $_obj, $_arrKT, &$arrPendingReview, &$arrErr ) {
		if ( !$_obj->withIds( array_keys( $_arrKT ) )->getList( $_arrRes ) ) {
			return;
		}
		$this->initBadwords();
		foreach( $_arrRes as $v ) {
			if ( $this->filter( $v ) ) {
				$arrPendingReview[]=$_arrKT[$v['id']];
			} else {
				$arrErr[]=$_arrKT[$v['id']];
			}
		}
	}

	public static function getInstance( $_intPrj=0 ) {
		if ( self::$_instance==NULL ) {
			self::$_instance=new Project_Syndication_Content( $_intPrj );
		}
		return self::$_instance;
	}

	// обновление статусов по завершению проекта
	private function updateByPlanLog() {
		$_arrPlanLog=Project_Syndication_Content_Plan::getPlanRaw( $this->_projectId );
		$_arrPub=$_arrErr=array();
		foreach( $_arrPlanLog as $v ) {
			// если контент опубликован и ниразу неудачно не публиковался
			if ( $v['flg_status']==Project_Syndication_Content_Plan::$stat['published']&&!in_array( $v['content_id'], $_arrErr ) ) {
				$_arrPub[$v['content_id']]=$v['content_id'];
			} else { // если контент неудачно публиковался удалим из удачно опубликованного
				unSet( $_arrPub[$v['content_id']] );
				$_arrErr[$v['content_id']]=$v['content_id'];
			}
		}
		// обновляем статусы
		if ( !empty( $_arrPub ) ) {
			Core_Sql::setExec( 'UPDATE '.Project_Syndication::$tables['content'].' SET flg_status='.self::$stat['published'].' WHERE id IN('.join( ', ', $_arrPub ).')' );
		}
		if ( !empty( $_arrErr ) ) {
			Core_Sql::setExec( 'UPDATE '.Project_Syndication::$tables['content'].' SET flg_status='.self::$stat['error'].' WHERE id IN('.join( ', ', $_arrErr ).')' );
		}
		return true;
	}

	// принудительное обновление статусов
	private function updateByProject( $_strStatus='' ) {
		if ( !in_array( $_strStatus, array_keys( self::$stat ) ) ) {
			return false;
		}
		Core_Sql::setExec( 'UPDATE '.Project_Syndication::$tables['content'].' SET flg_status='.self::$stat[$_strStatus].' WHERE project_id='.$this->_projectId );
		return true;
	}

	public function status( $_strStatus='' ) {
		return (empty( $_strStatus )? $this->updateByPlanLog():$this->updateByProject( $_strStatus ));
	}
}
?>