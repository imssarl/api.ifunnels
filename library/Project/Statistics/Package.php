<?php

class Project_Statistics_Package extends Core_Data_Storage {

	protected $_fields=array('id','package_id','flg_type','ip','referer','date');
	protected $_table='stat_package';
	const TYPE_IMPRESSION=1,TYPE_CLICK=2,TYPE_SALE=3;
	private $_onlyImpressions=false;
	private $_onlyClicks=false;
	private $_onlySales=false;
	private $_withTime=false;
	const TIME_TODAY=1,TIME_YESTERDAY=2,TIME_LAST_7_DAYS=3,TIME_THIS_MONTH=4,THIS_YEAR=5,TIME_LAST_YEAR=6,TIME_ALL=7;

	public static function add( $_packageId, $_type ){
		$_self=new self();
		$_self->setEntered(array(
			'package_id'=>$_packageId,
			'flg_type'=>$_type,
			'ip'=>$_SERVER['REMOTE_ADDR'],
			'referer'=>$_SERVER['HTTP_REFERER'],
			'date'=>date('Y-m-d H:i:s',time())
		))->set();
	}

	protected function init(){
		parent::init();
		$this->_onlyImpressions=false;
		$this->_withTime=false;
		$this->_onlyClicks=false;
		$this->_onlySales=false;
	}

	public function withFilter( $arrFilter ){
		$this->withTime( $arrFilter['time'] );
		return $this;
	}

	public function onlyImpressions(){
		$this->_onlyImpressions=true;
		return $this;
	}

	public function onlyClicks(){
		$this->_onlyClicks=true;
		return $this;
	}

	public function onlySales(){
		$this->_onlySales=true;
		return $this;
	}

	public function withTime( $_type ){
		$this->_withTime=$_type;
		return $this;
	}

	protected function assemblyQuery(){
		parent::assemblyQuery();
		switch( $this->_withTime ){
			case self::TIME_TODAY :
				$this->_crawler->set_where("DATE_FORMAT(FROM_UNIXTIME(d.date),'%Y-%m-%d')='".date('Y-m-d',time())."'");
				break;
			case self::TIME_YESTERDAY :
				$this->_crawler->set_where("DATE_FORMAT(FROM_UNIXTIME(d.date),'%Y-%m-%d')='".date('Y-m-d',strtotime("yesterday"))."'");
				break;
			case self::TIME_LAST_7_DAYS :
				$this->_crawler->set_where("DATE_FORMAT(FROM_UNIXTIME(d.date),'%Y-%m-%d')>'".date('Y-m-d',time()-(60*60*24*7))."'");
				break;
			case self::TIME_THIS_MONTH :
				$this->_crawler->set_where("DATE_FORMAT(FROM_UNIXTIME(d.date),'%Y-%m')='".date('Y-m',time())."'");
				break;
			case self::TIME_LAST_YEAR :
				$this->_crawler->set_where("DATE_FORMAT(FROM_UNIXTIME(d.date),'%Y')='".date('Y',strtotime('-1 year'))."'");
				break;
			case self::TIME_ALL :
			default:
			break;
		}
	}

	public function getList( &$arrRes ){
		parent::getList( $arrTmp );
		foreach( $arrTmp as $_item ){
			if( $_item['flg_type']==self::TYPE_CLICK )
			$arrRes[$_item['package_id']]['clicks'][]=$_item;
			if( $_item['flg_type']==self::TYPE_IMPRESSION )
			$arrRes[$_item['package_id']]['impressions'][]=$_item;
			if( $_item['flg_type']==self::TYPE_SALE )
			$arrRes[$_item['package_id']]['sales'][]=$_item;
		}
	}
}
?>