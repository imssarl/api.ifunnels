<form action="" method="get">
	<div style="padding: 0 0 10px 0;">
		Group: <select class="elogin" style="width:150px;" name="arrFilter[group_id]">
			<option value="">-select group-</option>
			{html_options options=$arrGroups selected=$smarty.get.arrFilter.group_id}
		</select>
		Package: <select class="elogin" style="width:150px;" name="arrFilter[package_id]">
			<option value="">-select package-</option>
			{html_options options=$arrPack selected=$smarty.get.arrFilter.package_id}
		</select>
		&nbsp;
		Nickname: <input class="elogin"  style="width:150px;" type="text" name="arrFilter[search][nickname]" value="{$smarty.get.arrFilter.search.nickname}" />
		Email: <input class="elogin"  style="width:150px;" type="text" name="arrFilter[search][email]" value="{$smarty.get.arrFilter.search.email}" /><input type="submit" value="Search">
	</div>
</form>
<form method="post" action="" id="users-filter">

{if Core_Acs::haveAccess( array( 'Super Admin' ) )}
<div style="margin-bottom:10px;">
		<select class="elogin" style="width:150px;" name="arrFilter[action]" id="go-action" >
			{html_options options=$arrActions selected=$arrFilter.action}
		</select> <input type="submit" value="Go" id="go">
</div>
{/if}
<table class="info glow" style="width:98%">
<thead>
<tr>
	<th style="padding-right:0;width:1px;"><input type="checkbox" id="sel" title="mass select" class="tooltip" rel="check to select all" /></th>
	<th>Nickname{include file="../../ord_backend.tpl" field='nickname'}</th>
	<th style="width:200px;">Parent User{include file="../../ord_backend.tpl" field='parent_id'}</th>
	<th style="width:230px;">Email{include file="../../ord_backend.tpl" field='email'}</th>
	<th>Credits{include file="../../ord_backend.tpl" field='amount'}</th>
	<th width="120">Registered{include file="../../ord_backend.tpl" field='added'}</th>
	<th width="80">Confirm{include file="../../ord_backend.tpl" field='flg_confirm'}</th>
	<th width="180">Options</th>
</tr>
</thead>
<tbody>
	<tr>
		<td colspan="7">{include file="../../pgg_backend.tpl"}</td>
	</tr>
{foreach $arrList as $v}
<tr{if ($v@iteration-1) is div by 2} class="matros"{/if}>
	<td style="padding-right:0;width:1px;"><input type="checkbox" name="arrList[{$v.id}]" class="check-me-sel" id="check-{$i.id}" /></td>
	<td><a href="{url name='members' action='set'  wg="id={$v.id}"}" target="_blank">{if empty($v.nickname)}{$v.buyer_name} {$v.buyer_surname}{else}{$v.nickname}{/if}</a></td>
	<td>{if !empty($v.parent)}<a  target="_blank" href="{url name='members' action='set'  wg="id={$v.parent.id}"}">{$v.parent.email}</a>{/if}</td>
	<td>{$v.email}</td>
	<td>{$v.amount}</td>
	<td>{$v.added|date_local:$config->date_time->dt_full_format}</td>
	<td>{if $v.flg_confirm==1}yes{else}no{/if}</td>
	<td class="option">
		<a href="{url name='members' action='set' wg="id={$v.id}"}">edit</a> |
{if Core_Acs::haveAccess( array( 'Super Admin', 'user_manager_pro' ) )}		<a href="{url name='members' action='manage'}?auth={Core_Payment_Encode::encode($v.id)}" target="_blank">login</a> |{/if}
		<a href="{url name='members' action='manage' wg="resend={$v.id}"}" class="resend">resend&nbsp;password</a>
	</td>
</tr>
{/foreach}
	<tr>
		<td colspan="7">{include file="../../pgg_backend.tpl"}</td>
	</tr>
</tbody>
</table>
</form>
<script>
window.addEvent('domready',function(){
	checkboxFullToggle($('sel'));
	$('go').addEvent('click',function(e){
		if( $('go-action').get('value')=='delete'&&!confirm('WARNING! All data will be deleted!') ){
			e.stop();
			return false;
		}
		if( $('go-action').get('value')=='delete'&&!confirm('You are sure? We can\'t recover the data!') ){
			e.stop();
			return false;
		}
	});
	$$('.resend').addEvent('click',function(e){
		if( !confirm('Change password and send email to user?')){
			e.stop();
			return false;
		}
	});
});
</script>