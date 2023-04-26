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


function smarty_modifier_ellipsis( $strString, $_intNeedLength=80, $_flgMode='middle', $_strSym=" ... " ) {
	return Core_String::getInstance( $strString )->ellipsis( $_intNeedLength, $_flgMode, $_strSym );
}
?>