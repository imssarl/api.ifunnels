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
 * Universal messaging content storage
 * @category framework
 * @package MessageSystem
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2008, Rodion Konnov
 * @author Rodion Konnov <kindzadza@mail.ru>
 * @date 22.03.2007
 * @version 0.2
 */


class Core_Messaging_Storage extends Core_Services {
	public $ms_tbl=array( 'id', 'user_id', 'title', 'message', 'edited', 'added' );

	function __construct() {}

	function m_getcontent( &$arrRes, $_arrSet=array() ) {
		if ( empty( $_arrSet['mode'] ) ) {
			return false;
		}
		switch( $_arrSet['mode'] ) {
			case 'by_ids': $arrRes=Core_Sql::getAssoc( '
				SELECT *
				FROM m_storage
				WHERE id IN("'.join( '", "', $_arrSet['ids'] ).'")
				ORDER BY added DESC
			' ); break;
			case 'by_id': $arrRes=Core_Sql::getRecord( '
				SELECT *
				FROM m_storage
				WHERE id="'.$_arrSet['id'].'"
				LIMIT 1
			' ); break;
			case 'by_user_id_ids': $arrRes=Core_Sql::getAssoc( '
				SELECT *
				FROM m_storage
				WHERE user_id="'.$_arrSet['user_id'].'"
				ORDER BY added DESC
			' ); break;
			case 'by_user_id':
				if ( empty( $_arrSet['user_id'] )&&Zend_Registry::get( 'objUser' )->getId( $_int ) ) {
					$_arrSet['user_id']=$_int;
				}
				$arrRes=Core_Sql::getField( 'SELECT id FROM m_storage WHERE user_id="'.$_arrSet['user_id'].'" ORDER BY added DESC' );
			break;
		}
		return !empty( $arrRes );
	}

	function m_setcontent( $_arrDta=array() ) {
		if ( empty( $_arrDta['message'] ) ) {
			return false;
		}
		if ( empty( $_arrDta['user_id'] )&&!empty( $this->user_id ) ) {
			$_arrDta['user_id']=$this->user_id;
		}
		if ( empty( $_arrDta['id'] ) ) {
			$_arrDta['added']=time();
		} else {
			$_arrDta['edited']=time();
		}
		$this->storage_id=Core_Sql::setInsertUpdate( 'm_storage', $this->get_valid_array( $_arrDta, $this->ms_tbl ) );
		return true;
	}

	function m_delcontent( $_arrIds=array() ) {
		if ( empty( $_arrIds ) ) {
			return false;
		}
		if ( !is_array( $_arrIds ) ) {
			$_arrIds=array( $_arrIds );
		}
		Core_Sql::setExec( 'DELETE FROM m_storage WHERE id IN("'.join( '", "', $_arrIds ).'")' );
		return true;
	}
}
?>