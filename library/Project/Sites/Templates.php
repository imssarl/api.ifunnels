<?php


/**
 * Template management.
 */
class Project_Sites_Templates extends Core_Data_Storage {

	protected $_fields=array( 'id', 'flg_type', 'flg_belong', 'flg_header', 'priority', 'filename', 'title', 'url', 'description', 'added' );
	protected $_table='es_templates';
	private $_tableLinkToUser='es_template2user';
	private $_tableLinkToSite='es_template2site';

	// настройки для getList
	private $_onlySiteId=false;
	private $_onlyCommon=false; // только общие
	private $_withPreview=false; // с путями до картинки
	private $_withFilename=false; // c сортировкой
	private $_withTitle=false; // c именем шаблона
	private $_withRight=false;
	private $_maxArchiveSize=3145728; // максимально разрешённый размер архива
	private $_withRandom=false;
	/**
	 * дира с общими шаблонами
	 * @var string
	 */
	private $_commonDir ='';

	/**
	 * дира с шаблонами пользовтеля
	 * @var string
	 */
	private $_userDir='';

	public function __construct( $_type='' ) {
		if ( empty( Project_Sites::$code[$_type] ) ) {
			throw new Exception( Core_Errors::DEV.'|Site Type not set' );
		}
		$this->_siteType=$_type;
		$this->_siteCode=Project_Sites::$code[$_type];
		$_strDir='sites'.DIRECTORY_SEPARATOR.$this->_siteCode;
		$this->_commonDir=Zend_Registry::get( 'config' )->path->relative->user_files.$_strDir.DIRECTORY_SEPARATOR;
		if ( !Zend_Registry::get( 'objUser' )->prepareDtaDir( $_strDir ) ) {
			throw new Exception( Core_Errors::DEV.'|Zend_Registry::get( \'objUser\' )->prepareDtaDir( $_strDir ) no dir set' );
		}
		$this->_userDir=$_strDir;
		$this->_userDirPreview=Zend_Registry::get( 'config' )->path->html->user_data.Core_Users::$info['id'].'/sites/'.$this->_siteCode.'/';
		$this->_commonDirPreview=Zend_Registry::get( 'config' )->path->html->user_files.'sites/'.$this->_siteCode.'/';
	}

	/**
	 * Восстановление ссылок на стандартные шаблоны для пользователся
	 * перед этим сначала удалим, чтобы небыло дубликатов
	 *
	 * @return boolean
	 */
	public function reassignCommonToUser() {
		$this->onlyCommon()->onlyIds()->getList( $_arrIds );
		Core_Sql::setExec( 'DELETE FROM '.$this->_tableLinkToUser.' WHERE template_id IN('.Core_Sql::fixInjection( $_arrIds ).') AND user_id='.Core_Users::$info['id'] );
		sleep(3);
		return $this->linkToUser( Core_Users::$info['id'], $_arrIds );
	}

	/**
	 * Добавление ссылок на стандартные шаблоны для нового пользователся
	 * проблема может возникнуть только в случае если пользователь удалил все стандартные - будем решать по факту
	 *
	 * @return boolean
	 */
	public function addCommonTemplatesToNewUser() {
		if ( !$this->onlyCommon()->onlyIds()->getList( $_arrIds )->checkEmpty() ) {
			return false;
		}
		$_arrTest=Core_Sql::getField( 'SELECT template_id FROM '.$this->_tableLinkToUser.' WHERE template_id IN('.Core_Sql::fixInjection( $_arrIds ).') AND user_id='.Core_Users::$info['id'] );
		sleep(3);
		if ( !empty( $_arrTest ) ) {
			return true;
		}
		return $this->linkToUser( Core_Users::$info['id'], $_arrIds );
	}

	/**
	 * Удаление шаблона из списка+попытка удалить физически
	 * пропадает из списков но при наличии связанных сайтов физически не удаляется
	 *
	 * @param int $_intId
	 * @return boolean
	 */
	public function deleteCommonTemplate( $_intId=0 ) {
		if ( empty( $_intId ) ) {
			return false;
		}
		Core_Sql::setExec( 'DELETE FROM '.$this->_tableLinkToUser.' WHERE template_id='.$_intId );
		$this->unlinkTemplates( $_intId );
		return true;
	}

	/**
	 * Удаление пользовательского шаблона из списка+попытка удалить физически
	 * пропадает из списков но при наличии связанных сайтов физически не удаляется
	 *
	 * @param int $_intId
	 * @return boolean
	 */
	public function deleteUserTemplate( $_intId=0 ) {
		if ( empty( $_intId ) ) {
			return false;
		}
		return $this->unlinkFromUser( Core_Users::$info['id'], $_intId );
	}

	/**
	 * Добавление ссылок на пользователя
	 *
	 * @param mix  $_arrUserIds - один или несколько id пользователей
	 * @param mix  $_arrTemplatesIds - один или несколько темплэйтов
	 * @return boolean
	 */
	public  function linkToUser( $_arrUserIds=array(), $_arrTemplatesIds=array() ) {
		if ( empty( $_arrUserIds )||empty( $_arrTemplatesIds ) ) {
			return false;
		}
		if ( !is_array( $_arrUserIds ) ) {
			$_arrUserIds=array( $_arrUserIds );
		}
		if ( !is_array( $_arrTemplatesIds ) ) {
			$_arrTemplatesIds=array( $_arrTemplatesIds );
		}
		$_arrIns=array();
		foreach( $_arrUserIds as $u ) {
			$_templatesForUser = Core_Sql::getField( 'SELECT template_id FROM ' . $this->_tableLinkToUser . ' WHERE user_id=' . $u );
			foreach( $_arrTemplatesIds as $p ) {
				if( !in_array( $p, $_templatesForUser ) )
					$arrIns[]=array( 'user_id'=>$u, 'template_id'=>$p );
			}
		}
		if( !empty( $arrIns ) ){
			return Core_Sql::setMassInsert( $this->_tableLinkToUser, $arrIns );
		}
		return true;
	}

	public function getErrors( &$arrRes ){
		$arrRes=Core_Data_Errors::getInstance()->getErrors();
		return $this;
	}

	/**
	 * Добавление ссылок на сайты + удаление ссылок (если указать только $_arrSiteIds)
	 * тут как бы linkToUser+unlinkFromUser только для сайтов
	 * @param mix  $_arrSiteIds - один или несколько id сайтов
	 * @param mix  $_arrTemplatesIds - один или несколько темплэйтов (осталось от Project_Wpress_Plugins обычно тут один шаблон или ниодного, при удалении)
	 * @return boolean
	 */
	public function siteLink( $_arrSiteIds=array(), $_arrTemplatesIds=array() ) {
		if ( empty( $_arrSiteIds ) ) {
			return false;
		}
		if ( !is_array( $_arrSiteIds ) ) {
			$_arrSiteIds=array( $_arrSiteIds );
		}
		if ( !is_array( $_arrTemplatesIds ) ) {
			$_arrTemplatesIds=array( $_arrTemplatesIds );
		}
		$_arrOldTemplatesIds=Core_Sql::getField( '
			SELECT template_id
			FROM '.$this->_tableLinkToSite.'
			WHERE site_id IN('.Core_Sql::fixInjection( $_arrSiteIds ).') AND flg_type="'.$this->_siteType.'"
			GROUP BY template_id
		' );
		// чистим таблицу линков
		Core_Sql::setExec( 'DELETE FROM '.$this->_tableLinkToSite.' WHERE site_id IN('.Core_Sql::fixInjection( $_arrSiteIds ).') AND flg_type="'.$this->_siteType.'"' );
		if ( empty( $_arrTemplatesIds ) ) {
			$this->unlinkTemplates( $_arrOldTemplatesIds ); // тут удаляем все
			return true;
		}
		$this->unlinkTemplates( array_diff( $_arrOldTemplatesIds, $_arrTemplatesIds ) ); // тут удаляем только те которые были отлинкованы от блога
		$_arrIns=array();
		foreach( $_arrSiteIds as $b ) {
			foreach( $_arrTemplatesIds as $p ) {
				$arrIns[]=array( 'site_id'=>$b, 'template_id'=>$p, 'flg_type'=>$this->_siteType );
			}
		}
		if( !empty( $arrIns ) ){
			return Core_Sql::setMassInsert( $this->_tableLinkToSite, $arrIns );
		}
		return true;
	}

	/**
	 * добавление общего шаблона
	 * @param array $_arrDta
	 * @param array $_arrZip
	 * @return bool
	 */
	public function addCommonTemplate( $_arrDta=array(), $_arrZip=array() ) {
		if ( $this->onlyCommon()->withFilename( $_arrZip['name'] )->getList( $_arrTmp )->checkEmpty() ) {
			return Core_Data_Errors::getInstance()->setError('This template has alredy exist');// такой шаблон уже есть
		}
		$_arrZip['name_only']=Core_Files::getFileName( $_arrZip['name'] );
		if ( !$this->checkFile( $_arrZip ) ) {
			return false; // некорректный файл
		}
		// если всё нормально то записываем перепакованную тему + картинку в папку общих шаблонов
		$_bool1=copy( $this->_extractDir.$_arrZip['name'], $this->_commonDir.$_arrZip['name'] );
		$_bool2=copy( $this->_extractDir.$_arrZip['name_only'].'.jpg', $this->_commonDir.$_arrZip['name_only'].'.jpg' );
		if ( !$_bool1||!$_bool2 ) {
			return Core_Data_Errors::getInstance()->setError('Can\'t copy scrinshot');
		}
		// в базу данных
		$_data=new Core_Data( $_arrDta );
		$_data->setFilter();
		$_intId=Core_Sql::setInsert( $this->_table, $_data->setMask( $this->_fields )->getValidCurrent( $_arrZip+array(
			'flg_type'=>$this->_siteType,
			'flg_belong'=>0,
			'flg_header'=>empty( $_data->filtered['flg_header'] )? 0:1,
			'priority'=>empty( $_data->filtered['priority'] )? 0:$_data->filtered['priority'],
			'filename'=>$_arrZip['name'],
			'added'=>time()
		) ) );
		if ( empty( $_intId ) ) {
			return false;
		}
		// и линки всем текущим пользователям
		$_users=new Project_Users_Management();
		$_users->onlyIds()->withRights( ($this->_siteType==Project_Sites::NCSB)?'use_ncsb_templates':'use_nvsb_templates' )->getList( $_arrUsersIds );
		return $this->linkToUser( $_arrUsersIds, $_intId );
	}

	/**
	 * добавление пользовтаельского шаблона
	 * @param array $_arrZip
	 * @return bool
	 */
	public function addUserTemplate( $_arrZip=array() ) {
		$_arrZip['name_only']=Core_Files::getFileName( $_arrZip['name'] );
		if ( !$this->checkFile( $_arrZip ) ) {
			return Core_Data_Errors::getInstance()->setError('Zip archive is not correct'); // некорректный файл
		}
		if ( $this->withFilename( $_arrZip['name'] )->getList( $_arrTmp )->checkEmpty() ) {
			return Core_Data_Errors::getInstance()->setError('This template has alredy exist');// такой шаблон уже есть
		}
		// если всё нормально то записываем перепакованную тему + картинку в папку общих шаблонов
		$_bool1=copy( $this->_extractDir.$_arrZip['name'], $this->_userDir.$_arrZip['name'] );
		if( is_file($this->_extractDir.Core_Files::getFileName( $_arrZip['name_only'] ).'.jpg') ) {
			$_bool2=copy( $this->_extractDir.Core_Files::getFileName( $_arrZip['name_only'] ).'.jpg', $this->_userDir.Core_Files::getFileName( $_arrZip['name_only'] ).'.jpg' );
		} else {
			$_bool2=true;
		}
		if ( !$_bool1||!$_bool2 ) {
			return false;
		}
		// в базу данных
		$_data=new Core_Data();
		$_intId=Core_Sql::setInsert( $this->_table, $_data->setMask( $this->_fields )->getValidCurrent( $_arrZip+array(
			'filename'=>$_arrZip['name'],
			'added'=>time(),
			'flg_type'=>$this->_siteType
		) ) );
		return $this->linkToUser( Core_Users::$info['id'], $_intId ); // и линки текущему пользователю
	}

	/**
	 * Копирование шаблонов.
	 * @param array $_arrData
	 * @return bool
	 */
	public function copyTemplate( $_arrData=array() ){
		if( empty( $_arrData ) ){
			return Core_Data_Errors::getInstance()->setError('Empty entered data');
		}
		if ( !$this->onlyOne()->withIds( $_arrData['id'] )->getList( $_arr )->checkEmpty() ){
			return Core_Data_Errors::getInstance()->setError('Can\'t find template to copy');
		}
		if ( $this->withTitle( $_arrData['name'] )->getList( $_arrTmp )->checkEmpty() ) {
			return Core_Data_Errors::getInstance()->setError('This template has alredy exist'); // такой шаблон уже есть
		}
		// от куда будем копировать
		$_fromDir = ( $_arr['flg_belong'] == 0 )? $this->_commonDir : $this->_userDir;
		$_arrData['filename'] = str_replace(' ','_',$_arrData['name']).'.zip';

		// Копируем архив с шаблоном
		if ( !copy( $_fromDir . $_arr['filename'], $this->_userDir . $_arrData['filename'] ) ){
			return Core_Data_Errors::getInstance()->setError('Can\'t copy template');
		}
		// Копируем превьюху
		if ( !copy( $_fromDir . Core_Files::getFileName( $_arr['filename'] ) . '.jpg', $this->_userDir . Core_Files::getFileName($_arrData['filename']) . '.jpg' ) ){
			return Core_Data_Errors::getInstance()->setError('Can\'t copy preview');
		}
		// пишем в базу
		unset($_arr['id']);
		$_arr['filename']=$_arrData['filename'];
		$_arr['title']=$_arrData['name'];
		$_arr['added']=time();
		$_arr['flg_belong']=1;
		$_data=new Core_Data();
		$_intId=Core_Sql::setInsert( $this->_table, $_data->setMask( $this->_fields )->getValidCurrent( $_arr ) );
		return $this->linkToUser( Core_Users::$info['id'], $_intId ); // и линки текущему пользователю
	}

	/**
	 * парисинг description.css
	 * @param $arrRes
	 * @param string $_strPathToFile
	 * @return bool
	 */
	public  function parseDesc( &$arrRes, $_strPathToFile='' ) {
		if ( !is_file( $_strPathToFile ) ) {
			return Core_Data_Errors::getInstance()->setError('Can\'t find file description.css');
		}
		Core_Files::getContent( $arrRes['description'], $_strPathToFile );
		$arrRes['title']=Core_Files::getFileName( $arrRes['name'] );
		$arrRes['screenshot']='datas/desc/screenshot.jpg';
		return true;
	}

	/**
	 * проверка файлов
	 * @param $arrZip
	 * @return bool
	 */
	public function checkFile( &$arrZip ) {
		if ( empty( $arrZip ) ) {
			return Core_Data_Errors::getInstance()->setError('Can\'t find archive with template');
		}
		if( $arrZip['size']>$this->_maxArchiveSize ){
			return Core_Data_Errors::getInstance()->setError('Uploaded file size is more than '.$this->_maxArchiveSize.'MB.Please upload below '.$this->_maxArchiveSize.'Mb');
		}
		if( Core_Files::getExtension( $arrZip['name'] )!='zip' ){
			return Core_Data_Errors::getInstance()->setError('Invalid file.Please upload only zip file.');
		}
		$this->_extractDir='Project_Sites_Templates@checkFile';
		if ( !Zend_Registry::get( 'objUser' )->prepareTmpDir( $this->_extractDir ) ) {
			return Core_Data_Errors::getInstance()->setError('Can\'t create temp dir');
		}
		$zip=new Core_Zip();
		if ( !$zip->setDir( $this->_extractDir )->extractZip( $arrZip['tmp_name'] ) ) {
			return Core_Data_Errors::getInstance()->setError('Zip file is not correct'); // проверка что это корректный zip и распаковываем во временную папку
		}
		if ( !Core_Files::dirScan( $_arr, $this->_extractDir ) ) {
			return Core_Data_Errors::getInstance()->setError('Zip archive is empty');// пусто
		}
		foreach( $_arr as $_strDir=>$_arrFiles ) {
			// для NCSB и NVSB
			if( in_array( $this->_siteType , array( Project_Sites::NCSB, Project_Sites::NVSB ) ) && (!in_array('config.php', $_arrFiles ) && !in_array('feed.xml', $_arrFiles )) ){
				continue;
			}
			if( in_array( $this->_siteType , array( Project_Sites::NCSB, Project_Sites::NVSB ) ) && !$this->parseDesc( $arrZip, $_strDir.DIRECTORY_SEPARATOR.'datas'.DIRECTORY_SEPARATOR.'desc'.DIRECTORY_SEPARATOR.'description.txt') ){
				return Core_Data_Errors::getInstance()->setError('Template is not correct');
			}
			// превьюха шаблона
			if ( is_file( $_strDir.DIRECTORY_SEPARATOR.$arrZip['screenshot'] ) ) {
				if ( !copy( $_strDir.DIRECTORY_SEPARATOR.$arrZip['screenshot'], $this->_extractDir.$arrZip['name_only'].'.jpg' ) ) {
					return false;
				}
			}
			// перепаковываем тему (файлы сразу в корне шаблона)
			if ( true!==$zip->open( $this->_extractDir.$arrZip['name'], ZipArchive::CREATE ) ) {
				return Core_Data_Errors::getInstance()->setError('Can\'t create archive');
			}
			if ( !$zip->addDirAndClose( $_strDir ) ) {
				return Core_Data_Errors::getInstance()->setError('Can\'t close archive');
			}
			return true;
		}
		Core_Data_Errors::getInstance()->setError('Template is not correct.');
		return false;
	}

	public function template2edit( &$arrRes, $_intId ){
		$this->_extractDir='Project_Sites_Templates@template2edit';
		$this->onlyOne()->withIds( $_intId )->getList( $_arrTheme );
		if ( !Zend_Registry::get( 'objUser' )->prepareTmpDir( $this->_extractDir ) ) {
			return Core_Data_Errors::getInstance()->setError('Can\'t create temp dir');
		}
		$zip=new Core_Zip();
		if ( !$zip->setDir( $this->_extractDir )->extractZip( $this->_userDir.$_arrTheme['filename'] ) ) {
			return false;
		}
		if ( !Core_Files::dirScan( $arrRes, $this->_extractDir ) ) {
			return false;
		}
		foreach ( $arrRes as $k1=>$v ){
			foreach ( $v as $k2=>$_file)
			if ( in_array( Core_Files::getExtension( $_file ), array( 'jpg', 'svn', 'png', 'gif' ) ) ){
				unset( $arrRes[$k1][$k2] );
			}
		}
		return true;
	}

	public function saveTemplate( $_intId, $_arrImg=array() ){
		$_extractDir=Zend_Registry::get( 'objUser' )->getTmpDirName();
		$_extractDir.='Project_Sites_Templates@template2edit'.DIRECTORY_SEPARATOR;
		if ( !$this->onlyOne()->withIds( $_intId )->getList( $_arrTheme )->checkEmpty() ){
			return false;
		}
		// меняем header у шаблона. заточено под ncsb, nvsb, psb
		if( !empty( $_arrImg ) && in_array( Core_Files::getExtension( $_arrImg['name'] ), array('png','jpg','gif') ) ) {
			if ( !copy( $_arrImg['tmp_name'], $_extractDir . 'images'.DIRECTORY_SEPARATOR.'header.'.Core_Files::getExtension( $_arrImg['name'] ) ) ) {
				return Core_Data_Errors::getInstance()->setError('Can\'t save template');
			}
		}
		// перепаковываем тему (файлы сразу в корне шаблона)
		$zip=new Core_Zip();
		if ( true!==$zip->open( $_extractDir.$_arrTheme['filename'], ZipArchive::CREATE ) ) {
			return Core_Data_Errors::getInstance()->setError('Can\'t save template');
		}
		if ( !$zip->addDirAndClose( $_extractDir ) ) {
			return Core_Data_Errors::getInstance()->setError('Can\'t save template');
		}
		if ( !copy($_extractDir.$_arrTheme['filename'],$this->_userDir.$_arrTheme['filename']) ){
			return Core_Data_Errors::getInstance()->setError('Can\'t save template');
		}
		return true;
	}

	public function onlySiteId( $_intId ){
		$this->_onlySiteId=intval( $_intId );
		return $this;
	}

	// только общие шаблоны
	public function onlyCommon() {
		$this->_onlyCommon=true;
		return $this;
	}

	// c html путём - для отображения превьюшек на веб странице
	public function withPreview() {
		$this->_withPreview=true;
		return $this;
	}

	public function withFilename( $_str='' ) {
		$this->_withFilename=$_str;
		return $this;
	}

	public function withTitle( $_str='' ) {
		$this->_withTitle=$_str;
		return $this;
	}

	public function withRight(){
		$this->_withRight=true;
		return $this;
	}

	public function withRandom(){
		$this->_withRandom=true;
		return $this;
	}

	public function getList( &$mixRes ){
		$_toSelect=$this->_toSelect;
		$_onlyIds=$this->_onlyIds;
		parent::getList( $mixRes );
		if( !$_toSelect&&!$_onlyIds ){
			$this->addPaths( $mixRes );
		}
		return $this;
	}

	protected function init(){
		parent::init();
		$this->_onlySiteId=false;
		$this->_onlyCommon=false;
		$this->_withPreview=false;
		$this->_withFilename=false;
		$this->_withTitle=false;
		$this->_withRight=false;
		$this->_withRandom=false;
	}

	protected function assemblyQuery(){
		parent::assemblyQuery();
		$this->_crawler->set_where( 'd.flg_type='.$this->_siteType ); // обязательное условие
		// в этом случае надо отображать только общие шаблоны на которые есть ссылка в $this->_tableLinkToUser,
		// т.к. если сслки нет это означает что шаблон удалён, даже если есть в $this->_tablePlugins
		if ( $this->_onlyCommon ) {
			$this->_crawler->set_from( 'INNER JOIN '.$this->_tableLinkToUser.' lu ON lu.template_id=d.id' );
			$this->_crawler->set_where( 'd.flg_belong=0' );
		} elseif ( Core_Users::$info['id'] ) {
			$this->_crawler->set_from( 'INNER JOIN '.$this->_tableLinkToUser.' lu ON lu.template_id=d.id AND lu.user_id='.Core_Users::$info['id'] );
		}
		if ( !empty( $this->_onlySiteId ) ) {
			$this->_crawler->set_from( 'INNER JOIN '.$this->_tableLinkToSite.' lb ON lb.template_id=d.id AND lb.site_id='.$this->_onlySiteId.' AND lb.flg_type='.Core_Sql::fixInjection($this->_siteType) );
		}
		if ( !empty( $this->_withFilename ) ) {
			$this->_crawler->set_where( 'd.filename='.Core_Sql::fixInjection( $this->_withFilename ) );
		}
		if ( !empty( $this->_withTitle ) ) {
			$this->_crawler->set_where( 'd.title='.Core_Sql::fixInjection( $this->_withTitle ) );
		}
		if( $this->_withRight ){
			$this->_crawler->set_where('d.id IN ('. Project_Acs_Template::getAccessSql( $this->_siteType ) .') OR flg_belong=1');
		}
		if ( Core_Users::$info['id']||!empty( $this->_onlySiteId )||$this->_onlyCommon ) {
			$this->_crawler->set_group( 'd.id' );
		}
		if( $this->_withRandom ){
			$this->_crawler->set_order('RAND()');
		}
	}

	/**
	 * html путь до картинки (preview) и системный путь до архива(path)
	 * @param $mixRes
	 * @return bool
	 */
	private function addPaths( &$mixRes ) {
		if ( empty( $mixRes ) ) {
			return false;
		}
		if ( !is_array($mixRes[0]) ) {
			$this->setPath( $mixRes );
		} else {
			foreach( $mixRes as $k=>$v ) {
				$this->setPath( $mixRes[$k] );
			}
		}
	}

	private function setPath( &$arrItem ) {
		if ( is_file( ( empty( $arrItem['flg_belong'] )? $this->_commonDir:$this->_userDir ) . Core_Files::getFileName( $arrItem['filename'] ).'.jpg' ) ) {
			$arrItem['preview']=( empty( $arrItem['flg_belong'] )? $this->_commonDirPreview:$this->_userDirPreview ).Core_Files::getFileName( $arrItem['filename'] ).'.jpg';
		}
		$arrItem['image']=( empty( $arrItem['flg_belong'] )? $this->_commonDir:$this->_userDir ).Core_Files::getFileName( $arrItem['filename'] ).'.jpg';
		$arrItem['path']=empty( $arrItem['flg_belong'] )? $this->_commonDir:$this->_userDir;
	}


	/**
	 * физическое удаление шаблонов, при условии что на шаблоны нет ссылок в $this->_tableLinkToUser и $this->_tableLinkToSite
	 * при добавлении нового шаблона ссылки появляются в любом случае
	 * @param array $_arrTemplatesToDel
	 * @return bool
	 */
	private function unlinkTemplates( $_arrTemplatesToDel=array() ) {
		if ( empty( $_arrTemplatesToDel ) ) {
			return false;
		}
		$_arrTemplatesWithNoLink=Core_Sql::getField( '
			SELECT p.id FROM '.$this->_table.' p WHERE
				p.id IN('.Core_Sql::fixInjection( $_arrTemplatesToDel ).') AND NOT (
					p.id IN(SELECT template_id FROM '.$this->_tableLinkToUser.' WHERE template_id=p.id) OR
					p.id IN(SELECT template_id FROM '.$this->_tableLinkToSite.' WHERE template_id=p.id)
				)
			GROUP BY p.id
		' );
		if ( empty( $_arrTemplatesWithNoLink ) ) {
			return false;
		}
		// если пользователи удалят у себя один из стандартных шаблонов и на него не будет завязан не один сайт то он может удалится - это корректно? 19.04.2010
		$_arrTemplates=Core_Sql::getAssoc( 'SELECT * FROM '.$this->_table.' WHERE id IN('.Core_Sql::fixInjection( $_arrTemplatesWithNoLink ).')' );
		if ( empty( $_arrTemplates ) ) {
			return false;
		}
		foreach( $_arrTemplates as $v ) {
			// предполагается что пользовательские шаблоны удаляет только пользователь, а если так то мы будем знать $this->_userDir
			@unlink( (empty( $v['flg_belong'] )? $this->_commonDir:$this->_userDir).$v['filename'] ); // тема
			@unlink( (empty( $v['flg_belong'] )? $this->_commonDir:$this->_userDir).Core_Files::getFileName( $v['filename'] ).'.jpg' ); // первьюха
		}
		Core_Sql::setExec( 'DELETE FROM '.$this->_table.' WHERE id IN('.Core_Sql::fixInjection( $_arrTemplatesWithNoLink ).')' );
		return true;
	}

	/**
	 * Удаление ссылок на пользователя + попытка удаления шаблонов
	 * использовать при удалении пользователя в том числе
	 *
	 * @param mix  $_arrUserIds - один или несколько id пользователей
	 * @param mix  $_arrTemplatesIds - один или несколько темплэйтов
	 * @return boolean
	 */
	private function unlinkFromUser( $_arrUserIds=array(), $_arrTemplatesIds=array() ) {
		if ( empty( $_arrUserIds ) ) {
			return false;
		}
		if ( !is_array( $_arrTemplatesIds ) ) {
			$_arrTemplatesIds=array( $_arrTemplatesIds );
		}
		if ( empty( $_arrTemplatesIds ) ) {
			$_arrTemplatesIds=Core_Sql::getField(
				'SELECT template_id FROM '.$this->_tableLinkToUser.' WHERE user_id IN('.Core_Sql::fixInjection( $_arrUserIds ).') GROUP BY template_id' );
		}
		$_bool=Core_Sql::setExec( 'DELETE FROM '.$this->_tableLinkToUser.' WHERE user_id IN('.Core_Sql::fixInjection( $_arrUserIds ).')'.
			(empty( $_arrTemplatesIds )? '':' AND template_id IN('.Core_Sql::fixInjection( $_arrTemplatesIds ).')') ); // чистим таблицу линков
		if ( !empty( $_arrTemplatesIds ) ) {
			$this->unlinkTemplates( $_arrTemplatesIds );
		}
		return true;
	}
}
?>