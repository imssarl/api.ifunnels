<?php


/**
 * функционал для поддержки таблицы в которой хранятся ссылки на таблицы которые пспользуются в моделях
 * инициализация и добавление новых
 * добавить фнкционал мэнеджмента таблицы в админке TODO!!! 24.01.2012
 */
class Core_i18n_Dynamic_Table extends Core_Data_Storage {

	protected $_table='lng_tables';

	protected $_fields=array( 'id', 'title' );

	public function check( $_flgRecurrent=false ) {
		if ( $this->onlyCell()->getList( $this->id )->checkEmpty() ) {
			return true;
		}
		if ( $_flgRecurrent ) { // запись так и не создалась
			return false;
		}
		$this->setEntered( array( 'title'=>$this->_cashe['title'] ) )->set(); // создаём запись
		return $this->withTitle( $this->_cashe['title'] )->check( true );
	}

	/**
	 * фильтр: ищем по тайтлу
	 *
	 * @var integer
	 */
	protected $_withTitle='';

	protected function init() {
		parent::init();
		$this->_withTitle='';
	}

	public function withTitle( $_str='' ) {
		if ( empty( $_str ) ) {
			return $this;
		}
		$this->_withTitle=$_str;
		$this->_cashe['title']=$this->_withTitle;
		return $this;
	}

	protected function assemblyQuery() {
		parent::assemblyQuery();
		if ( !empty( $this->_withTitle ) ) {
			$this->_crawler->set_where( 'd.title='.Core_Sql::fixInjection($this->_withTitle) );
		}
	}
}
?>