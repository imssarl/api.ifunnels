<?php
class Project_Pagebuilder_Blocks extends Core_Data_Storage{
	protected $_table='pb_blocks';
	protected $_fields=array( 'id', 'blocks_category', 'blocks_url', 'blocks_height', 'blocks_thumb', 'edited', 'added' );

	protected $_withUrl = false;

	public function withUrl($_url){
		$this->_withUrl = $_url;
		return $this;
	}

	protected function assemblyQuery(){
		parent::assemblyQuery();
		if($this->_withUrl){
			$this->_crawler->set_where( "d.blocks_url = ".Core_Sql::fixInjection( $this->_withUrl ) );
		}
	}

	protected function init(){
		parent::init();
		$this->_withUrl = false;
	}

	public function set(){
		$this->_data->setFilter( array( 'clear' ) );
		if ( !$this->beforeSet() ){
			return false;
		}
		$this->_data->setElement( 'id', Core_Sql::setInsertUpdate( $this->_table, $this->_data->filtered) );
		return $this->afterSet();
	}

	public function del(){
		$ids = $this->_withIds;
		$this->withIds($ids)->onlyOne()->getList($dataBlock);
		/** delete the thumbnail */
		if (file_exists(Zend_Registry::get('config')->path->absolute->pagebuilder . $dataBlock['blocks_thumb']) ) unlink(Zend_Registry::get('config')->path->absolute->pagebuilder . $dataBlock['blocks_thumb']);

		/** delete the template file */
		if (file_exists(Zend_Registry::get('config')->path->absolute->pagebuilder . $dataBlock['blocks_url'])) unlink(Zend_Registry::get('config')->path->absolute->pagebuilder . $dataBlock['blocks_url']);
		$this->_withIds = $ids;
		
		if ( empty( $this->_withIds ) ){
			$_bool=false;
		} else {
			Core_Sql::setExec( 'DELETE FROM '.$this->_table.' 
				WHERE id IN('.Core_Sql::fixInjection( $this->_withIds ).')'.($this->_onlyOwner&&$this->getOwnerId( $_intId )? ' AND user_id='.$_intId:'') );
			$_bool=true;
		}
		$this->init();
		return $_bool;
	}

	/**
	 * Random string 
	 * 
	 * @param $type | basis, alnum, numeric, nozero, alpha, unique, md5, encrypt, sha1
	 * @param $len
	 * @return string
	 */
	public static function random_string($type = 'alnum', $len = 8){
		switch ($type)
		{
			case 'basic':
				return mt_rand();
			case 'alnum':
			case 'numeric':
			case 'nozero':
			case 'alpha':
				switch ($type)
				{
					case 'alpha':
						$pool = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
						break;
					case 'alnum':
						$pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
						break;
					case 'numeric':
						$pool = '0123456789';
						break;
					case 'nozero':
						$pool = '123456789';
						break;
				}
				return substr(str_shuffle(str_repeat($pool, ceil($len / strlen($pool)))), 0, $len);
			case 'unique': // todo: remove in 3.1+
			case 'md5':
				return md5(uniqid(mt_rand()));
			case 'encrypt': // todo: remove in 3.1+
			case 'sha1':
				return sha1(uniqid(mt_rand(), TRUE));
		}
	}

	public static function loadTemplateFiles(){
		$templates = [];
		$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(substr(Zend_Registry::get('config')->path->html->pagebuilder, 1) . 'elements', RecursiveDirectoryIterator::SKIP_DOTS));

		foreach ($it as $file){
			if (pathinfo($file, PATHINFO_EXTENSION) == "html" && strpos($file, 'skeleton') === false){
				$templates[] = str_replace('\\', '/', (string)$file);
			}
		}
		sort($templates);
		return $templates;
	}

	/**
	 * Updates the block's original template file
	 *
	 * @param $tempalte | URL to file
	 * @param $source | Data 
	 * @return  boolean
	 */
	public function updateOriginal($template, $source){
		
		$template = str_replace((!empty($_SERVER['HTTPS']) && 'off' !== strtolower($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/skin/pagebuilder/', '', $template);
		$content = $source;
		if ( file_exists( Zend_Registry::get('config')->path->absolute->pagebuilder . $template) ){
			if ( file_put_contents(Zend_Registry::get('config')->path->absolute->pagebuilder . $template, $content) !== false ) {
				return true;
			}
			else {
				return false;
			}
		}
		else {
			return false;
		}
	}
}