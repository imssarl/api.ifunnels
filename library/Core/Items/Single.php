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
 * Item
 * @internal обработчик данных
 * @category framework
 * @package CompositeItems
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2008, Rodion Konnov
 * @author Rodion Konnov <kindzadza@mail.ru>
 * @date 03.11.2008
 * @version 1.7
 */


class Core_Items_Single extends Core_Services {
	protected $tables=array(
		'item_tbl'=>array( 'id', 'stencil_id', 'user_id', 'flg_hide', 'flg_rate', 'flg_comment', 'flg_tagged', 'flg_priority', 'edited', 'added' ),
		'fields_tbl'=>array( 'id', 'item_id', 'stencil_field_id', 'content' ),
	);
	protected $item_tbl, $fields_tbl;
	private $fields_to_delete=array();
	private $fields_exists=array();
	private $raw_item_fields=array();
	private $activate_field_checker=true;
	public $objS, $objU;
	public $with_hidden_fields=true; // с полями которые в стенсиле отмечены как скрытые

	public function __construct( $_strSys='' ) {
		$this->setStencil( $_strSys );
	}

	public function setStencil( $_str='' ) {
		if ( empty( $_str ) ) {
			return false;
		}
		$this->objS=new Core_Items_Stencil( $_str );
		$this->set_tables();
		return true;
	}

	public function set_with_hidden_fields( $_bool=true ) {
		$this->with_hidden_fields=$_bool;
	}

	private function set_tables() {
		if ( empty( $this->objS->stencil_id ) ) {
			trigger_error( ERR_PHP.'|no Stencil object created' );
		}
		$this->item_tbl=$this->objS->stencil['tbl_prefix'].'_item';
		$this->fields_tbl=$this->objS->stencil['tbl_prefix'].'_item_field';
	}

	// если через set_item проходит только часть полей то нужно проверку делать отдельно
	public function set_field_checker( $_bool=true ) {
		$this->activate_field_checker=$_bool;
	}

	public function duplicateItem( &$arrRes, $_intId=0 ) {
		if ( empty( $_intId ) ) {
			return false;
		}
		if ( !$this->get_item( $_arrSource, $_intId ) ) {
			return false;
		}
		unSet( $_arrSource['id'] );
		$this->set_item( $arrRes, $_arrErr, $_arrSource );
		return true;
	}

	// если поменялся stencil_id для блока данных то нужно плавно перейти к новому stencil_id
	// тобишь поля с одинаковым sys_name надо сохранить TODO!!!
	// хотя что касается пользователей то там так и происходит (старыми данными заполняются новые поля формы
	// и при совпадении они допишуться в форму)
	// целесообразноли мудрить с этим?
	public function set_item( &$arrItem, &$arrErr, $_arrDta=array(), $_arrFile=array() ) {
		$arrItem=$_arrDta;
		if ( !$this->check_item( $_arrDta, $_arrFile ) ) {
			$this->get_fields_errors( $arrErr['arrFields'] );
			return false;
		}
		$arrItem=$_arrDta;
		$_arrDta=Core_A::array_check( $arrItem, $this->post_filter ); // т.к. тут режутся тэги
		if ( empty( $_arrDta['id'] ) ) {
			$_arrDta['added']=time();
			if ( empty( $_arrDta['user_id'] )&&Zend_Registry::get( 'objUser' )->getId( $_int ) ) {
				$_arrDta['user_id']=$_int;
			}
		} else {
			$_arrDta['edited']=time();
		}
		$_arrDta['stencil_id']=$this->objS->stencil['id'];
		$_arrDta['flg_hide']=empty( $_arrDta['flg_hide'] )? 0:1;
		$_arrDta['flg_rate']=empty( $_arrDta['flg_rate'] )? 0:1;
		$_arrDta['flg_comment']=empty( $_arrDta['flg_comment'] )? 0:1;
		$_arrDta['flg_tagged']=empty( $_arrDta['flg_tagged'] )? 0:1;
		$arrItem['id']=$_arrDta['id']=$this->item_id=Core_Sql::setInsertUpdate( $this->item_tbl, $this->get_valid_array( $_arrDta, $this->tables['item_tbl'] ) );
		if ( empty( $this->item_id ) ) {
			return false;
		}
		return $this->fields_handling();
	}

	public function change_flg_priority( $_intId=0, $_intPriority=0 ) {
		if ( empty( $_intId )||empty( $_intPriority ) ) {
			return false;
		}
		Core_Sql::setUpdate( $this->item_tbl, array( 'id'=>$_intId, 'flg_priority'=>$_intPriority ) );
		return true;
	}

	public function check_item( $_arrItem=array(), $_arrFile=array() ) {
		// если это тот-же айтем и мы его уже чекали то сразу вернём тру ?
		if ( $this->item_id==$_arrItem['id']&&$this->item_already_checked ) {
			return true;
		}
		$this->set_exists_fields( $_arrItem['id'] );
		if ( empty( $this->item_fields ) ) {
			$this->set_db_fields( @$_arrItem['arrFields'] );
		}
		$this->set_file_fields( $_arrFile );
		//aux::p( $this->item_fields );
		if ( !$this->fields_checker() ) {
			return false;
		}
		$this->item_already_checked=true;
		return true;
	}

	// поля которые в данный момент хранятся в БД
	public function set_exists_fields( $_intId=0 ) {
		$this->fields_exists=array();
		if ( !$this->get_item_fields_full( $_arr, $_intId ) ) { // некорректный item_id
			return false;
		}
		$this->item_id=$_intId;
		if ( empty( $_arr ) ) { // у item нету полей хотя есть id - возможно это новый item
			return false;
		}
		$this->fields_exists=$_arr;
		return true;
	}

	public function set_db_fields( $_arrDta=array() ) {
		$this->item_fields=$this->raw_item_fields=array();
		if ( empty( $_arrDta ) ) {
			return;
		}
		$this->raw_item_fields=$_arrDta; // для полей с возможным html'ем
		$_arrDta=Core_A::array_check( $_arrDta, $this->post_filter );
		if ( empty( $_arrDta ) ) {
			return;
		}
		$this->item_fields=$_arrDta;
	}

	// чекаем поля айтема по настройкам стенсила
	public function fields_checker() {
		// чекер отключён
		if ( !$this->activate_field_checker ) {
			return true;
		}
		$this->fields_error=array();
		$this->clear_raw_by_stencil();
		if ( $this->objS->get_current_field_formatted( $_arrStFields, array( 'files_fields'=>true ) ) ) {
			$_arr=array_keys( $_arrStFields );
		}
		foreach( $this->objS->stencil['arrFields'] as $k=>$v ) {
			// обязательное поле
			if ( !empty( $v['flg_obligatory'] ) ) {
				if ( !empty( $_arr )&&in_array( $k, $_arr ) ) {
					if ( empty( $this->item_fields[$k]['content'] )&&empty( $this->item_fields[$k]['tmp_name'] ) ) { // для файловых полей
						$this->fields_error[$k]='not_exists';
						continue;
					}
				} else {
					if ( empty( $this->item_fields[$k]['content'] ) ) { // для не файловых полей
						$this->fields_error[$k]='not_exists';
						continue;
					}
				}
				/*aux::p( $this->fields_exists[$k] );
				// если из формы приходило то пишем ошибку
				// если item заполняется по частям то при отсутствии во входных данных поля
				// не появится ошики для такого поля (можно хакнуть убрав из формы поля)
				if ( isSet( $this->raw_item_fields[$k] ) ) {*/
					//$this->fields_error[$k]='not_exists';
				//}
				//continue; // если данных нет то проверять дальше смысла не имеет
			}
			// проверяем по маске reg_exp
			if ( !empty( $v['reg_exp'] ) ) {
				if ( !empty( $this->item_fields[$k] )&!preg_match( $v['reg_exp'], $this->item_fields[$k]['content'] ) ) {
					$this->fields_error[$k]='not_correct';
				}
			}
			// уникальное поле (только если прошол regexp)
			if ( !empty( $v['flg_unique'] )&&empty( $this->fields_error[$k]['not_correct'] ) ) {
				if ( empty( $this->fields_exists[$k]['id'] ) ) { // такого поля ещё небыло
					$_strSql='SELECT id FROM '.$this->fields_tbl.' WHERE stencil_field_id="'.$v['id'].'"
						AND content="'.$this->item_fields[$k]['content'].'" LIMIT 1';
				} else {
					$_strSql='SELECT id FROM '.$this->fields_tbl.' WHERE stencil_field_id="'.$v['id'].'"
						AND content="'.$this->item_fields[$k]['content'].'" AND id!="'.$this->fields_exists[$k]['id'].'" LIMIT 1';
				}
				$_intChk=Core_Sql::getCell( $_strSql );
				if ( !empty( $_intChk ) ) {
					$this->fields_error[$k]='not_unique';
				}
			}
			// файловое поле
			if ( !empty( $_arr )&&in_array( $k, $_arr )&&$this->objS->cast_sysname_to_mediatype( $strType, $k ) ) {
				$objM=new Core_Media();
				$objM->d_set_typebytypename( $strType );
				if ( !empty( $this->fields_exists[$k]['content'] ) ) {
					$objM->m_set_file_id( $this->fields_exists[$k]['content'] );
				}
				if ( !$objM->m_check_set_file_data( $arrRes, $arrErr, $this->item_fields[$k], $this->item_fields[$k] ) ) {
					if ( empty( $arrErr['upload_error'] ) ) { // файл загружался но с ошибками
						$this->fields_error[$k]='wrong_file';
					} else { // если необязательный файл не загружался поле не обрабатываем
						unset( $this->item_fields[$k] );
					}
				} else { // ошибок нет, данные инициализированы - сохраняем объект для последующего использования
					$this->item_fields[$k]['obj']=$objM;
				}
			}
		}
		return empty( $this->fields_error );
	}

	public function set_file_fields( $_arrDta=array() ) {
		if ( empty( $_arrDta ) ) {
			return;
		}
		foreach( $_arrDta as $k=>$v ) {
			foreach( $v as $key=>$val ) {
				$this->item_fields[$key][$k]=$val;
			}
		}
	}

	public function get_fields_errors( &$arrRes ) {
		$arrRes=$this->fields_error;
		return !empty( $arrRes );
	}

	/* сбор массива удаляемых полей
	если идёт редактирование (!empty( $this->fields_exists[$_strSys] ))
	и в данных с сервера это поле было (isSet( $this->raw_item_fields[$_strSys] ))
	но оно оказалось пустым (empty( $this->item_fields[$_strSys] ))
	то мы его стираем и в бд ($this->fields_to_delete[$_strSys])
	да но в случае с html полем оно может быть пустым в item_fields но непустым в raw_item_fields
	*/
	private function to_delete_accumulation( $_strSys='', $_strContent='' ) {
		if ( !empty( $this->fields_exists[$_strSys] )&&empty( $_strContent ) ) {
			$this->fields_to_delete[$_strSys]=$this->fields_exists[$_strSys];
			return true;
		}
		return false;
	}

	private function fields_handling() {
		if ( $this->objS->get_current_field_formatted( $_arrStFields, array( 'files_fields'=>true ) ) ) {
			$_arr=array_keys( $_arrStFields );
		}
		$this->fields_to_delete=array();
		foreach( $this->objS->stencil['arrFields'] as $k=>$v ) {
			// item_fields и raw_item_fields проверяются из-за html полей (в item_fields такие поял могут полностью отсутствовать)
			$_strContent=$this->get_field_content( $k );
			if ( $this->to_delete_accumulation( $k, $_strContent )||empty( $_strContent ) ) {
				continue;
			}
			// загрузка файла
			if ( !empty( $_arr )&&in_array( $k, $_arr ) ) {
				$this->set_file( $k );
			}
			// сохранение полей
			$_arrFld=array(
				'item_id'=>$this->item_id,
				'stencil_field_id'=>$this->objS->stencil['arrFields'][$k]['id'],
				'content'=>$this->get_field_content( $k )
			);
			if ( !empty( $this->fields_exists[$k] ) ) {
				$_arrFld['id']=$this->fields_exists[$k]['id'];
			}
			Core_Sql::setInsertUpdate( $this->fields_tbl, $_arrFld );
		}
		$this->del_field();
		return true;
	}

	private function get_field_content( $_strSys='' ) {
		if ( empty( $_strSys ) ) {
			return '';
		}
		if ( $this->objS->check_htmlfield_bytype( $this->objS->stencil['arrFields'][$_strSys]['flg_type'] ) ) {
			return $this->raw_item_fields[$_strSys]['content'];
		}
		if ( $this->objS->check_datetime_bytype( $this->objS->stencil['arrFields'][$_strSys]['flg_type'] ) ) {
			$objDate=new Core_Datetime( 'Y-m-d H:i:s' );
			return $objDate->to_gmt( $this->raw_item_fields[$_strSys]['content'] );
		}
		if ( empty( $this->item_fields[$_strSys]['content'] ) ) {
			return '';
		}
		return $this->item_fields[$_strSys]['content'];
	}

	// нужно сделать в media_manager возможность указать требуемый тип контента
	// и если файл другого типа чтобы возможности залить небыло
	private function set_file( $_strSysName='' ) {
		if ( !empty( $this->item_fields[$_strSysName]['obj'] )&&is_object( $this->item_fields[$_strSysName]['obj'] ) ) {
			if ( !$this->item_fields[$_strSysName]['obj']->m_set_filedata() ) {
				return false;
			}
			$this->item_fields[$_strSysName]['content']=$this->item_fields[$_strSysName]['obj']->m_file_id;
			return true;
		}
		// depercated!!! если чекер не использовался то система идёт этим путём нужно убрать TODO!!!
		if ( !$this->objS->cast_sysname_to_mediatype( $strType, $_strSysName ) ) {
			return false;
		}
		$objM=new Core_Media();
		$objM->d_set_typebytypename( $strType );
		if ( !empty( $this->fields_exists[$_strSysName]['content'] ) ) {
			$objM->m_set_file_id( $this->fields_exists[$_strSysName]['content'] );
		}
		if ( !$objM->m_set_file( $arrRes, $arrErr, $this->item_fields[$_strSysName], $this->item_fields[$_strSysName] ) ) {
			return false;
		}
		$this->item_fields[$_strSysName]['content']=$objM->m_file_id;
		return true;
	}

	// отдаёт айтем для текущей маски и пользователя ($_intId)
	// использовать в том случае если для данной маски подразумевается что есть только один айтем на каждого пользователя
	public function getByUserId( &$arrRes, $_intId=0 ) {
		if ( empty( $_intId ) ) {
			return false;
		}
		$_intRes=Core_Sql::getCell( 'SELECT id FROM '.$this->item_tbl.' WHERE stencil_id="'.$this->objS->stencil['id'].'" AND user_id='.Core_Sql::fixInjection( $_intId ).' LIMIT 1' );
		if ( empty( $_intRes ) ) {
			return false;
		}
		return $this->get_item( $arrRes, $_intRes );
	}

	public function get_item( &$arrRes, $_intId=0 ) {
		if ( empty( $_intId ) ) {
			return false;
		}
		$arrRes=Core_Sql::getRecord( 'SELECT * FROM '.$this->item_tbl.' WHERE id='.Core_Sql::fixInjection( $_intId ).' LIMIT 1' );
		return $this->get_item_fields_full( $arrRes['arrFields'], $arrRes['id'] );
	}

	public function isOwnItem( $_intId=0 ) {
		if ( empty( $_intId )||!Zend_Registry::get( 'objUser' )->getId( $_int ) ) {
			return false;
		}
		$_intRes=Core_Sql::getCell( 'SELECT 1 FROM '.$this->item_tbl.' WHERE id='.Core_Sql::fixInjection( $_intId ).' AND user_id='.$_int.' LIMIT 1' );
		return !empty( $_intRes );
	}

	public function get_item_masked_full( &$arrRes, $_intId=0, $_arrMask=array() ) {
		if ( empty( $_intId ) ) {
			return false;
		}
		$arrRes=Core_Sql::getRecord( 'SELECT * FROM '.$this->item_tbl.' WHERE id='.Core_Sql::fixInjection( $_intId ).' LIMIT 1' );
		if ( empty( $arrRes ) ) {
			return false;
		}
		return $this->get_item_fields_masked_full( $arrRes['arrFields'], $arrRes['id'], $_arrMask );
	}

	public function get_item_fields_masked_full( &$arrRes, $_intId=0, $_arrMask=array() ) {
		$this->get_item_fields_full( $_arrFields, $_intId, 'fields_short_with_extended_data', $_arrMask );
		if ( !empty( $_arrMask ) ) {
			foreach( $_arrMask as $v ) {
				$arrRes[$v]=empty( $_arrFields[$v] )? '':$_arrFields[$v];
			}
		} else {
			$arrRes=$_arrFields;
		}
		return !empty( $arrRes );
	}

	public function get_item_masked( &$arrRes, $_intId=0, $_arrMask=array() ) {
		if ( empty( $_intId ) ) {
			return false;
		}
		$arrRes=Core_Sql::getRecord( 'SELECT * FROM '.$this->item_tbl.' WHERE id='.Core_Sql::fixInjection( $_intId ).' LIMIT 1' );
		if ( empty( $arrRes ) ) {
			return false;
		}
		return $this->get_item_fields_masked( $arrRes['arrFields'], $arrRes['id'], $_arrMask );
	}

	public function get_item_fields_short( &$arrRes, $_intId=0 ) {
		return $this->get_item_fields_full( $arrRes, $_intId, 'fields_short' );
	}

	// если такого поля как в маске нету то оно якобы будет но пустое
	public function get_item_fields_masked( &$arrRes, $_intId=0, $_arrMask=array() ) {
		$this->get_item_fields_full( $_arrFields, $_intId, 'fields_short', $_arrMask );
		if ( !empty( $_arrMask ) ) {
			foreach( $_arrMask as $v ) {
				$arrRes[$v]=empty( $_arrFields[$v] )? '':$_arrFields[$v];
			}
		} else {
			$arrRes=$_arrFields;
		}
		return !empty( $arrRes );
	}

	public function get_item_fields_full( &$arrRes, $_intId=0, $_strGetType='fields_full', $_arrMask=array() ) {
		$_intId=empty( $_intId )?$this->item_id:$_intId;
		if ( empty( $_intId ) ) {
			return false;
		}
		$_strSql='
			SELECT s.sys_name, i.content, i.id, s.title, s.flg_type
			FROM '.$this->fields_tbl.' i
			INNER JOIN ci_stencil_field s ON s.id=i.stencil_field_id
			WHERE
				i.item_id="'.$_intId.'"'.(empty( $_arrMask )? '':' AND
				s.sys_name IN("'.join( '", "', $_arrMask ).'")').'
				'.( $this->with_hidden_fields?'':' AND s.flg_hidden=0' ).'
			ORDER BY s.flg_priority DESC, s.id
		';
		if ( $_strGetType=='fields_short' ) {
			$arrRes=Core_Sql::getKeyVal( $_strSql );
		} elseif ( $_strGetType=='fields_short_with_extended_data' ) {
			$arrRes=Core_Sql::getKeyVal( $_strSql );
			$this->get_item_files( $arrRes );
		} else {
			$arrRes=Core_Sql::getKeyRecord( $_strSql );
			$this->get_item_files( $arrRes );
		}
		return true;
	}

	public function del_item( $_intId=0 ) {
		if ( !$this->get_item_fields_short( $_arrFld, $_intId ) ) {
			return;
		}
		$this->del_item_files( $_arrFld );
		Core_Sql::setExec( '
			DELETE i, f
			FROM '.$this->item_tbl.' i
			LEFT JOIN '.$this->fields_tbl.' f ON f.item_id=i.id
			WHERE i.id="'.$_intId.'"
		' );
		return true;
	}

	private function clear_raw_by_stencil() {
		$this->item_fields=array_intersect_key( $this->item_fields, $this->objS->stencil['arrFields'] );
	}

	private function get_item_files( &$arrFields ) {
		if ( !$this->get_item_file_fields( $_arrFiles, $arrFields ) ) {
			return false;
		}
		// берём файлы из f_files через media_manager
		foreach( $_arrFiles as $k=>$v ) {
			if ( is_array( $v ) ) {
				$arrFields[$k]['file_info']=$this->get_item_file( $v['content'], $k );
			} else {
				$arrFields[$k]=$this->get_item_file( $v, $k );
			}
		}
		return true;
	}

	private function del_field() {
		if ( empty( $this->fields_to_delete ) ) {
			return false;
		}
		foreach( $this->fields_to_delete as $k=>$v ) {
			$_arrIds[]=$v['id'];
			$_arrFiles[$k]=$v['content'];
		}
		$this->del_item_files( $_arrFiles );
		Core_Sql::setExec( 'DELETE FROM '.$this->fields_tbl.' WHERE id IN("'.join( '", "', $_arrIds ).'")' );
		return true;
	}

	private function del_item_files( $_arrFld=array() ) {
		if ( !$this->get_item_file_fields( $_arrFiles, $_arrFld ) ) {
			return;
		}
		return $this->del_files_connector( $_arrFiles );
	}

	protected function del_files_connector( $_arrFiles=array() ) {
		if ( empty( $_arrFiles ) ) {
			return false;
		}
		$objM=new Core_Media();
		$objM->m_del_files( $_arrFiles );
		return true;
	}

	private function get_item_file( $intFileId, $_strSysName='' ) {
		if ( !$this->objS->cast_sysname_to_mediatype( $strType, $_strSysName ) ) {
			return false;
		}
		$objM=new Core_Media( array( 'flg_type'=>$strType, 'file_id'=>$intFileId ) );
		if ( !$objM->m_get_fileinfo( $arrRes ) ) {
			return false;
		}
		return $arrRes;
	}

	private function get_item_file_fields( &$arrRes, $_arrFields=array() ) {
		if ( empty( $_arrFields )||!$this->objS->get_current_field_formatted( $_arrStFields, array( 'files_fields'=>true ) ) ) {
			return false;
		}
		$arrRes=array_intersect_key( $_arrFields, $_arrStFields );
		return !empty( $arrRes );
	}

	public function get_item_id_byfield( &$_intId, $_strSysName='', $_strContent='' ) {
		if ( empty( $_strSysName )||empty( $this->objS->stencil['arrFields'][$_strSysName] ) ) {
			return false;
		}
		$_intId=Core_Sql::getCell( 'SELECT item_id FROM '.$this->fields_tbl.' 
			WHERE stencil_field_id='.$this->objS->stencil['arrFields'][$_strSysName]['id'].' AND content="'.$_strContent.'"
		' );
		return !empty( $_intId );
	}

	public function get_itembyfield( &$arrRes, $_strSysName='', $_strContent='' ) {
		if ( !$this->get_item_id_byfield( $_intId, $_strSysName, $_strContent ) ) {
			return false;
		}
		return $this->get_item( $arrRes, $_intId );
	}
}
?>