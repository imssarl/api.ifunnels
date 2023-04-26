<?php


/**
 * Парсинг конструкций аля '{dog|cat} the {food {in|out} the air {some|more|hm}} i liked {car|bike|u-bann}'
 * на основе конечных машин (Finite State Machine)
 */
class Core_Rewrite_FSM {

	private $_content=''; // Буфер данных
	private $_intLength=0; // Длина буфера
	private $_intPos=0; // Позиция курсора в буфере
	private $_reduce=false; // %%->%

	private $_line=0;
	private $_column=0;

	private $_error='';

	// Остальные символы имеют код 1
	private $_inState=array(
		'{'=>2, // начало вариантов
		'}'=>4, // конец варианта и выход из рекурсии
		'|'=>3, // конец варианта
		null=>4 // конец символов - аналогичен концу варианта, только в данном случае вариантом считается сам текст
	);

	private $_parserAutomat=array(
		/*
		-1 ошибка
		0 начало разбора - ожидаем символ или левую скобку
		1 получили символ, накапливаем их - ожидаем всё что угодно
		2 получили {. идём в рекурсию - после ожидаем символ или ещё одну левую скобку
		3 получили |. разбираемся с вариантом - ожидаем символ, любую скобку
		4 получили }. разбираемся с вариантом и выходим из рекурсии - ожидаем всё что угодно
		*/
		// состоян 0, 1, 2, 3, 4
		'1'=>array( 1, 1, 1, 1, 1 ), // символ
		'2'=>array( 2, 2, 2, 2, 2 ), // левая скобка
		'3'=>array(-1,3,-1,-1, 3 ), // палка
		'4'=>array(-1, 4,-1,4, 4 ), // правая скобка или конец строки
	);

	private $_state=0; // состояние машины

	public function setData( $_str='' ) {
		$this->_content=str_replace( '%', '%%', $_str );
		$this->_intLength=strlen( $_str );
		return $this;
	}

	public function getError() {
		return $this->_error;
	}

	private function updateStaying() {
		if ( $this->_content[$this->_intPos]=="\n" ) {
			$this->_line++;
			$this->_column=0;
		} else {
			$this->_column++;
		}
	}

	private function scan() {
		while ( $this->_intPos<$this->_intLength ) {
			$symbol=$this->_content[$this->_intPos];
			$this->updateStaying();
			$this->_intPos++;
			return $symbol;
		}
		return null;
	}

	public function parse() {
		$_arrVariation=array();
		$_arrVariants=array();
		$_strCurentVariant='';
		while( 1 ) {
			$symbol=$this->scan(); // получаем символ от сканера
			$instate=isSet( $this->_inState[$symbol] ) ?$this->_inState[$symbol]:1; // Устанавливаем код, подаваемого на вход автомата, символа
			$prev=$this->_state;
			$this->_state=$this->_parserAutomat[$instate][$this->_state];
			switch( $this->_state ) {
				case 1: // получили символ, накапливаем их - ожидаем всё что угодно
					$_strCurentVariant.=$symbol;
				break;
				case 2: // получили {. идём в рекурсию - после ожидаем символ или ещё одну левую скобку
					$_strCurentVariant.='%s';
					$_arrVariation[]=$this->parse();
				break;
				case 3: // получили |. разбираемся с вариантом - ожидаем символ, любую скобку
					if ( !empty( $_arrVariation ) ) {
						$_arrGenerated=$this->everyPossibleVariants( $_strCurentVariant, $_arrVariation );
						$_arrVariants=array_merge( $_arrVariants, $_arrGenerated );
						$_arrVariation=array();
					} else {
						$_arrVariants[]=$_strCurentVariant;
					}
					$_strCurentVariant='';
				break;
				case 4: // получили }. разбираемся с вариантом и выходим из рекурсии - ожидаем всё что угодно
					if ( !empty( $_arrVariation ) ) {
						if ( $symbol===null ) { // конец текста
							$this->_reduce=true;
						}
						$_arrGenerated=$this->everyPossibleVariants( $_strCurentVariant, $_arrVariation );
						$_arrVariants=array_merge( $_arrVariants, $_arrGenerated );
					} else {
						$_arrVariants[]=$_strCurentVariant;
					}
					return $_arrVariants;
				break;
				default: throw new Exception( Core_Errors::DEV.'|line: '.$this->_line.', col: '.$this->_column.' in "'.$this->_content.'"' ); break 2;
			}
		}
		return array();
	}

	private $_intCount=false; // Максимальное колличество генерируемых статей;
	private $_flgRandom=false; // Перемешать варианты для генерации;
	private $_arrCreated=array(); // Уже созданые ранее статьи;
	private $_intCountVariations=1; // Количеество возможных вариантов статей;

	public function init(){
		$this->_intCount=false;
		$this->_flgRandom=false;
		$this->_arrCreated=array();
	}

	public function setMax( $_int ){
		$this->_intCount = (int) $_int;
		return $this;
	}

	public function setRandom(){
		$this->_flgRandom=true;
		return $this;
	}

	public function setCreated( $_arr ){
		if ( empty($_arr) ){
			return $this;
		}
		$this->_arrCreated=(!is_array($_arr)) ? array($_arr) : $_arr;
		return $this;
	}

	public function isLast(){
		if ( empty($this->_arrCreated) ){
			return false;
		}
		return ( ($this->_intCountVariations - 1 ) - count($this->_arrCreated) ) <= 0;
	}

	private function everyPossibleVariants( $_str='', $_arr=array() ) {
		$f=new Core_Math_Combiner();
		if ( $this->_flgRandom ){
			foreach ( $_arr as &$_item ){
				shuffle( $_item );
			}
		}
		$f->setData( $_arr );
		if ( $this->_reduce ){
			foreach ($_arr as $_v ){
				$this->_intCountVariations=$this->_intCountVariations * count($_v);
			}
		}
		foreach($f as $e) {
			if ( $this->_reduce ){
				$_strArticle=str_replace( '%%', '%', vsprintf( $_str, $e ) );
				if ( !empty( $this->_arrCreated ) && in_array( str_replace(' ','',$_strArticle) , $this->_arrCreated ) ){
					continue;
				}
				$this->_intCount--;
				$arrRes[]=$_strArticle;
				if ( $this->_intCount <= 0 ){
					unset($_strArticle);
					break;
				}
			} else {
				$arrRes[]=vsprintf( $_str, $e );
			}
		}
		return $arrRes;
	}
}
?>