<?php


/**
 * Yahooanswers контент функционал
 */
class Project_Content_Adapter_Yahooanswers extends Core_Storage implements Project_Content_Interface {

	private $_tags=array('title'=>'{title}','question'=>'{question}','answer'=>'{answer}','body'=>'{body}');
	protected $_limit=10;
	protected $_counter=0;
	protected $_res;
	private $_settings; // параметры получаемые из шаблона
	protected $_isNotEmpty=false; // для проверки результата выборки (по умолчанию выборка пуста) отражает результаты последнего getList
	private $httpUrl = "http://answers.yahooapis.com/AnswersService/V1/questionSearch";
	private $arrSelect = array (
		'query' => '', // 	string (required) 	Search terms.
		'type' => 'all', // 	string: omit for default "all" 	Question status. Set to "all", "resolved", "open", or "undecided".
		'appid' => 'dj0yJmk9VVdtdmFWZFNMUlZNJmQ9WVdrOU5uRkxVMGhSTjJzbWNHbzlNVEExT1RRd056TTJNZy0tJnM9Y29uc3VtZXJzZWNyZXQmeD1hNQ-', // 	string (required) 	The application ID. See Application IDs for more information.
		'region' => 'us',//Filter based on country:
		'sort' => '' ,
		'start' => 0, // 	integer: default 0, max 1000 	Starting question to list, used to display further results.
		'results' => 10, // 	integer : default 10, max 50 	Number of questions to be returned.
		'output' => "xml", // 	string: omit for default "xml" 	Defines the output for the call. Accepted values are "xml", "json", "php", and "rss".
		'callback' => ''  // 	string: default "" 	If set, wraps the JSON object in call to the selected function. Only makes sense if output selected is JSON. 
	);

	public static $region=array(
		1=>array ( 'value' => "us", 'title' => 'United States' ),
		2=>array ( 'value' => "uk", 'title' => 'United Kingdom' ),
		3=>array ( 'value' => "ca", 'title' => 'Canada' ),	
		4=>array ( 'value' => "au", 'title' => 'Australia' ),			
		5=>array ( 'value' => "de", 'title' => 'Germany' ),
		6=>array ( 'value' => "fr", 'title' => 'France' ),
		7=>array ( 'value' => "it", 'title' => 'Italy' ),	
		8=>array ( 'value' => "es", 'title' => 'Spain' ),		
		9=>array ( 'value' => "br", 'title' => 'Brazil' ),
		10=>array ( 'value' => "ar", 'title' => 'Argentina' ),
		11=>array ( 'value' => "mx", 'title' => 'Mexico' ),
		12=>array ( 'value' => "sg", 'title' => 'Singapore' ),
		13=>array ( 'value' => "in", 'title' => 'India' ),
		14=>array ( 'value' => "el", 'title' => 'en Espanol' )
	);
	private $_withJson=false;
	private $_withRewrite=false;

	public function __construct() {
		if( !is_array( $_SESSION['paggedData'] )){
			$_SESSION['paggedData']=array();
		}
		$this->_pagedData=&$_SESSION['paggedData'];
	}

	public function withRewrite( $_int ){
		$this->_withRewrite=$_int;
		return $this;
	}

	public function withJson(){
		$this->_withJson=true;
		return $this;
	}

	protected function init(){
		parent::init();
		$this->_withJson=false;
		$this->_withRewrite=false;
	}

	public function getList( &$mixRes ) {
		if ( !empty($this->_withIds) ) {
			foreach ( $this->_withIds as $_contentId ) {
				$mixRes[] = $this->_pagedData[$_contentId];
			}
			if(!empty($this->_withJson)){
				foreach( $mixRes as &$_item ){
					$_item['fields']=serialize($_item);
				}
			}
			$this->_isNotEmpty=!empty( $mixRes );
			$this->init();
			$this->_pagedData = false;
			return $this;
		}
		if( !empty( $this->_withPaging['page'] ) ){
			$this->_counter=( $this->_withPaging['page']-1 ) * $this->_limit;
			$_page=$this->_withPaging['page'];
		} else {
			$_page=( $this->_counter/$this->_limit <= 1 )? 1 : ceil($this->_counter/$this->_limit);
		}
		$this->arrSelect['start'] = $this->_counter;
		$this->arrSelect['results'] = $this->_limit;
		$this->_paging=array( 'curpage'=>$_page );
		$index=$this->_counter;
		$curlData=Core_Curl::getInstance();
		if ( !$curlData->getContent( $this->httpUrl."?".http_build_query( array_merge( $this->arrSelect, $this->_settings ) ) ) ) {
			return $this;
		}
		$_strRes=$curlData->getResponce();
		$_xml=simplexml_load_string($_strRes);
		foreach ( $_xml->Question as $_content ) {
			$mixRes[$index]=array(
				'id'				=>	$index,
				'title'				=>	nl2br((string)$_content->Subject),
				'question'		=>	nl2br((string)$_content->Content),
				'answer'		=>	nl2br((string)$_content->ChosenAnswer),
				'body'			=>	''
			);
			$index ++;
		}
		$this->_isNotEmpty=!empty( $mixRes );
		if ( !($this->_settings === $_SESSION['pagedSettings']) ) {
			$this->_pagedData=array();
		}
		$_SESSION['pagedSettings'] = $this->_settings;
		$this->_pagedData = array_merge ($this->_pagedData, $mixRes);
		if(!empty($this->_withJson)){
			foreach( $mixRes as &$_item ){
				$_item['fields']=serialize($_item);
			}
		}
		$this->init();
		return $this;
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
				Zend_Registry::get('rewriter')->setText( $_fields['question'] )->setDeep( $this->_withRewrite )->rewrite( $_tmpRes );
				$_fields['question']=(empty($_tmpRes))?$_fields['question']:array_shift( $_tmpRes );
				unset($_tmpRes);
				Zend_Registry::get('rewriter')->setText( $_fields['answer'] )->setDeep( $this->_withRewrite )->rewrite( $_tmpRes );
				$_fields['answer']=(empty($_tmpRes))?$_fields['answer']:array_shift( $_tmpRes );
			}
			if( empty( $this->_settings['template'] ) ){
				$this->_settings['template']='<h1>{title}</h1><div>{question}<br/>Answer: {answer}</div>';
			}
			ksort($_fields);
			ksort($this->_tags);
			$_tmpTemplate=$this->_settings['template'];
			$_replace=array_intersect_key( $_fields, $this->_tags );
			$_tmpTemplate=str_replace( $this->_tags, $_replace, $_tmpTemplate );
			$_item['body']=$_tmpTemplate;
		}
		$this->init();
	}
	
	protected function assemblyQuery() {}

	public function withIds( $_arrIds=array() ){
		$this->_withIds = $_arrIds;
		return $this;
	}
	
	public static function getInstance() {}
	
	public function setFilter( $_arrFilter=array() ) {
		$this->_settings=$_arrFilter;
		return $this;
	}

	public function getFilter( &$arrRes ) {
		$arrRes = $this->_settings;
		return $this;
	}

	public function setLimited( $_intLimit ) {
		$this->_limit=$_intLimit;
		return $this;
	}

	public function setCounter( $_intCounter ) {
		$this->_counter=$_intCounter;
		return $this;
	}

	public function getAdditional( &$arrRes ) {
		$this->_res = $arrRes;
		return $this;
	}

	public function setPost( $_arrPost=array() ){
		$this->_post=$_arrPost;
		return $this;
	}

	public function setFile( $_arrFile=array() ){
		$this->_files=$_arrFile;
		return $this;
	}

	public function getResult( &$arrRes ){
		return $this;
	}

	public function withTags( $_str ){
		if( empty($_str) ){
			return $this;
		}
		$this->_withTags=$_str;
		return $this;
	}

	public function withPaging( $_arr=array() ) {
		$this->_withPaging=$_arr;
		if ( empty($this->_withPaging['page']) ) {
			$this->_withPaging['page'] = 1;
		} else {
			$this->arrSelect['start'] = $this->arrSelect['results'] * ($this->_withPaging['page']-1);
		}
		return $this;
	}

	public function getPaging( &$arrRes ) {
		$arrRes=$this->_paging;
		for($i=1; $i<$this->_paging['curpage']; $i++){
			$arrRes['num'][]=array('number'=>$i,'url'=>'./?page='.$i);
		}
		$arrRes['recfrom']=$arrRes['recto']=$arrRes['recall']=1;
		$arrRes['num'][] = array (
			'sel' => 1,
			'number' => $this->_paging['curpage']
		);
		$arrRes['num'][] = array (
			'number' => $this->_paging['curpage']+1,
			'url'=> './?page='.($this->_paging['curpage']+1)
		);
		$this->_paging=array();
		return $this;
	}
	
	public function setSettings( $arrSettings ){
		if( empty($arrSettings) ){
			return false;
		}
		$this->_settings=$arrSettings;
		return $this;
	}
}
?>