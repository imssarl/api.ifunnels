<?php
/**
 * Smarty additional
 * @category framework
 * @package SmartyAdditional
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2010, Pavel Livinskiy
 * @author Rodion Konnov <ikontakts@gmail.com>
 * @date 04.03.2013
 * @version 1.0
 */


function smarty_function_is_acs_write() {
	if( Core_Acs::haveWrite() ){
		return '';
	}
	return 'onclick="this.removeEvents(); r.alert(\'Access denied\',\'You have only read rights\',\'roar_error\');  return false;"';
}
?>