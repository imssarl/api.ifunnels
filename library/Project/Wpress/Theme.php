<?php


/**
 * Theme management
 */
class Project_Wpress_Theme extends Core_Data_Storage {
	// папки и файлы
	private $_maxArchiveSize=5242880; // максимально разрешённый размер архива
	private $_commonDir =''; // общие шаблоны
	private $_userDir=''; // плагины пользовтеля
	private $_commonDirPreview =''; // html путь до скриншотов общих шаблонов
	private $_userDirPreview=''; // html путь до скриншотов пользовательских шаблонов
	public $_extractDir='';

	// таблицы
	protected $_table='bf_themes';
	protected $_fields=array( 'id', 'flg_type', 'flg_prop', 'priority', 'filename', 'title', 'url', 'version', 'author', 'author_url', 'description', 'added' );
	private $_tableLinkToUser='bf_theme2user_link';
	private $_tableLinkToBlog='bf_theme2blog_link';
	private $_userId=false;

	public function __construct(){
		if ( !Zend_Registry::get( 'objUser' )->getId( $_int ) ) {
			throw new Exception( Core_Errors::DEV.'|Zend_Registry::get( \'objUser\' )->getId( $_int ) is not return an User Id' );
			return;
		}
		$_strDir='blogfusion'.DIRECTORY_SEPARATOR.'themes';
		$this->_commonDir=Zend_Registry::get( 'config' )->path->absolute->user_files.$_strDir.DIRECTORY_SEPARATOR;
		if ( !Zend_Registry::get( 'objUser' )->prepareDtaDir( $_strDir ) ) {
			throw new Exception( Core_Errors::DEV.'|Zend_Registry::get( \'objUser\' )->prepareDtaDir( $_strDir ) no dir set' );
			return;
		}
		$this->_userId=$_int;
		$this->_userDir=$_strDir;
		$this->_userDirPreview=Zend_Registry::get( 'config' )->path->html->user_data.$this->_userId.'/blogfusion/themes/';
		$this->_commonDirPreview=Zend_Registry::get( 'config' )->path->html->user_files.'blogfusion/themes/';
	}

	public function search( $_arrData ){
		$curl=Core_Curl::getInstance();
		$arrParams=array(
			'action'=> 'query_themes',
			'request'=> (object) array(
				$_arrData['type']=>($_arrData['type']=='tag')?array_map('trim',explode(',',$_arrData['search'])):$_arrData['search'],
    			'page' => $_arrData['page'],
    			'fields' => array(
            		'description' => 1,
            		'sections' => 0,
            		'tested' => 1,
            		'requires' => 1,
            		'rating' => 1,
            		'downloaded' => 1,
            		'downloadlink' => 1,
            		'last_updated' => 1,
            		'homepage' => 1,
            		'tags' => 1,
            		'num_ratings' => 1,
        		),
	    		'per_page' => $_arrData['per_page']
			)
		);
		if (!$curl->setPost( $arrParams )->getContent('http://api.wordpress.org/themes/info/1.0/')){
			return false;
		}
		return (array) unserialize($curl->getResponce());
	}

	public function getErrors( &$arrRes ) {
		$arrRes=Core_Data_Errors::getInstance()->getErrors();
		return $this;
	}

	/**
	 * Восстановление ссылок на стандартные шаблоны для пользователся
	 * перед этим сначала удалим, чтобы небыло дубликатов
	 *
	 * @return boolean
	 */
	public function reassignCommonToUser() {
		if ( !$this->toRestore()->onlyIds()->getList( $_arrIds ) ) {
			return false;
		}
		Core_Sql::setExec( 'DELETE FROM '.$this->_tableLinkToUser.' WHERE theme_id IN('.Core_Sql::fixInjection( $_arrIds ).') AND user_id="'.$this->_userId.'"' );
		return $this->linkToUser( $this->_userId, $_arrIds );
	}

	/**
	 * Добавление ссылок на стандартные шаблоны для нового пользователся
	 * проблема может возникнуть только в случае если пользователь удалил все стандартные - будем решать по факту
	 *
	 * !!TODO Проверить удаление тем. Почему-то при удалении темы в бекенде после того как пользователь заходит на страницу управления темами
	 * !!TODO на фронтенде, тема восстанавливается в бекенде :)
	 *
	 * @return boolean
	 */
	public function addCommonThemesToNewUser() {
		if ( !$this->toRestore()->onlyIds()->getList( $_arrIds )->checkEmpty() ) {
			return false;
		}
		$_arrTest=Core_Sql::getField( 'SELECT theme_id FROM '.$this->_tableLinkToUser.' WHERE theme_id IN('.Core_Sql::fixInjection( $_arrIds ).') AND user_id="'.$this->_userId.'"' );
		if ( !empty( $_arrTest )&&count( $_arrIds )==count( $_arrTest ) ) {
			return true;
		}
		if ( !empty( $_arrTest ) ) {
			// тут только новые общие темы которых нет у пользователя (допустим если он недавно перешёл на другой тарифный план)
			$_arrIds=array_diff( $_arrIds, $_arrTest );
		}
		return $this->linkToUser( $this->_userId, $_arrIds );
	}

	/**
	 * если удаляется общий плагин то он пропадает у всех пользователей, но при залинкованных блогах физически и из таблицы плагинов не удаляем
	 * @param int $_intId
	 * @return bool
	 */
	public function deleteCommonTheme( $_intId=0 ) {
		if ( empty( $_intId ) ) {
			return false;
		}
		Core_Sql::setExec( 'DELETE FROM '.$this->_tableLinkToUser.' WHERE theme_id="'.$_intId.'"' );
		$this->unlinkThemes( $_intId );
		return true;
	}

	/**
	 * если удаляется пользовательский плагин то он пропадает у пользователя, но при залинкованных блогах физически и из таблицы плагинов не удаляем
	 * @param int $_intId
	 * @return bool
	 */
	public function deleteUserTheme( $_intId=0 ) {
		if ( empty( $_intId ) ) {
			return false;
		}
		return $this->unlinkFromUser( $this->_userId, $_intId );
	}

	/**
	 * физическое удаление плагинов, при условии что на плагины нет ссылок в $this->_tableLinkToUser и $this->_tableLinkToBlog
	 * при добавлении нового темы ссылки появляются в любом случае
	 * @param array $_arrThemesToDel
	 * @return bool
	 */
	private function unlinkThemes( $_arrThemesToDel=array() ) {
		if ( empty( $_arrThemesToDel ) ) {
			return false;
		}
		$_arrThemesWithNoLink=Core_Sql::getField( '
			SELECT p.id FROM '.$this->_table.' p WHERE
				p.id IN('.Core_Sql::fixInjection( $_arrThemesToDel ).') AND NOT (
					p.id IN(SELECT theme_id FROM '.$this->_tableLinkToUser.' WHERE theme_id=p.id) OR
					p.id IN(SELECT theme_id FROM '.$this->_tableLinkToBlog.' WHERE theme_id=p.id)
				)
			GROUP BY p.id
		' );
		if ( empty( $_arrThemesWithNoLink ) ) {
			return false;
		}
		$_arrThemes=Core_Sql::getAssoc( 'SELECT * FROM '.$this->_table.' WHERE id IN('.Core_Sql::fixInjection( $_arrThemesWithNoLink ).')' );
		if ( empty( $_arrThemes ) ) {
			return false;
		}
		foreach( $_arrThemes as $v ) {
			// предполагается что пользовательские плагины удаляет только пользователь, а если так то мы будем знать $this->_userDir
			@unlink( (empty( $v['flg_type'] )? $this->_commonDir:$this->_userDir).$v['filename'] ); // тема
			@unlink( (empty( $v['flg_type'] )? $this->_commonDir:$this->_userDir).Core_Files::getFileName( $v['filename'] ).'.png' ); // первьюха
		}
		Core_Sql::setExec( 'DELETE FROM '.$this->_table.' WHERE id IN('.Core_Sql::fixInjection( $_arrThemesWithNoLink ).')' );
		return true;
	}

	/**
	 * добавление темы пользователям userLink( $_arrUserIds, $_themeId );
	 * добавление темы пользоватлем userLink( $_userId, $_themeId );
	 * @param array $_arrUserIds
	 * @param array $_arrThemesIds
	 * @return bool
	 */
	private function linkToUser( $_arrUserIds=array(), $_arrThemesIds=array() ) {
		if ( empty( $_arrUserIds )||empty( $_arrThemesIds ) ) {
			return false;
		}
		if ( !is_array( $_arrUserIds ) ) {
			$_arrUserIds=array( $_arrUserIds );
		}
		if ( !is_array( $_arrThemesIds ) ) {
			$_arrThemesIds=array( $_arrThemesIds );
		}
		$_arrIns=array();
		foreach( $_arrUserIds as $u ) {
			foreach( $_arrThemesIds as $p ) {
				$arrIns[]=array( 'user_id'=>$u, 'theme_id'=>$p );
			}
		}
		return Core_Sql::setMassInsert( $this->_tableLinkToUser, $arrIns );
	}

	/**
	 * удаление темы у пользователей unlinkFromUser( $_arrUserIds, $_themeId );
	 * удаление темы пользователем unlinkFromUser( $_userId, $_themeId );
	 * удаление пользователя unlinkFromUser( $_userId );
	 * @param array $_arrUserIds
	 * @param array $_arrThemesIds
	 * @return bool
	 */
	private function unlinkFromUser( $_arrUserIds=array(), $_arrThemesIds=array() ) {
		if ( empty( $_arrUserIds ) ) {
			return false;
		}
		if ( !is_array( $_arrThemesIds ) ) {
			$_arrThemesIds=array( $_arrThemesIds );
		}
		if ( empty( $_arrThemesIds ) ) {
			$_arrThemesIds=Core_Sql::getField( 'SELECT theme_id FROM '.$this->_tableLinkToUser.' WHERE user_id IN('.Core_Sql::fixInjection( $_arrUserIds ).') GROUP BY theme_id' );
		}
		$_bool=Core_Sql::setExec( 'DELETE FROM '.$this->_tableLinkToUser.' WHERE user_id IN('.Core_Sql::fixInjection( $_arrUserIds ).')'.
			(empty( $_arrThemesIds )? '':' AND theme_id IN('.Core_Sql::fixInjection( $_arrThemesIds ).')') ); // чистим таблицу линков
		if ( !empty( $_arrThemesIds ) ) {
			$this->unlinkThemes( $_arrThemesIds );
		}
		return true;
	}

	/**
	 * добавление в новый блог плагинов blogLink( $_arrBlogIds, $_arrThemesIds );
	 * обновление списка плагинов блога blogLink( $_arrBlogIds, $_arrThemesIds );
	 * удаление блога blogLink( $_arrBlogIds );
	 * @param array $_arrBlogIds
	 * @param array $_arrThemesIds
	 * @return bool
	 */
	public function blogLink( $_arrBlogIds=array(), $_arrThemesIds=array() ) {
		if ( empty( $_arrBlogIds ) ) {
			return false;
		}
		if ( !is_array( $_arrBlogIds ) ) {
			$_arrBlogIds=array( $_arrBlogIds );
		}
		if ( !is_array( $_arrThemesIds ) ) {
			$_arrThemesIds=array( $_arrThemesIds );
		}
		$_arrOldThemesIds=Core_Sql::getField( 'SELECT theme_id FROM '.$this->_tableLinkToBlog.' WHERE blog_id IN('.Core_Sql::fixInjection( $_arrBlogIds ).') GROUP BY theme_id' );
		Core_Sql::setExec( 'DELETE FROM '.$this->_tableLinkToBlog.' WHERE blog_id IN('.Core_Sql::fixInjection( $_arrBlogIds ).')' ); // чистим таблицу линков
		if ( empty( $_arrThemesIds ) ) {
			$this->unlinkThemes( $_arrOldThemesIds ); // тут удаляем темы физически если они помечены как удалённые в БД
			return true;
		}
		$this->unlinkThemes( array_diff( $_arrOldThemesIds, $_arrThemesIds ) ); // тут удаляем только те которые были отлинкованы от блога
		$_arrIns=array();
		foreach( $_arrBlogIds as $b ) {
			foreach( $_arrThemesIds as $p ) {
				$arrIns[]=array( 'blog_id'=>$b, 'theme_id'=>$p );
			}
		}
		return Core_Sql::setMassInsert( $this->_tableLinkToBlog, $arrIns ); // добавляем новый список линков
	}

	/**
	 * Добавление стандартной темы
	 *
	 * @param array $_arrDta
	 * @param array $_arrZip
	 * @return bool
	 */
	public function addCommonTheme( $_arrDta=array(), $_arrZip=array() ) {
		if ( !$this->checkFile( $_arrZip ) ) {
			return Core_Data_Errors::getInstance()->setError('File is not correct.'); // некорректный файл
		}
		if ( $this->onlyCommon()->withFilename( $_arrZip['name'] )->getList( $_arrTmp )->checkEmpty() ) {
			return Core_Data_Errors::getInstance()->setError('This theme has alredy exist.'); // такой плагин уже есть
		}
		// если всё нормально то записываем перепакованную тему + картинку в папку общих плагинов
		$_bool1=copy( $this->_extractDir.$_arrZip['name'], $this->_commonDir.$_arrZip['name'] );
		$_bool2=copy( $this->_extractDir.Core_Files::getFileName( $_arrZip['name'] ).'.png', $this->_commonDir.Core_Files::getFileName( $_arrZip['name'] ).'.png' );
		if ( !$_bool1||!$_bool2 ) {
			return false;
		}
		// в базу данных
		$_data=new Core_Data( $_arrDta );
		$_data->setFilter();
		$_intId=Core_Sql::setInsert( $this->_table, $_data->setMask( $this->_fields )->getValidCurrent( $_arrZip+array(
			'flg_type'=>0,
			'flg_prop'=>empty( $_data->filtered['flg_prop'] )? 0:1,
			'priority'=>empty( $_data->filtered['priority'] )? 0:$_data->filtered['priority'],
			'filename'=>$_arrZip['name'],
			'added'=>time()
		) ) );
		// и линки всем текущим пользователям
		$_users=new Project_Users_Management();
		$_users->onlyIds()->withRights( 'use_bf_templates' )->getList( $_arrUsersIds );
		return $this->linkToUser( $_arrUsersIds, $_intId );
	}

	public function downloadTheme($_strLink){
		if (empty($_strLink)){
			return false;
		}
		$_strTmp='Project_Wpress_Theme@downloadTheme';
		if ( !Zend_Registry::get( 'objUser' )->prepareTmpDir( $_strTmp ) ) {
			return false;
		}
		$_curl=Core_Curl::getInstance();
		if (!$_curl->getContent($_strLink)){
			return false;
		}
		$_strContent=$_curl->getResponce();
		if (!Core_Files::setContent($_strContent,$_strTmp.'theme.zip')){
			return false;
		}
		$_arrData=array(
			'name'=>Core_Files::getBaseName($_strLink),
			'tmp_name'=> $_strTmp.'theme.zip',
		);
		if ( !$this->addUserTheme($_arrData) ){
			return false;
		}
		return true;
	}

	/**
	 * добавление пользовтаельского темы
	 * @param array $_arrZip
	 * @return bool
	 */
	public function addUserTheme( $_arrZip=array() ) {
		if ( !$this->checkFile( $_arrZip ) ) {
			return Core_Data_Errors::getInstance()->setError('File is not correct'); // некорректный файл
		}
		if ( $this->withFilename( $_arrZip['name'] )->getList( $_arrTmp )->checkEmpty() ) {
			return Core_Data_Errors::getInstance()->setError('This theme has alredy exist'); // такой плагин уже есть
		}
		// если всё нормально то записываем перепакованную тему + картинку в папку общих плагинов
		$_bool1=copy( $this->_extractDir.$_arrZip['name'], $this->_userDir.$_arrZip['name'] );
		if( is_file($this->_extractDir.Core_Files::getFileName( $_arrZip['name'] ).'.png') ) {
			$_bool2=copy( $this->_extractDir.Core_Files::getFileName( $_arrZip['name'] ).'.png', $this->_userDir.Core_Files::getFileName( $_arrZip['name'] ).'.png' );
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
			'added'=>time()
		) ) );
		return $this->linkToUser( $this->_userId, $_intId ); // и линки текущему пользователю
	}

	/**
	 * Парсинг файла для получения информеции о теме
	 * в нормальной теме должна быть шапка например такого вида:
	 * Theme Name: Lifestyle WordPress Theme
	 * Theme URL: http://www.revolutiontwo.com/themes/lifestyle
	 * Description: Lifestyle is a 3-column Widget-ready theme created for WordPress.
	 * Author: Brian Gardner
	 * Author URI: http://www.briangardner.com
	 * Version: 3.0
	 * Tags: three columns, fixed width, white, tan, teal, purple, sidebar widgets
	 *
	 * @param array $_arrZip - массив $_FILES[name]
	 * @param array $_strFileContent - содержимое очередного файла из темы
	 * @return boolean
	 */
	private function parseFile( &$_arrZip, &$_strFileContent ) {
		if ( !preg_match( '/Theme Name ?: ?(.*)$/mi', $_strFileContent, $_arrMatch ) ) {
			return false;
		}
		$_arrZip['title']=trim( $_arrMatch[1] );
		if ( preg_match( '/Theme URL ?: ?(.*)$/mi', $_strFileContent, $_arrMatch ) ) {
			$_arrZip['url']=trim( $_arrMatch[1] );
		}
		if ( preg_match( '/Version ?: ?(.*)$/mi', $_strFileContent, $_arrMatch ) ) {
			$_arrZip['version']=trim( $_arrMatch[1] );
		}
		if ( preg_match( '/Author ?: ?(.*)$/mi', $_strFileContent, $_arrMatch ) ) {
			$_arrZip['author']=trim( $_arrMatch[1] );
		}
		if ( preg_match( '/Author URI ?: ?(.*)$/mi', $_strFileContent, $_arrMatch ) ) {
			$_arrZip['author_url']=trim( $_arrMatch[1] );
		}
		if ( preg_match( '/Description ?: ?(.*)$/mi', $_strFileContent, $_arrMatch ) ) {
			$_arrZip['description']=trim( $_arrMatch[1] );
		}
		return true;
	}

	private function checkChild(&$_arrZip, &$_strFileContent){
		if ( !preg_match( '/Template ?: ?(.*)$/mi', $_strFileContent, $_arrMatch ) ) {
			return false;
		}
		$_arrZip['template']=trim( $_arrMatch[1] );
		return true;
	}

	public function checkFile( &$_arrZip ) {
		if ( empty( $_arrZip ) ) {
			return Core_Data_Errors::getInstance()->setError('Can\'t find archive with theme');
		}
		if( $_arrZip['size']>$this->_maxArchiveSize ){
			return Core_Data_Errors::getInstance()->setError('Uploaded file size is more than '.$this->_maxArchiveSize.'MB.Please upload below '.$this->_maxArchiveSize.'Mb');
		}
		if( Core_Files::getExtension( $_arrZip['name'] )!='zip' ){
			return Core_Data_Errors::getInstance()->setError('Invalid file.Please upload only zip file.');
		}
		$this->_extractDir='Project_Wpress_Theme@checkFile';
		if ( !Zend_Registry::get( 'objUser' )->prepareTmpDir( $this->_extractDir ) ) {
			return Core_Data_Errors::getInstance()->setError('Can\'t create temp dir');
		}
		$zip=new Core_Zip();
		if ( !$zip->setDir( $this->_extractDir )->extractZip( $_arrZip['tmp_name'] ) ) {
			return  Core_Data_Errors::getInstance()->setError('Zip file is not correct'); // проверка что это корректный zip и распаковываем во временную папку
		}
		if ( !Core_Files::dirScan( $_arr, $this->_extractDir ) ) {
			return Core_Data_Errors::getInstance()->setError('Zip archive is empty'); // пусто
		}
		foreach ( $_arr as $_strDir=>$_arrFiles ) {
			if (in_array( 'style.css', $_arrFiles )){
				$_arrStyle[] = $_strDir;
			}
		}
		// возможно есть Template и Child template, т.к. имеется 2-а файла style.css. Проверяем на наличеие родителя и ребенка.
		if( count($_arrStyle) > 1) {
			foreach ( $_arrStyle as $_dir ) {
				if ( Core_Files::getContent( $_strFileContent, $_dir . DIRECTORY_SEPARATOR . 'style.css') && $this->checkChild( $_arrZip, $_strFileContent ) ){
					$_arrZip['child']=$_dir;
					break; // Нашли ребёнка.
				}
			}
		}
		foreach( $_arr as $_strDir=>$_arrFiles ) {
			// если тема с Child, ищем корень child
			if ( !empty( $_arrZip['template']) && !empty( $_arrZip['child'] ) ) {
				if ( $_strDir != $_arrZip['child'] ) {
					continue;
				}
			}
			// в минимальной теме, в корне должны лежать эти 2 файла
			if ( !in_array( 'style.css', $_arrFiles ) || ( empty( $_arrZip['child'] ) && !in_array( 'index.php', $_arrFiles ) ) ) {
				continue;
			}
			// выдираем информацию о теме - она хранится в style.css
			if ( !( $_strFileContent=@file_get_contents( $_strDir.DIRECTORY_SEPARATOR.'style.css' ) ) ) {
				continue;
			}
			if ( !$this->parseFile( $_arrZip, $_strFileContent ) ) {
				return Core_Data_Errors::getInstance()->setError('Theme is not correct');
			}
			// меняем имя файла (имя архива может не совпадать с названием темы - берём название папки с темой)
			$_arrDirs=Core_Files::getDirsOfPath( $_strDir.DIRECTORY_SEPARATOR.'style.css' );
			$_arrZip['name']=$_arrDirs[0].'.zip';
			// пакуем текущую диру в zip в корень $this->_extractDir + в туже папку переносим картинку и меняем название на <имя архива>.png
			if ( true!==$zip->open( $this->_extractDir.$_arrZip['name'], ZipArchive::CREATE ) ) {
				return Core_Data_Errors::getInstance()->setError('Can\'t create archive');
			}
			if ( !empty( $_arrZip['template']) ) {
				if ( !$zip->addDirAndClose( $this->_extractDir ) ) {
					return  Core_Data_Errors::getInstance()->setError('Can\'t create archive');
				}
			} elseif ( !$zip->setRoot( Core_Files::getFileName( $_arrZip['name'] ) )->addDirAndClose( $_strDir ) ) {
				return Core_Data_Errors::getInstance()->setError('Can\'t create archive');
			}
			foreach( $_arrFiles as $_strFile ) {
				if ( Core_Files::getFileName( $_strFile )!='screenshot'||!in_array( Core_Files::getExtension( $_strFile ), array( 'png', 'gif', 'jpg', 'jpeg' ) ) ) {
					continue;
				}
				if ( !copy( $_strDir.DIRECTORY_SEPARATOR.$_strFile, $this->_extractDir.Core_Files::getFileName( $_arrZip['name'] ).'.png' ) ) {
					return false;
				}
			}
			return true; // в архиве должна быть одна подпапка где лежит плагин
		}
		Core_Data_Errors::getInstance()->setError('Theme is not correct.');
		return false;
	}

	// настройки для getList
	private $_onlySiteId=0;
	private $_onlyCommon=false; // только общие
	private $_withPreview=false; // с путями до картинки
	private $_toRestore=false; // только общие для восстоновления
	private $_withFilename=''; // c сортировкой
	protected $_withOrder='d.priority--up';
	private $_withTitle=false;
	private $_withRight=false;

	public function withRight(){
		$this->_withRight=true;
		return $this;
	}
	// сброс настроек после выполнения getArticles
	protected function init() {
		parent::init();
		$this->_onlyCommon=false;
		$this->_withPreview=false;
		$this->_toRestore=false;
		$this->_withFilename='';
		$this->_onlySiteId=0;
		$this->_withRight=false;
		$this->_withTitle=false;
	}

	public function onlySiteId( $_intId){
		$this->_onlySiteId=intval($_intId);
		return $this;
	}

	public function onlyCommon() {
		$this->_onlyCommon=true;
		return $this;
	}

	/**
	 * c html путём - для отображения превьюшек на веб странице
	 * @return Project_Wpress_Theme
	 */
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

	public function toRestore( ) {
		$this->_toRestore=true;
		return $this;
	}

	protected function assemblyQuery(){
		parent::assemblyQuery();
		if ( $this->_onlyCommon ) {
			$this->_crawler->set_from( 'INNER JOIN '.$this->_tableLinkToUser.' lu ON lu.theme_id=d.id' );
			$this->_crawler->set_where( 'd.flg_type=0' );
		} elseif ( $this->_toRestore ) { // только стандартные темы
			$this->_crawler->set_where( 'd.flg_type=0' );
		} elseif ( !empty( $this->_userId ) ) {
			$this->_crawler->set_from( 'INNER JOIN '.$this->_tableLinkToUser.' lu ON lu.theme_id=d.id AND lu.user_id='.Core_Sql::fixInjection($this->_userId) );
		}
		if ( !empty( $this->_onlySiteId ) ) {
			$this->_crawler->set_from( 'INNER JOIN '.$this->_tableLinkToBlog.' lb ON lb.theme_id=d.id AND lb.blog_id='.Core_Sql::fixInjection($this->_onlySiteId) );
		}
		if ( !empty( $this->_withFilename ) ) {
			$this->_crawler->set_where( 'd.filename='.Core_Sql::fixInjection( $this->_withFilename ) );
		}
		if( $this->_withRight ){
			$this->_crawler->set_where('d.id IN ('. Project_Acs_Template::getAccessSql( Project_Sites::BF ) .') OR flg_type=1');
		}
		if( $this->_withTitle ){
			$this->_crawler->set_where( 'd.title='.Core_Sql::fixInjection( $this->_withTitle ) );
		}
		if ( !empty( $this->_userId )||!empty( $this->_onlySiteId )||$this->_onlyCommon ) {
			$this->_crawler->set_group( 'd.id' );
		}
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
		if ( is_file( ( empty( $arrItem['flg_type'] )? $this->_commonDir:$this->_userDir ) . Core_Files::getFileName( $arrItem['filename'] ).'.png' ) ) {
			$arrItem['preview']=( empty( $arrItem['flg_type'] )? $this->_commonDirPreview:$this->_userDirPreview ).Core_Files::getFileName( $arrItem['filename'] ).'.png';
		}
		$arrItem['image']=( empty( $arrItem['flg_type'] )? $this->_commonDir:$this->_userDir ).Core_Files::getFileName( $arrItem['filename'] ).'.png';
		$arrItem['path']=empty( $arrItem['flg_type'] )? $this->_commonDir:$this->_userDir;
	}
}
?>