<?php

class Core_Updater {
	public $settings=array();
	public $logger;

	public function __construct( $_arrData=array() ) {
		$this->settings=$_arrData;
		$this->setLogger();
		$this->startClass();
		$this->startSql();
	}

	private function setLogger() {

		if(empty($this->settings['method'])){
			$formatter = new Zend_Log_Formatter_Simple( Zend_Log_Formatter_Simple::DEFAULT_FORMAT.(php_sapi_name()=='cli'?PHP_EOL:"\n"));
			$_logdir=Zend_Registry::get('config')->path->absolute->logfiles.$this->settings['class'].DIRECTORY_SEPARATOR;
			mkdir($_logdir,0777,true);
			$writer=new Zend_Log_Writer_Stream( $_logdir.date('Y-m-d_H-i-s').'.log' );
		} else {
			$formatter = new Zend_Log_Formatter_Simple( Zend_Log_Formatter_Simple::DEFAULT_FORMAT.(php_sapi_name()=='cli'?PHP_EOL:'<br />'));
			$writer=new Zend_Log_Writer_Stream( 'php://output' );
		}
		$writer->setFormatter( $formatter );
		$this->logger = new Zend_Log( $writer );
	}

	// ?class[1]=test&class[2]=asdasd
	private function startClass() {
		if ( empty( $this->settings['class'] ) ) {
			return;
		}
		if ( !is_array( $this->settings['class'] ) ) {
			$this->settings['class']=array( $this->settings['class'] );
		}
		foreach( $this->settings['class'] as $v ) {
			$_str='Project_Updater_'.$v;
			$_obj=new $_str();
			$_obj->update( $this );
		}
	}

	// ?method[1]=2&method[1]=3
	private function startSql() {
		if ( empty( $this->settings['method'] ) ) {
			return;
		}
		if ( !is_array( $this->settings['method'] ) ) {
			$this->settings['method']=array( $this->settings['method'] );
		}
		foreach( $this->settings['method'] as $v ) {
			$_str='update'.$v;
			Project_Updater_Sql::$_str( $this );
		}
	}
}
?>