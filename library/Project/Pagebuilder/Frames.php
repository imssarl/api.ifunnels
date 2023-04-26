<?php

require_once Zend_Registry::get('config')->path->relative->library . 'SimpleHTMLDom/simple_html_dom.php';

class Project_Pagebuilder_Frames extends Core_Data_Storage{
	protected $_table='pb_frames';
	protected $_fields=array( 'id', 'pages_id', 'sites_id', 'position', 'frames_content', 'frames_height', 'frames_original_url', 'frames_loaderfunction', 'frames_sandbox', 'frames_timestamp', 'frames_global', 'frames_popup', 'frames_embeds', 'frames_settings', 'favourite', 'revision', 'created_at', 'modified_at' );

	protected $_withPageId = false;
	protected $_withSiteId = false;
	protected $_withRevision = false;
	protected $_withFramesTimestamp = false;
	protected $_withoutDecode = false;

	public function withPageId($_pageid){
		$this->_withPageId = $_pageid;
		return $this;
	}

	public function withSiteId($_siteid){
		$this->_withSiteId = $_siteid;
		return $this;
	}

	public function withRevision($_state){
		$this->_withRevision = $_state;
		return $this;
	}

	public function withFramesTimestamp($timestamp){
		$this->_withFramesTimestamp = $timestamp;
		return $this;
	}

	public function withoutDecode(){
		$this->_withoutDecode = true;
		return $this;
	}

	public function beforeSet(){
		$this->_data->setFilter( array( 'clear' ) );
		if(isset($this->_data->filtered['frames_content']) && !$this->_withoutDecode){
			$this->_data->filtered['frames_content'] = self::processFrameContent(base64_decode($this->_data->filtered['frames_content']));
		}
		return true;
	}

	public function set(){
		$this->_data->setFilter( array( 'clear' ) );
		if ( !$this->beforeSet() ){
			return false;
		}
		$this->_data->setElement( 'id', Core_Sql::setInsertUpdate( $this->_table, $this->_data->filtered) );
		return $this->afterSet();
	}

	public function afterSet(){
		$this->_withoutDecode = false;
		return $this;
	}

	protected function assemblyQuery(){
		parent::assemblyQuery();
		if($this->_withPageId){
			$this->_crawler->set_where( 'd.pages_id IN ('.Core_Sql::fixInjection( $this->_withPageId ) . ')' );
		}
		if($this->_withSiteId){
			$this->_crawler->set_where( 'd.sites_id = '.Core_Sql::fixInjection( $this->_withSiteId ) );
		}
		if($this->_withRevision !== FALSE){
			$this->_crawler->set_where( 'd.revision = '.Core_Sql::fixInjection( $this->_withRevision ) );
		}
		if($this->_withFramesTimestamp !== FALSE){
			$this->_crawler->set_where( 'd.frames_timestamp = '.Core_Sql::fixInjection( $this->_withFramesTimestamp ) );
		}
	}

	public static function processFrameContent($frameContent){

		$html = str_get_html($frameContent);

		/** remove data-selector attributes */
		foreach($html->find('*[data-selector]') as $element){
			/** remove attribute */
			$element->removeAttribute("data-selector");
		}

		/** remove draggable attributes */
		foreach($html->find('*[draggable]') as $element){
			$element->removeAttribute("draggable");
		}

		/** remove builder scripts (these are injected when loading the iframes) */
		foreach($html->find('script.builder') as $element){
			$element->outertext = '';
		}

		/** remove background images for parallax blocks */
		foreach($html->find('*[data-parallax]') as $element) {
			$oldCss = $element->getAttribute('style');
			$replaceWith = "background-image: none";
			$regex = '/(background-image: url\((["|\']?))(.+)(["|\']?\))/';
			$oldCss = preg_replace($regex, $replaceWith, $oldCss);
			$element->setAttribute('style', $oldCss);
		}

		/** remove data-hover="true" attribute **/
		foreach($html->find('*[data-hover]') as $element){
			$element->removeAttribute("data-hover");
		}

		/** remove the sb_hover class name **/
		foreach($html->find('*[class*="sb_hover"]') as $element){
			$element->class = str_replace('sb_hover', '', $element->class);
		}

		/** remove .canvasElToolbar elements **/
		foreach($html->find('div.canvasElToolbar') as $el){
			$el->outertext = '';
		}

		foreach($html->find("*[href]") as $element){
			$element->setAttribute('href', str_replace('..', '/skin/pagebuilder/elements', $element->getAttribute('href')));
		}

		foreach($html->find("*[src]") as $element){
			$element->setAttribute('src', str_replace('..', '/skin/pagebuilder/elements', $element->getAttribute('src')));
		}

		foreach($html->find("//*[@style]") as $element){
			$element->setAttribute('style', str_replace('..', '/skin/pagebuilder/elements', $element->getAttribute('style')));
		}

		return $html->find('html', 0)->outertext;
	}

	protected function init(){
		parent::init();
		$this->_withPageId = false;
		$this->_withSiteId = false;
		$this->_withRevision = false;
		$this->_withFramesTimestamp = false;
	}
}