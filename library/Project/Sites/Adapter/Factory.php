<?php
class Project_Sites_Adapter_Factory {

	public static function get( $intType ) {
		if( empty( $intType ) ){
			return false;
		}
		switch( $intType ) {
			case Project_Sites::BF: return Project_Sites_Adapter_Blogfusion::getInstance(); break;
			case Project_Sites::NCSB: return Project_Sites_Adapter_Ncsb::getInstance(); break;
			case Project_Sites::NVSB: return Project_Sites_Adapter_Nvsb::getInstance(); break;
			case Project_Sites::NCSB_DOWNLOAD: return Project_Sites_Adapter_Ncsb_Download::getInstance(); break;
			default: return false; break;
		}
	}
}
?>