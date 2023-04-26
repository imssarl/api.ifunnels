<?php
class Project_Squeeze_Subscribers extends Core_Data_Storage{

	protected $_withSqueezeId=array(); // c данными popup id
	protected $_withEmail=array(); // c данными email
	protected $_withTags=false; // с тегами

	protected $_table='s8rs_';
	protected $_userId=false;

	public function __construct( $_uid=false ){
		if( $_uid !== false ){
			$this->_userId=$_uid;
			$this->_table=$this->_table.$_uid;
		}
	}
	
	public function withSqueezeId( $_arrIds=array() ) {
		if( is_array( $_arrIds ) ){
			$this->_withSqueezeId=$_arrIds;
		}else{
			$this->_withSqueezeId=array( $_arrIds );
		}
		return $this;
	}

	public function withTags( $_tags ){
		$this->_withTags = array_filter( explode( ',', trim( Project_Tags::check( $_tags ), ',' ) ) );
		return $this;
	}
	
	public function withEmail( $_arrIds=array() ) {
		$this->_withEmail=$_arrIds;
		return $this;
	}
	
	public function withUserId( $_arrIds=array() ) {
		$this->_userId=$_arrIds;
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
		$this->_withPaging['href']='?page=';
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
		return $this;
	}
	
	public function getList( &$mixRes ){
		if( empty( $this->_userId ) ){
			$this->_userId=Core_Users::$info['id'];
		}
		$page='';
		if( !empty( $this->_withPaging ) ){
			$page=' LIMIT '.( $this->_withPaging['url']['page']>1?( ( $this->_withPaging['url']['page']-1 )*$this->_withPaging['reconpage'] ).','.$this->_withPaging['reconpage'] : $this->_withPaging['reconpage'] );
		}
		$_tags='';
		if ( !empty( $this->_withTags ) ) {
			$_moreLikes=array();
			foreach( $this->_withTags as $_tagN ){
				if( !empty( $_tagN ) ){
					$_moreLikes[]= 'd.tags LIKE \'%,'.$_tagN.',%\'';
				}
			}
			if( !empty( $_moreLikes ) ){
				$_tags=' AND ('.implode( ' OR ', $_moreLikes ).')';
			}
		}
		$_where=array();
		if( !empty( $this->_withSqueezeId ) ){
			$_where[]='( name="lead_id" AND value IN ('.Core_Sql::fixInjection( array_filter( $this->_withSqueezeId ) ).') )';
		}
		if ( !empty( $this->_withEmail ) ){
			$_emails=' AND d.email IN ('.Core_Sql::fixInjection( $this->_withEmail ).') ';
		}
		try{
			Core_Sql::setConnectToServer( 'lpb.tracker' );
			//========
			$_eventIds='';
			if( !empty( $_where ) ){
				$_arrEventIds=Core_Sql::getField( 'SELECT event_id FROM s8rs_parameters_'.$this->_userId.' WHERE '.implode( ' AND ', $_where ) );
				$_eventIds=' AND e.id IN ("'.implode( '","', $_arrEventIds).'")';
			}
			$this->_withPaging['rowtotal']=count( Core_Sql::getField( 'SELECT COUNT(*) FROM s8rs_'.$this->_userId.' d JOIN s8rs_events_'.$this->_userId.' e ON d.id=e.sub_id WHERE e.event_type='.Project_Subscribers_Events::LEAD_FORM.$_eventIds.$_tags.$_emails.' GROUP BY d.email ' ) );
			$mixRes=Core_Sql::getAssoc( 'SELECT d.id, d.email, d.ip, d.tags, d.name, d.settings, d.added FROM s8rs_'.$this->_userId.' d JOIN s8rs_events_'.$this->_userId.' e ON d.id=e.sub_id WHERE e.event_type='.Project_Subscribers_Events::LEAD_FORM.$_eventIds.$_tags.$_emails.' GROUP BY d.email ORDER BY d.added DESC'.$page );
			$_subIds=array();
			foreach( $mixRes as $_data ){
				$_subIds[$_data['id']]=$_data['id'];
			}
			$_arrEvents=Core_Sql::getAssoc( 'SELECT d.id, d.sub_id, d.added, e.name, e.value FROM s8rs_events_'.$this->_userId.' d JOIN s8rs_parameters_'.$this->_userId.' e ON d.id=e.event_id WHERE d.sub_id IN ("'.implode( '","', $_subIds ).'")' );
			//========
			Core_Sql::renewalConnectFromCashe();
		} catch(Exception $e) {
			Core_Sql::renewalConnectFromCashe();
			return $this;
		}
		$_tagsIds=array();
		foreach( $mixRes as &$_data ){
			foreach( $_arrEvents as $_event ){
				if( $_data['id'] == $_event['sub_id'] ){
					if( !isset( $_data['squeeze_events'] ) ){
						$_data['squeeze_events']=array($_event['id']=>array('added'=>$_event['added']));
					}
					if( !isset( $_data['squeeze_events'][$_event['id']] ) ){
						$_data['squeeze_events'][$_event['id']]=array('added'=>$_event['added']);
					}
					if( $_event['name'] == 'message' ){
						$_data['squeeze_events'][$_event['id']]['message']=unserialize( base64_decode( $_event['value'] ) );
					}else{
						$_data['squeeze_events'][$_event['id']][$_event['name']]=$_event['value'];
//						$_data['squeeze_events'][$_event['id']]['param'][$_event['name']]=$_event['value'];
					}
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
		if( !empty( $_tagsIds ) ){
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
		$_paging=$this->_withPaging;
		$this->init();
		$this->_withPaging=$_paging;
		return $this;
	}
	
	public function set() {
		$this->_data->setFilter( array( 'clear' ) );
		$_s8rObj=new Project_Subscribers( $this->_userId );
		// не забыть обработать тэги
		if( isset( $this->_data->filtered['tags'] ) && !empty( $this->_data->filtered['tags'] ) ){
			if(Core_Acs::haveAccess( array( 'Automate' ) )){
				Project_Automation::setEvent( Project_Automation_Event::$type['CONTACT_TAGGED'] , $this->_data->filtered['tags'], $this->_data->filtered['email'], array() );
			}
			$this->_data->setElement( 'tags', Project_Tags::set( $this->_data->filtered['tags'] ) );
			$this->_data->setFilter( array( 'clear' ) );
		}
		$_s8rObj->setEntered( $this->_data->filtered )->set();
		$_s8rObj->getEntered( $this->_data->filtered );
		if(Core_Acs::haveAccess( array( 'Automate' ) )){
			Project_Automation::setEvent( Project_Automation_Event::$type['CONTACT_CREATED'] , true, $this->_data->filtered['email'], array() );
			Project_Automation::setEvent( Project_Automation_Event::$type['CONTACT_ADDED_LC'] , $this->_data->filtered['squeeze_id'], $this->_data->filtered['email'], array() );
		}
		$_eventObj=new Project_Subscribers_Events( $this->_userId );
		$_arrEvent=array(
			'sub_id'=>$this->_data->filtered['id'],
			'event_type'=>Project_Subscribers_Events::LEAD_FORM,
			'added'=>$this->_data->filtered['added'],
			'param'=>array(
				'lead_id' => $this->_data->filtered['squeeze_id']
			),
		);
		if( isset( $this->_data->filtered['param'] ) ){
			$_arrEvent['param']=$this->_data->filtered['param']+$_arrEvent['param'];
		}
		if( isset( $this->_data->filtered['squeeze_id'] ) ){
			$_arrEvent['param']['lead_id']=$this->_data->filtered['squeeze_id'];
		}
		if( isset( $this->_data->filtered['message'] ) ){
			$_arrEvent['param']['message']=$this->_data->filtered['message'];
		}
		if( isset( $this->_data->filtered['options'] ) ){
			$_arrEvent['param']['options']=$this->_data->filtered['options'];
		}
		$_eventObj->setEntered( $_arrEvent )->set();
		$this->init();
		return $this;
	}
	
	protected function init() {
		parent::init();
		$this->_withSqueezeId=array();
		$this->_withEmail=false;
	}
	
	
	public function del(){
		$_strWith=array();
		if ( !empty( $this->_withIds ) ){
			$_strWith[]='id IN ('.Core_Sql::fixInjection( $this->_withIds ).')';
		}
		if( empty( $_strWith ) ){
			$this->init();
			return false;
		}
		try {
			Core_Sql::setConnectToServer( 'lpb.tracker' );
			//========
			Core_Sql::setExec( 'DELETE FROM '.$this->_table.' WHERE '.implode( ' and ', $_strWith ) );
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