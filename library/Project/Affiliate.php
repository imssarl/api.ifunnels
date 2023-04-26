<?php

class Project_Affiliate extends Core_Media_Ftp   {

	public $ftp_params = array();
	private $_transport=false;

	public function init( $params ){
		$this->_transport=new Project_Placement_Transport();
		$params['arrTransport']['ftp_directory']=Core_Files::getDirName($params['arrTransport']['ftp_directory']);
		if ( !$this->_transport->setInfo( $params['arrTransport'] ) ) {
			return false;
		}
		return true;
	}

	public function getFile( $arrData ) {
		$_file = $arrData['arrTransport']['ftp_directory'].( ( $arrData['file_name'] ) ? htmlspecialchars( $arrData['file_name']) : htmlspecialchars( $arrData['file_name_ad']) );
		//$_file=Core_Files::getBaseName($arrData['arrTransport']['ftp_directory']);
		if( !$this->_transport->readFile( $strContent, $_file)) {
			$this->_transport->breakConnect();
			return Core_Data_Errors::getInstance()->setError('Can not read file');
		}
		$this->_transport->breakConnect();
		return $strContent;
	}

	public function writeFile( $arrData ){
		if( empty( $arrData['file_name'] ) && empty( $arrData['file_name_ad'] ) && !empty( $arrData['arrTransport']['ftp_directory'] ) )
			$arrData['file_name']=$arrData['arrTransport']['ftp_directory'];
		if( $this->_transport->getDir() == substr( $arrData['arrTransport']['ftp_directory'], 0, strlen($this->_transport->getDir()) ) && strlen($this->_transport->getDir())!=strlen( $this->_transport->getDir() ) ){
			$arrData['file_name']=str_replace( $this->_transport->getDir(), '', $arrData['file_name'] );
			$arrData['arrTransport']['ftp_directory']='';
		}
		$_file = $arrData['arrTransport']['ftp_directory'].( ( $arrData['file_name'] ) ? htmlspecialchars( $arrData['file_name']) : htmlspecialchars( $arrData['file_name_ad']) );
		//$_file=Core_Files::getBaseName($arrData['arrTransport']['ftp_directory']);
		if( !$this->_transport->saveFile( $arrData['file_content'], $_file) ){
			return Core_Data_Errors::getInstance()->setError('Can not save content');
		}
		$this->_transport->chmod( $_file, 0644 );
		return true;
	}

	public function createContent( &$arrData ){
		if( !isset( $arrData['file_content'] ) ){
			$arrData['file_content']= '';
		}
		if ($arrData['cpp'] == 1) {
			$arrData['file_content'] .= $this->getTrackingCode($arrData['ad_id'], $arrData['ad_env'], $this->getTrackingIdByAdId($arrData['ad_id']));
		}
		if (isset($arrData['cloack']) && $arrData['cloack'] == 'redirect') {
			$arrData['file_content'] .= '<?php header("Location: '. $arrData['redirect_url'] .'"); ?>';
		} else {
			$ep_content=$ep_scripts='';
			if ( !empty($arrData['ep_link_id']) && $arrData['ep_link_add'] == 1) {
				Project_Exquisite::getOnLoadCampaign( $arrData['ep_link_id'], $ep_content );
			}
			$arrData['file_content'] .= '<!doctype html>
<html>
	<head>
		<base href="'.$arrData['redirect_url2'].'">
		<title>'. htmlentities( $arrData['page_title'] ) .'</title>
		<meta name="keywords" content="'. htmlentities( $arrData['meta_tag'] ) .'"/>
		<style type="text/css">
			html, body, div.iframe, iframe { margin:0; padding:0; height:100%; }
			iframe { display:block; width:100%; border:none; }
			html, body {overflow: hidden;}
		</style>
	</head>
	<body>
	<div class="iframe">
		<iframe src="'.$arrData['redirect_url2'].'" height="100%" width="100%"></iframe>
	</div>
';
			$code = array('get'=>'');
			if ( !empty($arrData['arrOpt']['dams']['ids']) && $arrData['dams_add'] == 1) {
				if( $arrData['headlines_spot1']==1 ){
					$code=Project_Widget_Adapter_Hiam_Split::getCode($arrData['arrOpt']['dams']['ids']);
				} else {
					$code=Project_Widget_Adapter_Hiam_Campaign::getCode($arrData['arrOpt']['dams']['ids']);
				}
			}
			if ( !empty($arrData['rt_link_content']) && $arrData['rt_link_add'] == 1) {
				$code['get'].=$arrData['rt_link_content'];
			}
			$arrData['file_content'].= $code['get'].$ep_content.'	</body>
</html>';
		}
	}

	public function creatPage( &$arrData ){
		$this->createContent( $arrData );
		if( !$this->writeFile( $arrData ) || !$this->setAffiliatePage( $arrData ) ){
			return false;
		}
		return true;
	}
	
	private function getTrackingIdByAdId( $aid ) {
		$sql = "SELECT id FROM  hct_ccp_track WHERE ad_id = $aid LIMIT 1";
		$kid = Core_Sql::getCell($sql);
		return $kid;
	}
		
	private function getTrackingCode($cid, $env='K', $tid=0)
	{
		if ($env == 'C') { 
			$clink = "&tid=$tid"; 
		} else {
			$clink = "";
		}
		
		$code ='<?php';
		if ($env == 'K' || $env == 'C') {
		$code .= '
		$href = urlencode(@$_SERVER["HTTP_REFERER"]);
		if($href=="")$href=urlencode($_SERVER["HTTP_HOST"].$_SERVER["PHP_SELF"]);
		$rfip = $_SERVER["REMOTE_ADDR"];
		$url = "http://'.$_SERVER['HTTP_HOST'].'/ccp/trackid.php?href=$href&ip=$rfip&id='.Project_Options_Encode::encode($cid).$clink.'";';
		} else if ($env == 'T') {
		$code .= '
		////////////////////////////////////////////////////////////////////////////
		$amount = "AMOUNT"; // AMOUNT can be replaced with actual amount of product
		$items = "ITEMS";   // ITEMS can be replaced with no of items
		////////////////////////////////////////////////////////////////////////////
		$track_id = $_COOKIE["track_id"];
		$url = "http://'.$_SERVER['HTTP_HOST'].'/ccp/trackid.php?mytid=$track_id&items=$items&amount=$amount";';
		}
		
		
		$code .= '
		if(function_exists("curl_init"))
		{
			$ch = @curl_init();
			@curl_setopt($ch, CURLOPT_URL, $url);
			@curl_setopt($ch, CURLOPT_HEADER, 0);
			@curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
			$resp = @curl_exec($ch); 
			
			$curl_resp = curl_errno($ch);

			if ($curl_resp == 0)
			{
				$val = $resp;
			}
			else if($curl_resp != 0 && $resp == "") 
			{
				$val = "";
			} 

			@curl_close($ch);
			unset($ch);		
		}
		else if(function_exists("fopen"))
		{
				$fp = @fopen($url,"r");
				if($fp)
				{		
					while(!@feof($fp))
					{
						$val .= @fgets($fp);
					}
					@fclose($fp);
				}
				else 
				{
					$val = "";
				}
		} ';
		
		if ($env != 'T') {
			$code .= '
			$tid = trim($val);
			setcookie("track_id", $tid); ?>';
		} else {
			$code .= '
			setcookie ("track_id", "", time() - 3600);
			unset($_COOKIE["track_id"]); ?>';
		}
		return $code;	
	}
	
	
	public function getCppTrakingPage( $id ) {
		$sql = "SELECT p.* , p.id as p_id, s.*, d.*, d.id as aid, n.* FROM hct_ccp_trackingpages p "
		." LEFT JOIN  hct_ccp_site s ON s.id = p.site_id "
		." LEFT JOIN  hct_ccp_ad d ON d.id = p.ad_id "
		." LEFT JOIN  hct_ccp_campaign n ON n.id = d.campaign_id "
		." WHERE p.id = $id ";
		$page = Core_Sql::getRecord($sql);
		$arrRes = $this->cppFormatArray($page);
		 
		return $arrRes;
	}

	private function getCppTrakingPages( $userId ){
		$sql = "SELECT p.*,(SELECT COUNT(*) FROM hct_affiliate_compaign as c WHERE p.id = c.page_id ) as is_compaign , p.id as p_id, s.*, d.*, d.id as aid, n.* FROM hct_ccp_trackingpages p "
			 ." LEFT JOIN  hct_ccp_site s ON s.id = p.site_id "
			 ." LEFT JOIN  hct_ccp_ad d ON d.id = p.ad_id "
			 ." LEFT JOIN  hct_ccp_campaign n ON n.id = d.campaign_id "
			 ." WHERE d.user_id = $userId ";
		$pages = Core_Sql::getAssoc($sql);
		$arrRes = array();
		foreach ($pages as $page) {
			$arrRes[] = $this->cppFormatArray($page);
		}
		return $arrRes;
	}
	
	private function cppFormatArray( $page = array() ) {
		$pageName = explode('/',$page['remote_path']);
		$len = strlen($pageName[count($pageName)-1]);
		$pagePath = substr($page['remote_path'],0,-$len);
		
		$page =	array(
		'page_id' 			=> $page['p_id'],
		'page_type' 		=> ($page['cloaked']) ? 'cloaked' : 'redirect',
		'page_name' 		=> $pageName[count($pageName)-1],
		'page_address'  	=> $page['url'],
		'user_id' 			=> Core_Users::$info['id'],
		'page_affiliate_url'=> $page['merchant_link'],
		'page_date_created' => $page['date'],
		'page_title' 		=> $page['title'], 
		'page_keywords' 	=> $page['keywords'], 
		'ftp_directory' 	=> $pagePath, 
		'is_compaign' 		=> $page['is_compaign'],
		'arrTransport'		=> array('placement_id' => $page['ftp_id'],'ftp_directory'=>$pagePath ),
		'is_cpp'			=> 1,
		'ad_env'			=> $page['ad_env'],
		'ad_id'				=> $page['ad_id'],
		'aid'				=> $page['aid']
		);
		$page['compaigns'] = Core_Sql::getAssoc("SELECT * FROM hct_affiliate_compaign WHERE page_id = {$page['page_id']}  ");
		
		if ( is_array( $page['compaigns'] ) ) {
			foreach ($page['compaigns'] as $item) {
				$page['ids'][] = $item['compaign_id'];
				$page['compaign_type'] = $item['compaign_type'];
			}
		}
		return $page;
	}
	
	public function getAffiliatePages() {
		Zend_Registry::get( 'objUser' )->getId( $user_id );
		$cppPages = $this->getCppTrakingPages($user_id);
		$pages = Core_Sql::getAssoc("SELECT *,(SELECT COUNT(*) FROM hct_affiliate_compaign as c WHERE c.page_id = page.page_id ) as is_compaign  FROM hct_affiliate_pages as page LEFT JOIN hct_ftp_details_tb as ftp ON ftp.id = page.ftp_id WHERE page.user_id = $user_id ORDER BY page.page_date_created DESC");
		$pages = array_merge($pages, $cppPages);
		return !empty($pages) ? $pages : false;
	}
	
	public function setAffiliatePage( $arrPage ) {
		$update = '';
		$path = $arrPage['arrTransport']['ftp_directory'];
		$filename = ( $arrPage['file_name'] ) ? htmlspecialchars( $arrPage['file_name']) : htmlspecialchars( $arrPage['file_name_ad']);
		//$filename = ( $arrPage['file_name'] ) ? htmlspecialchars( $arrPage['file_name']) : htmlspecialchars( $arrPage['file_name_ad']);
		if ( $arrPage['convert_page'] == 1 ) {
			$path = explode("/",$arrPage['arrTransport']['ftp_directory']);
			unset($path[count($path)-1]);
			$path = implode('/', $path);
			$path = (!empty($path)) ? $path .'/' : '/';
//			$filename = Core_Files::getBaseName($arrPage['arrTransport']['ftp_directory']);
//			$filename = explode('/',$filename);
//			$filename = $filename[count($filename)-1];
		}
		Zend_Registry::get( 'objUser' )->getId( $user_id );
		$_info=$this->_transport->getInfo();
		$_host=((!empty($_info['domain_http']))?$_info['domain_http']:$_info['domain_ftp']).str_replace( '/public_html', '', $_info['ftp_directory']);
		$_host=rtrim('http://'.$_host,'/').$path;
		$data = array(
			'page_address'			=> (empty($arrPage['arrTransport']['url'])?$_host:$arrPage['arrTransport']['url']),
			'page_name' 			=> $filename,
			'page_affiliate_url' 	=> ( $arrPage['redirect_url'] ) ? htmlspecialchars( $arrPage['redirect_url']) : htmlspecialchars( $arrPage['redirect_url2']), 
			'page_title'			=> ( $arrPage['page_title']) ? htmlspecialchars($arrPage['page_title']) : '',
			'page_keywords'			=> ( $arrPage['meta_tag']) ? htmlspecialchars($arrPage['meta_tag']) : '',
			'ftp_id'				=> ( $arrPage['arrTransport']['placement_id'] ) ? $arrPage['arrTransport']['placement_id'] : 0,
			'ftp_directory'			=> $path,
			'user_id'				=> $user_id,
			'page_type'				=> (isset($arrPage['cloack']) && $arrPage['cloack'] == 'redirect' ) ? 'redirect' : 'cloaked' 
		);
		if ( $arrPage['page_id'] ) {
			$data['page_id'] = $arrPage['page_id'];
			$update = 'page_id';
			$cppUpdate = 'id';
		}
		if ( $arrPage['cpp'] == 1) {
			$cppData = array(
				'id'	=> $arrPage['page_id'],
				'cloaked' => $arrPage['headlines_spot1'],
				'title'	=> $arrPage['page_title'],
				'keywords'	=> $arrPage['meta_tag']
			);
			Core_Sql::setInsertUpdate('hct_ccp_ad', array('id' => $arrPage['aid'], 'merchant_link' => $data['page_affiliate_url']),  'id');
			if ( !$id = Core_Sql::setInsertUpdate('hct_ccp_trackingpages', $cppData, $cppUpdate) ) {
				return false;
			}
		}else{
			if ( !$id = Core_Sql::setInsertUpdate('hct_affiliate_pages', $data, $update) ) {
				return false;
			}
		}
		Core_Sql::setExec("DELETE FROM hct_affiliate_compaign WHERE page_id = {$id}");
		if ( $arrPage['headlines_spot1'] && $arrPage['dams_add'] && !empty($arrPage['arrOpt']['dams']['ids']) ) {
			$compaigns = array();
			foreach ($arrPage['arrOpt']['dams']['ids'] as $compaign_id ) {
				$compaigns[] = array(
					'page_id' 		=> $id,
					'compaign_id' 	=> $compaign_id,
					'compaign_type'	=> ( $arrPage['headlines_spot1'] ) ? $arrPage['headlines_spot1'] : '',
					'mod_type'		=> ($arrPage['cpp'] == 1) ? 'cpp':'affilite'
				);
			}
			if ( !Core_Sql::setMassInsert('hct_affiliate_compaign', $compaigns) ) {
				return false;
			}
		}
		return true;
	}
	
	public function getAffiliatePageById( $id ) {
		Zend_Registry::get( 'objUser' )->getId( $user_id );
		$page = Core_Sql::getRecord("SELECT * FROM hct_affiliate_pages as p  WHERE p.page_id = {$id} AND p.user_id = {$user_id} ");
		if ( !$page ) {
			return false;
		}
		$page['compaigns'] = Core_Sql::getAssoc("SELECT * FROM hct_affiliate_compaign WHERE page_id = {$page['page_id']}  ");
		if ( is_array( $page['compaigns'] ) ) {
			foreach ($page['compaigns'] as $item) {
				$page['ids'][] = $item['compaign_id'];
				$page['compaign_type'] = $item['compaign_type'];
			}
		}
		$page['arrTransport']['placement_id'] = $page['ftp_id'];
		return $page;
	}
	
	public function deleteAffiliatePage( $id, $type ) {
		Zend_Registry::get( 'objUser' )->getId( $user_id );
		if ($type == 'cpp') {
			$page = $this->getCppTrakingPage($id);
		} else {
			$page = Core_Sql::getRecord("SELECT * FROM hct_affiliate_pages as p  WHERE p.page_id = {$id} AND p.user_id = {$user_id} ");
		}
		if ($type == 'cpp') {
			Core_Sql::setExec("DELETE FROM hct_ccp_trackingpages WHERE id = {$id} ");
		} else {
			Core_Sql::setExec("DELETE FROM hct_affiliate_pages WHERE page_id = {$id} ");
		}
		Core_Sql::setExec("DELETE FROM hct_affiliate_compaign WHERE page_id = {$id} ");
		$page['arrTransport']['placement_id'] = $page['ftp_id'];
		$this->init( $page );
		$this->_transport->removeFile( $page['page_name'] );
		return true;
	}
	
	
}

?>