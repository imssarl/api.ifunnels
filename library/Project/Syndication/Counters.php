<?php

 /**
 * управление счётчиками
 */
class Project_Syndication_Counters {

	private $_placmentCost=1;

	private $_viewCost=0.1;

	private $_startPoints=100;

	private $_userId=0;

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

	private function getUserPoints( &$arrRes, $_intUserId=0 ) {
		$arrRes=Core_Sql::getRecord( 'SELECT * FROM '.Project_Syndication::$tables['points'].' WHERE user_id='.$_intUserId );
		if ( empty( $arrRes ) ) {
			return false;
		}
		$arrRes['balance']=$arrRes['actual']-$arrRes['planned'];
		return true;
	}

	public function initUserPoints( &$arrRes ) {
		if ( $this->getUserPoints( $arrRes, $this->_userId ) ) {
			return;
		}
		Core_Sql::setInsert( Project_Syndication::$tables['points'], array( 
			'user_id'=>$this->_userId, 
			'actual'=>$this->_startPoints, 
			'planned'=>0 ) );
		$this->getUserPoints( $arrRes, $this->_userId );
	}

	// начисление баллов владельцам сайтов за просмотр чужого контента
	public function setViewPoint() {
		$_intTime=time();
		$_arrViews=Core_Sql::getKeyVal( '
			SELECT site_id, COUNT(*) views 
			FROM '.Project_Syndication::$tables['statistic'].' 
			WHERE added<'.$_intTime.' AND flg_status=0 
			GROUP BY site_id' );
		Core_Sql::setConnectToServer( 'members.creativenichemanager.info' );
		$_arrSites=Core_Sql::getKeyVal( 'SELECT * FROM '.Project_Syndication::$tables['sites'].' WHERE id IN('.Core_Sql::fixInjection( array_keys( $_arrViews ) ).')' );
		$_arrPoints=array();
		foreach( $_arrSites as $v ) {
			if ( empty( $_arrPoints[$v['user_id']] ) ) {
				$_arrPoints[$v['user_id']]=0;
			}
			$_arrPoints[$v['user_id']]+=$_arrViews[$v['id']];
		}
		foreach( $_arrPoints as $intUserId=>$_intViews ) {
			$intPoints=round( ($_intViews*$this->_viewCost) );
			if ( empty( $intPoints ) ) {
				continue;
			}
			self::increase( $intPoints, $intUserId );
		}
		Core_Sql::renewalConnectFromCashe();
		Core_Sql::setExec( 'UPDATE '.Project_Syndication::$tables['statistic'].' SET flg_status=1 WHERE added<'.$_intTime.' AND flg_status=0' );
	}

	public static function setPlacementPoint( $_intProjectId=0 ) {
		// уменьшаем пойнты владельцу проекта
		$_intDecreasePlanned=Core_Sql::getCell( 'SELECT COUNT(*) FROM '.Project_Syndication::$tables['content2site'].' WHERE project_id='.$_intProjectId );
		if ( empty( $_intDecreasePlanned ) ) {
			return;
		}
		$_intDecreaseActual=Core_Sql::getCell( '
			SELECT COUNT(*) 
			FROM '.Project_Syndication::$tables['content2site'].' 
			WHERE 
				project_id='.$_intProjectId.' AND 
				flg_status='.Project_Syndication_Content_Plan::$stat['published'] 
		);
		$_intPrjUserId=Core_Sql::getCell( 'SELECT user_id FROM '.Project_Syndication::$tables['project'].' WHERE id='.$_intProjectId );
		Core_Sql::setExec( '
			UPDATE '.Project_Syndication::$tables['points'].' 
			SET 
				actual=actual-'.$_intDecreaseActual.', 
				planned=planned-'.$_intDecreasePlanned.' 
			WHERE 
				user_id=(SELECT user_id FROM '.Project_Syndication::$tables['project'].' WHERE id='.$_intProjectId.')
		' );
		// увеличиваем пойнты владельцам сайтов на которые постились статьи
		$_arrIncrease=Core_Sql::getAssoc( '
			SELECT s.user_id, COUNT(*) point 
			FROM '.Project_Syndication::$tables['content2site'].' c2s
			INNER JOIN '.Project_Syndication::$tables['sites'].' s ON s.id=c2s.site_id
			WHERE c2s.flg_status='.Project_Syndication_Content_Plan::$stat['published'].'
			GROUP BY s.user_id
		' );
		foreach( $_arrIncrease as $v ) {
			self::increase( $v['point'], $v['user_id'] );
		}
	}

	public static function increasePlanned( $_intPoint=0, $_intUserId=0 ) {
		if ( empty( $_intPoint )||empty( $_intUserId ) ) {
			return;
		}
		Core_Sql::setExec( 'UPDATE '.Project_Syndication::$tables['points'].' SET planned=planned+'.$_intPoint.' WHERE user_id='.$_intUserId );
	}

	private static function increase( $_intPoint=0, $_intUserId=0 ) {
		if ( empty( $_intPoint )||empty( $_intUserId ) ) {
			return;
		}
		Core_Sql::setExec( 'UPDATE '.Project_Syndication::$tables['points'].' SET actual=actual+'.$_intPoint.' WHERE user_id='.$_intUserId );
	}

	public static function decrease( $_intPoint=0, $_intUserId=0 ) {
		if ( empty( $_intPoint )||empty( $_intUserId ) ) {
			return;
		}
		Core_Sql::setExec( 'UPDATE '.Project_Syndication::$tables['points'].' SET actual=actual-'.$_intPoint.' WHERE user_id='.$_intUserId );
	}
}
?>