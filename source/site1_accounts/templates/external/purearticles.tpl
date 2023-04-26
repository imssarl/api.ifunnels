	<ol>
		<li>
			<label>Article Language: <em>*</em></label>
				<select class="required" name="arrCnt[{$i.flg_source}][settings][lang]">
					<option value="en" {if $arrCnt.{$i.flg_source}.settings.lang == "en"}selected="selected"{/if}>English</option>
					<option value="fr" {if $arrCnt.{$i.flg_source}.settings.lang == "fr"}selected="selected"{/if}>French</option>
					<option value="es" {if $arrCnt.{$i.flg_source}.settings.lang == "es"}selected="selected"{/if}>Spanish</option>
					<option value="pg" {if $arrCnt.{$i.flg_source}.settings.lang == "pg"}selected="selected"{/if}>Portuguese</option>
					<option value="ru" {if $arrCnt.{$i.flg_source}.settings.lang == "ru"}selected="selected"{/if}>Russian</option>
				</select>
		</li>
		<li>
			<label>Keywords: <em>*</em></label>
			<input size="40" class="required" type="text" name="arrCnt[{$i.flg_source}][settings][keywords]" value="{if !empty($arrCnt.{$i.flg_source}.settings.keywords)}{$arrCnt.{$i.flg_source}.settings.keywords}{else}{/if}"/>
		</li>
		<li>
			<label>Strip All Links from Article Body: <em>*</em></label>
			<input class="required" name="arrCnt[{$i.flg_source}][settings][striplinks]" type="checkbox" {if $arrCnt.{$i.flg_source}.settings.striplinks == '1'}checked="checked"{/if} />
			<a style="text-decoration:none" class="Tips" title="<b>Warning:</b> Removing links from the articles content is against the articlesbase.com and authors terms of use. Use this setting at your own risk!"><b> ?</b></a>
		</li>
	</ol>