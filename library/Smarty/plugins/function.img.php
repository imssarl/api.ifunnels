<?php
/**
 * Smarty additional
 * @category framework
 * @package SmartyAdditional
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2011, Rodion Konnov
 * @author Rodion Konnov <kindzadza@mail.ru>
 * @date 16.09.2011
 * @version 3.0
 */


function smarty_function_img( $_arrPrm, &$objS ) {
	if( !empty($_arrPrm['geturl']) ){
		return ((stripos($_SERVER['SERVER_PROTOCOL'],'https'))?'https://':'http://').$_SERVER['HTTP_HOST'].ltrim($_arrPrm['src'],'.');
	}
	return Core_Files_Image_Thumbnail::generate( $_arrPrm );
}
?>