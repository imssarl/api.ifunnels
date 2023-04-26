<?php

class Project_Options_Encode 
{
	static public function encode($originalStr)
	{
		$encodedStr = $originalStr;
		$num = mt_rand(0,5);
		for($i=1;$i<=$num;$i++)
		{
			$encodedStr = base64_encode($encodedStr);
		}
		
		$seed_array = array('S','H','A','F','I','Q');
		$encodedStr = $encodedStr . "+" . $seed_array[$num];
		$encodedStr = base64_encode($encodedStr);
		
		return $encodedStr;
	}
	
	static public function decode($decodedStr)
	{
		$seed_array = array('S','H','A','F','I','Q');
		$decoded =  base64_decode($decodedStr);
		@list($decoded,$letter) =  explode("+", $decoded);
		
		for($i=0;$i<count($seed_array);$i++)
		{
			if($seed_array[$i] == $letter)
			break;
		}
		
		for($j=1;$j<=$i;$j++)
		{
			$decoded = base64_decode($decoded);
		}
		
		return $decoded;
	}
}
?>