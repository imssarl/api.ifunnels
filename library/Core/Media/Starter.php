<?php


/**
 * Converter Daemon
 * @internal демон для запуска конвертера медиа файлов
 */


class Core_Media_Starter extends Core_Services {
	private $max_succ_iteration=200; // если скрипт не вырубать то появляются проблемы с переполнением памяти (проверить для новой версии TODO!!!)
	private $tasks=array(); // полученные из бд записи о файлах на конвертацию

	private $fork_allowed=true; // использовать ветвление для многопоточной обработки файлов (true)
	private $childs=array(); // стэк с пидами запущенных дочерних процессов
	private $max_childs=3; // максимально возможное кол-во дочерних процессов

	private $objL; // класс логгирования действий
	public $pid=0; // пид родительского процесса

	public function __construct() {
		if ( $this->check_parent_process() ) {
			return;
		}
		$this->objL=new logger( $this );
		$this->objL->accumulation( DIR_MCPIDS.'converter_started successful created' );
	}

	public function set_max_childs( $_int=0 ) {
		if ( empty( $_int ) ) {
			return;
		}
		$this->max_childs=$_int;
	}

	public function set_max_succ_iteration( $_int=0 ) {
		if ( empty( $_int ) ) {
			return;
		}
		$this->max_succ_iteration=$_int;
	}

	public function set_fork_allowed( $_bool=true ) {
		$this->fork_allowed=$_bool;
	}

	// запускаем процессы
	public function run() {
		if ( empty( $this->pid ) ) {
			return false;
		}
		$i=0;
		while( true ) {
			if ( $this->set_tasks() ) {
				$i++;
			}
			if ( $i==$this->max_succ_iteration ) {
				$this->objL->accumulation( 'converter parent process stopped on $this->max_succ_iteration=='.$this->max_succ_iteration );
				if ( $this->d_rmfile( DIR_MCPIDS.'converter_started' ) ) {
					$this->objL->accumulation( DIR_MCPIDS.'converter_started successful unlinked' );
				}
				$this->objL->flush_log();
				Core_Sql::disconnect();
				exit;
			}
			sleep(3);
		}
	}

	private function set_tasks() {
		if ( !$this->get_tasks() ) {
			return false;
		}
		$_s=$this->get_microtime();
		foreach( $this->tasks as $v ) {
			if ( $this->fork_allowed ) {
				$this->objL->accumulation( 'go branching process' );
				$this->branching( $v );
			} else { // протестить вариант без ветвления
				$this->objL->accumulation( 'go single process' );
				$obj=new media_converter( $v );
				$obj->start_process();
			}
		}
		$this->objL->accumulation( 'end of set_tasks iteration in '.sprintf( "%.4f", $this->get_microtime()-$_s ).' sec, '.count( $this->tasks ).' task are started' );
		$this->objL->flush_log();
	}

	function get_tasks() {
		$_arr=Core_Sql::getAssoc( 'SELECT *, IF(LENGTH(sys_name)>0,CONCAT(sys_name,".",extension),"") sys_file FROM f_files WHERE flg_status_handling=1' );
		if ( empty( $_arr ) ) {
			$this->objL->accumulation( 'no task selected' );
			return false;
		}
		$this->objL->accumulation( 'get '.count( $_arr ).' sql task(s)' );
		$this->tasks=$_arr;
		return true;
	}

	private function branching( &$arrTask ) {
		$this->free_pid_waiting();
		$pid=pcntl_fork(); // ветвление
		if ( $pid==-1 ) { // ошибка
			$this->objL->accumulation( 'can\'t fork process' );
			$this->objL->flush_log();
		} elseif ( empty( $pid ) ) { // нить
			get_sql_instance( true ); // новый коннект к БД (т.к. старый может быть закрыт при другого чайлда выходе чайлда)
			$obj=new media_converter( $arrTask );
			$obj->start_process();
			exit;
		} else { // родитель
			get_sql_instance( true ); // новый коннект к БД (т.к. старый может быть закрыт при выходе чайлда)
			$this->objL->accumulation( 'successful fork process. pid is '.$pid );
			$this->childs[$pid]=1;
		}
		// ждать завершения всех нитей процесса незачем
		// т.к. скрипт висит в памяти постоянно
	}

	// тут ожидаем когда количество процессов уменьшится до $this->max_childs-1
	private function free_pid_waiting() {
		do {
			foreach( $this->childs as $pid=>$_int ) { // определяем работающие нити
				if ( pcntl_waitpid( $pid, $status, WNOHANG )==$pid ) {
					unSet( $this->childs[$pid] ); // нить завершила работу
				}
			}
			sleep(0.1);
			// если работающих нитей не меньше максимального количества - ждём дальше
		} while ( !( count( $this->childs )<$this->max_childs ) );
	}

	// проверяет, не запущен-ли уже родительский процесс
	private function check_parent_process() {
		$this->pid=getmypid();
		if ( !$this->pid||file_exists( DIR_MCPIDS.'converter_started' ) ) {
			$this->pid=0;
			return true;
		}
		$_fp=fopen( DIR_MCPIDS.'converter_started', 'w' );
		return !fclose( $_fp );
	}
}
?>