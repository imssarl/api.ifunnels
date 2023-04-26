<?php
class Project_Thechecker {
	
	protected $apiUrl='https://api.thechecker.co/v1/';
	private $_apiKey='e08ca53cbcbfdc409750756314fb3f4514cf0013d2210c3a0a06cc1b5f7fdd21';
	
	private $flgTest=false;
	
	public function checkOne( $_email=false ){
		if( empty( $_email ) ){
			return false;
		}
		return $this->call( 'verify', array( 'email'=>$_email ), 'get' );
	}
	
	public function checkValidate( $_id=false ){
		if( empty( $_id ) ){
			return false;
		}
		return $this->call( 'verifications/'.$_id, false, 'get' );
	}
	
	public function checkValidateJson( $_id=false ){
		if( empty( $_id ) ){
			return false;
		}
		return $this->call( 'verifications/'.$_id.'/json', false, 'get' );
	}
	
	public function sendFile( $_fileSender ){
		if( empty( $_fileSender ) ){
			return false;
		}
		return $this->call( 'verifications', array(
			'files' => array( $_fileSender['name'] => $_fileSender['tmp_name'] )
		), 'post', 'file' );
	}
	
	public function sendList( $_arrList ){
		if( empty( $_arrList ) ){
			return false;
		}
		return $this->call( 'verifications', array(
			'emails' =>$_arrList
		), 'post' );
	}
	
    private function call( $url, $data, $type='post', $send='data' ){
		$apiData['api_key']=$this->_apiKey;
		if( $type == 'get' && is_array($data) ){
			$apiData=$apiData+$data;
		}
		if( $send=='data' ){
			$headers=array(
				'Content-Type: application/json',
			);
		}
		if( $send=='file' ){
			$boundary=uniqid();
			$delimiter='-------------'.$boundary;
			$data=$this->buildBoundary( $boundary, $data['files'] );
			$headers=array(
				'Content-Type: multipart/form-data; boundary='.$delimiter,
				'Content-Length: '.strlen($data)
			);
		}else{
			$data=json_encode($data);
		}
		$url.='?'.http_build_query($apiData);
		if( !$this->flgTest ){
			$process=curl_init($this->apiUrl.$url);
			curl_setopt($process, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($process, CURLOPT_HEADER, false);
			curl_setopt($process, CURLOPT_TIMEOUT, 30);
			if($type == 'post'){
				curl_setopt($process, CURLOPT_POST, 1);
				curl_setopt($process, CURLOPT_POSTFIELDS, $data);
			}
			curl_setopt($process, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($process, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($process, CURLOPT_SSL_VERIFYHOST, false);
			$return=curl_exec($process);
			$_error=curl_error($process);
			curl_close($process);
		}else{
		//	var_dump( array( 'url'=>$this->apiUrl.$url, 'data'=>$data ) );
			$return=json_encode( array( true ) );
		}
		$_writer=new Zend_Log_Writer_Stream( Zend_Registry::get('config')->path->absolute->logfiles.'TheChecker.log' );
		$_writer->setFormatter( new Zend_Log_Formatter_Simple("%timestamp% %priorityName% (%priority%): %message%\r\n") );
		$_logger=new Zend_Log( $_writer );
		$_logger->info( 'Send: '.$this->apiUrl.$url );
		$_logger->info( 'Header: '.serialize( $headers ) );
		$_logger->info( 'Data: '.json_encode($data) );
		$_logger->info( 'Error: '.json_encode($_error) );
		$_logger->info( 'Get: '.$return );
		return json_decode($return, true);
	}
	
	function buildBoundary($boundary, $files){
		$data='';
		$eol="\r\n";
		$delimiter='-------------'.$boundary;
		foreach ($files as $name=>$filePath) {
			$data .= "--".$delimiter.$eol
				.'Content-Disposition: form-data; name="files"; filename="'.$name.'"'.$eol
				.'Content-Transfer-Encoding: binary'.$eol;

			$data.=$eol;
			$data.=@file_get_contents($filePath).$eol;
		}
		$data.="--".$delimiter."--".$eol;
		return $data;
	}
}
?>