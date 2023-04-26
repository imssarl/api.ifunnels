<?php
/**
 * Smarty additional
 * @category framework
 * @package SmartyAdditional
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2011, Pavel Livinskiy
 * @author Pavel Livinskiy <ikontakts@gmail.com>
 * @date 04.07.2013
 * @version 1.0
 */

function smarty_function_hex2rgb( $_arrPrm, &$objS ) {
	if( empty($_arrPrm['hex']) ){
		return '';
	}
	return Core_Common_Code::hex2rgb( $_arrPrm['hex'] );
}
?>