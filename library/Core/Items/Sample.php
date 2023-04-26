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
 * Sample
 * @internal интерфейс для поддержки удаления масок и полей из stencil
 * @category framework
 * @package CompositeItems
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2008, Rodion Konnov
 * @author Rodion Konnov <kindzadza@mail.ru>
 * @date 06.02.2008
 * @version 0.1
 */


interface Core_Items_Sample {
	public function del_items_bymask();
	public function del_fields_bymask( $_arrIds=array() );
}
?>