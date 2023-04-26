<?php
/**
 * WorkHorse Framework
 *
 * @category WorkHorse
 * @package Core_Module
 * @license http://opensource.org/licenses/ MIT License
 * @date 24.02.2010
 * @version 6.5
 */


/**
 * Site starter
 *
 * @category WorkHorse
 * @package Core_Module
 * @license http://opensource.org/licenses/ MIT License
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

/**
 * т.к. старый код кривой Magic Quotes влючены иначе он будет безбожно глючить
 * код движка работает с отключенными Magic Quotes посему программно чистим
 * массивы от них.
 *
 * @return void
 */
if (get_magic_quotes_gpc()) {
	function magicQuotes_awStripslashes(&$value, $key) {$value = stripslashes($value);}
	$gpc = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST);
	array_walk_recursive($gpc, 'magicQuotes_awStripslashes');
}
 if( isset($_REQUEST['function']) && $_REQUEST['function'] == 'call_user_func_array' ){
	header( 'HTTP/1.0 404 Not Found' );
	exit;
}
header( "HTTP/1.0: 200 OK\n" );
if (version_compare(phpversion(), '5.2.0', '<')===true) {
	echo '<div style="margin:80px 40px 70px 200px;">Whoops, it looks like you have an invalid PHP version. Wh supports PHP 5.2.0 or newer.</div>';
	exit;
}
require_once 'inc_config.php'; // set defined params - depercated!!!
//$_t1=xdebug_time_index();
require_once './library/WorkHorse.php'; // starter
WorkHorse::run();
//$_t2=xdebug_time_index();
//echo $_t2-$_t1;
?>