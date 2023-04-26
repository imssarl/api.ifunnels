<?php

 /**
 * функционал проверки контента
 */
class Project_Syndication_Content_Review extends Project_Syndication_Content {

	public static $rejectCause=array(
		1=>'wrong category', 
		2=>'spam', 
		3=>'too hype', 
		4=>'poor english'
	);

	/**
	* конструктор
	* переопределяем т.к. здесь конкретный проект не важен
	* @return void
	*/
	public function __construct() {}

	// проверить корректно ли работает разблокировка TODO!!! 26.05.2010
	private function unBlock() {
		Core_Sql::setExec( 'UPDATE '.$this->table.' SET blocked=0 WHERE blocked<='.(time()-60*60*24) );
	}

	public function unBlockById( $_intId=0 ) {
		if ( empty( $_intId ) ) {
			return false;
		}
		Core_Sql::setExec( 'UPDATE '.$this->table.' SET blocked=0 WHERE id='.Core_Sql::fixInjection( $_intId ) );
		return true;
	}

	// удаление из проекта КТ который был удалён из системы
	// чтобы проект не подвисал в пендинг статусе и т.д.
	private function clearDeleted() {
		$_arrIds=Core_Sql::getField( '(
			SELECT c.id FROM cs_content c
			LEFT JOIN hct_am_article a ON a.id=c.content_id
			WHERE c.flg_type=1 AND a.id IS NULL
		) UNION (
			SELECT c.id FROM cs_content c
			LEFT JOIN content_video v ON v.id=c.content_id
			WHERE c.flg_type=2 AND v.id IS NULL
		)' );
		if ( empty( $_arrIds ) ) {
			return;
		}
		$this->del( $_arrIds );
	}

	public function getList( &$mixRes ) {
		$this->clearDeleted();
		$this->unBlock();
		$this->withStatus( parent::$stat['pending'] );
		return parent::getList( $mixRes );
	}

	public function get( &$arrRes, $_intId=0 ) {
		if ( !parent::get( $arrRes, $_intId ) ) {
			return false;
		}
		if ( !empty( $arrRes['blocked'] ) ) {
			return false; // заблокирован другим пользователем
		}
		Core_Sql::setUpdate( $this->table, array( 'id'=>$_intId, 'blocked'=>time() ) ); // блокируем элемент для просмотра
		// данные контента
		if ( $arrRes['flg_type']==1 ) {
			$_obj=Project_Articles::getInstance();
		} elseif ( $arrRes['flg_type']==2 ) {
			$_obj=Project_Embed::getInstance();
		}
		if ( !$_obj->withIds( $arrRes['content_id'] )->onlyOne()->getList( $_arrRes ) ) {
			return false; // возможно котент удалили к этому времени
		}
		$arrRes['title']=$_arrRes['title'];
		$arrRes['body']=$_arrRes['body'];
		Project_Syndication_Project_Category::getWithTitle( $arrRes['project_id'], $arrRes['projectCats'] );
		return true;
	}

	public function set() {
		if ( !$this->_data->setFilter( array( 'trim', 'clear' ) )->setChecker( array(
			'id'=>empty( $this->_data->filtered['id'] ),
			'project_id'=>empty( $this->_data->filtered['project_id'] ), // понадобится дальше
			'flg_status'=>empty( $this->_data->filtered['flg_status'] ),
			'flg_cause'=>!empty( $this->_data->filtered['flg_status'] )&&$this->_data->filtered['flg_status']==parent::$stat['rejected']&&empty( $this->_data->filtered['flg_cause'] ),
		) )->check() ) {
			$this->_data->getErrors( $this->_errors['filtered'] );
			return false;
		}
		Core_Sql::setInsertUpdate( $this->table, $this->_data->setMask( $this->fields )->getValid() );
		// КТ прошел проверку копируем в отдельную таблицу. пользователь может только удалить КТ
		if ( $this->_data->filtered['flg_status'] == parent::$stat['approved'] ){
			$this->copyContent($this->_data);
		}		
		$this->updateProjectStatus( $this->_data->filtered['project_id'] );
		return true;
	}

	// если весь КТ просмотрен ставим нужный статус
	private function updateProjectStatus( $_intId=0 ) {
		$this->_projectId=$_intId;
		$this->getList( $_arrTmp );
		if ( !empty( $_arrTmp ) ) { // не всеь КТ проекта просмотрен (есть со статусом pending)
			$this->_projectId=0;
			return;
		}
		parent::getList( $_arrRes ); // берём все КТ проекта
		if ( empty( $_arrRes ) ) { // тут нужно поидее писать ошибку проекту
			return;
		}
		foreach( $_arrRes as $v ) {
			if ( $v['flg_status']==parent::$stat['rejected'] ) {
				Project_Syndication::status( 'rejected', $this->_projectId );
				// письмо в случае rejected у проекта
				Project_Syndication_Notification::statusRejected( $this->_projectId );
				return;
			}
		}
		Project_Syndication::status( 'approved', $this->_projectId );
		Project_Syndication_Notification::statusApproved( $this->_projectId );
		if ( $this->getProjectPostNum( $_intNum ) ) { // увеличиваем счётчик запланированного к постингу
			Project_Syndication_Counters::increasePlanned( $_intNum, Project_Syndication::getProjectOwnerId( $this->_projectId ) );
		}
	}
}
?>