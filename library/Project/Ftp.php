<?php
class Project_Ftp extends Core_Media_Ftp {

	private $_data;

	public $_userId=0;
	private $_table='hct_ftp_details_tb';
	private $_fields=array( 'id', 'ftp_address', 'ftp_username', 'ftp_password', 'user_id' );

	public function __construct() {
		if ( !Zend_Registry::get( 'objUser' )->getId( $_int ) ) {
			throw new Exception( Core_Errors::DEV.'|no _userId set' );
			return;
		}
		$this->_userId=$_int;
	}

	public function browse( &$arrRes, $_mode=Core_Media_Ftp::LS_DIRS_ONLY ) {
		if ( !$this->_data->setFilter()->setChecker( array(
			'ftp_host'=>empty( $this->_data->filtered['ftp_host'] ),
			'ftp_username'=>empty( $this->_data->filtered['ftp_username'] ),
			'ftp_password'=>empty( $this->_data->filtered['ftp_password'] ),
		) )->check() ) {
			$this->_data->getErrors( $this->_errors );
			return false;
		}
		if ( !$this
			->setChmod( '0755' )
			->setHost( urldecode( $this->_data->filtered['ftp_host'] ) )
			->setUser( urldecode( $this->_data->filtered['ftp_username'] ) )
			->setPassw( urldecode( $this->_data->filtered['ftp_password'] ) )
			->makeConnect() ) {
			return false;
		}
		$_strDir=empty($this->_data->filtered['directory'])?'':$this->_data->filtered['directory'];
		if ( !empty( $_strDir )&&$_mode!=Core_Media_Ftp::LS_DIRS_ONLY ) {
			$_strDir=explode( '/', $_strDir );
			unset( $_strDir[count( $_strDir )-1] );
			$_strDir=implode( '/', $_strDir );
		}
		/*if ( $_mode!=Core_Media_Ftp::LS_DIRS_ONLY ) {
			$_strDir=$this->getPrev( $_strDir );
		}*/
		return $this->dirForLs( $_strDir )->ls( $arrRes, $_mode );
	}

	// dir/dir/file -> /dir/dir/ | /dir/dir -> /dir/ | /dir -> '' etc.
	public function getPrev( $_strDir='' ) {
		if ( empty( $_strDir ) ) {
			return '';
		}
		$_arrDir=explode( '/', $_strDir );
		if ( empty( $_arrDir[count( $_arrDir )-1] ) ) {
			unSet( $_arrDir[count( $_arrDir )-1] );
		}
		if ( empty( $_arrDir[0] ) ) {
			unSet( $_arrDir[0] );
		}
		$_arrDir=array_values( $_arrDir );
		unset( $_arrDir[count( $_arrDir )-1] );
		if ( empty( $_arrDir ) ) {
			return '';
		}
		return '/'.implode( '/', $_arrDir ).'/';
	}

	public function getPrevDir( &$strRes ) {
		$this->getForLsDir( $_strDir );
		$_strDir=$this->getPrev( $_strDir );
		$strRes='directory='.$_strDir;
		return true;
		if ( empty( $_strDir ) ) {
		$strRes='directory='.$_strDir;
			return false;
		}
		$strRes='directory='.$_strDir;
	}

	// закрываем соидинение в любом случае
	public function makeDirAndClose( $_strNewDir='' ) {
		if ( empty( $_strNewDir ) ) {
			$this->closeConnection();
			return false;
		}
		$this->getForLsDir( $_strDir );
		$_bool=$this->makeDir( $_strDir.$_strNewDir );
		$this->closeConnection();
		return $_bool;
	}

	public function setData( $_arrData=array() ) {
		$this->_data=new Core_Data( $_arrData );
		return $this;
	}

	public function getEntered( &$arrRes ) {
		$arrRes=$this->_data->getFiltered();
		return $this;
	}

	public function getErrors( &$arrRes ) {
		$arrRes=$this->_errors;
		return $this;
	}
	
	public function import(){ 
		$this->_data->setFilter(array('striptags'));
		Core_Files::getContent($_strContent,$this->_data->filtered['tmp_name']);
		preg_match_all('/(?P<host>.*?),\s(?P<user>.*?),\s(?P<password>.*?)(\n|$)/i',$_strContent,$_arr);
		if (empty($_arr['host'])){
			$this->_errors['format']=true;
			return false;
		}
		$_arrData=array();
		foreach ($_arr['host'] as $_key=>$_host){
			$_data=array(
				'ftp_address'=>$_host,
				'ftp_username'=>$_arr['user'][$_key],
				'ftp_password'=>$_arr['password'][$_key],
				'user_id'=>$this->_userId,
				);
				
			if ( $this->withUsername( $_data['ftp_username'] )
			->withPassword( $_data['ftp_password'] )
			->withAddress( $_data['ftp_address'] )
			->onlyOne()
			->getList( $_arrRes )){
				continue;
			}
			Core_Sql::setInsertUpdate( $this->_table, $_data );
		}
		return true;
	}

	public function set() {
		if ( !$this->_data->setFilter()->setChecker( array(
			'ftp_address'=>empty( $this->_data->filtered['ftp_address'] ),
			'ftp_username'=>empty( $this->_data->filtered['ftp_username'] ),
			'ftp_password'=>empty( $this->_data->filtered['ftp_password'] ),
		) )->check() ) {
			$this->_data->getErrors( $this->_errors );
			return false;
		}
		// проверка существования такой записи
		if ( empty( $this->_data->filtered['id'] )&&$this->withUsername( $this->_data->filtered['ftp_username'] )
			->withPassword( $this->_data->filtered['ftp_password'] )
			->withAddress( $this->_data->filtered['ftp_address'] )
			->onlyOne()
			->getList( $arrRes ) ) {
			$this->_errors['exists']=true;
			return false;
		}
		// теситруем фтп на доступность
		if ( !$this
			->setHost( $this->_data->filtered['ftp_address'] )
			->setUser( $this->_data->filtered['ftp_username'] )
			->setPassw( $this->_data->filtered['ftp_password'] )
			->makeConnect() ) {
			$this->_errors['connect']=true;
			return false;
		}
		if ( empty( $this->_data->filtered['id'] ) ) {
			$this->_data->setElement( 'user_id', $this->_userId );
		}
		$_intId=Core_Sql::setInsertUpdate( $this->_table, $this->_data->setMask( $this->_fields )->getValid() );
		return !empty( $_intId );
	}

	public function del( $_mix=0 ) {
		if ( empty( $_mix ) ) {
			return false;
		}
		Core_Sql::setExec( 'DELETE FROM '.$this->_table.' WHERE id IN ('.Core_Sql::fixInjection( $_mix ).') AND user_id="'.$this->_userId.'" LIMIT '.count( $_mix ) );
		return true;
	}

	private $_toSelect=false;
	private $_toJson=false;
	private $_onlyOne=false;
	private $_withIds=0; // c данными id
	private $_withUsername='';
	private $_withPassword='';
	private $_withAddress='';
	private $_withPagging=array(); // постранично
	private $_withOrder='ftp_address--up'; // c сортировкой
	private $_paging=array(); // инфа по навигации
	private $_cashe=array(); // закэшированный фильтр

	private function init() {
		$this->_toSelect=false;
		$this->_toJson=false;
		$this->_onlyOne=false;
		$this->_withIds=0;
		$this->_withUsername='';
		$this->_withPassword='';
		$this->_withAddress='';
		$this->_withPagging=array();
		$this->_withOrder='ftp_address--up';
	}

	// array or int
	public function withIds( $_mixId=0 ) {
		$this->_withIds=$_mixId;
		return $this;
	}

	public function withPagging( $_arr=array() ) {
		$this->_withPagging=$_arr;
		return $this;
	}

	public function withOrder( $_str='' ) {
		if ( !empty( $_str ) ) {
			$this->_withOrder=$_str;
		}
		$this->_cashe['order']=$this->_withOrder;
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

	public function toSetect() {
		$this->_toSelect=true;
		return $this;
	}

	public function toJson() {
		$this->_toJson=true;
		return $this;
	}

	public function withUsername( $_str='' ) {
		$this->_withUsername=$_str;
		return $this;
	}

	public function withPassword( $_str='' ) {
		$this->_withPassword=$_str;
		return $this;
	}

	public function withAddress( $_str='' ) {
		$this->_withAddress=$_str;
		return $this;
	}

	public function onlyOne() {
		$this->_onlyOne=true;
		return $this;
	}

	public function getList( &$mixRes ) {
		$_crawler=new Core_Sql_Qcrawler();
		if ( $this->_toSelect ) {
			$_crawler->set_select( 'id, CONCAT(ftp_address," (",ftp_username,")") title' );
		} else {
			$_crawler->set_select( '*' );
		}
		$_crawler->set_from( $this->_table );
		$_crawler->set_where( 'user_id="'.$this->_userId.'"' );
		if ( !empty( $this->_withUsername ) ) {
			$_crawler->set_where( 'ftp_username="'.$this->_withUsername.'"' );
		}
		if ( !empty( $this->_withPassword ) ) {
			$_crawler->set_where( 'ftp_password="'.$this->_withPassword.'"' );
		}
		if ( !empty( $this->_withAddress ) ) {
			$_crawler->set_where( 'ftp_address="'.$this->_withAddress.'"' );
		}
		if ( !empty( $this->_withIds ) ) {
			$_crawler->set_where( 'id IN ('.Core_Sql::fixInjection( $this->_withIds ).')' );
		}
		$_crawler->set_order_sort( $this->_withOrder );
		$_crawler->get_result_full( $_strSql );
		if ( $this->_toSelect ) {
			$mixRes=Core_Sql::getKeyVal( $_strSql );
		} elseif ( $this->_onlyOne ) {
			$mixRes=Core_Sql::getRecord( $_strSql );
		} elseif ( $this->_toJson ) {
			$mixRes=Zend_Registry::get( 'CachedCoreString' )->php2json( Core_Sql::getKeyRecord( $_strSql ) );
		} else {
			$mixRes=Core_Sql::getAssoc( $_strSql );
		}
		$this->init();
		return !empty( $mixRes );
	}
}
?>