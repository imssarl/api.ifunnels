<div>
{if !$arrG}
	<div style="float:left; width: 100%">
		<div class="red" style="margin: 80px auto; width: 100%; text-align:center;"><b>no groups added</b></div>
	</div>
{else}
<form method="post" action="" id="s_list" name="s_list">
<table class="info glow" style="width:90%;">
<thead>
<tr>
	<th width="1px"><input type="checkbox" onClick="toggle_checkbox('s_list',this);" /></th>
	<th>title</th>
	<th>sys name</th>
	<th>description</th>
</tr>
</thead>
<tbody>
{foreach from=$arrG key='k' item='v'}
<tr{if $k%2=='0'} class="matros"{/if}>
	<td><input type="checkbox" name="arrR[{$v.id}][del]" value="{$v.id}"/></td>
	<td><input type="text" class="elogin" name="arrR[{$v.id}][title]" value="{$v.title|escape}" /></td>
	<td><input type="text" class="elogin" name="arrR[{$v.id}][sys_name]" value="{$v.sys_name|escape}" /></td>
	<td><textarea class="elogin" name="arrR[{$v.id}][description]">{$v.description|escape}</textarea></td>
</tr>
{/foreach}
<tr>
	<td>&nbsp;</td>
	<td><input type="text" name="arrR[0][title]" value="{$arrR.title|escape}" class="elogin"></td>
	<td><input type="text" name="arrR[0][sys_name]" value="{$arrR.sys_name|escape}" class="elogin"></td>
	<td><textarea class="elogin" name="arrR[0][description]">{$arrR.description|escape}</textarea></td>
</tr>
</tbody>
<tfoot>
	<tr><td colspan="4">{include file="../../pgg_backend.tpl"}</td></tr>
	<tr><td colspan="4"><center><a href="#" onClick="s_list.submit();return false;">update</a></center></td></tr>
</tfoot>
</table>
</form>
{/if}
</div>