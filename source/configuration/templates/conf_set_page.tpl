<div>
	<a href="{url name='configuration' action='sites_map'}?root_id={$smarty.get.root_id}">back to current site map</a>
	{if $smarty.get.pid&&$smarty.get.id} | <a href="{url name='configuration' action='set_page'}?root_id={$smarty.get.root_id}&pid={$smarty.get.pid}">create another page</a>{/if}
	{if $smarty.get.id} | <a href="{url name='configuration' action='set_page'}?root_id={$smarty.get.root_id}&pid={$smarty.get.id}">create child page</a>{/if}
</div>
<br/>
<br/>
<form method="post" action="" name="a_set" id="a_set" enctype="multipart/form-data" class="wh" >
<input type="hidden" name="mode" value="" id="mode"> {*при изменении parent page надо перерисовать page position*}
<input type="hidden" name="arrPage[id]" value="{$smarty.get.id}">
<input type="hidden" name="arrPage[root_id]" value="{$smarty.get.root_id}">
<fieldset>
	<legend></legend>
	<ol>
		<li>
			<label>Title:</label><input type="text" name="arrPage[title]"  value="{$arrPage.title|escape:"html"}" class="elogin lang">
		</li>
		<li>
			<label {if $arrErr.sys_name_exists} class="red"{/if}>url name:</label><input type="text" name="arrPage[sys_name]" value="{$arrPage.sys_name}" class="elogin">
		</li>
{*изменять можно только для страниц фронтэнда и не рутовых*}
	{if $arrPage.id!=$smarty.get.root_id}
		{if $arrSite.flg_type==0}
		<li>
			<label>parent page:<p><small>(autodispatch field)</small></p></label><select name="arrPage[pid]" class="elogin" onchange="$('mode').value='chenge_pid';a_set.submit();return false;">
								{function name=recursion}
									{if $tree}
										{foreach from=$tree item='v'}
										<option value="{$v.id}"{if $v.id==$selected} selected=""{/if}>{$v.title|indent:$v.level:"-&nbsp;"}</option>
										{if $v.node}
											{recursion tree=$v.node selected=$selected}
										{/if}
										{/foreach}
									{/if}
								{/function}
								{recursion tree=$arrTree selected=$arrPage.pid}
							</select>
		</li>
		{else}
			<li><input type="hidden" name="arrPage[pid]" value="{$arrPage.pid}"></li>
		{/if}
		{*изменять можно только для созданных страниц - кстати почему? TODO!!! *}
		<li>
			<label>page position:</label>
			<select name="arrPage[position]" class="elogin">
					<option value="first">first page</option>
					{foreach $arrPos as $k=>$v}
						{if $v.id==$arrPage.id}
							{continue}
						{/if}
						<option value="{$v.id}"{if $arrPos[$v@key+1].id==$arrPage.id} selected{/if}>after "{$v.title}" page</option>
					{/foreach}
				</select>
		</li>
	{else}
		<input type="hidden" name="arrPage[pid]" value="{$arrPage.pid}">
	{/if}
		<li>
			<label>seo tool:<p>(to fill description and keywords meta-tags)</p></label><textarea name="full_description" rows="4" cols="20" class="elogin" style="overflow:auto"></textarea>

		</li>
		<li>
			<label>description meta-tag:<p>(200 - 250 chars may be indexed. this amount may be displayed partly.)</p></label><textarea name="arrPage[meta_description]" rows="4" cols="20" class="elogin lang" style="overflow:auto">{$arrPage.meta_description}</textarea>

		</li>
		<li>
			<label>keywords meta-tag:<p>(Search engines indexed up to 1000 characters of text. Commas weren't required.)</p></label>
			<textarea name="arrPage[meta_keywords]" rows="4" cols="20" class="elogin lang" style="overflow:auto">{$arrPage.meta_keywords}</textarea>

		</li>
		<li>
			<label>robots meta-tag:<p>(If checked, the page will be indexed by search engines.)</p> </label><input type="checkbox" name="arrPage[meta_robots]"{if $arrPage.meta_robots} checked{/if}>
		</li>
		<div style="clear:both;"></div>
		<li>
			<label>show on site map:<p>(If checked, the page will be shown on site map.)</p></label><input type="checkbox" name="arrPage[flg_onmap]"{if $arrPage.flg_onmap} checked{/if}>
		</li>
		<div style="clear:both;"></div>
		<li>
			<label>action:</label>
			<select name="arrPage[action_id]" class="elogin">
				<option value="0"> - select - </option>
				{html_options options=$arrModulesWithActions selected=$arrPage.action_id}
				</select>
		</li>
		<li>
			<div style="width:90%;text-align:center;clear:both;padding-top: 20px;"><a href="#" onclick="a_set.submit();return false;">{if $arrPage.id}update{else}add{/if}</a></div>
		</li>
	</ol>
</fieldset>
</form>
<script type="text/javascript">window.addEvent('domready',function(){ i18n.setData('{$arrPage|json}') });</script>