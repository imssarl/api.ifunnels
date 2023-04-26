<div>
{if !$arrI}
	<div style="float:left; width: 100%">
		<div class="red" style="margin: 80px auto; width: 100%; text-align:center;"><b>no rights added</b></div>
	</div>
{else}
<form method="post" action="" id="s_list" name="s_list">
<table class="info glow" style="width:90%;">
<thead>
<tr>
	<th width="1px"><input type="checkbox" onClick="toggle_checkbox('s_list',this);" /></th>
	<th width="30%">title</th>
	<th width="1px">sys name</th>
	<th width="50%">description</th>
	<th width="20%">actions</th>
</tr>
</thead>
<tbody>
{foreach from=$arrI key='k' item='v'}
<tr{if $k%2=='0'} class="matros"{/if}>
	<td><input type="checkbox" name="arrI[{$v.id}]" value="{$v.id}"/></td>
	<td><nobr>{$v.title}</nobr></td>
	<td>{$v.sys_name}</td>
	<td>{$v.description|nl2br}</td>
	<td>&nbsp;</td>
</tr>
{/foreach}
<tr>
	<td>&nbsp;</td>
	<td><input type="text" name="arrR[title]" value="{$arrR.title}" class="elogin"></td>
	<td><input type="text" name="arrR[sys_name]" value="{$arrR.sys_name}" class="elogin"></td>
	<td><input type="text" name="arrR[description]" value="{$arrR.description}" class="elogin"></td>
	<td>&nbsp;</td>
</tr>
</tbody>
<tfoot>
	<tr><td colspan="5">{include file="../../pgg_backend.tpl"}</td></tr>
	<tr><td colspan="5"><center><a href="#" onClick="s_list.submit();return false;">update</a></center></td></tr>
</tfoot>
</table>
</form>
{/if}
</div>