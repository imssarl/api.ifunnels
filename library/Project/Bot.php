<?php
class Project_Bot extends Core_Data_Storage {

	protected $_table='bot_ai';
	protected $_fields=array('id','query','reply','related_id','static_data','expected_response','edited','added');
	
	private $_dialog;
	private $_userId;

	public $_withLogger=true;
	public $_logger=false;
	
	function __construct( $_userId ) {
		$this->_dialog=new Project_Bot_Dialog();
		$this->_userId=$_userId;
	}
	
	public static function install(){
		Core_Sql::setExec('DROP TABLE IF EXISTS bot_ai');
		Core_Sql::setExec('CREATE TABLE `bot_ai` (
				`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
				`related_id` int(11) NULL,
				`query` TEXT NULL,
				`reply` TEXT NULL,
				`static_data` TEXT NULL,
				`expected_response` TEXT NULL,
				`edited` int(11) unsigned NOT NULL DEFAULT \'0\',
				`added` int(11) unsigned NOT NULL DEFAULT \'0\',
				PRIMARY KEY (`id`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8'
		);
	}
	
	protected function init() {
		parent::init();
	}

	protected function assemblyQuery() {
		parent::assemblyQuery();
	}
	
	private $_strCommmand=false;
	private $_strAnswer=false;
	
	public function setCommand( $_str='' ){
		if( !empty( $_str ) ){
			$this->_strCommmand=$_str;
			$this->_dialog->setEntered(array(
				'question'=>$this->_strCommmand,
				'user_id'=>$this->_userId
			))->set();
		}
		return $this;
	}
	
	public function removeCommand( $commandId ){
		if( !isset( $commandId ) ){
			return $this;
		}
		$this->_dialog->withIds( $commandId )->del();
		return $this;
	}
	
	public function getLastCommand( &$arrCommand ){
		$this->_dialog->withUserId( $this->_userId )->withOrder( 'd.id--up' )->getList( $_arrCommands );
		if( isset( $_arrCommands[0] ) ){
			$arrCommand=$_arrCommands[0];
		}
		return $this;
	}
	
	public function updateCatch( $_catch=array() ){
		$this->_dialog->withUserId( $this->_userId )->withOrder( 'd.id--up' )->getList( $_arrDialog );
		$_arrDialogLast=$_arrDialog[0];
		$_arrDialogLast['settings']=$_arrDialogLast['settings']+$_catch;
		$this->_dialog->setEntered( $_arrDialogLast )->set();
	}
	
	public function cleanUserDialog(){
		$_arrRemoveDialog=array();
		$this->_dialog->withUserId( $this->_userId )->onlyIds()->getList( $_arrRemoveDialog );
		$this->_dialog->withIds( $_arrRemoveDialog )->del();
	}
	
	public function getCatch( &$_catch ){
		if( isset( $this->_userId ) && !empty( $this->_userId ) ){
			$this->_dialog->withUserId( $this->_userId )->withOrder( 'd.id--dn' )->getList( $_arrDialog );
			$_catch=array();
			$_questionWithoutAnswer=false;
			$_questionWithoutAnswerPrev=false;
			$_question=false;
			$_prevKey=false;
			foreach( $_arrDialog as $_key=>$_dialog ){
				if( empty( $_dialog['answer'] ) ){
					$_questionWithoutAnswer=$_dialog;
					if( $_prevKey!==false ){
						$_questionWithoutAnswerPrev=$_arrDialog[$_prevKey];
					}
				}
				if( !empty( $_dialog['settings'] ) ){
					$_catch=$_dialog['settings']+$_catch;
				}
				$_prevKey=$_key;
			}
			if( $_questionWithoutAnswer !== false ){
				$this->getList( $_arrBotIi );
				$_arrAi=array();
				foreach( $_arrBotIi as $_botLogic ){
					$_arrAi[$_botLogic['id']]=$_botLogic;
				}
				unset( $_arrBotIi );
				$_result=array();
				foreach( $_arrAi as $_check ){
					if( ( !empty( $_questionWithoutAnswerPrev ) && !empty( $_check[ 'related_id' ] ) && $_check[ 'related_id' ]!=$_questionWithoutAnswerPrev['botai_id'] ) 
						|| ( empty( $_questionWithoutAnswerPrev ) && !empty( $_check[ 'related_id' ] ) ) 
					){
						// не обрабатываем ответы со связью, но без предыдущео запроса связи
					}else{
						if( !empty( $_questionWithoutAnswerPrev ) 
							&& !empty( $_arrAi[$_questionWithoutAnswerPrev['botai_id']] ) 
							&& !empty( $_arrAi[$_questionWithoutAnswerPrev['botai_id']]['expected_response'] )
							&& $_arrAi[$_questionWithoutAnswerPrev['botai_id']]['expected_response'] == $_check['id']
						){
							// запросы с ожиданием ответа
							$_result=array( array(
								'percent'=>100,
								'data'=>$_check
							));
							break;
						}else{
							// выборка исключительно записей с привязкой к предыдущей
							preg_match(self::getQueryPattern( $_check['query'] ), $_questionWithoutAnswer['question'], $matches);
							similar_text( $_questionWithoutAnswer['question'], $matches[0], $_percent );
							$_result[]=array(
								'percent'=>$_percent,
								'data'=>$_check
							);
						}
					}
				}
				$_parser=false;
				if( !empty( $_result ) ){
					$_maxPercent=$_result[0]['percent']; 
					$_parser=$_result[0]['data'];
					foreach ($_result as $_item) {
						if( $_item['percent'] > $_maxPercent ){
							$_maxPercent=$_item['percent'];
							$_parser=$_item['data'];
						}
					}
				}
				if( $_parser===false ){ // не смогли определить ответ
					return $this;
				}
				$_staticData=array();
				if( isset( $_parser['static_data'] ) && !empty( $_parser['static_data'] ) ){
					parse_str( str_replace( ',', '&', $_parser['static_data'] ), $_staticData );
				}
				$_catch=$_staticData+$_catch;
				if( strpos( $_parser['query'], '{' ) !== false && strpos( $_parser['query'], '}' ) !== false ){
					$matches=array();
					$_arrayIntersect=array();
					preg_match( self::getQueryPattern( $_parser['query'], $_arrayIntersect ), $_questionWithoutAnswer['question'], $matches );
					$_catch=$_staticData+array_intersect_key($matches, $_arrayIntersect)+$_catch;
					foreach( $_catch as &$_trimWOrds ){
						$_trimWOrds=trim( $_trimWOrds );
					}
				}
				$this->_dialog->setEntered(array(
					'settings'=>$_catch,
					'user_id'=>$this->_userId,
					'botai_id'=>$_parser['id'],
					'answer'=>'', // специально не даем ответ на такие запросы, просто возвращаем кэш, чтобы при следующей команде получить ответ по конечному запросу после обработки кэша
				)+$_questionWithoutAnswer)->set();
			}
		}
		return $this;
	}
	
	public function getAnswer( &$_return, &$_catch ){
		if( isset( $this->_userId ) && !empty( $this->_userId ) ){
			$this->_dialog->withUserId( $this->_userId )->withOrder( 'd.id--dn' )->getList( $_arrDialog );
			$_catch=array();
			$_questionWithoutAnswer=false;
			$_questionWithoutAnswerPrev=false;
			$_question=false;
			$_prevKey=false;
			$_fullAnswer=array();
			foreach( $_arrDialog as $_key=>$_dialog ){
				if( empty( $_dialog['answer'] ) ){
					$_questionWithoutAnswer=$_dialog;
					if( $_prevKey!==false ){
						$_questionWithoutAnswerPrev=$_arrDialog[$_prevKey];
					}
				}
				if( !empty( $_dialog['settings'] ) ){
					$_catch=$_dialog['settings']+$_catch;
				}
				$_prevKey=$_key;
				if( $_questionWithoutAnswer !== false ){
					$this->getList( $_arrBotIi );
					$_arrAi=array();
					foreach( $_arrBotIi as $_botLogic ){
						$_arrAi[$_botLogic['id']]=$_botLogic;
					}
					unset( $_arrBotIi );
					$_result=array();
					foreach( $_arrAi as $_check ){
						if( ( !empty( $_questionWithoutAnswerPrev ) && !empty( $_check[ 'related_id' ] ) && $_check[ 'related_id' ]!=$_questionWithoutAnswerPrev['botai_id'] ) 
							|| ( empty( $_questionWithoutAnswerPrev ) && !empty( $_check[ 'related_id' ] ) ) 
						){
							// не обрабатываем ответы со связью, но без предыдущео запроса связи
						}else{
							if( !empty( $_questionWithoutAnswerPrev ) 
								&& !empty( $_arrAi[$_questionWithoutAnswerPrev['botai_id']] ) 
								&& !empty( $_arrAi[$_questionWithoutAnswerPrev['botai_id']]['expected_response'] )
								&& $_arrAi[$_questionWithoutAnswerPrev['botai_id']]['expected_response'] == $_check['id']
							){
								// запросы с ожиданием ответа
								$_result=array( array(
									'percent'=>100,
									'data'=>$_check
								));
								break;
							}else{
								// выборка исключительно записей с привязкой к предыдущей
								preg_match(self::getQueryPattern( $_check['query'] ), $_questionWithoutAnswer['question'], $matches);
								similar_text( $_questionWithoutAnswer['question'], $matches[0], $_percent );
								$_result[]=array(
									'percent'=>$_percent,
									'data'=>$_check
								);
							}
						}
					}
					$_parser=false;
					$_maxPercent=0;
					if( !empty( $_result ) ){
						$_maxPercent=$_result[0]['percent']; 
						$_parser=$_result[0]['data'];
						foreach ($_result as $_item) {
							if( $_item['percent'] > $_maxPercent ){
								$_maxPercent=$_item['percent'];
								$_parser=$_item['data'];
							}
						}
					}
					if( $_parser===false || $_maxPercent==0 ){ // не смогли определить ответ
						$_return='';
						return $this;
					}
					$_stepReturn=$_parser['reply'];
					if( $this->_withLogger ){
						$_writer=new Zend_Log_Writer_Stream( Zend_Registry::get('config')->path->absolute->logfiles.'Facebook_Messanger/user_'.$this->_userId.'.log' );
						$_writer->setFormatter( new Zend_Log_Formatter_Simple("%timestamp% %priorityName% (%priority%): %message%\r\n") );
						$this->_logger=new Zend_Log( $_writer );
						$this->_logger->info('Bot : '.serialize($_parser) );
					}
					if( strpos( $_parser['reply'], '(' ) !== false && strpos( $_parser['reply'], ')' ) !== false ){
						preg_match_all('/\\((.*?)\\)/is', $_stepReturn, $matches);
						foreach( $matches[1] as $_strRandWord ){
							$_changeWord=$_strRandWord;
							$_checkTail='';
							if( strpos( $_strRandWord, '|' ) !== false ){
								$_changeWord=explode( '|', $_strRandWord );
								$_changeWordKey=array_rand($_changeWord);
								$_changeWord=$_changeWord[$_changeWordKey];
							}else{
								$_checkTail='?';
							}
							$_stepReturn=str_replace( '('.$_strRandWord.')'.$_checkTail, $_changeWord, $_stepReturn );
						}
					}
					$_staticData=array();
					if( isset( $_parser['static_data'] ) && !empty( $_parser['static_data'] ) ){
						parse_str( str_replace( ',', '&', $_parser['static_data'] ), $_staticData );
					}
					$_catch=$_staticData+$_catch;
					if( strpos( $_parser['query'], '{' ) !== false && strpos( $_parser['query'], '}' ) !== false ){
						$matches=array();
						$_arrayIntersect=array();
						preg_match( self::getQueryPattern( $_parser['query'], $_arrayIntersect ), $_questionWithoutAnswer['question'], $matches );
						$_catch=$_staticData+array_intersect_key($matches, $_arrayIntersect)+$_catch;
						foreach( $_catch as &$_trimWOrds ){
							$_trimWOrds=trim( $_trimWOrds );
						}
						$matches=array();
						preg_match_all('/{(.*?)}/is', $_stepReturn, $matches);
						foreach( $matches[0] as $key=>$_replaceWord ){
							if( isset( $_catch[$matches[1][$key]] ) ){
								$_stepReturn=str_replace( $_replaceWord, $_catch[$matches[1][$key]], $_stepReturn );
							}
						}
					}else{
						//$_stepReturn=$_parser['reply'];
					}
					$this->_dialog->setEntered(array(
						'settings'=>$_catch,
						'user_id'=>$this->_userId,
						'botai_id'=>$_parser['id'],
						'answer'=>$_stepReturn
					)+$_questionWithoutAnswer)->set();
					$_fullAnswer[]=$_stepReturn;
				}
			}
		}
		$_return=implode( ' ', $_fullAnswer );
		return $this;
	}

	public static function getQueryPattern( $_queryString, &$_arrayIntersect ){
		$matches=array(); // выбираем все включения которые длжны уйти в кэш
		$_endTail='(\s*|$)';
		$_queryString=str_replace( array('\\','^','$'.'*','+','?'), array('\\\\','\\^','\\$','\\*','\\+','\\?'), $_queryString );
		if( strpos( $_queryString, '[' ) !== false || strpos( $_queryString, ']' ) !== false ){
			$_queryString=str_replace( array('[',']'), array('\\[','\\]'), $_queryString );
		}
		if( strpos( $_queryString, '(' ) !== false && strpos( $_queryString, ')' ) !== false ){
			preg_match_all('/\\((.*?)\\)/is', $_queryString, $matches);
			foreach( $matches[1] as $_strRandWord ){
				$_changeWord=$_strRandWord;
				$_checkTail='';
				if( strpos( $_strRandWord, '|' ) === false ){
					$_checkTail='*+';
				}
				$_queryString=str_replace( '('.$_strRandWord.')',  '('. str_replace(' ', '\s', $_changeWord ).')'.$_checkTail, $_queryString );
			}
		}
		if( substr($_queryString, -3) == ')*+' ){
			$_endTail='|$)';
			$_queryString=substr($_queryString, 0, -3);
		}
		if( substr($_queryString, -1) == ')' ){
			$_endTail=')*+$';
			$_queryString=substr($_queryString, 0, -1);
		}
		$matches=array();
		preg_match_all('/{(.*?)}/is', $_queryString, $matches);
		$_queryString= str_replace(' ', '\s*', $_queryString );
		foreach( $matches[0] as $key=>$_replaceWord ){
			$_arrayIntersect[$matches[1][$key]]=true;
			$_queryString=str_replace( $_replaceWord, '(?P<'.$matches[1][$key].'>.*?)', $_queryString );
		}
		if( substr($_queryString, -3) == '*?)' ){
			$_queryString.='$';
		}
		if( substr($_queryString, 4) == '(?P<' ){
			$_queryString='^'.$_queryString;
		}
		$_queryString='/(?i)'.$_queryString.$_endTail.'/'; // (?i) делает выражение нечуствительным к регистру
		return $_queryString;
	}
	
}
?>