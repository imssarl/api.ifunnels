	<ol>
		<li>	
			<label>Web Services Token: <em>*</em></label>
			<input size="40" class="required" type="text" name="arrCnt[{$i.flg_source}][settings][appkey]" value="{if !empty($arrCnt.{$i.flg_source}.settings.appkey)}{$arrCnt.{$i.flg_source}.settings.appkey}{else}{/if}"/>
			<a target="_blank" href="http://www.linkshare.com" style="text-decoration:none" class="Tips" title="This setting is required for the Linkshare module to work!"><b> ?</b></a>
		</li>		
		<li>
			<label>Category: <em>*</em></label>
				<select class="required" name="arrCnt[{$i.flg_source}][settings][cats]">
					<option value="retailprice" {if $arrCnt.{$i.flg_source}.settings.cats == "retailprice"}selected="selected"{/if} >Price</option>
					<option value="productname" {if $arrCnt.{$i.flg_source}.settings.cats == "productname"}selected="selected"{/if} >Product Name</option>
					<option value="categoryname" {if $arrCnt.{$i.flg_source}.settings.cats == "categoryname"}selected="selected"{/if} >Category Name</option>
					<option value="mid" {if $arrCnt.{$i.flg_source}.settings.cats == "mid"}selected="selected"{/if} >Merchant ID</option>							
				</select>
		</li>		
		<li>
			<label>Sort by: <em>*</em></label>
				<select class="required" name="arrCnt[{$i.flg_source}][settings][sort]">
					<option value="retailprice" {if $arrCnt.{$i.flg_source}.settings.sort == "retailprice"}selected="selected"{/if} >Price</option>
					<option value="productname" {if $arrCnt.{$i.flg_source}.settings.sort == "productname"}selected="selected"{/if} >Product Name</option>
					<option value="categoryname" {if $arrCnt.{$i.flg_source}.settings.sort == "categoryname"}selected="selected"{/if} >Category Name</option>
					<option value="mid" {if $arrCnt.{$i.flg_source}.settings.sort == "mid"}selected="selected"{/if} >Merchant ID</option>							
				</select>
		</li>		
	</ol>