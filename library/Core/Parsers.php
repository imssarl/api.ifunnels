<?php
/**
 * Parsers
 * @category framework
 * @package Parsers
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2008, Rodion Konnov
 * @author Rodion Konnov <kindzadza@mail.ru>
 * @date 09.04.2009
 * @version 2.0
 */


class Core_Parsers {

	public static function viewAsXml( &$_arr ) {
		if ( empty( $_arr ) ) {
			return false;
		}
		$objX=new Core_Parsers_Xml( array( 'xml_format'=>'2.0' ) );
		$objX->array2xml( $strXml, $_arr );
		header( 'Content-Type: text/xml; charset="'.$objX->out_data.'"');
		header( 'Content-Length: '.strval(strlen($strXml)));
		ob_end_clean();
		echo $strXml;
		return true;
	}

	// если экшен такого типа вызывать сразу модуль экшен нужный
	// не доводить до смарти TODO!!!18.03.2011
	public static function viewAsJson( &$_arr ) {
		$strJson=Zend_Registry::get( 'CachedCoreString' )->php2json( $_arr );
		header( 'Content-Type: text/javascript' );
		header( 'Content-Length: '.strval(strlen($strJson)));
		ob_end_clean();
		echo $strJson;
		return true;
	}

	public static function viewAsHtml( &$_arr, $strTpl='' ) {
		ob_end_clean();
		echo self::getParsedHtml( $_arr, $strTpl );
		return true;
	}

	public static function getParsedHtml( &$_arr, $strTpl='' ) {
		$smarty=new Core_Parsers_Smarty();
		$smarty->template( $strHtml, $_arr, $strTpl );
		return $strHtml;
	}
}
?>