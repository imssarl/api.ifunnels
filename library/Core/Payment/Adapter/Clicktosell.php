<?php


/**
 * Clicktosell
 */

class Core_Payment_Adapter_Clicktosell {

	private $_data=null;
	private $_logger=false;

	public function __construct(){
		$this->setLogger();
	}

	private function setLogger() {
		$writer=new Zend_Log_Writer_Stream( Zend_Registry::get('config')->path->absolute->logfiles.'Core_Payment_Adapter_Clicktosell.log' );
		$writer->setFormatter( new Zend_Log_Formatter_Simple("%timestamp% %priorityName% (%priority%): %message%\r\n") );
		$this->_logger=new Zend_Log( $writer );
	}

	/**
	 * Set and format data from http://www.click2sell.eu/
	 *
	 * @param array $_data
	 *  	acquirer_transaction_id - Transaction number in your payment processor's (i.e. Paypal) system
	 *		c2s_transaction_id		- click2sell transaction number
	 *		purchase_date 			- format: yyyy-MM-dd (year-month-day)
	 *		purchase_time 			- format: HH:mm:ss (hours:minutes:seconds)
	 *		payment_type 			- Paypal, Moneybookers, Google Checkout, credit card
	 *		transaction_type 		- Sale, Refund, Subscription
	 *		payment_status 			- OK or Failed, now Always sends OK since we send notification only on successfull cases now
	 *		subscription_status 	- in case of subscription: New, Recurring, Cancel
	 *		buyer_name 				- name
	 *		buyer_surname 			- surname
	 *		buyer_address 			- address
	 *		buyer_city 				- city
	 *		buyer_province 			- province
	 *		buyer_zip 				- postal code
	 *		buyer_country 			- country name
	 *		buyer_email				- email
	 *		buyer_phone 			- phone
	 *		product_price 			- price: amount + currency – "###.## CUR", CUR is a currency code: USD, EUR or GBP
	 *		product_id 				- product id in click2sell
	 *		product_name 			- product name
	 *		merchant_name 			- name of the vendor
	 *		merchant_username 		- click2sell login name of the vendor
	 *		affiliate_username 		- click2sell login of the affiliate in case of the affiliated sale
	 *		cp_package_id			- Package id in our database
	 * 		cp_user_id				- User id in our database
	 *
	 * @return bool
	 */
	public function setData( $_data ){
		if( empty($_data) ){
			$this->_logger->err('empty $_data:'.serialize($_data) );
			return false;
		}
		array_walk($_data, 'urldecode');
		$_data['cp_p']=Core_Payment_Encode::decode( $_data['cp_p'] );
		$this->_logger->info($_data['transaction_type'].': Start transaction: '.serialize($_data) );
		$this->_data= new Core_Data( $_data + array(
			'package_id' => $_data['cp_p']['package_id'],
			'user_id'	 => $_data['cp_p']['user_id'],
			'click2sell_id'	 => $_data['cp_p']['click2sell_id']
		));
		if( !$this->_data->setFilter( array( 'trim', 'clear' ) )->setChecker(array(
			'package_id' => empty( $this->_data->filtered['package_id'] ),
			'user_id' => empty( $this->_data->filtered['user_id'] )
		))->check() ){
			$this->_logger->err('empty required fields: '. serialize($this->_data->filtered) );
			return false;
		}
		$this->_data->setElements(array(
			'flg_auto'=>(($this->_data->filtered['transaction_type']=='Subscription')?1:0),
			'transaction_type'=>(($this->_data->filtered['transaction_type']=='Refund')?Core_Payment_Service::TYPE_REFUND:Core_Payment_Service::TYPE_PAYMENT),
			'transaction_id'=>$this->_data->filtered['acquirer_transaction_id'],
			'payment_type'=>Core_Payment_Service::PAYMENT_TYPE_CLICK2SELL
		));
		$_user=new Project_Users_Management();
		$_user->withIds( $this->_data->filtered['user_id'] )->forBackend()->onlyOne()->getList( $arrProfile );
		Zend_Registry::get( 'objUser' )->setByProfile( $arrProfile );
		$this->_data->setElement('id',$this->_data->filtered['user_id']);
		$this->_data->setElement('nickname',$this->_data->filtered['buyer_name'].' '.$this->_data->filtered['buyer_surname']);
		return $_user->setEntered($this->_data->filtered)->set();
	}

	public function getData(){
		return $this->_data;
	}
}
?>