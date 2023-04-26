<?php
class Project_Sites_Adapter_Blogfusion_Export {

	private $_dir='';
	private $_result=array();
	private $_data;

	public function __construct( Core_Data $obj ) {
		$this->_data=$obj;
	}

	private function generateExporter( $_strMode='', $_strExport='' ) {
		if ( empty( $_strExport )||empty( $_strMode ) ) {
			$this->setError('No export and mode data');
			return false;
		}
		$_arrFiles=array();
		$_str=Project_Sites_Adapter_Blogfusion_Import::getCodeHeader().$_strExport;
		switch( $_strMode ) {
			case 'pages': $_str.=Project_Sites_Adapter_Blogfusion_Import::getCodePages(); break;
			case 'posts': $_str.=Project_Sites_Adapter_Blogfusion_Import::getCodePosts(); break;
			case 'comments': $_str.=Project_Sites_Adapter_Blogfusion_Import::getComments(); break;
			case 'cats': $_str.=Project_Sites_Adapter_Blogfusion_Import::getCodeCats(); break;
		}
		$_str.=Project_Sites_Adapter_Blogfusion_Import::getCodeXml();
		// временная дира
		$this->_dir='Project_Sites_Adapter_Blogfusion_Export@generateExporter';
		if ( !Zend_Registry::get( 'objUser' )->prepareTmpDir( $this->_dir ) ) {
			$this->setError('No acess to dir '. $this->_dir);
			return false;
		}
		if( !Core_Files::setContent( $_str, $this->_dir.'cnm-export.php' ) ){
			$this->setError('Can\'t set content to '.$_str);
			return false;
		}
		return true;
	}

	private function getResult() {
		if ( empty( $this->_dir ) ) {
			$this->setError('Empty dir name');
			return false;
		}
		$_transport=new Project_Placement_Transport();
		if ( !$_transport
			->setInfo( $this->_data->filtered )
			->setSourceDir( $this->_dir )
			->placeAndBreakConnect() ) {
			$this->setError('Empty place & break connect');
			return false;
		}
		// дёргаем
		if ( !Core_Curl::getResult( $_strRes, $this->_data->filtered['url'].'cnm-export.php' ) ) {
//			p($this->_data->filtered['url'].'cnm-export.php');
			$this->setError('No respond '.$this->_data->filtered['url'].'cnm-export.php');
			return Core_Data_Errors::getInstance()->setError( 'no respond '.$this->_data->filtered['url'].'cnm-export.php' );
		}
		$_xml=new Core_Parsers_Xml();
		$_xml->xml2array( $_arrRes, $_strRes );
		unset( $_strRes, $_xml );// освобождаем память
		$this->_result=$_arrRes['data'];
		unset( $_arrRes );// освобождаем память
		return true;
	}

	/*
	 * wp_delete_category
	 * wp_update_category
	 * wp_insert_category
	*/
	// генерим апдэйтер и данные для локального хранилища категорий
	public function category( Project_Wpress_Content_Category $obj ) {
		$_str='$_arrIds=array();';
		foreach( $obj->data->filtered as $k=>$v ) {
			if ( !empty( $v['del'] )&&!empty( $v['flg_default'] ) ) {
				continue;
			}
			if ( !empty( $v['del'] ) ) {
				$_str.='wp_delete_category( '.$v['ext_id'].' );';
				continue;
			}
			$_str.=( empty( $v['id'] )? '$id=wp_insert_category( array( "cat_name"=>stripslashes(\''.addslashes( $v['title'] ).'\'), "category_nicename"=>"'.Core_String::getInstance( $v['title'] )->toSystem().'" ) ); if ( !empty($id) ) $_arrIds['.$k.']=$id;':'wp_update_category( array( "cat_name"=>stripslashes(\''.addslashes( $v['title'] ).'\'), "category_nicename"=>"'.Core_String::getInstance( $v['title'] )->toSystem().'", "cat_ID"=>"'.$v['ext_id'].'" ) );' );
		}
		if ( !$this->generateExporter( 'cats', $_str ) ) {
			$this->setError('Can\'t generate exporter');
			return false;
		}
		if ( !$this->getResult() ) {
			$this->setError('Can\'t get result');
			return false;
		}
		if ( empty( $this->_result['cats'] ) ) {
			return true; // сработало но вставки новых категорий небыло
		}
		foreach( $this->_result['cats'] as $c ) { // тут должны быть только новые посты см. $_arrIds в сгеренённом коде
			if ( !isset( $c['mother_key'] ) ) {
 				continue;
			}			
			$obj->data->filtered[$c['mother_key']]['ext_id']=$c['ext_id'];
		}
		return true;
	}

	/*
	 * wp_delete_post
	 * wp_insert_post
	 * wp_update_post
	*/
	public function post( Project_Wpress_Content_Posts $obj ) {
		$_str='$_user=wp_authenticate(\''.$this->_data->filtered['dashboad_username'].'\',\''.$this->_data->filtered['dashboad_password'].'\'); set_current_user($_user->ID); $_arrIds=array();';
//		p($obj->data->filtered);
		foreach( $obj->data->filtered as $k=>$v ) {
			if ( !empty( $v['del'] ) ) {
				$_str.='wp_delete_post( '.$v['ext_id'].' );';
				continue;
			}
			if( !empty($v['id']) ){
				$_str.='$id='.$v['ext_id'].';';
			}
			$_str.=( empty( $v['id'] )? '$id=wp_insert_post( array( "filter" => "db", "post_author" => 1,"post_title"=>stripslashes(\''.addslashes( $v['title'] ).'\'), "post_content"=>stripslashes(\''.addslashes( $v['content'] ).'\'), "post_category" => array('.join(',',$v['catIds']).'), "post_status" => "publish", "tags_input" => stripslashes(\''.addslashes( $v['tags'] ).'\') ) ); if ( !empty($id) ) $_arrIds['.$k.']=$id;':'wp_update_post( array( "ID"=>"'.$v['ext_id'].'", "filter" => "db", "post_title"=>stripslashes(\''.addslashes( $v['title'] ).'\'), "post_content"=>stripslashes(\''.addslashes( $v['content'] ).'\'), "post_category" => array('.join(',',$v['catIds']).'), "post_status" => "publish", "tags_input" => stripslashes(\''.addslashes( $v['tags'] ).'\') ) );' );
			if( !empty($v['thumb']) ){
				$_str.='$upload_dir = wp_upload_dir();
					$image_data = file_get_contents("./images/'.$v['thumb'].'");
					$filename = basename("'.$v['thumb'].'");
					if(wp_mkdir_p($upload_dir["path"]))
					    $file = $upload_dir["path"] . "/" . $filename;
					else
					    $file = $upload_dir["basedir"] . "/" . $filename;
					file_put_contents($file, $image_data);

					$wp_filetype = wp_check_filetype($filename, null );
					$attachment = array(
					    "post_mime_type" => $wp_filetype["type"],
					    "post_title" => sanitize_file_name($filename),
					    "post_content" => "",
					    "post_status" => "inherit"
					);
					$attach_id = wp_insert_attachment( $attachment, $file, $id );
					require_once(ABSPATH . "wp-admin/includes/image.php");
					$attach_data = wp_generate_attachment_metadata( $attach_id, $file );
					wp_update_attachment_metadata( $attach_id, $attach_data );
					set_post_thumbnail( $id, $attach_id ); unset($id);';
			}
		}
		if ( !$this->generateExporter( 'posts', $_str ) ) {
			$this->setError('Can\'t generate exporter');
			return false;
		}
		$_strImageDir=$this->_dir.'images'.DIRECTORY_SEPARATOR;
		if ( !is_dir( $_strImageDir ) ) {
			mkdir( $_strImageDir, 0777, true );
		}
		foreach( $obj->data->filtered as $v ){
			if(!empty($v['files'])){
				foreach( $v['files'] as $_file ){
					copy( $_file, $_strImageDir.Core_Files::getBaseName($_file) );
				}
			}
		}
		if ( !$this->getResult() ) {
			$this->setError('Can\'t get result');
			return false;
		}
		if ( empty( $this->_result['posts'] ) ) {
			return true; // сработало но вставки новых страниц небыло
		}
		foreach( $this->_result['posts'] as $c ) { // тут должны быть только новые посты см. $_arrIds в сгеренённом коде
			if ( !isset( $c['mother_key'] ) ) {
 				continue;
			}			
			$obj->data->filtered[$c['mother_key']]['ext_id']=$c['ext_id'];
			$obj->data->filtered[$c['mother_key']]['catIds']=explode("@@",$c['category']);
		}
		return true;
	}

	/*
	 * wp_delete_post
	 * wp_insert_post
	 * wp_update_post
	*/
	public function page( Project_Wpress_Content_Pages $obj ) {
		$_str='$_arrIds=array();';
		foreach( $obj->data->filtered as $k=>$v ) {
			if ( !empty( $v['del'] ) ) {
				$_str.='wp_delete_post( '.$v['ext_id'].' );';
				continue;
			}
			$_str.=( empty( $v['id'] )? '$id=wp_insert_post( array( "post_title"=>stripslashes(\''.addslashes( $v['title'] ).'\'), "post_content"=>stripslashes(\''.addslashes( $v['content'] ).'\'), "post_type"=>"page", "post_status" => "publish" ) ); if ( !empty($id) ) $_arrIds['.$k.']=$id;':'wp_update_post( array( "ID"=>"'.$v['ext_id'].'", "post_title"=>stripslashes(\''.addslashes( $v['title'] ).'\'), "post_content"=>stripslashes(\''.addslashes( $v['content'] ).'\'), "post_category" => array('.$v['cat_id'].'), "post_status" => "publish" ) );' );
		}
		if ( !$this->generateExporter( 'pages', $_str ) ) {
			$this->setError('Can\'t generate exporter');
			return false;
		}
		if ( !$this->getResult() ) {
			$this->setError('Can\'t get result');
			return false;
		}
		if ( empty( $this->_result['pages'] ) ) {
			return true; // сработало но вставки новых страниц небыло
		}
		foreach( $this->_result['pages'] as $c ) { // тут должны быть только новые посты см. $_arrIds в сгеренённом коде
			if ( !isset( $c['mother_key'] ) ) {
 				continue;
			}			
			$obj->data->filtered[$c['mother_key']]['ext_id']=$c['ext_id'];
			$obj->data->filtered[$c['mother_key']]['catIds']=$c['category'];
		}
		return true;
	}

	/*
	 * wp_delete_comment
	 * wp_insert_comment
	 * wp_update_comment
	*/
	public function comment( Project_Wpress_Content_Comments $obj ) {
		$_str='$_arrIds=array();';
		$_str.='$time = current_time(\'mysql\', $gmt = 0); ';
		foreach( $obj->data->filtered as $k=>$v ) {
			if ( !empty( $v['del'] ) ) {
				$_str.='wp_delete_comment( '.$v['ext_id'].' );';
				continue;
			}
			$_str.=( empty( $v['id'] )? '$id=wp_insert_comment( array("user_id"=>1, "comment_date_gmt" => $time, "comment_author"=>"'.$obj->blog->filtered['dashboad_username'].'", "comment_author_email"=>"'.$obj->blog->filtered['admin_email'].'", "comment_post_ID"=>"'.$v['ext_post_id'].'", "comment_content"=>stripslashes(\''.addslashes( $v['content'] ).'\') ) ); if ( !empty($id) ) $_arrIds['.$k.']=$id;':'wp_update_comment( array("comment_ID"=>"'.$v['ext_id'].'", "comment_content"=>stripslashes(\''.addslashes( $v['content'] ).'\') ) );' );
		}
		if ( !$this->generateExporter( 'comments', $_str ) ) { 
			return false;
		}
		if ( !$this->getResult() ) { 
			return false;
		}
		if ( empty( $this->_result['comments'] ) ) {
			return true; // сработало но вставки новых комментов небыло
		}
		foreach( $this->_result['comments'] as $c ) { // тут должны быть только новые посты см. $_arrIds в сгеренённом коде
			if ( !isset( $c['mother_key'] )) {
 				continue;
			}
			$obj->data->filtered[$c['mother_key']]['ext_id']=$c['ext_id'];
			$obj->data->filtered[$c['mother_key']]['ext_post_id']=$c['ext_post_id'];
		}
		return true;
	}

	public function theme() {}

		
	private $_arrErrors=array();
	
	public function getErrors( &$arrErrors ) {
		foreach( $this->_arrErrors as $_err ){
			$arrErrors[]=$_err;
		}
		return $this;
	}
	
	public function setError( $_strError ) {
		$this->_arrErrors[]=$_strError;
	}
}
?>