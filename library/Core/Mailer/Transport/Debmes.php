<?php
class Core_Mailer_Transport_Debmes extends Zend_Mail_Transport_Abstract {
	public function _sendMail() {
		Core_Files::devMess( ($this->header."\r\n\r\n".$this->body) );
	}
}
?>