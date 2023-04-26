<?php


/**
 * identify by cookie
 */


class identifier extends Core_Services {
	public $i_uid=0; // значение которое будет хранится в куке
	public $i_interval=array(); // array( 'hour', 'day', 'year' )
	public $i_tname=''; // таблица где хранится уникальный номер
	public $i_cname=''; // кука где хранится тот же номер
	public $i_fld='flg_uid'; // поле в таблице с номером

	function identifier( $_arrSet=array() ) {
		if ( empty( $_arrSet ) ) {
			throw new Exception( Core_Errors::DEV.'|Can\'t run constructor in identifier class' );
		}
		foreach ( $_arrSet as $k=>$v ) {
			$this->$k=$v;
		}
	}
// идентификация клиента
	function identification( $_strAdd='' ) {
		if ( !empty( $_strAdd ) ) {
			$this->i_cname=$this->i_cname.'_'.$_strAdd;
		}
		if ( empty( $_SESSION[$this->i_cname] ) ) {
			$this->i_uid=$_SESSION[$this->i_cname]=!empty( $_COOKIE[$this->i_cname] ) ? $_COOKIE[$this->i_cname]:$this->set_uid();
		} else {
			$this->i_uid=$_SESSION[$this->i_cname];
		}
		return !empty( $this->i_uid );
	}
// в первый раз устанавливаем клиенту уникальный номер
	function set_uid() {
		if ( !empty( $this->i_tname ) ) { // если данные заносятся в таблицу то хранимый номер должен быть уникальным
			$this->get_table_uid();
		} elseif ( empty( $this->i_uid ) ) { // иначе любой (если не указан какой именно)
			$this->i_uid=Core_A::rand_int();
		}
		$this->set_cookie();
		return $this->i_uid;
	}

	function get_table_uid() {
		if ( !$this->sv_get_uniq_code_int( $this->i_uid, $this->i_fld, $this->i_tname ) ) {
			return false;
		}
		return true;
	}

// удаление ненужных кук
	function del_uid( $_strAdd='' ) {
		if ( !$this->identification( $_strAdd ) ) {
			return false;
		}
		unSet( $_SESSION[$this->i_cname] );
		return setcookie( $this->i_cname, '', time()-3600, '/' );
	}

	function check() {
		return !empty( $_COOKIE[$this->i_cname] );
	}

	function set_cookie() {
		setcookie( $this->i_cname, $this->i_uid, mktime( 
			date("H")+$this->i_interval['hour'], 
			date("m"), date("s"), date("m"), 
			date("d")+$this->i_interval['day'], 
			date("Y")+$this->i_interval['year']
		 ), '/' );
	}
}
?>