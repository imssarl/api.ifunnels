<?php 

class Project_Options_Snippets {

	public function getSnippetShowPart($id) {
		$sql = "SELECT is_itm_enabled FROM hct_snippets WHERE id={$id}";
		$isITMenabled = Core_Sql::getCell($sql);
		if ( empty( $isITMenabled ) ){
			return false;
		}
		if ($isITMenabled=="Y")	{
			$showpart = $this->getITMSnippetShowPart($id);
		} else {
			$showpart = $this->getERSnippetShowPart($id);
		}

		$this->updatePartShown($showpart["id"]);
		return $showpart;
	}

	public function updatePartShown($id) {
		$data = array(
		"snippet_part_id" => intval($id),
		"date"			  => date("Y-m-d H:i:s")
		);
		$sid = Core_Sql::setInsert('hct_snippet_impression_details', $data);
		$this->updatePartEvent($id, "impressions");
		return $sid;
	}

	public function updatePartEvent($id, $event) {
		if (empty($id)){
			return false;
		}
		Core_Sql::setExec("UPDATE hct_snippet_parts SET {$event} = {$event}+1 WHERE id={$id}");
	}

	public function getITMSnippetShowPart($id) {
		$isranked = $this->checkRankingStarted($id);
		if (!$isranked) {
			$showpart = $this->getERSnippetShowPart($id);
		} else {
			$showpartid = $this->getSnippetShowPartOnITMBalancing($id);
			$showpart = Core_Sql::getRecord("SELECT p.* FROM hct_snippet_parts p	 WHERE p.id = {$showpartid} AND p.pause!=1 LIMIT 1");
		}
		return $showpart;
	}

	public function checkRankingStarted($id) {
		$rankedpart = Core_Sql::getRecord("SELECT d.id	FROM hct_snippet_parts AS p JOIN hct_snippets AS s ON s.id = p.snippet_id JOIN hct_snippet_click_details AS d ON d.snippet_part_id = p.id WHERE s.id = {$id} ");
		return ($rankedpart && $rankedpart>0);
	}


	public function getSnippetShowPartOnITMBalancing($id) {
		$rank["snippet_part_1"] = 4;
		$rank["snippet_part_2"] = 2;
		$rank["snippet_part_3"] = 1;
		$rank1 = $rank["snippet_part_1"]/$rank["snippet_part_3"];
		$rank2 = $rank["snippet_part_2"]/$rank["snippet_part_3"];
		$rank3 = 1;
		$part = $this->findTopThree($id);
		if (count($part) == 3) {
			if ($part[0]["ctr"] == $part[1]["ctr"] && $part[1]["ctr"] == $part[2]["ctr"]) {
				if(rand(0,1)==1) {
					$showid = $this->getRandomInLowers($id, $part, 0);
					return $showid;
				}
			} else if ($part[0]["ctr"] == $part[1]["ctr"]) {
				if(rand(0,1)==1) {
					$showid = $this->getRandomInLowers($id, $part, 0);
					return $showid;
				}
			} else if( $part[1]["ctr"] == $part[2]["ctr"]) {
				if(rand(0,1)==1) {
					$showid = $this->getRandomInLowers($id, $part, 0);
					return $showid;
				}
			}
		}
		if (count($part) < 3) {
			if (count($part) == 1) {
				$islastone = $this->checkIsLastShown($id, $part[0]["id"], ceil($rank1/$rank2));
				if ($islastone) {
					$showid = $this->getRandomInLowers($id, $part);
				} else {
					$showid = $part[0]["id"];
				}
			} else if (count($part) == 2) {
				if ($part[0]["shown"] >= $part[1]["shown"]*($rank1/$rank2) ) {
					$showidgenerated = false;
					if(rand(0,1)==1) {
						$showid = $this->getRandomInLowers($id, $part, 0);
						$showidgenerated = true;
					}
					if ($showidgenerated==false) {
						$islast2wasone = $this->checkIsLastShown($id, $part[0]["id"], ceil($rank1/$rank2));// dbt
						if ($islast2wasone) {
							$showid = $part[rand(0,1)]["id"];
						} else {
							$showid = $part[0]["id"];
						}
					}
				} else {
					if ($part[0]["ctr"] == $part[1]["ctr"])	{
						if(rand(0,1)==1) {
							$showid = $this->getRandomInLowers($id, $part, 0);
							return $showid;
						}
					}
					$islast4wasone = $this->checkIsLastShown($id, $part[0]["id"], ceil($rank1/$rank2));
					if ($islast4wasone)	{
						$showid = $this->getRandomInLowers($id, $part);
					} else {
						$showid = $part[0]["id"];
					}
				}
			}
		}elseif ($part[0]["shown"] >= $part[2]["shown"]*$rank1 ){
			if ($part[0]["shown"] >= $part[1]["shown"]*($rank1/$rank2) ) {
				if ($part[1]["shown"] >= $part[2]["shown"]*$rank2 ) {
					$showidgenerated = false;
					if ($part[0]["shown"] == $part[1]["shown"]*($rank1/$rank2) && $part[0]["shown"]== $part[2]["shown"]*$rank1) {
						if(rand(0,1)==1) {
							$showid = $this->getRandomInLowers($id, $part, 0);
							$showidgenerated = true;
						}
					}
					if ($showidgenerated==false) {
						$islastthird = $this->checkIsLastShown($id, $part[2]["id"], $rank3);
						if ($islastthird) {
							$showid = $this->getRandomInLowers($id, $part);
						} else {
							$showid = $part[2]["id"];
						}
					}
				} else {
					$showid = $part[1]["id"];
				}
			} else {
				$islast4wasone = $this->checkIsLastShown($id, $part[0]["id"], $rank1);
				if ($islast4wasone) {
					$myrand = rand(0,1);
					$showid = $part[$myrand*2]["id"];
				} else {
					$showid = $part[0]["id"];
				}
			}
		} else {
			if ($part[0]["shown"] >= $part[1]["shown"]*($rank1/$rank2) ) {
				$islast2wasone = $this->checkIsLastShown($id, $part[0]["id"], ceil($rank1/$rank2));
				if ($islast2wasone) {
					$showid = $part[rand(0,1)]["id"];
				} else {
					$showid = $part[0]["id"];
				}
			} else {
				$islast2wasone = $this->checkIsLastShown($id, $part[0]["id"], ceil($rank1));
				if ($islast2wasone) {
					$showid = $this->getRandomInLowers($id, $part, 2);
				} else {
					$showid = $part[0]["id"];
				}
			}
		}
		return $showid;
	}

	public function getRandomInLowers($id, $part, $leave=1, $onlyranked=false) {
		$cond = "";
		$totparts = Core_Sql::getCell("SELECT COUNT(*) FROM hct_snippet_parts p WHERE snippet_id = {$id} ");
		if ($onlyranked) {
			$whichparts = " AND clicks > 0 ";
		} else {
			$whichparts = "";
		}
		if ($totparts != false && $totparts > 3) {
			for ($i=0; $i < count($part)-$leave;$i++) {
				$cond .= " AND  id != ".$part[$i]["id"];
			}
		}
		$showid = Core_Sql::getCell("SELECT id FROM hct_snippet_parts p WHERE snippet_id = {$id} {$cond} {$whichparts} ORDER BY RAND() LIMIT 1");
		if (!$showid) {
			$showid = "0";
		}
		return $showid;
	}


	public function findTopThree($id) {
		$sql = "SELECT p.id,p.snippet_id, p.clicks AS clicks ,p.impressions AS shows, (p.clicks/p.impressions)*100 AS ctr"
		." FROM hct_snippet_parts p LEFT JOIN hct_snippets AS s ON s.id = p.snippet_id"
		." WHERE s.id = {$id} AND p.clicks > 0 AND p.pause!=1 GROUP BY p.id ORDER BY ctr DESC, p.id ASC LIMIT 3";
		$toprs = Core_Sql::getAssoc($sql);
		if (!$toprs) {
			return false;
		}
		
		$data = array();
		foreach ($toprs as $top) {
			$data[] = array(
			"id" 		=> $top["id"],
			"shown" 	=> $top["shows"],
			"clicked" 	=> $top["clicks"],
			"ctr"		=> $top["ctr"]
			);
		}
		return $data;
	}

	// refactoring by Rodion Konnov 17.08.2010
	// id последней показанной части сниппета
	public function getLastShownByIP( $id ){
		return Core_Sql::getCell( '
			SELECT snippet_part_id
			FROM hct_snippet_impression_details
			WHERE snippet_part_id IN(SELECT id FROM hct_snippet_parts WHERE snippet_id='.$id.')
			ORDER BY id DESC
			LIMIT 1
		' );
	}

	// refactoring by Rodion Konnov 17.08.2010
	// сколько раз в лимите повторялась искомая часть
	public function checkIsLastShown( $snippet, $part, $limit ) {
		$limit = ceil($limit);
		$index=Core_Sql::getCell( '
			SELECT COUNT(*)
			FROM (
				SELECT snippet_part_id de_id
				FROM hct_snippet_impression_details
				WHERE snippet_part_id IN(SELECT id FROM hct_snippet_parts WHERE snippet_id='.$snippet.')
				ORDER BY id DESC
				LIMIT '.$limit.'
			) derived
			WHERE derived.de_id='.$part.'
		' );
		return ($index=$limit);
	}

	public function getERSnippetShowPart($id) {
		$lastid = $this->getLastShownByIP($id);
		$newpart = false;
		if ($lastid) {
			$newpart = Core_Sql::getRecord( '
				SELECT p.* 
				FROM hct_snippet_parts p 
				LEFT JOIN hct_snippets s ON s.id=p.snippet_id 
				WHERE p.id>'.$lastid.' AND s.id='.$id.' AND p.pause!=1
				LIMIT 1
			' );
		}
		if (!$newpart) {
			$newpart =  Core_Sql::getRecord( '
				SELECT p.* 
				FROM hct_snippet_parts p 
				LEFT JOIN hct_snippets s ON s.id=p.snippet_id 
				WHERE s.id='.$id.' AND p.pause!=1
				LIMIT 1
			' );
		}
		return $newpart;
	}

	public function changeLinksWithTrackURLs($in, $partid) {
		$posstart = 0;
		$out = $in = html_entity_decode($in);
		while(($posofA = strpos(strtolower($in),"<a",$posstart)) !== false) {
			$posofAend = strpos($in, ">",$posofA+1);
			$posofAtagend = strpos(strtolower($in), "</a>",$posofA+1);
			if ($posofAend === false) {
				if ($posofAtagend === false) {
					return $in;
				} else {
					$posstart = $posofAtagend;
					continue;
				}
			} else {
				if ($posofAtagend === false) {
					return $in;
				} else {
					if ($posofAend > $posofAtagend) {
						$posstart = $posofAtagend;
						continue;
					}
				}
			}
			if ($posofA>=0) {
				$posofhref = strpos(strtolower($in),"href",$posofA+1);
				if ($posofhref>=0) {
					$posoflinkstarta = strpos($in,"'",$posofhref+1);
					$posoflinkstartb = strpos($in,'"',$posofhref+1);
					$linkstartposfound = false;
					if ($posoflinkstarta !== false && $posoflinkstartb !== false) {
						if ($posoflinkstarta < $posoflinkstartb) {
							$posoflinkstart = $posoflinkstarta+1;
							$linkquot = "'";
							$linkstartposfound = true;
						} else {
							$posoflinkstart = $posoflinkstartb+1;
							$linkquot = '"';
							$linkstartposfound = true;
						}
					} else if ($posoflinkstarta !== false) {
						$posoflinkstart = $posoflinkstarta+1;
						$linkquot = "'";
						$linkstartposfound = true;
					} else if ($posoflinkstartb !== false) {
						$posoflinkstart = $posoflinkstartb+1;
						$linkquot = '"';
						$linkstartposfound = true;
					} else {
						$linkstartposfound = false;
					}
					if ($linkstartposfound) {
						$posoflinkend = strpos($in,$linkquot,$posoflinkstart);
						if ($posoflinkend>0) {
							$link = substr($in,$posoflinkstart,$posoflinkend-$posoflinkstart);
							$posstart = $posoflinkend+1;
							$tracked = strpos($link,"snippetstrack.php?");
							if ($tracked<=0) {
								$posofAclose = strpos(strtolower($in),">",$posoflinkend);
								$posofAtagclose = strpos(strtolower($in),"</a>",$posofAclose);
								if ($posofAclose !== false && $posofAtagclose!== false)
								$atext = substr($in,$posofAclose+1,$posofAtagclose-$posofAclose-1);
								else
								$atext = "X";
								$posstart = $posoflinkstart;
								$trackURL = $this->getTrackURLForLink($link,$atext, $partid);
								$in = substr_replace($in,$trackURL,$posoflinkstart,$posoflinkend-$posoflinkstart);
							}
						} else {
							$posstart = $posoflinkstart;
						}
					} else {
						$posstart = $posofhref;
					}
				} else {
					$posstart = $posofA;
				}
			}
		}
		return $in;
	}

	public function getTrackURLForLink($link,$atext, $partid) {
		$sql = "SELECT * FROM  hct_snippet_trackurls WHERE snippet_part_id = {$partid}";
		$trk_rs = Core_Sql::getAssoc($sql);
		if ($trk_rs) {
			foreach ($trk_rs as $turl)	{
				if ($link == $turl["url"]) {
					$trackURL = "http://".$_SERVER['HTTP_HOST']."/cronjobs/getcontent.php?type_view=snippetstrack&id=$partid-".$turl["id"];
					return $trackURL;
				}
			}
		}
		$data = array(
			"snippet_part_id" => $partid,
			"url"	=> $link,
			"anchortext"	=> $atext
		);
		$id = Core_Sql::setInsert("hct_snippet_trackurls", $data);
		$trackURL = "http://".$_SERVER['HTTP_HOST']."/snippetstrack.php?id=$partid-$id";
		return $trackURL;
	}
	
	
	public function getTrackUrlToRedirect($redirect) {
		return Core_Sql::getCell("SELECT url FROM hct_snippet_trackurls WHERE id = {$redirect}");
	}	
	
	public function updatePartClicked($id,$lid) {
		$data = array(
			"snippet_part_id" 	=> $id,
			"date"				=> date("Y-m-d H:i:s"),
			"ip_address"		=> $_SERVER["REMOTE_ADDR"],
			"url_shown"			=> (!empty($_SERVER['HTTP_REFERER']))?$_SERVER['HTTP_REFERER'] : "",
			"trackurl_id"		=> $lid
		);
		$cid = Core_Sql::setInsert("hct_snippet_click_details", $data);
		$this->updatePartEvent($id, "clicks");
		return $cid;
	}	
	
}

?>