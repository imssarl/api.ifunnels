<div>
<form method="post" class="wh" action="{Core_Module_Router::$uriFull}" name="r_set" id="r_set">
<input type="hidden" name="change_group" value="" id="change_group">
<div><b>Select Group</b>: <select name="arrR[group_id]" class="elogin" style="width:50%;" onchange="$('change_group').value=1;r_set.submit();">
	<option value="0"> - select - </option>
	{html_options options=$arrG selected=$smarty.request.group_id}
</select></div>
<fieldset>
	<legend>Select Rights</legend>
	<ol>
		<li>
			<label for="g_sel_all">select all</label><input type="checkbox" onClick="toggle_checkbox('r_set',this);" id="g_sel_all" />
		</li>
	</ol>

</fieldset>
<div>
	{foreach from=$arrSource item=i key=type}
	<fieldset>
		<legend>{$type}</legend>
		<ol>
			{foreach from=$i item=source}
			<li>
				<input type="hidden" value="0" name="data[{$source.flg_source}]" />
				<label style="cursor: pointer;" for="id-{$source.flg_source}">{$source.title}&nbsp;</label><input type="checkbox" id="id-{$source.flg_source}" value="{$source.flg_source}" {if in_array($source.flg_source,$sourceIds)} checked="1" {/if} name="data[{$source.flg_source}]" />
			</li>
			{/foreach}
		</ol>
	</fieldset>
	{/foreach}
</div>
<div style="width:90%;text-align:center;clear:both;padding-top: 20px;">
	<a href="#" onclick="r_set.submit();return false;">{if $smarty.request.group_id}update{else}attach{/if} rights</a></div>
</form>
</div>