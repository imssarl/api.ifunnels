<?php
/**
 * Composite Items
 * @category framework
 * @package CompositeItems
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2008, Rodion Konnov
 * @author Rodion Konnov <kindzadza@mail.ru>
 * @date 10.04.2008
 * @version 1.0
 */


/**
 * Item Stencil
 * @internal работа с шаблонами данных
 * вынести tbl_prefix в отдельную таблицу и связать с ci_stencil многие к многим,
 * чтобы можно было типы забивать из админки TODO!!!
 * @category framework
 * @package CompositeItems
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2008, Rodion Konnov
 * @author Rodion Konnov <kindzadza@mail.ru>
 * @contact kindzadza@mail.ru
 * @date 03.11.2008
 * @version 1.8
 */


class Core_Items_Stencil extends Core_Services {
	private $fields_to_delete;
	protected $tables=array(
		'ci_stencil'=>array( 'id', 'flg_default', 'sys_name', 'title', 'tbl_prefix', 'added' ),
		'ci_stencil_field'=>array( 'id', 'stencil_id', 'flg_type', 'flg_cast', 'flg_obligatory', 'flg_unique', 'flg_hidden', 'flg_priority', 'reg_exp', 'sys_name', 'title', 'description', 'added' ),
	);
	public $field_types=array( 
		'plain_string', 'plain_text', 'plain_html', 'html_editor', 'date_time', 
		'file_other', 'file_audio', 'file_video', 'file_image', 'file_flash',
		'select_single', 'select_multi', 'select_optgroup', 'select_with_addnew', 'select_multi_with_addnew'
	);
	// отношение типов медиа системы к типам системы хранения данных, в которой хранится только ссылка (f_files.id)
	public $media_file_types=array(
		'file_other'=>'other',
		'file_audio'=>'audio',
		'file_video'=>'video',
		'file_image'=>'image',
		'file_flash'=>'flash',
	);
	// тип данных хранящихся в полях айтема, используется для сортировок не текстовых полей с помощью CAST()
	// если у поля не указан этот тип, значит сортируем как текст
	public $field_cast=array(
		1=>'BINARY',
		2=>'CHAR',
		3=>'DATE',
		4=>'DATETIME',
		5=>'SIGNED',
		6=>'TIME',
		7=>'UNSIGNED',
	);
	public $stencil=array();
	public $stencil_id=0;

	// если значение равно true все татйтлы если они есть будут переводится в нижний регистр,
	// пробелы заменятся на "_" и в случае отсутствия системного имени использоваться вместо него
	// пока реализация только для киррилицы
	// если системное имя указано то над таким полем данный флаг не действует
	public $generate_sys_names=false;

	public $with_hidden=true;

	public function __construct( $_strSys='' ) {
		$this->set_stencil_bysysname( $_arrTmp, $_strSys );
	}

	public function set_with_hidden( $_bool=true ) {
		$this->with_hidden=$_bool;
	}

	public function set_generate_sys_names( $_bool=true ) {
		$this->generate_sys_names=$_bool;
	}

	private function do_generate_sys_names( &$arrRes, $_arrDta=array() ) {
		$arrRes=$_arrDta;
		if ( !$this->generate_sys_names||!empty( $_arrDta['sys_name'] )||empty( $_arrDta['title'] ) ) {
			return false;
		}
		$arrRes['sys_name']=Core_String::getInstance( Core_String::getInstance( $arrRes['title'] )->rus2translite() )->toSystem( '_' );
		return true;
	}

	public function set( &$arrRes, &$arrErr, $_arrDat=array() ) {
		if ( empty( $_arrDat ) ) {
			return false;
		}
		$this->do_generate_sys_names( $_arrDat['arrSten'], $_arrDat['arrSten'] );
		$_arrDat=Core_A::array_check( $_arrDat['arrSten'], $this->post_filter );
		if ( !$this->error_check( $arrRes, $arrErr, $_arrDat, array(
			'sys_name'=>empty( $_arrDat['sys_name'] ),
			'title'=>empty( $_arrDat['title'] ),
			'tbl_prefix'=>empty( $_arrDat['tbl_prefix'] ),
			'fields'=>empty( $_arrDat['fields'] )
		) ) ) {
			return false;
		}
		// обрабатываем маску
		if ( empty( $this->stencil_id ) ) {
			$_arrDat['added']=time();
		}
		$this->stencil_id=Core_Sql::setInsertUpdate( 'ci_stencil', $this->get_valid_array( $_arrDat, $this->tables['ci_stencil'] ) );
		if ( !$this->set_fields( $_arrDat['fields'] ) ) {
			return false;
		}
		return $this->setNewTables( $_arrDat );
	}

	private function set_fields( $_arrSet=array() ) {
		if ( empty( $_arrSet )||empty( $this->stencil_id ) ) {
			return false;
		}
		$this->fields_to_delete=array();
		foreach ( $_arrSet as $k=>$v ) {
			$this->do_generate_sys_names( $v, $v );
			if ( !empty( $v['del'] ) ) {
				$this->fields_to_delete[]=$k;
			} elseif ( $this->error_check( $_arrFld, $_arrErrTmp, $v, array(
				'sys_name'=>empty( $v['sys_name'] ),
				'flg_type'=>!isSet( $v['flg_type'] )
			) ) ) {
				if ( !empty( $k )&&empty( $_arrDat['flg_allnew'] ) ) {
					$_arrFld['id']=$k;
				} else {
					$_arrFld['added']=time();
				}
				$_arrFld['stencil_id']=$this->stencil_id;
				$_arrFld['flg_obligatory']=empty( $_arrFld['flg_obligatory'] ) ? 0:1;
				$_arrFld['flg_unique']=empty( $_arrFld['flg_unique'] ) ? 0:1;
				$_arrFld['flg_hidden']=empty( $_arrFld['flg_hidden'] ) ? 0:1;
				$_arrFld['flg_priority']=empty( $_arrFld['flg_priority'] ) ? 0:$_arrFld['flg_priority'];
				$_arrFld['reg_exp']=empty( $_arrFld['reg_exp'] ) ? '':$_arrFld['reg_exp'];
				$_arrFld['title']=empty( $_arrFld['title'] ) ? '':$_arrFld['title'];
				$_arrFld['description']=empty( $_arrFld['description'] ) ? '':$_arrFld['description'];
				$_arrFld['flg_cast']=empty( $_arrFld['flg_cast'] ) ? 0:$_arrFld['flg_cast'];
				Core_Sql::setInsertUpdate( 'ci_stencil_field', $this->get_valid_array( $_arrFld, $this->tables['ci_stencil_field'] ) );
			}
		}
		$this->del_stencil_field();
		return true;
	}

	private function setNewTables( $arrDta=array() ) {
		$backup=new Core_Sql_Backup();
		if ( !$backup->b_get_db_tables( $_arrTbl ) ) {
			return false;
		}
		if ( !in_array( $arrDta['tbl_prefix'].'_item', $_arrTbl ) ) {
			Core_Sql::setExec( '
				CREATE TABLE `'.$arrDta['tbl_prefix'].'_item` (
				  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
				  `stencil_id` int(11) unsigned NOT NULL DEFAULT "0",
				  `user_id` int(11) unsigned NOT NULL DEFAULT "0",
				  `flg_hide` tinyint(1) unsigned NOT NULL DEFAULT "0",
				  `flg_rate` tinyint(1) unsigned NOT NULL DEFAULT "0",
				  `flg_comment` tinyint(1) unsigned NOT NULL DEFAULT "0",
				  `flg_tagged` tinyint(1) unsigned NOT NULL DEFAULT "0",
				  `edited` int(11) unsigned NOT NULL DEFAULT "0",
				  `added` int(11) unsigned NOT NULL DEFAULT "0",
				  PRIMARY KEY (`id`),
				  KEY `stencil_idx` (`stencil_id`),
				  KEY `user_idx` (`user_id`)
				) ENGINE=MyISAM ROW_FORMAT=FIXED;
			' );
		}
		if ( !in_array( $arrDta['tbl_prefix'].'_item_field', $_arrTbl ) ) {
			Core_Sql::setExec( '
				CREATE TABLE `'.$arrDta['tbl_prefix'].'_item_field` (
				  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
				  `item_id` int(11) unsigned NOT NULL DEFAULT "0",
				  `stencil_field_id` int(11) unsigned NOT NULL DEFAULT "0",
				  `content` text,
				  PRIMARY KEY (`id`),
				  KEY `item_idx` (`item_id`),
				  KEY `stencil_field_idx` (`stencil_field_id`),
				  FULLTEXT KEY `content_idx` (`content`)
				) ENGINE=MyISAM ROW_FORMAT=FIXED;
			' );
		}
		return true;
	}

	public function set_defult() {
		if ( empty( $this->stencil_id ) ) {
			return false;
		}
		Core_Sql::setExec( 'UPDATE ci_stencil SET flg_default=0' );
		Core_Sql::setExec( 'UPDATE ci_stencil SET flg_default=1 WHERE id="'.$this->stencil_id.'"' );
		return true;
	}

	static public function get_sys_name_byid( $_intId=0 ) {
		if ( empty( $_intId ) ) {
			return '';
		}
		return Core_Sql::getCell( 'SELECT sys_name FROM ci_stencil WHERE id="'.$_intId.'" LIMIT 1' );
	}

	public function get_field_types( &$arrRes ) {
		$arrRes=$this->field_types;
	}

	public function get_field_casts( &$arrRes ) {
		$arrRes=$this->field_cast;
	}

	public function check_htmlfield_bytype( $_intType=0 ) {
		return in_array( $this->field_types[$_intType], array( 'plain_html', 'html_editor' ) );
	}

	public function check_datetime_bytype( $_intType=0 ) {
		return in_array( $this->field_types[$_intType], array( 'date_time' ) );
	}

	public function cast_sysname_to_mediatype( &$strRes, $_strName=null ) {
		if ( is_null( $_strName ) ) {
			return false;
		}
		$strRes=$this->media_file_types[$this->field_types[$this->stencil['arrFields'][$_strName]['flg_type']]];
		return true;
	}

	public function get_file_field_types( &$arrRes ) {
		$arrRes=array_intersect_key( array_flip( $this->field_types ), $this->media_file_types );
	}

	public function get_current_stencil( &$arrRes ) {
		if ( empty( $this->stencil ) ) {
			return false;
		}
		$arrRes=$this->stencil;
		return true;
	}

	public function get_current_field_formatted( &$arrRes, $_arrSet=array() ) {
		// в качестве key id а не sys_name как по умолчанию
		if ( !empty( $_arrSet['id_key'] ) ) {}
		// ограниченное кол-во полей
		if ( !empty( $_arrSet['field_limit'] ) ) {
			$i=1;
			foreach( $this->stencil['arrFields'] as $v ) {
				$arrRes[$v['sys_name']]=$v;
				if ( $i>=$_arrSet['field_limit'] ) {
					break;
				}
				$i++;
			}
		}
		// только файловые поля
		if ( !empty( $_arrSet['files_fields'] ) ) {
			$this->get_file_field_types( $_arrFileTypes );
			foreach( $this->stencil['arrFields'] as $v ) {
				if ( !in_array( $v['flg_type'], $_arrFileTypes ) ) {
					continue;
				}
				$arrRes[$v['sys_name']]=$v;
			}
			return !empty( $arrRes );
		}
		// только определённые поля (sys_name)
		if ( !empty( $_arrSet['arrNeedField'] ) ) {}
		// если специально не форматируем то выдаём все поля шаблона
		if ( empty( $arrRes ) ) {
			$arrRes=$this->stencil['arrFields'];
			return false;
		}
		return true;
	}

	public function get_stencil( &$arrRes, $_strSys='' ) {
		if ( empty( $_strSys ) ) {
			return false;
		}
		$arrRes=Core_Sql::getRecord( 'SELECT * FROM ci_stencil WHERE sys_name='.Core_Sql::fixInjection( $_strSys ).' LIMIT 1' );
		if ( empty( $arrRes ) ) {
			return false;
		}
		$this->get_fields( $arrRes['arrFields'], $arrRes['id'] );
		return true;
	}

	public function get_fields( &$arrRes, $_intId=0 ) {
		$_intId=empty( $_intId )?$this->stencil_id:$_intId;
		if ( empty( $_intId ) ) {
			return false;
		}
		$_strQ='
			SELECT sys_name syskey, id, stencil_id, flg_type, flg_cast, flg_hidden, flg_priority, sys_name, title, flg_obligatory, flg_unique, reg_exp, description 
			FROM ci_stencil_field 
			WHERE stencil_id="'.$_intId.'" '.($this->with_hidden?'':' AND flg_hidden=1 ').'
			ORDER BY flg_priority DESC
		';
		//$arrRes=Core_Sql::getAssoc( $_strQ );
		$arrRes=Core_Sql::getKeyRecord( $_strQ );
		return !empty( $arrRes );
	}

	public function get_all_toselect( &$arrRes ) {
		$arrRes=Core_Sql::getKeyVal( '
			SELECT s.sys_name, s.title
			FROM ci_stencil s
		' );
		return !empty( $arrRes );
	}

	public function get_all( &$arrRes ) {
		$arrRes=Core_Sql::getAssoc( '
			SELECT s.*, COUNT(f.id) field_num
			FROM ci_stencil s
			LEFT JOIN ci_stencil_field f ON f.stencil_id=s.id
			GROUP BY s.id
		' );
		return !empty( $arrRes );
	}

	public function del_stencil() {
		if ( empty( $this->stencil_id ) ) {
			return false;
		}
		// в будущем items может быть и другим классом (с более сложной или просто другой структурой данных)
		// нужно будет где-то брать имя класса который реализует айтем (например из БД?)
		// TODO!!!
		$_obj=new Core_Items( $this->stencil['sys_name'] );
		if ( $_obj instanceof items_sample ) {
			$_obj->del_items_bymask();
		}
		Core_Sql::setExec( 'DELETE s, f FROM ci_stencil s LEFT JOIN ci_stencil_field f ON f.stencil_id=s.id WHERE s.id="'.$this->stencil_id.'"' );
		return true;
	}

	private function del_stencil_field() {
		if ( empty( $this->fields_to_delete ) ) {
			return false;
		}
		$_obj=new Core_Items( $this->stencil['sys_name'] );
		if ( $_obj instanceof items_sample ) {
			$_obj->del_fields_bymask( $this->fields_to_delete );
		}
		Core_Sql::setExec( 'DELETE FROM ci_stencil_field WHERE id IN('.join( ', ', $this->fields_to_delete ).')' );
		return true;
	}

	public function set_stencil_bysysname( &$arrRes, $_strSys='' ) {
		if ( !$this->get_stencil( $arrRes, $_strSys ) ) {
			return false;
		}
		$this->stencil=$arrRes;
		$this->stencil_id=$arrRes['id'];
		return true;
	}
}
?>