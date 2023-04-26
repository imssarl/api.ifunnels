<?php
class Core_Tags {

	private $_type=0;
	private $_item=0;
	private $_tags=array();
	private $_user=0;

	public function __construct( $_strType='' ) {
		Core_Sql::singleMode();
		$this->_type=Core_Tags_Types::getInstance()->getTypeByTitle( $_strType );
		Core_Sql::replicationMode();
	}

	// тэги на айтемы не обязательно пользовательские
	// если хотим пользовательские надо дополнительно дёрнуть этот метод
	public function setUser() {
		if ( Zend_Registry::get( 'objUser' )->getId( $_int ) ) {
			$this->_user=$_int;
		}
		return $this;
	}

	public function setItem( $_int=0 ) {
		$this->_item=0;
		if ( empty( $_int ) ) {
			return $this;
		}
		$this->_item=$_int;
		return $this;
	}

	// вся пачка тэгов для $this->_item
	public function setTags( $_str='' ) {
		$this->_tags=array();
		if ( !self::encode( $_arrRes, $_str ) ) {
			return $this;
		}
		$this->_tags=$_arrRes;
		return $this;
	}

	// преобразование во внутренний формат хранилища тэгов
	public static function encode( &$arrRes, $_strTags='' ) {
		if ( empty( $_strTags ) ) {
			return false;
		}
		$arrRes=array_unique( explode( ', ', preg_replace( 
			/* input - '12, , , , , , 234, test, , asdas aa234 , ds^&*9da sd, sd, , dsas@4aad, , #@#$, , ds, , , dd' */
			array(
				'/[^\w\d\s,]/u', /*удаляем всё кроме*/
				'/[\s]{2,}/u', /*все пробелы приводим к единичным*/
				'/([\w\d])\s+([\w\d])/u', /*пробелы между \w\d заменяем на _*/
				'/([,\s]+)[\w\d]{1,2}(?=[,\s]+)/u', /*если [,\s]+ после которой меньше 3-х символов \w\d\s за которыми запятая или пробел, (последнее не включатся в результат)*/
				'/[,\s]+/iu', /*пробелы и запятые между \w\d заменяем на , */
				'/^[,\s]*(.+[\w\d])[,\s]*$/u', /*удаляем пробелы и запятые в начале и конце строки*/
				'/^[\w\d]{1,2}, (.+)$/u', /*удаляем тэги состоящие мнее чем из 3-х символов в начале строки*/
				'/^(.+), [\w\d]{1,2}$/u', /*удаляем тэги состоящие мнее чем из 3-х символов в конце строки*/
			), 
			array( '', ' ', '$1_$2', '$1$2', ', ', '$1', '$1', '$1' ),
			/* output - '234, test, asdas_aa234, ds9da_sd, dsas4aad' */
			trim( str_replace( ',', ', ', mb_strtolower( str_replace( array( "\r\n", "\n\r", "\n", "\r" ), ',', $_strTags ),'UTF8' ) ) )
		) ) );
		return !empty( $arrRes );
	}

	// удаление всех линков на тэги для данного айтема
	public function del() {
		if ( empty( $this->_item ) ) {
			return false;
		}
		Core_Sql::setExec( 'DELETE FROM tag_link WHERE type_id='.$this->_type.' AND item_id='.$this->_item );
		return true;
	}

	// удаление линка на определённый тэг
	public function delByTag( $_intTagId=0 ) {
		if ( empty( $_intTagId ) ) {
			return false;
		}
		$_strSql='DELETE FROM tag_link WHERE type_id='.$this->_type.' AND item_id='.$this->_item.' AND tags_id='.$_intTagId;
		if ( !empty( $this->_user ) ) { // если тэги пользовательские значит удалить их может только пользователь (или админ TODO!!!)
			$_strSql.=' AND user_id='.$this->_user;
		}
		Core_Sql::setExec( $_strSql );
		return true;
	}

	public function get( &$arrRes ) {
		if ( empty( $this->_item ) ) {
			return false;
		}
		Core_Sql::singleMode();
		$arrRes=Core_Sql::getAssoc( '
			SELECT c.id, c.tag, IF(INSTR(c.tag, "_"),REPLACE(c.tag,"_"," "),c.tag) decoded
			FROM tag_link l
			INNER JOIN tag_content c ON c.id=l.tags_id
			WHERE l.type_id='.$this->_type.' AND l.item_id='.$this->_item
		 );
		Core_Sql::replicationMode();
		return true;
	}

	public function set() {
		if ( empty( $this->_item )||empty( $this->_tags ) ) {
			return false;
		}
		$this->add(); // добавляем тэги в словарь
		$this->getHash( $_arrRes ); // достаём ids тэгов. для нового набора тэгов для данного айтема
		if ( empty( $_arrRes ) ) {
			throw new Exception( Core_Errors::DEV.'|Empty tags hash.' );
			return false;
		}
		$this->cacheLinkOwner()->del(); // удаляем старый набор тэгов для данного айтема
		foreach( $_arrRes as $k=>$v ) {
			$arrIns[$k]=array(
				'tags_id'=>$k, 
				'item_id'=>$this->_item, 
				'type_id'=>$this->_type, 
				'user_id'=>$this->getOwner( $k ),
			);
		}
		Core_Sql::setMassInsert( 'tag_link', $arrIns ); // сохраняем новый набор тэгов для данного айтема
		return true;
	}

	// кэшируем предыдущих владельцев линки на тэги.
	// актуально только для пользовательских тэгов (т.е. для линков где указан владелец)
	private function cacheLinkOwner() {
		if ( empty( $this->_user ) ) {
			return $this;
		}
		Core_Sql::singleMode();
		$this->_cache=Core_Sql::getKeyVal( 'SELECT tags_id, user_id FROM tag_link WHERE type_id='.$this->_type.' AND item_id='.$this->_item );
		Core_Sql::replicationMode();
		return $this;
	}

	// для закешированных владельцев используем значения из кэша
	// для новых линков используем текущего пользователя в качестве владельца
	// либо 0 для тэгов 
	private function getOwner( $_intTagId=0 ) {
		if ( empty( $this->_user ) ) {
			return 0;
		}
		return ( empty( $this->_cache[$_intTagId] )? $this->_user:$this->_cache[$_intTagId] );
	}

	// вытаскиваем имеющиеся пары для данного типа
	private function getHash( &$arrRes ) {
		Core_Sql::singleMode();
		$arrRes=Core_Sql::getKeyVal( 'SELECT id, tag FROM tag_content WHERE tag IN('.Core_Sql::fixInjection( $this->_tags ).')' );
		Core_Sql::replicationMode();
		return !empty( $arrRes );
	}

	// сохренение тэгов в словарь
	public function add() {
		$_arrAdd=$this->_tags;
		if ( $this->getHash( $_arrExists ) ) {
			$_arrAdd=array_diff( $this->_tags, $_arrExists );
		}
		if ( empty( $_arrAdd ) ) { // новых тэгов не обнаружено
			return;
		}
		foreach( $_arrAdd as $v ) {
			$arrIns[]=array( 'tag'=>$v, 'added'=>time() );
		}
		Core_Sql::setMassInsert( 'tag_content', $arrIns );
	}

	private $_computeStat=false;

	public function setComputeStat() {
		$this->_computeStat=true;
	}

	// т.к. для статистики важен не только тэг но и тип тэга я решил вынести статистику в отдельную табличку с type_id и tags_id см.схему
	// доделать статистику TODO!!!
	// обновление статистики по тэгам если требуется
	// например что бы посмотреть тэги по которым искали последнее время (search_last)
	// или тэги по которым чаще всего ищут (search_num)
	private function updateStat() {
		if ( !$this->_computeStat ) {
			return;
		}
		// если нужно тут можно сделать какие-то настройки по кэшированию
		// напрмер обновлять раз в час или только один раз за сессию пользователя
		// вобщем вариантов может быть много
		// как появится необходимость в статистике - сделаем TODO!!! 17.03.2011
		//Core_Sql::setExec( 'UPDATE tag_content SET search_num=search_num+1, search_last=UNIX_TIMESTAMP() WHERE tag IN('.Core_Sql::fixInjection( $this->_tags ).')' );
	}

	// вложенный запрос для поиска айтомов которые имеют те-же тэги что и выбранные ($_arrIds)
	// т.е. совпадают по тэгам
	public function getSearchRelatedQuery( &$strRes ) {
		if ( empty( $this->_item ) ) {
			return false;
		}
		// надо отрефакторить чтобы избавиться от подзапроса. так видимо будет тормозить на больших объёмах TODO!!!
		// сделать как в getSearchQuery
		$strRes='SELECT item_id FROM tag_link WHERE 
			type_id='.$this->_type.' AND 
			item_id NOT IN('.Core_Sql::fixInjection( $this->_item ).') AND 
			tags_id IN(
				SELECT tags_id FROM tag_link WHERE type_id='.$this->_type.' AND item_id IN('.Core_Sql::fixInjection( $this->_item ).') GROUP BY tags_id)';
		return true;
	}

	// ids айтемов найденных через getSearchRelatedQuery
	public function getRelatedItems( &$arrIds ) {
		if ( !$this->getSearchRelatedQuery( $_strSql ) ) {
			return false;
		}
		Core_Sql::singleMode();
		$arrIds=Core_Sql::getField( $_strSql );
		Core_Sql::replicationMode();
		return !empty( $arrIds );
	}

	// вложенный запрос для поиска айтемов по тэгам
	public function getSearchQuery( &$strRes ) {
		if ( empty( $this->_tags ) ) {
			return false;
		}
		$this->updateStat();
		$strRes='SELECT l.item_id FROM tag_link l
			INNER JOIN tag_content c ON l.tags_id=c.id 
			where l.type_id='.$this->_type.' AND c.tag IN('.Core_Sql::fixInjection( $this->_tags ).')';
		return true;
	}

	// ids айтемов найденных по $this->_tags
	public function getItemsByTags( &$arrIds ) {
		if ( !$this->getSearchQuery( $_strSql ) ) {
			return false;
		}
		Core_Sql::singleMode();
		$arrIds=Core_Sql::getField( $_strSql );
		Core_Sql::replicationMode();
		return !empty( $arrIds );
	}
}
?>