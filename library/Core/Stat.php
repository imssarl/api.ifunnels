<?php
/**
 * Auxiliary classes
 * @category framework
 * @package Auxiliary
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2008, Rodion Konnov
 * @author Rodion Konnov <kindzadza@mail.ru>
 * @date 20.11.2008
 * @version 1.0
 */


/*
* Get counters
* inline {@internal выделяем из raw данных количественную статистику}}
* @date 22.01.2008
* @package framework
* @author Rodion Konnov
* @contact kindzadza@mail.ru
* @version 1.83
*/
class Core_Stat extends Core_Stat_Set {
	public $now_time=0;
	public $type_id=0;
	public $current_types_init=array();

	function __construct( $_arrSet=array() ) {
		// используется при архивировании и удалении статистики. по хорошему надо лочить таблицы в промежутке вставка-архивирование
		$this->now_time=time();
		$this->set_current_types( $this->current_types_init, $this->s_types, (empty($_arrSet)?$this->s_types:$_arrSet) ); // если не указали то архивируем всё
	}

	function check_actual_types( $_strTbl='' ) {
		$this->current_types=$this->current_types_init;
		$this->set_current_types( $_arrNoAuto, $this->s_types, $this->s_notauto_arch[$_strTbl] );
		foreach( $this->current_types as $k=>$v ) {
			if ( in_array( $v, $_arrNoAuto ) ) {
				unSet( $this->current_types[$k] );
			}
		}
		return !empty( $this->current_types );
	}

	// полное количество
	function s_full_cheat() {
		if ( !$this->check_actual_types( 'stat_count' ) ) {
			return;
		}
		Core_Sql::setExec( '
			REPLACE INTO stat_count
			SELECT
				r.flg_type, r.item_id, IF(
					(SELECT c.counter FROM stat_count c WHERE c.flg_type=r.flg_type AND c.item_id=r.item_id)>0,
					(SELECT c.counter FROM stat_count c WHERE c.flg_type=r.flg_type AND c.item_id=r.item_id)+COUNT(*),
					COUNT(*)
				) counter, MAX(added) last_hit
			FROM stat_raw r
			WHERE r.added<"'.$this->now_time.'" AND r.flg_cheat=0'.(empty($this->current_types)?'':' AND r.flg_type IN("'.join( '", "', $this->current_types ).'")').'
			GROUP BY r.item_id, r.flg_type
		' );
	}

	// количество в день
	function s_by_day_cheat() {
		if ( !$this->check_actual_types( 'stat_by_day' ) ) {
			return;
		}
		Core_Sql::setExec( '
			REPLACE INTO stat_by_day
			SELECT
				r.flg_type, r.item_id, r.added thisdaystamp, FROM_UNIXTIME(r.added,"%Y%j") day_of_year, IF(
					(SELECT c.counter FROM stat_by_day c WHERE c.flg_type=r.flg_type AND c.item_id=r.item_id AND FROM_UNIXTIME(r.added,"%Y%j")=c.day_of_year)>0,
					(SELECT c.counter FROM stat_by_day c WHERE c.flg_type=r.flg_type AND c.item_id=r.item_id AND FROM_UNIXTIME(r.added,"%Y%j")=c.day_of_year)+COUNT(*),
					COUNT(*)
				) counter
			FROM stat_raw r
			WHERE r.added<"'.$this->now_time.'" AND r.flg_cheat=0'.(empty($this->current_types)?'':' AND r.flg_type IN("'.join( '", "', $this->current_types ).'")').'
			GROUP BY r.item_id, r.flg_type, day_of_year
		' );
	}

	// выставляем флаг "архивировано"
	function s_set_archived() {
		Core_Sql::setExec( 'UPDATE stat_raw SET flg_cheat=1 WHERE added<"'.$this->now_time.'"'.(empty($this->current_types)?'':' AND flg_type IN("'.join( '", "', $this->current_types ).'")').'' );
	}

	function s_settypeid( $_strVal='' ) {
		if ( empty( $_strVal )||!in_array( $_strVal, $this->s_types ) ) {
			return false;
		}
		$this->type_id=array_search( $_strVal, $this->s_types );
		return true;
	}

	function s_counter( $_arrSet=array() ) {
		if ( empty( $_arrSet['item_id'] )||!$this->s_settypeid( @$_arrSet['type'] ) ) {
			return false;
		}
		if ( !$this->s_getcounter( $_intTmp, $_arrSet ) ) {
			Core_Sql::setInsert( 'stat_count', array(
				'flg_type'=>$this->type_id,
				'item_id'=>$_arrSet['item_id'],
				'last_hit'=>time(),
			) );
		}
		Core_Sql::setExec( 'UPDATE stat_count SET counter=counter+'.(empty( $_arrSet['num'] )?1:$_arrSet['num']).' 
			WHERE flg_type="'.$this->type_id.'" AND item_id="'.$_arrSet['item_id'].'"' );
		return true;
	}

	function s_getcounter( &$intRes, $_arrSet=array() ) {
		if ( empty( $_arrSet['item_id'] )||!$this->s_settypeid( @$_arrSet['type'] ) ) {
			return false;
		}
		$_arrRes=Core_Sql::getRecord( 'SELECT * FROM stat_count WHERE flg_type="'.$this->type_id.'" AND item_id="'.$_arrSet['item_id'].'"' );
		if ( empty( $_arrRes['item_id'] ) ) {
			return false;
		}
		$intRes=$_arrRes['counter'];
		return true;
	}

	function s_setcounterbytype( $_arrSet=array() ) {
		if ( empty( $_arrSet['item_id'] )||!$this->s_settypeid( @$_arrSet['type'] ) ) {
			return false;
		}
		Core_Sql::setExec( 'UPDATE stat_count SET counter="'.$_arrSet['num'].'" WHERE flg_type="'.$this->type_id.'" AND item_id="'.$_arrSet['item_id'].'" LIMIT 1' );
		return true;
	}

	function s_getstat( &$arrRes, &$arrFilter, $_arrSet=array() ) {
		if ( empty( $_arrSet['type'] )||!in_array( $_arrSet['type'], $this->s_types ) ) {
			return false;
		}
		$_strSql='
			SELECT item_id, counter, FROM_UNIXTIME(last_hit,"%e %b %Y") date
			FROM stat_count
			WHERE flg_type="'.array_search( $_arrSet['type'], $this->s_types ).'"';
		// generate query
		if ( !empty( $_arrSet['filter']['order'] ) ) {
			$_arrPrt=explode( "+", $_arrSet['filter']['order'] );
			$_strSql.=' ORDER BY '.$_arrPrt[0].' '.( ( $_arrPrt[1]=='up' ) ? 'DESC':'ASC' );
		}
		if ( !empty( $_arrSet['filter']['limit'] ) ) {
			$_strSql.=' LIMIT '.$_arrSet['filter']['limit'];
		}
		$arrRes=Core_Sql::getKeyRecord( $_strSql );
		$arrFilter=$_arrSet['filter'];
		if ( empty( $arrRes ) ) {
			return false;
		}
		return true;
	}

	function s_getstat_by_day( &$arrRes, $arrFilter, $_arrSet=array() ) {
		if ( empty( $_arrSet['type'] )||!in_array( $_arrSet['type'], $this->s_types ) ) {
			return false;
		}
		$_strSql='
			SELECT counter, FROM_UNIXTIME(thisdaystamp,"%Y-%m-%d") date
			FROM stat_by_day
			WHERE flg_type="'.array_search( $_arrSet['type'], $this->s_types ).'" AND item_id="'.$_arrSet['item_id'].'"';
		// generate query
		if ( !empty( $_arrSet['filter']['order'] ) ) {
			$_arrPrt=explode( "+", $_arrSet['filter']['order'] );
			$_strSql.=' ORDER BY '.$_arrPrt[0].' '.( ( $_arrPrt[1]=='up' ) ? 'DESC':'ASC' );
		}
		if ( !empty( $_arrSet['filter']['limit'] ) ) {
			$_strSql.=' LIMIT '.$_arrSet['filter']['limit'];
		}
		$arrRes=Core_Sql::getKeyRecord( $_strSql );
		$arrFilter=$_arrSet['filter'];
		if ( empty( $arrRes ) ) {
			return false;
		}
		return true;
	}

	// удаление статистики по типу и id айтема
	function s_delstat_items_types( $_mixI=array(), $_mixT=array() ) {
		if ( empty( $_mixI )||empty( $_mixT ) ) {
			return false;
		}
		$_arrI=!is_array( $_mixI )?array( $_mixI ):$_mixI;
		$_arrT=!is_array( $_mixT )?array( $_mixT ):$_mixT;
		$_arrT=array_keys( array_intersect( $this->s_types, $_arrT ) );
		Core_Sql::setExec( 'DELETE FROM stat_raw WHERE item_id IN("'.join( '", "', $_arrI ).'") AND flg_type IN("'.join( '", "', $_arrT ).'")' );
		Core_Sql::setExec( 'DELETE FROM stat_count WHERE item_id IN("'.join( '", "', $_arrI ).'") AND flg_type IN("'.join( '", "', $_arrT ).'")' );
		return true;
	}

	function s_set_counter( $_intItem=0, $_intViews=0 ) {
		if ( empty( $_intItem )||empty( $this->current_types ) ) {
			return false;
		}
		Core_Sql::setExec( 'UPDATE stat_count SET counter='.Core_Sql::fixInjection( $_intViews ).' WHERE flg_type="'.$this->current_types[0].'" AND item_id="'.$_intItem.'" LIMIT 1' );
		return true;
	}

	function s_delstat_archived() {
		Core_Sql::setExec( 'DELETE FROM stat_raw WHERE flg_cheat=1' );
	}
}
?>