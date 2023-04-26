<?php


/**
 * News administration
 */

class Project_News extends Core_Data_Storage {

	protected $_fields=array(
		'id', 'title', 'meta', 'description', 'added', 'flg_archive', 'user_id','edited'
	);
	protected $_table='content_news';

	private $_link='content_news_link';

	protected function beforeSet(){
		if ( !Core_Data_Errors::getInstance()->setData( $this->_data->setFilter('trim','clear') )->setValidators( array(
			'title'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' ),
			'description'=>Core_Data_Errors::getInstance()->getValidator( 'Zend_Validate_NotEmpty' )
		) )->isValid() ) {
			return Core_Data_Errors::getInstance()->setError('Incorrect entered data.');
		}
		return true;
	}

	protected function afterSet(){
		$this->addLinks( $this->_data->filtered['id'], $this->_data->filtered['groups'] );
		return true;
	}

	public function addLinks($_newsId,$_arrGroups){
		if( empty($_newsId) ){
			return false;
		}
		$this->deleteLink( $_newsId );
		if( empty($_arrGroups) ){
			return false;
		}
		foreach( $_arrGroups as $_groupId ){
			$_arrLink[]=array('group_id'=>$_groupId,'news_id'=>$_newsId);
		}
		Core_Sql::setMassInsert( $this->_link, $_arrLink );
		return true;
	}

	public function deleteLink( $_newsIds ){
		return Core_Sql::setExec('DELETE FROM '.$this->_link.' WHERE news_id IN ('.Core_Sql::fixInjection($_newsIds).')');
	}

	public function del(){
		$this->deleteLink( $this->_withIds );
		parent::del();
	}

	private $_checkGroups=false;

	public function checkGroups(){
		$this->_checkGroups=true;
		return $this;
	}

	protected function assemblyQuery(){
		parent::assemblyQuery();
		if( $this->_checkGroups && !empty( Core_Users::$info['groups'] ) ){
			$this->_crawler->set_where('d.id IN (SELECT news_id FROM '. $this->_link.' WHERE group_id IN('. Core_Sql::fixInjection( array_keys( Core_Users::$info['groups'] ) ) .'))');
		}
	}

	public function getList( &$arrMix ){
		parent::getList( $arrMix );
		if(empty($arrMix)){
			return $this;
		}
		if( is_array($arrMix[0]) ){
			foreach( $arrMix as &$_item ){
				$_item['groups']=Core_Sql::getField('SELECT group_id FROM '.$this->_link.' WHERE news_id='.$_item['id']);
			}
		} else {
			$arrMix['groups']=Core_Sql::getField('SELECT group_id FROM '.$this->_link.' WHERE news_id='.$arrMix['id']);
		}
		return $this;
	}
}
?>