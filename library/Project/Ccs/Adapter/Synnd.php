<?php

/**
 * Создание кампаний для SYNND
 */
class Project_Ccs_Adapter_Synnd {

	/**
	 * Object Core_Data
	 * @var Core_Data object
	 */
	private $_data=false;

	/**
	 * category: Shopping and Product Reviews
	 * @var int
	 */
	private $_category=15845632;

	private $_promoteCount=50;

	private $_promoteTypes=3;

	private $_flgType=1;

	public function setEntered( Core_Data $_data ){
		$this->_data=$_data;
		return $this;
	}

	public function run(){
		$arrCampaign=array(
			'settings'=>array(
				'url'=>$this->_data->filtered['url'],
				'title'=>$this->getTitle($this->_data->filtered['main_keyword']),
				'tags'=>$this->getTags($this->_data->filtered['main_keyword']),
				'description'=>$this->getDescription($this->_data->filtered['main_keyword']),
				'category_id'=>$this->_category,
				'promoteCount'=>array('3'=>$this->_promoteCount),
				'promoteTypes'=>array('3'=>$this->_promoteTypes),
			),
			'flg_type'=>$this->_flgType
		);
		$_synnd=new Project_Synnd();
		return $_synnd->setEntered( $arrCampaign )->set();
	}

	private function getTitle( $_strKeyword ){
		if(str_word_count($_strKeyword)==1){
			$_strKeyword ='Popular '.$_strKeyword.' Product Reviews';
		} elseif( str_word_count($_strKeyword)>1&&str_word_count($_strKeyword)<4 ){
			$_arr=array(
				'#keyword# Product Reviews',
				'Recommended #keyword# Products'
			);
			$_template=$_arr[array_rand($_arr)];
			$_strKeyword=str_replace('#keyword#',$_strKeyword,$_template);
		}
		return ucwords($_strKeyword);
	}

	private function getTags( $_strKeyword ){
		$_strKeyword=strtolower($_strKeyword);
		$_strKeyword=trim(preg_replace('@\s[a-z|0-9]{0,2}\s@si',' ',' '.$_strKeyword.' '));
		$_arrWords=explode(' ', $_strKeyword);
		if(count($_arrWords)>3){
			$_arrWords=array_slice($_arrWords,0,3);
			$_strKeyword=implode(' ',$_arrWords);
		}
		if( count($_arrWords)>1 ){
			$_str=join(', ',$_arrWords).', ';
		}
		$_str.=$_strKeyword.', ';
		if(count($_arrWords)>2){
			$_arrWords=array_slice($_arrWords,0,2);
			$_strKeyword=implode(' ',$_arrWords);
		}
		$_str.=$_strKeyword.' store';
		return $_str;
	}

	private function getDescription( $_strKeyword ){
		return "We've listed the best and most popular {$_strKeyword} for your pleasure";
	}
}
?>