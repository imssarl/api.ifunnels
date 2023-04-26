<?php
class Project_Automation_Subscribers extends Core_Data_Storage{
	protected $_withEmail=array();
	protected $_withAutoId=false;
	protected $_withTags=false;
	protected $_withoutTags=false;
	protected $_withStatus=false;
	protected $_onlyEmails=false;
	protected $_withStatusMessage=false;
	
	protected $_userId=false;

	public function __construct( $_uid=false ){
		if( $_uid !== false ){
			$this->_userId=$_uid;
		}
	}
	
	public function withStatus(){
		$this->_withStatus=true;
		return $this;
	}
	
	public function onlyEmails(){
		$this->_onlyEmails=true;
		return $this;
	}
	
	public function withEmail( $_arrIds=array() ){
		$this->_withEmail=$_arrIds;
		return $this;
	}
	
	public function withAutoId( $_varIds ){
		$this->_withAutoId = $_varIds;
		return $this;
	}

	public function withTags( $_tags ){
		$this->_withTags = array_filter( explode( ',', trim( Project_Tags::check( $_tags ), ',' ) ) );
		return $this;
	}

	public function withoutTags( $_tags ){
		$this->_withoutTags=true;
		return $this;
	}

	public function withStatusMessage( $status ){
		if( !empty( $status ) ){
			$this->_withStatusMessage=$status;			
		}
		return $this;
	}

	protected function init(){
		$this->_withEmail=false;
		$this->_withAutoId=false;
		$this->_withTags=false;
		$this->_withoutTags=false;
		$this->_withStatus=false;
		$this->_onlyEmails=false;
	}
	
	public function set() {
		$this->_data->setFilter( array( 'clear' ) );
		$_s8rObj=new Project_Subscribers( $this->_userId );
		$_s8rObj->setEntered( $this->_data->filtered )->set();
		$_s8rObj->getEntered( $this->_data->filtered );
		$_eventObj=new Project_Subscribers_Events( $this->_userId );
		$_arrEvent=array(
			'sub_id'=>$this->_data->filtered['id'],
			'event_type'=>Project_Subscribers_Events::AUTOMATION,
			'added'=>$this->_data->filtered['added'],
			'param'=>array(
				'auto_id' => $this->_data->filtered['auto_id']
			),
		);
		$_eventObj->setEntered( $_arrEvent )->set();
		return $this;
	}
	
	protected $_withSortOrder=' ORDER BY d.added DESC';
	
	public function withOrder( $_str='' ){
		if ( !empty( $_str ) ){
			$this->_withSortOrder=$_str;
		}else{
			return $this;
		}
		$this->_cashe['order']=$this->_withSortOrder;
		if ( !is_array( $this->_withSortOrder ) ) {
			$_arrOrd=array( $this->_withSortOrder );
		}
		foreach( $_arrOrd as $v ) {
			if ( $v=='rand' ) {
				$this->_withSortOrder=' ORDER BY RAND()';
			}else{
				$_arrPrt=explode( '--', $v );
				$this->_withSortOrder=' ORDER BY '.$_arrPrt[0].' '.( ( $_arrPrt[1]=='up' ) ? 'DESC':'ASC' );
			}
		}
		return $this;
	}

	public function withPaging( $_arr=array() ){
		$this->_withPaging=$_arr;
		if( !isset( $this->_withPaging['reconpage'] ) || empty( $this->_withPaging['reconpage'] ) ){
			$this->_withPaging['reconpage']=Zend_Registry::get( 'config' )->database->paged_select->row_in_page;
		}
		if( !isset( $this->_withPaging['numofdigits'] ) || empty( $this->_withPaging['numofdigits'] ) ){
			$this->_withPaging['numofdigits']=Zend_Registry::get( 'config' )->database->paged_select->num_of_digits;
		}
		if( !isset( $this->_withPaging['url']['page'] ) || empty( $this->_withPaging['url']['page'] ) ){
			$this->_withPaging['url']['page']=1;
		}
		return $this;
	}

	public function getPaging( &$arrRes ){
		$arrRes=array();
		if ( $this->page>1 ) { // у нас не первая страница
			$this->_withPaging['rec_from']=( ( $this->page-1 )*$this->_withPaging['reconpage'] )+1;
			$_intTest=$this->_withPaging['rec_from']+$this->_withPaging['reconpage']-1;
			$this->_withPaging['rec_to']=$this->_withPaging['rowtotal']>$_intTest?$_intTest:$this->_withPaging['rowtotal'];
		} else { // первая страница
			$this->_withPaging['rec_from']=1;
			$this->_withPaging['rec_to']=$this->_withPaging['rowtotal']>$this->_withPaging['reconpage']?$this->_withPaging['reconpage']:$this->_withPaging['rowtotal'];
		}
		$this->_withPaging['maxpage']=ceil( $this->_withPaging['rowtotal']/$this->_withPaging['reconpage'] );
		$arrRes['curpage']=$this->_withPaging['url']['page'];
		$arrRes['recall']=$this->_withPaging['rowtotal'];
		$arrRes['recfrom']=$this->_withPaging['rec_from'];
		$arrRes['recto']=$this->_withPaging['rec_to'];
		if ( !( $this->_withPaging['rowtotal']>$this->_withPaging['reconpage'] ) ) {
			return $this;
		}
		if( empty( array_diff_key( $this->_withPaging['url'], array( 'page' => '' ) ) ) ){
			$this->_withPaging['href'] = '?page=';
		} else {
			$this->_withPaging['href'] = '?' . http_build_query( array_diff_key( $this->_withPaging['url'], array( 'page' => '' ) ) ) .'&page=';
		}
		// calculate diapazon refaktoring TODO 04.12.2008
		$_intStart=$this->_withPaging['url']['page']-$this->_withPaging['numofdigits']/2;
		$_intEnd=$this->_withPaging['url']['page']+$this->_withPaging['numofdigits']/2;
		if ( $_intStart<1 ) {
			$_intStart=1;
			$_intEnd=$_intStart+$this->_withPaging['numofdigits'];
		}
		$_intEnd1=intVal( ( $this->_withPaging['rowtotal']-1 )/$this->_withPaging['reconpage'] );
		$_intEnd1++;
		if ( $_intEnd>$_intEnd1&&$_intStart>$_intEnd-$_intEnd1 ) {
			$_intEnd=$_intEnd1;
			$_intStart=$_intEnd-$this->_withPaging['numofdigits'];
		} elseif ( $_intEnd>$_intEnd1 ) {
			$_intEnd=$_intEnd1;
			$_intStart=1;
		}
		$arrRes['urlmin']=$this->_withPaging['href'].'1';
		if ( $this->_withPaging['url']['page']>$_intStart ){
			$arrRes['urlminus']=$this->_withPaging['href'].( $this->_withPaging['url']['page']-1 );
		}
		$b=0;
		for ( $a=intVal( $_intStart ); $a<=$_intEnd; $a++ ) {
			if ( $a==$this->_withPaging['url']['page'] ) $arrRes['num'][$b]['sel']=1;
			$arrRes['num'][$b]['url']=$this->_withPaging['href'].$a;
			$arrRes['num'][$b]['number']=$a;
			$b++;
		}
		if ( $this->_withPaging['url']['page']<$_intEnd ){
			$arrRes['urlplus']=$this->_withPaging['href'].( $this->_withPaging['url']['page']+1 );
		}
		$arrRes['urlmax']=$this->_withPaging['href'].$this->_withPaging['maxpage'];
		$arrRes['maxpage']=$this->_withPaging['maxpage'];
		$arrRes['href']=$this->_withPaging['href'];
		return $this;
	}
	
	public function getList( &$mixRes ) {
		if( empty( $this->_userId ) ){
			$this->_userId=Core_Users::$info['id'];
		}
		$page='';
		if( !empty( $this->_withPaging ) ){
			$page=' LIMIT '.( $this->_withPaging['url']['page']>1?( ( $this->_withPaging['url']['page']-1 )*$this->_withPaging['reconpage'] ).','.$this->_withPaging['reconpage'] : $this->_withPaging['reconpage'] );
		}
		$_whereMass=$_where=array();
		if ( !empty( $this->_withTags ) ) {
			$_moreLikes=array();
			foreach( $this->_withTags as $_tagN ){
				if( !empty( $_tagN ) ){
					$_moreLikes[]= 'd.tags LIKE \'%,'.$_tagN.',%\'';
				}
			}
			if( !empty( $_moreLikes ) ){
				$_whereMass[]='('.implode( ' OR ', $_moreLikes ).')';
			}
		}
		if( !empty( $this->_withAutoId ) ){
			$_where[]='( name="auto_id" AND value='.Core_Sql::fixInjection( $this->_withAutoId ).' )';
		}
		if( !empty( $this->_withStatusMessage ) ){
			$_where[]='( name="'.$this->_withStatusMessage.'" AND value<>"" )';
		}
		if ( !empty( $this->_withEmail ) ){
			$_whereMass[]='d.email IN ('.Core_Sql::fixInjection( $this->_withEmail ).')';
		}
		if ( !empty( $this->_withIds ) ){
			$_whereMass[]='d.id IN ('.Core_Sql::fixInjection( $this->_withIds ).')';
		}
		try{
			Core_Sql::setConnectToServer( 'lpb.tracker' );
			//========
			$fields=Core_Sql::getField( 'DESCRIBE s8rs_'.$this->_userId );
			if ( !in_array( 'status', $fields ) ){
				Core_Sql::setExec('ALTER TABLE s8rs_'.$this->_userId.' ADD COLUMN status VARCHAR(15) NOT NULL DEFAULT \'\'');
				Core_Sql::setExec('ALTER TABLE s8rs_'.$this->_userId.' ADD COLUMN status_data INT(11) UNSIGNED NOT NULL DEFAULT \'0\'');
			}
			if( !empty( $_where ) ){
				$_arrEventIds=array();
				foreach( $_where as $_swr ){
					$_arrNewEventIds=Core_Sql::getField( 'SELECT event_id FROM s8rs_parameters_'.$this->_userId.' WHERE '.$_swr );
					if( empty( $_arrEventIds ) ){
						$_arrEventIds=$_arrNewEventIds;
					}else{
						$_arrEventIds=array_intersect($_arrEventIds, $_arrNewEventIds);
					}
				}
				$_whereMass[]='e.id IN ("'.implode( '","', $_arrEventIds).'")';
			}
			
			$this->_withPaging['rowtotal']=count( Core_Sql::getField( 'SELECT 1 FROM s8rs_'.$this->_userId.' d JOIN s8rs_events_'.$this->_userId.' e ON d.id=e.sub_id'.(!empty($_whereMass)?' WHERE ':'').implode( ' AND ', $_whereMass ).' GROUP BY d.email' ) );
			
		//	p( 'SELECT d.id, d.email, d.ip, d.tags, d.name, d.settings, d.added, d.status, d.status_data FROM s8rs_'.$this->_userId.' d JOIN s8rs_events_'.$this->_userId.' e ON d.id=e.sub_id'.(!empty($_whereMass)?' WHERE ':'').implode( ' AND ', $_whereMass ).' GROUP BY d.email'.(!empty($this->_withSortOrder)?$this->_withSortOrder:'').$page );
			if ( !empty( $this->_onlyEmails ) ){
				$mixRes=Core_Sql::getField( 'SELECT d.email FROM s8rs_'.$this->_userId.' d JOIN s8rs_events_'.$this->_userId.' e ON d.id=e.sub_id'.(!empty($_whereMass)?' WHERE ':'').implode( ' AND ', $_whereMass ).' GROUP BY d.email'.(!empty($this->_withSortOrder)?$this->_withSortOrder:'').$page );
				$this->init();
				
				Core_Sql::renewalConnectFromCashe();
				
				return $this;
			}
			$mixRes=Core_Sql::getAssoc( 'SELECT d.id, d.email, d.ip, d.tags, d.name, d.settings, d.added, d.status, d.status_data FROM s8rs_'.$this->_userId.' d JOIN s8rs_events_'.$this->_userId.' e ON d.id=e.sub_id'.(!empty($_whereMass)?' WHERE ':'').implode( ' AND ', $_whereMass ).' GROUP BY d.email'.(!empty($this->_withSortOrder)?$this->_withSortOrder:'').$page );
			$_subIds=array();
			foreach( $mixRes as $_data ){
				$_subIds[$_data['id']]=$_data['id'];
			}
			$_arrEvents=Core_Sql::getAssoc( 'SELECT d.id, d.sub_id, d.added, e.name, e.value FROM s8rs_events_'.$this->_userId.' d JOIN s8rs_parameters_'.$this->_userId.' e ON d.id=e.event_id WHERE d.sub_id IN ("'.implode( '","', $_subIds ).'")' );
			//========
			Core_Sql::renewalConnectFromCashe();
		}catch(Exception $e) {
			Core_Sql::renewalConnectFromCashe();
			return $this;
		}
		$_tagsIds=$_combineEvents=array();
		foreach( $_arrEvents as $_key=>$_event ){
			if( !isset( $_combineEvents[$_event['sub_id']] ) ){
				$_combineEvents[$_event['sub_id']]=array($_event['id']=>array('added'=>$_event['added']));
			}
			if( !isset( $_combineEvents[$_event['sub_id']][$_event['id']] ) ){
				$_combineEvents[$_event['sub_id']][$_event['id']]=array('added'=>$_event['added']);
			}
			$_combineEvents[$_event['sub_id']][$_event['id']][$_event['name']]=$_event['value'];
			unset( $_arrEvents[$_key] );
		}
		unset( $_arrEvents );
		foreach( $mixRes as &$_data ){
			foreach( $_combineEvents as $_subId=>$_events ){
				if( $_data['id'] == $_subId ){
					$_data['efunnel_events']=$_events;
					unset( $_combineEvents[$_subId] );
				}
			}
			if( strpos( $_data['tags'], ',' ) !== false ){
				$_data['tags']=array_filter( explode( ',', trim( $_data['tags'], ',' ) ) );
			}
			if( !empty( $_data['tags'] ) && !in_array( $_data['tags'], $_tagsIds ) ){
				foreach( $_data['tags'] as $_tagId ){
					$_tagsIds[$_tagId]=$_tagId;
				}
			}
		}
		if( !empty( $_tagsIds ) && !$this->_withoutTags ){
			$_tags = Project_Tags::get( implode( ',', $_tagsIds ) );
			foreach( $mixRes as &$item ){
				foreach( $item['tags'] as &$_tagGetName ){
					foreach( $_tags as $_tagId=>$_tagName ){
						if( $_tagId == $_tagGetName ) {
							$_tagGetName=$_tagName;
						}
					}
				}
			}
		}
		$this->init();
		return $this;
	}

	public function setMass(){
		$this->_data->setFilter();
		try {
			Core_Sql::setConnectToServer( 'lpb.tracker' );
			//========
			$_addTime=$_efId=false;
			$_arrSend=$_arrEmails=$_arrValues=array();
			foreach( $this->_data->filtered as $_send ){
				if( isset( $_send['email'] ) && !empty( $_send['email'] ) ){
					$_arrEmails[$_send['email']]=$_send['email'];
				}
				foreach( array_keys( $_send ) as $_name ){
					if( $_name == 'auto_id' ){
						$_efId=$_send[$_name];
						continue;
					}
					if( $_name == 'start' ){
						$_addTime=$_send[$_name];
						continue;
					}
					if( !isset( $_arrValues[$_name] ) ){
						$_arrValues[$_name]='';
					}
					if( $_name == 'email' ){
						$_arrEmails[$_name]=$_name;
					}
				}
			}
			$_arrOldEmails=Core_Sql::getAssoc( 'SELECT d.id, d.email FROM s8rs_'.$this->_userId.' d WHERE email IN ('.Core_Sql::fixInjection( $_arrEmails ).')' );
			$_oldEmails=array();
			foreach( $_arrOldEmails as $_data ){
				$_oldEmails[$_data['id']]=$_data['email'];
			}
			foreach( $this->_data->filtered as $_send ){
				$_arrSender=$_arrValues;
				if( !in_array( $_send['email'], $_oldEmails ) ){
					foreach( array_keys( $_arrValues ) as $_checkKey ){
						if( isset( $_send[$_checkKey] ) ){
							$_arrSender[$_checkKey]=$_send[$_checkKey];
						}
					}
					$_arrSend[$_send['email']]=implode( '","', $_arrSender );
				}
			}
			if( !empty( $_arrSend ) ){
				Core_Sql::setExec( 'INSERT INTO s8rs_'.$this->_userId.' (`'.implode( '`,`', array_keys( $_arrValues  ) ).'`) VALUES ("'.implode( '"),("', $_arrSend ).'")' );
			}
			$_arrNewEmailsIds=Core_Sql::getField( 'SELECT d.id FROM s8rs_'.$this->_userId.' d WHERE email IN ('.Core_Sql::fixInjection( $_arrEmails ).')' );
			if( $_addTime === false ){
				$_addTime=time();
			}
			$_arrSend=array();
			foreach( $_arrNewEmailsIds as $_newId ){
				$_arrSend[]='("'.$_newId.'","'.Project_Subscribers_Events::AUTOMATION.'","'.$_addTime.'")';
			}
			Core_Sql::setExec( 'INSERT INTO s8rs_events_'.$this->_userId.' (`sub_id`,`event_type`,`added`) VALUES '.implode( ',', $_arrSend ) );
			if( $_efId!==false ){
				sleep( 2 );
				$_arrNewEventsIds=Core_Sql::getField( 'SELECT id FROM s8rs_events_'.$this->_userId.' WHERE added='.$_addTime );
				$_arrSend=array();
				foreach( $_arrNewEventsIds as $_newId ){
					$_arrSend[]='("'.$_newId.'","auto_id","'.$_efId.'")';
				}
				Core_Sql::setExec( 'INSERT INTO s8rs_parameters_'.$this->_userId.' (`event_id`,`name`,`value`) VALUES '.implode( ',', $_arrSend ) );
			}
			//========
			Core_Sql::renewalConnectFromCashe();
		} catch(Exception $e) {
			Core_Sql::renewalConnectFromCashe();
			$this->init();
			return false;
		}
		return true;
	}
	
	public function del(){
		$_strWith=array();
		if ( !empty( $this->_withEfunnelIds ) ){
			$_strWith[]='sender_id IN ('.Core_Sql::fixInjection( $this->_withEfunnelIds ).')';
		}
		if ( !empty( $this->_withEmail ) ){
			$_strWith[]='email IN ('.Core_Sql::fixInjection( $this->_withEmail ).')';
		}
		if( empty( $_strWith ) ){
			$this->init();
			return false;
		}

			$_writer=new Zend_Log_Writer_Stream( Zend_Registry::get('config')->path->absolute->logfiles.'EF_Contacts_Remove.log' );
			$_writer->setFormatter( new Zend_Log_Formatter_Simple("%timestamp% %priorityName% (%priority%): %message%\r\n") );
			$_logger=new Zend_Log( $_writer );
			$_logger->info('-------------Project_Automation_Subscribers---------------');
			$_logger->info(serialize($_SERVER));
			$_logger->info('DELETE FROM s8rs_'.$this->_userId.' WHERE '.implode( ' and ', $_strWith ));
			$_logger->info('-------------Project_Automation_Subscribers---------------');

		try {
			Core_Sql::setConnectToServer( 'lpb.tracker' );
			//========
			Core_Sql::setExec( 'DELETE FROM s8rs_'.$this->_userId.' WHERE '.implode( ' and ', $_strWith ) );
			//========
			Core_Sql::renewalConnectFromCashe();
		} catch(Exception $e) {
			Core_Sql::renewalConnectFromCashe();
			$this->init();
			return false;
		}
		$this->init();
		return true;
	}
}
?>