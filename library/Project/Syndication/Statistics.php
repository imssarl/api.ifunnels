<?php

 /**
 * сбор статистики на удалённом сервере, подсчёт её на продакшн, очистка таблицы от устаревших данных (старше месяца) на удалённом сервере
 */
class Project_Syndication_Statistics {
	
	/**
	* список полей таблицы
	* @var array
	*/
	private $_fields=array( 'shedule_id', 'site_id', 'flg_status', 'added' );
	
	/**
	* название таблицы
	* @var string
	*/
	public static $table='cs_statistic';
	
	private static $_service='http://syndication.qjmpz.com/services/statistics.php';

	public static function generateLink( $_intSheduleId=0, $_intSiteId=0 ) {
		return '<img src="'.self::$_service.'?check='.base64_encode( $_intSheduleId.'-'.$_intSiteId ).'" width="1" height="1" border="0" style="border:none !important;" />';
	}

	public static function set( $_arrRequest=array() ) {
		if ( empty( $_arrRequest['check'] ) ) {
			return false;
		}
		$_arrIds=explode( '-', base64_decode( $_arrRequest['check'] ) );
		Core_Sql::setInsert( self::$table, array(
			'shedule_id'=>$_arrIds[0], // cs_content2site.id
			'site_id'=>$_arrIds[1], // cs_sites.id
			'added'=>time(),
		) );
		return true;
	}
}
?>