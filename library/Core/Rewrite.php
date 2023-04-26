<?php

class Core_Rewrite  {

	private $_limit=1;
	private $_text=false;
	private $_depth=false;
	private $_objTags=false;
	private static $_tableWords='art_words';
	private static $_tableLink='art_senses';
	private static $_tableVariants='art_variants';
	private static $_userSelection=false;
	const LIGHT=1, MODERATE=2, HIGH=3;

	public function __construct(){
		$this->_objTags=new Core_Tags_Generate();
	}

	/**
	 * Set text for rewriteing
	 * @param  $_str
	 * @return Project_Articles_Rewrite_Tags
	 */
	public function setText( $_str ){
		$this->_text=$_str;
		return $this;
	}

	/**
	 * Set depth for rewriting
	 * @param  $_depth
	 * @return Project_Articles_Rewrite_Tags
	 */
	public function setDeep( $_depth ){
		switch( $_depth ){
			case Core_Rewrite::LIGHT: $this->_depth=40; break;
			case Core_Rewrite::MODERATE: $this->_depth=70; break;
			case Core_Rewrite::HIGH: $this->_depth=100; break;
		}
		return $this;
	}

	/**
	 * Set return limits
	 * @param  $_limit
	 * @return Project_Articles_Rewrite_Tags
	 */
	public function setLimit( $_limit ){
		$this->_limit=$_limit;
		return $this;
	}

	/**
	 * Rewrite text
	 * @param  $arrRes
	 * @return bool
	 */
	public function rewrite( &$arrRes ){
		if( !$this->prepareText() ){
			return false;
		}
		$fsm=new Core_Rewrite_FSM();
		$fsm->init();
		$arrRes=$fsm->setRandom()->setMax( $this->_limit )->setData( $this->_text )->parse();
		return true;
	}

	/**
	 * Prepare text for rewriteing
	 * @return bool
	 */
	public function prepareText(){
		if( empty($this->_text) ){
			return false;
		}
		$this->_objTags->setText( strip_tags($this->_text) )->getTags( $arrTags );
		shuffle($arrTags);
		$arrTags=array_slice( $arrTags, 0, intval( count($arrTags) * $this->_depth / 100 ) );
		foreach( $arrTags as &$word ){
			if( !self::getSynonimous( $synonimous, $word ) ){
				continue;
			}
			$arrWords[]=array( 'word'=>$word, 'synonimous'=>$synonimous );
		}
		if(empty($arrWords)){
			return false;
		}
		foreach( $arrWords as &$item ){
			$this->_text=preg_replace("|\s(".$item['word'].")\s|", ' {$1|'.join('|', $item['synonimous'] )."} ", $this->_text );
		}
		return true;
	}

	/**
	 * Get synonimous from database
	 * @param  $arrRes
	 * @param string $_strWords
	 * @return bool
	 */
	public static function getSynonimous( &$arrRes, $_strWords='' ) {
		if ( empty( $_strWords ) ) {
			return false;
		}
		$_strWords=Core_Sql::fixInjection( $_strWords );
		$arrRes=Core_Sql::getField( '
			SELECT DISTINCT w2.lemma
			FROM '. self::$_tableLink .' s2
			INNER JOIN '. self::$_tableWords .' w2 ON w2.wordid=s2.wordid
			INNER JOIN (
				SELECT s1.synsetid
				FROM '. self::$_tableWords .' w1
				INNER JOIN '. self::$_tableLink .' s1 ON w1.wordid=s1.wordid
				WHERE w1.flg_root=1 AND w1.lemma='.$_strWords.'
			) tmp ON s2.synsetid=tmp.synsetid
			WHERE w2.lemma<>'.$_strWords.'
			ORDER BY RAND()
			LIMIT 0,35' );
		return !empty( $arrRes );
	}

	public static function getVars( &$arrRes, $_strWords='' ) {
		self::$_userSelection=array();
		if ( empty( $_strWords ) ) {
			return false;
		}
		self::$_userSelection=Core_Sql::getRecord( 'SELECT * FROM '.self::$_tableVariants.' WHERE variant='.Core_Sql::fixInjection( $_strWords ).'' );
		if ( empty( self::$_userSelection ) ) {
			return false;
		}
		$arrRes=Core_Sql::getField( 'SELECT variant FROM '.self::$_tableVariants.' WHERE parent_id='.self::$_userSelection['id'] );
		return true;
	}

	public static function setVars( $_str='' ) {
		if ( empty( $_str ) || !Zend_Registry::isRegistered( 'objUser' ) ) {
			return false;
		}
		Zend_Registry::get( 'objUser' )->getId( $_userId );
		$_arrVars=explode( '::|::', $_str );
		foreach( $_arrVars as $v ) {
			$_arrVarsCur=explode( '|', $v );
			if ( count( $_arrVarsCur )<2 ) {
				continue;
			}
			$_strSelected=array_shift( $_arrVarsCur ); // первый элемент считается выбранным пользователем
			if ( self::getVars( $_arrVarsOld, $_strSelected ) ) {
				$_arrVarsCur=array_diff( $_arrVarsCur, $_arrVarsOld ); // оставляем только новые варианты
			}
			if ( empty( $_arrVarsCur ) ) {
				continue;
			}
			if ( empty( self::$_userSelection['id'] ) ) {
				$_intParentId=Core_Sql::setInsert( self::$_tableVariants, array(
					'user_id'=>$_userId,
					'variant'=>$_strSelected,
				) );
			} else {
				$_intParentId=self::$_userSelection['id'];
			}
			foreach( $_arrVarsCur as $_strVariant ) {
				$_arrNewVars[]=array(
					'parent_id'=>$_intParentId,
					'user_id'=>$_userId,
					'variant'=>$_strVariant,
				);
			}
		}
		if ( empty($_arrNewVars) ){
			return false;
		}
		Core_Sql::setMassInsert( self::$_tableVariants, $_arrNewVars );
	}

}
?>