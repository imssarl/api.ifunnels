<?php


/**
 * Media File Converter
 * @internal конвертит из одного формата в другой (с изменением размера для видео) для аудио и видео
 */

deFine( 'DIR_MENCODER', '/usr/bin/' );
deFine( 'DIR_MCTEMP', DIR_USERS.'mc_tmp/' );
deFine( 'DIR_MCPIDS', DIR_USERS.'mc_pids/' );
deFine( 'M_MENCODER_VER', 23466 ); //начиная с r23982 убрали -lavfopts i_certify_that_my_video_stream_does_not_use_b_frames

class Core_Media_Converter extends Core_Media_Driver {
	private $objS3; // если для хранения используем amazonS3
	private $current_task=array(); // данные с которыми работаем
	private $src='';

	private $objL; // класс логгирования действий
	public $pid=0; // пид процесса

	public function __construct( $_arrTask=array() ) {
		if ( !$this->get_pid() ) {
			return;
		}
		parent::__construct();
		$this->objL=new logger( $this );
		if ( !$this->set_task( $_arrTask ) ) {
			return;
		}
		if ( deFined( 'M_ST_TYPE' )&&M_ST_TYPE=='S3' ) {
			$this->objS3=new s3();
		}
	}

	public function set_task( $_arrTask=array() ) {
		if ( empty( $_arrTask ) ) {
			return false;
		}
		$this->current_task=$_arrTask;
		return true;
	}

	public function start_process() {
		$this->objL->accumulation( 'child process started. pid is '.$this->pid );
		if ( empty( $this->current_task ) ) {
			$this->objL->accumulation( 'task data is empty' );
			$this->objL->flush_log();
			return false;
		}
		$this->objL->accumulation( 'task data is: '.print_r( $this->current_task, true ) );
		$_s=$this->get_microtime();
		if ( $this->check_task() ) {
			Core_Sql::setUpdate( 'f_files', array( 'id'=>$this->current_task['id'], 'flg_status_handling'=>2 ) );
			if ( !$this->process_task( $arrRes ) ) {
				Core_Sql::setUpdate( 'f_files', array( 'id'=>$this->current_task['id'], 'flg_status_handling'=>3 ) );
			} else {
				Core_Sql::setUpdate( 'f_files', $arrRes );
			}
		} else {
			Core_Sql::setUpdate( 'f_files', array( 'id'=>$this->current_task['id'], 'flg_status_handling'=>3 ) );
		}
		$this->objL->flush_log();
		$this->objL->accumulation( 'end child process. duration of process: '.sprintf( "%.4f", $this->get_microtime()-$_s ).'. pid is '.$this->pid );
	}

	private function get_pid() {
		$this->pid=getmypid();
		if ( !$this->pid ) {
			return false;
		}
		return true;
	}

	private function check_task() {
		if ( empty( $this->current_task['extension'] ) ) {
			$this->objL->accumulation( 'file extension not specified' );
			return false;
		}
		if ( !in_array( $this->current_task['extension'], $this->m_ext_allowed[$this->current_task['flg_type']] ) ) {
			$this->objL->accumulation( 'file type not supported or no valid' );
			return false;
		}
		$this->src=$this->m_dirs_path[$this->current_task['flg_type']].$this->current_task['sys_file'];
		if ( !file_exists( $this->src ) ) {
			$this->objL->accumulation( 'file not found at '.$this->src );
			return false;
		}
		return true;
	}

	// короч конвертим пока только видeо и только в flv
	// все параметры - харкод
	// потом можно сделать интерфейс для задания параметров для расширений
	private function process_task( &$arrRes ) {
		if ( $this->m_type_txt!='video' ) {
			$this->objL->accumulation( "false m_types for ".$this->current_task['sys_file'] );
			return false;
		}
		// пробуем улучшить качество
		if ( !$this->sys_mencoder() ) {
			$this->objL->accumulation( "false sys_mencoder for ".$this->src );
			return false;
		}
		// делаем flv
		if ( !$this->sys_toflv() ) {
			$this->objL->accumulation( "false sys_toflv for ".$this->src );
			return false;
		}
		// подготавливаем данные и загоняем их в БД
		if ( !$this->set_fileinfo( $arrRes ) ) {
			$this->objL->accumulation( "false set_fileinfo for ".$this->src );
			return false;
		}
		$this->save_into_s3_storage();
		$this->objL->accumulation( "all done for ".$this->src );
		return true;
	}

	// из оригинала в avi (для скриншотов и длительности видео)
	private function sys_mencoder() {
		$_bool=$this->shell_execute( $_arrRes, 
			DIR_MENCODER.'mencoder '.$this->src.
			' -af volnorm -srate 44100 -oac mp3lame -ovc lavc -lavcopts vcodec=h263p:vhq:vmax_b_frames=0:vbitrate=1200 -o '.
			DIR_MCTEMP.$this->current_task['sys_name'].'.avi' 
		);
		$this->objL->accumulation( join( "\n", $_arrRes ) );
		return $_bool;
	}

	// из оригинала в flv (для просмотра пользователем)
	private function sys_toflv() {
		$_strCmd=DIR_MENCODER.'mencoder '.$this->src.' -o '.$this->m_dirs_path[$this->current_task['flg_type']].'flv/'.$this->current_task['sys_name'].'.flv';
		$_strCmd.=' -ovc lavc';
		$_strCmd.=' -of lavf';
		$_strCmd.=' -vf scale=400:290';
		$_strCmd.=' -srate 22050';
		$_strCmd.=' -oac mp3lame';
		$_strCmd.=' -lavcopts vcodec=flv:keyint=50:vbitrate=500:mbd=2:mv0:trell:v4mv:cbp:last_pred=3';
		if ( M_MENCODER_VER<23980 ) {
			$_strCmd.=' -lavfopts i_certify_that_my_video_stream_does_not_use_b_frames';
		}
		if ( $_arrDta['extension']=='wmv' ) {
			$_strCmd.=' -lavfopts format=flv';
			$_strCmd.=' -lameopts aq=0:highpassfreq=-1:lowpassfreq=-1:vbr=2:q=6';
			$_strCmd.=' -demuxer lavf';
		} else {
			$_strCmd.=' -lameopts abr:br=64';
			$_strCmd.=' -ofps 25';
		}
		/*$_strSrc=$this->m_dirs_path[$_arrDta['flg_type']].'flv/'.$_arrDta['sys_name'].'.flv';
		$_strCmd=DIR_MENCODER.'mencoder '.$this->src.' -ofps 25 -o '.$_strSrc.' -of lavf -oac mp3lame -lameopts abr:br=64 -srate 22050 -ovc lavc '.(M_MENCODER_VER>23980?'':'-lavfopts i_certify_that_my_video_stream_does_not_use_b_frames ').'-lavcopts vcodec=flv:keyint=50:vbitrate=500:mbd=2:mv0:trell:v4mv:cbp:last_pred=3 -vf scale=360:270';*/
		$_bool=$this->shell_execute( $_arrRes, $_strCmd );
		$this->objL->accumulation( join( "\n", $_arrRes ) );
		return $_bool;
	}

	private function set_fileinfo( &$arrUpd ) {
		$arrUpd=array(
			'id'=>$this->current_task['id'],
			'flg_status_handling'=>0,
			'converted'=>time()
		);
		// все данные и превьюхи берём из улучшеного avi-файла (может стоит это всё вытягивать из оригинала? TODO!!!)
		$_strSrc=DIR_MCTEMP.$this->current_task['sys_name'].'.avi';
		if ( !$this->d_getvideoinfo( $_arrInfo, $_strSrc ) ) {
			$this->objL->accumulation( 'd_getvideoinfo false at '.$_strSrc.' output: '.print_r( $_arrInfo['err'], true ) );
		} else {
			$arrUpd['duration']=$_arrInfo['duration_sec'];
			$this->objL->accumulation( 'd_getvideoinfo successful' );
		}
		// получаем тамнэйлы
		if ( !$this->d_getvideoframe( $_strSrc, $this->m_dirs_path[$this->current_task['flg_type']], $this->current_task['sys_name'], $arrUpd['duration'] ) ) {
			$this->objL->accumulation( 'd_getvideoframe false at '.$_strSrc );
		} else {
			$this->objL->accumulation( 'd_getvideoframe successful' );
		}
		$this->objL->accumulation( 'fileinfo: '.print_r( $arrUpd, true ) );
		return true;
	}

	// закидываем флэшку на s3
	private function save_into_s3_storage() {
		if ( !is_object( $this->objS3 ) ) {
			$this->objL->accumulation( 'save_into_s3_storage: not finded $this->objS3' );
			return false;
		}
		if ( !$this->objS3->m3_get_data( $_arrRes, $this->objS3->putObject(
			$this->current_task['sys_name'].'.flv', 
			file_get_contents( $this->m_dirs_path[$this->current_task['flg_type']].'flv/'.$this->current_task['sys_name'].'.flv' ), 
			$this->objS3->current_bucket, 
			'public-read', 
			'application/octet-stream'
		) ) ) {
			$this->objL->accumulation( 'save_into_s3_storage: fail putObject or m3_get_data' );
			return false;
		}
		$this->objL->accumulation( 'save_into_s3_storage: successful stored' );
		return true;
	}
}
?>