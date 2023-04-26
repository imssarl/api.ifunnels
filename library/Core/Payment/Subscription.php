<?php
class Core_Payment_Subscription extends Core_Data_Storage {

	protected $_table='p_subscription';

	/**
	 * user_id - пользователь таб. u_users
	 * package_id - пакет  из таб. p_package
	 * flg_auto - автопродление подписки 0 - no, 1 - yes.
	 * flg_lifetime - флаг показвает пакет на всегда или.. 0 - not, 1 - yes
	 * cycles_remain - счетчик, сколько раз надо оплатить чтобы получить пожизненную подписку, 0 - подписака не lifetime.
	 * counter - счетчик, сколько раз была проплачена подписка. ( добавлено 10.01.2013. )
	 * transaction_id - id транзакции от сервиса через который была оплачена подписка (всегда храним только последнюю транзакцию)
	 * payment_type - тип сервиса оплаты см. Core_Payment_Service::PAYMENT_TYPE_...
	 * @var array
	 */
	protected $_fields=array( 'id', 'user_id', 'package_id', 'flg_auto','flg_lifetime','cycles_remain','counter','transaction_id','payment_type', 'expiry', 'added' );

	public function check( $_newPack ){
		if( !$this->onlyExpiry()->getList( $_arrSubs )->checkEmpty() ){
			return $this;
		}
		$_package=new Core_Payment_Package();
		foreach( $_arrSubs as $_sub ){
			if( !$_package->withIds( $_sub['package_id'] )->onlyOne()->getList( $_oldPack )->checkEmpty() ){
				continue;
			}
			if( $_oldPack['group_id']!=$_newPack['group_id'] ){
				continue;
			}
			$this->withIds( $_sub['id'] )->del();
		}
		return $this;
	}

	/**
	 * аспект кторый вызывается до выполнения set()
	 * после переназначения тут например можно организовать проверку полей
	 *
	 * @return boolean
	 */
	protected function beforeSet() {
		if ( !$this->_data->setFilter( array( 'trim', 'clear' ) )->setChecker( array(
			'user_id'=>empty( $this->_data->filtered['user_id'] ),
			'package_id'=>empty( $this->_data->filtered['package_id'] )
		))->check() ){
			$this->_data->getErrors( $this->_errors );
			return false;
		}

		return true;
	}

	private $_withPackage=false;
	private $_onlyPackageIds=false;
	private $_onlyActive=false;
	private $_onlyExpiry=false;
	private $_forUser=false;
	private $_withLifetime=false;
	private $_withoutLifetime=false;

	public function withPackage( $_mix ){
		if(!empty($_mix)){
			$this->_withPackage=$_mix;
		}
		return $this;
	}

	public function withLifetime(){
		$this->_withLifetime=true;
		return $this;
	}

	public function withoutLifetime(){
		$this->_withoutLifetime=true;
		return $this;
	}

	public function onlyPackageIds(){
		$this->_onlyPackageIds=true;
		return $this;
	}

	public function onlyActive(){
		$this->_onlyActive=true;
		return $this;
	}

	public function onlyExpiry(){
		$this->_onlyExpiry=true;
		return $this;
	}

	public function forUser( $_intId ){
		$this->_forUser=$_intId;
		return $this;
	}

	protected function init(){
		parent::init();
		$this->_withPackage=false;
		$this->_onlyPackageIds=false;
		$this->_onlyActive=false;
		$this->_onlyExpiry=false;
		$this->_forUser=false;
		$this->_withLifetime=false;
		$this->_withoutLifetime=false;
	}

	protected function assemblyQuery(){
		parent::assemblyQuery();
		$this->_crawler->set_select('(IF(d.expiry>'.time().',0,1)) as flg_expiry');
		if(!empty($this->_withPackage)){
			$this->_crawler->set_where('d.package_id IN ('. Core_Sql::fixInjection($this->_withPackage) .')');
		}
		if( $this->_onlyActive ){
			$this->_crawler->set_where('d.expiry>'.time() );
		}
		if($this->_onlyPackageIds){
			$this->_crawler->clean_select();
			$this->_crawler->set_select('d.package_id');
		}
		if( $this->_onlyExpiry ){
			$this->_crawler->set_where('d.expiry<'.time());
		}
		if( $this->_forUser ){
			$this->_crawler->set_where('d.user_id='.$this->_forUser);
		}
		if( $this->_withLifetime ){
			$this->_crawler->set_where('d.flg_lifetime=1');
		}
		if( $this->_withoutLifetime ){
			$this->_crawler->set_where('d.flg_lifetime=0');
		}
	}

}
?>