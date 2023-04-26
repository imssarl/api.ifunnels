<?php
/**
 * Auxiliary classes
 * @category framework
 * @package Auxiliary
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2008, Rodion Konnov
 * @author Rodion Konnov <kindzadza@mail.ru>
 * @date 20.11.2008
 * @version 1.0
 */


/**
 * Set item stat
 * @internal Установка статистики определённого типа
 * @category framework
 * @package Auxiliary
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2008, Rodion Konnov
 * @author Rodion Konnov <kindzadza@mail.ru>
 * @date 12.01.2008
 * @version 2.4
 */


class Core_Stat_Set extends Core_Services {
	public $s_types=array( 'docview', 'video_file' );
	public $s_notauto_arch=array(
		'stat_count'=>array( 1=>'video_file', 2=>'user_points', 5=>'user_mango' ),
		'stat_by_day'=>array( 0=>'docview', 2=>'user_points', 5=>'user_mango' )
	);
	public $s_interval=array( 'day'=>0, 'hour'=>3, 'year'=>0 );
	public $current_types=array();

	function __construct( $_arrSet=array() ) {
		if ( !empty( $_arrSet['interval'] ) ) {
			$this->s_interval=$_arrSet['interval'];
		}
		$this->objI=new identifier( array(
			'i_tname'=>'stat_raw',
			'i_cname'=>'stat_client_uid',
			'i_interval'=>$this->s_interval,
		) );
	}

	// установка статистики
	function set( $_arrSet=array() ) {
		if ( !$this->set_current_types( $this->current_types, $this->s_types, @$_arrSet['types'] ) ) {
			return false;
		}
		if ( empty( $_arrSet['item_id'] )||!$this->objI->identification() ) {
			return false;
		}
		foreach( $this->current_types as $v ) {
			if ( ( Core_Sql::getCell( '
				SELECT flg_uid
				FROM stat_raw
				WHERE
					flg_type="'.$v.'" AND flg_uid="'.$this->objI->i_uid.'" AND item_id="'.$_arrSet['item_id'].'" AND
					FROM_UNIXTIME(added,"%j%Y")=FROM_UNIXTIME(UNIX_TIMESTAMP(NOW()),"%j%Y")
			' )>0 ) ) {
				continue;
			}
			Core_Sql::setInsert( 'stat_raw', array(
				'flg_type'=>$v,
				'flg_uid'=>$this->objI->i_uid,
				'item_id'=>$_arrSet['item_id'],
				'added'=>time(),
			) );
		}
		return true;
	}
}
?>