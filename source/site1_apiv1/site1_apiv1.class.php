<?php
class site1_apiv1 extends Core_Module {

	public function set_cfg() {
		$this->inst_script=array(
			'module'=>array( 'title'=>'Ifunnels API V1', ),
			'actions'=>array(

				array( 'action'=>'authorize', 'title'=>'Authorize', 'flg_tpl'=>3, 'flg_tree'=>2 ),
				array( 'action'=>'token', 'title'=>'Token', 'flg_tpl'=>3, 'flg_tree'=>2 ),
				
				array( 'action'=>'resource', 'title'=>'Resource', 'flg_tpl'=>3, 'flg_tree'=>2 ),
				
				array( 'action'=>'contacts', 'title'=>'Contacts', 'flg_tpl'=>3, 'flg_tree'=>2 ),
				array( 'action'=>'emailfunnels', 'title'=>'Email funnels', 'flg_tpl'=>3, 'flg_tree'=>2 ),
				array( 'action'=>'leadchannels', 'title'=>'Lead Channels', 'flg_tpl'=>3, 'flg_tree'=>2 ),
				array( 'action'=>'memberships', 'title'=>'Memberships', 'flg_tpl'=>3, 'flg_tree'=>2 ),
				array( 'action'=>'sales', 'title'=>'Sales', 'flg_tpl'=>3, 'flg_tree'=>2 ),
				

			),
		);
	}
	
	private $storage;
	private $server;
	
	public function autoloading() {
		// Autoloading (composer is preferred, but for this example let's just do this)
		require_once('./library/OAuth2/Autoloader.php');
		OAuth2\Autoloader::register();
		// $dsn is the Data Source Name for your database, for exmaple "mysql:dbname=my_oauth2_db;host=localhost"
		$this->storage = new OAuth2\Storage\Pdo(array(
			'dsn' => 'mysql:host='.Zend_Registry::get( 'config' )->database->master->host.';dbname='.Zend_Registry::get( 'config' )->database->master->dbname.'',
			'username' => Zend_Registry::get( 'config' )->database->master->username ,
			'password' => Zend_Registry::get( 'config' )->database->master->password ));
		// Pass a storage object or array of storage objects to the OAuth2 server class
		$this->server = new OAuth2\Server($this->storage);
	}
	
	
	/*
вставьте следующий URL в ваш браузер

текст
http://localhost/authorize/?response_type=code&client_id=testclient&state=xyz
Вам будет предложено ввести форму авторизации и получить код авторизации после нажатия «да»

Код авторизации теперь можно использовать для получения токена доступа от ранее созданной token конечной точки. Просто вызовите эту конечную точку, используя возвращенный код авторизации:

текст
curl -u testclient:testpass http://localhost/token/ -d 'grant_type=authorization_code&code=YOUR_CODE'
И так же, как и раньше, вы получите токен доступа:

json
{"access_token":"6f05ad622a3d32a5a81aee5d73a5826adb8cbf63","expires_in":3600,"token_type":"bearer","scope":null}
Примечание. Обязательно сделайте это быстро, поскольку срок действия кодов авторизации истекает через 30 секунд!
	*/
	public function authorize() {
		$this->autoloading();
		$request = OAuth2\Request::createFromGlobals();
		$response = new OAuth2\Response();
		// validate the authorize request
		if (!$this->server->validateAuthorizeRequest($request, $response)) {
			$response->send();
			die;
		}
		// display an authorization form
		if (empty($_POST)) {
			exit('<form method="post"><label>Do You Authorize ThisClient?</label><br /><input type="submit" name="authorized" value="yes"><input type="submit" name="authorized" value="no"></form>');
		}
		// print the authorization code if the user has authorized your client
		$is_authorized = ($_POST['authorized'] === 'yes');
		$this->server->handleAuthorizeRequest($request, $response, $is_authorized);
		if ($is_authorized) {
		  // this is only here so that you get to see your code in the cURL request. Otherwise, we'd redirect back to the client
		  $code = substr($response->getHttpHeader('Location'), strpos($response->getHttpHeader('Location'), 'code=')+5, 40);
		  exit("AUTHORIZATION_CODE: $code");
		}
		$response->send();
	}

	/*
Запустите следующий SQL для создания клиента OAuth:

sql
INSERT INTO oauth_clients (client_id, client_secret, redirect_uri) VALUES ("testclient", "testpass", "http://fake/");
Теперь запустите следующее из командной строки:

текст
curl -u testclient:testpass http://localhost/token/ -d 'grant_type=client_credentials'
Примечание: http: //localhost/token/ предполагает, что у вас есть файл token на локальном компьютере, и вы настроили веб-хост «localhost», чтобы он указывал на него. Это может варьироваться для вашего приложения.

Если все работает, вы должны получить такой ответ:

json
{"access_token":"03807cb390319329bdf6c777d4dfae9c0d3b3c35","expires_in":3600,"token_type":"bearer","scope":null}

	*/
	public function token() {
		$this->autoloading();
		switch( $_REQUEST['grant_type'] ) {
			case 'authorization_code':
				// create the grant type
				$grantType = new OAuth2\GrantType\AuthorizationCode($this->storage);
				// add the grant type to your OAuth server
				$this->server->addGrantType($grantType);
			break;
			
			
			case 'client_credentials': // grant_type=client_credentials&client_id=TestClient&client_secret=TestSecret
			//	if( !isset( $_REQUEST['client_id'] ) || !isset( $_REQUEST['client_secret'] ) ){
			//		echo '{"error":"invalid_client","error_description":"The client credentials are invalid"}';
			//		exit;
			//	}
				// create test clients in memory
			//	$clients = array($_REQUEST['client_id'] => array('client_secret' => $_REQUEST['client_secret']));
				// create a storage object
			//	$this->storage = new OAuth2\Storage\Memory(array('client_credentials' => $clients));
				// create the grant type
				/*
					allow_credentials_in_request_body :
					false
					# using HTTP Basic Authentication
					$ curl -u TestClient:TestSecret https://api.mysite.com/token -d 'grant_type=client_credentials'
					true
					# using POST Body
					$ curl https://api.mysite.com/token -d 'grant_type=client_credentials&client_id=TestClient&client_secret=TestSecret'
				*/
				$grantType = new OAuth2\GrantType\ClientCredentials($this->storage, array(
					'allow_credentials_in_request_body' => false //defaukt true
				));
				// add the grant type to your OAuth server
				$this->server->addGrantType($grantType);
			break;
			
			
			case 'password': // grant_type=password&username=testclient&password=testpass
				// create some users in memory
				if( !isset( $_REQUEST['username'] ) || !isset( $_REQUEST['password'] ) ){
					echo '{"error":"invalid_client","error_description":"The client credentials are invalid"}';
					exit;
				}
				$users=array(
					$_REQUEST['username'] => array(
						'password'=>$_REQUEST['password']
					)
				);
				if( !isset( $_REQUEST['first_name'] ) ){
					$users[$_REQUEST['username']]['first_name']=$_REQUEST['first_name'];
				}
				if( !isset( $_REQUEST['last_name'] ) ){
					$users[$_REQUEST['username']]['last_name']=$_REQUEST['last_name'];
				}
				// create a storage object
				$this->storage = new OAuth2\Storage\Memory(array('user_credentials' => $users));
				// create the grant type
				$grantType = new OAuth2\GrantType\UserCredentials($this->storage);
				// add the grant type to your OAuth server
				$this->server->addGrantType($grantType);
			break;
			
			
			case 'refresh_token':
				// create the grant type
				$grantType = new OAuth2\GrantType\RefreshToken($this->storage, array(
					'always_issue_new_refresh_token' => true
				));
				// add the grant type to your OAuth server
				$this->server->addGrantType($grantType);
			break;
			default:
			break;
		}
	//	p();
		
		
		// Handle a request for an OAuth2.0 Access Token and send the response to the client
		$this->server->handleTokenRequest( OAuth2\Request::createFromGlobals() )->send();
		exit;
	}
	/*
Пример получения жанных при активированном доступе
текст
curl http://localhost/resource -d 'access_token=YOUR_TOKEN'
Примечание. Вместо YOUR_TOKEN используйте значение, возвращенное в «access_token» из предыдущего шага.

Если все идет хорошо, вы должны получить такой ответ:

json
{"success":true,"message":"You accessed my APIs!"}
	*/
	public function resource() {
		$this->getRest();
		$this->autoloading();
		// Handle a request to a resource and authenticate the access token
		$_requestData=OAuth2\Request::createFromGlobals();
		if ( !$this->server->verifyResourceRequest( $_requestData ) ){
			$this->server->getResponse()->send();
			die;
		}
		$_requestData->url_parameters=explode( '/', trim( $_requestData->server['REQUEST_URI'], '/' ) );
		$_dataId=@$_requestData->url_parameters[2];
		p( $_dataId );
		echo json_encode(array('success' => true, 'message' => 'You accessed my APIs!'));
		exit;
	}
	
	private $method='GET';
	public function getRest() {
        //Определение метода запроса
        $this->method = $_SERVER['REQUEST_METHOD'];
        if ($this->method == 'POST' && array_key_exists('HTTP_X_HTTP_METHOD', $_SERVER)) {
            if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'DELETE') {
                $this->method = 'DELETE';
            } else if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'PUT') {
                $this->method = 'PUT';
            } else {
                $this->method = 'GET';
            }
        }
	}
	
	private function _json_encode($val){
		if (is_string($val)) return '"'.addslashes($val).'"';
		if (is_numeric($val)) return $val;
		if ($val === null) return 'null';
		if ($val === true) return 'true';
		if ($val === false) return 'false';
		$assoc = false;
		$i = 0;
		foreach ($val as $k=>$v){
			if ($k !== $i++){
				$assoc = true;
				break;
			}
		}
		$res = array();
		foreach ($val as $k=>$v){
			$v = $this->_json_encode($v);
			if ($assoc){
				$k = '"'.addslashes($k).'"';
				$v = $k.':'.$v;
			}
			$res[] = $v;
		}
		$res = implode(',', $res);
		return ($assoc)? '{'.$res.'}' : '['.$res.']';
	}
	
	/*
	List contacts
	Create new contact
	*/
	public function contacts() {
		$this->getRest();
		$this->autoloading();
		// Handle a request to a resource and authenticate the access token
		$_requestData=OAuth2\Request::createFromGlobals();
		if ( !$this->server->verifyResourceRequest( $_requestData ) ){
			$this->server->getResponse()->send();
			exit();
		}
		$_requestData->url_parameters=explode( '/', trim( $_requestData->server['REQUEST_URI'], '/' ) );
		$_dataId=@$_requestData->url_parameters[2];
		if( $_dataId[0]!='?' ){
			$_requestData->query['id']=$_dataId;
		}
		$_outJson=array();
		$_userId=Core_Payment_Encode::decode( $this->server->getToken()['client_id'] );
		try{
			//========
			switch( $this->method ){
				
				case 'POST': // создать
					if( !isset( $_requestData->request['email'] ) ){
						$_outJson=array(
							'error'=>'invalid_data',
							'error_description'=>'Can\'t add contact email'
						);
					}else{
						Core_Sql::setConnectToServer( 'lpb.tracker' );
						$_addTags=explode( ',', @$_requestData->request['tags'] );
						foreach( $_addTags as &$_tagValue ){
							$_tagValue=trim( $_tagValue );
						}
						$_addTags=array_unique( $_addTags );
						$_obj=new Project_Efunnel_Subscribers( $_userId );
						$_obj->setEntered( array(
							'email'=>@$_requestData->request['email'],
							'name'=>@$_requestData->request['name'],
						))->set();
						$_obj->getEntered( $_outJson );
						unset( $_outJson['settings'] );
						Core_Sql::renewalConnectFromCashe();
					}
				break;

				case 'DELETE': // удалить
				
				break;

				case 'PATCH': // обновляет те поля, которые были переданы
					if( !isset( $_requestData->request['id'] ) || !isset( $_requestData->request['email'] ) ){
						$_outJson=array(
							'error'=>'invalid_data',
							'error_description'=>'Can\'t patch contact email'
						);
					}else{
						Core_Sql::setConnectToServer( 'lpb.tracker' );
						$_addTags=explode( ',', @$_requestData->request['tags'] );
						foreach( $_addTags as &$_tagValue ){
							$_tagValue=trim( $_tagValue );
						}
						$_addTags=array_unique( $_addTags );
						$_obj=new Project_Efunnel_Subscribers( $_userId );
						$_obj->setEntered( array(
							'id'=>@$_requestData->request['id'],
							'email'=>@$_requestData->request['email'],
							'name'=>@$_requestData->request['name'],
						))->set();
						$_obj->getEntered( $_outJson );
						unset( $_outJson['settings'] );
						Core_Sql::renewalConnectFromCashe();
					}
				break;

				case 'PUT': // просто заменяет одну данные на другие
					if( !isset( $_requestData->request['id'] ) || !isset( $_requestData->request['email'] ) ){
						$_outJson=array(
							'error'=>'invalid_data',
							'error_description'=>'Can\'t update contact email'
						);
					}else{
						Core_Sql::setConnectToServer( 'lpb.tracker' );
						$_addTags=explode( ',', @$_requestData->request['tags'] );
						foreach( $_addTags as &$_tagValue ){
							$_tagValue=trim( $_tagValue );
						}
						$_addTags=array_unique( $_addTags );
						$_obj=new Project_Efunnel_Subscribers( $_userId );
						$_obj->setEntered( array(
							'id'=>@$_requestData->request['id'],
							'email'=>@$_requestData->request['email'],
							'name'=>@$_requestData->request['name'],
						))->set();
						$_obj->getEntered( $_outJson );
						unset( $_outJson['settings'] );
						Core_Sql::renewalConnectFromCashe();
					}
				break;
				
				default:
				case 'GET': // получить список
					Core_Sql::setConnectToServer( 'lpb.tracker' );
					$_model=new Project_Efunnel_Subscribers( $_userId );
					if( isset( $_requestData->query['id'] ) && !empty( $_requestData->query['id'] ) ){
						$_model->withids( $_requestData->query['id'] )->onlyOne()->getList( $_outJson['data'] );
						unset( $_outJson['data']['efunnel_events'] );
						unset( $_outJson['data']['tags'] );
						unset( $_outJson['data']['settings'] );
						unset( $_outJson['data']['status_data'] );
						unset( $_outJson['data']['flg_global_unsubscribe'] );
					}else{
						if( !empty( $_requestData->query['search'] ) ){
							$_model->withTags( $_requestData->query['search'] );
						}
						if( !empty( $_requestData->query['email'] ) ){
							$_model->withEmail( $_requestData->query['email'] );
						}
						if( !empty( $_requestData->query['arrFilter']['email_funnels'] ) ){
							if( $_requestData->query['arrFilter']['email_funnels'] == 'ns' ){
								$_funnel=new Project_Efunnel();
								$_funnel->onlyOwner()->onlyIds()->getList( $_EFids );
								$_model->withoutEfunnelIs($_EFids);
							}else{
								$_model->withEfunnelIds( $_requestData->query['arrFilter']['email_funnels'] );
							}
						}
						if( !empty( $_requestData->query['arrFilter']['lead_channels'] ) ){
							$_model->withLead( $_requestData->query['arrFilter']['lead_channels'] );
						}	
						if( !empty( $_requestData->query['arrFilter']['status'] ) && $_requestData->query['arrFilter']['status'] != 'unsubscribe' ){
							$_model->withStatusMessage( $_requestData->query['arrFilter']['status'] );
						}
						if( !empty( $_requestData->query['arrFilter']['status'] ) && $_requestData->query['arrFilter']['status'] == 'unsubscribe' ){
							$_model->onlyFlgGlobalUnsubscribe();
						}else{
							$_model->withoutFlgGlobalUnsubscribe();
						}
						if( !empty( $_requestData->query['arrFilter']['tags'] ) ){
							$_model->withTags( $_requestData->query['arrFilter']['tags'] );
						}
						if( !empty( $_requestData->query['arrFilter']['validation'] ) ){
							$_model->withValidation( $_requestData->query['arrFilter']['validation'] );
						}
						if( !empty( $_requestData->query['arrFilter']['time'] ) ){
							$_model->withTime( $_requestData->query['arrFilter']['time'], $_requestData->query['arrFilter']['time_start'], $_requestData->query['arrFilter']['time_end'] );
						}
						$_recoonpage=$_requestData->query['page']['size'];
						if( !isset( $_requestData->query['page']['size'] ) || $_requestData->query['page']['size'] > 100 ){
							$_recoonpage=100;
						}
						$_model
							->withStatus()
							->withoutTags()
							->withOrder( @$_requestData->query['order'] )
							->withPaging( array(
								'url'=>array( 'page'=> @$_requestData->query['page']['number'] ),
								'page'=>@$_requestData->query['page']['number'], 
								'reconpage'=>$_recoonpage,
							) )
							->getList( $_outJson['data'] )
							->getPaging( $_arrPg );
						foreach( $_outJson['data'] as &$_s8rData ){
							unset( $_s8rData['efunnel_events'] );
							unset( $_s8rData['tags'] );
							unset( $_s8rData['settings'] );
							unset( $_s8rData['status_data'] );
							unset( $_s8rData['flg_global_unsubscribe'] );
						}
						$_outJson['meta']['count']=$_arrPg['recall'];
						$_outJson['meta']['page']['number']=$_arrPg['curpage'];
						$_outJson['meta']['page']['size']=$_recoonpage;
					}
					Core_Sql::renewalConnectFromCashe();
				break;
			}
			//========

		}catch(Exception $e){
			$_outJson=array(
				'error'=>'invalid_data',
				'error_description'=>$e->getMessage()
			);
			$this->_exception=$e;
			$this->handling( $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine() );
			Core_Sql::renewalConnectFromCashe();
		}
		echo $this->_json_encode( $_outJson );
		exit;
	}
	
	/*
	List email funnels
	Add someone to an email funnel
	*/
	public function emailfunnels(){
		$this->getRest();
		$this->autoloading();
		// Handle a request to a resource and authenticate the access token
		$_requestData=OAuth2\Request::createFromGlobals();
		if ( !$this->server->verifyResourceRequest( $_requestData ) ){
			$this->server->getResponse()->send();
			die;
		}
		$_requestData->url_parameters=explode( '/', trim( $_requestData->server['REQUEST_URI'], '/' ) );
		$_dataId=@$_requestData->url_parameters[2];
		if( $_dataId[0]!='?' ){
			$_requestData->query['id']=$_dataId;
		}
		$_userId=Core_Payment_Encode::decode( $this->server->getToken()['client_id'] );
		if( isset( $_requestData->request['contact_id'] ) && !empty( $_requestData->request['contact_id'] ) ){
			try{
				//========
				Core_Sql::setConnectToServer( 'lpb.tracker' );
				$_model=new Project_Efunnel_Subscribers( $_userId );
				$_model->withIds( $_requestData->request['contact_id'] )->getList( $_arrContacts );
				//========
			}catch(Exception $e){
				$_outJson=array(
					'error'=>'invalid_data',
					'error_description'=>$e->getMessage()
				);
				$this->_exception=$e;
				$this->handling( $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine() );
				Core_Sql::renewalConnectFromCashe();
			}
		}
		try {
			Core_Sql::setConnectToServer( 'cnm.cnmbeta.info' );
			Core_Users::getInstance()->setById( $_userId );
			//========
			switch( $this->method ) {

				case 'POST': // создать
					if( !isset( $_requestData->query['id'] ) || !isset( $_requestData->request['contact_id'] ) || empty( $_requestData->request['contact_id'] ) || empty( $_arrContacts ) ){
						$_outJson=array(
							'error'=>'invalid_data',
							'error_description'=>'Can\'t patch contact emails'
						);
					}else{
						$_model=new Project_Efunnel();
						$_model->withids( $_requestData->query['id'] )->onlyOne()->getList( $_arrEfData );
						$_arrEmails=array();
						foreach( $_arrContacts as $_contactData ){
							if( $_SERVER['SERVER_NAME'] != 'api.ifunnels.local' ){
								Core_Curl::async( 'https://fasttrk.net/email-funnels/getcode/', array(
									'code'=>Core_Payment_Encode::encode( array( 'id' => $_arrEfData['id'], 'user_id'=>$_arrEfData['user_id'] ) ),
									'email'=>$_contactData['email'],
									'name'=>$_contactData['name']
								), 'POST' );
							}
							$_arrEmails[]=$_contactData['email'];
						}
						$_outJson=array(
							'success' => true,
							'message' => 'You add '.count($_arrEmails).' contact'.(count($_arrEmails)>1?'s':'').' to `'.$_arrEfData['title'].'` Email Funnel campaign',
							'data'=>$_arrEmails
						);
					}
				break;

				case 'DELETE': // удалить
				
				break;

				case 'PATCH': // обновляет те поля, которые были переданы
				
				break;

				case 'PUT': // просто заменяет одну данные на другие
				
				break;
				
				default:
				case 'GET': // получить список
				$_model=new Project_Efunnel();
				if( isset( $_requestData->query['id'] ) && !empty( $_requestData->query['id'] ) ){
					$_model->withUserId( array( $_userId ) )->withids( $_requestData->query['id'] )->onlyOne()->getList( $_outJson['data'] );
					unset( $_outJson['data']['user_id'] );
					unset( $_outJson['data']['log_text'] );
					unset( $_outJson['data']['smtp_id'] );
					unset( $_outJson['data']['flg_template'] );
					unset( $_outJson['data']['flg_pause'] );
					unset( $_outJson['data']['type'] );
					foreach( $_outJson['data']['message'] as &$_efMessage ){
						unset( $_efMessage['body_html'] );
						unset( $_efMessage['resend'] );
						unset( $_efMessage['efunnel_id'] );
					}
				}else{
					$_recoonpage=$_requestData->query['page']['size'];
					if( !isset( $_requestData->query['page']['size'] ) || $_requestData->query['page']['size'] > 100 ){
						$_recoonpage=10;
					}
					$_model->withPaging(array(
						'url'=>array( 'page'=> @$_requestData->query['page']['number'] ),
						'page'=>@$_requestData->query['page']['number'], 
						'reconpage'=>$_recoonpage,
					))
					->withUserId( array( $_userId ) )
					->withOrder( @$_requestData->query['order'] )
					->getList( $_outJson['data'] )
					->getPaging( $_arrPg );
					foreach( $_outJson['data'] as &$_efData ){
					//	unset( $_s8rData['flg_global_unsubscribe'] );
						unset( $_efData['user_id'] );
						unset( $_efData['options'] );
						unset( $_efData['log_text'] );
						unset( $_efData['smtp_id'] );
						unset( $_efData['flg_template'] );
						unset( $_efData['flg_pause'] );
						unset( $_efData['type'] );
						foreach( $_efData['message'] as &$_efMessage ){
							unset( $_efMessage['body_html'] );
							unset( $_efMessage['body_plain_text'] );
							unset( $_efMessage['resend'] );
							unset( $_efMessage['efunnel_id'] );
						}
					}
					$_outJson['meta']['count']=$_arrPg['recall'];
					$_outJson['meta']['page']['number']=$_arrPg['curpage'];
					$_outJson['meta']['page']['size']=$_recoonpage;
				}

			
				break;
			}
			//========
			Core_Users::getInstance()->setZero();
			Core_Sql::renewalConnectFromCashe();
		}catch(Exception $e){
			$_outJson=array(
				'error'=>'invalid_data',
				'error_description'=>$e->getMessage()
			);
			$this->_exception=$e;
			$this->handling( $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine() );
			Core_Sql::renewalConnectFromCashe();
		}
		echo $this->_json_encode( $_outJson );
		exit;
	}
	
	/*
	List lead channels
	Add someone to a lead channel
	*/
	public function leadchannels() {
		$this->getRest();
		$this->autoloading();
		// Handle a request to a resource and authenticate the access token
		$_requestData=OAuth2\Request::createFromGlobals();
		if ( !$this->server->verifyResourceRequest( $_requestData ) ){
			$this->server->getResponse()->send();
			die;
		}
		$_requestData->url_parameters=explode( '/', trim( $_requestData->server['REQUEST_URI'], '/' ) );
		$_dataId=@$_requestData->url_parameters[2];
		if( $_dataId[0]!='?' ){
			$_requestData->query['id']=$_dataId;
		}
		$_userId=Core_Payment_Encode::decode( $this->server->getToken()['client_id'] );
		if( isset( $_requestData->request['contact_id'] ) && !empty( $_requestData->request['contact_id'] ) ){
			try{
				//========
				Core_Sql::setConnectToServer( 'lpb.tracker' );
				$_model=new Project_Efunnel_Subscribers( $_userId );
				$_model->withIds( $_requestData->request['contact_id'] )->getList( $_arrContacts );
				//========
			}catch(Exception $e){
				$_outJson=array(
					'error'=>'invalid_data',
					'error_description'=>$e->getMessage()
				);
				$this->_exception=$e;
				$this->handling( $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine() );
				Core_Sql::renewalConnectFromCashe();
			}
		}
		try {
			Core_Sql::setConnectToServer( 'cnm.cnmbeta.info' );
			//========
			Core_Users::getInstance()->setById( $_userId );
			switch( $this->method ) {

				case 'POST': // создать
					if( !isset( $_requestData->query['id'] ) || !isset( $_requestData->request['contact_id'] ) || empty( $_requestData->request['contact_id'] ) || empty( $_arrContacts ) ){
						$_outJson=array(
							'error'=>'invalid_data',
							'error_description'=>'Can\'t patch contact emails'
						);
					}else{
						$_model=new Project_Mooptin();
						$_model->withids( $_requestData->query['id'] )->onlyOne()->getList( $_arrLcData );
						$_arrEmails=array();
						foreach( $_arrContacts as $_contactData ){
							if( $_SERVER['SERVER_NAME'] != 'api.ifunnels.local' ){
								Core_Curl::async( 'https://app.ifunnels.com/lead-channels/form/', array(
									'id'=>Core_Payment_Encode::encode( array( $_arrLcData['id'] ) ),
									'userAgent'=>'S',
									'email'=>$_contactData['email'],
									'firstname'=>$_contactData['name']
								), 'POST' );
							}
							$_arrEmails[]=$_contactData['email'];
						}
						$_outJson=array(
							'success' => true,
							'message' => 'You add '.count($_arrEmails).' contact'.(count($_arrEmails)>1?'s':'').' to `'.$_arrLcData['title'].'` Lead Channel',
							'data'=>$_arrEmails
						);
					}
				break;

				case 'DELETE': // удалить
				
				break;

				case 'PATCH': // обновляет те поля, которые были переданы
				
				break;

				case 'PUT': // просто заменяет одну данные на другие
				
				break;
				
				default:
				case 'GET': // получить список
				$_model=new Project_Mooptin();
				if( isset( $_requestData->query['id'] ) && !empty( $_requestData->query['id'] ) ){
					$_model->withids( $_requestData->query['id'] )->onlyOne()->getList( $_outJson['data'] );
					$_outJson['data']['form'] = str_replace("\r\n", '', Project_Mooptin::getCodeForm( $_outJson['data']['settings']['optin_form'], $_outJson['data']['settings']['form'], $_outJson['data']['id'] ) );
					unset( $_outJson['data']['settings'] );
					unset( $_outJson['data']['user_id'] );
				}else{
					$_recoonpage=$_requestData->query['page']['size'];
					if( !isset( $_requestData->query['page']['size'] ) || $_requestData->query['page']['size'] > 100 ){
						$_recoonpage=10;
					}
					$_model->withPaging(array(
						'url'=>array( 'page'=> @$_requestData->query['page']['number'] ),
						'page'=>@$_requestData->query['page']['number'], 
						'reconpage'=>$_recoonpage,
					))
					->onlyOwner()
					->withOrder( @$_requestData->query['order'] )
					->getList( $_outJson['data'] )
					->getPaging( $_arrPg );
					foreach( $_outJson['data'] as &$_lcData ){
						unset( $_lcData['settings'] );
						unset( $_lcData['user_id'] );
					}
					$_outJson['meta']['count']=$_arrPg['recall'];
					$_outJson['meta']['page']['number']=$_arrPg['curpage'];
					$_outJson['meta']['page']['size']=$_recoonpage;
				}
				break;
			}
			Core_Users::getInstance()->setZero();
			//========
			Core_Sql::renewalConnectFromCashe();
		}catch(Exception $e){
			$_outJson=array(
				'error'=>'invalid_data',
				'error_description'=>$e->getMessage()
			);
			$this->_exception=$e;
			$this->handling( $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine() );
			Core_Sql::renewalConnectFromCashe();
		}
		echo $this->_json_encode( $_outJson );
		exit;
	}
	
	/*
	List products / memberships
	*/
	public function memberships() {
		$this->getRest();
		$this->autoloading();
		// Handle a request to a resource and authenticate the access token
		$_requestData=OAuth2\Request::createFromGlobals();
		if ( !$this->server->verifyResourceRequest( $_requestData ) ){
			$this->server->getResponse()->send();
			die;
		}
		$_requestData->url_parameters=explode( '/', trim( $_requestData->server['REQUEST_URI'], '/' ) );
		$_dataId=@$_requestData->url_parameters[2];
		if( $_dataId[0]!='?' ){
			$_requestData->query['id']=$_dataId;
		}
		$_userId=Core_Payment_Encode::decode( $this->server->getToken()['client_id'] );
		if( isset( $_requestData->request['contact_id'] ) && !empty( $_requestData->request['contact_id'] ) ){
			try{
				//========
				Core_Sql::setConnectToServer( 'lpb.tracker' );
				$_model=new Project_Efunnel_Subscribers( $_userId );
				$_model->withIds( $_requestData->request['contact_id'] )->getList( $_arrContacts );
				//========
			}catch(Exception $e){
				$_outJson=array(
					'error'=>'invalid_data',
					'error_description'=>$e->getMessage()
				);
				$this->_exception=$e;
				$this->handling( $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine() );
				Core_Sql::renewalConnectFromCashe();
			}
		}
		try {
			Core_Sql::setConnectToServer( 'cnm.cnmbeta.info' );
			//========
			Core_Users::getInstance()->setById( $_userId );
			switch( $this->method ) {

				case 'POST': // создать
				break;

				case 'DELETE': // удалить
				break;

				case 'PATCH': // обновляет те поля, которые были переданы
				break;

				case 'PUT': // просто заменяет одну данные на другие
				break;
				
				default:
				case 'GET': // получить список
				$_model=new Project_Deliver_Site();
				$_recoonpage=$_requestData->query['page']['size'];
				if( !isset( $_requestData->query['page']['size'] ) || $_requestData->query['page']['size'] > 100 ){
					$_recoonpage=10;
				}
				$_model->withPaging(array(
					'url'=>array( 'page'=> @$_requestData->query['page']['number'] ),
					'page'=>@$_requestData->query['page']['number'], 
					'reconpage'=>$_recoonpage,
				))
				->onlyOwner()
				->withOrder( @$_requestData->query['order'] )
				->getList( $_outJson['data'] )
				->getPaging( $_arrPg );
				foreach( $_outJson['data'] as &$_lcData ){
					unset( $_lcData['settings'] );
					unset( $_lcData['user_id'] );
				}
				$_outJson['meta']['count']=$_arrPg['recall'];
				$_outJson['meta']['page']['number']=$_arrPg['curpage'];
				$_outJson['meta']['page']['size']=$_recoonpage;
				break;
			}
			Core_Users::getInstance()->setZero();
			//========
			Core_Sql::renewalConnectFromCashe();
		}catch(Exception $e){
			$_outJson=array(
				'error'=>'invalid_data',
				'error_description'=>$e->getMessage()
			);
			$this->_exception=$e;
			$this->handling( $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine() );
			Core_Sql::renewalConnectFromCashe();
		}
		echo $this->_json_encode( $_outJson );
		exit;
	}

	/*
	List sales
	*/
	public function sales() {
		$this->getRest();
		$this->autoloading();
		// Handle a request to a resource and authenticate the access token
		$_requestData=OAuth2\Request::createFromGlobals();
		if ( !$this->server->verifyResourceRequest( $_requestData ) ){
			$this->server->getResponse()->send();
			die;
		}
		$_requestData->url_parameters=explode( '/', trim( $_requestData->server['REQUEST_URI'], '/' ) );
		$_dataId=@$_requestData->url_parameters[2];
		if( $_dataId[0]!='?' ){
			$_requestData->query['id']=$_dataId;
		}
		$_userId=Core_Payment_Encode::decode( $this->server->getToken()['client_id'] );
		if( isset( $_requestData->request['contact_id'] ) && !empty( $_requestData->request['contact_id'] ) ){
			try{
				//========
				Core_Sql::setConnectToServer( 'lpb.tracker' );
				$_model=new Project_Efunnel_Subscribers( $_userId );
				$_model->withIds( $_requestData->request['contact_id'] )->getList( $_arrContacts );
				//========
			}catch(Exception $e){
				$_outJson=array(
					'error'=>'invalid_data',
					'error_description'=>$e->getMessage()
				);
				$this->_exception=$e;
				$this->handling( $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine() );
				Core_Sql::renewalConnectFromCashe();
			}
		}
		try {
			Core_Sql::setConnectToServer( 'cnm.cnmbeta.info' );
			//========
			Core_Users::getInstance()->setById( $_userId );
			switch( $this->method ) {

				case 'POST': // создать
				break;

				case 'DELETE': // удалить
				break;

				case 'PATCH': // обновляет те поля, которые были переданы
				break;

				case 'PUT': // просто заменяет одну данные на другие
				break;
				
				default:
				case 'GET': // получить список
				$_model = new Project_Deliver_Payment();
				if( isset( $_requestData->query['id'] ) && !empty( $_requestData->query['id'] ) ){
					$_model->withids( $_requestData->query['id'] )->onlyOne()->getList( $_outJson['data'] );
					$_outJson['data']['form'] = str_replace("\r\n", '', Project_Mooptin::getCodeForm( $_outJson['data']['settings']['optin_form'], $_outJson['data']['settings']['form'], $_outJson['data']['id'] ) );
					unset( $_outJson['data']['settings'] );
					unset( $_outJson['data']['user_id'] );
				}else{
					$_recoonpage=$_requestData->query['page']['size'];
					if( !isset( $_requestData->query['page']['size'] ) || $_requestData->query['page']['size'] > 100 ){
						$_recoonpage=10;
					}
					$_model->withPaging(array(
						'url'=>array( 'page'=> @$_requestData->query['page']['number'] ),
						'page'=>@$_requestData->query['page']['number'], 
						'reconpage'=>$_recoonpage,
					))
					->onlyOwner()
					->withMembershipName()
					->withCustomerName()
					->withOrder( @$_requestData->query['order'] )
					->getList( $_outJson['data'] )
					->getPaging( $_arrPg );
					foreach( $_outJson['data'] as &$_lcData ){
						unset( $_lcData['settings'] );
						unset( $_lcData['user_id'] );
					}
					$_outJson['meta']['count']=$_arrPg['recall'];
					$_outJson['meta']['page']['number']=$_arrPg['curpage'];
					$_outJson['meta']['page']['size']=$_recoonpage;
				}
				break;
			}
			Core_Users::getInstance()->setZero();
			//========
			Core_Sql::renewalConnectFromCashe();
		}catch(Exception $e){
			$_outJson=array(
				'error'=>'invalid_data',
				'error_description'=>$e->getMessage()
			);
			$this->_exception=$e;
			$this->handling( $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine() );
			Core_Sql::renewalConnectFromCashe();
		}
		echo $this->_json_encode( $_outJson );
		exit;
	}


	private $_exception;
	private $_mode=0; // 0-на ошибки по возможности не реагируем, 1-отсылаем письмо, 2-и в браузер полную инфу
	private $_maxlen=128; // размер отображаемой в трэйсе текстовой переменной
	private $_info=array(); // массив с накопленными данными об ошибке
	
	public function handling( $_intNum, $_strMsg, $_strFile, $_strLine ) {
		if ( in_array( $_intNum, array( E_NOTICE, E_WARNING ) ) ) { // не обарбатываем (как обработать Parse error? TODO!!!)
			return;
		}
		$this->_info['arrHeader']=array( 
			'errname'=>( empty( $this->_cods[$_intNum] )? 'Unknown Error':$this->_cods[$_intNum] ), 
			'msg'=>$_strMsg, 
			'file'=>str_replace( Zend_Registry::get( 'config' )->path->absolute->root, '', $_strFile ).' ['.$_strLine.' line]',
		);
		if ( ( $pos=strpos( $_strMsg, '|' ) )!==false ) { // эксепшн или триггер движка
			$this->_info['arrHeader']['myType']=substr( $_strMsg, 0, $pos );
			$this->_info['arrHeader']['msg']=substr( $_strMsg, ++$pos );
		} else { // системный эксепшн
			$this->_info['arrHeader']['myType']=self::PHP;
		}
		$this->_info['phpver']=PHP_VERSION;
		$this->_info['project']=Zend_Registry::get( 'config' )->engine->project_domain;
		$this->_info['datetime']=date( "M d, Y H:i:s" );
		$this->_info['session']=@print_r( $_SESSION, true );
		$this->_info['server']=@print_r( $_SERVER, true );
		$this->_info['request']=@print_r( $_REQUEST, true );
		$this->trace();
		$this->messaging();
	}

	private function trace() {
		if ( !function_exists( 'debug_backtrace' ) ) { // >= 4.3.0
			return;
		}
		$_arrTrace=empty( $this->_exception )? debug_backtrace():$this->_exception->getTrace();
		array_splice( $_arrTrace, 0, 3 );
		if ( empty( $_arrTrace ) ) {
			return;
		}
		foreach ( $_arrTrace as $k=>$v ) {
			$_arrA=$_arrO=array();
			if ( !empty( $v['args'] ) ) {
				foreach ( $v['args'] as $i ) {
					if ( is_null( $i ) ) {
						$_arrA[]='null';
					} elseif ( is_array( $i ) ) {
						$_arrA[]='Array('.sizeof( $i ).')';
					} elseif ( is_object( $i ) ) {
						$_arrA[]='Object:'.get_class( $i );
					} elseif ( is_bool( $i ) ) {
						$_arrA[]=$i ? 'true':'false';
					} elseif ( is_array( $i ) ) {
						$_arrA[]='array('.sizeof( $i ).')';
					} elseif ( !empty( $i ) ) {
					}
				}
			}
			if ( isSet( $v['class'] ) ) {
				$this->_info['trace'][$k]['method']=$v['class'].'::'.$v['function'].'('.join( ', ', $_arrA ).')';
			} elseif ( isSet( $v['function'] ) ) {
				$this->_info['trace'][$k]['function']=$v['function'].'('.join( ', ', $_arrA ).')';
			}
			if ( !empty( $_arrO )&&$v['function']!='trigger_error' ) {
				$this->_info['trace'][$k]['args']=join( ', ', $_arrO );
			}
			if ( !empty( $v['file'] )&!empty( $v['line'] ) ) {
				$this->_info['trace'][$k]['file']=str_replace( Zend_Registry::get( 'config' )->path->absolute->root, '', $v['file'] ).' ['.$v['line'].' line]';
			}
		}
	}

	private function messaging() {
		if( !empty( Zend_Registry::get( 'config' )->engine->project_domain ) ) {
			Core_Files::getContent( $_errorMailLog, $this->_errorLog );
			$_errorMailLog=unserialize($_errorMailLog);
				$_errorMailLog[$this->_info['arrHeader']['file'].'|'.$this->_info['arrHeader']['msg']]=time();
				$_errorMailLog=serialize($_errorMailLog);
				Core_Files::setContent($_errorMailLog,$this->_errorLog);
				Core_Mailer::getInstance()
					->setVariables( $this->_info )
					->setTemplate( 'system_error' )
					->setSubject( 'Error hunter '.self::$version.': '.Zend_Registry::get( 'config' )->engine->project_domain.' system error' )
					->setPeopleTo( Zend_Registry::get( 'config' )->engine->default_bugtrack->toArray() )
					->setPeopleFrom( array( 'name' => Zend_Registry::get('config')->engine->project_sysemail->name, 'email' => Zend_Registry::get('config')->engine->project_sysemail->email ) )
					->sendOneToMany();
		}
	}
}
?>