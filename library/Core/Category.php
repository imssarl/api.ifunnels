<?php
class Core_Category {

	public static $shema=array(
		'simple'=>'Simple',
		'flagged'=>'Flagged',
		'nested'=>'Nested',
	);
	public static $sort=array( 
		'category title', 
		'category priority' );
	public static $user=array( 
		'without user_id', 
		'include user_id' );
	// линки надо реализовать - сейчас храню их не в категориях (может так и оставить?)
	public static $link=array( 
		'one to one', 
		'one to many' );

	private $_driver;
	private $_type;

	public function __construct( $_type='' ) {
		$this->initCategory( $_type );
	}

	protected function initCategory( $_type='' ) {
		if ( empty( $_type ) ) {
			throw new Exception( Core_Errors::DEV.'|Category type not found' );
			return;
		}
		$_obj=new Core_Category_Type();
		if ( !$_obj->byTitle( $_type )->get( $this->_type ) ) {
			throw new Exception( Core_Errors::DEV.'|Type '.$_type.' not found' );
			return;
		}
		switch( $this->_type['type'] ) {
			case 'simple': $this->_driver=new Core_Category_Driver_Simple( $this->_type ); break;
			case 'flagged': $this->_driver=new Core_Category_Driver_Flagged( $this->_type ); break;
			case 'nested': $this->_driver=new Core_Category_Driver_Nested( $this->_type ); break;
			default: throw new Exception( Core_Errors::DEV.'|Category driver not found' ); break;
		}
	}

	public function getDriver() {
		return $this->_driver;
	}

	public function getType( &$arrRes ) {
		$arrRes=$this->_type;
		return $this;
	}

	public function setMode( $_str='' ) {
		return $this->_driver->setMode( $_str );
	}

	public function getLng() {
		return $this->_driver->getLng();
	}

/* только для Core_Category_Driver_Nested драйвера */

	public function getTree( &$arrRes, $_intPid=0 ) {
		if ( get_class( $this->_driver )!='Core_Category_Driver_Nested' ) {
			return;
		}
		return $this->_driver->getTree( $arrRes, $_intPid );
	}

	public function getLevel( &$arrRes, $_intPid=0 ) {
		return $this->_driver->getLevel( $arrRes, $_intPid );
	}

	public function setPid( $_intPid ) {
		return $this->_driver->setPid( $_intPid );
	}



	public function getFlags( &$arrRes ) {
		if ( get_class( $this->_driver )!='Core_Category_Driver_Flagged' ) {
			return;
		}
		$this->_driver->getFlags( $arrRes );
	}

	public function byTitle( $_str='' ) {
		return $this->_driver->byTitle( $_str );
	}

	public function levelMore( $_str='' ) {
		return $this->_driver->levelMore( $_str );
	}

	public function withFlags( $_arr=array() ) {
		return $this->_driver->withFlags( $_arr );
	}

	public function withoutFlags( $_arr=array() ) {
		return $this->_driver->withoutFlags( $_arr );
	}

	public function toSelect() {
		return $this->_driver->toSelect();
	}

	public function byId( $_mix=0 ) {
		return $this->_driver->byId( $_mix );
	}

	public function withPagging( $_arr=array() ) {
		return $this->_driver->withPagging( $_arr );
	}

	public function get( &$mixRes, &$arrPg ) {
		return $this->_driver->get( $mixRes, $arrPg );
	}

	public function set( &$arrRes, &$arrErr, $_arrData=array() ) {
		return $this->_driver->set( $arrRes, $arrErr, $_arrData );
	}

	public function del( $_mixId=array() ) {
		return $this->_driver->del( $_mixId );
	}

	// для Core_Category_Driver_Nested, переделать в таком же ключе остальные драйвера TODO!!!
	public function setCategory() {
		return $this->_driver->setCategory();
	}

	public function setData( $_arrData=array() ) {
		return $this->_driver->setData( $_arrData );
	}

	public function getEntered( &$arrRes ) {
		return $this->_driver->setData( $arrRes );
	}

	public function getErrors( &$arrRes ) {
		return $this->_driver->setData( $arrRes );
	}

	public function getList( &$arrRes ) {
		return $this->_driver->getList( $arrRes );
	}
}
?>