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
 * Stensil item simple form generator
 * @category framework
 * @package SmartyAdditional
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2008, Rodion Konnov
 * @author Rodion Konnov <kindzadza@mail.ru>
 * @date 25.11.2008
 * @version 1.0
 */


function smarty_function_form( $_arrPrm, &$objS ) {
	if ( empty( $_arrPrm['stencil'] ) ) {
		return 'No stencil selected';
	}
	$_objSt=new Core_Items_Stencil( $_arrPrm['stencil'] );
	if ( !$_objSt->get_current_stencil( $_arrPrm['arrStencil'] ) ) {
		return 'Stencil "'.$_arrPrm['stencil'].'" not exists';
	}
	$_arrPrm['arrTypes']=$_objSt->field_types;
	Core_Parsers_Smarty::getInstance()->template( $strRes, $_arrPrm, Zend_Registry::get( 'config' )->path->relative->source.'stencil_form/stencil_form.tpl' );
	return $strRes;
}
?>