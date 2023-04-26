<?php


/**
 * Class to archive database snapshots
 */
class Core_Sql_Backup {

	private $current_teble='';
	
	public function setTable( $_strTable ){ 
		if (empty($_strTable)){
			return false;
		}
		$this->current_teble=$_strTable;
		return $this;
	}
	public function b_get_table_columns(){
		$arrColumns=Core_Sql::getField('SHOW COLUMNS FROM '.$this->current_teble);
		return $arrColumns;
	}
	private $_withPaging=array();
	protected $_paging=array(); 
		
	public function init() {
		$this->_withPaging=array();
	}
	
	public function withPagging( $_arr=array() ) {
		$this->_withPagging=$_arr;
		return $this;
	}
	
	public function getPaging(&$arrRes){
		$arrRes=$this->_paging;
		$this->_paging=array();
		return $this;
	}
		
	public function b_view_table(){
		$_crawler=new Core_Sql_Qcrawler();		
		$_crawler->set_select( 'd.*' );
		$_crawler->set_from( $this->current_teble.' d' );		
		$this->_withPagging['rowtotal']=Core_Sql::getCell( $_crawler->get_result_counter( $_strTmp ) );
		$_crawler->set_paging( $this->_withPagging )->get_sql( $_strSql, $this->_paging );
		$arrList=Core_Sql::getAssoc($_strSql);
		return $arrList;
	}
	
	function b_get_dumps_list( &$arrRes ) {
		if ( !Core_Files::dirScan( $_arrDirScan, Zend_Registry::get( 'config' )->path->relative->db_backup, Core_Files::$fileInfo['withMTime'] ) ) {
			return false;
		}
		$arrRes=current( $_arrDirScan );
		if ( empty( $arrRes ) ) {
			return false;
		}
		foreach( $arrRes as $v ) {
			$_arrName[]=$v['name'];
		}
		// сортируем по имени файла - внести этот функционал в Core_Files::dirScan TODO!!!
		return array_multisort( $_arrName, SORT_ASC, $arrRes );
	}

	function b_set_dump( $_strFileName='' ) {
		if ( empty( $_strFileName ) ) {
			return false;
		}
		if ( !Core_Files::getContent( $_strSql, Zend_Registry::get( 'config' )->path->relative->db_backup.$_strFileName ) ) {
			return false;
		}
		$_arrSql=explode( '+_+::+_+', str_replace( array( "\r", ";\n", "\n" ), array( '', '+_+::+_+', '' ), $_strSql ) );
		foreach( $_arrSql as $v ) {
			if ( empty( $v )||ord($v{0})==35 ) {
				continue;
			}
			Core_Sql::setExec( $v );
		}
		return true;
	}

	function b_del_dump( $_strFileName='' ) {
		if ( empty( $_strFileName ) ) {
			return false;
		}
		return Core_Files::rmFile( Zend_Registry::get( 'config' )->path->relative->db_backup.$_strFileName );
	}

	function b_create_dump( $_arrSet=array() ) {
		if ( empty( $_arrSet['tables'] ) ) {
			return false;
		}
		$_strTime=date('Y_m_d_H_i_s', time());
		$strContent="\n# dump of ".Zend_Registry::get( 'config' )->engine->project_domain." project database at ".$_strTime.".;\n\n";
		$strContent.="SET NAMES ".Zend_Registry::get( 'config' )->database->codepage.";\n\n";
		foreach( $_arrSet['tables'] as $v ) {
			$_arrRes=$_arrCont=array();
			$this->current_table=$v;
			$strContent.="\n# structure of ".$v." table;\n\n";
			$strContent.=stripslashes( 'DROP TABLE IF EXISTS '.$v.";\n".$this->b_get_table_create( $v )."\n" );
			if ( $this->b_get_table_content( $_arrCont ) ) {
				$strContent.="\n\n# content of ".$v." table;\n\n";
				$strContent.=join( "\n", $_arrCont );
			}
			$strContent.="\n\n";
		}
		return Core_Files::setContent( $strContent, Zend_Registry::get( 'config' )->path->relative->db_backup.$_strTime.'.sql' );
	}

	function b_get_table_create() {
		$_arr=Core_Sql::getRecord( 'SHOW CREATE TABLE '.$this->current_table );
		if ( empty( $_arr['Create Table'] ) ) {
			return '';
		}
		return $_arr['Create Table'].';';
	}

	// сделать сортировку по автоинкременто полю если таковое присутствует TODO!!!
	function b_get_table_content( &$arrRes ) {
		$_arrRow=Core_Sql::getAssoc( 'SELECT * FROM '.$this->current_table );
		if ( empty( $_arrRow ) ) {
			return false;
		}
		foreach( $_arrRow as $r ) {
			$_arrFld=array();
			$_strIns='INSERT INTO '.$this->current_table.' VALUES (';
			foreach( $r as $v ) {
				if ( !isSet( $v ) ) {
					$_arrFld[]='NULL';
				} elseif ( $v!='' ) {
					$_arrFld[]="'".str_replace("\n", '\n', addslashes($v))."'";
				} else {
					$_arrFld[]="''";
				}
			}
			$arrRes[]=$_strIns.join( ',', $_arrFld ).');';
		}
		return !empty( $arrRes );
	}

	public function b_get_db_tables( &$arrRes ) {
		$arrRes=Core_Sql::getField( 'SHOW TABLES' );
		return !empty( $arrRes );
	}
}
?>