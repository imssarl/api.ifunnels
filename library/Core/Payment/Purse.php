<?php


/**
 * Пользовательский кошелёк
 */
class Core_Payment_Purse extends Core_Data_Storage {

	protected $_table='p_history';
	protected $_fields=array( 'id', 'user_id', 'flg_status', 'flg_type', 'amount', 'description', 'added' );
	private $_amount=0;
	private $_type=0;
	private $_userId=0;
	private $_message='';
	private $_betterAdded=false;
	/**
	 * Минимальное количество кредитов на счету пользователя
	 * для использования сервисов.
	 * @var int
	 */
	private static $_minBalance=0;

	/**
	 * Минимальное количество кредитов на счету пользователя
	 * при котором выдаем Warning-сообщение.
	 * @var int
	 */
	public static $minWarningBalance=5;
	/**
	 * Типы транзакций:
	 * TYPE_INTERNAL - все внутренние операции с кредитами, за искл. подарочных кредитов.
	 * TYPE_EXTERNAL - все внешние операции с кредитами, пополнение счетов покупка пакетов т.и.д.
	 * TYPE_REWARD_SITES - подарочные кредиты за сайты в системе
	 * TYPE_REWARD_HOSTING - подарочные кредиты за хостинг в системе
	 */
	const TYPE_INTERNAL=1, TYPE_EXTERNAL=0,TYPE_REWARD_SITES=2,TYPE_REWARD_HOSTING=3;

	private static function initAmount(){
		self::$_minBalance=0;
		if( Core_Acs::haveAccess(array('Blog Fusion CSPP','Blog Fusion CSP'))){
			$_users=new Project_Users_Management();
			$_users->withIds( Core_Users::$info['id'] )->onlyOne()->getList( $arrProfile );
			$_buns=new Core_Payment_Buns();
			$_buns->withSysName('Project_Placement_Hosting')->onlyOne()->getList( $arrHosting );
			if( Core_Acs::haveAccess(array('Blog Fusion CSPP'))&&$arrProfile['domains_parked']<50 ){
				self::$_minBalance=$arrHosting['credits']*(50-$arrProfile['domains_parked']);
			}
			if( Core_Acs::haveAccess(array('Blog Fusion CSP'))&&$arrProfile['domains_parked']<5 ){
				self::$_minBalance=$arrHosting['credits']*(5-$arrProfile['domains_parked']);
			}
		}
	}

	/**
	 * Сумма для снятия/пополнения
	 * @param int $_int
	 * @return Core_Payment_Purse
	 */
	public function setAmount( $_int=0 ) {
		$this->_amount=$_int;
		return $this;
	}

	/**
	 * Устанавливает тип транзакции для отображения в статистики см. константы TYPE_...
	 * @param $_int
	 * @return Core_Payment_Purse
	 */
	public function setType( $_int ){
		$this->_type=$_int;
		return $this;
	}

	/**
	 * Устанавливает пользователя
	 * @param int $_int
	 * @return Core_Payment_Purse
	 */
	public function setUserId( $_int=0 ) {
		$_user=new Core_Users_Management();
		$_user->withIds( $_int )->onlyOne()->getList($arrProfile);
		// Если это суб-аккаунт то кредиты снимаются с родительского аккаунта
		if( !empty($arrProfile['parent_id']) ){
			$_int=$arrProfile['parent_id'];
		}
		$this->_userId=$_int;
		return $this;
	}

	/**
	 * Сообщение которое будет добавлено в историю по платежам
	 * @param string $_str
	 * @return Core_Payment_Purse
	 */
	public function setMessage( $_str='' ) {
		$this->_message=$_str;
		return $this;
	}

	public function betterAdded( $_time ){
		$this->_betterAdded=$_time;
		return $this;
	}

	/**
	 * Возвращает текущий баланс минус минимальный запас.
	 * @static
	 * @return int
	 */
	public static function getAmount( $_added=false ) {
		self::initAmount();
		$_users=new Core_Users_Management();
		$_users->withIds( ((Core_Users::$info['parent_id'])?Core_Users::$info['parent_id']:Core_Users::$info['id']) )->onlyOne()->getList( $arrProfile );
		$_amount=($arrProfile['amount']>=self::$_minBalance)?($arrProfile['amount']-self::$_minBalance):0;
		if ( empty( $_added ) ) {
			return $_amount;
		}
		$_core=new Core_Payment_Purse();
		$_core->onlyOwner()->onlyOne()->beforeDate( $_added )->setStatus( 1 )->getList( $_input );
		$_core->onlyOwner()->onlyOne()->beforeDate( $_added )->setStatus( 2 )->getList( $_output );
		return $_amount+$_input['summ']-$_output['summ'];
	}
	
	/**
	 * Возвращает текущий баланс минус минимальный запас на выбранной странице по дате
	 * @static
	 * @return int
	 */
	public static function getBalanceBefore( $_added=false ){
		if ( empty( $_added ) ) {
			return false;
		}
		self::initAmount();
		$_users=new Core_Users_Management();
		$_users->withIds( ((Core_Users::$info['parent_id'])?Core_Users::$info['parent_id']:Core_Users::$info['id']) )->onlyOne()->getList( $arrProfile );
		return ($arrProfile['amount']>=self::$_minBalance)?($arrProfile['amount']-self::$_minBalance):0;
	}

	/**
	 * Пополнение кошелька пользователя
	 * @return bool
	 */
	public function receipts() {
		if ( empty( $this->_amount )||empty( $this->_userId ) ) {
			return false;
		}
		Core_Sql::setExec( 'UPDATE u_users SET amount=amount+'.$this->_amount.' WHERE id='.$this->_userId );
		return $this->setEntered( array(
			'user_id'=>$this->_userId,
			'flg_status'=>1,
			'flg_type'=>$this->_type,
			'amount'=>$this->_amount,
			'description'=>$this->_message,
		) )->set();
	}

	/**
	 * Снятие кредитов с кошелька пользователя
	 * @return bool
	 */
	public function expenditure() {
		if ( empty( $this->_amount )||empty( $this->_userId ) ) {
			return false;
		}
		Core_Sql::setExec( 'UPDATE u_users SET amount=amount-'.$this->_amount.' WHERE id='.$this->_userId );
		return $this->setEntered( array(
			'user_id'=>$this->_userId,
			'flg_status'=>2,
			'flg_type'=>$this->_type,
			'amount'=>$this->_amount,
			'description'=>$this->_message,
		) )->set();
	}

	private $_withUsers=false;
	private $_onlyInternal=false;
	private $_onlyExternal=false;
	private $_onlyDomainLike=false;
	private $_withType=false;
	private $_beforeDate=false;
	private $_setStatus=false;

	public function beforeDate( $_beforeDate ){
		if( !isset( $_beforeDate ) ){
			$this->_beforeDate=false;
		}
		$this->_beforeDate=$_beforeDate;
		return $this;
	}

	public function setStatus( $_setStatus ){
		if( !isset( $_setStatus ) ){
			$this->_setStatus=false;
		}
		$this->_setStatus=$_setStatus;
		return $this;
	}

	public function withUsers(){
		$this->_withUsers=true;
		return $this;
	}

	public function onlyInternal(){
		$this->_onlyInternal=true;
		return $this;
	}

	public function onlyExternal(){
		$this->_onlyExternal=true;
		return $this;
	}

	public function onlyDomainLike(){
		$this->_onlyDomainLike=true;
		return $this;
	}

	public function withType($_arrTypes){
		if( !empty($_arrTypes)){
			$this->_withType=(is_array($_arrTypes))?$_arrTypes:array($_arrTypes);
		}
		return $this;
	}

	protected function assemblyQuery(){
		parent::assemblyQuery();
		if( $this->_withUsers ){
			$this->_crawler->set_select('u.email,u.buyer_name,u.buyer_surname');
			$this->_crawler->set_from('LEFT JOIN u_users u ON u.id=d.user_id');
		}
		if( $this->_onlyExternal ){
			$this->_crawler->set_where('d.flg_type='.self::TYPE_EXTERNAL );
		}
		if( $this->_onlyInternal ){
			$this->_crawler->set_where('d.flg_type='.self::TYPE_INTERNAL );
		}
		if( $this->_onlyDomainLike ){
			$this->_crawler->set_where('d.description LIKE "%domain:%"' );
		}
		if( $this->_withType ){
			$this->_crawler->set_where('d.flg_type IN('. Core_Sql::fixInjection($this->_withType) .')');
		}
		if( $this->_betterAdded ){
			$this->_crawler->set_where('d.added>='.Core_Sql::fixInjection(time()-$this->_betterAdded) );
		}
		if( $this->_setStatus!==false ){
			$this->_crawler->set_where('d.flg_status='.$this->_setStatus );
		}
		if( $this->_beforeDate!==false ){
			$this->_crawler->set_where('d.added > '.$this->_beforeDate);
			$this->_crawler->clean_select();
			$this->_crawler->set_select(' SUM( d.amount ) as summ ');
		}
	}

	protected function init(){
		parent::init();
		$this->_withUsers=false;
		$this->_onlyExternal=false;
		$this->_onlyInternal=false;
		$this->_betterAdded=false;
		$this->_withType=false;
		$this->_onlyDomainLike=false;
		$this->_setStatus=false;
	}
	
	protected function beforeSet() {
		$this->_data->setFilter(array( 'trim', 'clear'));
		return true;
	}
}
?>