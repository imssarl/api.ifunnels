<?php
class Project_Publisher_Adapter_Factory {

	public static function get( $intType ) {
		if( empty( $intType ) ){
			return false;
		}
		switch( $intType ) {
			case Project_Sites::BF: return Project_Publisher_Adapter_Blogfusion::getInstance(); break;
			case Project_Sites::NCSB: return Project_Publisher_Adapter_Ncsb::getInstance(); break;
			case Project_Sites::NVSB: return Project_Publisher_Adapter_Nvsb::getInstance(); break;
			default: return false; break;
		}
	}
}
?>