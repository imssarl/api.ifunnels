<?php


/**
 * функционал для поддержки таблицы в которой хранятся ссылки на поля в таблицах которые пспользуются в моделях
 * инициализация и добавление новых
 * добавить фнкционал мэнеджмента таблицы в админке TODO!!! 24.01.2012
 */
class Core_i18n_Dynamic_Reference extends Core_Data_Storage {

	protected $_table='lng_reference';

	protected $_fields=array( 'id', 'table_id', 'title' );

	public function getInstalled() {
		return $this->_installed;
	}

	public function check( $_arrFields, $_flgRecurrent=false ) {
		if ( $this->toSelect()->getList( $this->_installed )->checkDiff( $_arrFields ) ) { // если список полей==списку полей в бд
			return true;
		}
		if ( $_flgRecurrent ) {
			return false;
		}
		$this->setFields( $_arrFields );
		return $this->withTableId( $this->_cashe['table_id'] )->check( $_arrFields, true );
	}

	private function checkDiff( $_arrFields=array() ) {
		$_arrTmp=Core_Common::fullArrayDiff( $_arrFields, $this->_installed );
		return empty( $_arrTmp );
	}

	private function setFields( $_arrFields=array() ) {
		$this->_installed=array_flip( $this->_installed );
		foreach( $_arrFields as $k=>$v ) {
			if ( isSet( $this->_installed[$v] ) ) {
				unSet( $_arrFields[$v], $this->_installed[$v] );
			}
		}
		// удаляем те которых нету во входном массиве $_arrFields
		$this->withIds( array_values( $this->_installed ) )->del();
		// добавлять нечего
		if ( empty( $_arrFields ) ) {
			return;
		}
		// добавляем те которые остались от $_arrFields
		foreach( $_arrFields as $v ) {
			$_arrIns[]=array( 'title'=>$v, 'table_id'=>$this->_cashe['table_id'] );
		}
		Core_Sql::setMassInsert( 'lng_reference', $_arrIns );
	}

	/**
	 * фильтр: ищем по id таблицы
	 *
	 * @var integer
	 */
	protected $_withTableId='';

	protected function init() {
		parent::init();
		$this->_withTableId='';
	}

	public function withTableId( $_str='' ) {
		if ( empty( $_str ) ) {
			return $this;
		}
		$this->_withTableId=$_str;
		$this->_cashe['table_id']=$this->_withTableId;
		return $this;
	}

	protected function assemblyQuery() {
		parent::assemblyQuery();
		if ( !empty( $this->_withTableId ) ) {
			$this->_crawler->set_where( 'd.table_id='.$this->_withTableId );
		}
	}
}
?>