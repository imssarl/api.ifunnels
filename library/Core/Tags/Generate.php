<?php
class Core_Tags_Generate {

	private $_tagger;

	public function __construct() {
		$this->_tagger=new Core_Tags_Generate_Tagger();
	}

	public function setText( $_strText='' ) {
		$this->_text=$_strText;
		return $this;
	}

	public function termCompare( $x, $y ) {
		if ( ($y[1]+$y[2]*2)>($x[1]+$x[2]*2) ) {
			return 1;
		} elseif ( $y[1]==$x[1]&&$y[2]==$x[2] ) {
			return 0;
		} else { // $x<$y
			return -1;
		}
	}

	public function getTags( &$arrRes ) {
		if ( empty( $this->_text ) ) {
			return false;
		}
		$this->_text=mb_strtolower($this->_text, "UTF8");
		$arrRes=$this->extract( $this->_tagger->get( $this->_text ) );
		uasort( $arrRes, array( $this, 'termCompare' ) );
		$arrRes=array_slice( $arrRes, 0, 50 );
		foreach( range( count( $arrRes )-1, 0, -1 ) as $v ) {
			if ( $arrRes[$v][0]==1||$arrRes[$v][2]>2||stripos( $arrRes[$v][0], 'http' )!==false ) {
				unSet( $arrRes[$v] );
			} else {
				$arrRes[$v]=$arrRes[$v][0];
			}
		}
		return true;
	}

	private $_multiterm=array();
	private $_terms=array();
	private $_SEARCH=0;
	private $_NOUN=1;

	private function add( $term, $norm ) {
		$this->_multiterm[]=array( $term, $norm );
		if ( !isSet( $this->_terms[$norm] ) ) {
			$this->_terms[$norm]=0;
		}
		$this->_terms[$norm]++;
	}

	private function extract( $taggedTerms=array() ) {
		$this->_multiterm=$this->_terms=$_arrWords=$arrRes=array();
		$state=$this->_SEARCH;
		while ( !empty( $taggedTerms ) ) {
			list( $term, $tag, $norm )=array_shift( $taggedTerms );
			if ( $state==$this->_SEARCH&&$tag[0]=='N' ) {
				$state=$this->_NOUN;
				$this->add( $term, $norm );
			} elseif ( $state==$this->_SEARCH&&$tag[0]=='JJ'&&Core_String::isUpper( $term[0] ) ) { // такого вроде быть не должно мы в начале текст переводим в нижний регистр
				$state=$this->_NOUN;
				$this->add( $term, $norm );
			} elseif ( $state==$this->_NOUN&&$tag[0]=='N' ) {
				$this->add( $term, $norm );
			} elseif ( $state==$this->_NOUN&&$tag[0]!='N' ) {
				$state=$this->_SEARCH;
				if ( count( $this->_multiterm )>1 ) {
					$_arrWords=array();
					foreach( $this->_multiterm as $v ) {
						$_arrWords[]=$v[0];
					}
					$_strWord=join( ' ', $_arrWords );
					if ( !isSet( $this->_terms[$_strWord] ) ) {
						$this->_terms[$_strWord]=0;
					}
					$this->_terms[$_strWord]++;
				}
				$this->_multiterm=array();
			}
		}
		foreach( $this->_terms as $word=>$occur ) {
			$strength=str_word_count( $word );
			if ( $this->filter( $word, $occur, $strength ) ) {
				$arrRes[]=array( $word, $occur, $strength );
			}
		}
		return $arrRes;
	}

	// может фильтрацию тоже как отдельный объект сделать?
	private $_singleStrengthMinOccur=1;
	private $_noLimitStrength=2;

	private function filterPermissive( $word, $occur, $strength ) {
		return true;
	}

	private function filter( $word, $occur, $strength ) {
		return ( $strength==1&&$occur>=$this->_singleStrengthMinOccur )||( $strength>=$this->_noLimitStrength );
	}
}
?>