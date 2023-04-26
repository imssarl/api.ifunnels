	<ol>
		<li>
			<label>Amazon Affiliate ID: <em>*</em></label>
			<input size="40" class="required" type="text" name="arrCnt[{$i.flg_source}][settings][id]" value="{if !empty($arrCnt.{$i.flg_source}.settings.id)}{$arrCnt.{$i.flg_source}.settings.id}{else}free3columtem-20{/if}"/>
			<a style="text-decoration:none" title="This option is not required but you will only earn affiliate commission if you enter your Amazon affiliate ID." class="Tips" ><b> ?</b></a>
		</li>
		<li>
			<label>API Key (Access Key ID): <em>*</em></label>
			<input size="40" class="required" type="text" name="arrCnt[{$i.flg_source}][settings][key]" value="{if !empty($arrCnt.amazon.settings.key)}{$arrCnt.{$i.flg_source}.settings.key}{else}AKIAITJWE5YJGO3UWSTQ{/if}"/>
			<a target="_blank" href="https://affiliate-program.{$i.flg_source}.settings.com/gp/advertising/api/detail/main.html" style="text-decoration:none" class="Tips" title="This setting is required for the Amazon module to work!<br/><b>Click to get to the Amazon API sign up page!</b>"><b> ?</b></a>
		</li>		
		<li>
			<label>Secret Access Key: <em>*</em></label>
			<input size="40" class="required" type="text" name="arrCnt[{$i.flg_source}][settings][secret]" value="{if !empty($arrCnt.{$i.flg_source}.settings.secret)}{$arrCnt.{$i.flg_source}.settings.secret}{else}sTNy4XgSiLympf4RVImBqZpLv3AkoSrtj2DkjEuR{/if}"/>
			<a style="text-decoration:none" class="Tips" title="Warning: Removing links from the articles content is against the articlesbase.com and authors terms of use. Use this setting at your own risk!"><b> ?</b></a>
		</li>		
		<li>
			<label>Skip Products If: <em>*</em></label>
				<select class="required" name="arrCnt[{$i.flg_source}][settings][skiping]">
					<option value="dontskip" {if $arrCnt.{$i.flg_source}.settings.skiping == "dontskip"}selected="selected"{/if}>Don't skip</option>
					<option value="nodesc" {if $arrCnt.{$i.flg_source}.settings.skiping == "nodesc"}selected="selected"{/if}>No description found</option>
					<option value="noimg" {if $arrCnt.{$i.flg_source}.settings.skiping == "noimg"}selected="selected"{/if}>No thumbnail image found</option>
					<option value="noall" {if $arrCnt.{$i.flg_source}.settings.skiping == "noall"}selected="selected"{/if}>No description OR no thumbnail</option>
				</select>
		</li>		
		<li>
			<label>Amazon Description Length: <em>*</em></label>
				<select class="required" name="arrCnt[{$i.flg_source}][settings][length]">
					<option value="250" {if $arrCnt.{$i.flg_source}.settings.length == "250"}selected="selected"{/if}>250 Characters</option>
					<option value="500" {if $arrCnt.{$i.flg_source}.settings.length == "500"}selected="selected"{/if}>500 Characters</option>
					<option value="750" {if $arrCnt.{$i.flg_source}.settings.length == "750"}selected="selected"{/if}>750 Characters</option>
					<option value="1000" {if $arrCnt.{$i.flg_source}.settings.length == "1000"}selected="selected"{/if}>1000 Characters</option>
					<option value="full" {if $arrCnt.{$i.flg_source}.settings.length == "full"}selected="selected"{/if}>Full Description</option>
				</select>
		</li>
		<li>
			<label>Amazon Website: <em>*</em></label>
				<select class="required" name="arrCnt[{$i.flg_source}][settings][site]">
					<option value="com" {if $arrCnt.{$i.flg_source}.settings.site == "com"}selected="selected"{/if}>Amazon.com</option>
					<option value="co.uk" {if $arrCnt.{$i.flg_source}.settings.site == "co.uk"}selected="selected"{/if}>Amazon.co.uk</option>
					<option value="de" {if $arrCnt.{$i.flg_source}.settings.site == "de"}selected="selected"{/if}>Amazon.de</option>
					<option value="ca" {if $arrCnt.{$i.flg_source}.settings.site == "ca"}selected="selected"{/if}>Amazon.ca</option>
					<option value="jp" {if $arrCnt.{$i.flg_source}.settings.site == "jp"}selected="selected"{/if}>Amazon.jp</option>
					<option value="fr" {if $arrCnt.{$i.flg_source}.settings.site == "fr"}selected="selected"{/if}>Amazon.fr</option>
				</select>
		</li>
		<li>
			<label>Review Template: <em>*</em></label>
				<textarea name="arrCnt[{$i.flg_source}][settings][templait]" style="height:50px;">{if !empty($arrCnt.{$i.flg_source}.settings.templait)}{$arrCnt.{$i.flg_source}.settings.templait}{else}{literal}<i>Review by {author} for {link}</i>
<b>Rating: {rating}</b>
{content}{/literal}{/if}</textarea>
<a target="_blank" href="http://wprobot.net/test/documentation/#33" style="text-decoration:none" class="Tips" title="How the product reviews will look in posts and comments. Click to see all available template tags in the documentation."><b> ?</b></a>
		</li>
			<li>
				<label>Format site content: <em>*</em></label>
				<textarea name="arrCnt[{$i.flg_source}][settings][format]" style="height:50px;">{if !empty($arrCnt.{$i.flg_source}.settings.format)}{$arrCnt.{$i.flg_source}.settings.format}{else}{literal}<i>{title}</i></br> vs {body}{/literal}{/if}</textarea>
<a style="text-decoration:none" class="Tips" title="Help for content formate!"><b> ?</b></a>
			</li>
	</ol>