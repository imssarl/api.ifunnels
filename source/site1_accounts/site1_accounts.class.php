<?php
class site1_accounts extends Core_Module {

	public function set_cfg() {
		$this->inst_script=array(
			'module'=>array( 'title'=>'CNM accounts and general', ),
			'actions'=>array(
			
			
			
			
				array( 'action'=>'authorize', 'title'=>'Authorize', 'flg_tpl'=>3, 'flg_tree'=>2 ),
				array( 'action'=>'token', 'title'=>'Token', 'flg_tpl'=>3, 'flg_tree'=>2 ),
				
				array( 'action'=>'resource', 'title'=>'Resource', 'flg_tpl'=>3, 'flg_tree'=>2 ),
				
				array( 'action'=>'contacts', 'title'=>'Contacts', 'flg_tpl'=>3, 'flg_tree'=>2 ),
				array( 'action'=>'emailfunnels', 'title'=>'Email funnels', 'flg_tpl'=>3, 'flg_tree'=>2 ),
				array( 'action'=>'leadchannels', 'title'=>'Lead Channels', 'flg_tpl'=>3, 'flg_tree'=>2 ),
				

			),
		);
	}
	
	private $storage;
	private $server;
	
	public function autoloading() {
		$dsn= 'mysql:host=localhost;dbname=db_api';
		$username = 'root';
		$password = '';
		// Autoloading (composer is preferred, but for this example let's just do this)
		require_once('/library/OAuth2/Autoloader.php');
		OAuth2\Autoloader::register();
		// $dsn is the Data Source Name for your database, for exmaple "mysql:dbname=my_oauth2_db;host=localhost"
		$this->storage = new OAuth2\Storage\Pdo(array('dsn' => $dsn, 'username' => $username, 'password' => $password));
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
		if (!$server->validateAuthorizeRequest($request, $response)) {
			$response->send();
			die;
		}
		// display an authorization form
		if (empty($_POST)) {
			exit('<form method="post"><label>Do You Authorize ThisClient?</label><br /><input type="submit" name="authorized" value="yes"><input type="submit" name="authorized" value="no"></form>');
		}

		// print the authorization code if the user has authorized your client
		$is_authorized = ($_POST['authorized'] === 'yes');
		$server->handleAuthorizeRequest($request, $response, $is_authorized);
		if ($is_authorized) {
		  // this is only here so that you get to see your code in the cURL request. Otherwise, we'd redirect back to the client
		  $code = substr($response->getHttpHeader('Location'), strpos($response->getHttpHeader('Location'), 'code=')+5, 40);
		  exit("SUCCESS! Authorization Code: $code");
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
		$_dataId=@$_requestData->url_parameters[1];
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
			die;
		}
		$_requestData->url_parameters=explode( '/', trim( $_requestData->server['REQUEST_URI'], '/' ) );
		$_dataId=@$_requestData->url_parameters[1];
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
				// параметры id и т.п. - даные только по одной

				// параметры page и т.п. - даные списком
/*
GET /articles?page[size]=30&page[number]=2
Content-Type: application/json

HTTP/1.1 200 OK
{
	"data": [{ "id": 1, "title": "JSONAPI"}, ...],
	"meta": { "count": 10080 }
}
*/
			break;
		}

		echo json_encode(array('success' => true, 'message' => 'You accessed my APIs!'));
		exit;
	}
	
	/*
	List email funnels
	Add someone to an email funnel
	*/
	public function emailfunnels() {
		$this->getRest();
		$this->autoloading();
		// Handle a request to a resource and authenticate the access token
		$_requestData=OAuth2\Request::createFromGlobals();
		if ( !$this->server->verifyResourceRequest( $_requestData ) ){
			$this->server->getResponse()->send();
			die;
		}
		$_requestData->url_parameters=explode( '/', trim( $_requestData->server['REQUEST_URI'], '/' ) );
		$_dataId=@$_requestData->url_parameters[1];
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
				// параметры id и т.п. - даные только по одной
				
				
				// параметры page и т.п. - даные списком
				
			break;
		}

		echo json_encode(array('success' => true, 'message' => 'You accessed my APIs!'));
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
		$_dataId=@$_requestData->url_parameters[1];
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
				// параметры id и т.п. - даные только по одной
				
				
				// параметры page и т.п. - даные списком
				
			break;
		}

		echo json_encode(array('success' => true, 'message' => 'You accessed my APIs!'));
		exit;
	}
/*
List products / memberships
List sales
*/
	
}
?>