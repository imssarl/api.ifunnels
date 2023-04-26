<?php


/**
 * User Delete
 * @internal удаление из всех таблиц с префиксом u_ ( за исключением u_groups u_rights u_rights2group
 */


class Core_Users_Delete {
	private $media_collector='media_collector@root.dev'; // системный
	private $collector_id=0;
	private $with_child=false; // у аккаунтов нет подчинённых аккаунтов
	private $keep_media=false; // файлы переходят коллектору (true)
	private $ids=array(); // ids удаляемых аккаунтов

	private $objUsers; // объект управления пользователями

	public function __construct( Core_Users_Sample $obj ) {
		$this->objUsers=$obj;
	}

	public function set( $_mixIds=array() ) {
		if ( empty( $_mixIds ) ) {
			return false;
		}
		$this->ids=!is_array( $_mixIds )?array( $_mixIds ):$_mixIds;
		if ( $this->with_child ) {
			$this->collect_child_ids();
		}
		if ( $this->keep_media ) {
			// тут $this->ids может опустеть, в случае если удаляемый аккаунт и есть коллектор )
			$this->get_collector_id();
			$this->ids=array_diff( $this->ids, array( $this->collector_id ) );
		}
		return true;
	}

	private function collect_child_ids() {
		$_arrCids=Core_Sql::getField( 'SELECT id FROM u_users WHERE parent_id IN("'.join( '", "', $this->ids ).'")' ); // берём чайлдов если есть парент профайлы
		if ( !empty( $_arrCids ) ) { // если есть чайлды подмашиваем их в массив для удаления
			$this->ids=array_unique( array_merge( $this->ids, $_arrCids ) );
		}
	}

	// чекаем системного пользователя и если его нет в системе то создаём
	private function get_collector_id() {
		if ( $this->objUsers->onlyCell()->onlyIds()->withEmail( $this->media_collector )->getList( $this->collector_id )->checkEmpty() ) {
			return true;
		}
		if ( !$this->objUsers->setData( array(
			'email'=>$this->media_collector,
			'passwd'=>md5( 'media_collector' ),
			'nickname'=>'media_collector',
			'flg_status'=>1,
		) )->createSysUser() ) {
			throw new Exception( Core_Errors::DEV.'|Can\'t create system user' );
		}
		$this->objUsers->getEntered( $_arrUsr );
		$this->collector_id=$_arrUsr['id'];
		return !empty( $this->collector_id );
	}

	public function get_ids( &$arrRes ) {
		if ( empty( $this->ids ) ) {
			return false;
		}
		$arrRes=$this->ids;
		return true;
	}

	public function initiate() {
		//$this->items_delete(); // сначала удаляются йтемы с файлами
		//$this->media_delete(); // потом удаляются оставшееся медиа
		// возможно есть смысл хранить файлы айтемов с привязкой по user_id данного пользователя
		// тогда можно обойтись без использования класса items TODO!!!
		// удаляем пользователей
		Core_Sql::setExec( '
			DELETE u, group_link
			FROM u_users u
			LEFT JOIN u_link group_link ON group_link.user_id=u.id
			WHERE u.id IN("'.join( '", "', $this->ids ).'")
		' );
		return true;
	}

	private function items_delete() {
		// пока так, потом нужно будет сделать через items т.к. в таком варианты файлы на диске физически не удаляться
		Core_Sql::setExec( '
			DELETE i, f
			FROM u_item i
			LEFT JOIN u_item_field f ON f.item_id=i.id
			WHERE i.user_id IN("'.join( '", "', $this->ids ).'")
		' );
		/*
		$this->factory( 'objItems' );
		$this->objItems->del_items_byuserids( $this->ids ); // профайлы пользователей
		*/
	}

	private function media_delete() {
		if ( $this->keep_media ) {
			Core_Sql::setExec( 'UPDATE f_files SET user_id="'.$this->collector_id.'" WHERE user_id IN("'.join( '", "', $this->ids ).'")' );
			return true;
		}
		// заюзать класс медиа для физического удаления файлов TODO!!!
		/*$_arrFids=Core_Sql::getField( 'SELECT id FROM f_files WHERE user_id IN("'.join( '", "', $_arrIds ).'")' ); // удаляем файлы и всё что с ними связано
		$_objM=new kids_media();
		// файлы
		if ( !empty( $_arrFids ) ) {
			$_objC->m_delcomment_items_types( $_arrFids, 'f_files' ); // комменты на файлы
			$_objTc->del_link_by_item_type( array( 'VideoCategories', 'VideoFlags', 'UserVideoCategories', 'UserFavorites', 'VideoServices' ), $_arrFids );// удаление айтемов из типов
			$_objM->m_del_files( $_arrFids ); // сами файлы
		}*/
	}

	// удаление файлов доделать как то покрасивей, хотя всёравно через этот класс надо TODO!!!
	public function del_selected_profile_media( $_mixIds=array() ) {
		if ( empty( $_mixIds ) ) {
			return false;
		}
		$this->delete_media_ids=!is_array( $_mixIds )?array( $_mixIds ):$_mixIds;
		if ( $this->keep_media ) {
			return $this->reassign_selected_files();
		} else {
			return $this->delete_selected_files();
		}
	}

	private function delete_selected_files() {
		return true;
	}

	// ассайним файло на системного пользователя
	private function reassign_selected_files() {
		Core_Sql::setExec( 'UPDATE f_files SET user_id="'.$this->get_collector_id().'" WHERE id IN("'.join( '", "', $this->delete_media_ids ).'")' );
		return true;
	}
}
?>