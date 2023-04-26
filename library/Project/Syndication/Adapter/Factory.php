<?php
class Project_Syndication_Adapter_Factory {

	public static function get( $intType ) {
		if( empty( $intType ) ){
			return false;
		}
		switch( $intType ){
			case Project_Sites::BF: return Project_Syndication_Adapter_Blogfusion::getInstance(); break;
			case Project_Sites::NCSB: return Project_Syndication_Adapter_Ncsb::getInstance(); break;
			case Project_Sites::NVSB: return Project_Syndication_Adapter_Nvsb::getInstance(); break;
			default: return false; break;
		}
	}

	public static function getLastUrls() {
		$_arrUrls=array_merge(
			Project_Syndication_Adapter_Blogfusion::getLastUrls(),
			Project_Syndication_Adapter_Ncsb::getLastUrls(),
			Project_Syndication_Adapter_Nvsb::getLastUrls()
		);
		return $_arrUrls;
	}
}
?>