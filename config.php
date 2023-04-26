<?php

$_strDs=DIRECTORY_SEPARATOR;
$_strAbs=dirname(__FILE__).$_strDs;
$_strRel='.'.$_strDs;
$_strHtml='/';
$_arrSys=array(
	'compiled'=>'compiled',
	'db_backup'=>'db_backup',
	'img'=>'skin'.$_strDs.'i',
	'cron_jobs'=>'jobb',
	'library'=>'library',
	'core'=>'library'.$_strDs.'Core',
	'project'=>'library'.$_strDs.'Project',
	'zend'=>'library'.$_strDs.'Zend',
	'smarty'=>'library'.$_strDs.'Smarty',
	'source'=>'source',
	'letters'=>'source'.$_strDs.'letters',
	'user_files'=>'usersdata',
	'media'=>'usersdata'.$_strDs.'fsfiles',
	'mailpool'=>'mailpool',
	'tumb_cache'=>'usersdata'.$_strDs.'tumb_cache',
	// временные файлы относящиеся к аккаунту пользователя
	'user_temp'=>'usersdata'.$_strDs.'temp'.$_strDs.'users',
	// постоянные файлы относящиеся к аккаунту пользователя
	'user_data'=>'usersdata'.$_strDs.'users',
	// относятся к видеоконвертеру
	'logfiles'=>'usersdata'.$_strDs.'temp'.$_strDs.'log_files',
	'mctmp'=>'usersdata'.$_strDs.'temp'.$_strDs.'mc_tmp',
	'mcpid'=>'usersdata'.$_strDs.'temp'.$_strDs.'mc_pids',
	// Zend_Cache для бэкенда File место хранения кэшируемых данных
	'cache'=>'usersdata'.$_strDs.'temp'.$_strDs.'cache',
	'services'=>'services',
	'crontab'=>'services'.$_strDs.'crontab',
);
$_arrAbs=$_arrRel=$_arrHtml=array();
$_arrAbs['root']=$_strAbs;
$_arrRel['root']=$_strRel;
$_arrHtml['root']=$_strHtml;
foreach( $_arrSys as $k=>$v ) {
	$_arrAbs[$k]=$_strAbs.$v.$_strDs;
	$_arrRel[$k]=$_strRel.$v.$_strDs;
	$_arrHtml[$k]=$_strHtml.implode( $_strHtml, explode( $_strDs, $v ) ).$_strHtml;
}
$_xDebug=false; // Вывод отчета об ошибках
$_debug_mode=1;
/**
 * set db
 *
 * @return void
 */
switch( @$_SERVER['HTTP_HOST'] ) {
	case 'api.ifunnels.local':
		$_arrPrm=array(
			'host'=>'localhost',
			'username'=>'root',
			'password'=>'',
			'dbname'=>'db_api',
		); 
		$_xDebug=true;
		$_debug_mode=2;
	break;
	case 'cnm.cnmbeta.info':
		if( isset( $_SERVER['SERVER_NAME'] ) && $_SERVER['SERVER_NAME'] == 'api.ifunnels.local' ){
			$_arrPrm=array(
				'host'=>'localhost',
				'username'=>'root',
				'password'=>'',
				'dbname'=>'db_cnm',
			);
		}else{
			$_arrPrm=array(
				'host'=>'10.133.56.49',
				'username'=>'prod_cnm',
				'password'=>'lSO6CZcdftfc',
				'dbname'=>'prod_cnm',
			);
		}
	break; // dev server
	case 'lpb.tracker': // tracking server
		if( isset( $_SERVER['SERVER_NAME'] ) && $_SERVER['SERVER_NAME'] == 'api.ifunnels.local' ){
			$_arrPrm=array(
				'host'=>'localhost',
				'username'=>'root',
				'password'=>'',
				'dbname'=>'lpb_tracker',
			);
		}else{
			$_arrPrm=array(
				'host'=>'10.133.56.49',
				'username'=>'lpb_tracker',
				'password'=>'hxKz!76911GY',
				'dbname'=>'lpb_tracker',
			);
		}
	break;
	case 'api.ifunnels.com':
	default:
	$_arrPrm=array(
		'host'=>'10.133.56.49',
		'username'=>'api_user',
		'password'=>'CYK1EMUp8ecl5Sm',
		'dbname'=>'prod_api',
	); break;
}

if( isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] == 'api.ifunnels.local' ){
	$_arrDomain=array(
		'domain'=>array(
			'scheme'=>'http',
			'host'=>'api.ifunnels.local',
			'url'=>'http://api.ifunnels.local',
		),
	);
}else{
	$_arrDomain=array(
		'domain'=>array(
			'scheme'=>'https',
			'host'=>'api.ifunnels.com',
			'url'=>'https://api.ifunnels.com',
		),
	);
}
/**
 * return config to Zend_Config
 *
 * @return void
 */
return $_arrDomain+array(

	// database and adapter settings
	'database'=>(empty( $arrDbSetting )?array(
		'codepage'=>'utf8',
		'arhitecture'=>'single', // single or replication
		'master'=>$_arrPrm+array(
			'adapter'=>'pdo_mysql',
			'port'=>'',
		),
		'slave'=>array(
			'adapter'=>'pdo_mysql',
			'host'=>'',
			'port'=>'',
			'username'=>'',
			'password'=>'',
			'dbname'=>'',
		),
		'paged_select'=>array(
			'row_in_page'=>12,
			'num_of_digits'=>3,
		),
	):$arrDbSetting),

	// system path
	'path'=>array(
		'absolute'=>$_arrAbs,
		'relative'=>$_arrRel,
		'html'=>$_arrHtml,
		'utilites'=>array(
			'image_magic'=>'/usr/bin/',
			'ffmpeg'=>'/usr/bin/',
			'mencoder'=>'/usr/bin/',
			'mp3lame'=>'/usr/bin/',
			'java'=>'/usr/bin/',
		),
	),

	'mailer'=>(empty( $_arrMailer )?
		array(
			'send_mode'=>'sendmail', // debmes - письма сохраняются в файл ./debmes.txt, print - выводятся в браузер, sendmail - mail(), smtp
			'codepage'=>'utf-8', // кодировка письма
			'smtp'=>array(), // TODO!!! см. Zend_Mail_Transport_Smtp
		)
	:$_arrMailer),

	'user'=>array(
		'salt'=>'j259dU9e', // добвляется при сохранении пароля, в данном проекте соли небыло. надо написать апдэйтер чтобы добавить - TODO!!! 15.02.2012
		'interval'=>2592000, // на сколько ставить куки (по умолчанию - месяц)
		'sesion_key'=>'USER', // информация о пользователе в сессии (чтобы каждый раз не выбирать из бд) $_SESSION[sesion_key]
		'cookies_name'=>'rem_', // префикс куков которые позволяют запоминать логин пользователя
		'min_passwd_len'=>3,
		'max_passwd_len'=>10,
		'min_nick_len'=>2,
		'max_nick_len'=>40,
	),

	'i18n'=>array(
		'languages'=>array( 'en', 'de', 'fr' ), // наименования по ISO 639-1 (1998) более новые трёхбуквенные не поддерживаются
		'default_language'=>'en',
	),

	'engine'=>array(
		'project_title'=>'iFunnels API',
		'project_domain'=>@$_SERVER['HTTP_HOST'],
		'max_back_urls'=>200,
		'i18n'=>false, // Internationalizing проекта
		'with_pear'=>0, // если используется PEAR - отключаем обработку E_STRICT 1-off 0-on
		'project_devemail'=>array( 'alex.lam92@yandex.by' ),
		'project_sysemail'=>array( 'name'=>'iFunnels API', 'email'=>'api@creativenichemanager.info' ), // почта проекта, используется по умолчанию
		'default_bugtrack'=>array( 'shadow-dwarf@yandex.by'), // используется в случае эксепшенов см. Core_Errors
		'current_domain'=>'cnm', // если не указано то используется поля domain для определения текущего фронтенда
		'default_profile_mask'=>'simple_profile',
		'check_installed'=>true, // проверять проинсталлированы ли модули/сайты
		'session_handler'=>false, // true - сессия в БД false - сессия в файле
	),

	// настройки для Core_Captcha_Image
	'captcha'=>array(
		'font'=>'arial.ttf',
		'wordLen'=>5,
		'width'=>120,
		'dotNoiseLevel'=>0,
		'lineNoiseLevel'=>0,
	),

	'image'=>array(
		'noimage'=>'backend'.$_strDs.'noimage.gif',
		'implementation'=>'gd', // gd/imagick
		'quality'=>85,
		'thumbnail_cashing'=>true,
		'cashe_expires'=>(60*60*12), // cashe files expires. 0-never expired
	),

	// описание сайтов данного проекта
	'sites'=>array(
		array(
			'flg_type'=>'backend',
			'flg_active'=>1,
			'prefix'=>'/site-backend',
			'domain'=>'api.ifunnels.com',
			'title'=>'Control panel',
			'sys_name'=>'site-backend',
		),
		array(
			'flg_type'=>'frontend',
			'flg_active'=>1,
			'prefix'=>'',
			'domain'=>'api.ifunnels.com',
			'title'=>'Main Site',
			'sys_name'=>'cnm',
		),
	),
	
	// для этих настроек надо сделать мастер TODO!!! 08.04.2009
	'project'=>array(
		'backend'=>array( 
			'flg_active'=>1,
			'domain'=>'/site-backend',
			'sys_name'=>'site-backend',
			'title'=>'Control panel',
		),
		// список фронтэндов для настройки БД
		'frontends'=>array(
			'engine5'=>array(
				'flg_active'=>1,
				'domain'=>'api.ifunnels.com',
				'sys_name'=>'api_ifunnels',
				'title'=>'Frontend',
			),
		),
	),

	'date_time'=>array(
		'dt_user_zone'=>false, // true - используется локальное время пользователя (для этого надо его сохранять при регистрации), false - не используется
		'dt_zone'=>'UTC', // зона в которой работает веб сервер, где нужно будет использована как значение по умолчению
		'dt_full_format'=>'d.m.Y H:i',
		'dt_date_format'=>'d.m.Y',
		'dt_full_strftime'=>'%d.%m.%Y %H:%M',
		'dt_date_strftime'=>'%d.%m.%Y',
	),

	'debugging'=>array(
		'debug_mode'=>$_debug_mode, // 0-на ошибки по возможности не реагируем, 1-отсылаем письмо, 2-в браузер полную инфу, 3-и письмо и браузер
		'show_tpl_path'=>true, // коммент с именем шаблона в html
		'show_tpl_hash'=>false, // хэш для каждого шаблона в html
		'xdebug_enable'=>$_xDebug,
	),

	// Core_Media_Converter default setting
	'conv'=>array(
		'mencoder_ver'=>23466, // начиная с r23982 убрали -lavfopts i_certify_that_my_video_stream_does_not_use_b_frames
		'video_storage'=>'local', // S3-Amazon Simple Storage Service, LOCAL-php application server
		's3'=>array(
			'bucket'=>'get_from_s3',
			'key'=>'get_from_s3',
			'secretkey'=>'get_from_s3'
		),
	),

	// Core_Media_Driver default setting
	'fsdriver'=>array(
		'method'=>1, // 1 - through gd_lib, 2 - through ImageMagic
		'cashing'=>1, // Chaching enabled, 1 - yes, 0 - no
		'exp'=>(60*60*12), // cashe files expires 0-never expired
		// default dimension
		'nw'=>100,
		'nh'=>100,
		'maxw'=>400,
		'maxh'=>400,
		'quality'=>85, //thumb quality
		'noimage'=>'backend'.$_strDs.'0.gif',
		'captcha'=>array(
			'LENGHT'=>4,
			'WIDTH'=>130,
			'HEIGHT'=>40,
		)
	),
);
?>
