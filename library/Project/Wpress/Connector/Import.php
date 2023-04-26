<?php


/**
 * Read date from remote blogs
 */
class Project_Wpress_Connector_Import extends Project_Wpress_Connector {

	private $_result=array();
	private $_parts=array( 'all' );

	public function __construct( Core_Data $obj ) {
		parent::__construct( $obj );
	}

	public static function prepareObject( $_intId=0 ) {
		if ( empty( $_intId ) ) {
			return false;
		}
		$_arr=Core_Sql::getRecord( 'SELECT * FROM '.Project_Wpress::$table.' WHERE id='.Core_Sql::fixInjection( $_intId ) );
		if ( empty( $_arr ) ) {
			return false;
		}
		$obj=new Core_Data( $_arr );
		$obj->setFilter();
		return new Project_Wpress_Connector_Import( $obj );
	}

	public function setParts( $_arrSet=array() ) {
		if ( empty( $_arrSet ) ) {
			$_arrSet='all';
		}
		if ( !is_array( $_arrSet ) ) {
			$_arrSet=array( $_arrSet );
		}
		$this->_parts=$_arrSet;
		return $this;
	}

	private function generateImporter() {
		$_str=$this->getCodeHeader();
		foreach( $this->_parts as $v ) {
			switch( $v ) {
				case 'pages': $_str.=$this->getCodePages(); break;
				case 'posts': $_str.=$this->getCodePosts(); break;
				case 'cats': $_str.=$this->getCodeCats(); break;
				case 'comments': $_str.=$this->getComments(); break;
				case 'opt': $_str.=$this->getCodeOptions(); break;
				case 'all': $_str.=$this->getCodePages().$this->getCodePosts().$this->getComments().$this->getCodeCats().$this->getCodeOptions(); break;
			}
		}
		$_str.=$this->getCodeXml();
		// временная дира
		$this->_dir='Project_Wpress_Connector_Import@generateImporter';
		if ( !Zend_Registry::get( 'objUser' )->prepareTmpDir( $this->_dir ) ) {
			return false;
		}
		// файл экспорта
		return Core_Files::setContent( $_str, $this->_dir.'cnm-import.php' );
	}

	public function putImporter() {
		if ( !$this->generateImporter() ) {
			return false;
		}
		// подготавливаем сервер и систему
		if ( !$this->prepare() ) {
			return false;
		}
		// заливаем имортер
		if ( !$this->fileUpload( $this->_data->filtered['ftp_directory'].'cnm-import.php', $this->_dir.'cnm-import.php' ) ) {
			return $this->setError( 'unable upload '.$this->_data->filtered['ftp_directory'].'cnm-import.php' );
		}
		return true;
	}

	public function start() {
		// дёргаем
		if ( !$this->getResponce( $_strRes, $this->_data->filtered['url'].'cnm-import.php' ) ) {
			return $this->setError( 'no respond '.$this->_data->filtered['url'].'cnm-import.php' );
		}
		$_xml=new Core_Parsers_Xml();
		$_xml->xml2array( $_arrRes, $_strRes );
		unset( $_strRes, $_xml );// освобождаем память
		$this->_result=$_arrRes['data'];
		unset( $_arrRes );// освобождаем память
		$this->putDatas();
		return true;
	}

	// закидываем полученные данные в бд
	private function putDatas() {
		if ( !empty( $this->_result['cats'] ) ) {
			$cats=new Project_Wpress_Content_Category();
			$cats->setBlogByObject( $this->_data );
			$cats->setToDb( new Core_Data( $this->_result['cats'] ) );
		}
		if ( !empty( $this->_result['options'] ) ) {
			// линкуем тему
			$theme=new Project_Wpress_Theme();
			if ( $theme->onlyOne()->withFilename( $this->_result['options']['current_theme'].'.zip' )->getList( $arrTheme ) ) {
				$theme->blogLink( $this->_data->filtered['id'], $arrTheme['id'] );
			}
			// линкуем плагины
			$this->_result['options']['active_plugins']=unserialize(html_entity_decode($this->_result['options']['active_plugins']));
			if ( !empty( $this->_result['options']['active_plugins'] ) ) {
				$plugin=new Project_Wpress_Plugins();
				$arrName=array();
				foreach ($this->_result['options']['active_plugins'] as $filename){
					$tempName=explode('/',$filename);
					if (!empty($tempName[0])) {
						$arrName[]=$tempName[0].'.zip';
					}
				}
				if ( $plugin->onlyIds()->withFilenames( $arrName )->getList( $arrPluginIds )->checkEmpty() ){
					$plugin->blogLink( $this->_data->filtered['id'], $arrPluginIds );
				}
			}
			// обновляем настройки блога
			$_arrFlipTypes=array_flip( Project_Wpress::$permalinkTypes );
			$this->_data->setElements( array(
				'blogtag_line'=>$this->_result['options']['blogdescription'],
				'title'=>$this->_result['options']['blogname'],
				'flg_ping_status'=>( ( $this->_result['options']['default_ping_status']=='open' )? 1:0 ),
				'pingsite_list'=>$this->_result['options']['ping_sites'],
				'post_perpage'=>$this->_result['options']['posts_per_page'],
				'version'=>$this->_result['options']['version'],
				'flg_permalink'=>$_arrFlipTypes[$this->_result['options']['permalink_structure']],
			) );
			Core_Sql::setInsertUpdate( Project_Wpress::$table, $this->_data->setMask( Project_Wpress::$fields )->getValid() );
		}
		if ( !empty( $this->_result['pages'] ) ) {
			$page=new Project_Wpress_Content_Pages();
			$page->setBlogByObject( $this->_data );
			$page->setToDb( new Core_Data( $this->_result['pages'] ) );
		}
		if ( !empty( $this->_result['posts'] ) ) {
			$post=new Project_Wpress_Content_Posts();
			$post->setBlogByObject( $this->_data );
			foreach ($this->_result['posts'] as &$i) {
				$i['cat_id']=explode('@@',$i['category']);
			}
			$post->setToDb( new Core_Data( $this->_result['posts'] ) );
		}
		if ( !empty( $this->_result['comments'] ) ) {
			$comments=new Project_Wpress_Content_Comments();
			$comments->setBlogByObject( $this->_data );
			$comments->setToDb( new Core_Data( $this->_result['comments'] ) );
		}
	}

	public static function getCodeHeader() {
		return '
<?php
require_once( \'./wp-config.php\' );
require_once( \'./wp-includes/version.php\' );
require_once( \'./wp-admin/includes/taxonomy.php\' );
if ( !empty($wpdb->error) ) wp_die($wpdb->error->get_error_message());
global $wpdb;
$arrRes=array();
		';
	}

	public static function getCodeCats() {
		return '
function getCats( &$arrRes ) {
	global $wp_version;
	$_id=get_option( \'default_category\' );
	if ( version_compare( $wp_version, \'2.1.0\', \'<\' ) ) { // get_categories since wp 2.1.0
		global $wpdb;
		$_arr=$wpdb->get_results( \'SELECT * FROM \'.$wpdb->terms.\' left join \'.$wpdb->term_taxonomy.\' on \'.$wpdb->terms.\'.term_id=\'.$wpdb->term_taxonomy.\'.term_id where \'.$wpdb->term_taxonomy.\'.taxonomy="category"\'.(empty( $GLOBALS[\'_arrIds\'] )?\'\':\' AND id IN("\'.join(\'", "\',$GLOBALS[\'_arrIds\']).\'")\') );
	} else {
		$_arrOpt=array( \'hide_empty\'=>false );
		if ( !empty( $GLOBALS[\'_arrIds\'] ) ) { // code to help for inserting cats
			$_arrOpt[\'include\']=join( \',\', $GLOBALS[\'_arrIds\'] );
		}
		$_arr=get_categories( $_arrOpt );
	}
	if ( empty( $_arr ) ) {
		return false;
	}
	foreach( $_arr as $k=>$v ) {
		$arrRes[\'cat\'.$k]=array( \'ext_id\'=>$v->term_id, \'title\'=>$v->name, \'flg_default\'=>($v->term_id==$_id? 1:0));
		if( !empty( $GLOBALS[\'_arrIds\']) ){
			foreach ( $GLOBALS[\'_arrIds\'] as $mother_key=>$id) {
				if ($v->term_id == $id) {
					$arrRes[\'cat\'.$k][\'mother_key\']=$mother_key;
				}
			}		
		}
	}
	return true;
}
getCats($arrRes[\'data\'][\'cats\']);
		';
	}

	public static function getCodePosts( ) {
		return '
function getPosts( &$arrRes ) {
	global $wp_version;
	$_arrInclude=array();
	if ( !empty( $GLOBALS[\'_arrIds\'] ) ) { // code to help for inserting posts
		$_arrInclude[\'include\']=join( \',\', $GLOBALS[\'_arrIds\'] );
	}
	$count_post=wp_count_posts();
	$_arr=get_posts( ( array( \'numberposts\'=>$count_post->publish, \'post_status\' => \'publish\', \'orderby\'=>\'ID\', \'post_type\'=>\'post\', \'order\'=>\'ASC\' )+$_arrInclude ) );
	$_arr_1=get_posts( ( array( \'numberposts\'=>$count_post->draft, \'post_status\' => \'draft\', \'orderby\'=>\'ID\', \'post_type\'=>\'post\', \'order\'=>\'ASC\' )+$_arrInclude ) );
	$_arr_2=get_posts( ( array( \'numberposts\'=>$count_post->pending, \'post_status\' => \'pending\', \'orderby\'=>\'ID\', \'post_type\'=>\'post\', \'order\'=>\'ASC\' )+$_arrInclude ) );
	$_arr=array_merge($_arr,$_arr_1,$_arr_2);
	if ( empty( $_arr ) ) {
		return false;
	}
	foreach( $_arr as $k=>$v ) {
		$arrTags=get_the_tags($v->ID);
		if ( !empty( $arrTags ) ) {
			$strTag=\'\';
			foreach ( $arrTags as $tag ) {
				$strTag .= $tag->name .\',\'; 
			}
			$strTag=substr( $strTag, 0, -1);
		}	
		$arrRes[\'post\'.$k]=array( \'ext_id\'=>$v->ID, \'title\'=>$v->post_title, \'content\'=>$v->post_content, \'tags\' =>  $strTag );
		if ( !empty( $GLOBALS[\'_arrIds\'] ) ) { // code to help for inserting posts
			foreach( $GLOBALS[\'_arrIds\'] as $key=>$val ) {
				if ( $val==$v->ID ) {
					$arrRes[\'post\'.$k][\'mother_key\']=$key;
					break;
				}
			}
		}
		if ( version_compare( $wp_version, \'0.71\', \'<\' ) ) { // get_the_category since wp 0.71
			continue;
		}
		$_arrCats=get_the_category( $v->ID );
		if ( empty( $_arrCats ) ) {
			continue;
		}
		$_arrIds=array();
		foreach( $_arrCats as $c ) {
			$_arrIds[]=$c->cat_ID;
		}
		$arrRes[\'post\'.$k][\'category\']=join( \'@@\', $_arrIds );
	}
	return true;
}
getPosts($arrRes[\'data\'][\'posts\']);
		';
	}

	public static function getCodePages() {
		return '
function getPages( &$arrRes ) {
	$_arrInclude=array();
	if ( !empty( $GLOBALS[\'_arrIds\'] ) ) { // code to help for inserting page
		$_arrInclude[\'include\']=join( \',\', $GLOBALS[\'_arrIds\'] );
	}
	$count_post=wp_count_posts();
	$_arr=get_posts( ( array( \'numberposts\'=>$count_post->publish, \'post_status\' => \'publish\', \'orderby\'=>\'ID\', \'post_type\'=>\'page\', \'order\'=>\'ASC\' )+$_arrInclude ) );
	$_arr_1=get_posts( ( array( \'numberposts\'=>$count_post->draft, \'post_status\' => \'draft\', \'orderby\'=>\'ID\', \'post_type\'=>\'page\', \'order\'=>\'ASC\' )+$_arrInclude ) );
	$_arr_2=get_posts( ( array( \'numberposts\'=>$count_post->pending, \'post_status\' => \'pending\', \'orderby\'=>\'ID\', \'post_type\'=>\'page\', \'order\'=>\'ASC\' )+$_arrInclude ) );
	$_arr=array_merge($_arr,$_arr_1,$_arr_2);
	if ( empty( $_arr ) ) {
		return false;
	}	
	foreach( $_arr as $k=>$v ) {
		$arrRes[\'page\'.$k]=array( \'ext_id\'=>$v->ID, \'title\'=>$v->post_title, \'content\'=>$v->post_content );
		if ( !empty( $GLOBALS[\'_arrIds\'] ) ) { // code to help for inserting page
			foreach( $GLOBALS[\'_arrIds\'] as $key=>$val ) {
				if ( $val==$v->ID ) {
					$arrRes[\'page\'.$k][\'mother_key\']=$key;
					break;
				}
			}
		}
	}
	return true;
}
getPages($arrRes[\'data\'][\'pages\']);
		';
	}
	
	public static function getComments() {
		return '
function getComments( &$arrRes) {
	if ( !empty( $GLOBALS[\'_arrIds\'] ) ) { // code to help for inserting comments
		foreach( $GLOBALS[\'_arrIds\'] as $k=>$v ) {
			wp_set_comment_status( $v,\'approve\' ); // wp_set_comment_status since wp 1.0.0
			$_obj=get_comment($v); // get_comment since wp 2.0.0
			$arrRes[\'comment\'.$k]=array(
				\'ext_id\' => $_obj->comment_ID,
				\'content\' => $_obj->comment_content,
				\'ext_post_id\' => $_obj->comment_post_ID,
				\'mother_key\'=>$k
			);
		}
		$_arrInclude[\'include\']=join( \',\', $GLOBALS[\'_arrIds\'] );
	} else {
		global $wp_version;
		if ( version_compare( $wp_version, \'2.7.0\', \'<\' ) ) { // get_comments since wp 2.7.0
			global $wpdb;
			$_arr=$wpdb->get_results( \'SELECT * FROM \'.$wpdb->comments.\' WHERE comment_approved IN("0","1") ORDER BY comment_date_gmt\' );
		} else {
			$_arr=get_comments(array(\'orderby\' => \'comment_date_gmt\'));
		}
		foreach ($_arr as $k=>$v) {
			$arrRes[\'comment\'.$k]=array(
				\'ext_id\' => $v->comment_ID,
				\'content\' => $v->comment_content,
				\'ext_post_id\' => $v->comment_post_ID,
			);
		}
	}
}
getComments( $arrRes[\'data\'][\'comments\']);
	';
	}

	public static function getCodeOptions() {
		return '
function getOptions( &$arrRes ) {
	global $wpdb;
	global $wp_version;
	$_arr=$wpdb->get_results( \'SELECT * FROM \'.$wpdb->options.\' where option_name IN("\'.join( \'", "\', array( \'blogname\', \'posts_per_page\', \'default_ping_status\', \'active_plugins\', \'permalink_structure\', \'comment_moderation\', \'ping_sites\', \'blogdescription\', \'current_theme\' ) ).\'")\' );
	if ( empty( $_arr ) ) {
		return false;
	}
	foreach( $_arr as $v ) {
		$arrRes[$v->option_name]=$v->option_value;
	}
	$arrRes["version"]=$wp_version;
	return true;
}
getOptions($arrRes[\'data\'][\'options\']);
		';
	}

	public static function getCodeXml() {
		return '
function generateXml( $arrTree, $strKey=\'\' ) {
	$strXml=\'\';
	foreach ( $arrTree as $k=>$v ) {
		if ( is_array( $v ) ) {
			if ( is_int( $k ) ) {
				if ( isSet( $strKey ) ) {
					$strXml.=\'<\'.$strKey.\'>\'.generateXml( $v ).\'</\'.$strKey.\'>\'."\n";
				} else {
					$strXml.=generateXml( $v );
				}
			} else {
				$strXml.=\'<\'.$k.\'>\'."\n".generateXml( $v, $k ).\'</\'.$k.\'>\'."\n";
			}
		} else {
			$strXml.=\'<\'.$k.\'><![CDATA[\'.str_replace(\']]>\',\'\',str_replace(\'<![CDATA[\',\'\',$v)).\']]></\'.$k.\'>\'."\n";
		}
	}
	return $strXml;
}

echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n".generateXml( $arrRes );
?>
		';
	}
}
?>