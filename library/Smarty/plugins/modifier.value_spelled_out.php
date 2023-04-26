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


function smarty_modifier_value_spelled_out( $strString, $_withoutKop=true ) {
	return Core_String::getInstance( $strString )->valueSpelledOut( $_withoutKop );
}
?>
