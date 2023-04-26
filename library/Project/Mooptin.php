<?php
/**
 * Project_Mooptin
 */
class Project_Mooptin extends Core_Data_Storage{

	protected $_table='mooptin';
	protected $_fields=array('id', 'user_id', 'name', 'settings', 'tags', 'edited', 'added');

	public function __construct(){
		self::update();
	}
	
	public static function install(){
		Core_Sql::setExec('DROP TABLE IF EXISTS mooptin');
		Core_Sql::setExec("CREATE TABLE `mooptin`(
			`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
			`user_id` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			`name` TEXT NULL,
			`settings` TEXT NULL,
			`tags` TEXT NULL,
			`added` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			`edited` INT(11) UNSIGNED NOT NULL DEFAULT '0',
			PRIMARY KEY(`id`)
		)
		COLLATE='utf8_general_ci'
		ENGINE=MyISAM");
	}

	public static function update(){
		$_arrNulls=Core_Sql::getAssoc("SELECT NULL
            FROM INFORMATION_SCHEMA.COLUMNS
           WHERE table_name = 'mooptin'
             AND column_name = 'tags';");
		if( count( $_arrNulls ) == 0 ){
			Core_Sql::setExec("ALTER TABLE `mooptin` ADD `tags` TEXT NULL");
		}
	}

	protected function beforeSet(){
		$this->_data->setFilter( array( 'clear' ) );
		$company = new Project_Mooptin_Autoresponders();
		$company
			->onlyOwner()
			->getList( $_arList );
		foreach( $_arList as $_listId=>&$_listData ){
			if( in_array( $_listData['id'], $this->_data->filtered['settings']['integrations'] ) ){
				if( isset($this->_data->filtered['settings']['form'][$_listData['id']] ) && !empty( $this->_data->filtered['settings']['form'][$_listData['id']] ) ){
					$_flgHaveNewCustomField=false;
					foreach( $this->_data->filtered['settings']['form'][$_listData['id']] as $_key=>&$_linksElts ){
						if( isset( $_linksElts['new_name'] ) ){
							$flgUpdateName=false;
							if( isset($_listData['settings']['integration'][0]) && 'getresponse'==$_listData['settings']['integration'][0] ){
								$params=array();
								$params['name'] = $_linksElts['new_name'];
								$params['type'] = 'text';
								$params['hidden'] = 'false';
								$params['values'] = array();
								$getresponse=new Project_Mooptin_Getresponse( $_listData['settings']['options']['getresponse_api_key'] );
								$getresponse->setCustomField($params);
								$flgUpdateName=true;
							}
							if( $flgUpdateName ){
								$this->_data->filtered['settings']['form'][$_listData['id']][$_key]['name']=$_linksElts['new_name'];
								unset( $this->_data->filtered['settings']['form'][$_listData['id']][$_key]['new_name'] );
							}
						}
					}
				}
			}
		}
		if( !empty( $this->_data->filtered['tags'] ) ){
			$this->_data->filtered['tags'] = Project_Tags::set( $this->_data->filtered['tags'] );
		}
		$this->_data->setElement('settings', base64_encode( serialize( $this->_data->filtered['settings'] ) ) );
		return true;
	}

	protected function afterSet(){
		$this->_data->filtered['settings']=unserialize( base64_decode( $this->_data->filtered['settings'] ) );
		if( isset( $this->_data->filtered['settings']['validation_realtime'] ) ){
			Project_Validations_Realtime::setValue( Project_Validations_Realtime::MOOPTIN, $this->_data->filtered['id'], $this->_data->filtered['settings']['validation_realtime'] );
		}
		return true;
	}
	
	public function getList( &$mixRes ){
		parent::getList( $mixRes );
		$_tags = array();
		if( array_key_exists( 0, $mixRes ) ){
			foreach( $mixRes as &$_arrZeroData ){
				$_oldSettings=$_arrZeroData['settings'];
				$_arrZeroData['settings']=unserialize( base64_decode( $_arrZeroData['settings'] ) );
				if( $_arrZeroData['settings']===false ){
					$_arrZeroData['settings']=unserialize( $_arrZeroData['settings'] );
				}
				if( $_arrZeroData['settings']===false ){
					$_arrZeroData['settings']=unserialize( json_decode( str_replace( '\r\n\n', '\r\n', json_encode( $_oldSettings ) ) ) );
				}
				if( $_arrZeroData['settings']===false ){
					$_arrZeroData['settings']=$_oldSettings;
				}
				if( ctype_digit( $_arrZeroData['tags'] ) && !empty( $_arrZeroData['tags'] ) && !in_array( $_arrZeroData['tags'], $_tags ) ){
					$_tags[] = $_arrZeroData['tags'];
				}
			}
			if( !empty( $_tags ) ){
				$_tags = Project_Tags::get( $_tags );
				foreach( $mixRes as &$item ){
					if( ctype_digit( $item['tags'] ) ) {
						$item['tags'] = $_tags[ $item['tags'] ];
					}
				}
			}
		}elseif( isset( $mixRes['settings'] ) ){
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
			if( ctype_digit( $mixRes['tags'] ) )
				$mixRes['tags'] = current( Project_Tags::get( $mixRes['tags'] ) );
		}
		return $this;
	}

	public static function generateJsFormAction( $_strForm='', $_arrFetch=array(), $_formId='', $_redirectUrl='', $_jsEvent='' ){
		$urlArrayData='';
		$_links=array( $_redirectUrl );
		if( strpos( $_redirectUrl, ',' ) !== false ){
			$_links=explode( ',', str_replace( ' ', '', $_redirectUrl ) );
		}
		if( is_array( $_links ) ){
			foreach( $_links as $_url ){
				$urlArrayData.='urlArray.push("'.$_url.'");';
			}
		}
		$_jsFunctions='
jQuery.noConflict();
var ulp_encode64=function(input){
	var keyString = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
	var output = "";
	var chr1, chr2, chr3, enc1, enc2, enc3, enc4;
	var i = 0;
	input = ulp_utf8encode(input);
	while(i < input.length){
		chr1 = input.charCodeAt(i++);
		chr2 = input.charCodeAt(i++);
		chr3 = input.charCodeAt(i++);
		enc1 = chr1 >> 2;
		enc2 =((chr1 & 3) << 4) |(chr2 >> 4);
		enc3 =((chr2 & 15) << 2) |(chr3 >> 6);
		enc4 = chr3 & 63;
		if(isNaN(chr2)){
			enc3 = enc4 = 64;
		} else if(isNaN(chr3)){
			enc4 = 64;
		}
		output = output + keyString.charAt(enc1) + keyString.charAt(enc2) + keyString.charAt(enc3) + keyString.charAt(enc4);
	}
	return output;
}
var ulp_utf8encode=function(string){
	string = string.replace(/\x0d\x0a/g, "\x0a");
	var output = "";
	for(var n = 0; n < string.length; n++){
		var c = string.charCodeAt(n);
		if(c < 128){
			output += String.fromCharCode(c);
		} else if((c > 127) &&(c < 2048)){
			output += String.fromCharCode((c >> 6) | 192);
			output += String.fromCharCode((c & 63) | 128);
		} else {
			output += String.fromCharCode((c >> 12) | 224);
			output += String.fromCharCode(((c >> 6) & 63) | 128);
			output += String.fromCharCode((c & 63) | 128);
		}
	}
	return output;
}
var transformToAssocArray=function( prmstr ){
	var params = {};
	var prmarr = prmstr.split("&");
	for( var i = 0; i < prmarr.length; i++){
		var tmparr = prmarr[i].split("=");
		params[tmparr[0]] = tmparr[1];
	}
	return params;
}
var getSearchParameters=function(){
	var prmstr = window.location.search.substr(1);
	return prmstr != null && prmstr != "" ? transformToAssocArray(prmstr) : {};
}
var global_redirect_url="";
var global_email_status="";
var global_moopen="";
var ulp_subscribing=false;
var ulp_subscribe=function( ulpElt ){
	if(ulp_subscribing) return false;
	ulp_subscribing = true;
	var sendData={};
	var haveErrors=false;
	jQuery.each(ulpElt.find(\'input\'), function( key, element ){
		if( jQuery(element)[0].value == "" && jQuery(jQuery(element)[0]).attr("type")!="button" && jQuery(jQuery(element)[0]).attr("type")!="submit" ){
		//	jQuery(element).attr("data-placeholder", jQuery(element).attr("placeholder"));
		//	jQuery(element).attr("placeholder", "This field is required");
			haveErrors=true;
			jQuery(element).attr("data-background",jQuery(element).css("background-color"));
			jQuery(element).animate({
				"background-color": "#faa"
			}, 5000, function(){
				jQuery(element).animate({
					"background-color": jQuery( this ).attr("data-background")
				}, 5000);
			});
		//	jQuery(element).focus(function(){
		//		jQuery( this ).attr("placeholder", jQuery( this ).attr("data-placeholder"));
		//	});
		}	
		sendData[jQuery(element).attr("name")]=jQuery(element)[0].value;
	});
	if( haveErrors ){
		alert("Please enter all required fields!");
		ulp_subscribing=false;
		return false;
	}
	sendData["id"]="'.$_formId.'";
	if(typeof userIP!="undefined"){
		sendData["ip"]=userIP;
	}else{
		sendData["ip"]="";
	}
	var userAgent = navigator.userAgent || navigator.vendor || window.opera;
	sendData["userAgent"]="W";
	if(/windows phone/i.test(userAgent)){
		sendData["userAgent"]="W";
	}
	if(/android/i.test(userAgent)){
		sendData["userAgent"]="A";
	}
	if(/iPad|iPhone|iPod/.test(userAgent) && !window.MSStream){
		sendData["userAgent"]="I";
	}
	var getParam=getSearchParameters();
	if( getParam!={} ){
		sendData["with_get"]=getParam;
	}
	jQuery( ulpElt ).prepend( \'<div class="cssload-circle"><div class="cssload-up"><div class="cssload-innera"></div></div><div class="cssload-down"><div class="cssload-innerb"></div></div></div>\' );
	jQuery( ".error-email" ).remove();
	global_email_status="";
	jQuery.ajax({
		url: "//'.@$_SERVER['HTTP_HOST'].'/services/mooptin.php",
		data: sendData,
		method: "post",
		dataType: "jsonp",
		success: function(data){
			try {
				var status = data.status;
				if(status == "OK"){
					var close_delay = 0;
					if(data.close_delay) close_delay = data.close_delay;
					var redirect_url = "";
					if( data.return_url != null ){
						var redirect_url = data.return_url;
					}
					setTimeout(function(){
						var urlArray=[];
						'.$urlArrayData.'
						global_redirect_url=urlArray[Math.floor(Math.random()*urlArray.length)];
						if( redirect_url!="" ){
							global_moopen=redirect_url;
						} else {
							ulp_subscribing = false;
						}
					}, close_delay);
					'.$_jsEvent.'
				} else if(status == "error"){
					global_email_status="error";
					ulp_subscribing = false;
					if( typeof data.errors != "undefined" ){
						jQuery.each(data.errors, function( key, element ){
						if( jQuery(\'[name="\'+element+\'"]\').length > 0 ){
								jQuery(\'[name="\'+element+\'"]\').addClass("mo-error");
								jQuery(\'[name="\'+element+\'"]\').change(function(evt){
									jQuery(evt.target).removeClass("mo-error");
								});
							}	
						});
					}
				} else {
					ulp_subscribing = false;
				}
				jQuery(".cssload-circle").remove();
				if( typeof data.message != "undefined" ){
					jQuery( ulpElt ).prepend( "<span class=\"error-email\">" + data.message + "</span>" );
				}
			} catch(error){
				ulp_subscribing = false;
			}
		}
	});
	return false;
}
jQuery.each(jQuery("#form-'.$_formId.'").find(\'input\'), function( key, element ){
	if( jQuery(jQuery(element)[0]).attr("placeholder") != "" && jQuery(jQuery(element)[0]).attr("type")!="button" && jQuery(jQuery(element)[0]).attr("type")!="submit" ){
		if( jQuery(jQuery(element)[0]).attr("data-placeholder") == null ){
			jQuery(jQuery(element)[0]).attr("data-placeholder", jQuery(jQuery(element)[0]).attr("placeholder"));
		}
		jQuery(jQuery(element)[0]).focus(function(){
			jQuery(jQuery(element)[0]).attr("placeholder", "" );
		});
		jQuery(jQuery(element)[0]).blur(function(){
			jQuery(jQuery(element)[0]).attr("placeholder",jQuery(jQuery(element)[0]).attr("data-placeholder"));
		});
	}	
});
';
$_jsRun='
ulp_subscribe(jQuery("#form-'.$_formId.'"));
var redirect_check=function(){
	setTimeout(function(){
		if( global_redirect_url != "" || global_moopen != "" ){
			if( global_moopen != "" ){
				window.location.href=global_moopen;
				setTimeout(function(){
					window.location.href=global_redirect_url;
				}, 50);
			}else{
				window.location.href=global_redirect_url;
			}
		}else{
			redirect_check();
		}
	}, 50);
};
redirect_check();
';
$_jsAction='
jQuery("#form-'.$_formId.'").submit(function(elt){
	elt.preventDefault();
	'.$_jsRun.'
	return false;
});
';
return array( 'function'=> $_jsFunctions, 'action'=>$_jsAction, 'run'=> $_jsRun );
	}

	public static function generateForm( $_strForm='',$_arrFetch=array(), $_formId='' ){
		$patternForm="'<\/?(FORM|INPUT|SELECT|TEXTAREA|(OPTION))[^<>]*>(?(2)(.*(?=<\/?(option|select)[^<>]*>[\r\n]*)|(?=[\r\n]*))|(?=[\r\n]*))'Usi";
		$patternElt = '#(?(DEFINE)
			(?<name>[a-zA-Z][a-zA-Z0-9-:]*)
			(?<value_double>"[^"]+")
			(?<value_single>\'[^\']+\')
			(?<value_none>[^\s>]+)
			(?<value>((?&value_double)|(?&value_single)|(?&value_none))))
			(?<n>(?&name))(=(?<v>(?&value)))?#xs';
		$eltsAttrs=array();
		if( preg_match_all( $patternForm, str_replace( '/>','>',$_strForm), $elements) ){
			foreach( $elements[0] as $_k=>$strElt ){
				if( $strElt[1] == '/' ){
					continue;
				}
				if(preg_match_all($patternElt, $strElt, $matches, PREG_SET_ORDER)){
					foreach($matches as $_i=>$match){
						if( $_i == 0 ){
							$eltsAttrs[$_k]['tag']=$match['n'];
						}else{
							$eltsAttrs[$_k][$match['n']] = isset($match['v'])
								? trim($match['v'], '\'"')
								: null;
						}
					}
				}
			}
		}
		$_html=$_formAction='';
		foreach( $_arrFetch['add'] as $key=>$values ){
			if( isset( $values['remove'] ) && $values['remove'] == 1 ){
				unset( $_arrFetch['add'][$key] );
			}
		}
		foreach( $_arrFetch['add'] as $new ){
			if( !empty( $new['name'] ) ||( !isset( $new['name'] ) && !empty( $new['tag'] ) ) ){
				$newArrAdd=array(
					'tag' => 'input',
					'type' => 'text',
					'name' =>(!empty($new['name'])?$new['name']:$new['tag']),
					'value' =>(!empty($new['value'])?$new['value']:''),
					'flg_hidden' => (!empty($new['flg_hidden'])?$new['flg_hidden']:''),
				);
				if( isset( $new['placeholder'] ) && !empty( $new['placeholder'] ) ){
					$newArrAdd['placeholder']=$new['placeholder'];
				}
				$eltsAttrs[]=$newArrAdd;
			}
		}
		foreach( $eltsAttrs as $values ){
			//===============
			$_eltType='';
			$_nameTst=implode( ' ', $values );
			if( strpos( strtolower( $_nameTst ), 'name' ) !== false ){
				$_eltType='name';
			}
			if( strpos( strtolower( $_nameTst ), 'firstname' ) !== false ||  strpos( strtolower( $_nameTst ), 'fname' ) !== false ){
				$_eltType='firstname';
			}
			if( strpos( strtolower( $_nameTst ), 'lastname' ) !== false ||  strpos( strtolower( $_nameTst ), 'lname' ) !== false ){
				$_eltType='lastname';
			}
			if( strpos( strtolower( $_nameTst ), 'street' ) !== false ){
				$_eltType='street';
			}
			if( strpos( strtolower( $_nameTst ), 'zip' ) !== false ){
				$_eltType='zip';
			}
			if( strpos( strtolower( $_nameTst ), 'city' ) !== false ){
				$_eltType='city';
			}
			if( strpos( strtolower( $_nameTst ), 'country' ) !== false ){
				$_eltType='country';
			}
			if( strpos( strtolower( $_nameTst ), 'phone' ) !== false ){
				$_eltType='phone';
			}
			if( strpos( strtolower( $_nameTst ), 'email' ) !== false ){
				$_eltType='email';
			}
			//===========
			$_tag=$_type=$_name=$_value=$_placeholder='';
			foreach( $values as $name=>$value ){
				if( $name == 'tag' ){
					$_tag=$value;
					if( $value == 'form' ){
						$_formAction=$_formActionOld=$values['action'];
						$_formHash=md5( $value.$_formActionOld );
						$_formMethod=( isset( $values['method'] )?$values['method']:'get' );
						if( isset( $_arrFetch['attr'][$_formHash]['method'] ) ){
							$_formMethod=$_arrFetch['attr'][$_formHash]['method'];
						}
						if( isset( $_arrFetch['attr'][$_formHash]['action'] ) ){
							$_formAction=$_arrFetch['attr'][$_formHash]['action'];
						}
						$_html.='<form id="form-'.$_formId.'" action="'.$_formAction.'" method="'.$_formMethod.'">';
						continue 2;
					}
				}
				if( $name == 'name' ){
					$_nameOld=$_name=$value;
					if( isset( $_arrFetch['add'][$_formHash]['name'] ) ){
						$_name=$_arrFetch['attr'][$_formHash]['name'];
					}elseif( isset( $_arrFetch['add'][$_formHash]['label'] ) ){
						$_name=$_arrFetch['attr'][$_formHash]['label'];
					}
				}
				if( $name == 'type' ){
					$_type=$value;
					if( isset( $_arrFetch['add'][$_formHash]['type'] ) ){
						$_type=$_arrFetch['attr'][$_formHash]['type'];
					}
				}
				if( $name == 'flg_hidden' && $value == 1 ){
					$_type='hidden';
				}
				if( $name == 'value' ){
					$_valueOld=$_value=$value;
					if( isset( $_arrFetch['add'][$_formHash]['value'] ) ){
						$_value=$_arrFetch['attr'][$_formHash]['value'];
					}
				}
				if( $name == 'placeholder' ){
					$_placeholder=$value;
					if( isset( $_arrFetch['add'][$_formHash]['placeholder'] ) ){
						$_placeholder=$_arrFetch['attr'][$_formHash]['placeholder'];
					}
				}
			}
			if( !empty( $_name ) && !empty( $_type ) ){
				$_hachTag=md5( $_valueOld.$_nameOld.$_formActionOld );

				$_phText='';
				switch( $_eltType ){
					case 'email': $_phText='Enter Your Email';break;
					case 'phone': $_phText='Enter Your Phone';break;
					case 'country': $_phText='Enter Your Country';break;
					case 'city': $_phText='Enter Your City';break;
					case 'zip': $_phText='Enter Your Zip Code';break;
					case 'street': $_phText='Enter Your Street';break;
					case 'lastname': $_phText='Enter Your Last Name';break;
					case 'firstname': $_phText='Enter Your First Name';break;
					case 'name': $_phText='Enter Your Name';break;
					default:
				}
				if( !empty( $_placeholder ) ){
					$_phText=$_placeholder;
				}
				if( $_type != 'textarea' && $_type != 'select' && $_type != 'option' ){
					$_html.='
<input type="'.$_type.'" name="'.$_name.'"'.(!empty($_value)?' value="'.$_value.'"':'').''.(!empty($_phText)?' placeholder="'.$_phText.'"':'').' />';
				}elseif( $_type != 'select' && $_type != 'option' ){
					$_html.='
<textarea type="'.$_type.'" name="'.$_name.'">'.(!empty($_value)?' value="'.$_value.'"':(!empty($_phText)?' placeholder="'.$_phText.'"':'')).'</textarea>';
				}else{
					
				}
			}
		}
		
		if( !empty( $_html ) ){
			if( $_arrFetch['flg_gdpr'] ){
				$_html .= sprintf( '<div class="gdpr-block">%s</div>', $_arrFetch['gdpr'] );
			}
			$_html.='
<input type="submit" value="Submit">
</form>';
		}
		return $_html;
		exit;
	}

	public static function getCodeForm( $_strForm='',$_arrFetch=array(), $_formId='' ){
		$patternForm="'<\/?(FORM|INPUT|SELECT|TEXTAREA|(OPTION))[^<>]*>(?(2)(.*(?=<\/?(option|select)[^<>]*>[\r\n]*)|(?=[\r\n]*))|(?=[\r\n]*))'Usi";
		$patternElt = '#(?(DEFINE)
			(?<name>[a-zA-Z][a-zA-Z0-9-:]*)
			(?<value_double>"[^"]+")
			(?<value_single>\'[^\']+\')
			(?<value_none>[^\s>]+)
			(?<value>((?&value_double)|(?&value_single)|(?&value_none))))
			(?<n>(?&name))(=(?<v>(?&value)))?#xs';
		$eltsAttrs=array();
		if( preg_match_all( $patternForm, str_replace( '/>','>',$_strForm), $elements) ){
			foreach( $elements[0] as $_k=>$strElt ){
				if( $strElt[1] == '/' ){
					continue;
				}
				if(preg_match_all($patternElt, $strElt, $matches, PREG_SET_ORDER)){
					foreach($matches as $_i=>$match){
						if( $_i == 0 ){
							$eltsAttrs[$_k]['tag']=$match['n'];
						}else{
							$eltsAttrs[$_k][$match['n']] = isset($match['v'])
								? trim($match['v'], '\'"')
								: null;
						}
					}
				}
			}
		}
		$_html=$_formAction='';
		foreach( $_arrFetch['add'] as $key=>$values ){
			if( isset( $values['remove'] ) && $values['remove'] == 1 ){
				unset( $_arrFetch['add'][$key] );
			}
		}
		foreach( $_arrFetch['add'] as $new ){
			if( !empty( $new['name'] ) ||( !isset( $new['name'] ) && !empty( $new['tag'] ) ) ){
				$newArrAdd=array(
					'tag' => 'input',
					'type' => 'text',
					'name' =>(!empty($new['name'])?$new['name']:$new['tag']),
					'value' =>(!empty($new['value'])?$new['value']:''),
					'flg_hidden' => (!empty($new['flg_hidden'])?$new['flg_hidden']:''),
				);
				if( isset( $new['placeholder'] ) && !empty( $new['placeholder'] ) ){
					$newArrAdd['placeholder']=$new['placeholder'];
				}
				$eltsAttrs[]=$newArrAdd;
			}
		}
		foreach( $eltsAttrs as $values ){
			//===============
			$_eltType='';
			$_nameTst=implode( ' ', $values );
			if( strpos( strtolower( $_nameTst ), 'name' ) !== false ){
				$_eltType='name';
			}
			if( strpos( strtolower( $_nameTst ), 'firstname' ) !== false ||  strpos( strtolower( $_nameTst ), 'fname' ) !== false ){
				$_eltType='firstname';
			}
			if( strpos( strtolower( $_nameTst ), 'lastname' ) !== false ||  strpos( strtolower( $_nameTst ), 'lname' ) !== false ){
				$_eltType='lastname';
			}
			if( strpos( strtolower( $_nameTst ), 'street' ) !== false ){
				$_eltType='street';
			}
			if( strpos( strtolower( $_nameTst ), 'zip' ) !== false ){
				$_eltType='zip';
			}
			if( strpos( strtolower( $_nameTst ), 'city' ) !== false ){
				$_eltType='city';
			}
			if( strpos( strtolower( $_nameTst ), 'country' ) !== false ){
				$_eltType='country';
			}
			if( strpos( strtolower( $_nameTst ), 'phone' ) !== false ){
				$_eltType='phone';
			}
			if( strpos( strtolower( $_nameTst ), 'email' ) !== false ){
				$_eltType='email';
			}
			//===========
			$_tag=$_type=$_name=$_value=$_placeholder='';
			foreach( $values as $name=>$value ){
				if( $name == 'tag' ){
					$_tag=$value;
					if( $value == 'form' ){
						$_formAction=$_formActionOld=$values['action'];
						$_formHash=md5( $value.$_formActionOld );
						$_formMethod=( isset( $values['method'] )?$values['method']:'get' );
						if( isset( $_arrFetch['attr'][$_formHash]['method'] ) ){
							$_formMethod=$_arrFetch['attr'][$_formHash]['method'];
						}
						if( isset( $_arrFetch['attr'][$_formHash]['action'] ) ){
							$_formAction=$_arrFetch['attr'][$_formHash]['action'];
						}
						$_html.='<form id="form-'.$_formId.'" action="https://'.Zend_Registry::get( 'config' )->engine->project_domain.Core_Module_Router::getCurrentUrl( array('name'=>'site1_mooptin','action'=>'form') ).'" method="post">';
						continue 2;
					}
				}
				if( $name == 'name' ){
					$_nameOld=$_name=$value;
					if( isset( $_arrFetch['add'][$_formHash]['name'] ) ){
						$_name=$_arrFetch['attr'][$_formHash]['name'];
					}
				}
				if( $name == 'type' ){
					$_type=$value;
					if( isset( $_arrFetch['add'][$_formHash]['type'] ) ){
						$_type=$_arrFetch['attr'][$_formHash]['type'];
					}
				}
				if( $name == 'flg_hidden' && $value == 1 ){
					$_type='hidden';
				}
				if( $name == 'value' ){
					$_valueOld=$_value=$value;
					if( isset( $_arrFetch['add'][$_formHash]['value'] ) ){
						$_value=$_arrFetch['attr'][$_formHash]['value'];
					}
				}
				if( $name == 'placeholder' ){
					$_placeholder=$value;
					if( isset( $_arrFetch['add'][$_formHash]['placeholder'] ) ){
						$_placeholder=$_arrFetch['attr'][$_formHash]['placeholder'];
					}
				}
			}
			if( $_type=='hidden' ){
				$_value=$_placeholder;
				$_placeholder='';
			}
			if( !empty( $_name ) && !empty( $_type ) ){
				$_hachTag=md5( $_valueOld.$_nameOld.$_formActionOld );

				$_phText='';
				switch( $_eltType ){
					case 'email': $_phText='Enter Your Email';break;
					case 'phone': $_phText='Enter Your Phone';break;
					case 'country': $_phText='Enter Your Country';break;
					case 'city': $_phText='Enter Your City';break;
					case 'zip': $_phText='Enter Your Zip Code';break;
					case 'street': $_phText='Enter Your Street';break;
					case 'lastname': $_phText='Enter Your Last Name';break;
					case 'firstname': $_phText='Enter Your First Name';break;
					case 'name': $_phText='Enter Your Name';break;
					default:
				}
				if( !empty( $_placeholder ) ){
					$_phText=$_placeholder;
				}
				if( $_type != 'textarea' && $_type != 'select' && $_type != 'option' ){
					$_html.='
<input type="'.$_type.'" name="'.$_name.'"'.(!empty($_value)?' value="'.$_value.'"':'').''.(!empty($_phText)?' placeholder="'.$_phText.'"':'').' />';
				}elseif( $_type != 'select' && $_type != 'option' ){
					$_html.='
<textarea type="'.$_type.'" name="'.$_name.'">'.(!empty($_value)?' value="'.$_value.'"':(!empty($_phText)?' placeholder="'.$_phText.'"':'')).'</textarea>';
				}else{
					
				}
			}
		}
		if( !empty( $_html ) ){
			$_html.='
<input type="hidden" name="userAgent" value="F" />
<input type="hidden" name="id" value="'. Core_Payment_Encode::encode( array( $_formId ) ) .'" />';
			$_html.='
<input type="submit" value="Submit">
</form>';
		}
		return $_html;
		exit;
	}

	public static function fetchForm( $_strForm ){
		$patternForm="'<\/?(FORM|INPUT|SELECT|TEXTAREA|(OPTION))[^<>]*>(?(2)(.*(?=<\/?(option|select)[^<>]*>[\r\n]*)|(?=[\r\n]*))|(?=[\r\n]*))'Usi";
		$patternElt = '#(?(DEFINE)
			(?<name>[a-zA-Z][a-zA-Z0-9-:]*)
			(?<value_double>"[^"]+")
			(?<value_single>\'[^\']+\')
			(?<value_none>[^\s>]+)
			(?<value>((?&value_double)|(?&value_single)|(?&value_none))))
			(?<n>(?&name))(=(?<v>(?&value)))?#xs';
		$eltsAttrs=array();
		if( preg_match_all( $patternForm, str_replace( '/>','>',$_strForm), $elements) ){
			foreach( $elements[0] as $_k=>$strElt ){
				if( $strElt[1] == '/' ){
					continue;
				}
				if(preg_match_all($patternElt, $strElt, $matches, PREG_SET_ORDER)){
					foreach($matches as $_i=>$match){
						if( $_i == 0 ){
							$eltsAttrs[$_k]['tag']=$match['n'];
						}else{
							$eltsAttrs[$_k][$match['n']] = isset($match['v'])
								? trim($match['v'], '\'"')
								: null;
						}
					}
				}
			}
		}
		$_html=$_formAction='';
		
		foreach( $eltsAttrs as $values ){
			//===============
			$_eltType='';
			$_nameTst=implode( ' ', $values );
			if( strpos( strtolower( $_nameTst ), 'name' ) !== false ){
				$_eltType='name';
			}
			if( strpos( strtolower( $_nameTst ), 'firstname' ) !== false ||  strpos( strtolower( $_nameTst ), 'fname' ) !== false ){
				$_eltType='firstname';
			}
			if( strpos( strtolower( $_nameTst ), 'lastname' ) !== false ||  strpos( strtolower( $_nameTst ), 'lname' ) !== false ){
				$_eltType='lastname';
			}
			if( strpos( strtolower( $_nameTst ), 'street' ) !== false ){
				$_eltType='street';
			}
			if( strpos( strtolower( $_nameTst ), 'zip' ) !== false ){
				$_eltType='zip';
			}
			if( strpos( strtolower( $_nameTst ), 'city' ) !== false ){
				$_eltType='city';
			}
			if( strpos( strtolower( $_nameTst ), 'country' ) !== false ){
				$_eltType='country';
			}
			if( strpos( strtolower( $_nameTst ), 'phone' ) !== false ){
				$_eltType='phone';
			}
			if( strpos( strtolower( $_nameTst ), 'email' ) !== false ){
				$_eltType='email';
			}
			//===========
			$_tag=$_type=$_name=$_value=$_placeholder='';
			$_html.='<div class="form-group new_etl_parent_load" style="margin-bottom: 5px;">';
			foreach( $values as $name=>$value ){
				if( $name == 'tag' ){
					$_tag=$value;
					if( $value == 'form' ){
						$_formAction=$values['action'];
					//	if( $_html=='' ){
							
					//	}
						if( $_formAction != '' ){
							$_html.='
<label style="float:left;width:20%;height:30px;margin-top: 7px;">Form Action:</label>
<input type="text" class="form-control" name="arrData[settings][form][attr]['.md5( $value.$_formAction ).'][action]" value="'.$values['action'].'" placeholder="Form Action" style="width:75%;float:left;margin-bottom:5px;">';
						}
						$_html.='<input type="hidden" name="arrData[settings][form][attr]['.md5( $value.$_formAction ).'][method]" value="'.( isset( $values['method'] )?$values['method']:'get' ).'">';
						continue 2;
					}
				}
				if( $name == 'name' ){
					$_name=$value;
				}
				if( $name == 'type' ){
					$_type=$value;
				}
				if( $name == 'value' ){
					$_value=$value;
				}
				if( $name == 'placeholder' ){
					$_placeholder=$value;
				}
			}
			if( !empty( $_name ) && !empty( $_type ) ){
				$_hachTag=md5( $_value.$_name.$_formAction );
				if( $_eltType=='' ) $_eltType=$_name;
				$_html.='
	<select name="arrData[settings][form][inputs]['.$_hachTag.'][name]" class="btn-group mooptin-select selectpicker show-tick bs-select-hidden" style="width: 40%; display: inline-block;">
		<option value="" '.(($_eltType!="name"&&$_eltType!="firstname"&&$_eltType!="lastname"&&$_eltType!="phone"&&$_eltType!="email"&&$_eltType!="country"&&$_eltType!="city"&&$_eltType!="zip"&&$_eltType!="street")?'selected':'').'>New Field Type</option>
		<option value="name" '.(($_eltType=="name")?'selected':'').'>Name</option>
		<option value="firstname" '.(($_eltType=="firstname")?'selected':'').'>First Name</option>
		<option value="lastname" '.(($_eltType=="lastname")?'selected':'').'>Last Name</option>
		<option value="phone" '.(($_eltType=="phone")?'selected':'').'>Phone</option>
		<option value="email" '.(($_eltType=="email")?'selected':'').'>Email</option>
		<option value="country" '.(($_eltType=="country")?'selected':'').'>Country</option>
		<option value="city" '.(($_eltType=="city")?'selected':'').'>City</option>
		<option value="zip" '.(($_eltType=="zip")?'selected':'').'>Zip Code</option>
		<option value="street" '.(($_eltType=="street")?'selected':'').'>Street</option>
	</select>
	
	<input type="text" class="form-control form_label" name="arrData[settings][form][inputs]['.$_hachTag.'][label]" style="width: 21%; margin-left: 5px; display: inline-block;" '.(($_eltType!="name"&&$_eltType!="firstname"&&$_eltType!="lastname"&&$_eltType!="phone"&&$_eltType!="email"&&$_eltType!="country"&&$_eltType!="city"&&$_eltType!="zip"&&$_eltType!="street")?'value="'.$_eltType.'"':'').'>
	
	<input type="hidden" class="form-control new_class_add_2 hash_tags" placeholder="write_name" name="arrData[settings][form][inputs]['.$_hachTag.'][tag]" value="'.$_eltType.'" >
	<input type="hidden" name="arrData[settings][form][inputs]['.$_hachTag.'][type]" value="'.$_type.'">';
				$_phText='';
				switch( $_eltType ){
					case 'email': $_phText='Enter Your Email';break;
					case 'phone': $_phText='Enter Your Phone';break;
					case 'country': $_phText='Enter Your Country';break;
					case 'city': $_phText='Enter Your City';break;
					case 'zip': $_phText='Enter Your Zip Code';break;
					case 'street': $_phText='Enter Your Street';break;
					case 'lastname': $_phText='Enter Your Last Name';break;
					case 'firstname': $_phText='Enter Your First Name';break;
					case 'name': $_phText='Enter Your Name';break;
					default:
				}
				if( !empty( $_placeholder ) ){
					$_phText=$_placeholder;
				}
				$_html.='
	<input name="arrData[settings][form][inputs]['.$_hachTag.'][placeholder]" data-name="Enter Your Name" data-firstname="Enter Your First Name" data-lastname="Enter Your Last Name" data-phone="Enter Your Phone" data-email="Enter Your Email" data-country="Enter Your Country" data-city="Enter Your City" data-zip="Enter Your Zip Code" data-street="Enter Your Street" type="text" placeholder="Enter Placeholder" class="form-control" style="width: 30%; margin-left: 5px; display: inline-block;" value="'.$_phText.'">
	<div class="checkbox checkbox-primary" style="width:10%;display: inline-block;margin-bottom:5px;margin-left:5px;">
		<input type="hidden" name="arrData[settings][form][inputs]['.$_hachTag.'][hidden]" value="0" checked>
		
		<input type="checkbox" name="arrData[settings][form][inputs]['.$_hachTag.'][hidden]" value="1" '.($_type=='hidden'?'checked':'').'>
		<label>Hide</label>
	</div>
';
			}
			$_html.='</div>';
		}
		echo $_html;
		exit;
	}
}
?>