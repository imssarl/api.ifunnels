<?php

class Project_Wizard_Domain_Rules_Authority extends Project_Wizard_Domain_Rules_Abstract implements Project_Wizard_Domain_Rules_Interface {

	protected $_arrX12=array(
		'x1'=>array('buy','find',''),
		'x2'=>array('top','popular','cheap',''),
		'x3'=>array('products')
	);

	protected $_arrX36=array(
		'x1'=>array('compare','purchase','get',''),
		'x2'=>array('best','new','recommended',''),
		'x3'=>array('books','gifts','courses','dvds')
	);

	protected $_keywordX12=array('search'=>' ','replace'=>'');

	protected $_keywordX36=array('search'=>' ','replace'=>'');

}
?>