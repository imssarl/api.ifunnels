<?php


/**
 * Тарифный план и пакеты кредитов
 */
class Core_Payment_Package extends Core_Data_Storage implements Core_i18n_Dynamic_Interface {

	protected $_table='p_package';

	protected $_fields=array( 'id', 'parent_id', 'group_id', 'flg_type', 'flg_hide', 'flg_length', 'length','cycles', 'image',
		'click2sell_id', 'credits','recurring_credits', 'cost','recurring_cost', 'title', 'click2sell_url', 'description', 'edited', 'added' );

	public static $periodType=array( 'Days', 'Month', 'Years' );

	public static $packageType=array( 'Package', 'Credits' );

	/**
	 * Get lenght in seconds
	 * @static
	 * @param $_arrPak
	 * @return bool
	 */
	public static function getLengthInSeconds( $_arrPak ){
		if( empty($_arrPak) ){
			return false;
		}
		if( self::$periodType[ $_arrPak['flg_length'] ]=='Days' ){
			return $_arrPak['length']*(60*60*24); // Days
		} elseif( self::$periodType[ $_arrPak['flg_length'] ]=='Month' ){
			return $_arrPak['length']*(60*60*24*30); // Month
		} elseif( self::$periodType[ $_arrPak['flg_length'] ]=='Years' ){
			return $_arrPak['length']*(60*60*24*30*12); // Years
		}
	}

	public static function isFree( $_arrPack ){
		if( empty($_arrPack['click2sell_id']) ){
			return true;
		}
		return false;
	}

	/**
	 * аспект кторый вызывается до выполнения set()
	 * после переназначения тут например можно организовать проверку полей
	 *
	 * @return boolean
	 */
	protected function beforeSet() {
		$this->_data->setFilter( array( 'trim', 'clear' ) );
		if ( empty( $this->_data->filtered['flg_type'] ) ) { // тарифный план
			$this->_data->setFilter( array( 'trim' ) );
			if ( !$this->_data->setChecker( array(
				'title'=>empty( $this->_data->filtered['title'] ),
				'length'=>empty( $this->_data->filtered['flg_length'] )||empty( $this->_data->filtered['length'] ),
//				'click2sell_url'=>empty( $this->_data->filtered['click2sell_url'] ),
//				'click2sell_id'=>empty( $this->_data->filtered['click2sell_id'] ),
			))->check() ){
				$this->_data->getErrors( $this->_errors );
				return false;
			}
			// предполагается оплата за 30 дней
			// поле используется при начислении кредитов при оплате тарифа
//			$this->_data->setElement( 'credits', ($this->_data->filtered['num_in_day']*30) );
		} else { // доп кредиты
			if ( !$this->_data->setChecker( array(
				'title'=>empty( $this->_data->filtered['title'] ),
//				'parent_id'=>empty( $this->_data->filtered['parent_id'] ),
				'credits'=>empty( $this->_data->filtered['credits'] ),
				'click2sell_url'=>empty( $this->_data->filtered['click2sell_url'] ),
				'click2sell_id'=>empty( $this->_data->filtered['click2sell_id'] ),
			))->check() ){
				$this->_data->getErrors( $this->_errors );
				return false;
			}
		}
		// в данной системе одна группа пользователей поэтому группы не используем
		if ( empty( $this->_data->filtered['group_id'] ) ) {
			// тут создаём делаем дубликат группы визиторс и присваеваем id
			$this->_data->filtered['group_id']=0;
		}
		return true;
	}

	/**
	 * аспект кторый вызывается после выполнения set()
	 * после переназначения тут например можно сделать какие-либо действия после сохранения данных
	 *
	 * @return boolean
	 */
	protected function afterSet() {
		if ( empty( $this->_data->filtered['id'] ) ) {
			return false;
		}
		if ( Zend_Registry::isRegistered( 'locale' ) ) {
			$this->getLng()->set( $this->_data->filtered );
		}
		return true;
	}

	/**
	 * фильтр: ищем по id группы
	 *
	 * @var integer
	 */
	protected $_withGroupId=0;

	/**
	 * фильтр: все пакеты включая скрытые
	 *
	 * @var boolean
	 */
	protected $_withHided=false;

	/**
	 * фильтр: только тарифные пакеты
	 *
	 * @var boolean
	 */
	protected $_onlyTariffPkg=false;

	/**
	 * фильтр: только кредитные пакеты
	 *
	 * @var boolean
	 */
	protected $_onlyCreditPkg=false;

	protected $_withOrder=''; // c сортировкой

	protected $_withoutParams=false; // c сортировкой
	private  $_onlyGroupIds=false;

	protected function init() {
		parent::init();
		$this->_withGroupId=0;
		$this->_withHided=false;
		$this->_onlyTariffPkg=false;
		$this->_onlyCreditPkg=false;
		$this->_withOrder='';
		$this->_withoutParams=false;
		$this->_onlyGroupIds=false;
	}

	public function onlyGroupIds(){
		$this->_onlyGroupIds=true;
		return $this;
	}

	public function withGroupId( $_int=0 ) {
		if ( empty( $_int ) ) {
			return $this;
		}
		$this->_withGroupId=$_int;
		return $this;
	}

	public function withHided() {
		$this->_withHided=true;
		return $this;
	}

	public function onlyTariffPkg() {
		$this->_onlyTariffPkg=true;
		return $this;
	}

	public function onlyCreditPkg() {
		$this->_onlyCreditPkg=true;
		return $this;
	}

	public function withoutParams() {
		$this->_withoutParams=true;
		return $this;
	}

	protected function assemblyQuery() {
		parent::assemblyQuery();
		$this->_crawler->clean_select();
		if ( $this->_onlyIds ) {
			$this->_crawler->set_select( 'd.id' );
		} elseif ( $this->_toSelect||$this->_toPosition ) {
			if ( Zend_Registry::isRegistered( 'locale' ) ){
				$this->_crawler->set_select( 'd.id, '.($this->_editMode? 'd.title':$this->getLng()->setWorkedField( 'title' )->getSubQuery()) );
			} else {
				$this->_crawler->set_select( 'd.id,d.title');
			}
		} else {
			if ( Zend_Registry::isRegistered( 'locale' ) ){
				$this->_crawler->set_select( 'd.*'.($this->_editMode? '':', '.$this->getLng()->getSubQuery()) );
			} else {
				$this->_crawler->set_select( 'd.*' );
			}
		}
		if ( empty( $this->_withOrder ) ) {
			$this->_withOrder='d.cost--dn';
		}

		if ( !empty( $this->_withGroupId ) ) {
			$this->_crawler->set_where( 'd.group_id='.Core_Sql::fixInjection( $this->_withGroupId ) );
		}
		if ( !$this->_withHided&&!$this->_withIds&&!$this->_withGroupId ) {
			$this->_crawler->set_where( 'd.flg_hide=0' );
		}
		if ( $this->_onlyTariffPkg ) {
			$this->_crawler->set_where( 'd.flg_type=0' );
		}
		if ( $this->_onlyCreditPkg ) {
			$this->_crawler->set_where( 'd.flg_type=1' );
		}
		if($this->_onlyGroupIds){
			$this->_crawler->clean_select();
			$this->_crawler->set_select('d.group_id');
		}
	}

	private function addPaymentUrl( &$arrRes ) {
		// может быть вариант toSelect() например.. такая  проверка empty( $arrRes['id'] )||empty( $arrRes[0]['id'] )|| не катит.
		if ( ( empty( $arrRes['id'] )&&empty( $arrRes[0]['id'] ) )||empty( Core_Users::$info['id'] ) ) {
			return $this;
		}
		if( empty( $arrRes['id'] ) ){
			foreach( $arrRes as &$_item ){
				$_item['click2sell_redirect_url']=$_item['click2sell_url'].((stripos($_item['click2sell_url'],'?'))?'&':'?').'p='.Core_Payment_Encode::encode( array( 'package_id'=>$_item['id'],'user_id'=>Core_Users::$info['id'],'click2sell_id'=>$_item['click2sell_id'] ) );
				$_item['click2sell_url']='/services/package.php?params='.Core_Payment_Encode::encode(array( 'package_id'=>$_item['id'],'user_id'=>Core_Users::$info['id'],'click2sell_id'=>$_item['click2sell_id'] ));
			}
		} else {
			$arrRes['click2sell_redirect_url']=$arrRes['click2sell_url'].((stripos($arrRes['click2sell_url'],'?'))?'&':'?').'p='.Core_Payment_Encode::encode( array( 'package_id'=>$arrRes['id'],'user_id'=>Core_Users::$info['id'],'click2sell_id'=>$arrRes['click2sell_id'] ) );
			$arrRes['click2sell_url']='/services/package.php?params='.Core_Payment_Encode::encode(array( 'package_id'=>$arrRes['id'],'user_id'=>Core_Users::$info['id'],'click2sell_id'=>$arrRes['click2sell_id'] ));
		}
		return $this;
	}

	public function getList( &$arrRes ){
		$_withoutParams=$this->_withoutParams;
		parent::getList( $arrRes );
		if ( Zend_Registry::isRegistered( 'locale' )&&$this->_editMode ) {
			$this->getLng()->withIds( $this->_withIds )->setImplant( $arrRes );
		}
		$this->_editMode=false;
		return ($_withoutParams)? $this : $this->addPaymentUrl( $arrRes );
	}

	/**
	 * флаг который используем при мультиязычности
	 * гаситм не в init а в getList
	 *
	 * @var boolean
	 */
	protected $_editMode=false;

	public function editMode() {
		$this->_editMode=true;
		return $this;
	}

	public function getTable( $_bool=false ) {
		return ($_bool?'d':$this->_table);
	}

	public function getFieldsForTranslate() {
		return array( 'title', 'description' );
	}

	public function getDefaultLang() {
		return 'en';
	}

	public function getLng() {
		return Core_i18n_Dynamic::getInstance( $this );
	}
}
?>