<div>
<form method="post" action="{Core_Module_Router::$uriFull}" name="g_set" id="g_set">
<input type="hidden" name="change_right" value="" id="change_right">
<input type="hidden" name="change_sys" value="" id="change_sys">
<div><b>Select Right</b>: <select name="arrG[right_id]" class="elogin" style="width:50%;" onchange="$('change_right').value=1;$('change_sys').value=0;g_set.submit();">
	<option value="0"> - select - </option>
	{html_options options=$arrR selected=$smarty.request.right_id}
</select></div>
<div><b>Select System</b>: <select name="arrG[system_id]" class="elogin" style="width:50%;" onchange="$('change_sys').value=1;$('change_right').value=0;g_set.submit();">
	<option value="0"> - select - </option>
	{html_options options=$arrS selected=$smarty.request.sys_id}
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
{foreach $arrG as $group}
	{if ($group@iteration-1) is div by 3}
</tr>
<tr>
	{/if}
	<td width="30%" class="for_checkbox">
		<input type="checkbox" name="arrG[groups][{$group@key}]"{if $arrL[$group@key]} checked{/if} id="g_{$group@key}">
		<label for="g_{$group@key}">{$group}</label>
	</td>
{/foreach}
</tr>
</table>
</div>
<div style="width:90%;text-align:center;clear:both;padding-top: 20px;">
{if $smarty.request.right_id}
{$arrR[$smarty.request.right_id]} {$arrS[$smarty.request.right_id]}
{/if}
{if $smarty.request.sys_id}
{$arrR[$smarty.request.sys_id]} {$arrS[$smarty.request.sys_id]}
{/if}
<br>
{foreach $arrG as $group}
{if $arrL[$group@key]}<br/>{$group}{/if}
{/foreach}
</div>
<div style="width:90%;text-align:center;clear:both;padding-top: 20px;">
	<a href="#" onclick="g_set.submit();return false;">{if $smarty.request.right_id || $smarty.request.sys_id}update{else}attach{/if} groups</a></div>
</form>
</div>