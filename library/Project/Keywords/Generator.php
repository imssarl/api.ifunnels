<?php

class Project_Keywords_Generator {

	private $user_raw_data=array();
	private $user_data=array();
	private $modes=array();
	private $available=array();
	private $checked=array();
	private $num_of_boxes=6;
	private $tablePrefix = "hct_";

	
	public function set_data( $_arr=array() ) {
		if ( empty( $_arr )||!$this->parse_input( $_arr ) ) {
			return false;
		}
		return true;
	}

	private function parse_input( $_arr ) {
		foreach( $_arr as $k=>$v ) {
			$arrRes[$k]=$this->parse_into_array( $v['keywords'] );
			if ( empty( $arrRes[$k] ) ) {
				continue;
			}
			$this->available[]=$k;
			if ( !empty( $v['check'] ) ) {
				$this->checked[]=$k;
			}
		}
		if ( !empty( $this->available ) ) {
			$this->user_raw_data=$_arr;
			$this->user_data=$arrRes;
			$this->possible_modes();
		}
		return !empty( $this->available );
	}

	private function possible_modes() {
		// если выбрано 1 и 2
		$this->modes[]=$this->available; // 1+2+3+4
		if ( empty( $this->checked ) ) {
			return;
		}
		$this->modes[]=$_arr=array_diff( $this->available, $this->checked ); // 3+4
		if ( count( $this->checked )==1 ) { // чтобы не повторять $this->available
			return;
		}
		foreach( $this->checked as $v ) {
			$this->modes[]=array_merge( array( $v ), $_arr ); // 1+3+4, потом 2+3+4
		}
	}

	private function parse_into_array( $_str='' ) {
		if ( empty( $_str ) ) {
			return array();
		}
		return explode( ', ', str_replace( array( "\r\n", "\n\r", "\n", "\r" ), ', ', trim( $_str ) ) );
	}

	public function get_data() {
		if ( empty( $this->user_raw_data ) ) {
			return array_fill( 1, $this->num_of_boxes, array( 'keywords'=>'' ) );
		}
		return $this->user_raw_data;
	}

	public function get_result($left = '', $right = '') {
		if ( empty( $this->user_data ) ) {
			return '';
		}
		$_str="";
		foreach( $this->modes as $v ) {
			$_arrRes = $this->generate( $v ); 
			if ( !empty( $_arrRes ) ) {
				$_str.=join( "\n", $_arrRes )."\n";
			}
		}
		$_str = preg_replace('/(.*?)(\n)/i',$left.'$1'.$right.'$2', $_str);
		return $_str;
	}

	private function generate( $_arrMode=array() ) {
		$arrRes=array();
		foreach( $_arrMode as $v ) {
			if ( empty( $this->user_data[$v] ) ) {
				continue;
			}
			$arrRes=$this->merge( $arrRes, $this->user_data[$v] );
		}
		return $arrRes;
	}

	private function merge( $_arrRes, $_arrNew=array() ) {
		if ( empty( $_arrRes ) ) {
			return $_arrNew;
		}
		foreach( $_arrRes as $k ) {
			foreach( $_arrNew as $v ) {
				$arrRes[]=$k.' '.$v;
			}
		}
		return $arrRes;
	}

	public function get_file() {
		$_str=$_POST['result'];
		if ( empty( $_str ) ) {
			return false;
		}
		$_file=new Core_Files();
		if ( !$_file->getExtension( $_POST['name'] ) ) {
			$_POST['name'] .= '.txt';
		}
		ob_end_clean();
		set_time_limit(0);
		header( 'HTTP/1.1 200 OK' );
		header( 'Content-Length: '.strval(strlen($_str)) );
		header( 'Content-Type:  text/plain; charset="utf8"' );
		header( 'Content-Disposition: attachment; filename="'.(empty($_POST['name'])?'keywords.txt':$_POST['name']).'"' );
		header( '' );
		print  $_str;
		exit;

	}

	public function insertKeywords($user_id){

		$title  = $_POST['title'] ? htmlspecialchars($_POST['title']) : 'empty title';
		$data = array(
			"list_title" => $title,
			"user_id"	 => $user_id
		);
		$id	= Core_Sql::setInsert($this->tablePrefix.'kwd_savedlist',$data);
		
		$insert = array();
		$keywords = explode("\n" , $_POST['result']);		
		foreach ($keywords as $k => $v)
		{
			if ($v)
			{
				$insert[] = array("keyword" => $v, "list_id" => $id);
			}
		}
		
		if (count($insert)) 
		{
			Core_Sql::setMassInsert($this->tablePrefix.'kwd_savedkwds', $insert);
		}

		header("location: /kwdresearch/savedkwds.php");
	}
	
	public function getSavedList( &$arrRes ){
		if ( !Zend_Registry::get( 'objUser' )->getId( $_int ) ) {
			throw new Exception( Core_Errors::DEV.'|Zend_Registry::get( \'objUser\' )->getId( $_int ) is not return an User Id' );
			return;
		}		
		$arrRes = Core_Sql::getAssoc('SELECT * FROM ' . $this->tablePrefix.'kwd_savedlist WHERE user_id='.$_int );
		return !empty($arrRes);
	}
	/**
	 * Get keywords
	 *
	 * @param array $_mixId
	 * @return array
	 */
	public function getKeywords( $_arrId ){
		if ( empty($_arrId) ){
			return array();
		}
		$arrRes=Core_Sql::getAssoc("SELECT keyword FROM {$this->tablePrefix}kwd_savedkwds WHERE list_id IN ( ".join( ',', $_arrId )." )");
		if (empty($arrRes)){
			return array();
		}
		return $arrRes;
	}

}

?>