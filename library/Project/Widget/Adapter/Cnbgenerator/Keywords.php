<?php
class Project_Widget_Adapter_Cnbgenerator_Keywords extends Core_Data_Storage {

	protected $_table='xedant_keywords_english245';
	protected $_counter=0;
	protected $_limit=false;
	
	private $_sphinxFields=array(
		'google_adwords_cost_per_click_in_cents'=>'Google Adwords cost per click (in cents)',
		'google_searches'=>'Google searches',
		'adwords_competition_rate'=>'Adwords Competition rate',
		'google_adwords_cost_per_month'=>'Google Adwords cost per month',
		'google_search_results'=>'Google search results',
		'google_search_results_phrase_match'=>'Google search results (phrase match)',
		'google_search_results_with_allinanchor'=>'Google search results with allinanchor',
		'google_search_results_with_allinurl'=>'Google search results with allinurl',
		'google_search_results_with_allintext'=>'Google search results with allintext',
		'google_search_results_with_allintitle'=>'Google search results with allintitle',
	);

	private $_sphinxOrder=array(
		'desc'=>'from highest to lowest',
		'asc'=>'from lowest to highest',
	);

	public function getSphinxOrder(){
		return $this->_sphinxOrder;
	}

	public function getSphinxFields(){
		return $this->_sphinxFields;
	}

	private function setOrdering() {
		if ( empty( $this->_data->filtered['ordering'] ) ) {
			return;
		}
		$this->_withOrder=array();
		foreach( $this->_data->filtered['ordering'] as $k=>$v ) {
			if ( $k!='wordInKeyword' && $k!='keywordNeed' ){
				$this->_crawler->no_bracket()->set_where( $k.'!=-1' );
				$this->_withOrder[]=$k.'--'.($v=='desc'?'up':'dn');
			}
		}
		return;
	}
 
	protected function assemblyQuery() {
		$this->_crawler->set_select( 'id' );
		$this->_crawler->set_from( $this->_table );
		$this->_crawler->no_bracket()->set_where( "MATCH(".Core_Sql::fixInjection( $this->_data->filtered['keyword'] ).")" );
		$this->setOrdering();
		if ( !( $this->_onlyOne||$this->_onlyCell ) ) {
			$this->_crawler->set_order_sort( $this->_withOrder );
		}
		$this->_crawler->set_limit( $this->_counter.','.$this->_limit );
	}

	public function getList( &$mixRes ) {
		Core_Sql::setConnectToServer( 'sphinx.search' );
		parent::getList( $mixRes );
		Core_Sql::renewalConnectFromCashe();
		return $this;
	}

	private function getListKeywords() {
		if ( !$this->onlyIds()->getList( $_arrIds )->checkEmpty() ) {
			return false;
		}
		Core_Sql::setConnectToServer( 'articles.db' );
		$_arrKeywords=Core_Sql::getKeyVal( 'SELECT id, keyword FROM '.$this->_table.' WHERE id IN('.Core_Sql::fixInjection( $_arrIds ).')' );
		Core_Sql::renewalConnectFromCashe();
		foreach( $_arrIds as $v ) {
			if ( $this->_data->filtered['ordering']['wordInKeyword']=='0' || str_word_count( $_arrKeywords[$v] )<=$this->_data->filtered['ordering']['wordInKeyword'] ){
				$this->_keywords[]=$_arrKeywords[$v];
			}
		}
		return !empty( $_arrKeywords );
	}

	private $_keywords=array();
	
	private $_maxIteration=10;

	public function getKeywords( &$arrRes ) {
		$this->_data->getFiltered();
		$i=0;
		while( true ) {
			$this->_limit=$i*$this->_data->filtered['ordering']['keywordNeed']+$this->_data->filtered['ordering']['keywordNeed'];
			$this->_counter=$i*$this->_data->filtered['ordering']['keywordNeed'];
			$i++;
			if ( !$this->getListKeywords() ) {
				break;
			}
			if ( count( $this->_keywords )>=$this->_data->filtered['ordering']['keywordNeed'] ) {
				break;
			}
		}
		$arrRes = array_slice($this->_keywords, 0, $this->_data->filtered['ordering']['keywordNeed']);
	}
}