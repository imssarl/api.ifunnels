<?php
/**
 * Smarty additional
 *
 * преобразование пременных в json формат из шаблона
 *
 * @category framework
 * @package SmartyAdditional
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2011, Rodion Konnov
 * @author Rodion Konnov <kindzadza@mail.ru>
 * @date 18.03.2011
 * @version 1.0
 */


function smarty_modifier_json( $mix ) {
	return Zend_Registry::get( 'CachedCoreString' )->php2json( $mix );
}
?>