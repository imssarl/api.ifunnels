	<ol>
		<li>
			<label>Shopzilla API Key: <em>*</em></label>
			<input size="40" class="required" type="text" name="arrCnt[{$i.flg_source}][settings][appkey]" value="{if !empty($arrCnt.{$i.flg_source}.settings.appkey)}{$arrCnt.{$i.flg_source}.settings.appkey}{else}{/if}"/>
			<a style="text-decoration:none" class="Tips" title="This setting is required for the Shopzilla module to work!"><b> ?</b></a>
		</li>		
		<li>
			<label>Shopzilla Publisher ID: <em>*</em></label>
			<input size="40" class="required" type="text" name="arrCnt[{$i.flg_source}][settings][pubkey]" value="{if !empty($arrCnt.{$i.flg_source}.settings.pubkey)}{$arrCnt.{$i.flg_source}.settings.pubkey}{else}{/if}"/>
			<a style="text-decoration:none" class="Tips" title="This setting is required for the Shopzilla module to work!"><b> ?</b></a>
		</li>		
		<li>
			<label>Number of Offers: <em>*</em></label>
			<input size="40" class="required" type="text" name="arrCnt[{$i.flg_source}][settings][offers]" value="{if !empty($arrCnt.{$i.flg_source}.settings.offers)}{$arrCnt.{$i.flg_source}.settings.offers}{else}5{/if}"/>
		</li>		
		<li>	
			<label>Category: <em>*</em></label>
				<select class="required" name="arrCnt[{$i.flg_source}][settings][sort]">
					<option value="relevancy_desc" {if $arrCnt.{$i.flg_source}.settings.sort == "relevancy_desc"}selected="selected"{/if} >Sort by relevancy of results</option>
					<option value="price_asc" {if $arrCnt.{$i.flg_source}.settings.sort == "price_asc"}selected="selected"{/if} >Sort by price, ascending</option>
					<option value="price_desc" {if $arrCnt.{$i.flg_source}.settings.sort == "price_desc"}selected="selected"{/if} >Sort by price, descending</option>							
				</select>
		</li>		
		<li>
			<label>Minimum Price: <em>*</em></label>
			<input size="40" class="required" type="text" name="arrCnt[{$i.flg_source}][settings][minprice]" value="{if !empty($arrCnt.{$i.flg_source}.settings.minprice)}{$arrCnt.{$i.flg_source}.settings.minprice}{else}{/if}"/>
		</li>
		<li>
			<label>Maximum Price: <em>*</em></label>
			<input size="40" class="required" type="text" name="arrCnt[{$i.flg_source}][settings][maxprice]" value="{if !empty($arrCnt.{$i.flg_source}.settings.maxprice)}{$arrCnt.{$i.flg_source}.settings.maxprice}{else}{/if}"/>
		</li>		
	</ol>