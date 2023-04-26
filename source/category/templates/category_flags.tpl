{if $smarty.get.type_id}
<div style="width:89%;padding-right:1%">
<form method="post" action="">
<table class="info glow" style="width:100%;">
<thead>
	<tr>
		<th width="3%">del</th>
		<th width="1px">&nbsp;</th>
		<th width="30%">flag name</th>
		<th width="57%">description</th>
	</tr>
</thead>
	{foreach from=$arrFlags key='k' item='v'}
	<tr{if $k%2=='0'} class="matros"{/if}>
		<input type="hidden" name="arrFlags[{$v.id}][id]" value="{$v.id}"/>
		<td style="padding-right:0;" valign="top"><input type="checkbox" name="arrFlags[{$v.id}][del]" /></td>
		<td>{if $arrErr.$k.title}<span class="red">*</span>{/if}</td>
		<td valign="top"><input type="text" class="elogin" name="arrFlags[{$v.id}][title]" value="{$v.title}" /></td>
		<td><textarea name="arrFlags[{$v.id}][description]" class="elogin">{$v.description}</textarea></td>
	</tr>
	{/foreach}
	<tr>
		<input type="hidden" name="arrFlags[0][id]" value="0">
		<td>&nbsp;</td>
		<td>{if $arrErr.0.title}<span class="red">*</span>{/if}</td>
		<td valign="top"><input type="text" class="elogin" name="arrFlags[0][title]" value="" /></td>
		<td><textarea name="arrFlags[0][description]" class="elogin"></textarea></td>
	</tr>
</table>
<div><input type="image" src="/skin/i/backend/bt_update.gif" style="margin-top:20px;" /></div>
</form>
</div>
{/if}