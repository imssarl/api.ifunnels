<?php
interface Project_Sites_Adapter_Interface {

	public function getErrors( &$arrErrors );
	public function setUser( $_int );
	public function setContent( $data );
	public function getContent();
	public function setSite( $_intId=0 );
	public function get( &$arrRes, $_arrSite=array() );
	public function import( Project_Sites $object );
	public function set( Project_Sites $object );
	public function upload();
	public function deleteContent();
	public function deleteSites( $_arrIds );
}
?>