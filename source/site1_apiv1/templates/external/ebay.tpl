	<ol>
		<li>
			<label>eBay Affiliate ID (CampID): <em>*</em></label>
			<input size="40" class="required" type="text" name="arrCnt[{$i.flg_source}][settings][affiliate_id]" value="{if !empty($arrCnt.{$i.flg_source}.settings.affiliate_id)}{$arrCnt.{$i.flg_source}.settings.affiliate_id}{else}{/if}"/>
			<a style="text-decoration:none" class="Tips" title="This option is not required but you will only earn affiliate commission if you enter your Ebay affiliate ID."><b> ?</b></a>
		</li>
		<li>
			<label>Country: <em>*</em></label>
				<select class="required" name="arrCnt[{$i.flg_source}][settings][country]">
					<option value="0" {if $arrCnt.{$i.flg_source}.settings.country == "0"}selected="selected"{/if} >United States</option>
					<option value="2" {if $arrCnt.{$i.flg_source}.settings.country == "2"}selected="selected"{/if} >Canada</option>
					<option value="3" {if $arrCnt.{$i.flg_source}.settings.country == "3"}selected="selected"{/if} >United kingdom</option>
					<option value="15" {if $arrCnt.{$i.flg_source}.settings.country == "15"}selected="selected"{/if} >Australia</option>
					<option value="16" {if $arrCnt.{$i.flg_source}.settings.country == "16"}selected="selected"{/if} >Austria</option>
					<option value="23" {if $arrCnt.{$i.flg_source}.settings.country == "23"}selected="selected"{/if} >Belgium (French)</option>
					<option value="71" {if $arrCnt.{$i.flg_source}.settings.country == "71"}selected="selected"{/if} >France</option>
					<option value="77" {if $arrCnt.{$i.flg_source}.settings.country == "77"}selected="selected"{/if} >Germany</option>
					<option value="100" {if $arrCnt.{$i.flg_source}.settings.country == "100"}selected="selected"{/if} >eBay Motors</option>
					<option value="101" {if $arrCnt.{$i.flg_source}.settings.country == "101"}selected="selected"{/if} >Italy</option>
					<option value="123" {if $arrCnt.{$i.flg_source}.settings.country == "123"}selected="selected"{/if} >Belgium (Dutch)</option>
					<option value="146" {if $arrCnt.{$i.flg_source}.settings.country == "146"}selected="selected"{/if} >Netherlands</option>
					<option value="186" {if $arrCnt.{$i.flg_source}.settings.country == "186"}selected="selected"{/if} >Spain</option>
					<option value="193" {if $arrCnt.{$i.flg_source}.settings.country == "193"}selected="selected"{/if} >Switzerland</option>
					<option value="196" {if $arrCnt.{$i.flg_source}.settings.country == "196"}selected="selected"{/if} >Taiwan</option>
					<option value="223" {if $arrCnt.{$i.flg_source}.settings.country == "223"}selected="selected"{/if} >China</option>
					<option value="203" {if $arrCnt.{$i.flg_source}.settings.country == "203"}selected="selected"{/if} >India</option>
				</select>
		</li>
		<li>
			<label>Language: <em>*</em></label>
				<select class="required" name="arrCnt[{$i.flg_source}][settings][lang]">
					<option value="en-US" {if $arrCnt.{$i.flg_source}.settings.lang == "en-US"}selected="selected"{/if} >English</option>
					<option value="de" {if $arrCnt.{$i.flg_source}.settings.lang == "de"}selected="selected"{/if} >German</option>
					<option value="fr" {if $arrCnt.{$i.flg_source}.settings.lang == "fr"}selected="selected"{/if} >French</option>
					<option value="it" {if $arrCnt.{$i.flg_source}.settings.lang == "it"}selected="selected"{/if} >Italian</option>
					<option value="es" {if $arrCnt.{$i.flg_source}.settings.lang == "es"}selected="selected"{/if} >Spanish</option>
					<option value="nl" {if $arrCnt.{$i.flg_source}.settings.lang == "nl"}selected="selected"{/if} >Dutch</option>
					<option value="cn" {if $arrCnt.{$i.flg_source}.settings.lang == "cn"}selected="selected"{/if} >Chinese</option>
					<option value="tw" {if $arrCnt.{$i.flg_source}.settings.lang == "tw"}selected="selected"{/if} >Taiwanese</option>
				</select>
		</li>
		<li>
			<label>Sort results by: <em>*</em></label>
				<select class="required" name="arrCnt[{$i.flg_source}][settings][sortby]">
					<option value="bestmatch" {if $arrCnt.{$i.flg_source}.settings.sortby == "bestmatch"}selected="selected"{/if} >Best Match</option>
					<option value="&fsop=1&fsoo=1" {if $arrCnt.{$i.flg_source}.settings.sortby == "&fsop=1&fsoo=1"}selected="selected"{/if} >Time: ending soonest</option>
					<option value="&fsop=2&fsoo=2" {if $arrCnt.{$i.flg_source}.settings.sortby == "&fsop=2&fsoo=2"}selected="selected"{/if} >Time: newly listed</option>
					<option value="&fsop=34&fsoo=1" {if $arrCnt.{$i.flg_source}.settings.sortby == "&fsop=34&fsoo=1"}selected="selected"{/if} >Price + Shipping: lowest first</option>
					<option value="&fsop=34&fsoo=2" {if $arrCnt.{$i.flg_source}.settings.sortby == "&fsop=34&fsoo=2"}selected="selected"{/if} >Price + Shipping: highest first</option>
					<option value="&fsop=3&fsoo=2" {if $arrCnt.{$i.flg_source}.settings.sortby == "&fsop=3&fsoo=2"}selected="selected"{/if} >Price: highest first</option>
				</select>
		</li>
	</ol>