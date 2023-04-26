<?php
class Project_Squeeze_Restrictions extends Core_Data_Storage {

	protected $_table='lpb_restrictions';
	protected $_fields=array('id', 'uid', 'flg_type', 'restrictions', 'added');
	
	public static function install(){
		$_obj=new Project_Squeeze_Restrictions();
		$_obj->getList( $_data );
		Core_Sql::setExec( "DROP TABLE IF EXISTS lpb_restrictions" );
		Core_Sql::setExec("CREATE TABLE lpb_restrictions ("
			."id INT(11) NOT NULL AUTO_INCREMENT,"
			."uid INT(11) NOT NULL DEFAULT '0',"
			."restrictions INT(10) NOT NULL DEFAULT '0',"
			."flg_type INT(1) NOT NULL DEFAULT '0'," // 0 - обновляется каждый месяц, 1 - единовременный взнс
			."added INT(11) UNSIGNED NOT NULL DEFAULT '0',"
			."UNIQUE INDEX id (id)"
		.")
		COLLATE='utf8_general_ci'
		ENGINE=InnoDB;");
		$_added=time();
		foreach( $_data as $_rest ){
			if( isset( $_rest['uid'] ) ){
				$_obj->setEntered( $_rest )->set();
			}else{
				$_obj->setEntered( array(
					'uid'=>$_rest['id'],
					'restrictions'=>$_rest['restrictions'],
					'flg_type'=>0,
				) )->set();
			}
			
		}
	}
	
	protected $_withUserId=array(); // по id пользователя
	protected $_withFlgType=false; // по id пользователя
	
	public function withUserId( $_arrIds=array() ) {
		$this->_withUserId=$_arrIds;
		return $this;
	}
	
	public function withFlgType( $_int=0 ) {
		$this->_withFlgType=$_int;
		return $this;
	}

	protected function assemblyQuery() {
		parent::assemblyQuery();
		if ( !empty( $this->_withUserId ) ) {
			$this->_crawler->set_where( 'd.uid IN ('.Core_Sql::fixInjection( $this->_withUserId ).')' );
		}
		if ( $this->_withFlgType !== false ) {
			$this->_crawler->set_where( 'd.flg_type='.Core_Sql::fixInjection( $this->_withFlgType ) );
		}
	}

	protected function init() {
		parent::init();
		$this->_withUserId=array();
		$this->_withFlgType=false;
	}
}
?>