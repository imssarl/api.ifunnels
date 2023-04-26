<?php


/**
 * Категории Embed видео
 */
class Project_Embed_Category extends Core_Category {

	public function __construct() {
		parent::__construct( 'Embed Manager' );
	}

	public function set( &$arrRes, &$arrErr, $_arrData=array() ) {
		$_arrIds=array();
		foreach( $_arrData as $v ) {
			if ( !empty( $v['del'] ) ) {
				$_arrIds[]=$v['id'];
			}
		}
		if ( !empty( $_arrIds ) ) { // удаляем видео из категорий
			$_embed=new Project_Embed();
			$_embed->del( $_arrIds );
		}
		return parent::set( $arrRes, $arrErr, $_arrData );
	}

	public function withPagging( $_arr=array() ) {
		parent::withPagging( $_arr );
		return $this;
	}

	// + подсчёт видео в каждой категории
	public function management( &$arrRes, &$arrPg ) {
		if ( !$this->get( $arrRes, $arrPg ) ) {
			return false;
		}
		$_embed=new Project_Embed();
		foreach( $arrRes as $k=>$v ) {
			$_embed
				->withCategory( $v['id'] )->onlyCount()->getList( $arrRes[$k]['count'] )
				->withCategory( $v['id'] )->toSelect()->getList( $arrRes[$k]['items'] );
		}
		return true;
	}
}
?>