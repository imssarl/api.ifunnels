<?php

/**
 * Project_Tags
 */

class Project_Tags {

	protected static $_table='tags';
	protected $_fields=array('id', 'tag');

	public function __construct() {}
    
    public static function get( $ids ){
		if( empty( $ids ) ){
			return '';
		}
		$_ids=$ids;
		if( is_string( $_ids ) ){
			$_ids=array_filter( explode( ',', $_ids ) );
		}
		foreach( $_ids as $key => $value ){
			if( !ctype_digit( $value ) && !is_int( $value ) ){
				return $_ids;
			}		
		}
		return Core_Sql::getKeyVal( "SELECT * FROM ".self::$_table." WHERE id IN (".Core_Sql::fixInjection( $_ids ).")" );
	}

    public static function set( $tags ){
		if( empty( $tags ) ){
			return '';
		}
		$_tagsNames=$tags;
		if( is_string($tags) && strpos( $tags, ',' ) !== false ){
			$_tagsNames=explode( ',', $tags );
			foreach( $_tagsNames as &$_tagN ){
				$_tagN=trim( $_tagN, "\t\n\r\0\x0B '" );
			}
		}
		
		$ids2Tags=Core_Sql::getKeyVal( 'SELECT id, tag FROM '.self::$_table.' WHERE tag IN ('.Core_Sql::fixInjection( $_tagsNames ).')' );
		if( is_array( $_tagsNames ) ){
			foreach( $_tagsNames as $_newTag ){
				if( !in_array( str_replace( "'", "", $_newTag ), $ids2Tags ) && !empty( $_newTag ) ){
					$ids2Tags[Core_Sql::setInsert( self::$_table, array( 'tag' => str_replace( "'", "", $_newTag ) ) )] = str_replace( "'", "", $_newTag );
				}
			}
		} else {
			if( !in_array( $tags, $ids2Tags ) ){
				$ids2Tags[Core_Sql::setInsert( self::$_table, array( 'tag' => $tags ) )] = $tags;
			}
		}
		if( empty( $ids2Tags ) ){
			return '';
		}
		return ','.implode(',',array_keys( $ids2Tags )).',';
    }
	
    public static function check( $tags ){
		if( empty( $tags ) ){
			return '';
		}
		$_tagsNames=$tags;
		$_sqlSearch=array();
		if( is_string($tags) && strpos( $tags, ',' ) !== false ){
			$_tagsNames=explode( ',', $tags );
			foreach( $_tagsNames as &$_tagN ){
				$_tagN=trim( $_tagN, "\t\n\r\0\x0B '" );
				$_sqlSearch[]='tag LIKE "%'.$_tagN.'%"';
			}
		}else{
			$_sqlSearch[]='tag LIKE "%'.$tags.'%"';
		}
		$ids2Tags=Core_Sql::getKeyVal( 'SELECT id, tag FROM '.self::$_table.' WHERE '.implode(' OR ',$_sqlSearch) );
		if( empty( $ids2Tags ) ){
			return '';
		}
		return ','.implode(',',array_keys( $ids2Tags )).',';
	}
	
	public static function getList( &$mixRes ){
		$mixRes = Core_Sql::getAssoc( 'SELECT * FROM '.self::$_table );
	}

	public static function install(){
        Core_Sql::setExec(
            "CREATE TABLE IF NOT EXISTS `tags` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `tag` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_unicode_ci',
                UNIQUE INDEX `id` (`id`)
            )"
        );
	}
}
?>