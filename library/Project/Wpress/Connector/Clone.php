<?php


/**
 * Blog clone transport
 */
class Project_Wpress_Connector_Clone {

	protected $_cloneSrcDir=''; // там где лежат исходники используемые для копирования блога
	protected $_mutatorDir=''; // папка где собирается конкретная инсталляция (см. Project_Wpress_Connector_Create)
	/**
	 * Project_Placement_Transport
	 * @var Project_Placement_Transport object
	 */
	private $_transport=false;
	/**
	 * Place for new blog
	 * @var Core_Data object
	 */
	private $_arrDest=array();
	/**
	 * Blog for clon
	 * @var Core_Data object
	 */
	private $_arrClon=array();
	
	// в $obj site_id (для получения фтп настроек) а также настройки для фтп куда клонировать и site_url
	public function init() {
		$this->_mutatorDir=Zend_Registry::get( 'objUser' )->getTmpDirName().'Project_Wpress_Connector_Create@generateMutator'.DIRECTORY_SEPARATOR;
		$this->_cloneSrcDir=Zend_Registry::get( 'config' )->path->absolute->user_files.'blogfusion'.DIRECTORY_SEPARATOR.'clone_source'.DIRECTORY_SEPARATOR;
		return true;
	}

	public function setClon( Core_Data $_arrData ){
		if (empty($_arrData)){
			return false;
		}
		$this->_arrClon=$_arrData;
		return $this;
	}

	public function setDestination( Core_Data $_arrData ){
		if (empty($_arrData)){
			return false;
		}
		$this->_arrDest=$_arrData;
		return $this;
	}

	public function prepareServer() {
		$this->_transport=new Project_Placement_Transport();
		if ( !$this->_transport->setInfo( $this->_arrDest->filtered )->setDb( $this->_arrDest ) ) {
			return false;
		}
		$this->_transport->breakConnect();
		if ( !$this->_transport->setInfo( $this->_arrClon->filtered ) ) {
			return false;
		}
		if ( !$this->_transport
			->setSourceDir( $this->_cloneSrcDir )
			->place() ) {
			return false;
		}
		return true;
	}

	// на этом этапе к фтп не подключаемся
	public function setConfigCloner() {
		// подготовить диру, $this->_mutatorDir определяется выше потому что она ещё понадобится в других методах (шагах)
		$_strTmp='Project_Wpress_Connector_Clone@generateMutator';
		if ( !Zend_Registry::get( 'objUser' )->prepareTmpDir( $_strTmp ) ) {
			return false;
		}
		$this->_mutatorDir=$_strTmp;
		Core_Files::getContent( $_strFile, $this->_cloneSrcDir.'clone'.DIRECTORY_SEPARATOR . 'clone.php');
		if ( empty($_strFile) ){
			return false;
		}
		$_serach = array(
			'#DB_NAME#',
			'#DB_USER#',
			'#DB_PASSWORD#',
			'#DB_HOST#',
			'#new_table_prefix#',
			'#siteurl#',
			'#home#',
			'#blogname#',
			'#without_post#',
			'#without_page#'
		);
		if ( substr( $this->_arrDest->filtered['ftp_directory'], -1 )!='/' ) {
			$this->_arrDest['ftp_directory'].='/';
		}
		if ( substr( $this->_arrDest->filtered['ftp_directory'], 0,1 )!='/' ) {
			$this->_arrDest->filtered['ftp_directory']='/'.$this->_arrDest->filtered['ftp_directory'];
		}		
		$_replace = array(
			$this->_arrDest->filtered['db_name'],
			$this->_arrDest->filtered['db_username'],
			$this->_arrDest->filtered['db_password'],
			$this->_arrDest->filtered['db_host'],
			$this->_arrDest->filtered['db_tableprefix'],
			$this->_arrDest->filtered['url'],
			(empty($this->_arrDest->filtered['ftp_directory']))? '/':$this->_arrDest->filtered['ftp_directory'],
			$this->_arrDest->filtered['title'],
			$this->_arrDest->filtered['without_post'],
			$this->_arrDest->filtered['without_page'],
			
		);
		$_strFile=str_replace($_serach,$_replace,$_strFile);
		mkdir($_strTmp.'clone'.DIRECTORY_SEPARATOR);
		if ( !Core_Files::setContent($_strFile,$_strTmp.'clone'.DIRECTORY_SEPARATOR.'clone.php') ){
			return false;
		}
		return true;
	}

	public function uploadMutator() {
		if ( !$this->_transport
			->setSourceDir( $this->_mutatorDir )
			->place() ) {
			return false;
		}
		return true;
	}

	public function startCloner(){
		// упаковываем блог
		if ( !Core_Curl::getResult( $_strRes, $this->_arrClon->filtered['url'].'clone/pack.php' ) ) {
			return Core_Data_Errors::getInstance()->setError( 'no respond '.$this->_arrClon->filtered['url'].'clone/pack.php' );
		}
		// заливаем блог на новый сервак;
		if (!$this->uploadDestination() ) {
			return false;
		}
		return true;		
	}
	
	private function uploadDestination(){
		$_strTmp='Project_Wpress_Connector_Clone@uploadDestination';
		if ( !Zend_Registry::get( 'objUser' )->prepareTmpDir( $_strTmp ) ) {
			return false;
		}
		if ( !$this->_transport->downloadAndBreakConnect($this->_arrClon->filtered['ftp_directory'].'clone/blog.zip',$_strTmp.'blog.zip') ) {
			return Core_Data_Errors::getInstance()->setError('unbale download file ' . $this->_arrClon->filtered['url'].'clone/blog.zip' );
		}
		$this->setClon( $this->_arrDest );
		if (!$this->prepareServer() ){
			return false;
		}
		if ( !$this->uploadMutator() ){
			return false;
		}
		if ( !$this->_transport
			->setSourceDir( $_strTmp.'blog.zip' )
			->place() ) {
			return false;
		}
		if ( !$this->endCloner() ){
			return false;
		}	
		return true;
	}
	
	private function endCloner(){
		// распаковываем блог
		if ( !Core_Curl::getResult( $_strRes, $this->_arrDest->filtered['url'].'clone/unpack.php' ) ) {
			return Core_Data_Errors::getInstance()->setError( 'no respond '.$this->_arrDest->filtered['url'].'clone/unpack.php' );
		}		
		return true;
	}
}
?>