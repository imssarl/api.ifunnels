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


function smarty_function_module( $_arrPrm, &$objS ) {
	Core_Module::startModule( $_arrPrm )->run();
}
?>