<?php

/**
 * PHP Thumb
 *
 * @see Core/Files/Image/PHPThumb/
 */
require_once 'Core/Files/Image/PHPThumb/ThumbLib.inc.php';

/**
 * Работа с картинками
 * Пока отсутствует Zend_Image используем PHP Thumb (http://phpthumb.gxdlabs.com/)
 */
class Core_Files_Image_Thumbnail {

	private $_source='';

	private $_src='';

	private $_type='resize';

	private $_w=0;

	private $_h=0;

	private $_p=0;

	public static function generate( $_arrParam=array() ) {
		$obj=new self();
		$obj->setSource( $_arrParam['src'] );
		if ( !empty( $_arrParam['p'] ) ) {
			$obj->setPercent( $_arrParam['p'] );
		} elseif ( !empty( $_arrParam['w'] )||!empty( $_arrParam['h'] ) ) {
			$obj->setDimension( @$_arrParam['w'], @$_arrParam['h'] );
		}
		if ( !empty( $_arrParam['type'] ) ) {
			$obj->setType( $_arrParam['type'] );
		}
		return $obj->getThumbnailSrc();
	}

	public function setType( $_strSrc='resize' ) {
		$this->_type=$_strSrc;
		return $this;
	}

	public function setSource( $_strSrc='' ) {

		if ( empty( $_strSrc )||!is_file( $_strSrc ) ) {
			$this->_source=Zend_Registry::get( 'config' )->path->relative->img.Zend_Registry::get( 'config' )->image->noimage;
			return $this;
		}
		$this->_source=$_strSrc;
		// проверить тип файла TODO!!!
		return $this;
	}

	public function setDimension( $_intW=null, $_intH=null ) {
		if ( is_null( $_intW )&&is_null( $_intH ) ) {
			$this->_errors=true;
			return $this;
		}
		if ( is_null( $_intH ) ) {
			$_intH=$_intW;
		}
		if ( is_null( $_intW ) ) {
			$_intW=$_intH;
		}
		$this->_w=$_intW;
		$this->_h=$_intH;
		return $this;
	}

	public function setPercent( $_intP=null ) {
		if ( is_null( $_intP ) ) {
			$this->_errors=true;
			return $this;
		}
		$this->_p=$_intP;
		return $this;
	}

	public function setSrc( $_strSrc='' ) {
		if ( empty( $_strSrc ) ) {
			$this->_errors=true;
			return $this;
		}
		$this->_src=$_strSrc;
		return $this;
	}

	public function resize() {
		if ( $this->_errors ) {
			return false;
		}
		PhpThumbFactory::$defaultImplemenation=Zend_Registry::get( 'config' )->image->implementation;
		$thumb=PhpThumbFactory::create( $this->_source, array(
			'jpegQuality'=>Zend_Registry::get( 'config' )->image->quality,
		) );
		$_str=$this->_type;
		try{
			$thumb->$_str( $this->_w, $this->_h )->save( $this->_src );
		}catch(Exeption $e ){
			unlink( $this->_src );
		}
		return true;
	}

	public function getThumbnailSrc() {
		if ( $this->_errors) {
			return Core_Files::getWebPath( Zend_Registry::get( 'config' )->image->noimage );
		}
		if( $this->checkCashe() ){
			return Core_Files::getWebPath( $this->_src );
		}
		$this->resize();
		// $this->d_cleanup_tumbcashe(); // надо вешать на крон по хорошему, если сразу много картинок генерить то слишком часто проверяется кэш и это дико тормозит
		return Core_Files::getWebPath( $this->_src );
	}

	private function checkCashe() {
		if ( !Zend_Registry::get( 'config' )->image->thumbnail_cashing ) {
			return false;
		}
		$this->_src=Zend_Registry::get( 'config' )->path->relative->tumb_cache.md5( $this->_source.filemtime( $this->_source ).$this->_w.$this->_h ).'.pic';
		return is_file( $this->_src );
	}

	/**
	* очистка кэша от старых файлов
	* рефакторинг TODO!!! 13.12.2011
	* @param none
	* @return boolean
	*/
	private function d_cleanup_tumbcashe() {
		if ( !( $_hdl=openDir( $this->config->path->relative->tumb_cache ) ) ) {
			return false;
		}
		while ( ( $_strFile=readDir( $_hdl ) )!== false ) {
			if ( in_array( $_strFile, array( '.', '..' ) )||is_dir( $this->config->path->relative->tumb_cache.$_strFile )||
				time()-filemtime( $this->config->path->relative->tumb_cache.$_strFile )<$this->config->fsdriver->exp ) {
				continue;
			}
			$this->d_rmfile( $this->config->path->relative->tumb_cache.$_strFile );
		}
		closeDir( $_hdl );
		return true;
	}
}
?>