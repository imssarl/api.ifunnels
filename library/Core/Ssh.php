<?php


/**
 * SSH interface
 * work only with pub/priv keys
 */
class Core_Ssh {

	private $_connection=false;
	private $_sftp=false;
	
	
	private $_key=false;
	private $_strUser=false;
	private $_strHost=false;
	private $_strPort=22;
	
	private $_fileMode=640;
	private $_dirMode=750;
	private $_oldIncludePath='';
	
	
	private $_start=0;
	private $_withLogger=false;
	private $_logger=false;

	/**
	 * Construct ssh connection
	 * @param $_strHost
	 * @param $_strUser
	 * @param $_pubKey
	 * @param $_privKey
	 */
	public function __construct( $_strHost, $_strUser, $_pubKey, $_privKey ){
		if( @$_SERVER['HTTP_HOST'] != 'cnm.local' ){
			if( empty( $this->_oldIncludePath ) ){
				$this->_oldIncludePath=set_include_path(get_include_path().PATH_SEPARATOR.'library/phpseclib');
			}
			include_once 'Net/SSH2.php';
			include_once 'Crypt/RSA.php';
			include_once 'Net/SFTP.php';
			$this->_key = new Crypt_RSA();
			$this->_key->setPublicKey(file_get_contents($_pubKey));
			$this->_key->loadKey(file_get_contents($_privKey));
		}else{
			
		}
		if( strpos( $_strHost, ':' ) !== false ){
			$_strParse=explode( ':', $_strHost );
			$_strHost=$_strParse[0];
			$this->_strPort=$_strParse[1];
		}
		$this->_strHost=$_strHost;
		$this->_strUser=$_strUser;
		/*---------------------------------------------*/
		if( $this->_withLogger ){
			$_writer=new Zend_Log_Writer_Stream( Zend_Registry::get('config')->path->absolute->logfiles.'Core_Ssh/timing_'.time().'.log' );
			$_writer->setFormatter( new Zend_Log_Formatter_Simple("%timestamp% %priorityName% (%priority%): %message%\r\n") );
			$this->_logger=new Zend_Log( $_writer );
			$this->_start=microtime(true);
			$this->_logger->info('Start '.$_strHost.'-----------------------------------------------------------------------------------------------------' );
		}
		/*---------------------------------------------*/
	}

	function __destruct(){
		set_include_path( $this->_oldIncludePath );
		if( $this->_withLogger ){
			$this->_logger->info('End -----------------------------------------------------------------------------------------------------' );
		}
		/**/if( @$_SERVER['HTTP_HOST'] == 'cnm.local' ){
		// 	var_dump( '__destruct' );exit; // точка останова, если нужно проверить прохождение комманд
		/**/}
	}

	private function correctUser( $_name ){
		return substr( $_name, 0, 32 );
	}

	private function _connectSSH2(){
		/**/if( @$_SERVER['HTTP_HOST'] != 'cnm.local' ){
		$ssh=new Net_SSH2($this->_strHost,$this->_strPort);
		if (!$ssh->login($this->_strUser, $this->_key)){
			/*---------------------------------------------*/
			if( $this->_withLogger ){
				$this->_start=microtime(true)-$this->_start;
				$this->_logger->info('Cant connect to ssh '.$this->_strHost.':'.$this->_strPort.' : '.$this->_start );
				$this->_start=microtime(true);
			}
			/*---------------------------------------------*/
			return Core_Data_Errors::getInstance()->setError('Cant connect to ssh');
		}
		/*---------------------------------------------*/
		if( $this->_withLogger ){
			$this->_start=microtime(true)-$this->_start;
			$this->_logger->info('_connectSSH2  '.$this->_strHost.': '.$this->_start );
			$this->_start=microtime(true);
		}
		/*---------------------------------------------*/
		/**/}else{
			$ssh=new fakeConnection();
		}
		return $ssh;
	}

	private function _connectSFTP(){
		/**/if( @$_SERVER['HTTP_HOST'] != 'cnm.local' ){
		$sftp=new Net_SFTP($this->_strHost,$this->_strPort);
		if (!$sftp->login($this->_strUser, $this->_key)){
			/*---------------------------------------------*/
			if( $this->_withLogger ){
				$this->_start=microtime(true)-$this->_start;
				$this->_logger->info('Cant connect to sftp  '.$this->_strHost.': '.$this->_start );
				$this->_start=microtime(true);
			}
			/*---------------------------------------------*/
			return Core_Data_Errors::getInstance()->setError('Cant connect to sftp');
		}
		/*---------------------------------------------*/
		if( $this->_withLogger ){
			$this->_start=microtime(true)-$this->_start;
			$this->_logger->info('_connectSFTP  '.$this->_strHost.': '.$this->_start );
			$this->_start=microtime(true);
		}
		/*---------------------------------------------*/
		/**/}else{
			$sftp=new fakeConnection();
		}
		return $sftp;
	}

	/**
	 * Set mode for all uploaded files
	 * @param $_intMode
	 * @return Core_Ssh
	 */
	public function setFileMode( $_intMode ){
		$this->_fileMode=$_intMode;
		return $this;
	}

	/**
	 * Set mode for all uploaded dirs
	 * @param $_intMode
	 * @return Core_Ssh
	 */
	public function setDirMode( $_intMode ){
		$this->_dirMode=$_intMode;
		return $this;
	}

	private function parseDirData( $_dirLink='', &$group, &$dir ){
		if( $_dirLink == '/data/www/' ){
			throw new Core_Ssh_Exception('Bad dir '.$_dirLink.' parsed in ssh '.serialize($this));
		}
		$_dirLink=strtolower( $_dirLink );
		$_ds=DIRECTORY_SEPARATOR;
		$dir=trim( $_dirLink, $_ds );
		$dir=substr($dir, 0, strrpos( $dir, $_ds ) );
		$group=$_dirLink;
		$_arrGroups=explode( $_ds, $dir );
		foreach( $_arrGroups as $_name ){
			if( strpos( $_name, '.' ) ){
				$group=$_name;
			}
		}
		if( substr( $_dirLink, 0, strlen('/data/www/') ) !== '/data/www/' ){
			$this->_strUser=$group='userdata';
		}
		$dir=$_ds.$dir.$_ds;
		//var_dump(  $_dirLink, $dir, $group  );exit;
		return;
	}
	
	/**
	 * Upload file
	 * @param $_localFile
	 * @param $_remoteFile
	 * @return bool
	 */

	
	public function uploadFile( $_localFile, $_remoteFile ){
		/*---------------------------------------------*/
		if( $this->_withLogger ){
			$this->_start=microtime(true)-$this->_start;
			$this->_logger->info('before uploadFile '.$_localFile.' to '.$_remoteFile.': '.$this->_start );
			$this->_start=microtime(true);
		}
		/*---------------------------------------------*/
		$this->parseDirData( $_remoteFile, $_strGroup, $_strPath );
		// первая сессия
		$ssh1=$this->_connectSSH2();
		if( $ssh1===false ){
			return false;
		}
		$ssh1->exec( 
			'sudo usermod -a -G '.$this->correctUser($_strGroup).' '.$this->correctUser($this->_strUser).';'.
			'sudo chmod 770 '.$_strPath
		);
		/*---------------------------------------------*/
		if( $this->_withLogger ){
			$this->_start=microtime(true)-$this->_start;
			$this->_logger->info('set mode '.$_localFile.' to '.$_remoteFile.': '.$this->_start );
			$this->_start=microtime(true);
		}
		/*---------------------------------------------*/
		// вторая сессия
		$sftp=$this->_connectSFTP();
		if( $sftp===false ){
			return false;
		}
		if( $sftp->size($_remoteFile) >= 0 ){
			$ssh2=$this->_connectSSH2();
			if( $ssh2===false ){
				return false;
			}
			$ssh2->exec( 'sudo chmod 660 '.$_remoteFile );
			$sftp->delete( $_remoteFile );
		}
		/*---------------------------------------------*/
		if( $this->_withLogger ){
			$this->_start=microtime(true)-$this->_start;
			$this->_logger->info('check and delete '.$_remoteFile.': '.$this->_start );
			$this->_start=microtime(true);
		}
		/*---------------------------------------------*/
		set_time_limit( 260 );
		$_res=$sftp->put($_remoteFile, $_localFile, NET_SFTP_LOCAL_FILE );
		/*---------------------------------------------*/
		if( $this->_withLogger ){
			$this->_start=microtime(true)-$this->_start;
			$this->_logger->info('put file with size '.@filesize( $_localFile ).' - '.$_localFile.' to '.$_remoteFile.': '.$this->_start );
			$this->_start=microtime(true);
		}
		/*---------------------------------------------*/
		// третья сессия
		$ssh3=$this->_connectSSH2();
		if( $ssh3===false ){
			return false;
		}
		$ssh3->exec( 
			'sudo chown '.$this->correctUser($_strGroup).':'.$this->correctUser($_strGroup).' '.$_remoteFile.';'.
			'sudo chmod '. $this->_fileMode .' '.$_remoteFile.';'.
			'sudo chmod '. $this->_dirMode .' '.$_strPath.';'.
			"sudo usermod -G `cat /etc/group | grep ".$this->correctUser($this->_strUser)." | grep -v ".$_strGroup." | cut -d ':' -f 1 | tr '\\n' ',' | sed 's/,$//'` ".$this->correctUser($this->_strUser).';'
		);
		/*---------------------------------------------*/
		if( $this->_withLogger ){
			$this->_start=microtime(true)-$this->_start;
			$this->_logger->info('set mode back'.$_localFile.' to '.$_remoteFile.': '.$this->_start );
			$this->_start=microtime(true);
		}
		/*---------------------------------------------*/
		// что возвращаем
		if( $_res != 1 ){
			if( $this->_withLogger ){
				$this->_start=microtime(true)-$this->_start;
				$this->_logger->info('ERROR'.$_localFile.' to '.$_remoteFile.' ERROR: '.serialize( $sftp->getErrors() ).' ERROR: '.serialize( $sftp->getSFTPErrors() ).': '.$this->_start );
				$this->_start=microtime(true);
			}
			return false;
		}
		/*---------------------------------------------*/
		if( $this->_withLogger ){
			$this->_start=microtime(true)-$this->_start;
			$this->_logger->info('after uploadFile '.$_localFile.' to '.$_remoteFile.': '.$this->_start );
			$this->_start=microtime(true);
		}
		/*---------------------------------------------*/
		/* OLD CODE 03/04/2016
		$_res=ssh2_scp_send( $this->_connection, $_localFile,$_remoteFile,$this->_fileMode );
		return $_res;
		*/
		return true;
	}

	/**
	 * Upload dir
	 * @param $_local
	 * @param $_remote
	 * @return bool
	 */
	public function uploadDir($_local,$_remote){
		if ( is_dir($_local) ){
			$this->mkDir($_remote);
  			$d=dir($_local);
  			while (false !== ($entry = $d->read())){
    			if ($entry == '.' || $entry == '..'){
					continue;
				}
				$_local=rtrim($_local,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
				$_remote=rtrim($_remote,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
				$_flgUploaded=$this->uploadDir($_local.$entry, $_remote.$entry);
    			if( $_flgUploaded === false ){
					sleep( 2 );
					$_flgUploaded=$this->uploadDir($_local.$entry, $_remote.$entry);
					if( $_flgUploaded === false ){
						return Core_Data_Errors::getInstance()->setError('Can\'t copy '.$_local.$entry.' in '.$_remote.$entry.'<div style="display:none">'.base64_encode(serialize($_flgUploaded)).'</div>');
					}
				}
  			}
  			$d->close();
		} else {
			return $this->uploadFile( $_local, $_remote );
		}
		return true;
	}

	/**
	 * Рекурсивное сканирование директорий по SSH
	 * @param $arrRes
	 * @param string $_strDir
	 * @param int $_intInfo
	 * @return bool
	 */
	public function dirScan( &$arrRes, $_strDir='', $_intInfo=1 ){
		/*---------------------------------------------*/
		if( $this->_withLogger ){
			$this->_start=microtime(true)-$this->_start;
			$this->_logger->info('before dirScan '.$_strDir.': '.$this->_start );
			$this->_start=microtime(true);
		}
		/*---------------------------------------------*/
		if(!$this->isDir($_strDir)){
			return false;
		}
		$this->parseDirData( $_strDir, $_strGroup, $_strPath );
		$_strDir='/'.trim($_strDir,'/').'/';
		$ssh=$this->_connectSSH2();
		if( $ssh===false ){
			return false;
		}
		$ssh->exec( 
			'sudo usermod -a -G '.$this->correctUser($_strGroup).' '.$this->correctUser($this->_strUser).';'.
			'sudo chmod 770 '.$_strDir
		);
		
		$sftp=$this->_connectSFTP();
		if( $sftp===false ){
			return false;
		}
		if( $_intInfo ){
			$arrRes=$sftp->rawlist();
		}else{
			$arrRes=$sftp->nlist();
		}
		
		$ssh=$this->_connectSSH2();
		if( $ssh===false ){
			return false;
		}
		$ssh->exec( 
			'sudo chmod '. $this->_dirMode .' '.$_strDir.';'.
			"sudo usermod -G `cat /etc/group | grep ".$this->correctUser($this->_strUser)." | grep -v ".$this->correctUser($_strGroup)." | cut -d ':' -f 1 | tr '\\n' ',' | sed 's/,$//'` ".$this->correctUser($this->_strUser)
		);
		/*---------------------------------------------*/
		if( $this->_withLogger ){
			$this->_start=microtime(true)-$this->_start;
			$this->_logger->info('after dirScan '.$_strDir.': '.$this->_start );
			$this->_start=microtime(true);
		}
		/*---------------------------------------------*/
		return true;
		/*
		return Core_Files::dirScan( $arrRes,"ssh2.sftp://". $this->_sftp . $_strDir, $_intInfo );
		*/
	}

	public function dirLs( &$arrRes ){
		/*---------------------------------------------*/
		if( $this->_withLogger ){
			$this->_start=microtime(true)-$this->_start;
			$this->_logger->info('before dirLs '.$this->_dirForLs.': '.$this->_start );
			$this->_start=microtime(true);
		}
		/*---------------------------------------------*/
		if( $this->isDir($this->_dirForLs) ){
			return false;
		}
		$this->parseDirData( $this->_dirForLs, $_strGroup, $_dirTmp );

		$ssh=$this->_connectSSH2();
		if( $ssh===false ){
			return false;
		}
		/*---------------------------------------------*/
		if( $this->_withLogger ){
			$this->_start=microtime(true)-$this->_start;
			$this->_logger->info('sudo usermod -a -G '.$this->correctUser($_strGroup).' '.$this->correctUser($this->_strUser).';'.
			'sudo chmod 770 '.$this->_dirForLs);
			$this->_start=microtime(true);
		}
		/*---------------------------------------------*/
		$ssh->exec( 
			'sudo usermod -a -G '.$this->correctUser($_strGroup).' '.$this->correctUser($this->_strUser).';'.
			'sudo chmod 770 '.$this->_dirForLs
		);
		/*---------------------------------------------*/
		if( $this->_withLogger ){
			$this->_start=microtime(true)-$this->_start;
			$this->_logger->info('dir for LS '.$this->_dirForLs.': '.$this->_start );
			$this->_start=microtime(true);
		}
		/*---------------------------------------------*/
		$sftp=$this->_connectSFTP();
		if( $sftp===false ){
			return false;
		}
		$_arrDirs=$sftp->rawlist($this->_dirForLs.'/');
		$ssh=$this->_connectSSH2();
		if( $ssh===false ){
			return false;
		}
		$ssh->exec( 
			'sudo chmod '. $this->_dirMode .' '.$this->_dirForLs.';'.
			"sudo usermod -G `cat /etc/group | grep ".$this->correctUser($this->_strUser)." | grep -v ".$this->correctUser($_strGroup)." | cut -d ':' -f 1 | tr '\\n' ',' | sed 's/,$//'` ".$this->correctUser($this->_strUser)
		);
		$arrRes=$_arrPaths=$_arrFiles=array();
		foreach( $_arrDirs as $_name=>$_stat ){
			if($_stat['type']==2){
				if( $_name == '.' ){
					$arrRes[1]=array(
						'name'=>$_name,
						'is_dir'=>(($_stat['type']==2)?true:false),
						'stat'=>$_stat,
						'type'=>'-'
					);
				}elseif( $_name == '..' ){
					$arrRes[0]=array(
						'name'=>$_name,
						'is_dir'=>(($_stat['type']==2)?true:false),
						'stat'=>$_stat,
						'type'=>'-'
					);
				}else{
					$_arrPaths[$_name]=array(
						'name'=>$_name,
						'is_dir'=>(($_stat['type']==2)?true:false),
						'stat'=>$_stat,
						'type'=>'-'
					);
				}
			}else{
				$_arrFiles[$_name]=array(
					'name'=>$_name,
					'is_dir'=>(($_stat['type']==2)?true:false),
					'stat'=>$_stat,
					'type'=>'-'
				);
			}
		}
		foreach( $_arrPaths as $_stat ){
			$arrRes[]=$_stat;
		}
		foreach( $_arrFiles as $_stat ){
			$arrRes[]=$_stat;
		}
		/*---------------------------------------------*/
		if( $this->_withLogger ){
			$this->_start=microtime(true)-$this->_start;
			$this->_logger->info('after dirLs '.$this->_dirForLs.': '.$this->_start );
			$this->_start=microtime(true);
		}
		/*---------------------------------------------*/
		/*
		$_path="ssh2.sftp://". $this->_sftp . $this->_dirForLs;
		$_d=opendir( $_path );
		while (($_result = readdir($_d)) !== false){
			$_stat=ssh2_sftp_stat($this->_sftp,$this->_dirForLs.'/'.$_result);
		  $arrRes[]=array(
			  'name'=>$_result,
			  'is_dir'=>(($_stat['mode']<20000)?true:false),
			  'stat'=>$_stat,
			  'type'=>'-'
		  );
		}
		closedir( $_d );
		*/
		return !empty($arrRes);
	}

	public function dirForLs( $_str ){
		$this->_dirForLs=$_str;
		return $this;
	}

	/**
	 * Download remote file to local
	 * @param $_remoteFile
	 * @param $_localFile
	 * @return bool
	 */
	public function download( $_remoteFile, $_localFile ){
		/*---------------------------------------------*/
		if( $this->_withLogger ){
			$this->_start=microtime(true)-$this->_start;
			$this->_logger->info('before download '.$_remoteFile.' to '.$_localFile.': '.$this->_start );
			$this->_start=microtime(true);
		}
		/*---------------------------------------------*/
		$this->parseDirData( $_remoteFile, $_strGroup, $_strPath );
		$ssh=$this->_connectSSH2();
		if( $ssh===false ){
			return false;
		}
		$ssh->exec( 
			'sudo usermod -a -G '.$this->correctUser($_strGroup).' '.$this->correctUser($this->_strUser).';'.
			'sudo chmod 770 '.$_strPath.';'.
			'sudo chmod 660 '.$_remoteFile
		);
		$sftp=$this->_connectSFTP();
		if( $sftp===false ){
			return false;
		}
		$_return=$sftp->get($_remoteFile, $_localFile);
		/*---------------------------------------------*/
		if( $this->_withLogger ){
			$this->_start=microtime(true)-$this->_start;
			$this->_logger->info('after download '.$_remoteFile.' to '.$_localFile.': '.$this->_start );
			$this->_start=microtime(true);
		}
		/*---------------------------------------------*/
		return $_return;
		/* OLD CODE 03/04/2016
		return ssh2_scp_recv($this->_connection,$_remoteFile,$_localFile);
		*/
	}

	/**
	 * Delete dir
	 * @param $_strDir
	 * @return bool
	 */
	public function rmDir( $_strDir ){
		if(empty($_strDir)){
			return false;
		}
		/*---------------------------------------------*/
		if( $this->_withLogger ){
			$this->_start=microtime(true)-$this->_start;
			$this->_logger->info('before rmDir '.$_strDir.': '.$this->_start );
			$this->_start=microtime(true);
		}
		/*---------------------------------------------*/
		$this->parseDirData( $_strDir, $_strGroup, $_strPath );
		$_strDir='/'.trim($_strDir,'/').'/';
		$ssh=$this->_connectSSH2();
		if( $ssh===false ){
			return false;
		}
		$ssh->exec( 
			'sudo usermod -a -G '.$this->correctUser($_strGroup).' '.$this->correctUser($this->_strUser).';'.
			'sudo chmod 770 '.$_strDir.';'.
			'sudo rm -rf '.$_strDir.';'.
			'sudo chmod '. $this->_dirMode .' '.$_strDir.';'.
			"sudo usermod -G `cat /etc/group | grep ".$this->correctUser($this->_strUser)." | grep -v ".$this->correctUser($_strGroup)." | cut -d ':' -f 1 | tr '\\n' ',' | sed 's/,$//'` ".$this->correctUser($this->_strUser).';'
		);
		/*---------------------------------------------*/
		if( $this->_withLogger ){
			$this->_start=microtime(true)-$this->_start;
			$this->_logger->info('after rmDir '.$_strDir.': '.$this->_start );
			$this->_start=microtime(true);
		}
		/*---------------------------------------------*/
		$this->replication( $_strGroup );
		return true;
		/* OLD CODE 03/04/2016
		return ssh2_exec($this->_connection, 'rm -rf '.$_strDir);
	//	return ssh2_sftp_rmdir($this->_sftp,$_strDir);
		*/
	}

	public function rmFile( $_remoteFile ){
		if(empty($_remoteFile)){
			return false;
		}
		/*---------------------------------------------*/
		if( $this->_withLogger ){
			$this->_start=microtime(true)-$this->_start;
			$this->_logger->info('before rmFile '.$_remoteFile.': '.$this->_start );
			$this->_start=microtime(true);
		}
		/*---------------------------------------------*/
		$this->parseDirData( $_remoteFile, $_strGroup, $_strPath );
		$ssh=$this->_connectSSH2();
		if( $ssh===false ){
			return false;
		}
		$ssh->exec( 
			'sudo usermod -a -G '.$this->correctUser($_strGroup).' '.$this->correctUser($this->_strUser).';'.
			'sudo chmod 770 '.$_strPath.';'.
			'sudo chmod 660 '.$_remoteFile
		);
		$sftp=$this->_connectSFTP();
		if( $sftp===false ){
			return false;
		}
		$_return=$sftp->delete($_remoteFile);
		$ssh=$this->_connectSSH2();
		if( $ssh===false ){
			return false;
		}
		$ssh->exec(
			'sudo chmod '. $this->_dirMode .' '.$_strPath.';'.
			"sudo usermod -G `cat /etc/group | grep ".$this->correctUser($this->_strUser)." | grep -v ".$this->correctUser($_strGroup)." | cut -d ':' -f 1 | tr '\\n' ',' | sed 's/,$//'` ".$this->correctUser($this->_strUser).';'
		);
		/*---------------------------------------------*/
		if( $this->_withLogger ){
			$this->_start=microtime(true)-$this->_start;
			$this->_logger->info('after rmFile '.$_remoteFile.': '.$this->_start );
			$this->_start=microtime(true);
		}
		/*---------------------------------------------*/
		$this->replication( $_strGroup );
		return $_return;
		/*
		return ssh2_sftp_unlink( $this->_sftp,$_remoteFile );
		*/
	}

	/**
	 * Clean all files in dir with extension, default .php
	 * @param $_strDir
	 * @param string $_extension
	 * @return bool|resource
	 */
	public function cleanDir( $_strDir, $_extension='.php' ){
		if(empty($_strDir)){
			return false;
		}
		/*---------------------------------------------*/
		if( $this->_withLogger ){
			$this->_start=microtime(true)-$this->_start;
			$this->_logger->info('before cleanDir '.$_strDir.': '.$this->_start );
			$this->_start=microtime(true);
		}
		/*---------------------------------------------*/
		$this->parseDirData( $_strDir, $_strGroup, $_strPath );
		$_strDir='/'.trim($_strDir,'/').'/';
		$ssh1=$this->_connectSSH2();
		if( $ssh1===false ){
			return false;
		}
		$ssh1->exec(
			'sudo usermod -a -G '.$this->correctUser($_strGroup).' '.$this->correctUser($this->_strUser).';'.
			'sudo chmod 770 '.$_strDir.';'.
			'sudo rm -fr '.$_strDir.'*'.$_extension.';'.
			'sudo chmod '. $this->_dirMode .' '.$_strDir.';'.
			"sudo usermod -G `cat /etc/group | grep ".$this->correctUser($this->_strUser)." | grep -v ".$this->correctUser($_strGroup)." | cut -d ':' -f 1 | tr '\\n' ',' | sed 's/,$//'` ".$this->correctUser($this->_strUser).';'
		);
		/*---------------------------------------------*/
		if( $this->_withLogger ){
			$this->_start=microtime(true)-$this->_start;
			$this->_logger->info('after cleanDir '.$_strDir.': '.$this->_start );
			$this->_start=microtime(true);
		}
		/*---------------------------------------------*/
		$this->replication( $_strGroup );
		return true;
		/* OLD CODE 03/04/2016
		$_strDir=rtrim($_strDir,'/').'/';
		return ssh2_exec($this->_connection, 'rm -fr '.$_strDir.'*'.$_extension);
		*/
	}

	/**
	 * Create dir
	 * @param $_strDir
	 * @return bool
	 */
	public function mkDir( $_strDir ){
		if(empty($_strDir)){
			return false;
		}
		/*---------------------------------------------*/
		if( $this->_withLogger ){
			$this->_start=microtime(true)-$this->_start;
			$this->_logger->info('before mkDir '.$_strDir.': '.$this->_start );
			$this->_start=microtime(true);
		}
		/*---------------------------------------------*/
		$this->parseDirData( $_strDir, $_strGroup, $_strPath );
		$_strDir='/'.trim($_strDir,'/').'/';
		$_strPath='/'.trim($_strPath,'/').'/';
		$ssh1=$this->_connectSSH2();
		if( $ssh1===false ){
			return false;
		}
		$ssh1->exec(
			'sudo usermod -a -G '.$this->correctUser($_strGroup).' '.$this->correctUser($this->_strUser).';'.
			'sudo chmod 770 '.$_strPath.';'.
			'sudo mkdir '.$_strDir.';'.
			'sudo chmod 770 '.$_strDir.';'.
			'sudo chown '.$this->correctUser($_strGroup).':'.$this->correctUser($_strGroup).' '.$_strDir.';'.
			'sudo chmod '. $this->_dirMode .' '.$_strDir.';'.
			'sudo chmod '. $this->_dirMode .' '.$_strPath.';'.
			"sudo usermod -G `cat /etc/group | grep ".$this->correctUser($this->_strUser)." | grep -v ".$this->correctUser($_strGroup)." | cut -d ':' -f 1 | tr '\\n' ',' | sed 's/,$//'` ".$this->correctUser($this->_strUser).';'
		);
		/*---------------------------------------------*/
		if( $this->_withLogger ){
			$this->_start=microtime(true)-$this->_start;
			$this->_logger->info('after mkDir '.$_strDir.': '.$this->_start );
			$this->_start=microtime(true);
		}
		/*---------------------------------------------*/
		$this->replication( $_strGroup );
		return true;
		/* OLD CODE 03/04/2016
		if( ssh2_sftp_lstat( $this->_sftp, $_strDir )){
			return true;
		}
//		return mkdir("ssh2.sftp://".$this->_sftp.$_strDir, $this->_dirMode, true);
		return ssh2_sftp_mkdir( $this->_sftp, $_strDir, $this->_dirMode, true);
		*/
	}

	/**
	 * Check if created dir or file
	 * @param $_strDir
	 * @return array
	 */
	public function isDir( $_strDir ){
		$_flgIsDir=$this->execCmd('test -d '.$_strDir.' && echo 2 || echo 1');
		/*---------------------------------------------*/
		if( $this->_withLogger ){
			$this->_start=microtime(true)-$this->_start;
			$this->_logger->info('check is dir '.$_strDir.' return '.$_flgIsDir.': '.$this->_start );
			$this->_start=microtime(true);
		}
		/*---------------------------------------------*/
		return ( $_flgIsDir== 2 )?true:false;
		/*
		return ssh2_sftp_lstat( $this->_sftp,$_strDir );
		*/
	}

	/**
	 * Chenge owner and group for files and dir
	 * @param $_strGroup
	 * @param $_strUser
	 * @param $_strPath
	 * @return resource
	 */
	public function chownR( $_strGroup, $_strUser, $_strPath ){
		/*---------------------------------------------*/
		if( $this->_withLogger ){
			$this->_start=microtime(true)-$this->_start;
			$this->_logger->info('before chownR '.$_strPath.': '.$this->_start );
			$this->_start=microtime(true);
		}
		/*---------------------------------------------*/
		$this->parseDirData( $_strPath, $_strGroupOld, $_none );
		$_strPath='/'.trim($_strPath,'/').'/';
		$ssh1=$this->_connectSSH2();
		if( $ssh1===false ){
			return false;
		}
		$ssh1->exec(
			'sudo usermod -a -G '.$this->correctUser($_strGroup).' '.$this->correctUser($this->_strUser).';'.
			'sudo chmod 770 '.$_strDir.';'.
			'sudo chown -R '.$this->correctUser($_strGroup).':'.$this->correctUser($_strUser).' '.$_strPath.';'.
			'sudo chmod '. $this->_dirMode .' '.$_strDir.';'.
			"sudo usermod -G `cat /etc/group | grep ".$this->correctUser($this->_strUser)." | grep -v ".$this->correctUser($_strGroup)." | cut -d ':' -f 1 | tr '\\n' ',' | sed 's/,$//'` ".$this->correctUser($this->_strUser).';'
		);
		/*---------------------------------------------*/
		if( $this->_withLogger ){
			$this->_start=microtime(true)-$this->_start;
			$this->_logger->info('after chownR '.$_strPath.': '.$this->_start );
			$this->_start=microtime(true);
		}
		/*---------------------------------------------*/
		return true;
		/*
		return ssh2_exec( $this->_connection, );
		*/
	}

	/**
	 * Set mode for dir and files in path
	 * use: setFileMode()->setDirMode()->chmodR();
	 * @param $_strPath
	 * @return bool
	 */
	public function chmodR( $_strPath ){
		if(empty($_strPath)||!$this->isDir($_strPath)){
			return false;
		}
		/*---------------------------------------------*/
		if( $this->_withLogger ){
			$this->_start=microtime(true)-$this->_start;
			$this->_logger->info('before chmodR '.$_strPath.': '.$this->_start );
			$this->_start=microtime(true);
		}
		/*---------------------------------------------*/
		$_strPath='/'.trim($_strPath,'/').'/';
		$this->execCmd('find '.$_strPath.' -name "*" -type d -exec chmod '. $this->_dirMode .' {} \;;');
		$this->execCmd('find '.$_strPath.' -name "*" -type f -exec chmod '. $this->_fileMode .' {} \;;');
		/*---------------------------------------------*/
		if( $this->_withLogger ){
			$this->_start=microtime(true)-$this->_start;
			$this->_logger->info('after chmodR '.$_strPath.': '.$this->_start );
			$this->_start=microtime(true);
		}
		/*---------------------------------------------*/
		return true;
	}

	/**
	 * Unzip archive
	 * @param $_remoteFile
	 * @param $_remoteDir
	 * @return bool|resource
	 */
	public function unzip( $_remoteFile, $_remoteDir ){
		if(empty($_remoteDir)||empty($_remoteFile)){
			return false;
		}
		/*---------------------------------------------*/
		if( $this->_withLogger ){
			$this->_start=microtime(true)-$this->_start;
			$this->_logger->info('before unzip '.$_remoteFile.' to '.$_remoteDir.': '.$this->_start );
			$this->_start=microtime(true);
		}
		/*---------------------------------------------*/
		$this->parseDirData( $_remoteFile, $_strGroup1, $_remoteFileDir );
		$this->parseDirData( $_remoteDir, $_strGroup2, $_none );
		$_remoteDir='/'.trim($_remoteDir,'/').'/';
		$_remoteFileDir='/'.trim($_remoteFileDir,'/').'/';
		$ssh1=$this->_connectSSH2();
		if( $ssh1===false ){
			return false;
		}
		$_exec1='sudo usermod -a -G '.$this->correctUser($_strGroup1).' '.$this->correctUser($this->_strUser).';'.
			'sudo usermod -a -G '.$this->correctUser($_strGroup2).' '.$this->correctUser($this->_strUser).';'.
			'sudo chmod 770 /data/www/'.$_strGroup1.';'.
			(($_strGroup2!=$_strGroup1)?'sudo chmod 770 /data/www/'.$_strGroup2.';':'');
		$ssh1->exec($_exec1);
		$ssh2=$this->_connectSSH2();
		if( $ssh2===false ){
			return false;
		}
		$_exec2='sudo unzip -o '.$_remoteFile. ' -d '.$_remoteDir.';';
		$ssh2->exec($_exec2);
		
		$ssh3=$this->_connectSSH2();
		if( $ssh3===false ){
			return false;
		}
		$_exec3=
			'sudo chown -R '.$this->correctUser($_strGroup1).':'.$this->correctUser($_strGroup1).' /data/www/'.$_strGroup1.';'.
			(($_strGroup2!=$_strGroup1)?'sudo chown -R '.$this->correctUser($_strGroup2).':'.$this->correctUser($_strGroup2).' /data/www/'.$_strGroup2.';':'').
			'sudo find /data/www/'.$_strGroup1.' -type f -exec chmod '. $this->_fileMode .' {} \;;'.
			'sudo find /data/www/'.$_strGroup1.' -type d -exec chmod '. $this->_dirMode .' {} \;;'.
			(($_strGroup2!=$_strGroup1)?'sudo find /data/www/'.$_strGroup2.' -type f -exec chmod '. $this->_fileMode .' {} \;;'.'sudo find /data/www/'.$_strGroup2.' -type d -exec chmod '. $this->_dirMode .' {} \;;':'').
			"sudo usermod -G `cat /etc/group | grep ".$this->correctUser($this->_strUser)." | grep -v ".$this->correctUser($_strGroup1)." | cut -d ':' -f 1 | tr '\\n' ',' | sed 's/,$//'` ".$this->correctUser($this->_strUser).';'.
			(($_strGroup2!=$_strGroup1)?"sudo usermod -G `cat /etc/group | grep ".$this->correctUser($this->_strUser)." | grep -v ".$this->correctUser($_strGroup2)." | cut -d ':' -f 1 | tr '\\n' ',' | sed 's/,$//'` ".$this->correctUser($this->_strUser).';':'');
		$ssh3->exec($_exec3);
		
		/*---------------------------------------------*/
		if( $this->_withLogger ){
			$this->_start=microtime(true)-$this->_start;
			$this->_logger->info('exec: '.$_exec1.$_exec2.$_exec3 );
			$this->_logger->info('after unzip '.$_remoteFile.' to '.$_remoteDir.': '.$this->_start );
			$this->_start=microtime(true);
		}
		/*---------------------------------------------*/
		$this->replication( $_strGroup1 );
		if( $_strGroup2!=$_strGroup1 ) $this->replication( $_strGroup2 );
		return true;
		/*
		$_res=$this->execCmd( 'unzip -o '.$_remoteFile. ' -d '.$_remoteDir );
		return !empty($_res);
		*/
	}

	/**
	 * Set content to remote file
	 * @param $_strContent
	 * @param $_remoteFile
	 * @return bool|resource
	 */
	public function setContent2File( $_strContent, $_remoteFile ){
		if(empty($_strContent)||empty($_remoteFile)){
			return false;
		}
		/*---------------------------------------------*/
		if( $this->_withLogger ){
			$this->_start=microtime(true)-$this->_start;
			$this->_logger->info('before setContent2File '.$_remoteFile.': '.$this->_start );
			$this->_start=microtime(true);
		}
		/*---------------------------------------------*/
		$this->parseDirData( $_remoteFile, $_strGroup, $_strPath );
		$ssh=$this->_connectSSH2();
		if( $ssh===false ){
			return false;
		}
		$ssh->exec( 
			'sudo usermod -a -G '.$this->correctUser($_strGroup).' '.$this->correctUser($this->_strUser).';'.
			'sudo chmod 770 '.$_strPath.';'.
			'sudo chmod 660 '.$_remoteFile
		);
		$sftp=$this->_connectSFTP();
		if( $sftp===false ){
			return false;
		}
		$_return=$sftp->put($_remoteFile, $_strContent);
		$ssh=$this->_connectSSH2();
		if( $ssh===false ){
			return false;
		}
		$ssh->exec(
			'sudo chmod '. $this->_dirMode .' '.$_strPath.';'.
			'sudo chmod '. $this->_fileMode .' '.$_remoteFile.
			"sudo usermod -G `cat /etc/group | grep ".$this->correctUser($this->_strUser)." | grep -v ".$this->correctUser($_strGroup)." | cut -d ':' -f 1 | tr '\\n' ',' | sed 's/,$//'` ".$this->correctUser($this->_strUser).';'
		);
		/*---------------------------------------------*/
		if( $this->_withLogger ){
			$this->_start=microtime(true)-$this->_start;
			$this->_logger->info('after setContent2File '.$_remoteFile.': '.$this->_start );
			$this->_start=microtime(true);
		}
		/*---------------------------------------------*/
		$this->replication( $_strGroup );
		return $_return;
		/*
		return ssh2_exec($this->_connection,'echo "'. $_strContent .'" > '.$_remoteFile);
		*/
	}

	/**
	 * Run .sh script
	 * @param $_remoteFile
	 * @return bool|resource
	 */
	public function runScript( $_remoteFile ){
		if(empty($_remoteFile)||$this->isDir($_remoteFile)){
			return false;
		}
		$ssh=$this->_connectSSH2();
		if( $ssh===false ){
			return false;
		}
		/*---------------------------------------------*/
		if( $this->_withLogger ){
			$this->_start=microtime(true)-$this->_start;
			$this->_logger->info('runScript  '.$_remoteFile.': '.$this->_start );
			$this->_start=microtime(true);
		}
		/*---------------------------------------------*/
		return $ssh->exec( $_remoteFile );
		/*
		return ssh2_exec( $this->_connection, $_remoteFile );
		*/
	}

	/**
	 * Reload Apache on remote server
	 * @return bool|string
	 */
	public function reloadApache(){
		return true;//$this->execCmd('/etc/init.d/apache2 reload');
	}

	/**
	 * replication
	 * @return bool|string
	 */
	public function replication( $group='root' ){
		return true;
	/*
		$_ssh=new Project_Placement_Hosting_Ssh('slave');
		if( @$_SERVER['HTTP_HOST'] != 'cnm.local' ){
			if(!is_file('/data/ip/master.txt')){
				throw new Core_Ssh_Exception('Can\'t get IP for master');
			}
			Core_Files::getContent( $_strMasterIp, '/data/ip/master.txt' );
		}
		return $_ssh->ssh()->execCmd( 'sudo /usr/bin/rsync -uvroghtl --delete-after --password-file=/usr/local/etc/rsyncd.scrt synccluster@'.$_strMasterIp.'::'.$group.' /data/www/'.$group );
	*/
	}

	/**
	 * Run ssh commands
	 * @param $_strCmd
	 * @return bool|string
	 */
	public final function execCmd( $_strCmd ){
		if(empty($_strCmd)){
			return false;
		}
		$ssh=$this->_connectSSH2();
		if( $ssh===false ){
			return false;
		}
		/*---------------------------------------------*/
		if( $this->_withLogger ){
			$this->_start=microtime(true)-$this->_start;
			$this->_logger->info('execCmd  '.$_strCmd.': '.$this->_start );
			$this->_start=microtime(true);
		}
		/*---------------------------------------------*/
		return $ssh->exec( $_strCmd );
		/*
		$_stream=@ssh2_exec( $this->_connection, $_strCmd );
		stream_set_blocking($_stream, true);
		$_res=stream_get_contents($_stream);
		return $_res;
		*/
	}
}



class fakeConnection{
	public function __construct(){}
	public function __call($methodName, $args){
		var_dump( $methodName. var_export( $args ));
		return $this;
	}
}


?>