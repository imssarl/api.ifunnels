<?php

class Project_Conversionpixel extends Core_Data_Storage {
	protected $_table='lpb_conversionpixel';
	protected $_fields=array('id', 'squeeze_id', 'flg_pixeltype', 'ip', 'country_id', 'added');
	private $_flgPixeltype = array('view' => 1, 'lead' => 2, 'sale' => 3);
	protected $_withSplitIds=false;

	public function beforeSet(){
		$this->_data->setFilter( array( 'clear' ) );
		$this->_data->setElements(
			array(
				'flg_pixeltype' => $this->_flgPixeltype[$this->_data->filtered['param']],
				'ip' => self::getUserIp()
			)
		);
		$this->_data->setElement( 'country_id', Core_Sql::getCell('SELECT country_id FROM getip_countries2ip WHERE ip_start <= ' . sprintf("%u\n", ip2long($this->_data->filtered['ip'])) . ' AND ' . sprintf("%u\n", ip2long($this->_data->filtered['ip'])) . ' <= ip_end') );
		return true;
	}

	public function getList(&$mixRes){
		try {
			Core_Sql::setConnectToServer( 'syndication.qjmpz.com' );
			if($this->_withSplitIds){
				$mixRes = Core_Sql::getAssoc('SELECT l.flg_pixeltype, COUNT( * ) AS count, g.name as country FROM  `lpb_conversionpixel` AS l, `getip_countries` g WHERE l.squeeze_id IN ('.Core_Sql::fixInjection($this->_withSplitIds).') AND l.country_id = g.id GROUP BY l.flg_pixeltype, l.country_id');

				$_tmp = array();
				foreach ($mixRes as $key => $value) {
					if(array_key_exists($value['country'], $_tmp)) {
						$_tmp[$value['country']][array_search($value['flg_pixeltype'], $this->_flgPixeltype)] = $value['count'];
					} else {
						$_tmp[$value['country']][array_search($value['flg_pixeltype'], $this->_flgPixeltype)] = $value['count'];
					}
				}
			}
			$mixRes = $_tmp;
				
			Core_Sql::renewalConnectFromCashe();
		} catch (Exception $e) {
			Core_Sql::renewalConnectFromCashe();
		}
		return $this;
	}

	public static function getUserIp(){
		if (!empty($_SERVER["HTTP_CLIENT_IP"])){
			$ip=$_SERVER["HTTP_CLIENT_IP"];
		} elseif (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])){
			$ip=$_SERVER["HTTP_X_FORWARDED_FOR"];
		} else {
			$ip=$_SERVER["REMOTE_ADDR"];
		}
		return $ip;
    }

    public function withSplitIds($_ids){
    	$this->_withSplitIds = $_ids;
    	return $this;
    }

	public function install (){
		Core_Sql::setExec('CREATE TABLE IF NOT EXISTS `lpb_conversionpixel` (
  			`id` int(11) NOT NULL AUTO_INCREMENT,
  			`squeeze_id` int(11) NOT NULL,
  			`flg_pixeltype` int(11) NOT NULL DEFAULT \'0\',
			`ip` varchar(255) NOT NULL DEFAULT \'\',
  			`country_id` int(11) NOT NULL DEFAULT \'0\',
  			`added` int(11) NOT NULL,
  			PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;');
	}

	public function init(){
		parent::init();
		$this->_withSplitIds = false;
	}
}

?>