<?php


/**
 * Jvzoo
 */

class Core_Payment_Adapter_Jvzoo {

	private $_data=null;
	private $_logger=false;

	public function __construct(){
		$this->setLogger();
	}

	private function setLogger() {
		$writer=new Zend_Log_Writer_Stream( Zend_Registry::get('config')->path->absolute->logfiles.'Core_Payment_Adapter_Jvzoo.log' );
		$writer->setFormatter( new Zend_Log_Formatter_Simple("%timestamp% %priorityName% (%priority%): %message%\r\n") );
		$this->_logger=new Zend_Log( $writer );
	}

	/**
	 * Set and format data from http://support.jvzoo.com/Knowledgebase/Article/View/17/2/jvzipn
	 *
	 * @param array $_data
	 *  	ccustname 		- customer name 1-510 Characters
	 *		ccuststate		- customer state 0-2 Characters
	 *		ccustcc 		- customer country code -2 Characters
	 *		ccustemail 		- customer email
	 *		cproditem 		- JVZoo product number
	 *		cprodtitle 		- title of product at time of purchase
	 *		cprodtype 		- type of product on transaction (STANDARD, and RECURRING)
	 *		ctransaction 	- action taken [SALE,BILL,RFND,CGBK,INSF,CANCEL-REBILL,UNCANCEL-REBILL]
	 *		ctransaffiliate 		- affiliate on transaction
	 *		ctransamount 			- amount paid to party receiving notification (in pennies (1000 = $10.00))
	 *		ctranspaymentmethod 	- method of payment by customer
	 *		ctransvendor 			- vendor on transaction
	 *		ctransreceipt 			- JVZoo Payment Id
	 *		cupsellreceipt 			- Parent receipt number for upsell transaction
	 *		caffitid 			- affiliate tracking id
	 *		cvendthru			- extra information passed to order form with duplicated information removed
	 *		cverify 			- the “cverify” parameter is used to verify the validity of the previous fields
	 *		ctranstime 			- the Epoch time the transaction occurred (not included in cverify)
	 *
	 * @return bool
	 */
	public function setData( $_data ){
		if( empty($_data) ){
			$this->_logger->err('empty $_data:'.serialize($_data) );
			return false;
		}
		if( !$this->jvzipnVerification($_data) ){
			$this->_logger->err('not verifid :'.serialize($_data) );
			return false;
		}
		parse_str($_data['cvendthru'],$_params);
		$_data['p']=Core_Payment_Encode::decode( $_params['p'] );
//		$_data['p']['user_id']=100001349;
//		$_data['p']['package_id']=44;
		$this->_logger->info($_data['ctransaction'].': Start transaction: '.serialize($_data) );
		$this->_data= new Core_Data( $_data + array(
			'package_id' => $_data['p']['package_id'],
			'user_id'	 => $_data['p']['user_id']
		));
		if( !$this->_data->setFilter( array( 'trim', 'clear' ) )->setChecker(array(
			'package_id' => empty( $this->_data->filtered['package_id'] ),
			'user_id' => empty( $this->_data->filtered['user_id'] )
		))->check() ){
			$this->_logger->err('empty required fields: '. serialize($this->_data->filtered) );
			return false;
		}
		$this->_data->setElements(array(
			'flg_auto'=>(($this->_data->filtered['cprodtype']=='RECURRING')?1:0),
			'transaction_type'=>(($this->_data->filtered['ctransaction']=='RFND')?Core_Payment_Service::TYPE_REFUND:Core_Payment_Service::TYPE_PAYMENT),
			'transaction_id'=>$this->_data->filtered['ctransreceipt'],
			'payment_type'=>Core_Payment_Service::PAYMENT_TYPE_JVZOO
		));
		$_user=new Project_Users_Management();
		$_user->withIds( $this->_data->filtered['user_id'] )->forBackend()->onlyOne()->getList( $arrProfile );
		Zend_Registry::get( 'objUser' )->setByProfile( $arrProfile );
		$this->_data->setElement('id',$this->_data->filtered['user_id']);
		$this->_data->setElement('nickname', urldecode($this->_data->filtered['ccustname']));
		$this->_data->setElement('buyer_name',urldecode($this->_data->filtered['ccustname']));
		return $_user->setEntered($this->_data->filtered)->set();
	}

	public function getData(){
		return $this->_data;
	}


	private function jvzipnVerification( $_data ){
		$secretKey = "XYAOH77mM79X";
		$pop = "";
		$ipnFields = array();
		foreach ($_data AS $key => $value) {
			if ($key == "cverify") {
				continue;
			}
			$ipnFields[] = $key;
		}
		sort($ipnFields);
		foreach ($ipnFields as $field) {
			$pop = $pop . $_POST[$field] . "|";
		}
		$pop = $pop . $secretKey;
		$calcedVerify = sha1(mb_convert_encoding($pop, "UTF-8"));
		$calcedVerify = strtoupper(substr($calcedVerify, 0, 8));
		return $calcedVerify == $_data["cverify"];
	}
}
?>