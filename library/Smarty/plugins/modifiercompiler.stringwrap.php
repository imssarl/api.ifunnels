<?php
/**
 * Smarty plugin
 */

/**
 * Smarty string modifier plugin
 * 
 * Type:     modifier<br>
 * Name:     stringwrap<br>
 * Purpose:  wrap a string of text at a given length
 *
 * @param array $params parameters
 * @return string with compiled code
 */
function smarty_modifiercompiler_stringwrap( $params ){
    if (!isset($params[1])) {
        $params[1] = 80;
    }
    if (!isset($params[2])) {
        $params[2] = '"<br>"';
    }
    return 'implode( '.$params[2].', str_split('.$params[0].',  '.$params[1].' ) )';
} 

?>