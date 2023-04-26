<?php

abstract class Project_Wizard_Domain_Rules_Abstract {
	protected $_arrX12=array(
		'x1'=>array(),
		'x2'=>array(),
		'x3'=>array()
	);

	protected $_arrX36=array(
		'x1'=>array(),
		'x2'=>array(),
		'x3'=>array()
	);

	protected $_keywordX12=array();

	protected $_keywordX36=array();

	/**
	 * Return keyword rules for X12
	 * @return array
	 */
	public function getKeywordX12(){
		return $this->_keywordX12;
	}

	/**
	 * Return keyword rules for  X36
	 * @return array
	 */
	public function getKeywordX36(){
		return $this->_keywordX36;
	}

	/**
	 * Return rules X12
	 * @return array
	 */
	public function get12(){
		return $this->_arrX12;
	}

	/**
	 * Return rules X36
	 * @return array
	 */
	public function get36(){
		return $this->_arrX36;
	}
}
?>