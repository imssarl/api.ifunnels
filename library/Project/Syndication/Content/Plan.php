<?php

 /**
 * генерация плана публикации КТ
 */
class Project_Syndication_Content_Plan {
	
	/**
	* список полей таблицы
	* @var array
	*/
	private $_fields=array( 'id', 'project_id', 'site_id', 'content_id', 'ext_post_id', 'flg_status', 'backlink' );
	
	/**
	* название таблицы
	* @var string
	*/
	private static $_table='cs_content2site';
	
	/**
	* объект Core_Data
	* @var object
	*/
	private $_data;

	/**
	* Статусы пунктов плана (cs_content2site.flg_status)
	* @var array
	*/
	public static $stat=array(
		'unpublished'=>0,
		'published'=>1,
		'error'=>2,
	);

	/**
	* конструктор
	* @return   void
	*/
	public function __construct( Core_Data $_data ) {
		$this->_data=$_data;
		//$this->_time=time();
	}

	public function generate() {
		$_content=new Project_Syndication_Content( $this->_data->filtered['id'] );
		if ( !$_content->getProjectPostNum( $intSitesForSyndicateNum ) ) {
			return false;
		}
		$_content->getList( $_arrPrePlan );
		Project_Syndication_Project_Category::get( $this->_data->filtered['id'], $arrCats );
		$_sites=new Project_Syndication_Sites();
		// получаем доступные сайты в нужных категориях. не больше необходимого кол-ва
		if ( !$_sites->getSitesByCat( $_arrSites, $arrCats, $intSitesForSyndicateNum ) ) {
			return false;
		}
		$_arrFullPlan=array();
		$_arrSitesIndex=$_arrSample=array_keys( $_arrSites );
		foreach( $_arrPrePlan as $v ) {
			$i=$v['sites_num'];
			if ( $i>$intSitesForSyndicateNum ) { // если доступных для размещения сайтов меньше чем пользователь планировал для одного из КТ
				$i=$intSitesForSyndicateNum;
			}
			do {
				$_arrFullPlan[]=array(
					'project_id'=>$this->_data->filtered['id'],
					'site_id'=>array_pop( $_arrSitesIndex ),
					'content_id'=>$v['id'],
					// узнать что с полем start. Пока постинг на все сайты одновременно. (поле из таблицы убрал)
				);
				$i--;
				if ( empty( $_arrSitesIndex ) ) {
					$_arrSitesIndex=$_arrSample;
				}
			} while ( $i>0 );
		}
		// бэклинкинг
		$backlink=new Project_Syndication_Backlink( $this->_data );
		if ( $backlink->get( $_arrRes, $intSitesForSyndicateNum ) ) {
			$_arrTmp=$_arrRes;
			foreach( $_arrFullPlan as $k=>$v ) {
				$_arrFullPlan[$k]['backlink']=array_rand( array_flip( Project_Syndication_Backlink::$templates ) );
				$_arrPair=array_pop( $_arrTmp );
				$_arrFullPlan[$k]['backlink']='<a href="'.$_arrPair['url'].'">'.str_replace( '[CATEGORY]', $_arrPair['cat_name'], $_arrFullPlan[$k]['backlink'] ).'</a>';
				if ( empty( $_arrTmp ) ) {
					$_arrTmp=$_arrRes;
				}
			}
		}
		if ( empty( $_arrFullPlan ) ) {
			return false;
		}
		return Core_Sql::setMassInsert( self::$_table, $_arrFullPlan );
	}

	public static function getPlanRaw( $_intProjectId ) {
		return Core_Sql::getAssoc( 'SELECT id, content_id, flg_status FROM '.self::$_table.' WHERE project_id='.$_intProjectId );
	}

	public static function getProjectPlan( &$arrPlan, &$arrContent, $_intProjectId=0 ) {
		$_arrPlan=Core_Sql::getAssoc( '
			SELECT p.*, s.site_id site_realid, s.flg_type site_type
			FROM '.Project_Syndication::$tables['content2site'].' p
			INNER JOIN '.Project_Syndication::$tables['sites'].' s ON s.id=p.site_id
			WHERE p.project_id="'.$_intProjectId.'" AND p.flg_status='.self::$stat['unpublished'].'
		' );
		$_arrCt=$arrRes=array();
		foreach( $_arrPlan as $v ) {
			$_arrCt[]=$v['content_id'];
			$v['statlink']=Project_Syndication_Statistics::generateLink( $v['id'], $v['site_id'] ); // скрытая ссылка которая дёргает сервер статистики при просмотре КТ
			$arrPlan[$v['site_realid']][]=$v; // контент разбрасываем по сайтам
		}
		$content=new Project_Syndication_Content( $_intProjectId );
		$content->toSyndicate()->withIds( array_unique( $_arrCt ) )->keyRecordForm()->getList( $arrContent );
	}

	// если хоть один КТ не публиковался то проект незавершён
	public static function isCompleted( $_intProjectId=0 ) {
		$_intRes=Core_Sql::getCell( 'SELECT COUNT(*) FROM '.self::$_table.' WHERE project_id='.$_intProjectId.' AND flg_status='.self::$stat['unpublished'] );
		return empty( $_intRes );
	}

	public static function setStatus( &$arrData, $_intStatus=NULL ) {
		if ( !in_array( $_intStatus, self::$stat )||empty( $arrData ) ) {
			return false;
		}
		$_arrIds=array();
		foreach( $arrData as $v ) {
			$_arrIds[]=$v['id'];
		}
		Core_Sql::setExec( 'UPDATE '.self::$_table.' SET flg_status='.$_intStatus.' WHERE id IN('.Core_Sql::fixInjection( $_arrIds ).')' );
		return true;
	}
}
?>