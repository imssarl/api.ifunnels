<div>
<form method="post" action="" id="users-filter">
	<input type="hidden" name="arrFilter[order]" value="{$arrFilter.order}" />
	<div style="margin-bottom:10px;">groups: <select class="elogin" style="width:150px;" id="with_groups-filter" >
		<option value=""> - show all - </option>
		{html_options options=$arrG selected=$arrFilter.with_groups.0}
		</select>
		search by nickname: <input type="text" value="{$arrFilter.nickname}" class="elogin" style="width:150px;" id="nickname-filter" />
		or(and) search by email: <input type="text" value="{$arrFilter.email}" class="elogin" style="width:150px;" id="email-filter" />
		<input type="submit" value="filter">
	</div>
</form>
<script type="text/javascript">
	$('users-filter').addEvent('submit',function(e){
		e&&e.stop();
		['with_groups','nickname','email'].toURI('-filter').go();
	});
</script>
{if !$arrList}
	<div style="float:left; width: 100%">
		<div class="red" style="margin: 80px auto; width: 100%; text-align:center;"><b>no users found</b></div>
	</div>
{else}
<form method="post" action="" id="u_list" name="u_list">
<table class="info glow" style="width:90%;">
	<tr><td colspan="9">
	<input type="submit" value="update" />
	<input type="button" value="cancel" onClick="$('u_list').reset();" />
	{include file="../../pgg_backend.tpl"}
	</td></tr>
<thead>
<tr>
	<th style="padding-right:0;width:1px;"><input type="checkbox" id="del" title="mass delete" class="tooltip" rel="check to select all" /></th>
	<th style="padding-right:0;width:1px;" nowrap>
		<div class="th_item">
			<input type="checkbox" id="set" title="mass switch" class="tooltip" rel="check to select all" />
			<span>enable</span>
		{if $arrPg.recall>1}
			{if $arrFilter.order!='u.flg_status--up'}<a href="{url name='accounts' action='user_list' wg='order=u.flg_status--up'}"><img src="/skin/i/backend/up.gif" width="5" height="11" alt="" /></a>{else}<img src="/skin/i/backend/up_off.gif" width="5" height="11" alt="" />{/if}{if $arrFilter.order!='u.flg_status--dn'}<a href="{url name='accounts' action='user_list' wg='order=u.flg_status--dn'}"><img src="/skin/i/backend/down.gif" width="5" height="11" alt="" /></a>{else}<img src="/skin/i/backend/down_off.gif" width="5" height="11" alt="" />{/if}
		{/if}
		</div>
	</th>
	<th>nickname
		{if $arrPg.recall>1}
			{if $arrFilter.order!='u.nickname--up'}<a href="{url name='accounts' action='user_list' wg='order=u.nickname--up'}"><img src="/skin/i/backend/up.gif" width="5" height="11" alt="" /></a>{else}<img src="/skin/i/backend/up_off.gif" width="5" height="11" alt="" />{/if}{if $arrFilter.order!='u.nickname--dn'}<a href="{url name='accounts' action='user_list' wg='order=u.nickname--dn'}"><img src="/skin/i/backend/down.gif" width="5" height="11" alt="" /></a>{else}<img src="/skin/i/backend/down_off.gif" width="5" height="11" alt="" />{/if}
		{/if}
	</th>
	<th>email
		{if $arrPg.recall>1}
			{if $arrFilter.order!='u.email--up'}<a href="{url name='accounts' action='user_list' wg='order=u.email--up'}"><img src="/skin/i/backend/up.gif" width="5" height="11" alt="" /></a>{else}<img src="/skin/i/backend/up_off.gif" width="5" height="11" alt="" />{/if}{if $arrFilter.order!='u.email--dn'}<a href="{url name='accounts' action='user_list' wg='order=u.email--dn'}"><img src="/skin/i/backend/down.gif" width="5" height="11" alt="" /></a>{else}<img src="/skin/i/backend/down_off.gif" width="5" height="11" alt="" />{/if}
		{/if}
	</th>
	{*foreach from=$arrHead item='v'}
	<th>{$v.title}
		{if $arrPg.recall>1}
			{if $arrFilter.order!='add_`$v.sys_name`--up'}<a href="{url name='accounts' action='user_list' wg='order=add_`$v.sys_name`--up'}"><img src="/skin/i/backend/up.gif" width="5" height="11" alt="" /></a>{else}<img src="/skin/i/backend/up_off.gif" width="5" height="11" alt="" />{/if}{if $arrFilter.order!='add_`$v.sys_name`--dn'}<a href="{url name='accounts' action='user_list' wg='order=add_`$v.sys_name`--dn'}"><img src="/skin/i/backend/down.gif" width="5" height="11" alt="" /></a>{else}<img src="/skin/i/backend/down_off.gif" width="5" height="11" alt="" />{/if}
		{/if}
	</th>
	{/foreach*}
	<th>actions</th>
</tr>
</thead>
<tbody>
{foreach from=$arrList key='k' item='v'}
<input type="hidden" name="arrList[{$v.id}][id]" value="{$v.id}" />
<tr{if $k%2=='0'} class="matros"{/if}>
	<td style="padding-right:0;width:1px;"><input type="checkbox" name="arrList[{$v.id}][del]" class="check-me-del" id="check-{$i.id}" /></td>
	<td style="padding-right:0;width:1px;"><input type="checkbox" name="arrList[{$v.id}][flg_status]" class="check-me-set"{if $v.flg_status} checked=""{/if}/></td>
	<td>{$v.nickname}</td>
	<td>{$v.email}</td>
	{*foreach from=$arrHead item='h'}
	<td>{$v.additional[$h.sys_name]|default:'-'}</td>
	{/foreach*}
	<td><a href="{url name='accounts' action='set_profile'}?id={$v.id}">edit</a></td>
</tr>
{/foreach}
</tbody>
<tfoot>
	<tr><td colspan="9">
	<input type="submit" value="update" />
	<input type="button" value="cancel" onClick="$('u_list').reset();" />
	{include file="../../pgg_backend.tpl"}
	</td></tr>
</tfoot>
</table>
</form>
{literal}
<script>
window.addEvent('domready',function(){
	checkboxToggle($('del'));
	checkboxToggle($('set'));
});
</script>
{/literal}
{/if}
</div>