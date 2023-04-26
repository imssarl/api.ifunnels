<?php
class Project_Submission_Driver_EA extends Project_Submission_Driver_Abstract {

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



				$name = $article['author']." ".$article['author_lname'];
				
				$str="email=".$login['username']."&pass=".$login['password']."&newlogin=1&jscheck=1";
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $login['url']."index.php");
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $str);
				curl_setopt ($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				$response = curl_exec($ch);
				
				
				curl_setopt($ch, CURLOPT_URL, $login['url']."index.php");
				$response = curl_exec($ch);
				
				$content = $response;
				$search = $name;
				
				$pos = strpos($content, $search);
				
				$idstart = '<input type="hidden" name="id" value="';//for original
				$idend = '"';
				$passstart = '<input type="hidden" name="pass" value="';//for original
				$passend = '"';
			
	//			$startname='<p><strong>';
	//			$endname='</strong>';
				
				$str = str_replace(array("\n","\r","\t"),array("","",""),$response);
				preg_match_all("|(".$idstart."(.*)".$idend.")|U",$str, $out1);
				preg_match_all("|(".$passstart."(.*)".$passend.")|U",$str, $out2);
	//			preg_match_all("|(".$startname."(.*)".$endname.")|U",$str, $out3);
				
				if ($pos == false)
				{
					$str2="pass=".$out2[2][0]."&id=".$out1[2][0]."&add_alt_author_first=".urlencode($article['author'])."&add_alt_author_last=".urlencode($article['author_lname'])."&verify=1&add_alt=1&act=addAltAuth";
					curl_setopt($ch, CURLOPT_URL, $login['url']."profile-manager/add_alternate.php");
					curl_setopt($ch, CURLOPT_POST, 1);
					curl_setopt($ch, CURLOPT_POSTFIELDS, $str2);
					curl_setopt ($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					$response = curl_exec($ch);
					
					$content = $response;
					$search = $name.' is already in use.';
					
					$position = strpos($content, $search);
				}
				
				//$content = $response;
				//$search   = '<div id="error_message">'.$name.' is already in use.<br>';
			//	print_r($out3); die();
				if($pos != false || $position == false)
				{
						if($article["biography_html"]!="")
						{
							$biography = urlencode(html_entity_decode($article["biography_html"]));
						}
						else
						{
							$biography = urlencode(html_entity_decode($article["biography"]));
						}
				
					$str2="category=".$article['category_id']."&title=".urlencode($article['title'])."&body=".urlencode(html_entity_decode($article['body']))."&sig=".$biography."&summary=".urlencode(html_entity_decode($article['summary']))."&keywords=".urlencode($article['keyword'])."&article_author=".urlencode($article['author'])." ".urlencode($article['author_lname'])."&agree=1&add_entry=1";
					//die($str2);
					///die($login['url']."submit.php?id=".$out1[2][0]."&pass=".$out2[2][0].""); 
					curl_setopt($ch, CURLOPT_URL, $login['url']."submit.php?id=".$out1[2][0]."&pass=".$out2[2][0]."");
					curl_setopt($ch, CURLOPT_POST, 1);
					curl_setopt($ch, CURLOPT_POSTFIELDS, $str2);
					curl_setopt ($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					$response = curl_exec($ch);
					
					$content = $response;
					$search = '<div id="error_message">';
					
					$error_pos = strpos($content, $search);
				
					if($error_pos == false)
					{
						$sql = "update `".TABLE_PREFIX."submission` set schedule='".$today."',isSubmit='Y' where id=".$article['id'];
						$asm_db->modify($sql);
					}
					else
					{				
						$errorstart = '<div id="error_message">';//for original
						$errorend = '</div>';
						
						$str = str_replace(array("\n","\r","\t"),array("","",""),$response);
						preg_match_all("|(".$errorstart."(.*)".$errorend.")|U",$str, $out1);
						//echo $out1[2][0];
						
						$log=str_replace('"',' ',$out1[2][0]);
						
						$sql = 'update `'.TABLE_PREFIX.'submission` set schedule="'.$today.'",error="Y",log="'.$log.'" where id='.$article["id"];
					
						$asm_db->modify($sql);
					}
				//echo $response; die();
					curl_close($ch);
					
					
				}
				else
				{
					$search = "Author name Name [".$name."] is already used by another user";
					$sql = "update `".TABLE_PREFIX."submission` set schedule='".$today."',error='Y',log='".$search."' where id=".$article['id'];
					
					$asm_db->modify($sql);
				}

?>