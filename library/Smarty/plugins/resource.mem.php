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


function smarty_resource_mem_source( $_strKey, &$strResult, &$objS ) {
	if ( !empty( $objS->resource['mem'][$_strKey] ) ) {
		$strResult=$objS->resource['mem'][$_strKey];
		return true;
	} else {
		return false;
	}
}

function smarty_resource_mem_timestamp( $_strTplName, &$intTime, &$objS ) {
	$intTime=time();
	return true;
}

function smarty_resource_mem_secure( $_strTplName, &$objS ) {
	return true;
}

function smarty_resource_mem_trusted( $_strTplName, &$objS ) {}
?>