<?php
class Project_Squeeze_Split extends Core_Data_Storage {

	protected $_table='squeeze_split'; // сплит тест компаний
	protected $_fields=array('id','user_id','flg_closed','flg_duration','duration','title','url','added','edited');
	private $_tableLink='squeeze_campaigns2split';
	private $_winnerId=false;
	private $_link=null;

	public function __construct(){
		$this->_link=new Project_Squeeze_Split_Link();
	}

	public static function install (){
		Core_Sql::setExec('CREATE TABLE IF NOT EXISTS `squeeze_split` (
						  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
						  `user_id` int(11) unsigned NOT NULL DEFAULT \'0\',
						  `flg_closed` int(1) unsigned NOT NULL DEFAULT \'0\',
						  `flg_duration` int(1) unsigned NOT NULL DEFAULT \'0\',
						  `flg_pause` int(11) NOT NULL DEFAULT \'0\',
						  `duration` int(11) unsigned NOT NULL DEFAULT \'0\',
						  `title` varchar(255) NOT NULL DEFAULT \' \',
						  `url` varchar(255) NOT NULL,
						  `edited` int(11) unsigned NOT NULL DEFAULT \'0\',
						  `added` int(11) unsigned NOT NULL DEFAULT \'0\',
						  PRIMARY KEY (`id`)
						) ENGINE=MyISAM  DEFAULT CHARSET=utf8;');

		Core_Sql::setExec('CREATE TABLE IF NOT EXISTS `squeeze_campaigns2split` (
						  `split_id` int(11) unsigned NOT NULL DEFAULT \'0\',
						  `campaign_id` int(11) unsigned NOT NULL DEFAULT \'0\',
						  `url` text NOT NULL,
						  `flg_winner` int(1) unsigned NOT NULL DEFAULT \'0\',
						  `shown` int(11) unsigned NOT NULL DEFAULT \'0\',
						  `clicks` int(11) NOT NULL DEFAULT \'0\',
						  PRIMARY KEY (`split_id`,`campaign_id`)
						) ENGINE=MyISAM DEFAULT CHARSET=utf8;');
	}


	public static function decode( $string ){
		$seed_array = array('S','H','A','F','I','Q');
		$string =  base64_decode($string);
		@list($mix,$letter) = explode("+",$string);
		for($i=0;$i<count($seed_array);$i++){
			if($seed_array[$i] == $letter)
			break;
		}
		for($j=1;$j<=$i;$j++){
			$mix = base64_decode($mix);
		}
		return $mix;
	}


	protected function init(){
		$this->_winnerId=false;
		parent::init();
	}

	public function getList( &$arrRes ){
		$_onlyOne=$this->_onlyOne;
		parent::getList( $arrRes );
		if ( $_onlyOne ){
			$this->_link->withSplitIds( $arrRes['id'] )->getList( $arrRes['arrCom'] );
			return $this;
		}
		foreach( $arrRes as &$_item ){
			$this->_link->withSplitIds( $_item['id'] )->getList( $_item['arrCom'] );
		}
		return $this;
	}

	public function getCampaign( $_splitId ){
		$this->onlyOwner()->getList($_arrSplit);

		foreach ($_arrSplit as $key => $value){
			if($value['flg_duration'] == 2 && ((int)$value['duration'] <= (int)Core_Sql::getCell('SELECT SUM(shown) FROM '.$this->_tableLink.' WHERE split_id='.Core_Sql::fixInjection($value['id'])))){
				Core_Sql::setExec('UPDATE '.$this->_table.' SET flg_closed=1 WHERE id='.Core_Sql::fixInjection($value['id']));
				$_idWinner = Core_Sql::getAssoc('SELECT campaign_id, (clicks/shown) as crt FROM '.$this->_tableLink.' WHERE split_id='.Core_Sql::fixInjection($value['id']));
				$_max = $_idWinner[0];
				foreach ($_idWinner as $k => $v){
					if(floatval($_max['crt']) < floatval($v['crt'])){
						$_max = $_idWinner[$k];
					}
				}
				if(floatval($_max['crt']) > 0){
					Core_Sql::setExec('UPDATE '.$this->_tableLink.' SET flg_winner=1 WHERE split_id='.Core_Sql::fixInjection($value['id']).' AND campaign_id='.Core_Sql::fixInjection($_max['campaign_id']));
				}
			}
			if($value['flg_duration'] == 1 && ($value['added'] + $value['duration']*60*60*24) <= time()){
				Core_Sql::setExec('UPDATE '.$this->_table.' SET flg_closed=1 WHERE id='.Core_Sql::fixInjection($value['id']));
				$_idWinner = Core_Sql::getAssoc('SELECT campaign_id, (clicks/shown) as crt FROM '.$this->_tableLink.' WHERE split_id='.Core_Sql::fixInjection($value['id']));
				$_max = $_idWinner[0];
				foreach ($_idWinner as $k => $v){
					if(floatval($_max['crt']) < floatval($v['crt'])){
						$_max = $_idWinner[$k];
					}
				}
				if(!empty($_max) && floatval($_max['crt']) > 0){
					Core_Sql::setExec('UPDATE '.$this->_tableLink.' SET flg_winner=1 WHERE split_id='.Core_Sql::fixInjection($value['id']).' AND campaign_id='.Core_Sql::fixInjection($_max['campaign_id']));
				}
			}
		}
	}

	public static function viewCampaign($_arr){
		$_view_all = 0;
		foreach ($_arr as $key => $value){
			$_view_all += (int)$value['shown'];
		}
		$_koef = array();
		foreach ($_arr as $key => $value){
			$_koef[] = (int)(($_view_all - (int)$value['shown']) * 100 / $_view_all);
		}
		$_koef_sum = array_sum ($_koef);
		$r = rand(0, $_koef_sum);
		
		if($r <= $_koef[0]){
			$url = $_arr[0]['url'];
			$splittest = $_arr[0]['split_id'];
			return $url .'?splt='.$splittest;
		} else {
			$_tmp = $_koef[0];
			for($i = 1; $i < count($_koef); $i++){
				$_tmp += $_koef[$i];
				if($r <= $_tmp){
					$url = $_arr[$i]['url'];
					$splittest = $_arr[$i]['split_id'];
					return $url .'?splt='.$splittest;
				}
			}
		}
	}
}
?>