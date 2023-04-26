<?php

class Project_Wizard_Domain_Rules_Video extends Project_Wizard_Domain_Rules_Abstract implements Project_Wizard_Domain_Rules_Interface {

	protected $_arrX12=array(
		'x1'=>array(),
		'x2'=>array('videoson','popular','watch','best','hot',''),
		'x3'=>array('videos','videoselection','')
	);

	protected $_arrX36=array(
		'x1'=>array(),
		'x2'=>array('videoson-','popular-','watch-','best-','hot-',''),
		'x3'=>array('-videos','-videoselection','')
	);

	protected $_keywordX12=array('search'=>' ','replace'=>'');

	protected $_keywordX36=array('search'=>' ','replace'=>'-');

}
?>