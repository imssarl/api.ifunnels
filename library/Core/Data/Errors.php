<?php


/**
 * Cбор ошибок и вывод пользователю при отрисовке страицы
 */
class Core_Data_Errors implements Core_Singleton_Interface {

	private $_errors=array();

	/**
	 * экземпляр объекта текущего класса (singleton)
	 *
	 * @var Core_Data_Errors object
	 */
	private static $_instance=NULL;

	/**
	 * возвращает экземпляр объекта текущего класса (singleton)
	 * при первом обращении создаёт
	 *
	 * @return Core_Data_Errors object
	 */
	public static function getInstance() {
		if ( self::$_instance==NULL ) {
			self::$_instance=new self();
		}
		return self::$_instance;
	}

	/**
	 * массив с проверяемыми полями
	 *
	 * @var array
	 */
	private $_data=array();

	/**
	 * сбрасывает массив с ошибками
	 * @return $this
	 */
	public function reset(){
		$this->_errors=array();
		return $this;
	}
	/**
	 * получает данные, актуальные на момент проверки
	 *
	 * @param string $_object Core_Data
	 * @return object
	 */
	public function setData( Core_Data $_object ) {
		$this->_data=$_object->filtered;
		return $this;
	}

	/**
	 * фабричный метод для создания объектов валидаторов (Zend_Validate_* и т.п.)
	 *
	 * @param string $_strName in
	 * @return object
	 */
	public function getValidator( $_strName='' ) {
		return new $_strName();
	}

	/**
	$obj->setData( Core_Data )->setValidators(
		'email'=>$obj->getValidator('Zend_Validate_EmailAddress')->setOptions()->...,
		'title'=>$obj->getValidator('Project_Validate_EmailAddress')->setType(),
	)->isValid()
	*/
	public function setValidators( $_arr=array() ) {
		$this->_validators=$_arr;
		return $this;
	}

	/**
	 * Валидирование данных
	 *
	 * @param array $_arr in
	 * @return object
	 */
	public function isValid() {
		$this->_errors=array();
		// все данные признаются прошедшими валидацию
		if ( empty( $this->_validators ) ) {
			return true;
		}
		// данных нет это тоже ошибка, т.к. подразумевалось что они есть
		if ( empty( $this->_data ) ) {
			return false;
		}
		foreach( $this->_validators as $k=>$v ) {
			if ( !$v->isValid( $this->_data[$k] ) ) {
				$this->_errors['errForm'][$k]=$v->getMessages();
			}
		}
		return empty( $this->_errors );
	}

	public function getErrorsForm() {
		return $this->_errors['errForm'];
	}

	public function getErrorsFlow() {
		return $this->_errors['errFlow'];
	}

	public function getErrorFlowShift(){
		return array_shift($this->_errors['errFlow']);
	}

	public function getErrorFormShift(){
		return array_shift($this->_errors['errForm']);
	}

	public function setError( $_str='' ) {
		$this->_errors['errFlow'][]=$_str;
		return false;
	}

	/**
	array(
		'errForm'=>array(
			'email'=array(
				'err one',
				'err two'
			)
		),
		'errFlow'=>array(
			'err one',
			'err two',
		)
	)
	*/
	public function getErrors() {
		return $this->_errors;
	}
}
?>