	<ol>
		<li>
			<label>Language: <em>*</em></label>
				<select class="required" name="arrCnt[{$i.flg_source}][settings][lang]">
							<option value="" {if $arrCnt.{$i.flg_source}.settings.lang == ""}selected="selected"{/if} >Any Language</option>
							<option value="ar" {if $arrCnt.{$i.flg_source}.settings.lang == "ar"}selected="selected"{/if} >Arabic</option>
							<option value="bg" {if $arrCnt.{$i.flg_source}.settings.lang == "bg"}selected="selected"{/if} >Bulgarian</option>
							<option value="ca" {if $arrCnt.{$i.flg_source}.settings.lang == "ca"}selected="selected"{/if} >Catalan</option>
							<option value="zh-cn" {if $arrCnt.{$i.flg_source}.settings.lang == "zh-cn"}selected="selected"{/if} >Chinese (Simplified)</option>
							<option value="zh-tw" {if $arrCnt.{$i.flg_source}.settings.lang == "zh-tw"}selected="selected"{/if} >Chinese (Traditional)</option>
							<option value="hr" {if $arrCnt.{$i.flg_source}.settings.lang == "hr"}selected="selected"{/if} >Croatian</option>
							<option value="cs" {if $arrCnt.{$i.flg_source}.settings.lang == "cs"}selected="selected"{/if} >Czech</option>
							<option value="da" {if $arrCnt.{$i.flg_source}.settings.lang == "da"}selected="selected"{/if} >Danish</option>
							<option value="nl" {if $arrCnt.{$i.flg_source}.settings.lang == "nl"}selected="selected"{/if} >Dutch</option>
							<option value="en" {if $arrCnt.{$i.flg_source}.settings.lang == "en"}selected="selected"{/if} >English</option>
							<option value="et" {if $arrCnt.{$i.flg_source}.settings.lang == "et"}selected="selected"{/if} >Estonian</option>
							<option value="fi" {if $arrCnt.{$i.flg_source}.settings.lang == "fi"}selected="selected"{/if} >Finnish</option>
							<option value="fr" {if $arrCnt.{$i.flg_source}.settings.lang == "fr"}selected="selected"{/if} >French</option>
							<option value="de" {if $arrCnt.{$i.flg_source}.settings.lang == "de"}selected="selected"{/if} >German</option>
							<option value="er" {if $arrCnt.{$i.flg_source}.settings.lang == "er"}selected="selected"{/if} >Greek</option>
							<option value="iw" {if $arrCnt.{$i.flg_source}.settings.lang == "iw"}selected="selected"{/if} >Hebrew</option>
							<option value="hu" {if $arrCnt.{$i.flg_source}.settings.lang == "hu"}selected="selected"{/if} >Hungarian</option>
							<option value="is" {if $arrCnt.{$i.flg_source}.settings.lang == "is"}selected="selected"{/if} >Icelandic</option>
							<option value="it" {if $arrCnt.{$i.flg_source}.settings.lang == "it"}selected="selected"{/if} >Italian</option>
							<option value="ja" {if $arrCnt.{$i.flg_source}.settings.lang == "ja"}selected="selected"{/if} >Japanese</option>
							<option value="ko" {if $arrCnt.{$i.flg_source}.settings.lang == "ko"}selected="selected"{/if} >Korean</option>
							<option value="lv" {if $arrCnt.{$i.flg_source}.settings.lang == "lv"}selected="selected"{/if} >Latvian</option>
							<option value="lt" {if $arrCnt.{$i.flg_source}.settings.lang == "lt"}selected="selected"{/if} >Lithuanian</option>
							<option value="no" {if $arrCnt.{$i.flg_source}.settings.lang == "no"}selected="selected"{/if} >Norwegian</option>
							<option value="pl" {if $arrCnt.{$i.flg_source}.settings.lang == "pl"}selected="selected"{/if} >Polish</option>
							<option value="pt" {if $arrCnt.{$i.flg_source}.settings.lang == "pt"}selected="selected"{/if} >Portuguese</option>
							<option value="ro" {if $arrCnt.{$i.flg_source}.settings.lang == "ro"}selected="selected"{/if} >Romanian</option>
							<option value="ru" {if $arrCnt.{$i.flg_source}.settings.lang == "ru"}selected="selected"{/if} >Russian</option>
							<option value="sr" {if $arrCnt.{$i.flg_source}.settings.lang == "sr"}selected="selected"{/if} >Serbian</option>
							<option value="sk" {if $arrCnt.{$i.flg_source}.settings.lang == "sk"}selected="selected"{/if} >Slovak</option>
							<option value="sl" {if $arrCnt.{$i.flg_source}.settings.lang == "sl"}selected="selected"{/if} >Slovenian</option>
							<option value="es" {if $arrCnt.{$i.flg_source}.settings.lang == "es"}selected="selected"{/if} >Spanish</option>
							<option value="sv" {if $arrCnt.{$i.flg_source}.settings.lang == "sv"}selected="selected"{/if} >Swedish</option>
							<option value="tr" {if $arrCnt.{$i.flg_source}.settings.lang == "tr"}selected="selected"{/if} >Turkish</option>						</select>
		</li>
		<li>
			<label>Safe Search: <em>*</em></label>
				<select class="required" name="arrCnt[{$i.flg_source}][settings][safe]">
					<option value="small" >Small</option>
							<option value="none" {if $arrCnt.{$i.flg_source}.settings.safe == "none"}selected="selected"{/if} >None</option>
							<option value="moderate" {if $arrCnt.{$i.flg_source}.settings.safe == "moderate"||empty($arrCnt.{$i.flg_source}.settings.safe)}selected="selected"{/if} >Moderate</option>
							<option value="strict" {if $arrCnt.{$i.flg_source}.settings.safe == "strict"}selected="selected"{/if} >Strict</option>
				</select>
		</li>
		<li>
			<label>Sort Videos by: <em>*</em></label>
				<select class="required" name="arrCnt[{$i.flg_source}][settings][sort]">
							<option value="relevance" {if $arrCnt.{$i.flg_source}.settings.sort == "relevance"}selected="selected"{/if} >Relevance</option>
							<option value="viewCount" {if $arrCnt.{$i.flg_source}.settings.sort == "viewCount"}selected="selected"{/if} >View Count</option>
							<option value="rating" {if $arrCnt.{$i.flg_source}.settings.sort == "rating"}selected="selected"{/if} >Rating</option>
							<option value="published" {if $arrCnt.{$i.flg_source}.settings.sort == "published"}selected="selected"{/if} >Date Published</option>
				</select>
		</li>
		<li>
			<label>Video width: <em>*</em></label>
			<input size="7" class="required" type="text" name="arrCnt[{$i.flg_source}][settings][width]" value="{if !empty($arrCnt.{$i.flg_source}.settings.width)}{$arrCnt.{$i.flg_source}.settings.width}{else}425{/if}"/>
		</li>
		<li>
			<label>Video height: <em>*</em></label>
			<input size="7" class="required" type="text" name="arrCnt[{$i.flg_source}][settings][height]" value="{if !empty($arrCnt.{$i.flg_source}.settings.height)}{$arrCnt.{$i.flg_source}.settings.height}{else}355{/if}"/>
		</li>	
		<li>	
			<label>Users: <em>*</em></label>
				<input size="40" class="required" type="text" name="arrCnt[{$i.flg_source}][settings][author]" value="{if !empty($arrCnt.{$i.flg_source}.settings.author)}{$arrCnt.{$i.flg_source}.settings.author}{else}{/if}"/>
				<a style="text-decoration:none" class="Tips" title="This option restricts the search to videos uploaded by a particular YouTube user. You can enter a comma-separated list of up to 20 YouTube usernames."><b> ?</b></a>	
		</li>
		<li>
			<label>Strip All Links from Video description: <em>*</em></label>
				<input class="required" name="arrCnt[{$i.flg_source}][settings][videodes]" type="checkbox" value={if $arrCnt.{$i.flg_source}.settings.videodes == '1'}checked="checked"{/if} />
		</li>
		<li>
			<label>Strip All Links from Comments <em>*</em></label>
				<input class="required" name="arrCnt[{$i.flg_source}][settings][comments]" type="checkbox" value={if $arrCnt.{$i.flg_source}.settings.comments  == '1'}checked="checked"{/if} />
		</li>	
	</ol>