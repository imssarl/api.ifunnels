<?php
class Project_Urlanalyz {

	/**
	 * Settings for analyze
	 *
	 * @var array
	 */
	private $_params = array();
	/**
	 * HTML content
	 *
	 * @var string
	 */
	private $_content='';
	/**
	 * Meta data
	 *
	 * @var array
	 */
	private $_meta=array();
	/**
	 * Content in <title></title>
	 *
	 * @var string
	 */
	private $_title='';
	/**
	 * Result keywords whith repeats
	 *
	 * @var array
	 */
	private $_arrList=array();
	/**
	 * Set settings for analyze
	 *
	 * @param array $_arrParams
	 * @return object
	 */
	public function setSettings( $_arrParams ){
		if (empty($_arrParams)){
			return false;
		}
		$this->_params=$_arrParams;
		return $this;
	}
	
	/**
	 * Geting HTML content from url;
	 *
	 * @return bool
	 */
	private function getContent(){
		if (empty($this->_params['url'])){
			return false;
		}
		$_curl = Core_Curl::getInstance();
		if ( !$_curl->getContent($this->_params['url']) ){
			return false;
		}
		$this->_content=$_curl->getResponce();
		return !empty($this->_content);
	}
	/**
	 * Start analyze
	 *
	 * @return bool
	 */
	public function run(){
		if ( !$this->getContent() ){
			return false;
		}
		if (!empty($this->_params['show_title'])){
			$this->getTitle();
		}
		if ( !$this->getTwoKeywords() ){
			return false;
		}
		if ( !$this->prepereData() ){
			return false;
		}
		return true;
	}
	
	/**
	 * Return keywords
	 *
	 * @return array
	 */
	public function getResponce(){
		return array_slice($this->_arrList ,0 ,$this->_params['count_words']); 
	}
	
	/**
	 * Return params
	 *
	 * @return array
	 */
	public function getParams(){
		preg_match('@^(?:http://)?([^/]+)@i',$this->_params['url'],$matches );
		return array(
			'title'=>$this->_title,
			'meta'=>$this->_meta, 
			'url'=>$this->_params['url'],
			'only_name'=> str_replace('http://','',$this->_params['url']),
			'domain'=>$matches[1]
			);
	}

	/**
	 * Prepere keyword
	 *
	 * @return bool
	 */
	private function prepereData(){
		if ( empty($this->_arrList) ){
			return false;
		}
		$p=count($this->_arrList)*10/100;
		for($i=0; $i<$p; $i++ ){
			$_arr[]=$this->_arrList[$i]['words'];
		}
		unset($this->_arrList);
		$_arr=array_unique($_arr);
		foreach ($_arr as $_key=>$_item){
			$this->_arrList[]=array('words'=>$_item,'score'=>$this->getScore($_item));
			unset($_arr[$_key]);
		}
		$this->_arrList=$this->array_sort($this->_arrList,'score',SORT_DESC);
		return !empty($this->_arrList);
	}
	/**
	 * Get content in tag "title"
	 *
	 * @return bool
	 */
	private function getTitle(){
		$string=strtolower($this->_content);
		preg_match_all("|<title>(.*)</title>|U",$string,$matches, PREG_PATTERN_ORDER);
		$this->_title=$matches[1][0];
		return !empty($this->_title);
	}
	
	/**
	 * Get keywords from url
	 *
	 * @return bool
	 */
	private function getTwoKeywords(){
		$_str=strtolower($this->_content);
		$this->stripstr( $_str );
		if ( $this->getMeta() ){
			$_str=(!empty($this->_meta['description']))?$this->_meta['description'].' '.$_str:$_str;
		}
		$_arr=split( ' ', $_str );
		for( $i=0; $i<count( $_arr ); $i++ ){
			if ( strlen(trim($_arr[$i+1]))<=3 ){
				$_arrwords[]=array(
					'words'=>trim($_arr[$i]) . ' ' .trim($_arr[$i+1]) . ' ' .trim($_arr[$i+2]),
					'score'=>$this->getScore( $_arr[$i] ) + $this->getScore( $_arr[$i+1] ) + $this->getScore( $_arr[$i+2] ),
				);
				$i++;
			} else {
				$_arrwords[]=array( 
					'words'=>trim($_arr[$i]) . ' ' .trim($_arr[$i+1]),
					'score'=>$this->getScore( $_arr[$i] ) + $this->getScore( $_arr[$i+1] ),
				);
			}
		}
		$this->_arrList=$this->array_sort($_arrwords,'score',SORT_DESC);
		unset($_arrwords);
		unset($_arr);
		return !empty($this->_arrList);
	}
	
	/**
	 * Get score for each keyword
	 *
	 * @param string $_str
	 * @return int
	 */
	private function getScore($_str){
		if (strlen($_str)<=3){
			return 0;
		}
		return substr_count(strtolower($this->_content), trim($_str) );
	}
	
	/**
	 * Strips string
	 *
	 * @param string $_str
	 */
	private function stripstr(&$_str){
		$_str=preg_replace('@&.*?;@i','',$_str);
		$_str=preg_replace('@\s{2}@i','',$_str);
		$_str=preg_replace('@<script.*?>.*?</script>@i','',$_str);
		$_str=preg_replace('@<style.*?>.*?</style>@i','',$_str);
		$_str=preg_replace('@<\!--.*?>.*?-->@i','',$_str);		
		$_str=strip_tags($_str);
		$_search=array('"',"'","~","!","$","%","^","&","*","(",")","-","|","+","=",":",";",'\"',"'","<",">",",",".","?","/","@","[","]","{","}","&nbsp;","\r","\n");
	    $_replace=array('','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','');
		$_str=str_replace($_search,$_replace,$_str);
		$_str=preg_replace('@[^a-z][0-9]]@i'," ",$_str);
		$_str=preg_replace('@<.*?>@i',' ',$_str);
		$_str=preg_replace('@\s{1,10}@i'," ",$_str);
		
	}
	
	/**
	 * Get Meta data from html
	 *
	 * @return bool
	 */
	private function getMeta(){
		$string=strtolower($this->_content);
		preg_match_all("|<meta (.*?)>|U",$string,$matches, PREG_PATTERN_ORDER);
		if (empty($matches[1])){
			return false;
		}
		$arrMeta=$matches[1];
		unset($matches);
		foreach ($arrMeta as $_meta ){
			preg_match_all("|name\=\"(.*)\" |U",$_meta,$name_matches, PREG_PATTERN_ORDER);
			if(!empty($name_matches[1][0])){
				preg_match_all("|content\=\"(.*)\"|U",$_meta,$content_matches, PREG_PATTERN_ORDER);
				$matches[$name_matches[1][0]]=$content_matches[1][0];
			}
		}
		$this->_meta=$matches;
		unset($matches);
		return !empty($this->_meta);
	}
	
	/**
	 * Sort array
	 *
	 * @param array $array
	 * @param string $on
	 * @param const $order
	 * @return array
	 */
	private function array_sort($array, $on, $order=SORT_ASC) {
		$new_array = array();
		$sortable_array = array();
		if (count($array) > 0) {
			foreach ($array as $k => $v) {
				if (is_array($v)) {
					foreach ($v as $k2 => $v2) {
						if ($k2 == $on) {
							$sortable_array[$k] = $v2;
						}
					}
				} else {
					$sortable_array[$k] = $v;
				}
			}
			switch ($order) {
				case SORT_ASC:
					asort($sortable_array);
					break;
				case SORT_DESC:
					arsort($sortable_array);
					break;
			}
			foreach ($sortable_array as $k => $v) {
				$new_array[] = $array[$k];
			}
		}
		return $new_array;
	}

}
?>