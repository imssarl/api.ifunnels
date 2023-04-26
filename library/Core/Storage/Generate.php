<?php
class Core_Storage_Generate {

	public $object;

	private $_needToGenerate=array();

	public static $types=array(
		'textarea'=>'text', 
		'htmleditor'=>'text', 
		'text'=>'text', /*поидее текстовое поле не ограничено 255 байтами*/
		'hidden'=>'text', /*в скрытом поле может быть любая по величине инфа*/
		'yes_no'=>'tinyint(1) tinyint(1) unsigned NOT NULL DEFAULT "0"', /*тут значение 0,1,2*/
		'radio'=>'int(11) unsigned NOT NULL DEFAULT "0"', /*тут одно значение в списке поидее будут только ids? поэтому int11*/
		'calendar'=>'int(11) unsigned NOT NULL DEFAULT "0"', /*дату-время храним в unixtime формате в int11*/
		'select'=>'int(11) unsigned NOT NULL DEFAULT "0"', /*тут может быть одно значение*/
		'file'=>'int(11) unsigned NOT NULL DEFAULT "0"', /*id из хранилища файлов*/
	);
//		'checkbox'=>'link', /*вообще тут массив значений, должен хранится в таблице связке*/
//		'multiselect'=>'link',

	public static $standart=array( 
		'id'=>'int(11) unsigned NOT NULL AUTO_INCREMENT', 
		'user_id'=>'int(11) unsigned NOT NULL DEFAULT "0"', 
		'flg_hide'=>'tinyint(1) unsigned NOT NULL DEFAULT "0"', 
		'flg_rate'=>'tinyint(1) unsigned NOT NULL DEFAULT "0"', 
		'flg_comment'=>'tinyint(1) unsigned NOT NULL DEFAULT "0"', 
		'flg_tagged'=>'tinyint(1) unsigned NOT NULL DEFAULT "0"', 
		'edited'=>'int(11) unsigned NOT NULL DEFAULT "0"', 
		'added'=>'int(11) unsigned NOT NULL DEFAULT "0"', 
	);

	public function __construct( Core_Dataset_Object $_obj ) {
		$this->object=$_obj;
	}

	// возможно имеет смысл сделать флаг в таблице dataset и проверять таблицы и поля один раз? TODO!!! 19.01.2010
	public function checkDatasetTable() {
		if ( !$this->checkTableExists()&&!$this->generateTable() ) {
			return false;
		}
		if ( !$this->checkFieldExists()&&!$this->addFields() ) {
			return false;
		}
		return true;
	}

	private function checkTableExists() {
		$_strRes=Core_Sql::getCell( 'SHOW TABLES LIKE "'.$this->object->getTable().'"' );
		if ( $_strRes===false ) {
			return false;
		}
		return true;
	}

	// генарация новой таблицы с системными полями
	private function generateTable() {
		foreach( self::$standart as $k=>$v ) {
			$_arrSql[]='`'.$k.'` '.$v;
		}
		$_arrSql[]='PRIMARY KEY (`id`)';
		Core_Sql::setExec( 'CREATE TABLE `'.$this->object->getTable().'` ('.join( ', ', $_arrSql ).') ENGINE=MyISAM DEFAULT CHARSET=utf8' );
		return $this->checkTableExists();
	}

	private function checkFieldExists() {
		$_arrRes=Core_Sql::getAssoc( 'SHOW COLUMNS FROM '.$this->object->getTable() );
		$_arr=$this->object->getTableFieldsFull();
		foreach( $_arrRes as $v ) {
			if ( isSet( $_arr[$v['Field']] ) ) {
				unSet( $_arr[$v['Field']] );
			}
		}
		$this->_needToGenerate=$_arr;
		return empty( $_arr );
	}

	// генерация недостающих полей и вставка в таблицу
	private function addFields() {
		$_arrFields=$this->object->getOnlyFields();
		foreach( $this->_needToGenerate as $k=>$v ) {
			// обработка стандартных полей
			if ( !empty( self::$standart[$k] ) ) {
				if ( $k=='id' ) {
					$_arrSql[]='ADD COLUMN `'.$k.'` '.self::$standart[$k].' FIRST, ADD PRIMARY KEY (id)';
					$_strAfterSys=$k;
					continue;
				}
				// это если поле id в таблице уже есть и надо добавить другие стандартные поля
				if ( empty( $_strAfterSys ) ) {
					foreach( self::$standart as $key=>$val ) {
						if ( $key==$k ) {
							$_strAfterSys=$_strKey;
							break;
						}
						$_strKey=$key;
					}
				}
				$_arrSql[]='ADD COLUMN `'.$k.'` '.self::$standart[$k].' AFTER '.$_strAfterSys;
				$_strAfterSys=$k;
				continue;
			}
			// поля из набора данных
			if ( empty( $_strAfter ) ) {
				$_strAfter='flg_tagged';
			}
			$_arrSql[]='ADD COLUMN `'.$k.'` '.self::$types[$_arrFields[$k]['type']].' AFTER '.$_strAfter;
			$_strAfter=$k;
		}
		if ( !empty( $_arrSql ) ) {
			Core_Sql::setExec( 'ALTER TABLE '.$this->object->getTable().' '.join( ', ', $_arrSql ) );
		}
		return $this->checkFieldExists();
	}
}
?>