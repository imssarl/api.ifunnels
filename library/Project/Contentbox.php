<?php
class Project_Contentbox extends Core_Data_Storage{

	protected $_table='cb_campaigns';
	protected $_fields=array('id','user_id','name','settings','flg_template','added','edited');
	
	public function __construct() {
		self::update();
	}
	
	public static function install() {
		Core_Sql::setExec("drop table if exists cb_campaigns");
		Core_Sql::setExec( "CREATE TABLE `cb_campaigns` (
			`id` INT(11) NOT NULL AUTO_INCREMENT,
			`user_id` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			`name` VARCHAR(255) NOT NULL DEFAULT '',
			`settings` TEXT NULL,
			`edited` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			`added` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			UNIQUE INDEX `id` (`id`)
		)
		COLLATE='utf8_general_ci'
		ENGINE=MyISAM
		AUTO_INCREMENT=41;
		" );
		return $this;
	}

	public static function update(){
		$_arrNulls=Core_Sql::getAssoc("SELECT NULL
            FROM INFORMATION_SCHEMA.COLUMNS
           WHERE table_name = 'cb_campaigns'
             AND column_name = 'flg_template';");
		if( count( $_arrNulls ) == 0 ){
			Core_Sql::setExec("ALTER TABLE `cb_campaigns` ADD `flg_template`  INT(1) UNSIGNED NOT NULL DEFAULT '0';");
		}
	}
	
	private $_onlyTemplates=false;
	
	public function onlyTemplates(){
		$this->_onlyTemplates=true;
		return $this;
	}
	
	protected function init(){
		parent::init();
		$this->_onlyTemplates=false;
	}
	
	protected function assemblyQuery(){
		parent::assemblyQuery();
		if( $this->_onlyTemplates!==false ){
			$this->_crawler->set_where('d.flg_template=1');
		}
	}
	
	protected function beforeSet() {
		//Project_Contentbox::install();
		$this->_data->setFilter( array( 'clear' ) );
		foreach( $this->_data->filtered['settings'] as &$_box ){
			if( $_box['type'] == 'image' ){
				ini_set("pcre.backtrack_limit",10000000);
				preg_match( '/data:image\/(.*);base64,(.*)/', $_box['src'], $_fileData );
				if( isset( $_fileData[1] ) && isset( $_fileData[2] ) ){
					$_fileName='contentbox'.DIRECTORY_SEPARATOR.'upload'.DIRECTORY_SEPARATOR.md5( $_box['src'] ).'.'.$_fileData[1];
					file_put_contents( Zend_Registry::get('config')->path->absolute->user_files.$_fileName, base64_decode( $_fileData['2'] ) );
					$_box['src']=Zend_Registry::get( 'config' )->domain->url.str_replace( DIRECTORY_SEPARATOR, '/', Zend_Registry::get('config')->path->html->user_files.$_fileName );
				}
			}else{
				foreach( $_box as &$_boxSetting ){
					if( substr( $_boxSetting, 0, strlen('data:image') ) == 'data:image' ){
						ini_set("pcre.backtrack_limit",10000000);
						preg_match( '/data:image\/(.*);base64,(.*)/', $_boxSetting, $_fileData );
						if( isset( $_fileData[1] ) && isset( $_fileData[2] ) ){
							$_fileName='contentbox'.DIRECTORY_SEPARATOR.'upload'.DIRECTORY_SEPARATOR.md5( $_boxSetting ).'.'.$_fileData[1];
							file_put_contents( Zend_Registry::get('config')->path->absolute->user_files.$_fileName, base64_decode( $_fileData['2'] ) );
							$_boxSetting=Zend_Registry::get( 'config' )->domain->url.str_replace( DIRECTORY_SEPARATOR, '/', Zend_Registry::get('config')->path->html->user_files.$_fileName );
						}
					}
				}
			}
		}
		$this->_data->setElements(array(
			'settings'=>base64_encode( serialize( $this->_data->filtered['settings'] ) ),
		));
		return true;
	}

	protected function afterSet() {
		$this->_data->filtered['settings']=unserialize( base64_decode( $this->_data->filtered['settings'] ) );
		return true;
	}

	public function getList( &$mixRes ) {
		parent::getList( $mixRes );
		if( empty($mixRes) ){
			return $this;
		}
		if( array_key_exists( 0, $mixRes ) ) {
			foreach( $mixRes as &$_data ) {
				$_oldSettings=$_data['settings'];
				$_data['settings']=unserialize( base64_decode( $_data['settings'] ) );
				if( $_data['settings']===false ){
					$_data['settings']=unserialize( $_data['settings'] );
				}
				if( $_data['settings']===false ){
					$_data['settings']=unserialize( json_decode( str_replace( '\r\n\n', '\r\n', json_encode( $_oldSettings ) ) ) );
				}
				if( $_data['settings']===false ){
					$_data['settings']=$_oldSettings;
				}
			}
		}else{
			$_oldSettings=$mixRes['settings'];
			$mixRes['settings']=unserialize( base64_decode( $mixRes['settings'] ) );
			if( $mixRes['settings']===false ){
				$mixRes['settings']=unserialize( $mixRes['settings'] );
			}
			if( $mixRes['settings']===false ){
				$mixRes['settings']=unserialize( json_decode( str_replace( '\r\n\n', '\r\n', json_encode( $_oldSettings ) ) ) );
			}
			if( $mixRes['settings']===false ){
				$mixRes['settings']=$_oldSettings;
			}
		}
		return $this;
	}

	public static function searchImageGoggle( $_word ){
		$_url='https://ajax.googleapis.com/ajax/services/search/images?v=1.0&imgsz=huge&q='.str_replace( ' ', '+', $_word);
		$_curl=new Core_Curl();
		$_curl->getContent($_url);
		$json=$_curl->getResponce();
		return $json;
	}

	public static function searchImagePixabay( $_word ){
		$_url='http://pixabay.com/api/?key=2142462-ddaca3e279ae01599c6f604f2&orientation=horizontal&q='.str_replace( ' ', '+', $_word);
		$_curl=new Core_Curl();
		$_curl->getContent($_url);
		$json=$_curl->getResponce();
		$_returnData=array();
		$_arrData=json_decode( $json, true );
		foreach( $_arrData['hits'] as $_data ){
			$_returnData[]=array(
				'url'=>$_data['webformatURL'],
				'tbWidth'=>$_data['webformatWidth'],
				'tbHeight'=>$_data['webformatHeight']
			);
		}
		return json_encode( $_returnData );
	}

	public static function getImageFromLink( $_link ){
		$URL2PNG_APIKEY = "PAA11E0D3718E90";
		$URL2PNG_SECRET = "S_4ED60B58B10F8";
		$options['force'] = 'false';   # [false,always,timestamp] Default: false
		$options['fullpage'] = 'false';   # [true,false] Default: false
		$options['thumbnail_max_width'] = 'false';   # scaled image width in pixels; Default no-scaling.
		$options['viewport'] = "1280x1024";  # Max 5000x5000; Default 1280x1024
		$options['url'] = urlencode( $_link ); # urlencode request target
		foreach($options as $key => $value) { $_parts[] = "$key=$value"; } # create the query string based on the options
		$query_string = implode("&", $_parts); # create a token from the ENTIRE query string
		if( copy( "https://api.url2png.com/v6/".$URL2PNG_APIKEY."/".md5($query_string . $URL2PNG_SECRET)."/png/?".$query_string, Zend_Registry::get('config')->path->absolute->user_files.'squeeze'.DIRECTORY_SEPARATOR.'backgrounds'.DIRECTORY_SEPARATOR.md5($_link).".jpg") ){
			$_returnData[]=array(
				'url'=>Zend_Registry::get( 'config' )->domain->url.Zend_Registry::get('config')->path->html->user_files.'squeeze'.'/backgrounds/'.md5($_link).'.jpg',
				'tbWidth'=>150,
				'tbHeight'=>99
			);
		}else{
			$_action="/bin/wkhtmltoimage --width 1200 --height 800 --quality 100 --zoom 1 --enable-javascript --javascript-delay 1000 ".escapeshellarg($_link)." ".escapeshellarg(Zend_Registry::get('config')->path->absolute->user_files.'squeeze'.DIRECTORY_SEPARATOR.'backgrounds'.DIRECTORY_SEPARATOR.md5($_link).".jpg");
			exec($_action, $output, $return);
			$_returnData[]=array(
				'url'=>Zend_Registry::get( 'config' )->domain->url.Zend_Registry::get('config')->path->html->user_files.'squeeze'.'/backgrounds/'.md5($_link).'.jpg',
				'tbWidth'=>150,
				'tbHeight'=>99
			);
		}
		return json_encode( $_returnData );
	}

	public static function getImages( $_imageType='backgrounds' ){
		$_dir=Zend_Registry::get('config')->path->absolute->user_files.'contentbox'.DIRECTORY_SEPARATOR.$_imageType.DIRECTORY_SEPARATOR;
		if( !is_dir( $_dir ) ){
			return array();
		}
		Core_Files::dirScan( $arrTmp, $_dir );
		$_tags=new Project_Squeeze_Buttontags();
		$_tags->getList( $_arrAllTags );
		foreach( array_shift($arrTmp) as $_item ){
			$_flgHaveTags=false;
			foreach( $_arrAllTags as $_tags ){
				if( $_tags['id'] == md5($_item.'#'.$_imageType) ){
					$_flgHaveTags=true;
					$arrRes[]=array(
						'tags'=>$_tags['tags'],
						'name'=>$_item,
						'title'=>ucfirst(str_replace(array('_','-'),array(' ',' '),Core_Files::getFileName($_item))),
						'preview'=>Core_Files_Image_Thumbnail::generate( array('src'=>$_dir.$_item) )
					);
					break;
				}
			}
			if( !$_flgHaveTags ){
				$arrRes[]=array(
					'tags'=>'',
					'name'=>$_item,
					'title'=>ucfirst(str_replace(array('_','-'),array(' ',' '),Core_Files::getFileName($_item))),
					'preview'=>Core_Files_Image_Thumbnail::generate( array('src'=>$_dir.$_item) )
				);
			}
		}
		return $arrRes;
	}

	public static function parseForm( $strForm='' ){
		$str=preg_replace("/\n*|\r*/si",'',htmlspecialchars_decode( $strForm ));
		preg_match_all('/(?P<forms><form.+?<\/form>)/is',$str,$_match);
		foreach( $_match['forms'] as $_key=>$_form ){
			preg_match_all('/(?<inputs><input.*?>)/si',$_form,$_match);
			preg_match_all('/(?<buttons><button.*?>)/si',$_form,$_matchB);
			if( !empty( $_matchB['buttons'] ) ){
				foreach( $_matchB['buttons'] as $_button ){
					$_match['inputs'][]=$_button;
				}
			}
			foreach( $_match['inputs'] as $_input ){
				if( ( stripos($_input,'type="submit"')||stripos($_input,'type=submit')||stripos($_input,"type='submit'") ) !== false ){
					if( stripos($_input,'onclick=') !== false ){
						$_addjs='PreventExitPopup=true;';
						$_replace=str_replace( 
							array(
								'onclick="',
								"onclick='"
							), 
							array(
								'onclick="'.$_addjs,
								"onclick='".$_addjs
							), 
							$_input
						);
					}else{
						$_replace=str_replace( array(
							'type="submit', 
							"type='submit", 
							'type=submit' 
						) , array(
							'type="submit" onclick="PreventExitPopup=true;',
							"type='submit' onclick='PreventExitPopup=true;",
							'type=submit onclick="PreventExitPopup=true;"'
						), $_input );
					}
					$strForm=str_replace( $_input, $_replace, $strForm );
				}
			}
		}
		return $strForm;
	}

	public static function regenerateId( $code ){
		$string=base64_decode( urldecode( $code ) );
		$position=substr( strstr($string, 'q', true), 1 );
		$base=substr( strstr($string, 'q'), 1 );
		return json_decode( base64_decode( substr($base, 0, -$position-1).substr($base, -$position) ), true );
	}

	public static function generateId( $id ){
		if( !isset( $id ) ){
			return false;
		}
		$_string=base64_encode( json_encode( $id ) );
		$_position=(int)( strlen( $_string )/3 );
		return urlencode( base64_encode( 'j'.$_position.'q'.substr($_string, 0, -$_position).'s'.substr($_string, -$_position) ) );
	}

	public static function updateForm( $strForm='', $arrUpdateData=array() ){
		if( empty($arrUpdateData) ){
			return $strForm;
		}
		$str=preg_replace( "/\n*|\r*/si",'',htmlspecialchars_decode( $strForm ) );
		preg_match_all('#(<form.*?</form>)#is',$str,$_arrForm);
		$_html='';
		foreach( $_arrForm[1] as $_key=>$_form ){
			preg_match_all('#(<input.*?>|<textarea.*?</textarea>|<select.*?</select>|<button.*?>)#si',$_form,$_arrInput);
			preg_match('/action=["|\'](?<action>.*?)["|\']/si',$_form,$_action);
			preg_match('/method=["|\'](?<method>.*?)["|\']/si',$_form,$_method);
			$_html='<form action="'.$_action['action'].'" method="'.$_method['method'].'" target="_blank">';
			foreach( $_arrInput[0] as $_input ){
				preg_match('/<(.*?) /si',$_input,$_tag);
				preg_match('/name=["|\'](.*?)["|\']/si',$_input,$_name);
				preg_match('/value=["|\'](?<value>.*?)["|\']/si',$_input,$_defValue);
				$_value=@$_defValue['value'];
				$_name=$_name[1];
				if( isset( $arrUpdateData['form_autoresponder_hide'][md5($_name.$_value.$_action['action'])] )
					&& $arrUpdateData['form_autoresponder_hide'][md5($_name.$_value.$_action['action'])] == 1
					){
					continue;
				}
				if( isset( $arrUpdateData['form_autoresponder'][md5($_name.$_value.$_action['action'])] ) ){
					$_value=$arrUpdateData['form_autoresponder'][md5($_name.$_value.$_action['action'])];
				}
				switch ($_tag[1]){
					case 'input':
					case 'button':
						preg_match('/type=["|\'](.*?)["|\']/',$_input,$_type);
						$_html.='<'.$_tag[1].' type="'.$_type[1].'" name="'.$_name.'" value="'.$_value.'">';
						break;
					case 'select':
						$_html.=$_input;
						break;
					case 'textarea':
						$_html.='<textarea name="'.$_name.'">'.$_value.'</textarea>';
						break;
				}
			}
			$_html.='</form>';
		}
		return $_html;
	}

	public static function editFormValues( $_strForm ){
		$str=preg_replace( "/\n*|\r*/si",'',htmlspecialchars_decode( $_strForm ) );
		preg_match_all('/(<form.*?<\/form>)/is',$str,$_arrForm);
		$_html='<table>';
		foreach( $_arrForm[1] as $_key=>$_form ){
			preg_match_all('#(<input.*?>|<textarea.*?</textarea>|<select.*?</select>|<button.*?>)#si',$_form,$_arrInput);
			preg_match('/action=["|\'](?<action>.*?)["|\']/si',$_form,$_action);
			preg_match('/method=["|\'](?<method>.*?)["|\']/si',$_form,$_method);
			foreach( $_arrInput[0] as $_input ){
				preg_match('/<(.*?) /si',$_input,$_tag);
				preg_match('/name=["|\'](.*?)["|\']/si',$_input,$_name);
				preg_match('/value=["|\'](?<value>.*?)["|\']/si',$_input,$_defValue);
				$_defaultValue=$_value=@$_defValue['value'];
				$_name=$_name[1];
				switch ($_tag[1]){
					case 'input':
					case 'button':
						preg_match('/type=["|\'](.*?)["|\']/',$_input,$_type);
						switch ($_type[1]){
							case 'text':
							case 'password':
								$_nameTst=ucfirst($_name);
								if( empty( $_value ) ){
									if( strpos( strtolower( $_nameTst ), 'email' ) !== false ){
										$_value='Enter Your Best Email';
									}
									if( strpos( strtolower( $_nameTst ), 'name' ) !== false ){
										$_value='Enter Your Name';
									}
									if( strpos( strtolower( $_nameTst ), 'firstname' ) !== false ||  strpos( strtolower( $_nameTst ), 'fname' ) !== false ){
										$_value='Enter Your First Name';
									}
									if( strpos( strtolower( $_nameTst ), 'lastname' ) !== false ||  strpos( strtolower( $_nameTst ), 'lname' ) !== false ){
										$_value='Enter Your Last Name';
									}
									if( strpos( strtolower( $_nameTst ), 'street' ) !== false ){
										$_value='Enter Your Street';
									}
									if( strpos( strtolower( $_nameTst ), 'zip' ) !== false ){
										$_value='Enter Your Zip Code';
									}
									if( strpos( strtolower( $_nameTst ), 'city' ) !== false ){
										$_value='Enter Your City';
									}
									if( strpos( strtolower( $_nameTst ), 'country' ) !== false ){
										$_value='Enter Your Country';
									}
									if( strpos( strtolower( $_nameTst ), 'phone' ) !== false ){
										$_value='Enter Your Phone';
									}
									if( $_value === false ){
										$_value=$_nameTst;
									}
								}
								$_html.='<tr><td>Input:</td><td><input type="text" class="medium-input text-input" name="settings[form_autoresponder]['.md5( $_name.$_defaultValue.$_action['action'] ).']" value="'.$_value.'"></td>';
								$_html.='<td><input type="checkbox" name="settings[form_autoresponder_hide]['.md5( $_name.$_defaultValue.$_action['action'] ).']" value="1">&nbsp;Hide</td></tr>';
								break;
							case 'radio':
							case 'checkbox':
								// распарсить текст до и после чекбокса
								$_html.='<tr><td>Flag '.$_type[1].' '.$_name.':</td><td><input type="text" class="medium-input text-input" name="settings[form_autoresponder]['.md5( $_name.$_defaultValue.$_action['action'] ).']" value="'.$_value.'"></td>';
								$_html.='<td><input type="checkbox" name="settings[form_autoresponder_hide]['.md5( $_name.$_defaultValue.$_action['action'] ).']" value="1">&nbsp;Hide</td></tr>';
								break;
							case 'button':
							case 'submit':
								if( empty( $_value ) ){
									$_value='Submit';
								}
								$_html.='<tr><td>Submit Button:</td><td><input type="text" class="medium-input text-input" name="settings[form_autoresponder]['.md5( $_name.$_defaultValue.$_action['action'] ).']" value="'.$_value.'"></td>';
								$_html.='<td><input type="checkbox" name="settings[form_autoresponder_hide]['.md5( $_name.$_defaultValue.$_action['action'] ).']" value="1">&nbsp;Hide</td></tr>';
								break;
							default: // date, datetime, datetime-local, email, month, number, range, search, tel, time, url, week - HTML5
								$_html.='<tr><td>Input '.$_tag[1].':</td><td><input type="text" class="medium-input text-input" name="settings[form_autoresponder]['.md5( $_name.$_defaultValue.$_action['action'] ).']" value="'.$_value.'"></td>';
								$_html.='<td><input type="checkbox" name="settings[form_autoresponder_hide]['.md5( $_name.$_defaultValue.$_action['action'] ).']" value="1">&nbsp;Hide</td></tr>';
								break;
						}
						break;
					case 'select':
						preg_match_all('#(<option.*?<)#si',$_input,$_arrOption);
					//	p( $_arrOption ); пока не используем
						break;
					case 'textarea':
						preg_match_all('#(<textarea.*?>.*?</textarea>)#si',$_input,$_arrValue);
						if( isset( $_arrValue[2] ) ){
							$_value=$_arrValue[2];
						}
						$_html.='<tr><td>Textarea:</td><td><textarea class="medium-input text-input" name="settings[form_autoresponder]['.md5( $_name.$_value.$_action['action'] ).']">'.$_value.'</textarea></td>';
						$_html.='<td><input type="checkbox" name="settings[form_autoresponder_hide]['.md5( $_name.$_value.$_action['action'] ).']" value="1">&nbsp;Hide</td></tr>';
						break;
				}
			}
		}
		$_html.='</table>';
		return $_html;
	}

}
?>