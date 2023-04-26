<?php
class Project_Pagebuilder_Category_Blocks extends Core_Data_Storage{
	protected $_table='pb_blocks_categories';
	protected $_fields=array( 'id', 'category_name', 'list_order' );

	public function getList(&$mixRes){
		parent::getList( $mixRes );
	}
}