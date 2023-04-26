<?php
class Core_Captcha_Image extends Zend_Captcha_Image {

	public static function getCaptcha() {
		$_captcha=new self( ( array(
			'font'=>Zend_Registry::get( 'config' )->path->relative->source.Zend_Registry::get( 'config' )->captcha->font,
			'imgDir'=>Zend_Registry::get( 'config' )->path->relative->captcha,
			'imgUrl'=>Zend_Registry::get( 'config' )->path->html->captcha,
		)+Zend_Registry::get( 'config' )->captcha->toArray() ) );
		return $_captcha->getPath();
	}

	public function getPath() {
		$_session=new $this->_sessionClass( 'Core_Captcha_Image' );
		$_session->id= $this->generate();
		return $this->getImgUrl(). $this->getId(). $this->getSuffix();
	
	}

	public function isValid($value, $context = null) {
		$_session=new $this->_sessionClass( 'Core_Captcha_Image' );
		return parent::isValid( array( 'id'=>$_session->id, 'input'=>$value ) );
	}
}
?>