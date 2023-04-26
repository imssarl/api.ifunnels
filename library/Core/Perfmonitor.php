<?php
/**
* Performance monitoring
*
* This function is for performacne monitoring of scripts
* usage:
* add after begin of monitoring process StartMeasure('process name');
* add before end of process EndMeasure('process name');
* use PerformanceReport(); at the end of script
* (use TOTAL process name for measure of total process and % for other)
*
* @package framework
* @author Serge Dzheigalo
* @copyright Activeunit Inc 2001-2004
* @version 1.0
*/

/**
* Getting micro-time
*/
function getmicrotime(){ 
   list($usec, $sec) = explode(" ",microtime()); 
   return ((float)$usec + (float)$sec); 
  } 

/**
* Starting measurement
*
* Starting measurement of time for specified block
*
* @param string $mpoint monitoring block name
*/
 function StartMeasure($mpoint) {
  global $perf_data;
  $tmp=getmicrotime();
  $perf_data[$mpoint]['START']=getmicrotime();
 }

/**
* Ending measurement
*
* Ending measurement of time for specified block
*
* @param string $mpoint monitoring block name
*/
 function EndMeasure($mpoint) {
  global $perf_data;
  if ( empty( $perf_data[$mpoint]['NUM'] ) ) {
	$perf_data[$mpoint]['NUM']=0;
  }
  $perf_data[$mpoint]['END']=getmicrotime();
  $perf_data[$mpoint]['TIME']=$perf_data[$mpoint]['END']-$perf_data[$mpoint]['START'];
  $perf_data[$mpoint]['NUM']++;
 }

/**
* Report builder
*
* Printing report for all blocks
*
* @param boolean $hidden n/a
*/
 function PerformanceReport( $hidden=true ) {
  global $perf_data;
  if ( $hidden ) echo "<!-- BEGIN PERFORMANCE REPORT\n";
  $tmp=array();
  foreach ($perf_data as $k => $v) {
  if (!empty( $perf_data['TOTAL']['TIME'] )) {
   $v['PROC']=((int)($v['TIME']/$perf_data['TOTAL']['TIME']*100*100))/100;
  }
  $tmp[]="$k (".$v['NUM']."): ".$v['TIME']." ".(empty( $v['PROC'] )?'':$v['PROC']."%");
  }
  echo implode("\n", $tmp);
  if ( $hidden ) echo "\n END PERFORMANCE REPORT -->";
 }

function sql_report() {
	if ( DEBUG_MODE==0 ) {
		return;
	}
	echo "\n<!-- BEGIN SQL REPORT\n";
	if ( DB_CONNECTION=='single' ) {
		$_intSum=0;
		foreach( $GLOBALS['CURRENT_SQL_QUERYS'] as $k=>$v ) {
			echo ($k+1).': '.$v['mess']."\n";
			$_intSum+=$v['time'];
		}
		echo 'full time of sql queries '.$_intSum;
	} elseif ( DB_CONNECTION=='replication' ) {
		foreach( $GLOBALS['CURRENT_SQL_QUERYS'] as $name=>$server ) {
			echo 'To '.$name.' querys '."\n";
			foreach( $server as $k=>$v ) {
				echo ($k+1).': '.$v."\n";
			}
		}
	}
	echo "\n END SQL REPORT -->";
}
?>