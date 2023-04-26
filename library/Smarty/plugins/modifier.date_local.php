<?php
/**
 * Smarty additional
 * @category framework
 * @package SmartyAdditional
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2012, Rodion Konnov
 * @author Rodion Konnov <kindzadza@mail.ru>
 * @date 08.12.2011
 * @version 3.0
 */
function smarty_modifier_date_local( $_strDate='', $_strFormat='' ) {
	return Core_Datetime::getInstance()->setFormat( $_strFormat )->setTime( $_strDate )->toLocal();
}
?>