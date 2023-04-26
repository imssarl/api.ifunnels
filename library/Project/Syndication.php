<?php

 /**
 * управление проектами
 */
class Project_Syndication extends Core_Storage {

	/**
	* список полей таблицы
	* @var array
	*/
	public $fields=array( 'id', 'user_id', 'flg_status', 'flg_backlinks', 'title', 'backlinks', 'added' );

	/**
	* название таблицы
	* @var string
	*/
	public $table='cs_project';

	/**
	* линк данных Core_Storage
	* тут линк нам не нужен
	* @var boolean
	*/
	protected $_link=false;

	/**
	* статусы проекта
	* @var array
	*/
	public static $stat=array(
		'draft'=>0,
		'rejected'=>1,
		'pending'=>2,
		'approved'=>3,
		'progress'=>4,
		'completed'=>5,
	);

	/**
	* таблицы синдикейшена
	* собраны в одном месте чтобы быстро поменять префикс например
	* @var array
	*/
	public static $tables=array(
		'project'=>'cs_project',
		'content'=>'cs_content',
		'content2site'=>'cs_content2site',
		'bsites'=>'cs_bsites',
		'project2category'=>'cs_project2category',
		'sites'=>'cs_sites',
		'statistic'=>'cs_statistic',
		'checker'=>'cs_checker',
		'badwords'=>'cs_badwords',
		'points'=>'cs_points',
	);

	/**
	* конструктор
	* @return   void
	*/
	public function __construct() {
		if ( !Zend_Registry::get( 'objUser' )->getId( $_int ) ) {
			throw new Exception( Core_Errors::DEV.'|Zend_Registry::get( \'objUser\' )->getId( $_int ) is not return an User Id' );
			return;
		}
		$this->_userId=$_int;
	}

	public function getOwnerId() {
		return $this->_userId;
	}

	// всегда отображать только по user_id
	public function getList( &$mixRes ) {
		$this->onlyOwner();
		return parent::getList( $mixRes );
	}

	/*
	array(
		'content'=>array( array( id, content_id, flg_status, flg_type, sites_num, (bool)del ), ... )
		'categories'=>array( id, id, id )
		'manual'=>array( array( site_id, flg_type ), ... )
		'id'
		'title'
		'flg_backlinks'
		'backlinks'
		'for_review'
	)
	*/
	public function set() {
		$this->_data->setFilter();
		if ( !empty($this->_data->filtered['id']) ) {
			$this->get($_arrPrj,$this->_data->filtered['id']);
		}
		// если у проекта поменялся статус во время его редактирования, сохранять нельзя.
		if( $this->_data->filtered['flg_status'] <  self::$stat['approved'] && $_arrPrj['flg_status'] >= self::$stat['approved'] ){
			return false;
		}
		// если проект approved,in progress то изменить можно только title
		if ( !empty($this->_data->filtered['id']) && $this->_data->filtered['flg_status'] >= self::$stat['approved']){
			$_arrPrj['title']=$this->_data->filtered['title'];
			$this->setData($_arrPrj);
			$this->_data->setFilter();
		}
		if ( !$this->_data->setChecker( array(
			'title'=>empty( $this->_data->filtered['title'] ),
			'content'=>empty( $this->_data->filtered['content'] ),
			'categories'=>empty( $this->_data->filtered['categories'] ),
			'manual'=>
				!empty( $this->_data->filtered['flg_backlinks'] )
				&&$this->_data->filtered['flg_backlinks']==Project_Syndication_Backlink::$stat['manual']
				&&empty( $this->_data->filtered['manual'] ), // если забыли выбрать сайты
		) )->check() ) {
			$this->_data->getErrors( $this->_errors['filtered'] );
			return false; 
		}
		foreach ( $this->_data->filtered['content'] as $v ){
			if ( $v['sites_num']<=0 ){
				return false;
			}
		}
		if ( empty( $this->_data->filtered['id']) ) {
			$this->_data->setElements( array(
				'user_id'=>$this->_userId,
				'added'=>time(),
			) );
		}
		$this->_data->setElement( 'backlinks', Project_Syndication_Backlink::checkLinkList( $this->_data->filtered['flg_backlinks'], $this->_data->filtered['backlinks'] ) );
		$this->_data->setElement( 'id', Core_Sql::setInsertUpdate( $this->table, $this->_data->setMask( $this->fields )->getValid() ) );
		if ( empty( $this->_data->filtered['id'] ) ) {
			return false;
		}
		if ( $this->_data->filtered['flg_status'] >= self::$stat['approved'] ) {
			return true; // в этом случае меняем только тайтл
		}
		// тут сохраняем дополнительные данные
		$_content=new Project_Syndication_Content( $this->_data->filtered['id'] );
		$_content->setData( $this->_data->filtered['content'] )->set();
		Project_Syndication_Project_Category::set( $this->_data->filtered['id'], $this->_data->filtered['categories'] );
		if ( !empty( $this->_data->filtered['flg_backlinks'] )&&$this->_data->filtered['flg_backlinks']==Project_Syndication_Backlink::$stat['manual'] ) {
			Project_Syndication_Backlink::set( $this->_data->filtered['id'], $this->_data->filtered['manual'] );
		}
		if ( !empty( $this->_data->filtered['for_review'] ) ) { // нажали кнопку Submit for review
			if ( !$_content->autoFiltering( $this->_errors['autoFiltering'] ) ) {
				self::status( 'rejected', $this->_data->filtered['id'] );
				return false;
			}
			self::status( 'pending', $this->_data->filtered['id'] ); // апдэйтим статус у проекта т.к. контент прошёл автоматическую проверку
		}
		return true;
	}

	public function del( $_arr=array() ) {
		if ( empty( $_arr ) ) {
			return false;
		}
		$_arr=!is_array( $_arr )? array( $_arr ):$_arr;
		// тут удаляем данные из других таблиц
		foreach( $_arr as $v ) {
			Project_Syndication_Project_Category::set( $v );
			Project_Syndication_Backlink::set( $v );
			$_content=new Project_Syndication_Content( $v );
			$_content->delAllByProject();
		}
		return parent::del( $_arr );
	}

	public static function getProjectOwnerId( $_intId ) {
		return Core_Sql::getCell( 'SELECT user_id FROM '.Project_Syndication::$tables['project'].' WHERE id='.$_intId );
	}

	public function getOnlyProject( &$arrRes, $_intId=0 ) {
		return parent::get( $arrRes, $_intId );
	}

	public function get( &$arrRes, $_intId=0 ) {
		if ( !parent::get( $arrRes, $_intId ) ) {
			return false;
		}
		$_content=new Project_Syndication_Content( $_intId );
		$_content->toJs()->getList( $arrRes['content'] );
		Project_Syndication_Project_Category::get( $_intId, $arrRes['categories'] );
		if ( $arrRes['flg_backlinks']==Project_Syndication_Backlink::$stat['manual'] ) {
			Project_Syndication_Backlink::getBacklinks( $_intId, $arrRes['manual'] );
		}
	}

	public static function status( $_str='', $_intId=0 ) {
		if ( empty( $_intId )||!in_array( $_str, array_keys( self::$stat ) ) ) {
			return;
		}
		Core_Sql::setUpdate( Project_Syndication::$tables['project'], array( 'flg_status'=>self::$stat[$_str], 'id'=>$_intId ) );
	}
}
?>