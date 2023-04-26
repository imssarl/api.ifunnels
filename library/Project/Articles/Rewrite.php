<?php
/**
 * WorkHorse Framework
 *
 * @category Project
 * @package Project_Articles
 * @license http://opensource.org/licenses/ MIT License
 * @copyright Copyright (c) 2009-2010, web2innovation
 * @author Rodion Konnov <kindzadza@mail.ru>
 * @date 18.11.2010
 * @version 2.0
 */


/**
 * Управление реврайтом статей, а также поиск вариаций по запросам
 *
 * @category Project
 * @package Project_Articles
 * @copyright Copyright (c) 2009-2010, web2innovation
 * @license http://opensource.org/licenses/ MIT License
 */
class Project_Articles_Rewrite extends Core_Storage {

	private $_userSelection='';

	/**
	* список полей таблицы
	* @var array
	*/
	public $fields=array( 'id', 'parent_id', 'user_id', 'variant' );

	/**
	* название таблицы
	* @var string
	*/
	public $table='art_variants';

	/**
	* линк данных Core_Storage
	* тут линк нам не нужен
	* @var boolean
	*/
	protected $_link=false;
	/**
	 * Признак последнего варианта статьи из всех возможных
	 *
	 * @var boolean
	 */
	private $_isLast=false;

	/**
	* конструктор
	* @return void
	*/
	public function __construct() {
		if ( !Zend_Registry::get( 'objUser' )->getId( $_int ) ) {
			throw new Exception( Core_Errors::DEV.'|Zend_Registry::get( \'objUser\' )->getId( $_int ) is not return an User Id' );
			return;
		}
		$this->_userId=$_int;
	}

	public function getSynonimous( &$arrRes, $_strWords='' ) {
		if ( empty( $_strWords ) ) {
			return false;
		}
		$_strWords=Core_Sql::fixInjection( $_strWords );
		$arrRes=Core_Sql::getField( '
			select distinct w2.lemma 
			from art_senses s2
			inner join art_words w2 on w2.wordid=s2.wordid
			inner join (
				select s1.synsetid
				from art_words w1
				inner join art_senses s1 on w1.wordid=s1.wordid
				where w1.lemma='.$_strWords.'
			) tmp on s2.synsetid=tmp.synsetid
			where w2.lemma<>'.$_strWords.'
			order by w2.lemma
			limit 0,35
		' );
		return !empty( $arrRes );
	}

	public function generateArticles( &$arrRes ){
		if ( !$this->_data
			->setFilter( array( 'stripslashes', 'trim', 'clear' ) )
			->setChecker( array(
				'title'=>empty( $this->_data->filtered['title'] ),
				'body'=>empty( $this->_data->filtered['body'] ),
				'id'=>empty( $this->_data->filtered['id'] ),
				'vars'=>empty( $this->_data->filtered['vars'] ), ) )
			->check() ) {
			$this->_data->getErrors( $this->_errors );
			return false;
		}
		$_strTitle=preg_replace( array( '/(<\/span>)/i', '/(<span .*?id=.*?>)/i' ), array( '}', '{' ), $this->_data->filtered['title'] );
		$_strBody=preg_replace( array( '/(<\/span>)/i', '/(<span .*?id=.*?>)/i' ), array( '}', '{' ), $this->_data->filtered['body'] );

		$fsm=new Project_Articles_Rewrite_FSM();
		$fsm->init();
		$_arrTitles=$fsm->setRandom()->setMax( $this->_data->filtered['max'] )->setData( $_strTitle )->parse();
		if ( !is_array( $_arrTitles )||empty( $_arrTitles ) ) {
			throw new Exception( Core_Errors::DEV.'|articles not generated in Project_Articles_Rewrite_FSM' );
			$this->_errors['unknown']=true;
			return false;
		}
		
		$fsm=new Project_Articles_Rewrite_FSM();
		$fsm->init();
		if ( $this->_data->filtered['clear_session'] && !empty($_SESSION['arrArticles']) ){
			unset($_SESSION['arrArticles']);
		}
		if (empty($_SESSION['arrArticles'])){ // исключаем оригинал статьи.
			$_SESSION['arrArticles'][] = str_replace(' ','',$this->_data->filtered['original_text']);
		}
		$_arrArticles=$fsm->setCreated( $_SESSION['arrArticles'] )->setRandom()->setMax( $this->_data->filtered['max'] )->setData( $_strBody )->parse();
		foreach ( $_arrArticles as $_article ){
			$_SESSION['arrArticles'][]=str_replace(' ','',$_article);
		}
		$this->_isLast=$fsm->isLast();
		if ( !is_array( $_arrArticles )||empty( $_arrArticles ) ) {
			throw new Exception( Core_Errors::DEV.'|articles not generated in Project_Articles_Rewrite_FSM' );
			$this->_errors['unknown']=true;
			return false;
		}
		$_arrTmp=$_arrTitles;
		foreach ( $_arrArticles as $_k=>$_strArticle ){ 
			$_intKey=array_rand( $_arrTmp, 1 );
			$arrRes[] = array(
				'body'	=>	$_strArticle,
				'title' =>	$_arrTmp[$_intKey] . '_' . ($_k+1),
			);
			unset( $_arrTmp[$_intKey] );
			if ( empty( $_arrTmp ) ) {
				$_arrTmp=$_arrTitles;
			}			
		}
		return true;		
	}
	
	public function isLast(){
		return $this->_isLast;
	}
	
	public function saveArticles( $_arrArticles ) {
		if (!$this->_data
			->setFilter( array( 'stripslashes', 'trim', 'clear' ) )
			->setChecker( array(
				'id'=>empty( $this->_data->filtered['id'] ),
				'vars'=>empty( $this->_data->filtered['vars'] ), ) )
			->check() ) {
			$this->_data->getErrors( $this->_errors );
			return false;
		}	
		$articles=new Project_Articles();		
		// берём образец
		if ( !$articles->withIds( $this->_data->filtered['id'] )->getList( $_arrSample ) ) {
			$this->_errors['unknown']=true;
			return false;
		}
		foreach( $_arrArticles as $_v ) {
			// сохранение постатейно
			$articles->setData( array(
				'category_id'=>$_arrSample['category_id'],
				'source_id'=>$_arrSample['source_id'],
				'title'=>$_v['title'],
				'author'=>$_arrSample['author'],
				'body'=>$_v['body'],
			) )->set();
		}
		unset($_SESSION['arrArticles']);
		unset($_SESSION['arrTitles']);
		$this->setVars( $this->_data->filtered['vars'] );
		return true;
	}

	public function getVars( &$arrRes, $_strWords='' ) {
		$this->_userSelection='';
		if ( empty( $_strWords ) ) {
			return false;
		}
		$this->_userSelection=Core_Sql::getRecord( 'SELECT * FROM '.$this->table.' WHERE variant='.Core_Sql::fixInjection( $_strWords ).'' );
		if ( empty( $this->_userSelection ) ) {
			return false;
		}
		$arrRes=Core_Sql::getField( 'SELECT variant FROM '.$this->table.' WHERE parent_id='.$this->_userSelection['id'] );
		return true;
	}

	private function setVars( $_str='' ) {
		if ( empty( $_str ) ) {
			return false;
		}
		$_arrVars=explode( '::|::', $_str );
		foreach( $_arrVars as $v ) {
			$_arrVarsCur=explode( '|', $v );
			if ( count( $_arrVarsCur )<2 ) {
				continue;
			}
			$_strSelected=array_shift( $_arrVarsCur ); // первый элемент считается выбранным пользователем
			if ( $this->getVars( $_arrVarsOld, $_strSelected ) ) {
				$_arrVarsCur=array_diff( $_arrVarsCur, $_arrVarsOld ); // оставляем только новые варианты
			}
			if ( empty( $_arrVarsCur ) ) {
				continue;
			}
			if ( empty( $this->_userSelection['id'] ) ) {
				$_intParentId=Core_Sql::setInsert( $this->table, array(
					'user_id'=>$this->_userId,
					'variant'=>$_strSelected,
				) );
			} else {
				$_intParentId=$this->_userSelection['id'];
			}
			foreach( $_arrVarsCur as $_strVariant ) {
				$_arrNewVars[]=array(
					'parent_id'=>$_intParentId,
					'user_id'=>$this->_userId,
					'variant'=>$_strVariant,
				);
			}
		}
		if ( empty($_arrNewVars) ){
			return false;
		}
		Core_Sql::setMassInsert( $this->table, $_arrNewVars );
		
	}
}
?>