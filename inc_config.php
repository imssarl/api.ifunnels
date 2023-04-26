<?php
/**
 * WorkHorse Framework
 *
 * @category WorkHorse
 * @package Core_Module
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2005-2010, Rodion Konnov
 * @author Rodion Konnov <kindzadza@mail.ru>
 * @date 24.02.2010
 * @version 6.5
 */


/**
 * Depercated config style - use config.php instead!!!
 *
 * @category WorkHorse
 * @package Core_Module
 * @copyright Copyright (c) 2005-2010, Rodion Konnov
 * @license http://opensource.org/licenses/ MIT License
 */


// Other framework const
deFine( 'PROJECT_DOMAIN', @$_SERVER['HTTP_HOST'] );
deFine( 'MULTI_LANG', 0 );

// Dirs constant. Script (DIR_ prefix) and Html (HTML_ prefix) variants
deFine( 'DIR_ROOT', './' );
deFine( 'HTML_ROOT', '/' );
$_arrDirs=array(
	'COMPILED'=>'compiled/',
	'DB_BACKUP'=>'db_backup/',
	'IMG'=>'i/',
	'JOBB'=>'jobb/',
	'LIBRARY'=>'library/',
	'CORE'=>'library/core/',
	'ZEND'=>'library/Zend/',
	'SMARTY'=>'library/Smarty/',
	'SOURCE'=>'source/',
	'USERS'=>'usersdata/',
	'LETTER_IMG'=>'i/letter/',
);
foreach( $_arrDirs as $k=>$v ) {
	deFine( 'DIR_'.$k, DIR_ROOT.$v );
	deFine( 'HTML_'.$k, HTML_ROOT.$v );
}
deFine( 'DIR_DBFS', DIR_USERS.'fsfiles/' );
deFine( 'DIR_MEDIA', DIR_USERS.'fsfiles/' );

// Debugging
deFine( 'DEBUG_MODE', 2 ); // 0-отсылаем письмо, 1-и в браузер полную инфу, 2-каммент с именем шаблона, 3-хэш каждого шаблона с коменнтом
deFine( 'ERR_MYSQL', 1 );
deFine( 'ERR_PHP', 2 );
deFine( 'ERR_FILESYS', 3 );
deFine( 'ERR_SMARTY', 4 );
deFine( 'ERR_SOAP', 5 );

// media_converter dir's
deFine( 'DIR_MENCODER', '/usr/bin/' );
deFine( 'DIR_LOGFILES', DIR_USERS.'log_files/' );
deFine( 'DIR_MCTEMP', DIR_USERS.'mc_tmp/' );
deFine( 'DIR_MCPIDS', DIR_USERS.'mc_pids/' );
deFine( 'M_TUMB_NOIMAGE', DIR_IMG.'0.gif' );
deFine( 'M_MENCODER_VER', 23466 ); //начиная с r23982 убрали -lavfopts i_certify_that_my_video_stream_does_not_use_b_frames
?>