<?php
class Core_Mailer_Transport_Print extends Zend_Mail_Transport_Abstract {
	public function _sendMail() {
		while( @ob_end_clean() );
		Core_View::factory( Core_View::$type['one'] )
			->setTemplate( Zend_Registry::get( 'config' )->path->relative->letters.'print.tpl' )
			->setHash( array( 
					'to'=>$this->recipients, 
					'from'=>$this->_mail->getFrom(), 
					'subject'=>$this->_mail->getSubject(), 
					'raw'=>($this->header.$this->body), 
				) )
			->parse()
			->show();
		exit;
	}
}
?>