<?php
/**
 * WorkHorse Framework
 *
 * @category Project
 * @package Project_Widget_Adapter
 * @copyright Copyright (c) 2009-2011, web2innovation
 * @author Pavel Livinskij <pavel.livinskij@gmail.com>
 * @date 12.07.2011
 * @version 1.0
 */


/**
 * Управление частями сниппета
 *
 * @category Project
 * @package Project_Widget_Adapter
 * @copyright Copyright (c) 2009-2011, web2innovation
 */

class Project_Widget_Adapter_Copt implements Project_Widget_Adapter_Interface {

	private $_settings=array();
	private $_part=false;
	
	public function get(){
		if( empty($this->_settings['id']) ){
			return false;
		}
		$_arrId = explode('-',$this->_settings['id']);
		Project_Widget_Mutator::decodeArray( $_arrId );
		foreach($_arrId as $_id ){
			if( empty($_id) ){
				continue;
			}
			$this->_settings['id']=$_id;
			$this->_part=new Project_Widget_Adapter_Copt_Parts();
			$this->getPart( $strPart );
			$_arrPart[]=preg_replace( '@\s@',' ',$strPart);
		}
		if( empty($_arrPart) ){
			die();
		}
		// TODO удалить после того как будет отключен старый сервис.
 		if( $this->_settings['old'] ){
			$this->statOldServices();
			echo htmlspecialchars_decode(join(" ",$_arrPart));
			die();
		}
		echo "document.write('".htmlspecialchars_decode( str_replace("'",'`',join(" ",$_arrPart)) )."');";
		die();
	}

	/**
	 * Временный метод, для отслеживания статистики использования старого сервиса.
	 * Удалить после отключения старого сервиса.
	 * @return void
	 */
	public function statOldServices(){
		$_host='-//-';
		$_path='-//-';
		if(!empty($_SERVER['HTTP_REFERER'])){
			$_tmp=parse_url($_SERVER['HTTP_REFERER']);
			$_host=$_tmp['host'];
			$_path=$_tmp['path'];
		}
		$_ip=$_SERVER['REMOTE_ADDR'];
		Core_Sql::setExec('INSERT INTO stat_old_services ( flg_type, `count`,ip, host ,remote_url, added ) VALUES (1,1,\''.$_ip.'\','.Core_Sql::fixInjection($_host).','.Core_Sql::fixInjection($_path).','.time().') ON DUPLICATE KEY UPDATE `count`=`count`+1,remote_url='.Core_Sql::fixInjection($_path));
	}
	
	public function set(){
		if( empty($this->_settings['id']) ){
			return false;
		}
		$_tmp=explode('-',$this->_settings['id']);
		if( empty($_tmp[0]) || empty($_tmp[1]) ){
			return false;
		}
		$_partId=Project_Widget_Mutator::decode($_tmp[0]);
		$_trackId=Project_Widget_Mutator::decode($_tmp[1]);
		$_part=new Project_Widget_Adapter_Copt_Parts();
		$_arrUrl=$_part->getTrackUrl( $_partId, $_trackId );
		$_part->setData( array(
			'part_id'	 => $_partId,
			'trackurl_id'=> $_trackId,
			'ip_address' => $_SERVER['REMOTE_ADDR'],
			'url_shown'	 => (empty($_SERVER['HTTP_REFERER']))?'':$_SERVER['HTTP_REFERER'],
			'added'		 => time()
		))->setClick();
		header( 'Location: '.$_arrUrl['url'] );
	}


	public function checkKey( $_strKey ){
		return true;
	}

	public function setSettings( $_arrSettings ){
		$this->_settings=$_arrSettings;
		return $this;
	}

	/**
	 * Выбирает одну часть из сниппета для показа на удленном сервере.
	 *
	 * Два вырианта показа:
	 * 1- С Intelligent link tracking
	 * 2- без ILT
	 *
	 * @param  $arrPart
	 * @return void
	 */
	private function getPart( &$strPart ){
		// Определяем по какому из вариантов будем показывать части.
		$_snippets=Project_Widget_Adapter_Copt_Snippets::getInstance();
		$_arrSnippet=array();
		$_tmpArr=array();
		if( !$_snippets->withRights(array('services_@_widgets_copt_snippets'))->onlyOne()->withIds( $this->_settings['id'] )->getList( $_arrSnippet )->checkEmpty() ){
			return;
		}
		if( $_snippets->withIds( $this->_settings['id'] )->getStatistic( $_tmpArr ) && $_arrSnippet['flg_enabled']==1 ){ // По частям сниппета уже кликали.
			$strPart=$this->getFirst();
			return;
		}
		$strPart=$this->getSecond();
		return;
	}

	/**
	 * Выбирает часть сниппета по первому варианту.
	 * @return bool
	 */
	private function getFirst(){
		$arrRes=array();
		if( !$this->_part->withIp($_SERVER['REMOTE_ADDR'])->setLimit( 3 )->withoutPause()->withOrder('ctr--up')->onlySnippet( $this->_settings['id'] )->getList( $arrRes )->checkEmpty() ){
			return false;
		}
		$arrRes=$this->getILTBalancing( $arrRes );
		$this->_part->setViews( $arrRes['id'] );
		return ($arrRes['snippet_id']==991)?$arrRes['content']:$arrRes['parsed'];
	}

	/**
	 * Выбирает часть сниппета по второму варианту.
	 * @return bool
	 */
	public function getSecond(){
		$arrRes=array();
		if( !$this->_part->withIp($_SERVER['REMOTE_ADDR'])->withoutPause()->withOrder('last_view--dn')->onlySnippet( $this->_settings['id'] )->getList( $arrRes )->checkEmpty() ){
			return false;
		}
		$arrRes=array_shift($arrRes);
		$this->_part->setViews( $arrRes['id'] );
		return ($arrRes['snippet_id']==991)?$arrRes['content']:$arrRes['parsed'];
	}

	/**
	 * Intelligent link tracking
	 * Если по одной части набора кликнули то для выборки части используется этот метод.
	 * Выбор идёт на основе пропорции (4:2:1) и количеству кликов. Части которые больше кликают - чаще показываются
	 * @param $arrParts
	 * @return mixed
	 */
	private function getILTBalancing( $arrParts ){
		$_count=count($arrParts);
		$_rank1=4;
		$_rank2=2;
		$_rank3=1;
		if( $_count== 1 ){
			return array_shift($arrParts);
		}
		if( $_count == 2 ){
			if( ($arrParts[0]['views'] >= ($arrParts[1]['views']*($_rank1/$_rank2))) || ($arrParts[0]['ctr']==$arrParts[1]['ctr']) ){
				if( rand(0,1) == 1 ){
					return $this->getRandomInLowers( $arrParts );
				}
				if( $arrParts[0]['view'] >= ceil($_rank1/$_rank2) ){
					$this->_part->resetView($arrParts[0]['id']);
					return $arrParts[ rand(0,1) ];
				}
			}
			return $arrParts[0];
		}
		if( $_count == 3 ){
			if(	( ( $arrParts[0]['ctr']==$arrParts[1]['ctr']&&$arrParts[1]['ctr']==$arrParts[2]['ctr'] ) ||
				( $arrParts[0]['ctr']==$arrParts[1]['ctr'] ) ||
				( $arrParts[1]['ctr']==$arrParts[2]['ctr'] ) ) && ( rand(0,1) == 1 ) ){
				return $this->getRandomInLowers( $arrParts );
			}
		}
		if( $arrParts[0]['views'] >= $arrParts[2]['views']*$_rank1 ){
			if( $arrParts[0]['views'] >= $arrParts[1]['views']*($_rank1/$_rank2) ){
				if( $arrParts[1]['views'] >= $arrParts[2]['view']*$_rank2 ){
					if( (   $arrParts[0]['views']==($arrParts[1]['views']*($_rank1/$_rank2)) &&
							$arrParts[0]['views']==($arrParts[2]['views']*$_rank1) &&
							rand(0,1)==1 ) || $arrParts[2]['view'] >= $_rank3 ){
						return $this->getRandomInLowers($arrParts);
					}
					if($arrParts[2]['view'] == $_rank3){
						$this->_part->resetView($arrParts[2]['id']);
						return $this->getRandomInLowers($arrParts);
					}
					return $arrParts[2];
				}
				return $arrParts[1];
			}
			if( $arrParts[0]['view'] == $_rank1 ){
				$this->_part->resetView($arrParts[0]['id']);
				return $arrParts[rand(0,1)*2];
			}
			return $arrParts[0];
		} else {
			if( $arrParts[0]['views'] >= $arrParts[1]['views']*($_rank1/$_rank2) ){
				if( $arrParts[0]['view'] == ceil($_rank1/$_rank2) ){
					$this->_part->resetView($arrParts[0]['id']);
					return $arrParts[rand(0,1)];
				}
			} elseif ( $arrParts[0]['view'] == $_rank1 ){
				$this->_part->resetView($arrParts[0]['id']);
				return $this->getRandomInLowers($arrParts);
			}
		}
		return $arrParts[0];
	}

	/**
	 * Выбор рандомом одной части из списка
	 * @param $_arrParts
	 * @return mixed
	 */
	private function getRandomInLowers( $_arrParts ){
		$_count=array();
		$arrRes=array();
		$this->_part->withIp($_SERVER['REMOTE_ADDR'])->onlySnippet($_arrParts[0]['snippet_id'])->onlyOne()->onlyCount()->getList( $_count );
		foreach( $_arrParts as $v ){
			$_arrIds[]=$v['id'];
			$_snippetId=$v['snippet_id'];
		}
		if( $_count > 3 ){
			$this->_part->withoutIds( $_arrIds );
		}
		$this->_part->withIp($_SERVER['REMOTE_ADDR'])->onlyParsed()->onlySnippet( $_snippetId )->getList( $arrRes );
		return $arrRes[array_rand($arrRes,1)];
	}

}
?>