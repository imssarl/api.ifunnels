<?php
class Core_Cpanel extends Core_Services {

	private $_uri;
	private $_uriSSL;
	private $_skin='x';
	private $_port='2082';
	private $_portSSL='2083';
	private $_domain='';
	private $_result=array();
	private $_access=array( 'user'=>'', 'passwd'=>'', 'host'=>'', 'theme'=>'x' );

	public function __construct( $_arrParams=array() ) {
		$this->_uri=Zend_Uri::factory( 'http' );
		$this->_uriSSL=Zend_Uri::factory( 'https' );
		if ( !empty( $_arrParams ) ) {
			$this->setAccess( $_arrParams );
		}
	}

	// http://<user>:<passwd>@<host>:<port>
	public function setAccess( $_arrParams=array() ) {
		$_arrParams=$_arrParams+$this->_access;
		try {
			$this->_uri->setUsername( urlencode( $_arrParams['user'] ) );
			$this->_uri->setPassword( urlencode( $_arrParams['passwd'] ) );
			$this->_uri->setHost( urlencode( $_arrParams['host'] ) );
			$this->_uri->setPort( $this->_port );
			$this->_uriSSL->setUsername( urlencode( $_arrParams['user'] ) );
			$this->_uriSSL->setPassword( urlencode( $_arrParams['passwd'] ) );
			$this->_uriSSL->setHost( urlencode( $_arrParams['host'] ) );
			$this->_uriSSL->setPort( $this->_portSSL );
		} catch ( Zend_Uri_Exception $e) {
			return false;
		}
		$this->setSkinName( $_arrParams['theme'] );
		return true;
	}

	public function setSkinName( $_strName='x' ) {
		$this->_skin=$_strName;
		return $this;
	}

	public function setDomain( $_str='' ) {
		if ( empty( $_str ) ) {
			return false;
		}
		$this->_domain=$_str;
		return true;
	}

	public function getResult() {
		return $this->_result;
	}

	public function createAddonDomain( &$arrErr, $_arrParams=array() ) {
		$_arrParams=Core_A::array_check( $_arrParams, $this->post_filter );
		if ( !$this->error_check( $_arrParams, $arrErr, $_arrParams, array(
			'domain'=>empty( $_arrParams['domain'] ),
			'pass'=>empty( $_arrParams['pass'] ),
			'user'=>empty( $_arrParams['user'] ),
		) ) ) {
			return false;
		}
		$this->_uri->setPath( '/frontend/'.$this->_skin.'/addon/doadddomain.html' );
		$this->_uriSSL->setPath( '/frontend/'.$this->_skin.'/addon/doadddomain.html' );
		$this->_uri->setQuery( array(
			'domain'=>$_arrParams['domain'], 
			'pass'=>$_arrParams['pass'], 
			'user'=>$_arrParams['user'], 
		) );
		$this->_uriSSL->setQuery( array(
			'domain'=>$_arrParams['domain'],
			'pass'=>$_arrParams['pass'],
			'user'=>$_arrParams['user'],
		) );
		if ( !$this->getResponce( $_strTmp ) ) {
//			$arrErr['connect']=true;
			$arrErr['notadded']=true;
			return false;
		}
//		if( stristr( $_strTmp, 'has been added' )===false||stristr( $_strTmp, 'was successfully parked' )===false ) {
//			$arrErr['notadded']=true;
//			return false;
//		}
		$this->_result=array(
			'domain'=>$_arrParams['domain'],
			'user'=>$_arrParams['user'].'@'.$this->_uri->getHost(),
			'pass'=>$_arrParams['pass'],
		);
		return true;
	}

	public function createSubDomains( $_mix='' ) {
		$_mix=Core_A::array_check( $_mix, $this->post_filter );
		if ( empty( $_mix )||empty( $this->_domain ) ) {
			return false;
		}
		if ( !is_array( $_mix ) ) {
			$_mix=array( $_mix );
		}
		foreach( $_mix as $v ) {
			$this->_uri->setPath( '/frontend/'.$this->_skin.'/subdomain/doadddomain.html' );
			$this->_uriSSL->setPath( '/frontend/'.$this->_skin.'/subdomain/doadddomain.html' );
			$this->_uri->setQuery( array(
				'domain'=>$v,
				'rootdomain'=>$this->_domain,
			) );
			$this->_uriSSL->setQuery( array(
				'domain'=>$v,
				'rootdomain'=>$this->_domain,
			) );
			$this->_result[$v]=$this->getResponce( $_strTmp );
		}
		
		return true;
	}

	public function createDb( $_arrParams=array() ) {
		if ( empty( $_arrParams['name'] ) ) {
			return false;
		}
		// create db
		$this->_uri->setPath( '/frontend/'.$this->_skin.'/sql/add'.($this->_skin=='x3'? '':'d').'b.html' );
		$this->_uriSSL->setPath( '/frontend/'.$this->_skin.'/sql/add'.($this->_skin=='x3'? '':'d').'b.html' );
		$this->_uri->setQuery( array(
			'db'=>$_arrParams['name'] 
		) );
		$this->_uriSSL->setQuery( array(
			'db'=>$_arrParams['name']
		) );
		if ( !$this->getResponce( $_strTmp ) ) {
			return false;
		}
		$this->_result['db']=$this->_uri->getUsername().'_'.$_arrParams['name'];
		if ( !empty( $_arrParams['user'] )&&!empty( $_arrParams['passwd'] ) ) {
			// add user
			$this->_uri->setPath( '/frontend/'.$this->_skin.'/sql/adduser.html' );
			$this->_uriSSL->setPath( '/frontend/'.$this->_skin.'/sql/adduser.html' );
			$this->_uri->setQuery( array(
				'user'=>$_arrParams['user'], 
				'pass'=>$_arrParams['passwd'] 
			) );
			$this->_uriSSL->setQuery( array(
				'user'=>$_arrParams['user'],
				'pass'=>$_arrParams['passwd']
			) );
			if ( !$this->getResponce( $_strTmp ) ) {
				return false;
			}
			$this->_result['user']=$this->_uri->getUsername().'_'.$_arrParams['user'];
			$this->_result['pass']=$_arrParams['passwd'];
			// add user to db
			$this->_uri->setPath( '/frontend/'.$this->_skin.'/sql/addusertodb.html' );
			$this->_uriSSL->setPath( '/frontend/'.$this->_skin.'/sql/addusertodb.html' );
			if ( $this->_skin=='x3' ) {
				$this->_uri->setQuery( array( 
					'user'=>$this->_uri->getUsername().'_'.$_arrParams['user'], 
					'db'=>$this->_uri->getUsername().'_'.$_arrParams['name'],
					'update'=>'',
					'ALL'=>'ALL',
				) );
				$this->_uriSSL->setQuery( array(
					'user'=>$this->_uri->getUsername().'_'.$_arrParams['user'],
					'db'=>$this->_uri->getUsername().'_'.$_arrParams['name'],
					'update'=>'',
					'ALL'=>'ALL',
				) );
			} else {
				$this->_uri->setQuery( array( 
					'user'=>$this->_uri->getUsername().'_'.$_arrParams['user'], 
					'db'=>$this->_uri->getUsername().'_'.$_arrParams['name'],
					'ALL'=>'ALL',
				) );
				$this->_uriSSL->setQuery( array(
					'user'=>$this->_uri->getUsername().'_'.$_arrParams['user'],
					'db'=>$this->_uri->getUsername().'_'.$_arrParams['name'],
					'ALL'=>'ALL',
				) );
			}
			if ( !$this->getResponce( $_strTmp ) ) {
				return false;
			}
			$this->_result['bind']=true;
		}
		return true;
	}

	/**
	 * Не используется. Не работает Curl с удаленным серваком для ссылок вида  http://<user>:<passwd>@<host>:<port>
	 *
	 */
	private function getResponceCurl( &$strRes ) {
		$curl=Core_Curl::getInstance();
		if ( !$curl->getContent( $this->_uri->getUri() ) ) {
			return false;
		}
		$strRes=$curl->getResponce();
		return true;
		//$strRes=@file_get_contents( $this->_uri->getUri() );
		//return $strRes!==false;
	}
	
	private function getResponce( &$strRes ) {
		$_userTempDir='Core_Cpanel@getResponce';
		if ( !Zend_Registry::get( 'objUser' )->prepareTmpDir( $_userTempDir ) ) {
			return false;
		}
		$file = '<?php 
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,  "'. $this->_uri->getUri() .'" );
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1) Gecko/20061010 Firefox/2.0" );
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			"Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
			"Cache-Control: max-age=0",
			"Connection: keep-alive",
			"Keep-Alive: 300",
			"Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7",
			"Accept-Language: en-us,en;q=0.5",
		));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		$responce = curl_exec($ch);
		if( stripos($responce,"https://")&&stripos($responce,"refresh") ){
			curl_setopt($ch, CURLOPT_URL,  "'.$this->_uriSSL->getUri().'" );
			$responce = curl_exec($ch);
		}
		curl_close ($ch);';
		if ($this->_skin == 'x3') {
		$file .='if( stripos( $responce , \'class="errors"\') ) {
			echo "error";
		} else {
			echo "added";
		}?>';
		} else {
		$file .='if( stripos( $responce , "error") ) {
			echo "error";
		} else {
			echo "added";
		}?>';
		}
		if ( !Core_Files::setContent( $file, $_userTempDir.'cpanel_creat.php' ) ) {
			return false;
		}
		$_ftp=new Core_Media_Ftp();
		if ( !$_ftp
			->setChmod( '0644' )
			->setHost( urldecode( $this->_uri->getHost() ) )
			->setUser( urldecode( $this->_uri->getUsername() ) )
			->setPassw( urldecode( $this->_uri->getPassword() ) )
			->setRoot( 'public_html' )
			->makeConnectToRootDir() ) {
			return false;
		}
		if ( $_ftp->fileUpload( 'cpanel_creat.php',$_userTempDir.'cpanel_creat.php' ) !== true) {
			return false;
		}
		$curl=Core_Curl::getInstance();
		if ( !$curl->getContent( 'http://'.$this->_uri->getHost() . '/cpanel_creat.php' ) ) {
			return false;
		}
		$strRes=$curl->getResponce();
		if ( stripos( $strRes , "error")!==false ) {
			return false;
		}
		return true;
	}
}
?>