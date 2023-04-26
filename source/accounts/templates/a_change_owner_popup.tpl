<div style="padding:20px;">
<form method="post" action="" style="padding:0px;" id="change_owner_form">
	<input type="hidden" name="arrFilter[order]" value="{$arrFilter.order}" id="change_owner_order"/>
</form>
<table cellspacing="1" cellpadding="3" border="0" class="info glow" style="width:100%;">
<thead id="order_handler">
	<tr align="center">
		<th>id
		{if count($arrList)>1}
			{if $arrFilter.order!='u.id+up'}<a href="#" title="u.id+up"><img src="/skin/i/backend/up.gif" width="5" height="11" alt="" /></a>{else}<img src="/skin/i/backend/up_off.gif" width="5" height="11" alt="" />{/if}{if $arrFilter.order!='u.id+dn'}<a href="#" title="u.id+dn"><img src="/skin/i/backend/down.gif" width="5" height="11" alt="" /></a>{else}<img src="/skin/i/backend/down_off.gif" width="5" height="11" alt="" />{/if}
		{/if}
		</th>
		<th>nickname
		{if count($arrList)>1}
			{if $arrFilter.order!='u.nickname+up'}<a href="#" title="u.nickname+up"><img src="/skin/i/backend/up.gif" width="5" height="11" alt="" /></a>{else}<img src="/skin/i/backend/up_off.gif" width="5" height="11" alt="" />{/if}{if $arrFilter.order!='u.nickname+dn'}<a href="#" title="u.nickname+dn"><img src="/skin/i/backend/down.gif" width="5" height="11" alt="" /></a>{else}<img src="/skin/i/backend/down_off.gif" width="5" height="11" alt="" />{/if}
		{/if}
		</th>
		<th>email
		{if count($arrList)>1}
			{if $arrFilter.order!='email+up'}<a href="#" title="email+up"><img src="/skin/i/backend/up.gif" width="5" height="11" alt="" /></a>{else}<img src="/skin/i/backend/up_off.gif" width="5" height="11" alt="" />{/if}{if $arrFilter.order!='email+dn'}<a href="#" title="email+dn"><img src="/skin/i/backend/down.gif" width="5" height="11" alt="" /></a>{else}<img src="/skin/i/backend/down_off.gif" width="5" height="11" alt="" />{/if}
		{/if}
		</th>
		{foreach from=$arrHead item='v'}
		<th>{$v.title}
			{if count($arrList)>1}
				{if $arrFilter.order!="add_`$v.sys_name`+up"}<a href="#" title="add_{$v.sys_name}+up"><img src="/skin/i/backend/up.gif" width="5" height="11" alt="" /></a>{else}<img src="/skin/i/backend/up_off.gif" width="5" height="11" alt="" />{/if}{if $arrFilter.order!="add_`$v.sys_name`+dn"}<a href="#" title="add_{$v.sys_name}+dn"><img src="/skin/i/backend/down.gif" width="5" height="11" alt="" /></a>{else}<img src="/skin/i/backend/down_off.gif" width="5" height="11" alt="" />{/if}
			{/if}
		</th>
		{/foreach}
		<th>actions</th>
	</tr>
</thead>
<tbody id="content_handler">
{foreach from=$arrList key="k" item="v"}
	<tr{if $k%2==0} class="matros"{/if}>
		<td>{$v.id}</td>
		<td id="user_name_{$v.id}">{$v.nickname}</td>
		<td>{$v.email}</td>
		{foreach from=$arrHead item='h'}
		<td>{$v.additional[$h.sys_name]|default:'-'}</td>
		{/foreach}
		<td><a href="#" id="{$v.id}">select</a></td>
	</tr>
{/foreach}
</tbody>
<tfoot>
	<tr><td colspan="9">{include file="../../pgg_backend.tpl"}</td></tr>
</tfoot>
</table>
</div>