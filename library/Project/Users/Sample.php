<?php
/**
 * Project Users Extension
 * @category project
 * @package ProjectUsersExtension
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2008, Rodion Konnov
 * @author Rodion Konnov <kindzadza@mail.ru>
 * @date 21.11.2008
 * @version 1.0
 */


/**
 * Additional field checker interface
 * @category project
 * @package ProjectUsersExtension
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2008, Rodion Konnov
 * @author Rodion Konnov <kindzadza@mail.ru>
 * @date 21.11.2008
 * @version 1.0
 */


interface Project_Users_Sample {
	public function set_additional( &$arrErr, &$arrRes, $_arrDat=array() );
}
?>