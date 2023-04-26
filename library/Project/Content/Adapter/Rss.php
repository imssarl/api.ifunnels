<?php


/**
 * Keywords контент функционал
 */
class Project_Content_Adapter_Rss implements Project_Content_Interface {

	private $_limit = 0; // ограничение количества rss 0-без ограничения
	protected $_isNotEmpty=false; // для проверки результата выборки (по умолчанию выборка пуста) отражает результаты последнего getList
	protected $_settings = '';
	private $_withJson=false;
	private $_withRewrite=false;

	private $_tags=array(
		'description'=>'{description}',
		'link'=>'{link}'
	);
	
	protected $_templates = array ( // шаблончики
		0 => '{description}',
		1 => '{description}&nbsp;<a href=\'{link}\'>more</a>'
	);

	public function withRewrite( $_int ){
		$this->_withRewrite=$_int;
		return $this;
	}

	public function withJson(){
		$this->_withJson=true;
		return $this;
	}

	/*
	* Выбирает список контента
	*
	* @param mixed $mixRes отдаёт в виде array( array( title, content ) )
	* @return boolean
	*/
	public function getList( &$mixRes ) {
		if ( empty( $this->_settings['rss_links'] ) ) {
			return $this;
		}
		$arrUrls = explode( "\n", str_replace( array( "\r\n", "\n\r", " " ), "\n", $this->_settings['rss_links'] ) );
		$volumeDate = array ();
		foreach ( $arrUrls as $oneUrl ) {
			$curl=Core_Curl::getInstance();
			if ( !$curl->getContent( $oneUrl ) ) {
				break;
			}
			$rss=@simplexml_load_string( $curl->getResponce() );
			if ( $rss===false ) {
				break;
			}
			foreach( $rss->channel->item as $item ) {
				$stackStr = str_replace( '"',"'", $item->description );
				$rssDateTime = new DateTime( $item->pubDate );
				$mixRes[] = array (
					'title'=> str_replace( array( "\r\n", "\n\r", "\n" ), "\n", str_replace( '"', "'", str_replace( "'", "`", $item->title ) ) ),
					'description'=> $stackStr, 
					'link'=> (string)$item->link, //превращаю в строку
					'pubDate' => $rssDateTime->format('U'), //использую UNIX-time
					'body' => ''
				);
				$volumeDate[] = $rssDateTime->format('U');
			}
		}
		array_multisort( $volumeDate, SORT_DESC, SORT_STRING, $mixRes );
		if ($this->_limit != 0) {
			$mixRes = array_slice( $mixRes, $this->_counter, $this->_limit );
		}
		$this->_settings['flg_insert_links'] = ($this->_settings['flg_insert_links'] == '1') ? '1' : '0';
		$this->_isNotEmpty=!empty( $mixRes );
		if(!empty($this->_withJson)){
			foreach( $mixRes as &$_item ){
				$_item['fields']=serialize($_item);
			}
		}
		$this->init();
		return $this;
	}

	public function checkEmpty() {
		return $this->_isNotEmpty;
	}
	
	public function prepareBody( &$mixRes ){
		foreach( $mixRes as &$_item ){
			if( !is_array($_item) ){
				return;
			}
			$_fields=unserialize($_item['body']);
			if(empty($_fields)){
				continue;
			}
			if( $this->_withRewrite ){
				Zend_Registry::get('rewriter')->setText( $_fields['title'] )->setDeep( $this->_withRewrite )->rewrite( $_tmpRes );
				$_fields['title']=(empty($_tmpRes))?$_fields['title']:array_shift( $_tmpRes );
				unset($_tmpRes);
				Zend_Registry::get('rewriter')->setText( $_fields['description'] )->setDeep( $this->_withRewrite )->rewrite( $_tmpRes );
				$_fields['description']=(empty($_tmpRes))?$_fields['description']:array_shift( $_tmpRes );
			}
			$this->_settings['template'] = $this->_templates[ (empty($this->_settings['flg_insert_links'])?0:$this->_settings['flg_insert_links']) ];
			ksort($_fields);
			ksort($this->_tags);
			$_tmpTemplate=$this->_settings['template'];
			$_replace=array_intersect_key( $_fields, $this->_tags );
			$_tmpTemplate=str_replace( $this->_tags, $_replace, $_tmpTemplate );
			$_item['body'] = $_tmpTemplate;
		}
		$this->init();
		return true;
	}

	protected function init(){
		$this->_withRewrite=false;
		$this->_withJson=false;
	}

	/**
	* Подсобный метод для формирования запроса
	* $_obj->withIds( $_arrIds )->getContent( $mixRes )
	*
	* @param array $_arrIds - ids нужного контента
	* @return object
	*/
	public function withIds( $_arrIds=array() ) {
		return $this;
	}

	/**
	* В случаях когда надо получить контент без
	* учёта принадлежности какому-либо пользователю
	*
	* @return object
	*/
	public static function getInstance() {}

	/**
	* Фильтр для списка контента
	* $_obj->setFilter( $_GET['arrFlt'] )->getList( $mixRes )
	*
	* @param array $_arrFilter - поля и значения фильтра
	* @return object
	*/
	public function setFilter( $_arrFilter=array() ) {
		$this->_settings = $_arrFilter;
		return $this;
	}

	/**
	* Получение массива для генерации постраничной навигации
	*
	* @param array $arrRes
	* @return object
	*/
	public function getPaging( &$arrRes ) {
		return $this;
	}

	/**
	* Ранее установленный фильтр для использования в шаблоне
	*
	* @param array $arrRes
	* @return object
	*/
	public function getFilter( &$arrRes ) {
		$arrRes = $this->_settings;
		return $this;
	}

	/**
	 * Сколько контента вернуть
	 *
	 * @param  $_intLimit
	 * @return object
	 */
	public function setLimited( $_intLimit ) {
		$this->_limit = $_intLimit;
		return $this;
	}

	/**
	 * Счетчик контента запощеного в проект от начала. Используется для внешних источников, те которые не име
	 *
	 * @param  $_intCounter
	 * @return object
	 */
	public function setCounter( $_intCounter ) {
		$this->_counter = $_intCounter;
		return $this;
	}

	/**
	 * Дополнительные данные для генерации формы на шаблоне адаптера
	 * Нужно быть остарожным чтобы не обнулить массив который выкидывается на шаблон
	 *
	 * @param array $arrRes
	 * @return object
	 */
	public function getAdditional( &$arrRes ) {
		return $this;
	}

	/**
	 * Сеттер для $_POST
	 *
	 * @param array $_arrPost
	 * @return object
	 */
	public function setPost( $_arrPost=array() ) {
		return $this;
	}

	
	public function setSettings( $arrSettings ){
		if( empty($arrSettings) ){
			return false;
		}
		$this->_settings=$arrSettings;
		return $this;
	}

	/**
	 * Сеттер для $_FILES
	 *
	 * @param array $_arrFile
	 * @return object
	 */
	public function setFile( $_arrFile=array() ) {
		return $this;
	}

	/**
	 * Результат работы второго шага выбора данных
	 *
	 * @param array $arrRes
	 * @return boolean
	 */
	public function getResult( &$arrRes ) {
		return true;
	}

}
?>