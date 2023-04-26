<?php
/**
 * WorkHorse Framework
 *
 * @category WorkHorse
 * @package ProjectSource
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2005-2009, Rodion Konnov
 * @author Rodion Konnov <kindzadza@mail.ru>
 * @date 24.04.2009
 * @version 1.0
 */


/**
 * Typical first started backend module
 *
 * @category WorkHorse
 * @package ProjectSource
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2005-2009, Rodion Konnov
 */
class backend extends Core_Module {

	public final function set_cfg() {
		$this->inst_script=array(
			'module'=>array( 'title'=>'Control panel', ),
			'actions'=>array(),
			'tables'=>array(),
		);
	}

	public final function before_run_parent() {
		$this->startModule( array( 'name'=>'configuration' ) ); // инсталлируем модуль чтобы можно было управлять модулями
		$this->out['arrMenu']=Core_Acs::haveUrlTreeAccess( $this->objMR->getCurrentTree() );
		$this->out['arrF']=$this->objMR->frontends;
	}
}
?>