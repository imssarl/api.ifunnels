<div>
<form method="post" action="{Core_Module_Router::$uriFull}" name="r_set" id="r_set">
<input type="hidden" name="change_group" value="" id="change_group">
<div><b>Select Group</b>: <select name="arrR[group_id]" class="elogin" style="width:50%;" onchange="$('change_group').value=1;r_set.submit();">
	<option value="0"> - select - </option>
	{html_options options=$arrG selected=$smarty.request.group_id}
</select></div>
<div><br />
<table style="width:100%;">
<tr>
	<td class="for_checkbox">
		<b>Select Rights</b> 
		<span>(</span><label for="g_sel_all">select all</label>
		<input type="checkbox" onClick="toggle_checkbox('r_set',this);" id="g_sel_all" /><span>):</span>
	</td>
</tr>
<tr><td>
{foreach $arrR as $r}
<table style="width:98%;">
<tr>
	<td colspan="3"><h2>{if $r@key==1}Frontend action{elseif $r@key==2}Both action{elseif $r@key==3}Manual added{else}Backend action{/if} rights</h2></td>
</tr>
{foreach $r as $v}
{if $arrM[$v@key]}
<tr>
	<td colspan="3">
		<h3 style="font-size:11px;border-top:solid 1px #E0E6EB;"><strong>{$arrM[$v@key]}:</strong></h3>
	</td>
</tr>
{/if}
<tr>
{foreach $v as $right}
	{if ($right@iteration-1) is div by 3}
</tr>
<tr>
	{/if}
	<td width="30%" class="for_checkbox">
		<input type="checkbox" name="arrR[rights][{$right.id}]"{if $arrL[$right.id]} checked{/if} id="g_{$right.id}">
		<label for="g_{$right.id}">{$right.title}</label>
	</td>
{/foreach}
</tr>
{/foreach}
</table>
{/foreach}
</td></tr>
</table>
</div>
<div style="width:90%;text-align:center;clear:both;padding-top: 20px;">
	<a href="#" onclick="r_set.submit();return false;">{if $smarty.request.group_id}update{else}attach{/if} rights</a></div>
</form>
</div>