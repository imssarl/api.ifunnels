<?php
class Project_Submission_Driver_AD extends Project_Submission_Driver_Abstract {

	private function login( $_arrDta=array() ) {
		$this->_client->setUri( 'http://'.Project_Submission::$_directoryUrl[$_arrDta['flg_type']].'/login2submitart.php' );
		$this->_client->setParameterPost( array(
			'f_username'=>$_arrDta['username'],
			'f_password'=>$_arrDta['password'],
			'action'=>'login',
			'B7'=>'Submit',
		) );
		$this->_client->request( 'POST' );
	}

	private function createPenname( $_arrDta=array() ) {
		$this->_client->setUri( 'http://'.Project_Submission::$_directoryUrl[$_arrDta['flg_type']].'/penname.php' );
		$this->_client->setParameterPost( array(
			'f_penname'=>$_arrDta['author_fname'], /*можнет fname+lname?*/
			'act'=>'add',
			'submit'=>'Submit',
		) );
		$_strRes=$this->_client->request( 'POST' );
		$this->_error='This Pen Name was already used by another Author';
		return !strpos( $_strRes, $this->_error );
	}

	public function placeContent( $_arrDta=array() ) {
		$this->login( $_arrDta );
		if ( !$this->createPenname() ) {
			return false;
		}
		$this->_client->setUri( 'http://'.Project_Submission::$_directoryUrl[$_arrDta['flg_type']].'/submitarticles.php' );
		$_strRes=$this->_client->request( 'POST' );
		// тут надо вычислить нужный f_pennameid из $_strRes
		$this->_client->setParameterPost( array(
			'f_pennameid'=>$_intId,
			'f_categoryid'=>$_arrDta['category_id'],
			'f_arttitle'=>$_arrDta['title'],
			'f_artsummary'=>$_arrDta['summary'],
			'f_artbody'=>$_arrDta['body'],
			'f_artres'=>(empty( $_arrDta['bio_html'] )? $_arrDta['bio_text']:$_arrDta['bio_html']),
			'f_artkey'=>$_arrDta['keywords'],
			'act'=>'add',
			'submit'=>'Submit',
		) );
		$_strRes=$this->_client->request( 'POST' );
		$this->_adapter->close();
		$this->_error='Sorry, this article has already been submitted to our directory. Please submit another article or choose another title';
		return !strpos( $_strRes, $this->_error );
	}
}
?>