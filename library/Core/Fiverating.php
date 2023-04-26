<?php


/**
 * Five-star item rating
 */


class fiverating extends Core_Services {
	public $f_tbl=array( 'user_id', 'item_id', 'flg_type', 'starnum', 'added' );
	public $f_types=array( 'c_items', 'u_users', 'f_files' );
	public $f_type=0; // тип позиции из $this->f_types
	public $f_item=0; // id позиции за которую подан голос
	public $f_viewer=0; // id пользователя который подал голос
	public $f_scale=5;

	function fiverating( $_arrSet=array() ) {
		if ( !empty( $_arrSet['user_id'] ) ) {
			$this->f_viewer=$_arrSet['user_id'];
		} elseif ( Zend_Registry::get( 'objUser' )->getId( $_int ) ) {
			$this->f_viewer=$_int;
		}
		if ( !empty( $_arrSet['item_id'] ) ) {
			$this->f_item=$_arrSet['item_id'];
		}
		if ( !empty( $_arrSet['flg_type'] ) ) {
			if ( ( $_intKey=array_search( $_arrSet['flg_type'], $this->f_types ) )!==false ) {
				$this->f_type=$_intKey;
			}
		}
	}

	function f_set( $_intStat=0 ) {
		if ( empty( $this->f_item )||empty( $this->f_viewer ) ) {
			return false;
		}
		$_arrDat=array(
			'user_id'=>$this->f_viewer,
			'item_id'=>$this->f_item,
			'flg_type'=>$this->f_type,
			'starnum'=>(($this->f_scale*$_intStat)/100),
			'added'=>time(),
		);
		Core_Sql::setInsert( 'stat_fiverating', $_arrDat );
		return true;
	}

	function f_get_average( &$arrRes, $_intId=0 ) {
		$_intId=empty( $_intId ) ? $this->f_item:$_intId;
		if ( empty( $_intId ) ) {
			return false;
		}
		$arrRes=Core_Sql::getRecord( '
			SELECT ROUND(SUM(starnum)/COUNT(*),2) rate, COUNT(*) votes
			FROM stat_fiverating
			WHERE flg_type="'.$this->f_type.'" AND item_id="'.$_intId.'"
		' );
		// долой лишние нули
		if ( empty( $arrRes['rate']{2} )&&empty( $arrRes['rate']{3} ) ) {
			$arrRes['rate']=(int)$arrRes['rate'];
		}
		return true;
	}

	// был-ли оставлен рэйт данным пользователем для данного айтема
	function f_check() {
		if ( empty( $this->f_viewer ) ) {
			return false;
		}
		$_arrRes=Core_Sql::getRecord( '
			SELECT starnum, user_id
			FROM stat_fiverating
			WHERE flg_type="'.$this->f_type.'" AND item_id="'.$this->f_item.'" AND user_id="'.$this->f_viewer.'"
		' );
		return empty( $_arrRes['user_id'] );
	}
}
?>