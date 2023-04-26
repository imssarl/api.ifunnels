<?php

class Project_Wizard_Domain_Rules_Clickbank extends Project_Wizard_Domain_Rules_Abstract implements Project_Wizard_Domain_Rules_Interface {

	protected $_arrX12=array(
		'x1'=>array('review','buy','find',''),
		'x2'=>array('top','popular','cheap',''),
		'x3'=>array('training','products','store')
	);

	protected $_arrX36=array(
		'x1'=>array('compare','purchase','get',''),
		'x2'=>array('best','new','recommended',''),
		'x3'=>array('books','courses','dvds')
	);


	protected $_keywordX12=array('search'=>' ','replace'=>'');

	protected $_keywordX36=array('search'=>' ','replace'=>'');

}
?>