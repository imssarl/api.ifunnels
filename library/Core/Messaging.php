<?php
/**
 * Message System
 * @category framework
 * @package MessageSystem
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2008, Rodion Konnov
 * @author Rodion Konnov <kindzadza@mail.ru>
 * @date 22.03.2007
 * @version 0.5
 */


/**
 * Universal comments
 * @category framework
 * @package MessageSystem
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2008, Rodion Konnov
 * @author Rodion Konnov <kindzadza@mail.ru>
 * @date 11.08.2008
 * @version 0.6
 */


class Core_Messaging extends Core_Messaging_Storage {
	public $mc_tbl=array( 'id', 'storage_id', 'item_id', 'flg_type', 'flg_hide' );
	public $mc_types=array( 'c_items', 'f_files', 'files_review' );
	public $item_id=0;
	public $user_id=0; // если не указан то используется текущий user_id
	public $flg_type=0; // типы item_id. если не указан то 'c_items'

	public function __construct( $_arrSet=array() ) {
		parent::m_storage();
		if ( !empty( $_arrSet['item_id'] ) ) {
			$this->item_id=$_arrSet['item_id'];
		}
		if ( !empty( $_arrSet['user_id'] ) ) {
			$this->user_id=$_arrSet['user_id'];
		} elseif ( Zend_Registry::get( 'objUser' )->getId( $_int ) ) {
			$this->user_id=$_int;
		}
		if ( !empty( $_arrSet['flg_type'] ) ) {
			if ( ( $_intKey=array_search( $_arrSet['flg_type'], $this->mc_types ) )!==false ) {
				$this->flg_type=$_intKey;
			}
		}
	}

	public function get_comments( &$arrRes, &$arrPg, $_arrSet=array() ) {
		if ( empty( $_arrSet['item_id'] )&&!empty( $this->item_id ) ) {
			$_arrSet['item_id']=$this->item_id;
		}
		if ( empty( $_arrSet['flg_type'] )&&isSet( $this->flg_type ) ) {
			$_arrSet['flg_type']=$this->flg_type;
		}
		$objQ=new Core_Sql_Qcrawler();
		$objQ->set_select( 'mc.id' );
		$objQ->set_from( 'm_comment mc' );
		$objQ->set_from( 'LEFT JOIN m_storage ms ON ms.id=mc.storage_id' );
		if ( isSet( $_arrSet['flg_hide'] ) ) {
			$objQ->set_where( 'mc.flg_hide="'.$_arrSet['flg_hide'].'"' );
		}
		if ( isSet( $_arrSet['flg_type'] ) ) {
			$objQ->set_where( 'mc.flg_type="'.$_arrSet['flg_type'].'"' );
		}
		if ( !empty( $_arrSet['item_id'] ) ) {
			$objQ->set_where( 'mc.item_id="'.$_arrSet['item_id'].'"' );
		}
		$_arrSet['order']=empty( $_arrSet['order'] ) ? 'ms.added--up':$_arrSet['order'];
		if ( !empty( $_arrSet['order'] ) ) {
			$objQ->set_order_sort( $_arrSet['order'] );
		}
		$objQ->get_sql( $_strQ, $arrPg, $_arrSet );
		$_arrIds=Core_Sql::getField( $_strQ );
		if ( empty( $_arrIds ) ) {
			return false;
		}
		$arrRes=Core_Sql::getAssoc( '
			SELECT mc.id cid, mc.flg_hide, mc.item_id iid, u.id uid, u.nickname, ms.*
			FROM m_comment mc
			LEFT JOIN m_storage ms ON ms.id=mc.storage_id
			LEFT JOIN u_users u ON u.id=ms.user_id
			WHERE mc.id IN("'.join( '", "', $_arrIds ).'")
			ORDER BY ms.added DESC
		' );
		return true;
	}

	function m_setcomm( &$arrRes, &$arrErr, $_arrSet=array() ) {
		$_arrDat=Core_A::array_check( $_arrSet, $this->post_filter );
		if ( empty( $_arrDat['item_id'] ) ) {
			$_arrDat['item_id']=$this->item_id;
		}
		if ( !$this->error_check( $arrRes, $arrErr, $_arrDat, array(
			'message'=>empty( $_arrDat['message'] ),
			'item_id'=>empty( $_arrDat['item_id'] ),
		) ) ) {
			return false;
		}
		$arrS=$_arrDat;
		if ( !empty( $_arrDat['id'] ) ) {
			$arrS['id']=Core_Sql::getCell( 'SELECT storage_id FROM m_comment WHERE id="'.$_arrDat['id'].'"' );
		} else {
			$_arrDat['flg_hide']=1;
		}
		if ( !$this->m_setcontent( $arrS ) ) {
			$arrErr['storage']=1;
			return false;
		} else {
			$_arrDat['storage_id']=$this->storage_id;
		}
		$_arrDat['flg_type']=$this->flg_type;
		$_arrDat['flg_hide']=empty( $_arrDat['flg_hide'] )?0:1;
		$this->comment_id=Core_Sql::setInsertUpdate( 'm_comment', $this->get_valid_array( $_arrDat, $this->mc_tbl ) );
		return true;
	}

	function m_cmdcomm( $_arrSet=array() ) {
		if ( empty( $_arrSet['mode'] ) ) {
			return false;
		}
		switch ( $_arrSet['mode'] ) {
			case 'switch': Core_Sql::setExec( 'UPDATE m_comment SET flg_hide=1-flg_hide WHERE id="'.$_arrSet['id'].'" LIMIT 1' ); break;
			case 'delbyid':
				if ( empty( $_arrSet['ids'] ) ) {
					return false;
				}
				$intIds=Core_Sql::getCell( 'SELECT storage_id FROM m_comment WHERE id IN("'.join( '", "', $_arrSet['ids'] ).'")' );
				if ( !$this->m_delcontent( $intIds ) ) {
					return false;
				}
				Core_Sql::setExec( 'DELETE FROM m_comment WHERE id IN("'.join( '", "', $_arrSet['ids'] ).'")' );
			break;
			case 'delbyitem':
				if ( empty( $_arrSet['ids'] ) ) {
					$_arrSet['ids']=array( $this->item_id );
				}
				$arrIds=Core_Sql::getField( 'SELECT storage_id FROM m_comment WHERE item_id IN("'.join( '", "', $_arrSet['ids'] ).'")' );
				if ( !$this->m_delcontent( $arrIds ) ) {
					return false;
				}
				Core_Sql::setExec( 'DELETE FROM m_comment WHERE item_id IN("'.join( '", "', $_arrSet['ids'] ).'")' );
			break;
			case 'delbyuser':
				$_arrSet['mode']='by_user_id';
				if ( !$this->m_getcontent( $arrIds, $_arrSet ) ) {
					return false;
				}
				if ( !$this->m_delcontent( $arrIds ) ) {
					return false;
				}
				Core_Sql::setExec( 'DELETE FROM m_comment WHERE storage_id IN("'.join( '", "', $arrIds ).'")' );
			break;
			case 'delbytype':
				$arrIds=Core_Sql::getField( 'SELECT storage_id FROM m_comment WHERE flg_type="'.$this->flg_type.'"' );
				if ( !$this->m_delcontent( $arrIds ) ) {
					return false;
				}
				Core_Sql::setExec( 'DELETE FROM m_comment WHERE storage_id IN("'.join( '", "', $arrIds ).'")' );
			break;
		}
		return true;
	}

	function m_get_comment_count_byitem( &$intCount, $_intItem=0, $flg_hide=null ) {
		$_intItem=empty( $_intItem ) ? $this->item_id:$_intItem;
		if ( empty( $_intItem ) ) {
			return false;
		}
		if ($flg_hide!==null) {
			$intCount=Core_Sql::getCell( 'SELECT COUNT(*) FROM m_comment WHERE flg_hide="'.(int)$flg_hide.'" AND flg_type="'.$this->flg_type.'" AND item_id="'.$_intItem.'"' );
		} else {
			$intCount=Core_Sql::getCell( 'SELECT COUNT(*) FROM m_comment WHERE flg_type="'.$this->flg_type.'" AND item_id="'.$_intItem.'"' );
		}
		if ( empty( $intCount ) ) {
			$intCount=0;
		}
		return $intCount;
	}

	// удаление комментов по типу и id айтема
	function m_delcomment_items_types( $_mixI=array(), $_mixT=array() ) {
		if ( empty( $_mixI )||empty( $_mixT ) ) {
			return false;
		}
		$_arrI=!is_array( $_mixI )?array( $_mixI ):$_mixI;
		$_arrT=!is_array( $_mixT )?array( $_mixT ):$_mixT;
		$_arrT=array_keys( array_intersect( $this->mc_types, $_arrT ) );
		Core_Sql::setExec( '
			DELETE ms, mc
			FROM m_storage ms
			INNER JOIN m_comment mc ON mc.storage_id=ms.id
			WHERE mc.item_id IN("'.join( '", "', $_arrI ).'") AND mc.flg_type IN("'.join( '", "', $_arrT ).'")
		' );
		return true;
	}

	// удаление комментов определённого пользователя
	function m_delcomment_users( $_mixIds=array() ) {
		if ( empty( $_mixIds ) ) {
			return false;
		}
		$_arrIds=!is_array( $_mixIds )?array( $_mixIds ):$_mixIds;
		Core_Sql::setExec( '
			DELETE ms, mc
			FROM m_storage ms
			INNER JOIN m_comment mc ON mc.storage_id=ms.id
			WHERE ms.user_id IN("'.join( '", "', $_arrIds ).'")
		' );
		return true;
	}
}
?>