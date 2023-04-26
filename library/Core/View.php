<?php


/**
 * View factory
 */
class Core_View {

	// по хорошему 'recursion' и 'one' это какбы один тип - html или даже smarty TODO!!!11.08.2011
	public static $type=array(
		'recursion'=>0, // обычная обработка шаблона (может участвовать в рекурсии)
		'one'=>1, // обрабатываем только один шаблон
		'xml'=>2, // xml-сервис. выходной массив парсится в xml (soap,flash)
		'json'=>3, // js-сервис. выходной массив парсится в json-объект (например если нужно передать в js через ajax структурированные данные)
	);

	public static function factory( $_type=0 ) {
		switch( $_type ) {
			case self::$type['xml']: $adapter=new Core_View_Xml(); break;
			case self::$type['json']: $adapter=new Core_View_Json(); break;
			default: $adapter=new Core_View_Smarty(); break;
		}
		return $adapter;
	}
}
?>