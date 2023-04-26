<div style="padding-bottom:10px;">
<div>Tips:</div>
<div>- for auto installed action rights "system name" field are disable</div>
<div>- if description changed, "system name" will be editable</div>
<div>- for add new right input "title" and "system name" fields in first row</div>
</div>
<p><input type="submit" value="Submit" class="subbut" /></p>
<form action="" id="current-form" method="post">
<table class="info glow" style="width:98%">
<thead>
	<tr>
		<th style="padding-right:0;"><input type="checkbox" id="del" class="tooltip no-click" title="mass delete" rel="check to select all" /></th>
		<th>Title</th>
		<th>System name</th>
		<th>Description</th>
	</tr>
</thead>
<tbody>
	<tr>
		<td>&nbsp;</td>
		<td valign="top"><input type="text" class="elogin" name="arrRights[0][title]" value="" /></td>
		<td valign="top"><input type="text" class="elogin" name="arrRights[0][sys_name]" value="" /></td>
		<td><textarea name="arrRights[0][description]" class="elogin"></textarea></td>
	</tr>
	<tr>
		<td colspan="4">{include file="../../pgg_backend.tpl"}</td>
	</tr>
	{foreach $arrList as $v}
	<tr{if ($v@iteration-1) is div by 2} class="matros"{/if}>
		<input type="hidden" name="arrRights[{$v.id}][id]" value="{$v.id}"/>
		<td style="padding-right:0;" valign="top"><input type="checkbox" name="arrRights[{$v.id}][del]" class="check-me-del" id="check-{$v.id}" /></td>
		<td valign="top"><input type="text" class="elogin" name="arrRights[{$v.id}][title]" value="{$v.title}" /></td>
		<td valign="top"><input type="text" class="elogin" name="arrRights[{$v.id}][sys_name]" value="{$v.sys_name}"{if $v.description=='module action right'} disabled="true"{/if} /></td>
		<td><textarea name="arrRights[{$v.id}][description]" class="elogin">{$v.description}</textarea></td>
	</tr>
	{/foreach}
	<tr>
		<td colspan="4">{include file="../../pgg_backend.tpl"}</td>
	</tr>
</tbody>
</table>
</form>
<div><input type="submit" value="Submit" class="subbut" /></div>
<script type="text/javascript">
$$('.subbut').addEvent('click',function(e){
	e && e.stop();
	if ($$('.check-me-del').some(function(item){
		return item.checked==true;
	})) {
		if(!confirm('Your sure to delete selected items?')) return;
	}
	$('current-form').submit();
});
checkboxFullToggle($('del'));
</script>