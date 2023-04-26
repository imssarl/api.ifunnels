<?php

 /**
 * шаринг сайтов
 */
class Project_Syndication_Sites extends Core_Storage {

	protected $_link=false; // тут линк нам не нужен

	/**
	* список полей таблицы
	* @var array
	*/
	public $fields=array( 'id', 'user_id', 'site_id', 'flg_type' );

	/**
	* название таблицы c сайтами на которые постим контент
	* @var string
	*/
	public $table='';

	private $_userId=0;

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
		$this->table=Project_Syndication::$tables['sites'];
	}

	public function getOwnerId() {
		return $this->_userId;
	}

	private $_onlyType=false; // только определенный тип сайтов

	public function onlyType( $_intType) {
		$this->_onlyType=$_intType;
		return $this;
	}

	private $_toPopupSelected=false; // данные для попапав в шаринге сайтов (см. Sharing sites)

	public function toPopupSelected() {
		$this->_toPopupSelected=true;
		return $this;
	}

	protected function init() {
		$this->_onlyType=false;
		$this->_toPopupSelected=false;
		parent::init();
	}

	public function assemblyQuery() {
		if ( $this->_onlyIds ) {
			$this->_crawler->set_select( 'd.id' );
		} elseif ( $this->_toPopupSelected ) {
			$this->_crawler->set_select( 'd.site_id, d.flg_type' );
		} else {
			$this->_crawler->set_select( 'd.*' );
//				IF(d.flg_type='.Project_Sites::PSB.',(SELECT main_keyword FROM '.Project_Sites::$tables[Project_Sites::PSB].' WHERE id=d.site_id),
//				IF(d.flg_type='.Project_Sites::CNB.',(SELECT primary_keyword FROM '.Project_Sites::$tables[Project_Sites::CNB].' WHERE id=d.site_id),
			$this->_crawler->set_select( '
				IF(d.flg_type='.Project_Sites::NCSB.',(SELECT main_keyword FROM '.Project_Sites::$tables[Project_Sites::NCSB].' WHERE id=d.site_id),
				IF(d.flg_type='.Project_Sites::NVSB.',(SELECT main_keyword FROM '.Project_Sites::$tables[Project_Sites::NVSB].' WHERE id=d.site_id),
				IF(d.flg_type='.Project_Sites::BF.',(SELECT title FROM '.Project_Sites::$tables[Project_Sites::BF].' WHERE id=d.site_id),"no name"
				))) title
			' );
			
//				IF(d.flg_type='.Project_Sites::PSB.',(SELECT url FROM '.Project_Sites::$tables[Project_Sites::PSB].' WHERE id=d.site_id),
//				IF(d.flg_type='.Project_Sites::CNB.',(SELECT url FROM '.Project_Sites::$tables[Project_Sites::CNB].' WHERE id=d.site_id),
$this->_crawler->set_select( '
				IF(d.flg_type='.Project_Sites::NCSB.',(SELECT url FROM '.Project_Sites::$tables[Project_Sites::NCSB].' WHERE id=d.site_id),
				IF(d.flg_type='.Project_Sites::NVSB.',(SELECT url FROM '.Project_Sites::$tables[Project_Sites::NVSB].' WHERE id=d.site_id),
				IF(d.flg_type='.Project_Sites::BF.',(SELECT url FROM '.Project_Sites::$tables[Project_Sites::BF].' WHERE id=d.site_id),"no url"
				))) url
			' );
		}
		if ( !Core_Acs::haveAccess( 'email test group','CNM1.0' ) ) {
			$this->_onlyType=Project_Sites::BF;
		}
		if( $this->_onlyType ){
			$this->_crawler->set_where( 'd.flg_type IN ('.Core_Sql::fixInjection( $this->_onlyType ).')' );
		}
		$this->_crawler->set_from( $this->table.' d' );
		if ( !empty( $this->_withIds ) ) {
			$this->_crawler->set_where( 'd.id IN ('.Core_Sql::fixInjection( $this->_withIds ).')' );
		}
		if ( $this->_onlyOwner ) {
			$this->_crawler->set_where( 'd.user_id='.$this->getOwnerId() );
		}
		if ( !$this->_onlyOne ) {
			$this->_crawler->set_order_sort( $this->_withOrder );
		}
	}

	public function set() {
		$this->_data->setFilter();
		foreach( $this->_data->filtered as $k=>$v ) {
			$_arr[$k]=array(
				'flg_type'=>$v['flg_type'],
				'site_id'=>$v['site_id'],
				'user_id'=>$this->getOwnerId()
			);
			if ( !empty( $v['id'] ) ) {
				$_arr[$k]['id']=$v['id'];
			}
		}
		if ( !Core_Sql::setMassInsert( $this->table, $_arr ) ){
			return false;
		}
		return true;
	}

	// внимание!!! тут не учитываем пользователя т.к. должно хватать типа и id сайта
	public static function isSyndicated( $_intId=0, $_intType=0 ) {
		if ( empty( $_intId )||empty( $_intType ) ) {
			return false;
		}
		$_intId=Core_Sql::getCell( 'SELECT id FROM '.Project_Syndication::$tables['sites'].' WHERE flg_type='.$_intType.' AND site_id='.$_intId.' LIMIT 1' );
		return !empty( $_intId );
	}

	public static function delByAdmin( $_arrData=array() ) {
		if ( empty( $_arrData ) ) {
			return;
		}
		foreach( $_arrData as $k=>$v ) {
			$_arr=explode( '-', $k );
			$_arrByType[$_arr[1]][]=$_arr[0];
		}
		foreach( $_arrByType as $k=>$v ) {
			Core_Sql::setExec( 'DELETE FROM '.Project_Syndication::$tables['sites'].' WHERE flg_type='.$k.' AND site_id IN ('.Core_Sql::fixInjection( $v ).')' );
		}
	}

	public static function setOutside( $_intId=0, $_intType=0, $_bool=true ) {
		$_syn=new Project_Syndication_Sites();
		if ( $_bool ) {
			$_syn->delOutside( $_intId, $_intType );
		} else {
			$_syn->addOutside( $_intId, $_intType );
		}
	}

	// $_mixId int or array of int
	public function delOutside( $_mixId=0, $_intType=0 ) {
		if ( empty( $_mixId )||empty( $_intType ) ) {
			return false;
		}
		Core_Sql::setExec( 'DELETE FROM '.$this->table.
			' WHERE flg_type='.$_intType.' AND site_id IN ('.Core_Sql::fixInjection( $_mixId ).') AND user_id='.$this->getOwnerId() );
		return true;
	}

	public function addOutside( $_intId=0, $_intType=0 ) {
		if ( empty( $_intId )||empty( $_intType ) ) {
			return false;
		}
		if ( self::isSyndicated( $_intId, $_intType ) ) {
			return true;
		}
		Core_Sql::setInsert( $this->table, array( 
			'flg_type'=>$_intType,
			'site_id'=>$_intId,
			'user_id'=>$this->getOwnerId()
		) );
		return true;
	}

	// $arrRes - сайты других пользователей в категориях $_arrCats (кстати можно и подзапросом сделать вобщем-то) числом не более $_intLimit
	// + которые не принадлежат текущенму пользователю
	public function getSitesByCat( &$arrRes, $_arrCats=array(), $_intLimit=0 ) {
		if ( empty( $_arrCats )||empty( $_intLimit ) ) {
			return false;
		}
//				IF(b.flg_type='.Project_Sites::PSB.',(SELECT category_id FROM '.Project_Sites::$tables[Project_Sites::PSB].' WHERE id=b.site_id),
//				IF(b.flg_type='.Project_Sites::CNB.',(SELECT category_id FROM '.Project_Sites::$tables[Project_Sites::CNB].' WHERE id=b.site_id),
		$arrRes=Core_Sql::getKeyRecord( '
			SELECT id, flg_type,
				IF(b.flg_type='.Project_Sites::NCSB.',(SELECT category_id FROM '.Project_Sites::$tables[Project_Sites::NCSB].' WHERE id=b.site_id),
				IF(b.flg_type='.Project_Sites::NVSB.',(SELECT category_id FROM '.Project_Sites::$tables[Project_Sites::NVSB].' WHERE id=b.site_id),
				IF(b.flg_type='.Project_Sites::BF.',(SELECT category_id FROM '.Project_Sites::$tables[Project_Sites::BF].' WHERE id=b.site_id),0
			))) category_id
			FROM '.$this->table.' b
			WHERE user_id!="'.$this->_userId.'"'.(Core_Acs::haveAccess( 'email test group' )? '':' AND flg_type='.Project_Sites::BF).'
			HAVING category_id IN('.Core_Sql::fixInjection( $_arrCats ).')
			ORDER BY RAND()
			LIMIT '.$_intLimit
		 );
		return !empty( $arrRes );
	}

/*				(SELECT count(id) site_num, category_id FROM '.Project_Sites::$tables[Project_Sites::PSB].' 
					WHERE id IN(SELECT site_id FROM '.$this->table.' WHERE flg_type='.Project_Sites::PSB.' AND user_id!='.$this->_userId.') GROUP BY category_id)
				UNION
				(SELECT count(id) site_num, category_id FROM '.Project_Sites::$tables[Project_Sites::CNB].' 
					WHERE id IN(SELECT site_id FROM '.$this->table.' WHERE flg_type='.Project_Sites::CNB.' AND user_id!='.$this->_userId.') GROUP BY category_id)
				UNION*/
	public function sitesInCat( &$arrRes ) {
		$arrRes=Core_Sql::getKeyVal( 'SELECT category_id, SUM(site_num) FROM ('.(Core_Acs::haveAccess( 'email test group' )? '
				(SELECT count(id) site_num, category_id FROM '.Project_Sites::$tables[Project_Sites::NCSB].' 
					WHERE id IN(SELECT site_id FROM '.$this->table.' WHERE flg_type='.Project_Sites::NCSB.' AND user_id!='.$this->_userId.') GROUP BY category_id)
				UNION
				(SELECT count(id) site_num, category_id FROM '.Project_Sites::$tables[Project_Sites::NVSB].' 
					WHERE id IN(SELECT site_id FROM '.$this->table.' WHERE flg_type='.Project_Sites::NVSB.' AND user_id!='.$this->_userId.') GROUP BY category_id)
				UNION
				':'').'
				(SELECT count(id) site_num, category_id FROM '.Project_Sites::$tables[Project_Sites::BF].' 
					WHERE id IN(SELECT site_id FROM '.$this->table.' WHERE flg_type='.Project_Sites::BF.' AND user_id!='.$this->_userId.') GROUP BY category_id)
			) tbl GROUP BY category_id
		' );
	}
}
?>