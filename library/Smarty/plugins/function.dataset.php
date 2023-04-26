<?php
/**
 * Smarty additional
 * @category framework
 * @package SmartyAdditional
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2010, Rodion Konnov
 * @author Rodion Konnov <kindzadza@mail.ru>
 * @date 11.10.2010
 * @version 2.0
 */


/**
 * Core_Dataset_Object auto form generator
 * @category framework
 * @package SmartyAdditional
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2010, Rodion Konnov
 * @author Rodion Konnov <kindzadza@mail.ru>
 * @date 18.01.2010
 * @version 1.0
 */


function smarty_function_dataset( $_arrPrm, &$objS ) {
	if ( empty( $_arrPrm['code'] ) ) {
		return 'No data_code setted';
	}
	$_object=Core_Dataset_Object::singleton( $_arrPrm['code'] );
	if ( !$_object->checkSetExists() ) {
		return 'No dataset exists with "'.$_arrPrm['code'].'" data_code';
	}
	$_arrPrm['arrFields']=$_object->getOnlyFields();
	$_arrPrm['arrDataset']=$_object->getOnlyData();
	return Core_View::factory( Core_View::$type['one'] )
		->setTemplate( Zend_Registry::get( 'config' )->path->relative->source.'dataset'.DIRECTORY_SEPARATOR.'fields'.DIRECTORY_SEPARATOR.'form_'.Core_Module_Router::$curSiteName.'.tpl' )
		->setHash( $_arrPrm )
		->parse()
		->getResult();
}
?>