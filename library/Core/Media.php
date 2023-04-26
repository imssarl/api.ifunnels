<?php


/**
 * File uploding & management
 */


class Core_Media extends Core_Media_Driver {
	public $m_title_limit=60;
	public $m_description_limit=250;
	public $m_check_limits=false;
	/**
	* Id в системе
	* @public integer
	*/
	public $m_file_id=0;
	/**
	* Id пользователя просматривающего файл
	* @public integer
	*/
	public $m_viewer=0;
	/**
	* Id владельца файла
	* @public integer
	*/
	public $m_owner=0;
	/**
	* маска таблицы-хранилища файловой информации
	* @public array
	*/
	public $m_mtbl=array(
		'id', 'user_id', 'flg_status_utilization', 'flg_status_handling', 'flg_kids', 'flg_junior', 'flg_maniacs', 'flg_type', 'flg_temp', 'flg_rate', 'flg_tags',
		'flg_comm', 'flg_embed', 'priority', 'tumb_num', 'duration', 'letter', 'alias', 'sys_name', 'orig_name', 'extension', 'size',
		'mime_type', 'title', 'description', 'converted', 'edited', 'added' );
	/**
	* constructor
	* @param array $_arrSet in - настройки
	* @return void
	*/
	function __construct( $_arrSet=array() ) {
		parent::__construct( $_arrSet );
		if ( !empty( $_arrSet['user_id'] ) ) {
			$this->m_owner=$this->m_viewer=$_arrSet['user_id'];
		} elseif ( Zend_Registry::get( 'objUser' )->getId( $_int ) ) {
			$this->m_owner=$this->m_viewer=$_int;
		}
		if ( !empty( $_arrSet['owner_id'] ) ) {
			$this->m_owner=$_arrSet['owner_id'];
		}
		if ( !empty( $_arrSet['file_id'] ) ) {
			$this->m_set_file_id( $_arrSet['file_id'] );
		}
		if ( !empty( $_arrSet['alias'] ) ) {
			$this->m_alias=$_arrSet['alias'];
		}
		if ( !empty( $_arrSet['with_limits'] ) ) {
			$this->m_check_limits=true;
		}
	}

	function m_set_file_id( $_intId=0 ) {
		$this->m_file_id=0;
		if (empty($_intId)) {
			return false;
		}
		$this->m_file_id=$_intId;
		// $this->m_file_id=Core_Sql::fixInjection( $_intId ); // с ковычками будет - может и не нужно TODO!!! 07.04.2009
		return true;
	}
	/**
	* сохранение единичного файла в системе
	* @param array $arrRes out - пост, обычно в $this->out
	* @param array $arrErr out - ошибки
	* @param array $_arrFile in - $_FILES['you_handler']
	* @param array $_arrDat in - $_POST['you_array_with_data']
	* @return boolean
	*/
	function m_set_file( &$arrRes, &$arrErr, $_arrFile=array(), $_arrDat=array() ) {
		if ( !$this->m_set_file_verify( $arrRes, $arrErr, $_arrFile, $_arrDat ) ) {
			return false;
		}
		if ( $this->m_set_file_data( $_arrDat, $_arrFile ) ) {
			$this->m_upload( $_arrDat, $_arrFile );
		}
		$this->m_update_insert_file( $_arrDat );
		return true;
	}

	public function m_check_set_file_data( &$arrRes, &$arrErr, $_arrFile=array(), $_arrDat=array() ) {
		if ( !$this->m_set_file_verify( $arrRes, $arrErr, $_arrFile, $_arrDat ) ) {
			return false;
		}
		if ( $this->m_set_file_data( $_arrDat, $_arrFile ) ) {
			$this->file_info_array=$_arrFile;
		}
		$this->file_dataset=$_arrDat;
		return !empty( $this->file_dataset );
	}

	public function m_set_filedata() {
		if ( empty( $this->file_dataset ) ) {
			return false;
		}
		if ( !empty( $this->file_info_array ) ) {
			$this->m_upload( $this->file_dataset, $this->file_info_array );
		}
		$this->m_update_insert_file( $this->file_dataset );
		$this->file_dataset=array();
		return true;
	}

	function m_update_insert_file( $_arrDat=array() ) {
		if ( empty( $_arrDat ) ) {
			return false;
		}
		$this->m_file_id=Core_Sql::setInsertUpdate( 'f_files', $this->get_valid_array( $_arrDat, $this->m_mtbl ) );
		return !empty( $this->m_file_id );
	}
	/**
	* чекаем данные файла
	* если запись в БД есть и файл при обновлении не заливается, то ошибки файла игнорируются
	* если-же записи ещё нет или файл всётаки заливается то всё чекаем
	* @param array $arrRes out - пост, обычно в $this->out
	* @param array $arrErr out - ошибки
	* @param array $_arrFile in/out - $_FILES['you_handler']
	* @param array $_arrDat in/out - $_POST['you_array_with_data']
	* @return boolean
	*/
	function m_set_file_verify( &$arrRes, &$arrErr, $_arrFile, &$_arrDat ) {
		$_arrDat=Core_A::array_check( $_arrDat, $this->post_filter );
		if ( empty( $_arrFile ) ) {
			$arrErr['no_data']=1;
			return true;
		}
		// в этот метод $_arrDat['id'] передать можно
		if ( !empty( $_arrDat['id'] ) ) {
			$this->m_set_file_id( $_arrDat['id'] );
		}
		// $_arrDat['id'] в этом классе depercated!!!
		if ( empty( $_arrDat['id'] )&&!empty( $this->m_file_id ) ) {
			$_arrDat['id']=$this->m_file_id;
		}
		if ( !empty( $this->m_file_id )&&!empty( $_arrFile['error'] ) ) {
			$arrErr['upload_error_but_id_exists']=1;
			return true;
		}
		if ( !empty( $_arrDat['force_type_by_ext'] ) ) { // принудительно устанавливаем тип по расширению файла
			$this->d_set_typebyfilename( $_arrFile['name'] );
		}
		if ( $this->m_check_limits ) {
			$_arrDat['title']=substr( $_arrDat['title'], 0, $this->m_title_limit );
			$_arrDat['description']=substr( $_arrDat['description'], 0, $this->m_description_limit );
		}
		if ( !$this->error_check( $arrRes, $arrErr, $_arrDat, array(
			'have_no_extension'=>!$this->d_get_extension( $_arrFile['name'] ), /*no extension file*/
			'extension_no_valid'=>!in_array( $this->m_sys_ext, $this->m_ext_allowed[$this->m_type] ), /*extension no valid*/
			'upload_error'=>!empty( $_arrFile['error'] ), /*new file & upload error*/
			'title'=>$this->m_check_limits&&empty( $_arrDat['title'] ),
			'description'=>$this->m_check_limits&&empty( $_arrDat['description'] ),
		) ) ) {
			return false;
		}
		return true;
	}
	/**
	* собираем информацию для записи в БД
	* @param array $_arrDat in/out - данные записи
	* @param array $_arrFile in - $_FILES['you_handler']
	* @return boolean
	*/
	function m_set_file_data( &$_arrDat, &$_arrFile ) {
		// set common data
		if ( empty( $this->m_file_id ) ) {
			$_arrDat['user_id']=$this->m_viewer;
			$_arrDat['flg_type']=$this->m_type;
			$_arrDat['added']=time();
		} else {
			$_arrDat['id']=$this->m_file_id; // вобщем-то он может быть и во входных данных, но на всякий случай перестраховка
			$_arrDat['edited']=time();
		}
		if ( !empty( $this->m_alias ) ) { // файл привязывается через псеводоним а не через id
			$_arrDat['alias']=$this->m_alias;
		}
		$_arrDat['flg_temp']=empty( $_arrDat['flg_temp'] )?0:1;
		$_arrDat['flg_comm']=empty( $_arrDat['flg_comm'] )?0:1;
		$_arrDat['flg_rate']=empty( $_arrDat['flg_rate'] )?0:1;
		$_arrDat['flg_tags']=empty( $_arrDat['flg_tags'] )?0:1;
		$_arrDat['flg_embed']=empty( $_arrDat['flg_embed'] )?0:1;
		$_arrDat['priority']=isSet($_arrDat['priority'])?(int)$_arrDat['priority']:0;
		// set file data
		if ( !empty( $_arrFile['error'] )||!$this->d_get_fileinfo( $_arrFile['name'] ) ) {
			unset( $_arrDat['size'] );
			return false;
		}
		if ( empty( $_arrDat['title'] ) ) {
			$_arrDat['title']=$this->m_sys_name; // результат обработки оригинального имени файла
		}
		$_arrDat['letter']=$_arrFile['name']{0};
		$_arrDat['extension']=$this->m_sys_ext;
		$this->m_get_fileinfo( $_arrOld, @$this->m_file_id ); // данные по записи, чтобы перезаписать файл а не генерить новое имя
		if ( !empty( $_arrDat['force_type_by_ext'] )&&empty( $_arrFile['error'] ) ) { // при этом тип может меняться даже у загруженного файла, актуально только в случае наличия файла
			$_arrDat['flg_type']=$this->m_type;
			// если тип файла сменился удаляем старый файл, если он был, физически (т.к. разные диры)
			if ( !empty( $_arrOld['sys_file'] )&&$_arrOld['flg_type']!=$_arrDat['flg_type'] ) {
				$this->d_rmfile( $this->m_dirs_path[$_arrOld['flg_type']].$_arrOld['sys_file'] );
				$_arrOld['sys_file']=''; // чтобы заново сгенерилось системное имя с нужным расширением
			}
		}
		if ( empty( $_arrOld['sys_file'] ) ) { // новый файл или сменился тип
			$this->d_get_newname();
			$_arrDat['sys_name']=$this->m_sys_name;
			$_arrFile['dst']=$this->m_sys_file;
		} else { // перезаливаем
			$_arrDat['flg_status_utilization']=1; // скрываем файл - возможно админ его уже апрувил
			// если файл того-же типа но изменилось расширение
			if ( $this->m_sys_ext!=$_arrOld['extension'] ) {
				$this->d_rmfile( $this->m_dirs_path[$_arrOld['flg_type']].$_arrOld['sys_file'] ); // удаляем оригинал
				$_arrOld['sys_file']=$_arrOld['sys_name'].'.'.$this->m_sys_ext; // используем старое имя + новое расширение
			}
			$_arrFile['dst']=$_arrOld['sys_file'];
		}
		return true;//$this->m_upload( $_arrDat, $_arrFile );
	}
	/**
	* собираем информацию для записи в БД
	* @param array $arrRes out - данные по файлу
	* @param array $_arrFile in - $_FILES['you_handler'] + 'dst', - под каким именем сохранять данный файл,
	* 'cpy' - если используется стандартный функционал для копированя/клонирования файла
	* @return boolean
	*/
	function m_upload( &$arrRes, $_arrDta=array() ) {
		if ( empty( $_arrDta )||empty( $this->m_dir ) ) {
			return false;
		}
		$this->m_destination=$this->m_dir.$_arrDta['dst'];
		if ( !empty( $_arrDta['cpy'] ) ) {
			copy( $_arrDta['tmp_name'], $this->m_destination );
		} elseif ( !empty( $_arrDta['mov'] ) ) {
			copy( $_arrDta['tmp_name'], $this->m_destination );
			$this->d_rmfile( $_arrDta['tmp_name'] );
		} else {
			move_uploaded_file( $_arrDta['tmp_name'], $this->m_destination );
		}
		$arrRes['orig_name']=$_arrDta['name'];
		$arrRes['mime_type']=$_arrDta['type'];
		$arrRes['size']=filesize( $this->m_destination );
		if ( $this->m_type_txt=='video' ) { // set task to media_converter
			$arrRes['flg_status_handling']=1;
		}
		return true;
	}

	function m_download_byname( $_strName='' ) {
		if ( empty( $_strName ) ) {
			return false;
		}
		$_arrFile=Core_Sql::getRecord( 'SELECT * FROM f_files WHERE sys_name='.Core_Sql::fixInjection( $_strName ).' LIMIT 1' );
		if ( empty( $_arrFile ) ) {
			return false;
		}
		return $this->d_download( $_arrFile );
	}
	/**
	* удаляет файл из системы {со всеми вриантами TODO!!!}
	* @param array $_arrIds in - ids файлов в БД
	* @return boolean
	*/
	function m_del_files( $_mixIds=0 ) {
		if ( empty( $_mixIds ) ) {
			return false;
		}
		if ( !is_array( $_mixIds ) ) {
			$_mixIds=array( $_mixIds );
		}
		if ( !$this->m_getfiles( $_arrRow, $_arrTmp, array( 'ids'=>$_mixIds ) ) ) {
			return false;
		}
		foreach( $_arrRow as $v ) {
			if ( empty( $v['sys_name'] ) ) {
				continue;
			}
			$this->d_rmfile( $this->m_dirs_path[$v['flg_type']].$v['sys_file'] );
		}
		Core_Sql::setExec( 'DELETE FROM f_files WHERE id IN("'.join( '", "', $_mixIds ).'")' );
		return true;
	}
	/**
	* для группового редактирования строк в таблице например
	* @param array $_arrDta in - массив $_POST например
	* @return boolean
	*/
	function m_edit_filerow( $_arrDta=array() ) {
		if ( empty( $_arrDta ) ) {
			return false;
		}
		foreach( $_arrDta as $k=>$v ) {
			if ( !empty( $v['del'] ) ) {
				$_arrDel[]=$k;
				continue;
			}
			$arrU['id']=$k;
			$arrU['flg_comm']=empty( $v['comm'] )?0:1;
			$arrU['flg_rate']=empty( $v['rate'] )?0:1;
			$arrU['flg_tags']=empty( $v['tag'] )?0:1;
			Core_Sql::setUpdate( 'f_files', $arrU );
		}
		if ( !empty( $_arrDel ) ) {
			$this->m_del_files( $_arrDel );
		}
		return true;
	}

	function m_get_fileinfo_by_sysname( &$arrRes, $_strSysName='' ) {
		if ( empty( $_strSysName ) ) {
			return false;
		}
		$this->m_file_id=Core_Sql::getCell( 'SELECT id FROM f_files WHERE sys_name='.Core_Sql::fixInjection( $_strSysName ) );
		if ( empty( $this->m_file_id ) ) {
			return false;
		}
		return $this->m_get_fileinfo( $arrRes );
	}
	/**
	* возвращает масив файлов с ключом в виде поля алиас (строковое значение)
	* требуется для строгого позиционирования конкретного файла на странице
	* @param array $arrRes out
	* @param array $_arrIds in - ids требуемых файлов
	* @return boolean
	*/
	function m_get_aliased_files( &$arrRes, $_arrIds=array() ) {
		if ( empty( $_arrIds ) ) {
			return false;
		}
		$arrRes=Core_Sql::getKeyRecord( '
			SELECT alias, id, flg_type, sys_name, IF(LENGTH(sys_name)>0,CONCAT(sys_name,".",extension),"") sys_file, size, mime_type
			FROM f_files WHERE id IN("'.join( '", "', $_arrIds ).'")
		' );
		foreach( $arrRes as $k=>$v ) {
			$arrRes[$k]['sys_name_path']=$this->m_folders[$v['flg_type']].$v['sys_file'];
		}
		return !empty( $arrRes );
	}
	/**
	* инфа по одному файлу
	* @param array $arrRes out
	* @param integer $_intId in - id файла
	* @return boolean
	*/
	function m_get_fileinfo( &$arrRes, $_intId=0, $_arrSet=array() ) {
		$_intId=empty( $_intId ) ? $this->m_file_id:$_intId;
		if ( empty( $_intId )&&empty( $_arrSet ) ) {
			return false;
		}
		if ( !empty( $_intId ) ) {
			$_arrSet['ids']=array( $_intId );
		}
		$this->m_collect_sql( $_strQ, $_arrTmp, $_arrSet );
		$arrRes=Core_Sql::getRecord( $_strQ.' LIMIT 1' );
		return !empty( $arrRes );
	}
	/**
	* возвращает массив записей о файлах по любым фильтрам
	* @param array $arrRes out
	* @param array $arrPg out - массив для пэджинга
	* @param array $_arrSet in - настройки (фильтры, натройки пэйджинга)
	* @return boolean
	*/
	public function m_getfiles( &$arrRes, &$arrPg, $_arrSet=array() ) {
		$this->m_collect_sql( $_strQ, $arrPg, $_arrSet );
		$arrRes=Core_Sql::getAssoc( $_strQ );
		return !empty( $arrRes );
	}

	public $filter=array();

	public function m_collect_sql( &$strQ, &$arrPg, $_arrSet=array() ) {
		$this->objQc=new Core_Sql_Qcrawler();
		$this->filter=$_arrSet;
		$this->m_getfiles_main( $_arrSet );
		$this->objQc->get_sql( $strQ, $arrPg, $_arrSet );
	}

	public function m_getfiles_main( $_arrSet=array() ) {
		$this->objQc->set_select( 'f.*, IF(LENGTH(f.sys_name)>0,CONCAT(f.sys_name,".",f.extension),"") sys_file, RIGHT(SEC_TO_TIME(f.duration),5) duration_time' );
		$this->objQc->set_select( 'u.nickname' );
		$this->objQc->set_from( 'f_files f' );
		if ( !empty( $_arrSet['with_anonim_files'] ) ) {
			$this->objQc->set_from( 'INNER JOIN u_users u ON u.id=f.user_id' );
		} else {
			$this->objQc->set_from( 'LEFT JOIN u_users u ON u.id=f.user_id' );
		}
		$this->get_keyword_search();
		$this->set_status();
		if ( !empty( $_arrSet['ids'] ) ) {
			$_strGrp=empty( $_arrSet['not_in_this_ids'] ) ? '':' NOT'; // инвертирует массив $_arrSet['ids']
			$_arrSet['ids']=is_array( $_arrSet['ids'] )?$_arrSet['ids']:array( $_arrSet['ids'] );
			$this->objQc->set_where( 'f.id'.$_strGrp.' IN("'.join( '", "', $_arrSet['ids'] ).'")' );
		}
		// файлы которые заливали эти группы пользователей (массив с названиями групп)
		if ( !empty( $_arrSet['user_groups'] )&&Core_Acs::getInstance()->r_get_ids_by_title( $_arrGrp, $_arrSet['user_groups'] ) ) {
			$_strGrp=empty( $_arrSet['not_in_groups'] ) ? '':' NOT'; // инвертирует массив $_arrSet['user_groups']
			$this->objQc->set_from( 'INNER JOIN u_link ul ON ul.user_id=u.id AND ul.group_id'.$_strGrp.' IN("'.join( '", "', array_keys( $_arrGrp ) ).'")' );
			$this->objQc->set_group( 'f.id' );
		}
		// файлы только перечисленных ids пользователей
		if ( !empty( $_arrSet['user_ids'] ) ) {
			$_strGrp=empty( $_arrSet['not_in_user_ids'] ) ? '':' NOT'; // инвертирует массив $_arrSet['user_ids']
			$_arrSet['user_ids']=is_array( $_arrSet['user_ids'] )?$_arrSet['user_ids']:array( $_arrSet['user_ids'] );
			$this->objQc->set_where( 'f.user_id'.$_strGrp.' IN("'.join( ', ', $_arrSet['user_ids'] ).'")' );
		}
/* DEPERCATED!!! */
		// файлы только этого пользователя
		if ( !empty( $_arrSet['user_id'] ) ) {
			$this->objQc->set_where( 'f.user_id="'.$_arrSet['user_id'].'"' );
		}
		// файлы пользователей
		if ( !empty( $_arrSet['users_ids'] ) ) {
			$this->objQc->set_where( 'f.user_id IN("'.join( ', ', $_arrSet['users_ids'] ).'")' );
		}
/* DEPERCATED!!! */
		// по типу файлов
		if ( !empty( $_arrSet['by_type'] ) ) {
			$this->d_set_typebytypename( $_arrSet['by_type'] );
			$this->objQc->set_where( 'f.flg_type="'.$this->m_type.'"' );
		}
		// статистика по просмотрам
		if ( !empty( $_arrSet['with_file_stat'] ) ) {
			$this->objQc->set_select( 's.counter views' );
			$this->objQc->set_from( 'LEFT JOIN stat_count s ON s.flg_type=1 AND s.item_id=f.id' );
		}
		// рэйтинг файла
		if ( !empty( $_arrSet['with_file_rate'] ) ) {
			$this->objQc->set_select( 'ROUND(SUM(r.starnum)/COUNT(*),2) rate' );
			$this->objQc->set_from( 'LEFT JOIN stat_fiverating r ON r.flg_type=2 AND r.item_id=f.id' );
		}
		// дополнительные данные
		$this->m_getfiles_additional( $_arrSet );
		// сортировка
		$_arrSet['order']=empty( $_arrSet['order'] ) ? array( 'f.id--up' ):$_arrSet['order'];
		if ( !empty( $_arrSet['order'] ) ) {
			$this->objQc->set_order_sort( $_arrSet['order'] );
		}
		$this->objQc->set_limit( @$_arrSet['limit'] );
	}

	// по наличию слов в тэгах, заголовке, описании
	public function get_keyword_search( $_strKey='' ) {
		if ( empty( $this->filter['keyword'] ) ) {
			return false;
		}
		$_arrK=array();
		$this->objQc->keyword_search( $_arrK, $_strTmp, array( 'f.title', 'f.description' ), $this->filter['keyword'] );
		$objT=new tags( array( 'flg_type'=>'video_file' ) );
		if ( $objT->t_search_items_sql( $_strSql, $this->filter['keyword'] ) ) {
			$_arrK[]='(f.id IN('.$_strSql.'))';
		}
		if ( empty( $_arrK ) ) {
			return false;
		}
		$this->objQc->set_where( '('.join( ' OR ', $_arrK ).')' ); // все условия поиска по тексту
	}

	public function set_status() {
		if ( !empty( $this->filter['show_to_owner'] ) ) {
			// файлы будут показаны владельцу
			$this->objQc->set_where( 'f.flg_status_utilization<2' );
		} elseif ( !empty( Core_Users::$info['right']['view_only_frontend_files'] ) ) { // ??? TODO!!!
			// показывать только отконверченные и аппрувленные файлы
			$this->objQc->set_where( 'f.flg_status_utilization=0 AND f.flg_status_handling=0' );
		} else {
			/*
			0-может отображаться на фронтэнде и бакэнде;
			1-отображается только в бакэнде;
			2-отображается только в бакэнде только в определённом месте (например в корзине);
			3-файлы находятся на хранении и выводятся из таблицы другими классами (не медиа классом)
			*/
			if ( isSet( $this->filter['flg_status_utilization'] ) ) {
				$this->objQc->set_where( 'f.flg_status_utilization='.$this->filter['flg_status_utilization'] );
			}
			/*
			0-обработан;
			1-задание на обработку;
			2-обрабатываем;
			3-кривой таск;
			*/
			if ( isSet( $this->filter['flg_status_handling'] ) ) {
				$this->objQc->set_where( 'f.flg_status_handling='.$this->filter['flg_status_handling'] );
			}
		}
	}

	public function m_getfiles_additional( &$_arrSet ) {}
}
?>