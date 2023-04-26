<?php
/**
 * WorkHorse Framework
 *
 * @category WorkHorse
 * @package ProjectSource
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2005-2011, Rodion Konnov
 * @author Rodion Konnov <kindzadza@mail.ru>
 * @date 14.03.2011
 * @version 2.0
 */


/**
 * Category management module
 *
 * @category WorkHorse
 * @package ProjectSource
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2005-2009, Rodion Konnov
 */
class category extends Core_Module {

	public final function set_cfg() {
		$this->inst_script=array(
			'module'=>array(
				'title'=>'Category',
			),
			'actions'=>array(
				array( 'action'=>'types', 'title'=>'Types management' ),
				array( 'action'=>'flags', 'title'=>'Category flags' ),
				array( 'action'=>'cats', 'title'=>'Category management' ),
			),
		);
	}

	public function types() {
		$types=new Core_Category_Type();
		if ( !empty( $_POST['arrTypes'] ) ) {
			if ( $types->setData( $_POST['arrTypes'] )->set() ) {
				$this->location();
			}
			$types
				->getEntered( $this->out['arrTypes'] )
				->getErrors( $this->out['arrErr'] );
		} else {
			$types->get( $this->out['arrTypes'] );
		}
		$this->out['arrSort']=Core_Category::$sort;
		$this->out['arrLink']=Core_Category::$link;
		$this->out['arrShema']=Core_Category::$shema;
	}

	public function flags() {
		if ( empty( $_GET['type_id'] ) ) {
			$this->location( array( 'action'=>'types' ) );
		}
		$flags=new Core_Category_Flag();
		if ( !$flags->setTypeById( $_GET['type_id'] ) ) {
			return;
		}
		if ( !empty( $_POST['arrFlags'] ) ) {
			if ( $flags->set( $this->out['arrFlags'], $this->out['arrErr'], $_POST['arrFlags'] ) ) {
				$this->location();
			}
		} else {
			$flags->get( $this->out['arrFlags'] );
		}
	}

	public function catsOuter() {
		if ( empty( $this->out['arrPrm']['category'] ) ) {
			return;
		}
		$type=new Core_Category_Type();
		if ( !$type->byTitle( $this->out['arrPrm']['category'] )->get( $this->out['arrType'] ) ) {
			return;
		}
		if ( $this->out['arrType']['type']=='nested' ) {
			$this->catsNested();
		} else {
			$this->catsOthers();
		}
	}

	public function cats() {
		if ( empty( $_GET['type_id'] ) ) {
			$this->location( array( 'action'=>'types' ) );
		}
		$type=new Core_Category_Type();
		if ( !$type->byId( $_GET['type_id'] )->get( $this->out['arrType'] ) ) {
			$this->location( array( 'action'=>'types' ) );
		}
		if ( $this->out['arrType']['type']=='nested' ) {
			$this->catsNested();
		} else {
			$this->catsOthers();
		}
	}

	private function catsNested() {
		$cat=new Core_Category( $this->out['arrType']['title'] );
		if ( !empty( $_POST['arrCats'] ) ) {
			if ( $cat->setPid( @$_GET['pid'] )->setData( $_POST['arrCats'] )->setCategory() ) {
				$this->location();
			}
			$cat
				->getEntered( $this->out['arrCats'] )
				->getErrors( $this->out['arrError'] );
		}
		if ( empty( $this->out['arrType']['flg_multilng'] ) ) {
			$cat->getLevel( $this->out['arrCats'], @$_GET['pid'] );
			$cat->getTree( $this->out['arrTree'] );
		} else {
			$cat->setMode( 'view' )->getTree( $this->out['arrTree'] );
			$cat->setMode( 'edit' )->getLevel( $this->out['arrCats'], @$_GET['pid'] );
		}
	}

	private function catsOthers() {
		$cat=new Core_Category( $this->out['arrType']['title'] );
		if ( !empty( $_POST['arrCats'] ) ) {
			if ( $cat->set( $this->out['arrCats'], $this->out['arrErr'], $_POST['arrCats'] ) ) {
				$this->location();
			}
		} else {
			$cat->get( $this->out['arrCats'], $this->out['arrPg'] );
		}
		$cat->getFlags( $this->out['arrFlags'] ); // для flagged
	}
}
?>