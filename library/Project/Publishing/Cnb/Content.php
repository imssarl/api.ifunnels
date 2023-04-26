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
 * Create content projects
 *
 * @category Project
 * @package Project_Wpress
 * @copyright Copyright (c) 2005-2010, web2innovation
 * @license http://opensource.org/licenses/ MIT License
 */
class Project_Publishing_Cnb_Content {

	private $_data=array(); // данные от пользователя
	private $_records=array(); // данные для записи в бд
	private $_contents=array(); // ids контента
	private $_sites=array(); // ids блога
	private $_time=0; // старт постинга
	private static $_table='pub_schedule';

	public function __construct( Core_Data $data ) {
		$this->_data=&$data->filtered;
		$this->_contents=json_decode( $this->_data['jsonContentIds'], true );
		$this->_sites=$this->_data['arrSiteIds'];
		$this->setStartTime();
	}

	private function setStartTime() {
		if ( empty( $this->_data['flg_schedule'] ) ) {
			return;
		}
		if ( $this->_data['flg_schedule']==1 ) {
			$this->_time=time();
		}
		$this->_time=(is_numeric( $this->_data['start'] )? $this->_data['start']:time());
	}

	// добавление контента в работающий проект
	public function update() {
		$this->_sites=Core_Sql::getField( 'SELECT site_id FROM '.self::$_table.' WHERE project_id='.$this->_data['id'].' GROUP BY site_id' );
		$this->_time=Core_Sql::getCell( 'SELECT start FROM '.self::$_table.' WHERE project_id='.$this->_data['id'].' ORDER BY start DESC LIMIT 1' );
		return $this->generate();
	}

	public function generate() {
		if ( empty( $this->_contents )||empty( $this->_sites ) ) {
			return false;
		}
		if ( $this->_data['flg_posting']==3 ) { // by list
			$this->listQueue();
		} else { // random
			$this->randomQueue();
		}
		$this->scheduling();
		return Core_Sql::setMassInsert( self::$_table, $this->_records );
	}

	/*
	// проверить чтобы все данные были в $this->_data
	0 - 0
	1 - 0+10 (10) так как для второго поста рандомный фактор необязателен
	2 - 10+10+5 (25)
	3 - 25 +10+5 (40)
	4 - 40 +10+5 (55)
	*/
	private function scheduling() {
		$_intRecordCount=count( $this->_records );
		$this->_data['time_between']=empty( $this->_data['time_between'] )? 0:$this->_data['time_between'];
		$this->_data['random']=empty( $this->_data['random'] )? 0:$this->_data['random'];
		for( $_int=0; $_int<$_intRecordCount; $_int++ ) {
			if ( $_int==0 ) {
				$this->_records[$_int]['start']=$this->_time;
			} elseif ( $_int==1 ) {
				$this->_records[$_int]['start']=$this->_time=$this->_time+$this->_data['time_between']*60;
			} else {
				$this->_records[$_int]['start']=$this->_time=$this->_time+$this->_data['time_between']*60+$this->_data['random']*60;
			}
		}
	}

	// постинг в сайты по списку
	private function listQueue() {
		foreach( $this->_sites as $b ) {
			foreach( $this->_contents as $c ) {
				$this->_records[]=array( 
					'project_id'=>$this->_data['id'], 
					'content_id'=>$c, 
					'site_id'=>$b['site_id'], 
				);
			}
		}
	}

	// постинг в сайты рандомно
	// сделать чтобы последний пост был в сайт отличный от того в который был первый пост
	private function randomQueue() {
		$_arrTmp=$this->_sites;
		foreach( $this->_contents as $v ) {
			$_intKey=array_rand( $_arrTmp, 1 );
			$this->_records[]=array( 
				'project_id'=>$this->_data['id'], 
				'content_id'=>$v, 
				'site_id'=>$_arrTmp[$_intKey]['site_id'], 
			);
			unSet( $_arrTmp[$_intKey] );
			if ( empty( $_arrTmp ) ) {
				$_arrTmp=$this->_sites;
			}
		}
	}


	public static function delete( $_intId=0 ) {
		if ( empty( $_intId ) ) {
			return false;
		}
		Core_Sql::setExec( 'DELETE FROM '.self::$_table.' WHERE project_id='.$_intId );
		return true;
	}

	// настройки для getList
	private $_withDate=0; // записи старше или равные данного таймстампа
	private $_withIds=array(); // c данными id
	private $_withOrder='s.start--up'; // c сортировкой
	private $_onlyPosted=false; // только те которые удалось запостить
	private $_onlyNonPosted=false; // только те которые ещё не запощены
	private $_withSite=false; // с сайтом

	// сброс настроек после выполнения getList
	private function init() {
		$this->_withDate=0;
		$this->_withIds=array();
		$this->_withOrder='s.start--up';
		$this->_onlyPosted=false;
		$this->_onlyNonPosted=false;
		$this->_withSite=false;
	}

	public function withIds( $_arrIds=array() ) {
		$this->_withIds=is_array( $_arrIds ) ? $_arrIds:array( $_arrIds );
		return $this;
	}

	public function _withDate( $_int=0 ) {
		$this->_withDate=$_int;
		return $this;
	}

	public function withOrder( $_str='' ) {
		if ( !empty( $_str ) ) {
			$this->_withOrder=$_str;
		}
		return $this;
	}

	public function onlyPosted() {
		$this->_onlyPosted=true;
		return $this;
	}	
	
	public function withSite() {
		$this->_withSite=true;
		return $this;
	}

	public function onlyNonPosted() {
		$this->_onlyNonPosted=true;
		return $this;
	}

	public function getList( &$mixRes ) {
		$_crawler=new Core_Sql_Qcrawler();
		$_crawler->set_select( 's.*' );
		$_crawler->set_from( self::$_table.' s' );
		if ( $this->_withSite ){
			$_crawler->set_select( 'c.url' );
			$_crawler->set_from( 'LEFT JOIN es_cnb as c ON c.id = s.site_id' );
		}		
		$_crawler->set_where( 's.project_id="'.$this->_data['id'].'"' );
		if ( !empty( $this->_withIds ) ) {
			$_crawler->set_where( 's.id IN ('.Core_Sql::fixInjection( $this->_withIds ).')' );
		}
		if ( !empty( $this->_withDate ) ) {
			$_crawler->set_where( 's.start<='.$this->_withDate );
		}
		if ( $this->_onlyPosted ) {
			$_crawler->set_where( 's.flg_status=1' );
		}
		if ( $this->_onlyNonPosted ) {
			$_crawler->set_where( 's.flg_status=0' );
		}
		$_crawler->set_order_sort( $this->_withOrder );
		$_crawler->get_result_full( $_strSql );
		$mixRes=Core_Sql::getAssoc( $_strSql );
		$this->init();
		return !empty( $mixRes );
	}
	
}
?>