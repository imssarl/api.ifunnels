<?php
/*
Typo Generator Class

- PHP 5 is required
- The class is not intended to be used for the construction of an object but rather as a namespace
- The class has four methods each of which accept a string and return and array of strings that are likely typos of the type that particular function producesi
- Copyright with the the MIT License
- Developer was Scott Horne of Takeshi Media and Web-Professor.net
- http://web-professor.net for mor info


Class Functions:
-----------------------------------------------------------

Project_Keywords_Typo::getWrongKeyTypos( $word )
	Typos based on a user hitting the wrong key that is near the intended key, only uses characters valid in ascii domain names 

Project_Keywords_Typo::getMissedCharTypos( $word )
	Typos based on a missed key

Project_Keywords_Typo::getTransposedCharTypos( $word )
	Typos based on transposition errors 

Project_Keywords_Typo::getDoubleCharTypos( $word )
	Typos based on hitting an intended key twice

Project_Keywords_Typo::getAllTypos( $word )
	This calls all the typos and returns every variety


Example Usage:
-----------------------------------------------------------
$word = "Hello";
$typos = array();
$typos = Project_Keywords_Typo::getAllTypos( $word );

print_r( $typos );







Copyright (c) 2006, Takeshi Media

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE. 

*/




class Project_Keywords_Typo {

// array of keys near character on a QWERTY keyboard
// only valid characters in a domain name
	static $keyboard = array(
// top row
		'1' => array( '2', 'q' ),
		'2' => array( '1', 'q', 'w', '3' ),
		'3' => array( '2', 'w', 'e', '4' ),
		'4' => array( '3', 'e', 'r', '5' ),
		'5' => array( '4', 'r', 't', '6' ),
		'6' => array( '5', 't', 'y', '7' ),
		'7' => array( '6', 'y', 'u', '8' ),
		'8' => array( '7', 'u', 'i', '9' ),
		'9' => array( '8', 'i', 'o', '0' ),
		'0' => array( '9', 'o', 'p', '-' ),
		'-' => array( '0', 'p' ),
// 2nd from top
		'q' => array( '1', '2', 'w', 'a' ),
		'w' => array( 'q', 'a', 's', 'e', '3', '2' ),
		'e' => array( 'w', 's', 'd', 'r', '4', '3' ),
		'r' => array( 'e', 'd', 'f', 't', '5', '4' ),
		't' => array( 'r', 'f', 'g', 'y', '6', '5' ),	
		'y' => array( 't', 'g', 'h', 'u', '7', '6' ),
		'u' => array( 'y', 'h', 'j', 'i', '8', '7' ),
		'i' => array( 'u', 'j', 'k', 'o', '9', '8' ),
		'o' => array( 'i', 'k', 'l', 'p', '0', '9' ),
		'p' => array( 'o', 'l', '-', '0' ),
// home row
		'a' => array( 'z', 's' , 'w', 'q' ),
		's' => array( 'a', 'z', 'x', 'd', 'e', 'w' ),
		'd' => array( 's', 'x', 'c', 'f', 'r', 'e' ),
		'f' => array( 'd', 'c', 'v', 'g', 't', 'r' ),
		'g' => array( 'f', 'v', 'b', 'h', 'y', 't' ),
		'h' => array( 'g', 'b', 'n', 'j', 'u', 'y' ),
		'j' => array( 'h', 'n', 'm', 'k', 'i', 'u' ),
		'k' => array( 'j', 'm', 'l', 'o', 'i' ),
		'l' => array( 'k', 'p', 'o' ),
// bottom row
		'z' => array( 'x', 's', 'a' ),
		'x' => array( 'z', 'c', 'd', 's' ),
		'c' => array( 'x', 'v', 'f', 'd' ),
		'v' => array( 'c', 'b', 'g', 'f' ),
		'b' => array( 'v', 'n', 'h', 'g' ),
		'n' => array( 'b', 'm', 'j', 'h' ),
		'm' => array( 'n', 'k', 'j' )
	);

	public static function getAllTypos( $word )
	{
		$typos = array();

		$typos = array_merge( $typos, Project_Keywords_Typo::getWrongKeyTypos($word));
		$typos = array_merge( $typos, Project_Keywords_Typo::getMissedCharTypos($word));
		$typos = array_merge( $typos, Project_Keywords_Typo::getTransposedCharTypos( $word ));
		$typos = array_merge( $typos, Project_Keywords_Typo::getDoubleCharTypos( $word ));

		return $typos;
	}

// accepts a string
// returns array of likely single "wrong key" typos
// arrays contain only characters that are valid domain names

	public  static function getWrongKeyTypos( $word )
	{
		$word = strtolower( $word );
		$typos = array();
		$length = strlen( $word );
// check each character
		for( $i = 0; $i < $length; $i++ )
		{
// if character has replacements then create all replacements
			if( Project_Keywords_Typo::$keyboard[$word{$i}] )
			{
// temp word for manipulating
				$tempWord = $word;
				foreach( Project_Keywords_Typo::$keyboard[$word{$i}] as $char )
				{
					$tempWord{$i} = $char;			
					array_push( $typos, $tempWord );
				}
			}
		}

		return $typos;
	}



// accepts a string
// returns array of likely single missed character typos
// arrays contain only characters that are valid domain names
	public  static  function getMissedCharTypos( $word )
	{
		$word = strtolower( $word );
		$typos = array();
		$length = strlen( $word );
// check each character
		for( $i = 0; $i < $length; $i++ )
		{
			$tempWord = '';
			if( $i == 0 )
			{
// at first character
				$tempWord = substr( $word, ( $i + 1 ) );

			} else if ( ( $i + 1 ) == $length ) {
// at last character
				$tempWord = substr( $word, 0,  $i  ) ;

			} else {
// in between
				$tempWord = substr( $word, 0,  $i  ) ;
				$tempWord .= substr( $word, ( $i + 1 )) ;

			}
			array_push( $typos, $tempWord );
		}

		return $typos;
	}


// accepts a string
// returns array of likely transposed character typos
// arrays contain only characters that are valid domain names
	public static function getTransposedCharTypos( $word )
	{
		$word = strtolower( $word );
		$typos = array();
		$length = strlen( $word );
// check each character
		for( $i = 0; $i < $length; $i++ )
		{
			if( ( $i + 1 ) == $length )
			{
// could have simplified the test by throwing it in the for loop but I didn't to keep it readable
// at the end no transposition
			} else {
				$tempWord = $word;
				$tempChar = $tempWord{$i};			
				$tempWord{$i} = $tempWord{( $i + 1 )} ;			
				$tempWord{( $i + 1 )} = $tempChar;			
				array_push( $typos, $tempWord );
			}
		}

		return $typos;
	}





// accepts a string
// returns array of likely double entered character typos
// arrays contain only characters that are valid domain names
	public static function getDoubleCharTypos( $word )
	{
		$word = strtolower( $word );
		$typos = array();
		$length = strlen( $word );
// check each character
		for( $i = 0; $i < $length; $i++ )
		{
// get first part of word
			$tempWord = substr( $word, 0, ($i+1) );
// add a character
			$tempWord .= $word{$i};
// add last part of strin if there is any 
			if( $i == ( $length - 1 ))
			{
// do nothing we are at the end
			} else {
// add the end part of the string
				$tempWord .= substr( $word, ($i+1));
			}
			array_push( $typos, $tempWord );
		}

		return $typos;
	}


}


?>
