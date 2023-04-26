	<ol>
		<li>
			<label>Add RSS links: <em>*</em></label>
				<textarea id="keyword-conteiner" name="arrCnt[{$i.flg_source}][settings][templait]" style="height:50px;">{if !empty($arrCnt.{$i.flg_source}.settings.templait)}{$arrCnt.{$i.flg_source}.settings.templait}{/if}</textarea>
		</li>
		<li>
			<label>Posts number: <em>*</em></label>
				<input class="required" name="arrCnt[{$i.flg_source}][settings][limit]" type="text" value="{if !empty($arrCnt.{$i.flg_source}.settings.limit)}{$arrCnt.{$i.flg_source}.settings.limit}{else}10{/if}" />
		</li>
		<li>
			<label> Don't insert link to content: <em>*</em></label>
				<input class="required" name="arrCnt[{$i.flg_source}][settings][insertlinks]" type="checkbox" {if $arrCnt.{$i.flg_source}.settings.insertlinks == '1'}checked="checked"{/if} value="1" />
		</li>
	</ol>