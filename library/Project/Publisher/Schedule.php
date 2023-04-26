<?php
class Project_Publisher_Schedule extends Core_Storage {

	public $fields=array( 'id', 'project_id', 'site_id','flg_type', 'link_to', 'link_to_master', 'ext_post_id', 'flg_status', 'start', 'ext_category_id', 'keyword', 'title', 'body' );
	public $table='pub_schedule';
	public  static $_status=array('notposted'=>0,'success'=>1,'error'=>2,'errorlocal'=>3);

	private $_projectId=0;
	private $_time=0;
	private $_records=array();
	private $_sites=array();
	private $_contents=array();

	public function __construct( Core_Data $data ){
		$this->_project=&$data->filtered;
		$this->_projectId=$this->_project['id'];
		$this->setStartTime();
	}

	public function setSites( $_arrSites ){
		$this->_sites=&$_arrSites;
		return $this;
	}

	public function setContent( $_arrIds ){
		Project_Content::factory( $this->_project['flg_source'] )->withJson()->setFilter( $this->_project['settings'] )->withIds( $_arrIds )->getList( $this->_contents );
		if( empty( $this->_contents ) ){
			sleep( 5 );
			Project_Content::factory( $this->_project['flg_source'] )->withJson()->setFilter( $this->_project['settings'] )->withIds( $_arrIds )->getList( $this->_contents );
		}
		if( empty( $this->_contents ) ){
			throw new Exception( Core_Errors::DEV.'|Project_Publisher_Schedule->setContent() - empty content:'.serialize(array(
				'flg_source'=>$this->_project['flg_source'],
				'settings'=>$this->_project['settings'] ,
				'withIds'=>$_arrIds,
			))  );
		}
		return $this;
	}

	private function setStartTime( $_int=0 ){
		if ( !empty( $_int ) ){
			$this->_time=$_int;
			return $this;
		}
		$this->_time=(is_numeric( $this->_project['start'] )? $this->_project['start']:time());
	}

	public function setStatus( $_arrItems, $_flgStatus ){
		foreach( $_arrItems as $_item ){
			Core_Sql::setExec('UPDATE '.$this->table.' SET flg_status='.$_flgStatus.' WHERE id='.$_item['id'] );
		}
	}

	// обновление статусов после публикации контента
	public function setHistory( $_boolStatus=false, $_arrHistory=array() ){
		if ( empty( $_arrHistory ) ){
			return false;
		}
		foreach( $_arrHistory as $v ){
			Core_Sql::setUpdate( $this->table, array(
				'id'=>$v['id'],
				'flg_status'=>( $_boolStatus? self::$_status['success']:self::$_status['error']),
				'ext_post_id'=>(empty( $v['ext_post_id'] )? 0:$v['ext_post_id']), // только для блогфьюжена (нужно для последующего установления networking)
			) );
		}
		return $_boolStatus;
	}

	// setContent()->addContent()
	// добавление контента в работающий проект
	public function addContent(){
		return $this
			->setSites( Core_Sql::getAssoc( 'SELECT site_id, ext_category_id FROM '.$this->table.' WHERE project_id='.$this->_projectId.' GROUP BY site_id' ) )
			->setStartTime( Core_Sql::getCell( 'SELECT start FROM '.$this->table.' WHERE project_id='.$this->_projectId.' ORDER BY start DESC LIMIT 1' ) )
			->generate();
	}

	// setSites()->setContent()->generate()
	public function generate(){
		if ( empty( $this->_contents )||empty( $this->_sites )||empty( $this->_time ) ){
			throw new Exception( Core_Errors::DEV.'|Project_Publisher_Schedule->generate() - not enough data to generate. serialize( content ):'.serialize(array(
				'content'=>$this->_contents,
				'sites'=>$this->_sites,
				'time'=>$this->_time,
			))  );
			return false;
		}
//		p($this->_project);
		if ( $this->_project['flg_posting']==3 ){ // by list
			$this->listQueue();
		} else { // random
			$this->randomQueue();
		}
		$this->scheduling();
		return $this->store();
	}

	// постинг в блоги по списку
	private function listQueue(){
		foreach( $this->_sites as $_type=>$_sites ){
			foreach( $_sites as $b ){
				foreach( $this->_contents as $c ){
					$this->_records[]=array(
						'project_id'=>$this->_projectId,
						'title'=>$c['title'],
						'body'=>$c['fields'],
						'site_id'=>$b['site_id'],
						'flg_type'=>$_type,
						'ext_category_id'=>(empty( $b['ext_category_id'] )? 0:$b['ext_category_id']),
					);
				}
			}
		}
	}

	// постинг в блоги рандомно
	// сделать чтобы последний пост был в блог отличный от того в который был первый пост
	private function randomQueue(){
		foreach( $this->_sites as $_type=>$_sites ){
			$_arrTmp=$_sites;
			foreach( $this->_contents as $c ){
				$_intKey=array_rand( $_arrTmp, 1 );
				$this->_records[]=array(
					'project_id'=>$this->_projectId,
					'title'=>$c['title'],
					'body'=>$c['fields'],
					'site_id'=>$_arrTmp[$_intKey]['site_id'],
					'flg_type'=>$_type,
					'ext_category_id'=>(empty( $_arrTmp[$_intKey]['ext_category_id'] )? 0:$_arrTmp[$_intKey]['ext_category_id']),
				);
				unSet( $_arrTmp[$_intKey] );
				if ( empty( $_arrTmp ) ){
					$_arrTmp=$_sites;
				}
			}
		}
	}

	/*
	// проверить чтобы все данные были в $this->_project
	0 - 0
	1 - 0+10 (10) так как для второго поста рандомный фактор необязателен
	2 - 10+10+5 (25)
	3 - 25 +10+5 (40)
	4 - 40 +10+5 (55)
	*/
	private function scheduling(){
		$_intRecordCount=count( $this->_records );
		$this->_project['time_between']=empty( $this->_project['time_between'] )? 0:$this->_project['time_between'];
		$this->_project['random']=empty( $this->_project['random'] )? 0:$this->_project['random'];
		for( $_int=0; $_int<$_intRecordCount; $_int++ ){
			if ( $_int==0 ){
				$this->_records[$_int]['start']=$this->_time;
			} elseif ( $_int==1 ){
				$this->_records[$_int]['start']=$this->_time=$this->_time+$this->_project['time_between']*60;
			} else {
				$this->_records[$_int]['start']=$this->_time=$this->_time+$this->_project['time_between']*60+$this->_project['random']*60;
			}
		}
	}

	// вызывать из крон скрипта если в проекте есть нетворкинг
	// перед этим надо сделать проект комплитед, чтобы небыло возможности добавлять дополнительный контент
	public function generateNetworking( &$arrRes ){
		if ( !$this->withOrder( 'd.site_id--dn' )->getList( $this->_records )->checkEmpty() ){
			return false;
		}
		$_bool1=$this->masterBlog();
		$_bool2=$this->circular();
		if ( !$_bool1&&!$_bool2 ){ // это означает что нетворкинга нет
			return false;
		}
		$this->store();
		$arrRes=$this->_records;
		return true;
	}

	// все статьи на ведомых линкуются к статьям на местер + статьи должны быть разными
	private function masterBlog(){
		if ( empty( $this->_project['flg_mastersite'] )||empty( $this->_project['mastersite_id'] ) ){
			return false;
		}
		$_arrMaster=$_arrSlave=array();
		foreach( $this->_records as $k=>$v ){
			if ( $v['site_id']==$this->_project['mastersite_id'] ){
				$_arrMaster[$k]=$v;
			} else {
				$_arrSlave[$k]=$v;
			}
		}
		$_arrTmp=$_arrMaster;
		foreach( $_arrSlave as $k=>$v ){
			if ( empty( $_arrTmp ) ){
				$_arrTmp=$_arrMaster;
			}
			foreach( $_arrTmp as $i=>$m ){
				if ( $v['body']!=$m['body'] ){ // интересно будет-ли тормозить? ), как вариант сделать сравнение md5 сумм
					$this->_records[$k]['link_to_master']=$m['id'];
					unSet( $_arrTmp[$i] );
					break;
				}
				unSet( $_arrTmp[$i] );
				if ( empty( $_arrTmp ) ){
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
	private function circular(){
		if ( empty( $this->_project['flg_circular'] ) ){
			return false;
		}
		$_tempArr=$this->_records;
		$_arrRrecipient=array();
		foreach ( $_tempArr as $k=>&$v ){
			if( $v['sort'] != 1 ){
				$_arrRrecipient[]=$v;
				$v['sort']=1;
				foreach ( $_tempArr as $k2=>&$v2 ){
					if ( $v['site_id']!=$v2['site_id'] && $v['title']!=$v2['title'] && $v2['sort'] != 1){
						$_arrRrecipient[]=$v2;
						$v2['sort']=1;
						break;
					}
				}
			}
		}
		unset($_tempArr);
		$this->_records=$_arrRrecipient;
		while( !empty( $_arrRrecipient ) ){
			if ( !isSet( $k1 ) ){ // случайным образом выбираем первый элемнт кольцевой цепочки
				$k1=$_intFirstKey=0;
				unSet( $_arrRrecipient[$k1] ); // удаляем из элементов на которые можно делать ссылки (для того чтобы на этот элемент сделать ссылку с последнего)
				$v1=$this->_records[$k1];
			}
			
			$found=false;
			foreach( $_arrRrecipient as $k2=>$v2 ){
				if ( $v1['site_id']!=$v2['site_id']&&$v1['title']!=$v2['title'] ){
					$this->_records[$k1]['link_to']=$v2['id']; // делаем ссылку
					unSet( $_arrRrecipient[$k2] ); // удаляем из элементов на которые можно делать ссылки
					$_intLastKey=$k1=$k2; // теперь будем искать ссылку для элемента на который сделали ссылку
					$v1=$v2;
					$found=true;
				}
			}
			if ( !$found ){ // для случая когда невозможно найти ссылку для элемента (например оба элемента размещены на одном и том же блоге)
				break;
			}
		}
		$this->_records[$_intLastKey]['link_to']=$this->_records[$_intFirstKey]['id'];
		return true;
	}

	public function set(){
		$this->_data->setFilter( array( 'trim', 'clear' ) );
		$this->_data->setElement( 'id', Core_Sql::setInsertUpdate( $this->table, $this->_data->setMask( $this->fields )->getValid() ) );
		return true;
	}

	// настройки для getList
	protected $_withOrder='d.start--up'; // c сортировкой
	private $_withTime=0; // записи старше или равные данного таймстампа
	private $_onlyPosted=false; // только те которые удалось запостить
	private $_onlyNonPosted=false; // только те которые ещё не запощены

	protected function init(){
		parent::init();
		$this->_withOrder='d.start--up';
		$this->_withTime=0;
		$this->_onlyPosted=false;
		$this->_onlyNonPosted=false;
	}

	public function withTime( $_int=0 ){
		$this->_withTime=$_int;
		return $this;
	}

	public function onlyPosted(){
		$this->_onlyPosted=true;
		return $this;
	}

	public function onlyNonPosted(){
		$this->_onlyNonPosted=true;
		return $this;
	}

	public function withGroup( $_mix=array() ){
		$this->_withGroup=$_mix;
		return $this;
	}

	protected function assemblyQuery(){
		if ( empty( $this->_withGroup ) ){
			$this->_crawler->set_select( 'd.*' );
		} else {
			$this->_crawler->set_select( 'd.site_id, d.title, d.ext_category_id' );
		}
		$this->_crawler->set_from( $this->table.' d' );
		$this->_crawler->set_where( 'd.project_id='.$this->_projectId );
		if ( $this->_onlyPosted ){
			$this->_crawler->set_where( 'd.flg_status=1' );
		}
		if ( $this->_onlyNonPosted ){
			$this->_crawler->set_where( 'd.flg_status=0' );
		}
		if ( !empty( $this->_withIds ) ){
			$this->_crawler->set_where( 'd.id IN ('.Core_Sql::fixInjection( $this->_withIds ).')' );
		}
		if ( !empty( $this->_withTime ) ){
			$this->_crawler->set_where( 'd.start<='.$this->_withTime );
		}
		$this->_crawler->set_order_sort( $this->_withOrder );
		if ( !empty( $this->_withGroup ) ){
			$this->_crawler->set_group( $this->_withGroup );
		}
	}

	public function del( $_tmp=array() ){
		Core_Sql::setExec( 'DELETE FROM '.$this->table.' WHERE project_id='.$this->_projectId );
	}

	private function store(){
		if ( empty( $this->_records ) ){
			return false;
		}
		foreach( $this->_records as $_item ){
			if( empty($_item) || empty($_item['title']) ){
				continue;
			}
			Core_Sql::setInsertUpdate( $this->table, $_item );
		}
		return true;
	}
}
?>