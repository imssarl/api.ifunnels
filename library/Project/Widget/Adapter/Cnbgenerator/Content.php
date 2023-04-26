<?php

class Project_Widget_Adapter_Cnbgenerator_Content  extends Core_Data_Storage {

	public $_table='content_widget';
	public $_fields=array('id','primary_keyword','keywords_list','added','edited','sort_by');

	private function getOwnerId( &$intRes ) {
		return false;
	}

	public function beforeSet(){
		if ( !$this->_data->setFilter( array( 'trim', 'clear' ) )->setChecker( array(
			'primary_keyword'=>empty( $this->_data->filtered['primary_keyword'] ),
			'keywords_list'=>empty( $this->_data->filtered['keywords_list'] ),
		) )->check() ) {
			$this->_data->getErrors( $this->_errors );
			return false;
		}
		return $this;
	}

	protected function assemblyQuery() {
		parent::assemblyQuery();
		if ( $this->_toSelect ) {
			$this->_crawler->set_select( 'd.id, d.primary_keyword' );
		}
	}
}
?>