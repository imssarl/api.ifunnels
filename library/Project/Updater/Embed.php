<?php
class Project_Updater_Embed extends Core_Updater_Abstract {

	public function update( Core_Updater $obj ) {
		$obj->logger->info( 'start Project_Updater_Embed' );
		if ( !$this->dbPrepare() ) {
			$obj->logger->err( 'dbPrepare false' );
		}
		if ( !$this->mooveVideo() ) {
			$obj->logger->err( 'mooveVideo false' );
		}
		$obj->logger->info( 'end Project_Updater_Embed' );
	}

	/**
	 * Создаём таблицу для хранение эмбед-видео
	 * Создаём типы категорий и категории для типа Embed Manager Source
	 * Переносим пользовательские категории из старой системы категорий в новую (тип Embed Manager)
	 */
	private function dbPrepare() {
		$_intSourceId=Core_Sql::setInsert( 'category_types', array( 'flg_user'=>0, 'type'=>'simple', 'title'=>'Embed Manager Source', 'description'=>'owner of users embed video in CNM Video Manager module', 'added'=>time() ) );
		$_arrOlds=Core_Sql::getAssoc( 'SELECT id, title FROM tc_categories WHERE type_id=2' );
		foreach( $_arrOlds as $v ) {
			$_intNewId=Core_Sql::setInsert( 'category_category', array( 'type_id'=>$_intSourceId, 'title'=>$v['title'] ) );
			if ( !$this->reassignOwnerCategory( $v['id'], $_intNewId ) ) {
				return false;
			}
		}
		$_intCatsId=Core_Sql::setInsert( 'category_types', array( 'flg_user'=>1, 'type'=>'simple', 'title'=>'Embed Manager', 'description'=>'users category for CNM Video Manager module', 'added'=>time() ) );
		$_arrOlds=Core_Sql::getAssoc( 'SELECT id, user_id, title FROM tc_categories WHERE type_id=1' );
		foreach( $_arrOlds as $v ) {
			$_intNewId=Core_Sql::setInsert( 'category_category', array( 'user_id'=>$v['user_id'], 'type_id'=>$_intCatsId, 'title'=>$v['title'] ) );
			if ( !$this->reassignUserCategory( $v['id'], $_intNewId ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Назначаем новые ids для категорий видео
	 */
	private function reassignOwnerCategory( $_intOldId=0, $_intNewId=0 ) {
		if ( empty( $_intOldId )||empty( $_intNewId ) ) {
			return false;
		}
		Core_Sql::setExec( 'UPDATE cnm_vm_item_field SET content='.$_intNewId.' WHERE stencil_field_id=2 AND content='.$_intOldId );
		return true;
	}

	/**
	 * Назначаем новые ids для категорий видео
	 */
	private function reassignUserCategory( $_intOldId=0, $_intNewId=0 ) {
		if ( empty( $_intOldId )||empty( $_intNewId ) ) {
			return false;
		}
		Core_Sql::setExec( 'UPDATE cnm_vm_item_field SET content='.$_intNewId.' WHERE stencil_field_id=1 AND content='.$_intOldId );
		return true;
	}

	/**
	 * Переносим видео из старой системы в новую
	 */
	private function mooveVideo() {
		$items=new Core_Items( 'cnm_video_manager_items' );
		$items->get_items( $arrRes );
		if ( empty( $arrRes ) ) {
			return false;
		}
		Core_Sql::setExec("DROP TABLE IF EXISTS content_video");
		Core_Sql::setExec( '
			CREATE TABLE `content_video` (
				`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
				`user_id` INT(11) UNSIGNED NOT NULL DEFAULT "0",
				`category_id` INT(11) UNSIGNED NOT NULL DEFAULT "0",
				`source_id` INT(11) UNSIGNED NOT NULL DEFAULT "0",
				`title` VARCHAR(255) NOT NULL,
				`embed_code` TEXT NULL,
				`url_of_video` TEXT NULL,
				`edited` INT(11) UNSIGNED NOT NULL DEFAULT "0",
				`added` INT(11) UNSIGNED NOT NULL DEFAULT "0",
				PRIMARY KEY (`id`)
			)
			ENGINE=MyISAM
			ROW_FORMAT=DEFAULT
		' );
		foreach( $arrRes as $v ) {
			$arrVid=array(
				'id'=>$v['id'],
				'user_id'=>$v['user_id'],
				'category_id'=>$v['arrFields']['category'],
				'source_id'=>(empty( $v['arrFields']['source'] )?0:$v['arrFields']['source']),
				'title'=>$v['arrFields']['title'],
				'body'=>$v['arrFields']['embed_code'],
				'url_of_video'=>$v['arrFields']['url_of_video'],
				'edited'=>(empty( $v['edited'] )?$v['added']:$v['edited']),
				'added'=>$v['added'],
			);
			Core_Sql::setInsert( 'content_video', $arrVid );
		}
		return true;
	}
}
?>