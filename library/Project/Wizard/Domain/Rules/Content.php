<?php

class Project_Wizard_Domain_Rules_Content extends Project_Wizard_Domain_Rules_Abstract implements Project_Wizard_Domain_Rules_Interface {

	protected $_arrX12=array(
		'x1'=>array(),
		'x2'=>array('howto',''),
		'x3'=>array('articles','resources','blog','101','facts','news','insights','highlights')
	);

	protected $_arrX36=array(
		'x1'=>array(),
		'x2'=>array('howto-',''),
		'x3'=>array('-articles','-resources','-blog','-101','-facts','-news','-insights','-highlights')
	);

	protected $_keywordX12=array('search'=>' ','replace'=>'');

	protected $_keywordX36=array('search'=>' ','replace'=>'-');

}
?>