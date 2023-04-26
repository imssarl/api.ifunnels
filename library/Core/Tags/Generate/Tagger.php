<?php
class Core_Tags_Generate_Tagger {

	private static $_tagsByTerm=array();

	public function __construct( $_strLang='english' ) {
		$this->getLexicon( $_strLang );
	}

	private function getLexicon( $_strLang ) {
		$_strFile=dirname( __FILE__ ).DIRECTORY_SEPARATOR.$_strLang.'-lexicon.txt';
		if ( !file_exists( $_strFile ) ) {
			throw new Exception( Core_Errors::DEV.'|Core_Tags_Generate_Tagger::getLexicon file not found or empty' );
			return;
		}
		$_file=file( $_strFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		foreach( $_file as $_line ) {
			$_arrLine=explode( ' ', $_line );
			self::$_tagsByTerm[$_arrLine[0]]=$_arrLine[1];
		}
		unSet( $_file );
	}

	private $_rules=array(
		//correctDefaultNounTag,
		'verifyProperNounAtSentenceStart',
		'determineVerbAfterModal',
		//normalizePluralForms,
	);

	// проверяем действительно ли существительное в начале предложения
	private function verifyProperNounAtSentenceStart( $_intId, $_arrTaggedTerm, &$_arrTaggedTerms ) {
		list( $term, $tag, $norm )=$_arrTaggedTerm;
		if ( in_array( $tag, array( 'NNP', 'NNPS' ) )&&( $_intId==0||$_arrTaggedTerms[$_intId-1][1]=='.' ) ) { // получается что точки из текста тоже нужны TODO!!!
			$lower_term=strtolower( $term ); // у нас вроде заранее всё в нижнем регистре
			$lower_tag=self::$_tagsByTerm[strtolower( $lower_term )];
			if ( in_array( $lower_tag, array( 'NN', 'NNS' ) ) ) {
				$_arrTaggedTerms[$_intId][0]=$_arrTaggedTerms[$_intId][2]=$lower_term;
				$_arrTaggedTerms[$_intId][1]=$lower_tag;
			}
		}
	}

	// опеределить глагол после модального глагола, чтобы предотвратить возможное ошибочное определение слова как существительного
	private function determineVerbAfterModal( $_intId, $_arrTaggedTerm, &$_arrTaggedTerms ) {
		list( $term, $tag, $norm )=$_arrTaggedTerm;
		if ( $tag!='MD' ) {
			return;
		}
		$len_terms=count( $_arrTaggedTerms );
		$_intId++;
		while( $_intId<$len_terms ) {
			if ( $_arrTaggedTerms[$_intId][1]=='RB' ) {
				$_intId++;
				continue;
			}
			if ( $_arrTaggedTerms[$_intId][1]=='NN' ) {
				$_arrTaggedTerms[$_intId][1]='VB';
			}
			break;
		}
	}
	//private function correctDefaultNounTag( $_intId, $_arrTaggedTerm, &$_arrTaggedTerms ) {}
			/*
def correctDefaultNounTag(idx, tagged_term, tagged_terms, lexicon):
    """Determine whether a default noun is plural or singular."""
    term, tag, norm = tagged_term
    if tag == 'NND':
        if term.endswith('s'):
            tagged_term[1] = 'NNS'
            tagged_term[2] = term[:-1]
        else:
            tagged_term[1] = 'NN'
	*/

	//private function normalizePluralForms( $_intId, $_arrTaggedTerm, &$_arrTaggedTerms ) {}
	/*
def normalizePluralForms(idx, tagged_term, tagged_terms, lexicon):
    term, tag, norm = tagged_term
    if tag in ('NNS', 'NNPS') and term == norm:
        # Plural form ends in "s"
        singular = term[:-1]
        if (term.endswith('s') and
            singular in lexicon):
            tagged_term[2] = singular
            return
        # Plural form ends in "es"
        singular = term[:-2]
        if (term.endswith('es') and
            singular in lexicon):
            tagged_term[2] = singular
            return
        # Plural form ends in "ies" (from "y")
        singular = term[:-3]+'y'
        if (term.endswith('ies') and
            singular in lexicon):
            tagged_term[2] = singular
            return
	*/


	public function get( $_strText='' ) {
		//$_arrTerms=str_word_count( $_strText, 1 );
		$_arrTaggedTerms=array();
		// назначаем тэги из лексикона к каждому элементу текста, если не находим то по умолчанию это существительное (NND)
		foreach( $this->tokenize( $_strText ) as $term ) {
			$_arrTaggedTerms[]=array( $term, (isSet( self::$_tagsByTerm[$term] )? self::$_tagsByTerm[$term]:'NND'), $term );
		}
		// прогоняем массив через несколько правил для улучшения теггирования и нормализации
		foreach( $_arrTaggedTerms as $k=>$v ) {
			foreach( $this->_rules as $rule ) {
				$this->$rule( $k, $v, $_arrTaggedTerms );
			}
		}
		return $_arrTaggedTerms;
	}

	private function tokenize( $_strText='' ) {
		$_arrTerms=array();
		foreach( preg_split( "/[\s]+/", $_strText, -1, PREG_SPLIT_NO_EMPTY ) as $term ) {
			if ( !preg_match( "/([^a-zA-Z]*)([a-zA-Z-\.]*[a-zA-Z])([^a-zA-Z]*[a-zA-Z]*)/", $term, $matches ) ) {
				$_arrTerms[]=$term;
				continue;
			}
			array_shift( $matches );
			foreach( $matches as $subTerm ) {
				if ( $subTerm!='' ) {
					$_arrTerms[]=$subTerm;
				}
			}
		}
		return $_arrTerms;
	}
}
?>