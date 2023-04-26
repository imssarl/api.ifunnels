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
 * Stencil Extension
 * @internal груповые операции с шаблонами данных
 * @category framework
 * @package CompositeItems
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2008, Rodion Konnov
 * @author Rodion Konnov <kindzadza@mail.ru>
 * @date 23.10.2008
 * @version 0.2
 */


class Core_Items_Stencil_Extension extends Core_Items_Stencil {
	private $field_filter=array();
	private $field_order='';
	private $fields=array();

	public function __construct() {}

	public function set_filter( $_arrFilter=array() ) {
		if ( empty( $_arrFilter ) ) {
			return;
		}
		$this->field_filter=$_arrFilter;
	}

	public function set_ordered( $_strOrder=array() ) {
		if ( empty( $_strOrder ) ) {
			return;
		}
		// если приходит в таком виде непример add_first_name--dn
		preg_match( '/add_(.*)\--/', $_strOrder, $res );
		//$_arrPrt=explode( '+', $_strOrder );
		$this->field_order=$res[1];
	}

	public function get_fields_by_stencilprefix( $_strPrefix='' ) {
		if ( !$this->get_stencil_byprefix( $_arrSten, $_strPrefix ) ) {
			return false;
		}
		foreach( $_arrSten as $v ) {
			$this->get_fields( $this->fields[$v['sys_name']], $v['id'] );
		}
		return !empty( $this->fields );
	}

	public function get_fields_result( &$arrRes ) {
		$arrRes=$this->fields;
		return $arrRes;
	}

	// подробно поля которые будут использоваться
	public function get_filtered_fields( &$arrFull, &$arrShort ) {
		foreach( $this->fields as $v ) {
			foreach( $v as $k=>$f ) {
				if ( empty( $this->field_filter ) ) { // если фильтр не указали то берём все поля
					$arrFull[$k]=$f;
				} elseif ( in_array( $k, $this->field_filter ) ) { // или по фильтру
					$arrFull[$k]=$f;
				}
			}
		}
		if ( empty( $arrFull ) ) {
			return false;
		}
		$arrShort=array_keys( $arrFull );
		return true;
	}

	// если, допутим, ордерим по полю которое престствует в двух масках то нужно учитыать оба поля
	public function get_ordered_field_ids( &$arrRes, &$strOrder ) {
		foreach( $this->fields as $v ) {
			if ( in_array( $this->field_order, array_keys( $v ) ) ) {
				$arrRes[]=$v[$this->field_order]['id'];
			}
		}
		$strOrder=$this->field_order;
		return !empty( $arrRes );
	}

	public function get_stencil_byprefix( &$arrRes, $_strPrefix='' ) {
		if ( empty( $_strPrefix ) ) {
			return false;
		}
		$arrRes=Core_Sql::getAssoc( '
			SELECT s.*, COUNT(f.id) field_num
			FROM ci_stencil s
			LEFT JOIN ci_stencil_field f ON f.stencil_id=s.id
			WHERE s.tbl_prefix="'.$_strPrefix.'"
			GROUP BY s.id
		' );
		return !empty( $arrRes );
	}

	public static function get_stencil_byprefix_toselect_ids( &$arrRes, $_strPrefix='' ) {
		if ( empty( $_strPrefix ) ) {
			return false;
		}
		$arrRes=Core_Sql::getKeyVal( '
			SELECT s.id, s.sys_name
			FROM ci_stencil s
			WHERE s.tbl_prefix="'.$_strPrefix.'"
		' );
		return !empty( $arrRes );
	}

	public static function get_stencil_byprefix_toselect( &$arrRes, $_strPrefix='' ) {
		if ( empty( $_strPrefix ) ) {
			return false;
		}
		$arrRes=Core_Sql::getKeyVal( '
			SELECT s.sys_name, s.title
			FROM ci_stencil s
			WHERE s.tbl_prefix="'.$_strPrefix.'"
		' );
		return !empty( $arrRes );
	}

	public function get_stencil_sysname_byid( &$strRes, $_intId=0 ) {
		if ( empty( $_intId ) ) {
			return false;
		}
		$strRes=Core_Sql::getCell( 'SELECT sys_name FROM ci_stencil WHERE id="'.$_intId.'"' );
		return !empty( $strRes );
	}

	public function get_stencil_default( &$arrRes ) {
		$arrRes=Core_Sql::getRecord( 'SELECT * FROM ci_stencil WHERE flg_default=1' );
		return !empty( $arrRes );
	}
}
?>