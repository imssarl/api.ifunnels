<?php

class Project_Coder extends Core_Data_Storage {
	protected $_table='data_coder';
	protected $_fields=array('id','md5code','code','added');

	protected $_withMd5Code=false;

	public function withMd5Code( $code ){
		$this->_withMd5Code = $code;
		return $this;
	}

	public static function install(){
		Core_Sql::setExec('DROP TABLE IF EXISTS data_coder');
		Core_Sql::setExec("CREATE TABLE `data_coder`(
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`md5code` varchar(32) DEFAULT NULL,
			`code` text,
			`added` int(11) NOT NULL DEFAULT '0',
			PRIMARY KEY (`id`)
		)
		COLLATE='utf8_general_ci'
		ENGINE=MyISAM");
	}

	protected function beforeSet(){
		$this->_data->setFilter( array( 'clear' ) );
		$_code = base64_encode( serialize( $this->_data->filtered['code'] ) );
		if( is_array( $this->_data->filtered['code'] ) || is_object( $this->_data->filtered['code'] ) ){
			$this->_data->setElement( 'md5code', md5( $_code ) );
			$this->_data->setElement( 'code', $_code );
		} else {
			$this->_data->setElement( 'md5code', md5( $this->_data->filtered['code'] ) );
		}
		return true;
	}

	protected function init(){
		parent::init();
		$this->_withMd5Code=false;
	}


	protected function assemblyQuery(){
		parent::assemblyQuery();
		if( $this->_withMd5Code ){
			$this->_crawler->set_where( 'd.md5code='.Core_Sql::fixInjection( $this->_withMd5Code ) );
		}
	}

	public static function clean(){
		Core_Sql::setExec( 'DELETE FROM data_coder WHERE added < UNIX_TIMESTAMP( ) -7*24*60*60' );
	}

	public static function decode( $str ){
		$model = new Project_Coder();
		$model->onlyOne()->withMd5Code( $str )->getList( $arrData );
		$code = unserialize( base64_decode( $arrData['code'] ) );
		if( $code !== false ){
			return $code;
		}
		return $arrData['code'];
	}

	public static function encode( $mixed ){
		$model = new Project_Coder();
		$model->setEntered( array( 'code' => $mixed ) )->set();
		$model->getEntered( $arrData );
		return $arrData['md5code'];
	}
}

?>