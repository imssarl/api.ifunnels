<?php

class Project_Wizard_Domain {

	private static $_arrX12=array();

	private static $_arrX36=array();

	private static $_arrTLD=array('x1'=>'.com','x2'=>'.info','x3'=>'.org','x4'=>'.net');

	private $_hosts=array();

	private $_strWord=false;

	private $_rules=false;

	public function __construct( $_type ){
		if(empty($_type)){
			throw new Project_Wizard_Exception('Type can\'t be empty');
		}
		$this->_rules=Project_Wizard_Domain_Rules::factory( $_type );
		self::$_arrX12=$this->_rules->get12();
		self::$_arrX36=$this->_rules->get36();
	}

	public function setTLD( $_str ){
		if( !isset( $_str ) ){
			return $this;
		}
		foreach( self::$_arrTLD as &$_tld ){
			$_tld=$_str;
		}
		return $this;
	}

	public function setWord( $_str ){
		if( empty($_str) ){
			throw new Project_Wizard_Exception('Can\'t find word: setWord()');
		}
		$this->_strWord=Core_String::removeAccents(trim(strtolower(Core_String::removeSpecChar($_str))));
		return $this;
	}

	public function get(){
		if( empty($this->_strWord) ){
			return 'error';
			throw new Project_Wizard_Exception('Can\'t find word: get()');
		}
		$this->getX8();
		$this->getX12();
		$this->getX36();
		return $this->_hosts;
	}

	public static function check( $_strDomain ){
		if( @$_SERVER['HTTP_HOST'] == 'cnm.local' ){
			return true;
		}
		$_check=new Project_Placement_Domen_Availability();
		$_placementDomain=new Project_Placement();
		return ( $_check->checkWhoisAvailability( $_strDomain ) && !$_placementDomain->withDomain( $_strDomain )->onlyOne()->getList( $_tmp )->checkEmpty() );
	}

	private function getX8(){
		$_hosts[]=str_replace( ' ','',$this->_strWord.self::$_arrTLD['x1'] );
		$_hosts[]=str_replace( ' ','-',$this->_strWord.self::$_arrTLD['x1'] );
		$_hosts[]=str_replace( ' ','',$this->_strWord.self::$_arrTLD['x2'] );
		$_hosts[]=str_replace( ' ','-',$this->_strWord.self::$_arrTLD['x2'] );
		$_hosts[]=str_replace( ' ','',$this->_strWord.self::$_arrTLD['x3'] );
		$_hosts[]=str_replace( ' ','-',$this->_strWord.self::$_arrTLD['x3'] );
		$_hosts[]=str_replace( ' ','',$this->_strWord.self::$_arrTLD['x4'] );
		$_hosts[]=str_replace( ' ','-',$this->_strWord.self::$_arrTLD['x4'] );
		$this->_hosts['x8']=array_unique($_hosts);
	}

	private function getX12(){
		$_index=0;
		$_hosts=array();
		$_rulles=$this->_rules->getKeywordX12();
		while( count($_hosts)<12&&$_index<1000 ){
			$_hosts[]=self::$_arrX12['x1'][array_rand(self::$_arrX12['x1'])].
					self::$_arrX12['x2'][array_rand(self::$_arrX12['x2'])].
					str_replace( $_rulles['search'], $_rulles['replace'], $this->_strWord ).
					self::$_arrX12['x3'][array_rand(self::$_arrX12['x3'])].
					self::$_arrTLD['x1'];
			$_hosts=array_unique($_hosts);
			$_index++;
		}
		$this->_hosts['x12']=$_hosts;
	}

	private function getX36(){
		$_index=0;
		$_hosts=array();
		$_rulles=$this->_rules->getKeywordX36();
		while( count($_hosts)<36&&$_index<4000 ){
			$_hosts[]=self::$_arrX36['x1'][array_rand(self::$_arrX36['x1'])].
					self::$_arrX36['x2'][array_rand(self::$_arrX36['x2'])].
					str_replace( $_rulles['search'], $_rulles['replace'], $this->_strWord ).
					self::$_arrX36['x3'][array_rand(self::$_arrX36['x3'])].
					self::$_arrTLD['x1'];
			$_hosts=array_unique($_hosts);
			$_index++;
		}
		$this->_hosts['x36']=$_hosts;
	}

}
?>