<?php


/**
 * File's groups (virtual folders)
 */
class Core_Files_Group extends Core_Data_Storage {

	/**
	 * список полей таблицы, лишние поля в данных будут удалены при сохранении
	 *
	 * @var array
	 */
	protected $_fields=array( 'id', 'user_id', 'flg_utilization', 'sysname', 'title', 'description', 'deleted', 'edited', 'added' );

	/**
	 * имя таблицы в которую сохраняются данные
	 *
	 * @var string
	 */
	protected $_table='file_group';

	/**
	 * список статусов для поля flg_utilization
	 *
	 * @var array
	 */
	private $_utilization=array(
		'exists'=>0,
		'deleted'=>1
	);

	/**
	 * аспект кторый вызывается до выполнения set()
	 * проверка введённых данных
	 *
	 * @return boolean
	 */
	protected function beforeSet() {
		if ( !$this->_data->setFilter()->setChecker( array(
			'title'=>empty( $this->_data->filtered['title'] ),
		) )->check() ) {
			$this->_data->getErrors( $this->_errors );
			return false;
		}
		if ( empty( $this->_data->filtered['id'] ) ) {
			if ( empty( $this->_data->filtered['sysname'] ) ) { // первый раз можно сгенерить sysname по title
				$this->_data->setElement( 'sysname', Core_String::getInstance( $this->_data->filtered['title'] )->toSystem( '_' ) );
			}
			if ( $this->onlyOne()->withSysName( $this->_data->filtered['sysname'] )->getList( $_arrTmp )->checkEmpty() ) {
				$this->_errors['sysname_exists']=true;
				return false;
			}
		}
		if ( !$this->_data->setChecker( array(
			'sysname'=>empty( $this->_data->filtered['sysname'] ),
		) )->check() ) {
			$this->_data->getErrors( $this->_errors );
			return false;
		}
		$this->_data->setElement( 'flg_utilization', (empty( $this->_data->filtered['flg_utilization'] )? $this->_utilization['exists']:$this->_utilization['deleted']) );
		if ( !empty( $this->_data->filtered['flg_utilization'] ) ) {
			$this->_data->setElement( 'deleted', time() );
		}
		return true;
	}

	/**
	 * при дублировании меняем название группы
	 *
	 * @return void
	 */
	public function changeFields( &$arrRes ) {
		unSet( $arrRes['sysname'] );
		$arrRes['title']=$arrRes['title'].'_'.time();
	}

	/**
	 * при удалении, группа не удаляется а помечается как удалённая
	 * вместе с группой помечаются и файлы
	 * имеет право только владелец данных групп
	 *
	 * если надо удалить файлы только определённого владельца 
	 * вызываем так $obj->onlyOwner()->utilization();
	 *
	 * @return boolean
	 */
	public function utilization() {
		if ( empty( $this->_withIds ) ) {
			return false;
		}
		if ( !$this->onlyIds()->getList( $_arrIds )->checkEmpty() ) { // ids групп
			return false;
		}
		// помечаем файлы в данных группах
		// помечать файлы не будем т.к. в группе могут быть файлы с разным статусом
		// чтобы при восстановлении группы статусы не изменились их нельзя менять
		// утилизированные группы не отображаются на фронтэнде
		/*$_files=new Core_Files_Info();
		if ( $this->_onlyOwner ) {
			$_files->onlyOwner();
		}
		$_files->withGroups( $_arrIds )->utilization();*/
		// помечаем группы
		Core_Sql::setExec( 'UPDATE '.$this->_table.' SET flg_utilization='.$this->_utilization['deleted'].', deleted='.time().' WHERE id IN('.Core_Sql::fixInjection( $_arrIds ).')' );
		return true;
	}

	/**
	 * физически удаляет crontab скрипт (см. Core_Files_Scavenger)
	 * вместе с группой удаляем и файлы
	 * т.к. это будет делать скрипт ->onlyOwner в этом случае использовать ненадо
	 *
	 * @return boolean
	 */
	public function del() {
		if ( !$this->onlyIds()->onlyDeleted()->toScavenger()->getList( $_arrIds )->checkEmpty() ) { // ids групп
			return false;
		}
		// удаляем файлы
		$_files=new Core_Files_Info();
		$_files->withGroups( $_arrIds )->del();
		// удаляем группы
		$this->withIds( $_arrIds );
		return parent::del();
	}

	/**
	 * фильтр: только удалённые файлы
	 *
	 * @var boolean
	 */
	protected $_onlyDeleted=false;

	/**
	 * фильтр: при физическом удалении файлов
	 *
	 * @var array
	 */
	protected $_toScavenger=false;

	/**
	 * фильтр: с данным системным именем
	 *
	 * @var string
	 */
	protected $_withSysName='';

	protected function init() {
		parent::init();
		$this->_onlyDeleted=false;
		$this->_toScavenger=false;
		$this->_withSysName='';
	}

	// используется сборщиком мусора
	public function onlyDeleted() {
		$this->_onlyDeleted=true;
		return $this;
	}

	public function toScavenger() {
		$this->_toScavenger=true;
		return $this;
	}

	public function withSysName( $_str='' ) {
		$this->_withSysName=$_str;
		return $this;
	}

	protected function assemblyQuery() {
		parent::assemblyQuery();
		if ( !empty( $this->_withSysName ) ) {
			$this->_crawler->set_where( 'd.sysname='.Core_Sql::fixInjection( $this->_withSysName ) );
		}
		$this->_crawler->set_where( 'd.flg_utilization='.($this->_onlyDeleted? $this->_utilization['deleted']:$this->_utilization['exists']) );
		if ( $this->_toScavenger ) {
			$this->_crawler->set_where( 'd.deleted>='.time().'-'.Core_Files_Scavenger::INTERVAL );
		}
	}
}
?>