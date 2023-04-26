<div>
	<div>
	{if $smarty.get.step!=1&&$arrOneStep}
		<a href="{url name='accounts' action='set_profile'}?step=1{if $smarty.get.id}&id={$smarty.get.id}{/if}" title="select group & field set">{/if}select group & field set</a> |
	{if $smarty.get.step!=2&&$arrOneStep}
		<a href="{url name='accounts' action='set_profile'}?step=2{if $smarty.get.id}&id={$smarty.get.id}{/if}" title="fill profile fields">{/if}fill profile fields</a>
	{if $smarty.get.step} | <a href="{url name='accounts' action='set_profile'}" title="create new profile">create new profile</a>{/if}
	</div>
<form method="post" action="" enctype="multipart/form-data" id="set_profile">
{if $smarty.get.step!=2} {*первый шаг*}
<div>
<div><b{if $arrErr.select_stencil} class="red"{/if}>profile fields set</b>:</div>
<div>
<select name="arrOneStep[stencil_name]" class="elogin" style="width:30%;">
	<option value=""> -- select -- </option>
	{html_options options=$arrStencils selected=$arrOneStep.stencil_name}
</select>
</div>
</div>
<div>
<table>
<tr>
	<td colspan="3" class="for_checkbox">
		<b{if $arrErr.select_groups} class="red"{/if}>select user groups</b> 
		<span>(</span><label for="g_sel_all">select all</label>
		<input type="checkbox" onClick="toggle_checkbox('set_profile',this);" id="g_sel_all" /><span>):</span>
	</td>
</tr>
<tr>
{foreach from=$arrG key='k' item='v'}
	{if $k%3==0}
</tr>
<tr>
	{/if}
	<td width="30%" class="for_checkbox">
		<input type="checkbox" name="arrOneStep[groups][{$v.id}]"{if $arrOneStep.groups[$v.id]} checked{/if} id="g_{$v.id}">
		<label for="g_{$v.id}">{$v.title}</label>
	</td>
{/foreach}
</tr>
</table>
</div>
<div><input type="submit" value="Submit"></div>
<script type="text/javascript">
{literal}
$('set_profile').addEvent('submit',function(e){
	if (!$$('input[type=checkbox]').some(function(item){
		return item.checked==true;
	})) {
		alert('At least one group should be selected');
		e.stop();
	}
});
{/literal}
</script>
{else} {*второй шаг*}
<div style="text-align:right;margin-right:50px;"><input type="submit" value="Submit"></div>
	<table class="info mask glow">
	{foreach from=$arrUfields.b item='v'}
	<tr>
		<td{if $arrErr.b[$v.sys_name]} class="red"{/if}>{$v.title}</td>
		{if $v.sys_name=='cost_id_tmp'}
		<td>
			<select name="arrU[b][cost_id_tmp]" class="elogin" style="width:50%;">
				<option value="0"> - укажите тарифный план - </option>
				{html_options options=$arrTp selected=$arrU.b.cost_id}
			</select>
		</td>
	</tr>
	<tr>
		<td>Подтвердить оплату</td>
		<td><input type="checkbox" name="arrU[b][payed]"{if $arrU.b.payed} checked{/if}></td>
			{if $arrOneStep.stencil_name}
	</tr>
	<tr>
		<td>Не проверять поля профайла</td>
		<td><input type="checkbox" name="arrU[b][dont_check]"{if $arrU.b.dont_check||!$smarty.post.arrU} checked{/if}></td>
			{/if}
		{else}
		<td>
			<input type="text" name="arrU[b][{$v.sys_name}]" value='{$arrU.b[$v.sys_name]}' class="elogin" style="width:50%;">
		</td>
		{/if}
	</tr>
	{/foreach}
	</table>
	{form stencil=$arrOneStep.stencil_name arrSelect=$arrSelect arrItem=$arrU.a arrErr=$arrErr.a item_setting=$item_setting debug=false}
<div style="text-align:right;margin-right:50px;"><input type="submit" value="Submit"></div>
{/if}
</form>
</div>