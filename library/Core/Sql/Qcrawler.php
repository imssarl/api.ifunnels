<?php


/**
 * Query crawler Class need to refactoring
 */
class Core_Sql_Qcrawler extends Core_Services {

	private $q_union=array(); // array of object
	private $page_setting=array();
	public $q_select=array();
	public $q_from=array();
	public $q_where=array();
	public $q_group=array();
	public $q_having=array();
	public $q_order=array();
	public $q_limit='';
	public $q_where_concat='AND';
	private $_bracketL='(';
	private $_bracketR=')';

	public $q_result_full='';
	public $q_result_counter='';
	public $q_keywordsearch_withlimit=true;

	public function __construct( $flg='' ) {
		$this->flg=$flg;
		$this->reconpage=Zend_Registry::get( 'config' )->database->paged_select->row_in_page;
		$this->numofdigits=Zend_Registry::get( 'config' )->database->paged_select->num_of_digits;
	}

	public function gen_union_full( &$strSql ) {
		if ( empty( $this->q_union ) ) {
			return false;
		}
		$_arrU=array();
		foreach( $this->q_union as $v ) {
			$v->get_result_full( $_arrU[] );
		}
		$this->q_result_full='('.join( ') UNION ALL (', $_arrU ).')';
		if ( !empty( $this->q_order ) ) {
			$this->q_result_full.=' ORDER BY '.join( ', ', $this->q_order );
		}
		if ( !empty( $this->q_limit ) ) {
			$this->q_result_full.=' LIMIT '.$this->q_limit;
		}
		$strSql=$this->q_result_full;
		return $strSql;
	}

	public function gen_union_counter() {
		if ( empty( $this->q_union ) ) {
			return false;
		}
		$_arrU=array();
		foreach( $this->q_union as $v ) {
			$v->gen_result_union_counter( $_arrU[] );
		}
		$this->q_result_counter='SELECT COUNT(*) num FROM ( ('.join( ') UNION ALL (', $_arrU ).') ) tmp';
		return true;
	}

	private function gen_result_union_counter( &$_strSql ) {
		$_strSql='SELECT "" FROM '.join( ' ', $this->q_from );
		if ( !empty( $this->q_where ) ) {
			$_strSql.=' WHERE '.join( $this->q_where_concat.' ', $this->q_where );
		}
		if ( !empty( $this->q_group ) ) {
			$_strSql.=' GROUP BY '.join( ', ', $this->q_group );
		}
		if ( !empty( $this->q_having ) ) {
			$_strSql.=' HAVING '.join( ', ', $this->q_having );
		}
	}

	public function set_paging( $_arrDta=array() ) {
		$this->page_setting=array();
		if ( empty( $_arrDta ) ) {
			return false;
		}
		$this->page_setting=$_arrDta;
		return $this;
	}

	public function get_union_sql( &$strSql, &$arrPg, $_arrSet=array() ) {
		$this->gen_union_full( $strSql );
		$this->page_setting=empty( $_arrSet['arrNav'] )? $this->page_setting:$_arrSet['arrNav'];
		if ( !empty( $this->page_setting ) ) {
			if ( empty( $this->page_setting['rowtotal'] ) ) {
				$this->gen_union_counter();
				$this->page_setting['rowtotal']=Core_Sql::getCell( $this->q_result_counter );
				//$this->page_setting['rowtotal']=array_sum( $_arrC );
			}
			$strSql=$this->getPaged( $strSql, $arrPg, $strSql, array( 'arrNav'=>$this->page_setting ) );
		}
		return $strSql;
	}

	public function get_sql( &$strSql, &$arrPg, $_arrSet=array() ) {
		$this->get_result_full( $strSql );
		$this->page_setting=empty( $_arrSet['arrNav'] )? $this->page_setting:$_arrSet['arrNav'];
		if ( !empty( $this->page_setting ) ) {
			if ( empty( $this->page_setting['rowtotal'] ) ) {
				$this->page_setting['rowtotal']=Core_Sql::getCell( $this->get_result_counter( $_strTmp ) );
			}
			$this->getPaged( $strSql, $arrPg, $strSql, array( 'arrNav'=>$this->page_setting ) );
		}
		return $strSql;
	}

	public function get_result_counter( &$strSql ) {
		$this->gen_result_counter();
		$strSql=$this->q_result_counter;
		return $strSql;
	}

	public function get_result_full( &$strSql ) {
		$this->gen_result_full();
		$strSql=$this->q_result_full;
		return $strSql;
	}

	public function gen_result_full() {
		$this->q_result_full='SELECT '.join( ', ', $this->q_select );
		$this->q_result_full.=' FROM '.join( ' ', $this->q_from );
		if ( !empty( $this->q_where ) ) {
			$this->q_result_full.=' WHERE '.join( $this->q_where_concat.' ', $this->q_where );
		}
		if ( !empty( $this->q_group ) ) {
			$this->q_result_full.=' GROUP BY '.join( ', ', $this->q_group );
		}
		if ( !empty( $this->q_having ) ) {
			$this->q_result_full.=' HAVING '.join( ', ', $this->q_having );
		}
		if ( !empty( $this->q_order ) ) {
			$this->q_result_full.=' ORDER BY '.join( ', ', $this->q_order );
		}
		if ( !empty( $this->q_limit ) ) {
			$this->q_result_full.=' LIMIT '.$this->q_limit;
		}
	}

	public function gen_result_counter() {
		$this->q_result_counter='SELECT COUNT(*) num FROM ( SELECT ""';
		$this->q_result_counter.=' FROM '.join( ' ', $this->q_from );
		if ( !empty( $this->q_where ) ) {
			$this->q_result_counter.=' WHERE '.join( $this->q_where_concat.' ', $this->q_where );
		}
		if ( !empty( $this->q_group ) ) {
			$this->q_result_counter.=' GROUP BY '.join( ', ', $this->q_group );
		}
		if ( !empty( $this->q_having ) ) {
			$this->q_result_counter.=' HAVING '.join( ', ', $this->q_having );
		}
		$this->q_result_counter.=') tmp';
	}

	public function set_union_select( Core_Sql_Qcrawler $obj ) {
		$this->q_union[]=$obj;
	}

	public function set_select( $_strS='' ) {
		if ( empty( $_strS ) ) {
			return false;
		}
		$this->q_select[]=$_strS;
		return true;
	}

	public function clean_select() {
		$this->q_select=array();
	}

	public function set_from( $_strS='' ) {
		if ( empty( $_strS ) ) {
			return false;
		}
		$this->q_from[]=$_strS;
		return true;
	}

	public function no_bracket() {
		$this->_bracketL='';
		$this->_bracketR='';
		return $this;
	}

	public function set_where( $_strS='' ) {
		if ( empty( $_strS ) ) {
			return false;
		}
		$this->q_where[]=$this->_bracketL.$_strS.$this->_bracketR;
		return true;
	}

	public function set_where_last_rewrite( $_strS='' ) {
		if ( empty( $_strS ) ) {
			return false;
		}
		if ( empty( $this->q_where ) ) {
			return $this->set_where( $_strS );
		}
		$this->q_where[count($this->q_where)-1]=$_strS;
		return true;
	}

	public function set_group( $_strS='' ) {
		if ( empty( $_strS ) ) {
			return false;
		}
		if ( is_array( $_strS ) ) {
			$this->q_group=array_merge( $this->q_group, $_strS );
		} else {
			$this->q_group[]=$_strS;
		}
		return true;
	}

	public function set_having( $_strS='' ) {
		if ( empty( $_strS ) ) {
			return false;
		}
		$this->q_having[]=$_strS;
		return true;
	}

	public function set_order( $_strS='' ) {
		if ( empty( $_strS ) ) {
			return false;
		}
		$this->q_order[]=$_strS;
		return true;
	}

	public function set_group_sort( $_arrOrd=array() ) {
		if ( empty( $_arrOrd ) ) {
			return false;
		}
		if ( !is_array( $_arrOrd ) ) {
			$_arrOrd=array( $_arrOrd );
		}
		foreach( $_arrOrd as $v ) {
			if ( $v=='rand' ) {
				$this->set_order( 'RAND()' );
			} else {
				$_arrPrt=explode( '+', $v );
				$this->set_group( $_arrPrt[0].' '.( ( $_arrPrt[1]=='up' ) ? 'DESC':'ASC' ) );
			}
		}
		return true;
	}

	public function set_order_sort( $_arrOrd=array() ) {
		if ( empty( $_arrOrd ) ) {
			return false;
		}
		if ( !is_array( $_arrOrd ) ) {
			$_arrOrd=array( $_arrOrd );
		}
		foreach( $_arrOrd as $v ) {
			if ( $v=='rand' ) {
				$this->set_order( 'RAND()' );
			} else {
				$_arrPrt=explode( '--', $v );
				$this->set_order( $_arrPrt[0].' '.( ( $_arrPrt[1]=='up' ) ? 'DESC':'ASC' ) );
			}
		}
		return true;
	}

	public function set_where_concat( $_strConcat='AND' ) {
		$this->q_where_concat=$_strConcat;
	}

	public function set_limit( $_strS='' ) {
		if ( empty( $_strS ) ) {
			return false;
		}
		$this->q_limit=$_strS;
		return true;
	}
	/**
	* Часть запроса для поиска по введённым пользователям словам (подставляем в WHERE)
	* MATCH AGAINST часть + LIKE часть (если есть слова в 3 буквы)
	* @param array $arrW out - куски для where
	* @param string $strK out - строка слов от пользователя
	* @param array $_arrFld in - поля по которым искать (с алиасами, если они есть в запросе)
	* @param string $_strK in - нормализованная строка слов (по которой реально искалось)
	* @return boolean
	*/
	public function keyword_search( &$arrW, &$strK, $_arrFld=array(), $_strK='' ) {
		if ( empty( $_strK )||empty( $_arrFld ) ) {
			return false;
		}
		$_arrM=$_arrK=$_arrL=array(); $strK='';
		$_strW=substr( preg_replace( "/[^\w\x7F-\xFF\s]/", " ", $_strK ), 0, 64 );
		$_arrW=preg_split( '/[\s\/]+/', $_strW, -1, PREG_SPLIT_NO_EMPTY );
		$_arrW=array_unique( $_arrW );
		// отсеиваем слова длинной 3 и более символов в отдельные массивы
		foreach( $_arrW as $v ) {
			if ( strlen( $v )>3||( strlen( $v )>1&!$this->q_keywordsearch_withlimit ) ) {
				$_arrM[]=$v;
				$_arrK[]=$v;
			} elseif ( $this->q_keywordsearch_withlimit&&strlen( $v )==3 ) {
				foreach ( $_arrFld as $f ) {
					$_arrL[]=$f.' LIKE "%'.$v.'%"';
				}
				$_arrK[]=$v;
			}
		}
		if ( empty( $_arrK ) ) { // подходящих слов не нашлось
			return false;
		}
		// генерим условия для полученных слов
		$strK=join( ' ', $_arrK );
		if ( !empty( $_arrL ) ) {
			$arrW[]=join( ' OR ', $_arrL );
		}
		if ( !empty( $_arrM ) ) {
			$arrW[]='MATCH ('.join( ', ', $_arrFld ).') AGAINST ("'.join( ' ', $_arrM ).'")';
		}
		return true;
	}

	// текущая страница для которой генерим запрос
	public $page=1;

	// переменные которые будут в сылках пэйджера
	public $url=array();
	
	// сколько записей не странице
	public $reconpage=0;
	// сколько чисел видно в пэйджере
	public $numofdigits=0;
	// сколько всего записей выгребает данный запрос
	public $rowtotal=0;

	// сгенерённая ссылка
	public $href='';

	public function getPaged( &$strRes, &$arrPg, $_strSql='', $_arrSet=array() ) {
		if ( empty( $_arrSet['arrNav'] ) ) {
			$strRes=$_strSql;
			return $strRes;
		}
		$_arrSet=$_arrSet['arrNav'];
		$this->sql_query=$_strSql;
		$this->page_inst=empty( $_arrSet['inst'] )?'':$_arrSet['inst'];
		if ( !empty( $_arrSet['page'] ) ) {
			$this->page=$_arrSet['page'];
		} elseif ( !empty( $_arrSet['url'][$this->page_inst.'page'] ) ) { // получаем из ссылки
			$this->page=$_arrSet['url'][$this->page_inst.'page'];
		}
		if ( !empty( $_arrSet['url'] ) ) {
			$this->url=$_arrSet['url'];
		}
		$this->no_link=false;
		if ( !empty( $_arrSet['no_link'] ) ) {
			$this->no_link=$_arrSet['no_link'];
		}
		if ( !empty( $_arrSet['reconpage'] ) ) {
			$this->reconpage=$_arrSet['reconpage'];
		}
		if ( !empty( $_arrSet['numofdigits'] ) ) {
			$this->numofdigits=$_arrSet['numofdigits'];
		}
		if ( isSet( $_arrSet['rowtotal'] ) ) { // может быть и 0
			$this->rowtotal=(int)$_arrSet['rowtotal'];
		} else { // пробуем выделить из запроса корректный кусок чтобы подсчитать $this->rowtotal TODO!!!
			$_arrParts=preg_split( '/\s+FROM\s+/', $this->sql_query );
			$this->rowtotal=(int)Core_Sql::getCell( 'SELECT COUNT(*) num FROM '.$_arrParts[1] );
		}
		$this->m_paged_sql( $strRes );
		$this->m_paged_bar( $arrPg );
		return $strRes;
	}

/*
	// pagging need refactoring TODO!!! 07.04.2009
	public function paged_select( &$strSql, &$arrPg, $strSql='', $arrSet=array() ) {
		$this->sql_query=$strSql;
		$this->m_paged_fetch( $strSql, $arrPg, $arrSet );
	}

	function m_paged_fetch( &$strSql, &$arrPg, $_arrSet=array() ) {
		$this->page_inst=empty( $_arrSet['inst'] )?'':$_arrSet['inst'];
		if ( !empty( $_arrSet['page'] ) ) {
			$this->page=$_arrSet['page'];
		} elseif ( !empty( $_arrSet['url'][$this->page_inst.'page'] ) ) { // получаем из ссылки
			$this->page=$_arrSet['url'][$this->page_inst.'page'];
		}
		if ( !empty( $_arrSet['url'] ) ) {
			$this->url=$_arrSet['url'];
		}
		$this->no_link=false;
		if ( !empty( $_arrSet['no_link'] ) ) {
			$this->no_link=$_arrSet['no_link'];
		}
		if ( !empty( $_arrSet['reconpage'] ) ) {
			$this->reconpage=$_arrSet['reconpage'];
		}
		if ( !empty( $_arrSet['numofdigits'] ) ) {
			$this->numofdigits=$_arrSet['numofdigits'];
		}
		if ( isSet( $_arrSet['rowtotal'] ) ) { // может быть и 0
			$this->rowtotal=(int)$_arrSet['rowtotal'];
		} else { // пробуем выделить из запроса корректный кусок чтобы подсчитать $this->rowtotal TODO!!!
			$_arrParts=preg_split( '/\s+FROM\s+/', $this->sql_query );
			$this->rowtotal=(int)Core_Sql::getCell( 'SELECT COUNT(*) num FROM '.$_arrParts[1] );
		}
		$this->m_paged_sql( $strSql );
		$this->m_paged_bar( $arrPg );
	}
*/
	private function m_paged_sql( &$_strSql ) {
		if ( empty( $this->rowtotal ) ) {
			$_strSql=$this->sql_query;
			return;
		}
		$_strSql=$this->sql_query.' LIMIT '.( $this->page>1?( ( $this->page-1 )*$this->reconpage ).','.$this->reconpage : $this->reconpage );
		return;
	}

	private function m_additional_info() {
		if ( $this->page>1 ) { // у нас не первая страница
			$this->rec_from=( ( $this->page-1 )*$this->reconpage )+1;
			$_intTest=$this->rec_from+$this->reconpage-1;
			$this->rec_to=$this->rowtotal>$_intTest?$_intTest:$this->rowtotal;
		} else { // первая страница
			$this->rec_from=1;
			$this->rec_to=$this->rowtotal>$this->reconpage?$this->reconpage:$this->rowtotal;
		}
		$this->maxpage=ceil( $this->rowtotal/$this->reconpage );
		//$this->maxpage++;
	}

	function m_paged_bar( &$_arrPg ) {
		$_arrPg=array();
		$this->m_additional_info();
		$_arrPg['curpage']=$this->page;
		$_arrPg['recall']=$this->rowtotal;
		$_arrPg['recfrom']=$this->rec_from;
		$_arrPg['recto']=$this->rec_to;
		if ( !( $this->rowtotal>$this->reconpage ) ) {
			return;
		}
		$this->href=$this->m_paged_url();
		// calculate diapazon refaktoring TODO 04.12.2008
		$_intStart=$this->page-$this->numofdigits/2;
		$_intEnd=$this->page+$this->numofdigits/2;
		if ( $_intStart<1 ) {
			$_intStart=1;
			$_intEnd=$_intStart+$this->numofdigits;
		}
		$_intEnd1=intVal( ( $this->rowtotal-1 )/$this->reconpage );
		$_intEnd1++;
		if ( $_intEnd>$_intEnd1&&$_intStart>$_intEnd-$_intEnd1 ) {
			$_intEnd=$_intEnd1;
			$_intStart=$_intEnd-$this->numofdigits;
		} elseif ( $_intEnd>$_intEnd1 ) {
			$_intEnd=$_intEnd1;
			$_intStart=1;
		}
		// generate hash
		//if ( $_intStart>1 )
			$_arrPg['urlmin']=$this->href.'1';
		if ( $this->page>$_intStart ) $_arrPg['urlminus']=$this->href.( $this->page-1 );
		$b=0;
		for ( $a=intVal( $_intStart ); $a<=$_intEnd; $a++ ) {
			if ( $a==$this->page ) $_arrPg['num'][$b]['sel']=1;
			$_arrPg['num'][$b]['url']=$this->href.$a;
			$_arrPg['num'][$b]['number']=$a;
			$b++;
		}
		if ( $this->page<$_intEnd ) $_arrPg['urlplus']=$this->href.( $this->page+1 );
		//if ( $_intEnd<>$_intEnd1 )
			$_arrPg['urlmax']=$this->href.$this->maxpage;
		return;
	}

	function m_paged_url() {
		$_strHref='';
		if ( $this->no_link ) {
			return '';
		}
		$_strHref.=Core_Module_Router::$uriVar.'?';
		unSet( $this->url[$this->page_inst.'page'] );
		if ( !empty( $this->url ) ) {
			if ( is_array( $this->url ) ) {
				unSet( $this->url[$this->page_inst.'page'] );
				$_strHref.=$this->sv_make_url_vars($this->url).'&';
	//			foreach( $this->url as $k=>$v ) {
	//				$_strHref.=$k.'='.$v.'&';
	//			}
			} else {
				$_strHref.=$this->url.'&';
			}
		}
		$_strOrder='';
		foreach( $this->q_order as $order ) {
			if( $order != 'd.id DESC' ){
				$_arrPrt=explode( ' ', $order );
				$_strOrder='order='.$_arrPrt[0].'--'.( ( $_arrPrt[1]=='DESC' ) ? 'up':'dn' ).'&';
			}
		}
		$_strHref.=$this->page_inst.$_strOrder.'page=';
		return $_strHref;
	}
}
?>