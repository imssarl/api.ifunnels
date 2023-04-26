<?php


/**
 * Поиск в google
 */
class Project_Content_Adapter_Googlesearch implements Project_Content_Interface{

	protected $_userAgents=array( 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:10.0.2) Gecko/20100101 Firefox/10.0.2' );

	private $_pattern='/<div class="g">(.*?)<a href="(?<url>[^"]*)"(.*?)">(?<title>.*?)<\/a>(.*?)<span class="st">(?<description>.*?)<\/span><\/div><\/div><\/div>(.*?)<!--n-->/ium';
	private $_pages='/;width:20px"><\/span>(?<page>[\d]*)<\/a><\/td><td>/ium';
	
	public $type=array( 'Forums', 'Blogs', 'Social Networks', 'Directories' );

	public $datacenters=array(
		0=>array( 'name'=>'Google', 'url'=>'www.google.com'),
		1=>array( 'name'=>'Anguilla', 'url'=>'www.google.off.ai' ),
		2=>array( 'name'=>'Antigua and Barbuda', 'url'=>'www.google.com.ag' ),
		3=>array( 'name'=>'Argentina', 'url'=>'www.google.com.ar' ),
		4=>array( 'name'=>'Australia', 'url'=>'www.google.com.au' ),
		5=>array( 'name'=>'Austria', 'url'=>'www.google.at' ),
		6=>array( 'name'=>'Azerbaijan', 'url'=>'www.google.az' ),
		7=>array( 'name'=>'Belgium', 'url'=>'www.google.be' ),
		8=>array( 'name'=>'Brazil', 'url'=>'www.google.com.br' ),
		9=>array( 'name'=>'British Virgin Islands', 'url'=>'www.google.vg' ),
		10=>array( 'name'=>'Burundi', 'url'=>'www.google.bi' ),
		11=>array( 'name'=>'Canada', 'url'=>'www.google.ca' ),
		12=>array( 'name'=>'Chad', 'url'=>'www.google.td' ),
		13=>array( 'name'=>'Chile', 'url'=>'www.google.cl' ),
		14=>array( 'name'=>'Colombia', 'url'=>'www.google.com.co' ),
		15=>array( 'name'=>'Costa Rica', 'url'=>'www.google.co.cr' ),
		16=>array( 'name'=>'Caste d\'Ivoire', 'url'=>'www.google.ci' ),
		17=>array( 'name'=>'Cuba', 'url'=>'www.google.com.cu' ),
		18=>array( 'name'=>'Dem. Rep. of the Congo', 'url'=>'www.google.cd' ),
		19=>array( 'name'=>'Denmark', 'url'=>'www.google.dk' ),
		20=>array( 'name'=>'Djibouti', 'url'=>'www.google.dj' ),
		21=>array( 'name'=>'Dominican Republic', 'url'=>'www.google.com.do' ),
		22=>array( 'name'=>'Ecuador', 'url'=>'www.google.com.ec' ),
		23=>array( 'name'=>'El Salvador', 'url'=>'www.google.com.sv' ),
		24=>array( 'name'=>'Federated States of Micronesia', 'url'=>'www.google.fm' ),
		25=>array( 'name'=>'Fiji', 'url'=>'www.google.com.fj' ),
		26=>array( 'name'=>'Finland', 'url'=>'www.google.fi' ),
		27=>array( 'name'=>'France', 'url'=>'www.google.fr' ),
		28=>array( 'name'=>'The Gambia', 'url'=>'www.google.gm' ),
		29=>array( 'name'=>'Georgia', 'url'=>'www.google.ge' ),
		30=>array( 'name'=>'Germany', 'url'=>'www.google.de' ),
		31=>array( 'name'=>'Gibraltar', 'url'=>'www.google.com.gi' ),
		32=>array( 'name'=>'Greece', 'url'=>'www.google.com.gr' ),
		33=>array( 'name'=>'Greenland', 'url'=>'www.google.gl' ),
		34=>array( 'name'=>'Guernsey', 'url'=>'www.google.gg' ),
		35=>array( 'name'=>'Honduras', 'url'=>'www.google.hn' ),
		36=>array( 'name'=>'Hong Kong', 'url'=>'www.google.com.hk' ),
		37=>array( 'name'=>'Hungary', 'url'=>'www.google.co.hu' ),
		38=>array( 'name'=>'India', 'url'=>'www.google.co.in' ),
		39=>array( 'name'=>'Ireland', 'url'=>'www.google.ie' ),
		40=>array( 'name'=>'Isle of Man', 'url'=>'www.google.co.im' ),
		41=>array( 'name'=>'Israel', 'url'=>'www.google.co.il' ),
		42=>array( 'name'=>'Italy', 'url'=>'www.google.it' ),
		43=>array( 'name'=>'Jamaica', 'url'=>'www.google.com.jm' ),
		44=>array( 'name'=>'Japan', 'url'=>'www.google.co.jp' ),
		45=>array( 'name'=>'Jersey', 'url'=>'www.google.co.je' ),
		46=>array( 'name'=>'Kazakhstan', 'url'=>'www.google.kz' ),
		47=>array( 'name'=>'Korea', 'url'=>'www.google.co.kr' ),
		48=>array( 'name'=>'Latvia', 'url'=>'www.google.lv' ),
		49=>array( 'name'=>'Lesotho', 'url'=>'www.google.co.ls' ),
		50=>array( 'name'=>'Liechtenstein', 'url'=>'www.google.li' ),
		51=>array( 'name'=>'Lithuania', 'url'=>'www.google.lt' ),
		52=>array( 'name'=>'Luxembourg', 'url'=>'www.google.lu' ),
		53=>array( 'name'=>'Malawi', 'url'=>'www.google.mw' ),
		54=>array( 'name'=>'Malaysia', 'url'=>'www.google.com.my' ),
		55=>array( 'name'=>'Malta', 'url'=>'www.google.com.mt' ),
		56=>array( 'name'=>'Mauritius', 'url'=>'www.google.mu' ),
		57=>array( 'name'=>'Mexico', 'url'=>'www.google.com.mx' ),
		58=>array( 'name'=>'Montserrat', 'url'=>'www.google.ms' ),
		59=>array( 'name'=>'Namibia', 'url'=>'www.google.com.na' ),
		60=>array( 'name'=>'Nepal', 'url'=>'www.google.com.np' ),
		61=>array( 'name'=>'Netherlands', 'url'=>'www.google.nl' ),
		62=>array( 'name'=>'New Zealand', 'url'=>'www.google.co.nz' ),
		63=>array( 'name'=>'Nicaragua', 'url'=>'www.google.com.ni' ),
		64=>array( 'name'=>'Norfolk Island', 'url'=>'www.google.com.nf' ),
		65=>array( 'name'=>'Pakistan', 'url'=>'www.google.com.pk' ),
		66=>array( 'name'=>'Panamas', 'url'=>'www.google.com.pa' ),
		67=>array( 'name'=>'Paraguay', 'url'=>'www.google.com.py' ),
		68=>array( 'name'=>'Peras', 'url'=>'www.google.com.pe' ),
		69=>array( 'name'=>'Philippines', 'url'=>'www.google.com.ph' ),
		70=>array( 'name'=>'Pitcairn Islands', 'url'=>'www.google.pn' ),
		71=>array( 'name'=>'Poland', 'url'=>'www.google.pl' ),
		72=>array( 'name'=>'Portugal', 'url'=>'www.google.pt' ),
		73=>array( 'name'=>'Puerto Rico', 'url'=>'www.google.com.pr' ),
		74=>array( 'name'=>'Rep. of the Congo', 'url'=>'www.google.cg' ),
		75=>array( 'name'=>'Romania', 'url'=>'www.google.ro' ),
		76=>array( 'name'=>'Russia', 'url'=>'www.google.ru' ),
		77=>array( 'name'=>'Rwanda', 'url'=>'www.google.rw' ),
		78=>array( 'name'=>'Saint Helena', 'url'=>'www.google.sh' ),
		79=>array( 'name'=>'San Marino', 'url'=>'www.google.sm' ),
		80=>array( 'name'=>'Singapore', 'url'=>'www.google.com.sg' ),
		81=>array( 'name'=>'Slovakia', 'url'=>'www.google.sk' ),
		82=>array( 'name'=>'South Africa', 'url'=>'www.google.co.za' ),
		83=>array( 'name'=>'Spain', 'url'=>'www.google.es' ),
		84=>array( 'name'=>'Sweden', 'url'=>'www.google.se' ),
		85=>array( 'name'=>'Switzerland', 'url'=>'www.google.ch' ),
		86=>array( 'name'=>'Taiwan', 'url'=>'www.google.com.tw' ),
		87=>array( 'name'=>'Thailand', 'url'=>'www.google.co.th' ),
		88=>array( 'name'=>'Trinidad and Tobago', 'url'=>'www.google.tt' ),
		89=>array( 'name'=>'Turkey', 'url'=>'www.google.com.tr' ),
		90=>array( 'name'=>'Ukraine', 'url'=>'www.google.com.ua' ),
		91=>array( 'name'=>'United Arab Emirates', 'url'=>'www.google.ae' ),
		92=>array( 'name'=>'United Kingdom', 'url'=>'www.google.co.uk' ),
		93=>array( 'name'=>'Uruguay', 'url'=>'www.google.com.uy' ),
		94=>array( 'name'=>'Uzbekistan', 'url'=>'www.google.uz' ),
		95=>array( 'name'=>'Vanuatu', 'url'=>'www.google.vu' ),
		96=>array( 'name'=>'Venezuela', 'url'=>'www.google.co.ve' )
	);
	
	private $_withPaging=false;
	protected $_intGoogleCounter=10;
	private $_paggedData=false;
	private $_settingsData=false;
	private $_settings;

	public function __construct() {
		if( !is_array( $_SESSION['paggedData'] )){
			$_SESSION['paggedData']=array();
		}
		$this->_paggedData=&$_SESSION['paggedData'];
		if( !is_array( $_SESSION['settingsData'] )){
			$_SESSION['settingsData']=array();
		}
		$this->_settingsData=&$_SESSION['settingsData'];
		if( !is_array( $_SESSION['pagingSettings'] )){
			$_SESSION['pagingSettings']=array();
		}
		$this->_withPaging=&$_SESSION['pagingSettings'];
	}

	public function getArraySlice( $_array, $_startKey, $_endKey ){
		$_arrNew=array();
		foreach( $_array as $_key=>$_value ){
			if( $_key>=$_startKey && $_key<$_endKey ){
				$_arrNew[$_key]=$_value;
			}
		}
		return $_arrNew;
	}

	public function getList( &$mixRes ){
		if( $this->_settingsData!==$this->_settings ){
			$this->_paggedData=array();
			$this->_withPaging['page']=1;
			$this->_withPaging['recall']=0;
		}
		$mixRes=$this->getArraySlice( $this->_paggedData, ($this->_withPaging['page']-1)*$this->_intGoogleCounter, ($this->_withPaging['page']-1)*$this->_intGoogleCounter+$this->_intGoogleCounter );
		if( count( $mixRes )>=$this->_intGoogleCounter ){
			return $this;
		}
		$this->getParsedData( $mixRes );
		$this->_paggedData=$this->_paggedData+$mixRes;
		$mixRes=$this->getArraySlice( $this->_paggedData, ($this->_withPaging['page']-1)*$this->_intGoogleCounter, ($this->_withPaging['page']-1)*$this->_intGoogleCounter+$this->_intGoogleCounter );
		$this->_settingsData=$this->_settings;
		return $this;
	}

	private function getParsedData( &$mixRes ){
		$_uri=Zend_Uri::factory( 'https' );
		$_strKeyword=$this->_settings['keyword'];
		if( $this->_settings['expand']=='true' ){
			$_strKeyword='"'.$this->_settings['keyword'].'"';
		}
		try{
			$_uri->setHost( $this->datacenters[$this->_settings['datacenter']]['url'] );
			$_uri->setPath( '/search' );
			$_uri->setQuery( array(
				'q'=>urlencode( $_strKeyword." ".$this->_settings['type'] ),
				'start'=>($this->_withPaging['page']-1)*$this->_intGoogleCounter,
			) );
		}catch( Zend_Uri_Exception $e){
			$this->_isNotEmpty=!empty( $mixRes );
			$this->_paggedData=false;
			return;
		}
		$_curl=new Core_Curl();
		if ( !$_curl->getContent( $_uri->getUri() ) ){
			return;
		}
		$_responce=$_curl->getResponce();
		if( strpos( $_responce, 'The document has moved' ) ){
			preg_match_all("/<[Aa][\s]{1}[^>]*[Hh][Rr][Ee][Ff][^=]*=[ '\"\s]*([^ \"'>\s#]+)[^>]*>/", $_responce, $matches);
			foreach( $matches[1] as $_urls ){
				$_curl=new Core_Curl();
				if ( !$_curl->getContent( $_urls ) ){
					return;
				}
				$_responce.=$_curl->getResponce();
			}
		}
		preg_match_all( $this->_pattern, $_responce, $results, PREG_SET_ORDER );
		preg_match_all( $this->_pages, $_responce, $pages );

	//	var_dump( $results );
	//	exit;
		
		
		$this->_withPaging['maxpage']=(end($pages['page'])>$this->_withPaging['maxpage'])?end($pages['page']):$this->_withPaging['maxpage'];
		$_intIndex=($this->_withPaging['page']-1)*$this->_intGoogleCounter;
		foreach( $results as &$_v ){
			$mixRes[$_intIndex]=array_intersect_key( $_v, array( 'title'=>null,'url'=>null,'description'=>null ) );
			$_intIndex++;
		}
	}

	public function getPaging( &$arrRes ){
		if( $this->_withPaging['page'] > $this->_withPaging['maxpage'] ){
			$this->_withPaging['page'] = $this->_withPaging['maxpage'];
		}
		$_arrQuery=array( 'arrData'=>$this->_settings );
		$_arrQuery['page']=1;
		$arrRes=array(
			'curpage'=>$this->_withPaging['page'],
			'urlmin'=>'?'.http_build_query( $_arrQuery ),
			'recall'=>$this->_withPaging['maxpage']*$this->_intGoogleCounter,
		);
		$this->_withPaging['numofdigits']= min( $this->_withPaging['numofdigits'], $this->_withPaging['maxpage'] );
		$_start=$this->_withPaging['page']-floor( $this->_withPaging['numofdigits']/2 );
		$_end=$this->_withPaging['page']+floor( $this->_withPaging['numofdigits']/2 );
		if( $_start<1 ){
			$_end-=$_start-1;
			$_start=1;
		}
		if( $_end>$this->_withPaging['maxpage'] ){
			$_start-=$_end-$this->_withPaging['maxpage']-1;
			$_end=$this->_withPaging['maxpage'];
		}
		for( $i=$_start; $i<=$_end; $i++ ){
			$_arrData=array(
				'number' =>$i,
			);
			if( $i!=$this->_withPaging['page'] ){
				$_arrQuery['page']=$i;
				$_arrData['url']='?'.http_build_query( $_arrQuery );
			}else{
				$_arrData['sel']=$i;
			}
			$arrRes['num'][]=$_arrData;
		}
		
		if( $this->_withPaging['page'] < $this->_withPaging['maxpage'] ){
			$_arrQuery['page']=$this->_withPaging['page']+1;
			$arrRes['urlplus']='?'.http_build_query( $_arrQuery );
		}
		if( $this->_withPaging['page'] > 1 ){
			$_arrQuery['page']=$this->_withPaging['page']-1;
			$arrRes['urlminus']='?'.http_build_query( $_arrQuery );
		}
		$_arrQuery['page']=100;
		$arrRes['urlmax']='?'.http_build_query( $_arrQuery );
		return $this;
	}

	public function withPaging( $_arr=array() ) {
		$this->_withPaging=array_merge($this->_withPaging, $_arr );
		if ( empty($this->_withPaging['page']) )
			$this->_withPaging['page']=1;
		if( $this->_withPaging['page'] > 100 ){
			$this->_withPaging['page'] = 100;
		}
		return $this;
	}

	public function setCounter( $_intCounter ){
		$this->_intGoogleCounter=$_intCounter;
		return $this;
	}

	public function setFile( $_arrFile=array() ){
		ob_end_clean();
		header('Content-Disposition: attachment; filename="'.$this->_settings['keyword'].'.txt"');
		foreach( $this->_paggedData as $v ){
			echo str_replace(
				array("&ndash;", "&lt;", "&gt;", '&amp;', '&#39;', '&quot;','&lt;', '&gt;'),
				array("–","<", ">",'&','\'','"','<','>'),
				htmlspecialchars_decode(
					strip_tags( /*$v['title']."\n".*/$v['url']."\n"/*.$v['description']."\n\n"*/ ),
					ENT_NOQUOTES
				)
			);
		}
		die;
	}

	public function setSettings( $arrSettings ){
		if( !empty( $arrSettings ) ){
			$this->_settings=$arrSettings;
		}
		return $this;
	}

	public function getResult( &$strRes ) {}

	public function getAdditional( &$arrRes ){}

	public function setPost( $_arrPost=array() ){}

	public function withIds( $_arrIds=array() ){}

	public static function getInstance(){}

	public function setFilter( $_arrFilter=array() ){}

	public function getFilter( &$arrRes ){}

	public function setLimited( $_intLimited ){}

	public function checkEmpty(){}

}
?>