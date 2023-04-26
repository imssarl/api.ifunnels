<?php
class Project_Amazon extends Core_Data_Storage{

	public function __construct(){
		$_arrNulls=Core_Sql::getAssoc("SELECT NULL FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = 'amazon_queue';");
		if( count( $_arrNulls ) == 0 ){
			Core_Sql::setExec("CREATE TABLE `amazon_queue` (
				`id` INT(11) NOT NULL AUTO_INCREMENT,
				`sleep` INT(4) NULL DEFAULT NULL,
				`priority` INT(1) NULL DEFAULT 0,
				`affiliate` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_unicode_ci',
				`link` TEXT NULL DEFAULT NULL COLLATE 'utf8_unicode_ci',
				`edited` INT(11) UNSIGNED NOT NULL DEFAULT '0',
				`added` INT(11) UNSIGNED NOT NULL DEFAULT '0',
				UNIQUE INDEX `id` (`id`)
			);");
		}
	}

	protected $_table='amazon_queue';
	protected $_fields=array('id',
		'sleep','affiliate','link','priority',
		'added','edited'
	);

	private $_strLink=false;
	private $_strAffiliate=false;
	private $_withAffiliate=false;
	private $_setPriority=false;
	private $_removeLast=false;
	
	public function withAffiliate( $_str ){
		$this->_withAffiliate=$_str;
		return $this;
	}
	
	public function setPriority( $_str ){
		$this->_setPriority=$_str;
		return $this;
	}
	
	public function setLink( $_strBase64 ){
		$this->_strLink=base64_decode( $_strBase64 );
		if( empty( $_strBase64 ) || empty( $this->_strLink ) ){
			return $this;
		}
		$_arrUrl=parse_url( $this->_strLink );
		parse_str($_arrUrl['query'], $_arrUrl['query']);
		if( !isset( $_arrUrl['query']['AssociateTag'] ) ){
			$this->_strLink=false;
			return $this;
		}
		$this->withAffiliate( $_arrUrl['query']['AssociateTag'] )->withOrder( 'd.added--up' )->getList( $_arrData );
		if( count( $_arrData )!=0 &&  !empty( $_arrData[0] ) && isset( $_arrData[0]['sleep'] ) ){
			$this->_intSleep=(int)$_arrData[0]['sleep'];
			$this->_removeLast=$_arrData[count($_arrData)-1]['id'];
		}
		$this->setEntered( array(
			'sleep'=>$this->_intSleep,
			'affiliate'=>$_arrUrl['query']['AssociateTag'],
			'link'=>$_strBase64,
			'priority'=>( !empty( $this->_setPriority )? $this->_setPriority : 0 )
		) )->set();
		$this->_withUrl=$_strBase64;
		$this->_strAffiliate=$_arrUrl['query']['AssociateTag'];
		return $this;
	}
	
	protected function init(){
		parent::init();
	}

	protected function assemblyQuery(){
		parent::assemblyQuery();
		if( $this->_withAffiliate){
			$this->_crawler->set_where('d.affiliate='.Core_Sql::fixInjection($this->_withAffiliate));
		}
	//	$this->_crawler->get_sql( $_strSql, $this->_paging );
	//	p( array($_strSql, $this->_paging, $this->_crawler) );
	}
	
	private $_intSleep=1000;
	private $_flgUpdateSleep=false;
	private $_flgNotUpdateSleepNow=false;
	
	public function runQueue(){
		$_intCounter=0;
		do{
			$this->withAffiliate( $this->_strAffiliate )->withOrder( 'd.priority--up' )->getList( $_arrData );
			$this->_intSleep=(int)$_arrData[0]['sleep'];
			if( count( $_arrData )>2 && !$this->_flgUpdateSleep && !$this->_flgNotUpdateSleepNow && $this->_intSleep>100 ){
				$this->_flgUpdateSleep=true;
				$this->_intSleep-=100;
				$this->updateSleep( $this->_strAffiliate, $this->_intSleep );
			}
			usleep( $this->_intSleep );
			$_intCounter+=$this->_intSleep;
		}while( $_arrData[0]['link'] != $this->_withUrl && $_intCounter<5000 );
		
		$data=@file_get_contents( base64_decode( $this->_withUrl ) );
		/*
		$c=curl_init( base64_decode( $this->_withUrl ) );
		curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($c, CURLOPT_TIMEOUT, 15);
		$data=curl_exec($c);
		curl_close($c);
		*/
		
		return $data;
	}
	
	public function getAmazon(){
		$_checkCounter=0;
		do{
			$data='You are submitting requests too quickly';
			if( strpos( $data, 'You are submitting requests too quickly' )!==false ){
				if( $this->_flgUpdateSleep && !$this->_flgNotUpdateSleepNow && $this->_intSleep<1000 ){
					$this->_flgNotUpdateSleepNow=true;
					$this->_intSleep+=100;
					$this->updateSleep( $this->_strAffiliate, $this->_intSleep );
				}
				$data=$this->runQueue();
			}
			$_checkCounter++;
		}while( strpos( $data, 'You are submitting requests too quickly' )!==false && $_checkCounter<4 );
		$this->withIds( $this->_removeLast )->del();
		return $data;
	}
	
	public static function updateSleep( $_affiliate, $_sleep ){
		Core_Sql::setExec( 'UPDATE `amazon_queue` SET `sleep`='.$_sleep.' WHERE `affiliate`="'.$_affiliate.'"' );
	}

}
?>