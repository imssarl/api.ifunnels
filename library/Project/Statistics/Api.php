<?php

class Project_Statistics_Api extends Core_Data_Storage {
/*
CREATE TABLE `stat_api` (
	`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	`request` TEXT NULL,
	`ip` VARCHAR(50) NULL DEFAULT '0',
	`referer` VARCHAR(255) NULL DEFAULT '0',
	`date` DATETIME NULL DEFAULT NULL,
	PRIMARY KEY (`id`)
)
COLLATE='utf8_general_ci'
ENGINE=InnoDB;
*/
	protected $_fields=array('id','request','ip','referer','date');
	protected $_table='stat_api';
	private $_withStatisticByIp=false;
	private $_withStatisticByReferer=false;
	private $_withTime=false;
	private $_withIP=false;
	const TIME_TODAY=1,TIME_YESTERDAY=2,TIME_LAST_7_DAYS=3,TIME_THIS_MONTH=4,THIS_YEAR=5,TIME_LAST_YEAR=6,TIME_ALL=7, TIME_MINUTE=8;

	public static function add(){
		$_self=new self();
		$_self->setEntered(array(
			'request'=>serialize( $_REQUEST ),
			'ip'=>$_SERVER['REMOTE_ADDR'],
			'referer'=>$_SERVER['HTTP_REFERER'],
			'date'=>date('Y-m-d H:i:s',time())
		))->set();
	}

	protected function init(){
		parent::init();
		$this->_withTime=false;
		$this->_withIP=false;
		$this->_withStatisticByIp=false;
		$this->_withStatisticByReferer=false;
	}
	
	public function withFilter( $arrFilter ){
		if( !empty( $arrFilter['time'] ) ){
			$this->withTime( $arrFilter['time'] );
		}
		if( !empty( $arrFilter['ip'] ) ){
			$this->withStatisticByIp( $arrFilter['ip'] );
		}
		if( !empty( $arrFilter['referer'] ) ){
			$this->withStatisticByReferer( $arrFilter['referer'] );
		}
		return $this;
	}
	
	public function withStatisticByIp( $_type ){
		$this->_withStatisticByIp=$_type;
		return $this;
	}
	
	public function withStatisticByReferer( $_type ){
		$this->_withStatisticByReferer=$_type;
		return $this;
	}
	
	public function withTime( $_type ){
		$this->_withTime=$_type;
		return $this;
	}

	public function withIP(){
		if( isset( $_SERVER['REMOTE_ADDR'] ) || isset( $_SERVER['HTTP_REFERER'] ) ){
			$this->_withIP=true;
		}
		return $this;
	}

	public static function checkRequestsLimit(){
		$_self=new self();
		if( $_self->withTime( self::TIME_MINUTE )->withIP()->onlyCount()->getList() > 1 ){
			return true;
		}
		return false;
	}

	protected function assemblyQuery(){
		parent::assemblyQuery();
		switch( $this->_withTime ){
			case self::TIME_MINUTE :
				$this->_crawler->set_where("DATE_FORMAT(FROM_UNIXTIME(d.date),'%Y-%m-%d-%H-%i')='".date('Y-m-d-H-i',time())."'");
				break;
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
		if( $this->_withIP ){
			$_arrWhere=array();
			if( isset( $_SERVER['REMOTE_ADDR'] ) ){
				$_arrWhere[]="ip='".@$_SERVER['REMOTE_ADDR']."'";
			}
			if( isset( $_SERVER['HTTP_REFERER'] ) ){
				$_arrWhere[]="referer='".@$_SERVER['HTTP_REFERER']."'";
			}
			if( !empty( $_arrWhere ) ){
				$this->_crawler->set_where("(".implode(" OR ", $_arrWhere ).")");
			}
		}
		if( $this->_withStatisticByIp ){
			$this->_crawler->set_where('d.ip="'.$this->_withStatisticByIp.'"' );
		}
		if( $this->withStatisticByReferer ){
			$this->_crawler->set_where('d.referer="'.$this->withStatisticByReferer.'"' );
		}
	}
}
?>