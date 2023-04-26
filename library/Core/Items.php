<?php
/**
 * Composite Items
 * @category framework
 * @package CompositeItems
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2008, Rodion Konnov
 * @author Rodion Konnov <kindzadza@mail.ru>
 * @date 10.04.2008
 * @version 1.0
 */


/**
 * Items
 * @internal функционал для работы с айтемами, выбоки и проч.
 * @category framework
 * @package CompositeItems
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2008, Rodion Konnov
 * @author Rodion Konnov <kindzadza@mail.ru>
 * @date 03.11.2008
 * @version 1.7
 */


class Core_Items extends Core_Items_Single implements Core_Items_Sample {
	public $fields_format; // ?!
	// переменные поиска
	private $mask_setting=array();
	private $field_limit=0;// 0 - все поля
	// могут понадобиться для extends классов
	public $order_field='';
	public $filter_sql='';
	public $page_setting=array();
	public $order_setting='';
	public $filter_setting=array();
	public $objS;

	public function __construct( $_strSys='' ) {
		parent::__construct( $_strSys );
	}

	public function get_blank_item( &$_arrRes ) {
		$_arrRes=$this->objS->stencil;
		$this->get_blank_item_fields( $_arrRes['arrFields'] );
	}

	public function get_blank_item_fields( &$_arrRes ) {
		if ( !$this->with_hidden_fields ) { // таким образом не показываем на форме скрытые поля
			$_arrRes=array();
			foreach( $this->objS->stencil['arrFields'] as $k=>$v ) {
				if ( empty( $v['flg_hidden'] ) ) {
					$_arrRes[$k]=$v;
				}
			}
		} else {
			$_arrRes=$this->objS->stencil['arrFields'];
		}
	}

	public function get_filed_types( &$_arrRes ) {
		$_arrRes=$this->objS->field_types;
	}

	public function del_items_bymask() {
		if ( $this->objS->get_current_field_formatted( $_arr, array( 'files_fields'=>true ) ) ) {
			foreach( $_arr as $v ) {
				$_arrFld[]=$v['id'];
			}
			$_arrFileIds=Core_Sql::getKeyVal( 'SELECT id, content FROM '.$this->fields_tbl.' WHERE stencil_field_id IN("'.join( '", ', $_arrFld ).'")' );
			$this->del_files_connector( $_arrFileIds );
		}
		Core_Sql::setExec( 'DELETE i, f FROM '.$this->item_tbl.' i LEFT JOIN '.$this->fields_tbl.' f ON f.item_id=i.id WHERE i.stencil_id="'.$this->objS->stencil['id'].'"' );
	}

	public function del_items_byuserids( $_arrIds=array() ) {}

	public function del_fields_bymask( $_arrIds=array() ) {
		if ( $this->objS->get_current_field_formatted( $_arr, array( 'files_fields'=>true ) ) ) {
			foreach( $_arr as $v ) {
				$_arrFld[]=$v['id'];
			}
			$_arrFileFld=array_intersect( $_arrIds, $_arrIds );
			if ( !empty( $_arrFileFld ) ) {
				$_arrFileIds=Core_Sql::getKeyVal( 'SELECT id, content FROM '.$this->fields_tbl.' WHERE stencil_field_id IN("'.join( '", ', $_arrFileFld ).'")' );
				$this->del_files_connector( $_arrFileIds );
			}
		}
		Core_Sql::setExec( 'DELETE FROM '.$this->fields_tbl.' WHERE stencil_field_id IN('.join( ', ', $_arrIds ).')' );
	}

	public function set_search_settings( $_arrDta=array() ) {
		// инициализация переменных
		$this->page_setting=$this->mask_setting=$this->filter_setting=$this->fields_filter=array();
		$this->order_setting=$this->order_field='';
		$this->field_limit=0;
		if ( empty( $_arrDta ) ) {
			return false;
		}
		// $_arrDta=Core_Sql::fixInjection( $_arrDta ); будет с ковычками - надо решить вопрос TODO!!! 07.04.2009
		if ( !empty( $_arrDta['arrNav'] ) ) { // постраничный вывод
			$this->page_setting=$_arrDta['arrNav'];
		}
		// сортировка данных если приходит в таком виде непример add_first_name--dn
		if ( !empty( $_arrDta['order'] ) ) {
			if ( preg_match( '/^add_((.*)\--(?:dn|up))$/', $_arrDta['order'], $res ) ) { // сортировка по полям $this->fields_tbl
				if ( !empty( $this->objS->stencil['arrFields'][$res[2]] ) ) { // такое поле есть в текущей маске
					$this->order_setting=$res[1];
					$this->order_field=$res[2];
				}
			} else {
				$this->order_setting=$_arrDta['order'];
			}
		}
		if ( !empty( $_arrDta['need_fields'] ) ) { // ограничения на количество выводимых полей
			if ( is_array( $_arrDta['need_fields'] ) ) { // набор нужных полей
				$this->mask_setting=$_arrDta['need_fields'];
			} else { // количество полей
				$this->field_limit=$_arrDta['need_fields'];
			}
		}
		//$_arrDta=Core_A::array_check( $_arrDta, $this->post_filter );
		if ( !empty( $_arrDta['fields_filter'] ) ) {
			$this->fields_filter=$_arrDta['fields_filter'];
		}
		$this->filter_setting=$_arrDta; // запоминаем весь вильтр
	}

	public function get_search_settings( &$arrRes ) {
		$arrRes=$this->filter_setting;
		if ( !empty( $this->mask_setting ) ) {
			$arrRes['fields_num']=count( $this->mask_setting );
		} else {
			$arrRes['fields_num']=$this->field_limit;
		}
	}

	public function get_items_ids( &$arrRes ) {
		$this->get_filtered_sql();
		return $this->get_sorted_ids( $arrRes, $_arrTmp );
	}

	public function get_items( &$arrRes, &$arrPg, &$arrHead ) {
		$this->get_filtered_sql();
		if ( !$this->get_sorted_ids( $_arrIds, $arrPg ) ) {
			return false;
		}
		$this->get_needed_field_set( $arrHead, $arrMask ); // берём дополнительный заголовок списка и маску для получения дополнительных полей
		// если в списке колонок должны быть не все поля маски (!$this->with_hidden_fields) но в шаблоне должна быть полная инфа по айтему
		// надо покрасивей реализовать 03.11.2008 TODO!!!
		if ( !empty( $this->filter_setting['ignore_hidden_setting'] ) ) {
			$arrMask=array_keys( $this->objS->stencil['arrFields'] );
			$this->set_with_hidden_fields();
		}
		foreach( $_arrIds as $k=>$v ) {
			if ( empty( $this->filter_setting['with_extended_data'] ) ) {
				$this->get_item_masked( $arrRes[$k], $v, $arrMask );
			} else {
				$this->get_item_masked_full( $arrRes[$k], $v, $arrMask );
			}
		}
		return true;
	}

	/*
	array(
		'field_name'=>array(
			'not_in'=>array( 'value', 'value' ), // NOT IN
			'in'=>array( 'value', 'value' ), // IN
			'gap_from'=>'value' // <
			'gap_to'=>'value' // >
			'keywords'=>'value' // full-text search
		) // BETWEEN - gap_from&gap_to
	)
	*/
	public function get_filtered_sql() {
		$this->filter_sql='';
		if ( empty( $this->fields_filter ) ) {
			return false;
		}
		$_arr=array();
		foreach( $this->fields_filter as $k=>$v ) {
			if ( empty( $this->objS->stencil['arrFields'][$k] )||!$this->get_where_field( $_str, $v, $k ) ) {
				continue;
			}
			$_arr[$k]=$_str;
		}
		return $this->get_nested_select( $this->filter_sql, $_arr );
	}

	private $search_conjunction='AND';

	public function set_search_conjunction( $_str='OR' ) {
		$this->search_conjunction=$_str;
	}

	private function get_nested_select( &$sql,  $_arrFieldsWhere=array() ) {
		if ( empty( $_arrFieldsWhere ) ) {
			return false;
		}
		$i=1; $_intC=count( $_arrFieldsWhere );
		foreach( $_arrFieldsWhere as $k=>$v ) {
			if ( $i==$_intC ) {
				$sql.='SELECT item_id FROM '.$this->fields_tbl.' WHERE stencil_field_id='.$this->objS->stencil['arrFields'][$k]['id'].' AND '.$v;
			} else {
				$sql.='SELECT item_id FROM '.$this->fields_tbl.' WHERE stencil_field_id='.$this->objS->stencil['arrFields'][$k]['id'].' AND '.$v.' '.$this->search_conjunction.' item_id IN(';
			}
			$i++;
		}
		if ( $i>1 ) {
			$sql.=str_repeat( ')', $i-2 );
		}
		return true;
	}

	private function get_where_field( &$str, $_arrField=array(), $_strField ) {
		if ( empty( $_arrField ) ) {
			return false;
		}
		$_str=$this->get_casted_content( $_strField );
		$_arr=array();
		if ( array_key_exists( 'gap_from', $_arrField )&&array_key_exists( 'gap_to', $_arrField ) ) { // BETWEEN
			$_arr[]=$_str.' BETWEEN "'.$_arrField['gap_from'].'" AND "'.$_arrField['gap_to'].'"';
		} elseif ( array_key_exists( 'gap_from', $_arrField ) ) { // >
			$_arr[]=$_str.'>"'.$_arrField['gap_from'].'"';
		} elseif ( array_key_exists( 'gap_to', $_arrField ) ) { // <
			$_arr[]=$_str.'<"'.$_arrField['gap_to'].'"';
		}
		if ( array_key_exists( 'not_in', $_arrField ) ) { // NOT IN
			$_arr[]=$_str.' NOT IN("'.join( '", "', $_arrField['not_in'] ).'")';
		}
		if ( array_key_exists( 'in', $_arrField ) ) { // IN
			if ( !is_array( $_arrField['in'] ) ) {
				$_arrField['in']=array( $_arrField['in'] );
			}
			$_arr[]=$_str.' IN("'.join( '", "', $_arrField['in'] ).'")';
		}
		if (array_key_exists('null',$_arrField)) {
			$nullItemIds=SQLselectOneField('SELECT `id` FROM `'.$this->item_tbl.'` as `originalItem` WHERE
			(SELECT
				`content`
			FROM
				`'.$this->fields_tbl.'`
			WHERE
				`item_id`=`originalItem`.`id` AND
				`stencil_field_id`="'.$this->objS->stencil['arrFields'][$_strField]['id'].'"
			) IS NULL' );
			if (is_array($this->filter_setting['ids'])) {
				$this->filter_setting['ids']=array_intersect($nullItemIds,$this->filter_setting['ids']);
			} else {
				$this->filter_setting['ids']=$nullItemIds;
			}
		}
		if ( array_key_exists( 'keywords', $_arrField ) ) { // full-text search
			$objQi=new Core_Sql_Qcrawler();
			if ( !$objQi->keyword_search( $_arrK, $_strTmp, array( 'content' ), $_arrField['keywords'] ) ) {
				return false;
			}
			$_arr[]=join( ' OR ', $_arrK );
		}
		if ( empty( $_arr ) ) {
			return false;
		}
		$str='('.join( '") AND ("', $_arr ).')';
		return true;
	}

	public function get_sorted_ids( &$arrRes, &$arrPg ) {
		$objQi=new Core_Sql_Qcrawler('i');
		$objQi->set_select( 'i.id' );
		if ( !empty( $this->order_field ) ) {
			if ( !empty( $this->filter_setting['order_by_external_table'][$this->order_field] ) ) {
				$_arr=explode( '.', $this->filter_setting['order_by_external_table'][$this->order_field] );
				$objQi->set_select(
					'(SELECT '.$_arr[1].' FROM '.$_arr[0].
					' WHERE id=(SELECT '.$this->get_casted_content( $this->order_field ).' FROM '.$this->fields_tbl.
					' WHERE item_id=i.id AND stencil_field_id='.$this->objS->stencil['arrFields'][$this->order_field]['id'].')) '.$this->order_field );
			} else {
				$objQi->set_select(
					'(SELECT '.$this->get_casted_content( $this->order_field ).' FROM '.$this->fields_tbl.
					' WHERE item_id=i.id AND stencil_field_id='.$this->objS->stencil['arrFields'][$this->order_field]['id'].') '.$this->order_field );
			}
		} elseif ( empty( $this->order_setting ) ) {
			$this->order_setting='id--up';
		}
		$objQi->set_from( $this->item_tbl.' i' );
		$objQi->set_where( 'stencil_id="'.$this->objS->stencil['id'].'"' );
		if ( !empty( $this->filter_setting['user_id'] ) ) {
			$objQi->set_where( 'user_id IN ("'.join('", "',(array)$this->filter_setting['user_id']).'")' );
		}
		if ( !empty( $this->filter_setting['id_in_sql'] ) ) { // тут можно задать вложенный селект
			$objQi->set_where( 'i.id IN('.$this->filter_setting['id_in_sql'].')' );
		}
		if ( isSet( $this->filter_setting['ids'] ) ) {
			$objQi->set_where( 'i.id IN('.Core_Sql::fixInjection( $this->filter_setting['ids'] ).')' );
		}
		if ( !empty( $this->filter_sql ) ) {
			$objQi->set_where( 'i.id IN('.$this->filter_sql.')' );
		}
		if ( !empty( $this->filter_setting['limit'] ) ) {
			$objQi->set_limit( $this->filter_setting['limit'] );
		}
		$objQi->set_order_sort( $this->order_setting );
		$objQi->set_paging( $this->page_setting );
		$arrRes=Core_Sql::getField( $objQi->get_sql( $_strQ, $arrPg ) );
		return !empty( $arrRes );
	}

	public function get_casted_content( $_strField='' ) {
		$_str='content';
		if ( empty( $_strField )||empty( $this->objS->field_cast[$this->objS->stencil['arrFields'][$_strField]['flg_cast']] ) ) {
			return $_str;
		}
		return 'CAST(content AS '.$this->objS->field_cast[$this->objS->stencil['arrFields'][$_strField]['flg_cast']].')';
	}

	// подробно поля которые будут использоваться
	public function get_needed_field_set( &$arrFull, &$arrShort ) {
		$i=0;
		foreach( $this->objS->stencil['arrFields'] as $k=>$v ) {
			if ( !empty( $this->field_limit )&&$i==$this->field_limit ) { // если указан лимит полей и он достигнут
				break;
			}
			if ( !$this->with_hidden_fields&&!empty( $v['flg_hidden'] ) ) { // пропускаем скрытые поля если $this->with_hidden_fields==false
				continue;
			}
			if ( !empty( $this->mask_setting )&&!in_array( $k, $this->mask_setting ) ) { // если есть фильтр то пропускаем поля не попавшие в него
				continue;
			}
			$arrFull[$k]=$v;
			$i++;
		}
		if ( empty( $arrFull ) ) {
			return false;
		}
		$arrShort=array_keys( $arrFull );
		return true;
	}

	public function set_items_flg_priority( $_arrDta=array() ) {
		if ( empty( $_arrDta ) ) {
			return false;
		}
		foreach( $_arrDta as $v ) {
			$this->change_flg_priority( $v['id'], $v['flg_priority'] );
		}
		return true;
	}
}
?>