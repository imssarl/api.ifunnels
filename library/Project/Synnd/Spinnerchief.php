<?php


/**
 * Spinnerchief
 */

class Project_Synnd_Spinnerchief {

	private $_strUsername='imssarl';
	private $_strPassword='el041299';
	private $_strApiKey='26141e388cd74b809';
	
	private static $_arrSettings=array();

	private static $_spynerUrl='http://api.spinnerchief.com:443';//'http://api.spinnerchief.com:443';http://api.spinnerchief.com:9001

	public function __construct(){
		self::$_arrSettings=array(
			"username"=>$this->_strUsername,
			"password"=>$this->_strPassword,
			"apikey"=>$this->_strApiKey
		);
	}
	
	public function setSettings( $_arrInvited=array()){
		// http://developer.spinnerchief.com/API_Document.aspx
		$_arrDeafault=array(
			"thesaurus"=>'english', /*	Arabic,Belarusian,Bulgarian,Croatian,Danish,Dutch,English,Filipino,
															Finnish,French,German,Greek,Hebrew,Indonesian,Italian,Lithuanian,Norwegian,Polish,Portuguese,
															Romanian,Slovak,Slovenian,Spanish,Swedish,Turkish,Vietnamese, Users’thesaurus ID */
			"spintype"=>0, /*	When spintype=0, SpinnerChief will return the spun article in {} (Spyntax) format. For example, if your article is "This is a great software", the return will be "{It|This} {is|must be} a {good|nice} {software|program}". 
											when spintype=1, SpinnerChief will return the spun article directly. For example, if your article is "This is a great software", the return will be "It is a nice program". */
			"spinfreq"=>1, //	The spinfreq means word spin frequency, for example if spinfreq=1, every word will be spun, if spinfreq=3, 1/3 of all words will be spun, etc. 
			"wordquality"=>0, /*	wordquality=0, use Best Thesaurus to spin
												wordquality=1, use Better Thesaurus to spin
												wordquality=2, use Good Thesaurus to spin
												wordquality=3, use All Thesaurus to spin
												wordquality=9, use Everyone’s favorite to spin 
												*/
			"original"=>1, /*	When original=0, the server will delete the original word in the return result.
											When original=1, server will keep the original words in the spun article, */
			"autospin"=>1, // 1- Yes 0 - No 
										/*	When autospin=0, SpinnerChief will not spin the words in your article without the {}(Spyntax) format. For example, if you post an article like "{It|This} is a good software", SpinnerChief will only spin the {It|This} part, other words will not get spun.
										When autospin=1, SpinnerChief will auto-spin the words in your article without the {}(Spyntax) format. For example, if you post an article like "{It|This} is a good software", SpinnerChief will not only spin {It|This} part, but also spin the words "good" and "software", so the return would be "This is a great program". */
			"wordscount"=>3, // Wordscount means how many words to use when spintype=0. For example, if the article is “hello”, Wordscount=3, the result will be {hello|hi|hey}. If Wordscount=2, the result will be {hello|hi}. 
			"orderly"=>0, /* When orderly=0, the server uses the thesaurus in its listed order to spin.
											When orderly=1, the server uses the thesaurus randomly to spin.*/
			"protectwords"=>'word1,word2,word3', // When you set protectwords, the server will not spin words in the the protect words list, the format is protectwords=word1,word2,word3,phrase1,phrase2
													// If you don’t want to use protectwords, just don’t use this parameter in your post url. 
			"replacetype"=>0, /*	replacetype =0：Replace phrase and word
												replacetype =1：Only replace phrase
												replacetype =2: Only replace word
												replacetype =3: Replace phrase first, then replace word till the article passes copyscape
												replacetype =4: Spin the article to most unique
												replacetype =5: Spin the article to most readable */
			"phrasecount"=>2, // phrasecount =2,3,4,X When replacing the phrase, how many words define a phrase. 
			"pos"=>1, /* 	pos=0：Don’t use ‘part of speech’ analysis
									pos=1 Use ‘part of speech’ analysis */
			"UseGrammarAI"=>1, /*	UseGrammarAI =0：Don’t use grammar correction；
														UseGrammarAI =1：Use grammar correction */
			"protecthtml"=>1, /*	When protecthtml=0, the server will not spin the words in the html tags in your article.
												When protecthtml =1, the server will still spin the words in html tags in your article. */
			"spinhtml"=>0, /*	When spinhtml=0, the server will still spin the {} part within html tags in your article.
											When spinhtml=1, the server will not spin the {} part within html tags in your article. */
			"tagprotect"=>'[],<!!>,[--]', /*This will protect the text between tags. If tagprotect=null, it will not protect any tags. If tagprotect=[],(),<- -> , it will protect the text between [ and ], ( and ), <- and ->. */
			"rule"=>0, /*	Arabic,Belarusian,Bulgarian,Croatian,Danish,Dutch,English,Filipino,Finnish,French,German,
									Greek,Hebrew,Indonesian,Italian,Lithuanian,Norwegian,Polish,Portuguese,Romanian,Slovak,
									Slovenian,Spanish,Swedish,Turkish,Vietnamese, Users’rule ID */
			"chartype"=>1,/*Chartype=1：Normal Chars； chartype=2：Special Chars； chartype=3：Non Unicode.（this parameter will only work when hurricane=0.*/
			"convertbase"=>0,/*convertbase=0：Convert Based On Char；chartype=1：Convert Based On Word。（this parameter will only work when hurricane=0.）*/
			"onecharforword"=>0,/*onecharforword=0：Don’t use“Only Convert One Char for every word” function；onecharforword =1：use “Only Convert One Char for every word”function.（this parameter will only work when hurricane=0.*/
			//----------------------------
			"usehurricane"=>0,
			"percent"=>0, /*percent=50：50% Conversion Rate（Must be integer from 1 to 100. （this parameter will only work when hurricane=0.）*/
			"querytimes"=>0, /*	When querytimes=1, the server returns today’s used query times of this account.
													When querytimes=2, the server returns today’s remaining query times of this account. */
		);
		self::$_arrSettings=$_arrInvited+self::$_arrSettings+$_arrDeafault;
		return $this;
	}
	
	public function getContent( $_strContent='' ){
		$_curl=Core_Curl::getInstance();
		$_curl
			->setPost( base64_encode( $_strContent ) )
			->getContent( self::$_spynerUrl."/".http_build_query( self::$_arrSettings ) );
		$_strResponce=base64_decode( $_curl->getResponce( ) );
		if( strpos( $_strResponce, 'error' ) === false ){
			return $_strResponce;
		}else{
			return $_strContent;
		}
	}
}
?>