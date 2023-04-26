<?php


/**
 * publisher adapter interface
 */
interface Project_Publisher_Adapter_Interface {

	public function setProject( $_arr=array() );

	public function setSourceType( $_intId=0 );

	public function getLink( $arr );
}
?>