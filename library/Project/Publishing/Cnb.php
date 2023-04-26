<?php
/**
 * WorkHorse Framework
 *
 * @category Project
 * @package Project_Publishing
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2005-2010, web2innovation
 * @author Rodion Konnov <kindzadza@mail.ru>
 * @date 02.02.2010
 * @version 0.1
 */


/**
 * Create CNB projects
 *
 * @category Project
 * @package Project_Wpress
 * @copyright Copyright (c) 2005-2010, web2innovation
 * @license http://opensource.org/licenses/ MIT License
 */
class Project_Publishing_Cnb implements Project_Publishing_Interface {

	private $_userId=0;

	public function __construct() {
		if ( !Zend_Registry::get( 'objUser' )->getId( $_int ) ) {
			throw new Exception( ERR_PHP.'|Zend_Registry::get( \'objUser\' )->getId( $_int ) is not return an User Id' );
			return;
		}
		$this->_userId=$_int;
	}

	public function getType(){
		return Project_Sites::CNB ;
	}
	
	public function getOwnerId() {
		return $this->_userId;
	}

	public function set( Project_Publishing $object ) {
		$object->data->setFilter('strip_tags', 'trim', 'clear');
		// проверим не запустился ли проект пока мы его редактировали
		if ( !empty( $object->data->filtered['id'] )&&empty( $object->data->filtered['flg_status'] ) ) {
			$object->get( $_arrPrj, $object->data->filtered['id'] );
			$object->data->setElement( 'flg_status', $_arrPrj['flg_status'] );
			// если таки запустился - показываем ошибку (т.к. пользователь отослал все данные а можно только добавлять конетнт)
			if ( !$object->data->setChecker( array( 'flg_status'=>!empty( $object->data->filtered['flg_status'] ) ) )->check() ) {
				return false; 
			}
		}
		// проект в процессе
		if ( !empty( $object->data->filtered['flg_status'] )&&$object->data->filtered['flg_status']==1 ) {
			if ( empty( $_arrPrj ) ) {
				$object->get( $_arrPrj, $object->data->filtered['id'] );
			}
			$_arrPrj['title'] = $object->data->filtered['title'];
			$object->data->set( $_arrPrj )->setFilter(); // т.к. на форме все поля отключены берём их из бд
			if ( !Core_Sql::setInsertUpdate( $object->table, $object->data->setMask( $object->fields )->getValid() )) {
				return false;
			}
			return $this->addAdditional( $object );
		}
		// рестарт проекта
		if ( !empty( $object->data->filtered['flg_status'] )&&$object->data->filtered['flg_status']==2 ) {
			$object->data->setElement( 'flg_status', ( $object->data->filtered['restart'] ) ? 1 : 2 );
		}
		// если проект ещё не запущен или уже завершён - можно менять все поля (в шедуле удаляем весь контент)
		if ( !$object->data->setChecker( array(
			'title'=>empty( $object->data->filtered['title'] ),
			'flg_source'=>empty( $object->data->filtered['flg_source'] ),
			'flg_posting'=>empty( $object->data->filtered['flg_posting'] ),
		) )->check() ) {
			return false; 
		}
		if ( empty( $object->data->filtered['start'] ) ) {
			$object->data->setElement( 'start', time() );
		}
		if ( empty( $object->data->filtered['id'] ) ) {
			$object->data
				->setElement( 'added', time() )
				->setElement( 'user_id', $this->_userId );
		} else {
			$object->data->setElement( 'edited', time() );
		}
		$object->data->setElement( 'flg_type', Project_Sites::CNB );
		$object->data->setElement( 'id', Core_Sql::setInsertUpdate( $object->table, $object->data->setMask( $object->fields )->getValid() ) );
		if ( empty( $object->data->filtered['id'] ) ) {
			return false;
		}
		return $this->storeAdditional( $object );	
	}

	// send to Content or Rss
	private function storeAdditional( Project_Publishing $object ) {
		$obj=new Project_Publishing_Cnb_Content( $object->data );
		if ( $object->data->filtered['flg_source']==2 ) { // Keywords
			$obj=new Project_Publishing_Cnb_Keywords( $object->data );
		}		
		return $obj->generate();
	}	

}
?>