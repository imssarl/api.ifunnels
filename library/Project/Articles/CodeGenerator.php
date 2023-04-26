<?php


/**
 * генератор кода для файлов которые заливаются на сайты
 */
class Project_Articles_CodeGenerator {
	
	public static function getCodeSingle($params) {
		$serverPath  = Zend_Registry::get( 'config' )->engine->project_domain;
		if (!empty($params['multibox_ids_select_one'])) {
			$ids = json_decode($params['multibox_ids_select_one']);
			foreach ($ids as $id) {
				$article_id = Project_Options_Encode::encode($id->id);
				$php .= '<?php
if(function_exists("curl_init"))
{
	$ch = @curl_init();
	curl_setopt($ch, CURLOPT_URL,"https://'.$serverPath.'/cronjobs/getcontent.php?type_view=showarticles&id='.$article_id.'");
	curl_setopt($ch, CURLOPT_HEADER, 0); curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$resp = @curl_exec($ch);
	$err = curl_errno($ch);
	if($err === false || $resp == "")
	{
		$newsstr = "";
	} else {
		if (function_exists("curl_getinfo"))
		{
			$info = curl_getinfo($ch);
			if ($info["http_code"]!=200)
			$resp="";
		}
		$newsstr = $resp;
	}
	@curl_close ($ch);
	echo $newsstr;
} else {
	@include("https://'.$serverPath.'/cronjobs/getcontent.php?type_view=showarticles&id='.$article_id.'");
}
?>';		
			}
		} else {
			
				$article_id = Project_Options_Encode::encode($params['id']);
				$php .= '<?php
if(function_exists("curl_init"))
{
	$ch = @curl_init();
	curl_setopt($ch, CURLOPT_URL,"https://'.$serverPath.'/cronjobs/getcontent.php?type_view=showarticles&id='.$article_id.'");
	curl_setopt($ch, CURLOPT_HEADER, 0); curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$resp = @curl_exec($ch);
	$err = curl_errno($ch);
	if($err === false || $resp == "")
	{
		$newsstr = "";
	} else {
		if (function_exists("curl_getinfo"))
		{
			$info = curl_getinfo($ch);
			if ($info["http_code"]!=200)
			$resp="";
		}
		$newsstr = $resp;
	}
	@curl_close ($ch);
	echo $newsstr;
} else {
	@include("https://'.$serverPath.'/cronjobs/getcontent.php?type_view=showarticles&id='.$article_id.'");
}
?>';			
			
			
		}
			return $php;
	}
	
	public static function getCodeCategory( $catid ) {
		$serverPath  = Zend_Registry::get( 'config' )->engine->project_domain;
		$catid = Project_Options_Encode::encode($catid);
		$php = '<?php 
	if(function_exists("curl_init")) { 
		$ch = @curl_init(); 
		curl_setopt($ch, CURLOPT_URL,"https://'.$serverPath.'/cronjobs/getcontent.php?type_view=showarticles&category_id='.$catid.'");
		curl_setopt($ch, CURLOPT_HEADER, 0); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		$resp = @curl_exec($ch); 
		$err = curl_errno($ch); 
		if($err === false || $resp == "") { 
			$newsstr = ""; 
		} else { 
			if (function_exists("curl_getinfo")) { 
				$info = curl_getinfo($ch); 
				if ($info["http_code"]!=200) $resp=""; 
			} 
			$newsstr = $resp; 
		} 
		@curl_close ($ch); 
		echo $newsstr; 
	} else { 
		@include("https://'.$serverPath.'/cronjobs/getcontent.php?type_view=showarticles&category_id='.$catid.'");
	} 
?>';		
		return $php;
	}
	
	public static function getCodeNumber($params) {
		$serverPath  = Zend_Registry::get( 'config' )->engine->project_domain;
		$ids = json_decode($params['multibox_ids_select_two']);
		foreach ($ids as $id) {
			$article_id = Project_Options_Encode::encode($id->id);
			$php .= '<?php
if(function_exists("curl_init"))
{
	$ch = @curl_init();
	curl_setopt($ch, CURLOPT_URL,"https://'.$serverPath.'/cronjobs/getcontent.php?type_view=showarticles&id='.$article_id.'");
	curl_setopt($ch, CURLOPT_HEADER, 0); curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$resp = @curl_exec($ch);
	$err = curl_errno($ch);
	if($err === false || $resp == "")
	{
		$newsstr = "";
	} else {
		if (function_exists("curl_getinfo"))
		{
			$info = curl_getinfo($ch);
			if ($info["http_code"]!=200)
			$resp="";
		}
		$newsstr = $resp;
	}
	@curl_close ($ch);
	echo $newsstr;
} else {
	@include("https://'.$serverPath.'/cronjobs/getcontent.php?type_view=showarticles&id='.$article_id.'");
}
?>';		}
			return $php;
	}	
	
	
	public static function getCodeRandom($params) {
		$serverPath  = Zend_Registry::get( 'config' )->engine->project_domain;
		$artNum = $params['random_number'];
		$catid = Project_Options_Encode::encode($params['category_randart']);
		$php = '<?php 
	if(function_exists("curl_init")) { 
		$ch = @curl_init(); 
		curl_setopt($ch, CURLOPT_URL,"https://'.$serverPath.'/cronjobs/getcontent.php?type_view=showarticles&category_id='.$catid.'&nb='.$artNum.'&type=rand");
		curl_setopt($ch, CURLOPT_HEADER, 0); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		$resp = @curl_exec($ch); 
		$err = curl_errno($ch); 
		if($err === false || $resp == "") { 
			$newsstr = ""; 
		} else { 
			if (function_exists("curl_getinfo")) { 
				$info = curl_getinfo($ch); 
				if ($info["http_code"]!=200) $resp=""; 
			} 
			$newsstr = $resp; 
		} 
		@curl_close ($ch); 
		echo $newsstr; 
	} else { 
		@include("https://'.$serverPath.'/cronjobs/getcontent.php?type_view=showarticles&category_id='.$catid.'&nb='.$artNum.'&type=rand");
	} 
?>';		
		return $php;
	}

	public static function getCodeKeyword($params) {
		$serverPath  = Zend_Registry::get( 'config' )->engine->project_domain;
		$keyword = $params['keyword'];
		$catid = Project_Options_Encode::encode($params['category_kwdart']);
		$php = '<?php 
if(function_exists("curl_init")) { 
	$ch = @curl_init(); 
	curl_setopt($ch, CURLOPT_URL,"https://'.$serverPath.'/cronjobs/getcontent.php?type_view=showarticles&keyword='.$keyword.'&defcategory='.$catid.'");
	curl_setopt($ch, CURLOPT_HEADER, 0); 
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
	$resp = @curl_exec($ch); 
	$err = curl_errno($ch); 
	if($err === false || $resp == "") { 
		$newsstr = ""; 
	} else { 
		if (function_exists("curl_getinfo")) { 
			$info = curl_getinfo($ch); 
			if ($info["http_code"]!=200) $resp=""; 
		} 
		$newsstr = $resp; 
	} 
	@curl_close ($ch); 
	echo $newsstr; 
} else { 
	@include("https://'.$serverPath.'/cronjobs/getcontent.php?type_view=showarticles&keyword='.$keyword.'&defcategory='.$catid.'");
} 
?>';		
		return $php;
	}

	public static function getCodeSnippets($params) {
		$serverPath  = Zend_Registry::get( 'config' )->engine->project_domain;
		$snippets = $params['snippets_number'];
		$catid = Project_Options_Encode::encode($params['category_artsnip']);
		$php = '<?php 
if(function_exists("curl_init")) { 
	$ch = @curl_init(); 
	curl_setopt($ch, CURLOPT_URL,"https://'.$serverPath.'/cronjobs/getcontent.php?type_view=showarticlesnippets&category_id='.$catid.'&nb='.$snippets.'");
	curl_setopt($ch, CURLOPT_HEADER, 0); 
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
	$resp = @curl_exec($ch); 
	$err = curl_errno($ch); 
	if($err === false || $resp == "") { 
		$newsstr = ""; 
	} else { 
		if (function_exists("curl_getinfo")) { 
			$info = curl_getinfo($ch); 
			if ($info["http_code"]!=200) $resp=""; 
		} 
		$newsstr = $resp; 
	} 
	@curl_close ($ch); 
	echo $newsstr; 
} else { 
	@include("https://'.$serverPath.'/cronjobs/getcontent.php?type_view=showarticlesnippets&category_id='.$catid.'&nb='.$snippets.'");
} 
?>';		
		return $php;
	}
	

	public static function saveCode( $params ) {
		$name = htmlentities($params['code_title']);
		$desc = htmlentities($params['code_desc']);
		$disp = htmlentities($params['optArt']);
		$code = htmlentities($params['php_code']);
		Zend_Registry::get( 'objUser' )->getId( $_int );
		$data = array(
			"name" => $name,
			"description"	=> $desc,
			"disp_option"	=> $disp,
			"code"			=> $code,
			"user_id"		=> $_int
		);
		$id = Core_Sql::setInsert("hct_am_savedcode", $data );
		return $id ? true : false;
	}
}
?>