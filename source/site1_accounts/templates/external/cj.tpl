	<ol>
		<li>
			<label>Commission Junction API ID: <em>*</em></label>
			<input size="40" class="required" type="text" name="arrCnt[{$i.flg_source}][settings][appkey]" value="{if !empty($arrCnt.{$i.flg_source}.settings.appkey)}{$arrCnt.{$i.flg_source}.settings.appkey}{else}{/if}"/>
			<a style="text-decoration:none" class="Tips" title="This setting is required for the Commission Junction module to work!"><b> ?</b></a>
		</li>
		<li>
			<label>Website ID (PID): <em>*</em></label>
			<input size="40" class="required" type="text" name="arrCnt[{$i.flg_source}][settings][webid]" value="{if !empty($arrCnt.{$i.flg_source}.settings.webid)}{$arrCnt.{$i.flg_source}.settings.webid}{else}{/if}"/>
			<a style="text-decoration:none" class="Tips" title="This setting is required for the Commission Junction module to work!"><b> ?</b></a>
		</li>
		<li>
			<label>Advertisers: <em>*</em></label>
			<input size="40" class="required" type="text" name="arrCnt[{$i.flg_source}][settings][advertisers]" value="{if !empty($arrCnt.{$i.flg_source}.settings.advertisers)}{$arrCnt.{$i.flg_source}.settings.advertisers}{else}joined{/if}"/>
			<a style="text-decoration:none" title="Decides which advertisers are used to display products. Possible Values:<br/><strong>joined</strong> - This special value restricts the search to advertisers which you have signed up for in your CJ account.<br/><strong>not-joined</strong> - This special value restricts the search to advertisers with which you do not have a relationship.<br/><strong>CIDs</strong> - You may provide list of one or more advertiser CIDs, separated by commas, to limit the results to a specific sub-set of merchants.<br/><strong>Empty String</strong> - You may leave the field empty to remove any advertiser-specific restrictions on the search.<br/><strong>Important: You can only earn commission for advertisers you have signed up for in your CJ account!</strong>" class="Tips" ><b> ?</b></a>
		</li>		
		<li>	
			<label>Sort by: <em>*</em></label>
				<select class="required" name="arrCnt[{$i.flg_source}][settings][sortby]">
					<option value="name" {if $arrCnt.{$i.flg_source}.settings.sortby == "name"}selected="selected"{/if} >Name</option>
					<option value="price" {if $arrCnt.{$i.flg_source}.settings.sortby == "price"}selected="selected"{/if} >Price</option>
					<option value="salePrice" {if $arrCnt.{$i.flg_source}.settings.sortby == "salePrice"}selected="selected"{/if} >salePrice</option>
					<option value="manufacturer" {if $arrCnt.{$i.flg_source}.settings.sortby == "manufacturer"}selected="selected"{/if} >Manufacturer</option>
				</select>
		</li>		
		<li>	
			<label>Sort Order: <em>*</em></label>
				<select class="required" name="arrCnt[{$i.flg_source}][settings][sortorder]">
					<option value="asc" {if $arrCnt.{$i.flg_source}.settings.sortorder == "asc"}selected="selected"{/if} >Ascending</option>
					<option value="desc" {if $arrCnt.{$i.flg_source}.settings.sortorder == "desc"}selected="selected"{/if} >Descending</option>		
				</select>
		</li>		
		<li>
			<label>Minimum Price: <em>*</em></label>
				<input size="4o" class="required" type="text" name="arrCnt[{$i.flg_source}][settings][lowprice]" value="{if !empty($arrCnt.{$i.flg_source}.settings.lowprice)}{$arrCnt.{$i.flg_source}.settings.lowprice}{else}{/if}"/>
		</li>
		<li>
			<label>Maximum Price: <em>*</em></label>
				<input size="40" class="required" type="text" name="arrCnt[{$i.flg_source}][settings][highprice]" value="{if $arrCnt.{$i.flg_source}.settings.highprice!=''}{$arrCnt.{$i.flg_source}.settings.highprice}{else}{/if}"/>
		</li>
		<li>
			<label>Skip Products If: <em>*</em></label>
				<select class="required" name="arrCnt[{$i.flg_source}][settings][skip]">
					<option value="" {if $arrCnt.{$i.flg_source}.settings.skip == ""}selected="selected"{/if} >Don't skip</option>
					<option value="nodesc" {if $arrCnt.{$i.flg_source}.settings.skip == "nodesc"||empty($arrCnt.{$i.flg_source}.settings.skip)}selected="selected"{/if} >No description found</option>
					<option value="noimg" {if $arrCnt.{$i.flg_source}.settings.skip == "noimg"}selected="selected"{/if} >No thumbnail image found</option>
					<option value="nox" {if $arrCnt.{$i.flg_source}.settings.skip == "nox"}selected="selected"{/if} >No description OR no thumbnail</option>
				</select>
		</li>		
		<li>
			<label>Country: <em>*</em></label>
				<select class="required" name="arrCnt[{$i.flg_source}][settings][lang]">
					<option value="en" {if $arrCnt.{$i.flg_source}.settings.lang == "en"}selected="selected"{/if} >English</option>
					<option value="de" {if $arrCnt.{$i.flg_source}.settings.lang == "de"}selected="selected"{/if} >German</option>
					<option value="fr" {if $arrCnt.{$i.flg_source}.settings.lang == "fr"}selected="selected"{/if} >French</option>
					<option value="it" {if $arrCnt.{$i.flg_source}.settings.lang == "it"}selected="selected"{/if} >Italian</option>
					<option value="es" {if $arrCnt.{$i.flg_source}.settings.lang == "es"}selected="selected"{/if} >Spanish</option>
					<option value="nl" {if $arrCnt.{$i.flg_source}.settings.lang == "nl"}selected="selected"{/if} >Dutch</option>
					<option value="cn" {if $arrCnt.{$i.flg_source}.settings.lang == "cn"}selected="selected"{/if} >Chinese</option>
					<option value="tw" {if $arrCnt.{$i.flg_source}.settings.lang == "tw"}selected="selected"{/if} >Taiwanese</option>								
				</select>
		</li>		
	</ol>