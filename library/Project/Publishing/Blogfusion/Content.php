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
class Project_Publishing_Blogfusion_Content {

	private $_data=array(); // данные от пользователя
	private $_records=array(); // данные для записи в бд
	private $_contents=array(); // ids контента
	private $_blogs=array(); // ids блога
	private $_time=0; // старт постинга
	private static $_table='pub_schedule';

	public function __construct( Core_Data $data ) {
		$this->_data=&$data->filtered;
		$this->_contents=json_decode( $this->_data['jsonContentIds'], true );
		$this->_blogs=$this->_data['arrBlogIds'];
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
		$this->_blogs=Core_Sql::getField( 'SELECT site_id FROM '.self::$_table.' WHERE project_id='.$this->_data['id'].' GROUP BY site_id' );
		$this->_time=Core_Sql::getCell( 'SELECT start FROM '.self::$_table.' WHERE project_id='.$this->_data['id'].' ORDER BY start DESC LIMIT 1' );
		return $this->generate();
	}

	public function generate() {
		if ( empty( $this->_contents )||empty( $this->_blogs ) ) {
			return false;
		}
		if ( $this->_data['flg_posting']==3 ) { // by list
			$this->listQueue();
		} else { // random
			$this->randomQueue();
		}
		$this->scheduling();
		return Core_Sql::setMassInsert( self::$_table, $this->_records );
		
		// ниже после отладки убрать
		/*if ( !Core_Sql::setMassInsert( self::$_table, $this->_records ) ) {
			return false;
		}
		if ( ( empty( $this->_data['flg_masterblog'] )||empty( $this->_data['masterblog_id'] ) )&&empty( $this->_data['flg_circular'] ) ) {
			return true; // нетворкинга в проекте небудет
		}
		$this->setNetworking();
		return true;*/
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

	// постинг в блоги по списку
	private function listQueue() {
		foreach( $this->_blogs as $b ) {
			foreach( $this->_contents as $c ) {
				$this->_records[]=array( 
					'project_id'=>$this->_data['id'], 
					'content_id'=>$c, 
					'site_id'=>$b['site_id'], 
					'ext_category_id'=>(empty( $b['ext_category_id'] )? 0:$b['ext_category_id']), 
				);
			}
		}
	}

	// постинг в блоги рандомно
	// сделать чтобы последний пост был в блог отличный от того в который был первый пост
	private function randomQueue() {
		$_arrTmp=$this->_blogs;
		foreach( $this->_contents as $v ) {
			$_intKey=array_rand( $_arrTmp, 1 );
			$this->_records[]=array( 
				'project_id'=>$this->_data['id'], 
				'content_id'=>$v, 
				'site_id'=>$_arrTmp[$_intKey]['site_id'], 
				'ext_category_id'=>(empty( $_arrTmp[$_intKey]['ext_category_id'] )? 0:$_arrTmp[$_intKey]['ext_category_id']), 
			);
			unSet( $_arrTmp[$_intKey] );
			if ( empty( $_arrTmp ) ) {
				$_arrTmp=$this->_blogs;
			}
		}
	}

	// вызывать из крон скрипта если в проекте есть нетворкинг
	// перед этим надо сделать проект комплитед, чтобы небыло возможности добавлять дополнительный контент
	public function setNetworking( &$arrRes ) {
		if ( !$this->onlyPosted()->getList( $this->_records ) ) {
			return false;
		}
		$_bool1=$this->masterBlog();
		$_bool2=$this->circular();
		if ( !$_bool1&&!$_bool2 ) { // это означает что нетворкинга нет
			return false;
		}
		foreach( $this->_records as $v ) {
			Core_Sql::setUpdate( self::$_table, $v );
		}
		$arrRes = $this->_records;
		return true;
	}

	// все статьи на ведомых линкуются к статьям на местер + статьи должны быть разными
	private function masterBlog() {
		if ( empty( $this->_data['flg_masterblog'] )||empty( $this->_data['masterblog_id'] ) ) {
			return false;
		}
		$_arrMaster=$_arrSlave=array();
		foreach( $this->_records as $k=>$v ) {
			if ( $v['site_id']==$this->_data['masterblog_id'] ) {
				$_arrMaster[$k]=$v;
			} else {
				$_arrSlave[$k]=$v;
			}
		}
		$_arrTmp=$_arrMaster;
		foreach( $_arrSlave as $k=>$v ) {
			if ( empty( $_arrTmp ) ) {
				$_arrTmp=$_arrMaster;
			}
			foreach( $_arrTmp as $i=>$m ) {
				if ( $v['content_id']!=$m['content_id'] ) {
					$this->_records[$k]['link_to_master']=$m['id'];
					unSet( $_arrTmp[$i] );
					break;
				}
				unSet( $_arrTmp[$i] );
				if ( empty( $_arrTmp ) ) {
					$_arrTmp=$_arrMaster;
				}
			}
		}
		return true;
	}

	// есть баги - надо проверять TODO!!! (с рандомными статьями + не ставит линк в последнем элементе на первый при выборе листом)
	// элементы блогов (статьи) выстраиваются в цепочку и последний ссылается на первый в цепочке
	// причём ссылатся можно только на другой блог и на другую статью
	// совпадений быть не должно
	private function circular() {
		if ( empty( $this->_data['flg_circular'] ) ) {
			return false;
		}
		$_tempArr = $this->_records;
		$_arrRrecipient=array();
		foreach ( $_tempArr as $k=>&$v ) {
			if( $v['sort'] != 1 ) {
				$_arrRrecipient[]=$v;
				$v['sort']=1;
				foreach ( $_tempArr as $k2=>&$v2 ) {
					if ( $v['site_id']!=$v2['site_id'] && $v['content_id']!=$v2['content_id'] && $v2['sort'] != 1){
						$_arrRrecipient[]=$v2;
						$v2['sort']=1;
						break;
					}
				}
			}
		}
		unset($_tempArr);
		$this->_records=$_arrRrecipient;
		while( !empty( $_arrRrecipient ) ) {
			if ( !isSet( $k1 ) ) { // случайным образом выбираем первый элемнт кольцевой цепочки
				$k1=$_intFirstKey=0;
				unSet( $_arrRrecipient[$k1] ); // удаляем из элементов на которые можно делать ссылки (для того чтобы на этот элемент сделать ссылку с последнего)
				$v1=$this->_records[$k1];
			}
			
			$found=false;
			foreach( $_arrRrecipient as $k2=>$v2 ) {
				if ( $v1['site_id']!=$v2['site_id']&&$v1['content_id']!=$v2['content_id'] ) {
					$this->_records[$k1]['link_to']=$v2['id']; // делаем ссылку
					unSet( $_arrRrecipient[$k2] ); // удаляем из элементов на которые можно делать ссылки
					$_intLastKey=$k1=$k2; // теперь будем искать ссылку для элемента на который сделали ссылку
					$v1=$v2;
					$found=true;
				}
			}
			if ( !$found ) { // для случая когда невозможно найти ссылку для элемента (например оба элемента размещены на одном и том же блоге)
				break;
			}
		}
		$this->_records[$_intLastKey]['link_to']=$this->_records[$_intFirstKey]['id'];	
		return true;
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

	// сброс настроек после выполнения getList
	private function init() {
		$this->_withDate=0;
		$this->_withIds=array();
		$this->_withOrder='s.start--up';
		$this->_onlyPosted=false;
		$this->_onlyNonPosted=false;
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

	public function onlyNonPosted() {
		$this->_onlyNonPosted=true;
		return $this;
	}

	public function getList( &$mixRes ) {
		$_crawler=new Core_Sql_Qcrawler();
		$_crawler->set_select( 's.*' );
		$_crawler->set_from( self::$_table.' s' );
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