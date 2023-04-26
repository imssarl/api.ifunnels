<div>
<form method="post" action="{Core_Module_Router::$uriFull}" name="r_set" id="r_set">
<input type="hidden" name="change_group" value="" id="change_group">
<div><b>Select Groups</b>: <select name="arrR[group_id]" class="elogin" style="width:50%;" onchange="$('change_group').value=1;r_set.submit();">
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
{foreach from=$arrR key='flg_tree' item='r'}
<table style="width:100%;">
<tr>
	<td colspan="3"><h2>{if $flg_tree==1}Frontend{elseif $flg_tree==2}Both{elseif $flg_tree==3}Core{else}Backend{/if} rights</h2></td>
</tr>
{foreach from=$r key='k' item='v'}
<tr>
	<td  colspan="3">
		<h3 style="font-size:11px;border-top:solid 1px #E0E6EB;">{if $arrM[$k]}<strong>{$arrM[$k]}:</strong>{/if}</h3>
	</td>
</tr>
<tr>
{foreach from=$v key='key' item='right'}
	{if $key%3==0}
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
<div style="width:90%;text-align:center;clear:both;padding-top: 20px;"><a href="#" onclick="r_set.submit();return false;">{if $arrR.group_id}update{else}attach{/if} rights</a></div>
</form>
</div>