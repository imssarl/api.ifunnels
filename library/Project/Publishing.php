<?php
/**
 * WorkHorse Framework
 *
 * @category Project
 * @package Project_Publishing
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2005-2010, web2innovation
 * @author Rodion Konnov <kindzadza@mail.ru>
 * @date 02.02.2010
 * @version 0.1
 */


/**
 * Data management for module (user interface)
 *
 * @category Project
 * @package Project_Wpress
 * @copyright Copyright (c) 2005-2010, web2innovation
 * @license http://opensource.org/licenses/ MIT License
 */
class Project_Publishing {


	public $fields=array( 
		'id', 'user_id', 'category_id', 'masterblog_id', 
		'flg_status', 'flg_type', 'flg_source', 'flg_posting', 'flg_masterblog', 'flg_circular', 'flg_rss_url', 'flg_schedule', 'flg_generate', 
		'start', 'time_between', 'random', 'rss_new', 'rss_limit', 'title', 'feeds', 'added', 'tags','keywords_first','keywords_random' );
	public $table='pub_project';

	public $data;
	private $_object;

	public function __construct( $object ) {
		if ( $object instanceof Project_Publishing_Interface ) {
			$this->_object=$object;
		} else {
			throw new Exception( '$object is not an Project_Publishing_Interface' );
		}
	}

	public function del( $_mix=0 ) {
		if ( empty( $_mix ) ) {
			return false;
		}
		$_mix=is_array( $_mix ) ? $_mix:array( $_mix );
		Core_Sql::setExec( '
			DELETE p, r, s, b 
			FROM '.$this->table.' p
			LEFT JOIN pub_schedule s ON s.project_id=p.id
			LEFT JOIN pub_rsscache r ON r.project_id=p.id
			LEFT JOIN pub_rssblogs b ON b.project_id=p.id
			WHERE p.id IN('.Core_Sql::fixInjection( $_mix ).')
		' );
		return true;
	}

	public function setData( $_arrData=array() ) {
		$this->data=new Core_Data( $_arrData );
		return $this;
	}

	public function set() {
		return $this->_object->set( $this );
	}

	public function getEntered( &$arrRes ) {
		$arrRes=$this->data->getFiltered();
		return $this;
	}

	public function getErrors( &$arrRes ) {
		$this->data->getErrors( $arrRes );
		return $this;
	}

	// настройки для getList
	private $_onlyIds=false; // массив с ids
	private $_onlyCount=false; // только количество
	private $_onlyOne=false; // только одна запись
	private $_onlyOwner=false; // записи принадлежащие текущему пользователю
	private $_withIds=array(); // c данными id
	private $_withPagging=array(); // постранично
	private $_withStatus=false; // cо статистикой
	private $_withOrder='p.id--up'; // c сортировкой
	private $_paging=array(); // инфа по навигации
	private $_cashe=array(); // закэшированный фильтр

	// сброс настроек после выполнения getArticles
	private function init() {
		$this->_onlyIds=false;
		$this->_onlyCount=false;
		$this->_onlyOne=false;
		$this->_onlyOwner=false;
		$this->_withStatus=false;
		$this->_withIds=array();
		$this->_withPagging=array();
		$this->_withOrder='p.id--up';
	}

	public function get( &$arrRes, $_intId=0 ) {
		if ( empty( $_intId ) ) {
			return false;
		}
		$this
			->onlyOne()
			->withIds( $_intId )
			->getList( $arrRes );
		if ( empty( $arrRes ) ) {
			return false;
		}
		// если запускаем из браузера и проект завершён, то удаляем запощенный контент из бд
		if ( !WorkHorse::$_isShell&&$arrRes['flg_status']==2 ) {
			switch( $arrRes['flg_type'] ) {
				case Project_Sites::BF :
					if ( $data->filtered['flg_source']==3 ) {
						Project_Publishing_Blogfusion_Rss::delete( $arrRes['id'] );
					} else {
						Project_Publishing_Blogfusion_Content::delete( $arrRes['id'] );
					}
				break;
				case Project_Sites::CNB : break; // TODO!!!
				case Project_Sites::NVSB : break; // TODO!!!
			}
		}
		return true;
	}

	public function onlyOne() {
		$this->_onlyOne=true;
		return $this;
	}

	public function onlyOwner() {
		$this->_onlyOwner=true;
		return $this;
	}

	public function withIds( $_arrIds=array() ) {
		$this->_withIds=is_array( $_arrIds ) ? $_arrIds:array( $_arrIds );
		return $this;
	}

	public function withPagging( $_arr=array() ) {
		$this->_withPagging=$_arr;
		return $this;
	}
	
	public function withStatus() {
		$this->_withStatus=true;
		return $this;
	}

	public function withOrder( $_str='' ) {
		if ( !empty( $_str ) ) {
			$this->_withOrder=$_str;
		}
		$this->_cashe['order']=$this->_withOrder;
		return $this;
	}

	public function getList( &$mixRes ) {
		$_crawler=new Core_Sql_Qcrawler();
		$_crawler->set_select( 'p.*' );
		$_crawler->set_where( 'p.flg_type='.$this->_object->getType() );
		if( $this->_withStatus ) {
			$_crawler->set_select( '(SELECT COUNT(*) FROM pub_schedule as s WHERE s.project_id = p.id) as count_content' );
			$_crawler->set_select( '(SELECT COUNT(*) FROM pub_schedule as s WHERE s.project_id = p.id AND s.flg_status = 1) as count_posted_content' );
			$_crawler->set_select( '(SELECT COUNT(*) FROM pub_rsscache as c WHERE c.project_id = p.id) as count_posted_rss_content' );
		}
				
		$_crawler->set_from( $this->table.' p' );
		if ( !empty( $this->_withIds ) ) {
			$_crawler->set_where( 'p.id IN ('.Core_Sql::fixInjection( $this->_withIds ).')' );
		}
		if ( $this->_onlyOwner ) {
			$_crawler->set_where( 'p.user_id='.$this->_object->getOwnerId() );
		}
		if ( !$this->_onlyOne ) {
			$_crawler->set_order_sort( $this->_withOrder );
		}
		if ( !empty( $this->_withPagging ) ) {
			$this->_withPagging['rowtotal']=Core_Sql::getCell( $_crawler->get_result_counter( $_strTmp ) );
			$_crawler->set_paging( $this->_withPagging )->get_sql( $_strSql, $this->_paging );
		} elseif ( !$this->_onlyCount ) {
			$_crawler->get_result_full( $_strSql );
		}
		if ( $this->_onlyIds ) {
			$mixRes=Core_Sql::getField( $_strSql );
		} elseif ( $this->_onlyCount ) {
			$mixRes=Core_Sql::getCell( $_crawler->get_result_counter() );
		} elseif ( $this->_onlyOne ) {
			$mixRes=Core_Sql::getRecord( $_strSql );
		} else {
			$mixRes=Core_Sql::getAssoc( $_strSql );
		}
		$this->init();
		return $this;
	}

	public function getFilter( &$arrRes ) {
		$arrRes=$this->_cashe;
		return $this;
	}

	public function getPaging( &$arrRes ) {
		$arrRes=$this->_paging;
		$this->_paging=array();
		return $this;
	}
}
?>