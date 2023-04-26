<?php
class Project_Pagebuilder_Category_Template extends Core_Data_Storage{
	protected $_table='pb_templates_categories';
	protected $_fields=array( 'id', 'category_name', 'edited', 'added' );

	public function getList(&$mixRes){
		parent::getList( $mixRes );
	}
}