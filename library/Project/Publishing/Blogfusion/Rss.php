<?php
/**
 * WorkHorse Framework
 *
 * @category Project
 * @package Project_Publishing
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2005-2010, web2innovation
 * @author Rodion Konnov <kindzadza@mail.ru>
 * @date 02.02.2010
 * @version 0.1
 */


/**
 * Create rss projects
 *
 * @category Project
 * @package Project_Wpress
 * @copyright Copyright (c) 2005-2010, web2innovation
 * @license http://opensource.org/licenses/ MIT License
 */
class Project_Publishing_Blogfusion_Rss {

	private $_data=array(); // данные от пользователя
	private $_records=array(); // данные для записи в бд
	private $_blogs=array(); // ids блога
	private static $_table='pub_rssblogs';
	

	public function __construct( Core_Data $data ) {
		$this->_data=&$data->filtered;
		$this->_blogs=$this->_data['arrBlogIds'];
	}

	public function storeBlogList() {
		if ( !empty($this->_data['id']) ) {
			Core_Sql::setExec('DELETE FROM '.self::$_table.' WHERE project_id = '.$this->_data['id'] );
		}
		foreach( $this->_blogs as $b ) {
			$this->_records[]=array( 
				'project_id'=>$this->_data['id'], 
				'site_id'=>$b['site_id'], 
				'ext_category_id'=>(empty( $b['ext_category_id'] )? 0:$b['ext_category_id']), 
			);
		}
		if ( !empty($this->_records) ) {
			Core_Sql::setMassInsert( self::$_table, $this->_records );
		}
		return true;
	}

	public static function delete( $_intId=0 ) {
		if ( empty( $_intId ) ) {
			return false;
		}
		Core_Sql::setExec( 'DELETE FROM '.self::$_table.' WHERE project_id='.$_intId );
		return true;
	}

	// настройки для getList
	private $_withIds=array(); // c данными id

	// сброс настроек после выполнения getList
	private function init() {
		$this->_withIds=array();
	}

	public function withIds( $_arrIds=array() ) {
		$this->_withIds=is_array( $_arrIds ) ? $_arrIds:array( $_arrIds );
		return $this;
	}

	public function getList( &$mixRes ) {
		$_crawler=new Core_Sql_Qcrawler();
		$_crawler->set_select( 'r.*' );
		$_crawler->set_from( self::$_table.' r' );
		$_crawler->set_where( 'r.project_id="'.$this->_data['id'].'"' );
		if ( !empty( $this->_withIds ) ) {
			$_crawler->set_where( 'r.id IN ('.Core_Sql::fixInjection( $this->_withIds ).')' );
		}
		$_crawler->set_order_sort( $this->_withOrder );
		$_crawler->get_result_full( $_strSql );
		$mixRes=Core_Sql::getAssoc( $_strSql );
		$this->init();
		return !empty( $mixRes );
	}
}
?>