<?php
class site1 extends Core_Module {

	public function set_cfg() {
		$this->inst_script=array(
			'module'=>array( 'title'=>'CNM Frontend', ),
			'actions'=>array(),
		);
	}

	public function before_run_parent() {

	}

	public function after_run_parent() {

	}

	private function temporaryUnavailable() {
		$this->out['temporaryUnavailable']=true;
		if ( !empty( $_GET['personnel'] )&&$_GET['personnel']=='Rdh4325dhUhfho23ejqfq2fHJEhd32' ) {
			$this->out['temporaryUnavailable']=false;
			$_SESSION['personnel']='Rdh4325dhUhfho23ejqfq2fHJEhd32';
		} elseif ( !empty( $_SESSION['personnel'] )&&$_SESSION['personnel']=='Rdh4325dhUhfho23ejqfq2fHJEhd32' ) {
			$this->out['temporaryUnavailable']=false;
		}
	}

	public function breadcrumb() {}

	public function head() {}
}
?>