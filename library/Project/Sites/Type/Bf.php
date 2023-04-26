<?php

 /**
 * Wpress (BlogFusion) сайты
 * объединить с Project_Wpress TODO!!! 13.10.2010
 */
class Project_Sites_Type_Bf extends Project_Sites_Type_Abstract {
	protected $_table='bf_blogs';

	public function set( Project_Sites $object ) {}
	public function get( &$arrRes, $_arrSite=array() ) {}
	public function del( $_arrIds ) {}
	public function prepareSource() {}
	public function import( Project_Sites $object ) {}
}
?>