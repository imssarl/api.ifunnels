<div>
<form method="post" action="{Core_Module_Router::$uriFull}" name="g_set" id="g_set">
<input type="hidden" name="change_right" value="" id="change_right">
<div><b>Select Right</b>: <select name="arrG[right_id]" class="elogin" style="width:50%;" onchange="$('change_right').value=1;g_set.submit();">
	<option value="0"> - select - </option>
	{html_options options=$arrR selected=$smarty.request.right_id}
</select></div>
<div><br />
<table>
<tr>
	<td colspan="3" class="for_checkbox">
		<b>Select Groups</b> 
		<span>(</span><label for="g_sel_all">select all</label>
		<input type="checkbox" onClick="toggle_checkbox('g_set',this);" id="g_sel_all" /><span>):</span>
	</td>
</tr>
<tr>
{foreach from=$arrG key='k' item='v'}
	{if $k%3==0}
</tr>
<tr>
	{/if}
	<td width="30%" class="for_checkbox">
		<input type="checkbox" name="arrG[groups][{$v.id}]"{if $arrL[$v.id]} checked{/if} id="g_{$v.id}">
		<label for="g_{$v.id}">{$v.title}</label>
	</td>
{/foreach}
</tr>
</table>
</div>
<div style="width:90%;text-align:center;clear:both;padding-top: 20px;"><a href="#" onclick="g_set.submit();return false;">{if $arrG.right_id}update{else}attach{/if} groups</a></div>
</form>
</div>