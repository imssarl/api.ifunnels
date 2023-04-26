<?php

class Project_Wizard {

	const
			TYPE_AMAZON_NCSB=1,
			TYPE_ZONTEREST_NCSB=2,
			TYPE_CONTENT_NCSB=3,
			TYPE_VIDEO_NVSB=4,
			TYPE_AUTHORITY=5,
			TYPE_ZONTEREST_PRO_NCSB=6,
			TYPE_CLICKBANK_NCSB=7,
			TYPE_CLICKBANKPRO_NCSB=8,
			TYPE_ZONTERESTLIGHT_NCSB=9,
			TYPE_CONTENT_PRO_NCSB=10,
			TYPE_IAM_NCSB=11;
	private $_adapter=false;
	private $_data=false;

	public function __construct( $_type ){
		$this->_adapter=self::factory( $_type );
	}
	/**
	 * Фабричный метод, исходя из типа создает нужный адаптер
	 * @static
	 * @param $_type
	 * @return bool|Project_Wizard_Adapter_*
	 * @throws Exception
	 */
	private static function factory( $_type ){
		switch( $_type ){
			case Project_Wizard::TYPE_AMAZON_NCSB : return new Project_Wizard_Adapter_Amazon(); break;
			case Project_Wizard::TYPE_ZONTEREST_NCSB : return new Project_Wizard_Adapter_Zonterest(); break;
			case Project_Wizard::TYPE_CONTENT_NCSB : return new Project_Wizard_Adapter_Content(); break;
			case Project_Wizard::TYPE_CONTENT_PRO_NCSB : return new Project_Wizard_Adapter_ContentPro(); break;
			case Project_Wizard::TYPE_VIDEO_NVSB : return new Project_Wizard_Adapter_Video(); break;
			case Project_Wizard::TYPE_AUTHORITY : return new Project_Wizard_Adapter_Authority(); break;
			case Project_Wizard::TYPE_ZONTEREST_PRO_NCSB : return new Project_Wizard_Adapter_ZonterestPro(); break;
			case Project_Wizard::TYPE_CLICKBANK_NCSB : return new Project_Wizard_Adapter_Clickbank(); break;
			case Project_Wizard::TYPE_CLICKBANKPRO_NCSB : return new Project_Wizard_Adapter_ClickbankPro(); break;
			case Project_Wizard::TYPE_IAM_NCSB : return new Project_Wizard_Adapter_IAM(); break;
			case Project_Wizard::TYPE_ZONTERESTLIGHT_NCSB : return new Project_Wizard_Adapter_ZonterestLight(); break;
			default:
				throw new Project_Wizard_Exception('Can not define type');
				break;
		}
	}

	/**
	 * Устанавливает тип визарда.
	 * @param $_type
	 * @return Project_Wizard
	 */
	public function setType( $_type ){
		$this->_adapter=self::factory( $_type );
		return $this;
	}

	public function getContentCount(){
		return $this->_adapter->getContentCount();
	}
	
	public function getSiteUrl(){
		return $this->_adapter->getSiteUrl();
	}

	/**
	 * Проверка доступности визарда.
	 * @return bool
	 */
	public function check( $_flgCheckOptions=true ){
		return $this->_adapter->check( $_flgCheckOptions );
	}

	/**
	 * Запускает создание того, что должен создать визард.
	 * @return bool
	 */
	public function run(){
		set_time_limit(0);
		ignore_user_abort(true);
		return $this->_adapter->run();
	}

	/**
	 * Устанавливает входные данные
	 * @param $_arrData
	 * @return Project_Wizard
	 * @throws Exception
	 */
	public function setEntered( $_arrData ){
		if( empty($_arrData) ){
			throw new Project_Wizard_Exception('Entered data is empty');
		}
		$this->_data=new Core_Data( $_arrData );
		if( !$this->_adapter->setEntered( $this->_data ) ){
			return false;
		}
		return $this;
	}

	public function getErrors( &$arrErrors ){
		$arrErrors=Core_Data_Errors::getInstance()->getErrors();
	}
}
?>