<?php

class Core_Media_Lock {
	
	private $_tmpFile = ''; // временный файл
	private $_timeout = 30; // таймаут в секундах

	public function __construct( $fileName = 'file.tmp' ) {
		$this->_tmpFile = sys_get_temp_dir() . 'inuse.' . md5( $fileName );
	}

	/**
	 * установка лимита времени на блокировку фала   [ секунды ]
	 *
	 * @param int $timeout
	 * @return bool
	 */
	public function setTimeout( $timeout = 30 ) {
		$this->_timeout = $timeout;
		return true;
	}	
	
	/**
	 * блокировка файла
	 *
	 * @return bool
	 */
	public function lock() {
		$f = fopen( $this->_tmpFile , 'w+' );
		if ( !fwrite( $f,time() ) ) {
			return false;
		}
		fclose( $f );
		return true;
	}
	
	/**
	 * разблокировка файла
	 *
	 * @return bool
	 */
	public function unLock() {
		if ( !file_exists( $this->_tmpFile ) ) {
			return false;
		}
		if ( !unlink( $this->_tmpFile ) ) {
			return false;
		}
		return true;
	}
	/**
	 * проверка блокировки файла, и принудительная разблокировка при привышении лемита времени
	 *
	 * @return bool
	 */
	public function isLocked() {
		if ( !file_exists( $this->_tmpFile ) ) {
			return false;
		}
		$time = time() - intval(file_get_contents( $this->_tmpFile ));
		if ( $time > $this->_timeout ) {
			$this->unLock();
			return false;
		}
		return true;
	}
	
	/**
	 * ожидание очереди на доступ к файлу
	 *
	 * @return bool
	 */
	public function whileLocked() {
		while ( $this->isLocked() ){
			sleep(1);
		}
		return true;		
	}
}
?>