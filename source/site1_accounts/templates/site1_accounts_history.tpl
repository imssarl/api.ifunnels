<div>
{if !$arrList}
	<div style="float:left; width: 100%">
		<div class="red" style="margin: 80px auto; width: 100%; text-align:center;"><b>no sites found</b></div>
	</div>
{else}
<form method="post" action="" id="u_list" name="u_list">
<table class="info glow" style="width:90%;">
	<tr><td colspan="7">
	{include file="../../pgg_frontend.tpl"}
	</td></tr>
<thead>
<tr>
	<th>Site type
		{if $arrPg.recall>1}
			{if $arrFilter.order!='site_type--up'}<a href="{url name='site1_accounts' action='history' wg='order=site_type--up'}"><img src="/skin/i/backend/up.gif" width="5" height="11" alt="" /></a>{else}<img src="/skin/i/backend/up_off.gif" width="5" height="11" alt="" />{/if}{if $arrFilter.order!='site_type--dn'}<a href="{url name='site1_accounts' action='history' wg='order=site_type--dn'}"><img src="/skin/i/backend/down.gif" width="5" height="11" alt="" /></a>{else}<img src="/skin/i/backend/down_off.gif" width="5" height="11" alt="" />{/if}
		{/if}
	</th>
	<th>Category
		{if $arrPg.recall>1}
			{if $arrFilter.order!='category--up'}<a href="{url name='site1_accounts' action='history' wg='order=category--up'}"><img src="/skin/i/backend/up.gif" width="5" height="11" alt="" /></a>{else}<img src="/skin/i/backend/up_off.gif" width="5" height="11" alt="" />{/if}{if $arrFilter.order!='category--dn'}<a href="{url name='site1_accounts' action='history' wg='order=category--dn'}"><img src="/skin/i/backend/down.gif" width="5" height="11" alt="" /></a>{else}<img src="/skin/i/backend/down_off.gif" width="5" height="11" alt="" />{/if}
		{/if}
	</th>
	<th>URL
		{if $arrPg.recall>1}
			{if $arrFilter.order!='url--up'}<a href="{url name='site1_accounts' action='history' wg='order=url--up'}"><img src="/skin/i/backend/up.gif" width="5" height="11" alt="" /></a>{else}<img src="/skin/i/backend/up_off.gif" width="5" height="11" alt="" />{/if}{if $arrFilter.order!='url--dn'}<a href="{url name='site1_accounts' action='history' wg='order=url--dn'}"><img src="/skin/i/backend/down.gif" width="5" height="11" alt="" /></a>{else}<img src="/skin/i/backend/down_off.gif" width="5" height="11" alt="" />{/if}
		{/if}
	</th>
	<th>Edit site
		{if $arrPg.recall>1}
			{if $arrFilter.order!='main_keyword--up'}<a href="{url name='site1_accounts' action='history' wg='order=main_keyword--up'}"><img src="/skin/i/backend/up.gif" width="5" height="11" alt="" /></a>{else}<img src="/skin/i/backend/up_off.gif" width="5" height="11" alt="" />{/if}{if $arrFilter.order!='main_keyword--dn'}<a href="{url name='site1_accounts' action='history' wg='order=main_keyword--dn'}"><img src="/skin/i/backend/down.gif" width="5" height="11" alt="" /></a>{else}<img src="/skin/i/backend/down_off.gif" width="5" height="11" alt="" />{/if}
		{/if}
	</th>
	<th>Template name
		{if $arrPg.recall>1}
			{if $arrFilter.order!='template_name--up'}<a href="{url name='site1_accounts' action='history' wg='order=template_name--up'}"><img src="/skin/i/backend/up.gif" width="5" height="11" alt="" /></a>{else}<img src="/skin/i/backend/up_off.gif" width="5" height="11" alt="" />{/if}{if $arrFilter.order!='template_name--dn'}<a href="{url name='site1_accounts' action='history' wg='order=template_name--dn'}"><img src="/skin/i/backend/down.gif" width="5" height="11" alt="" /></a>{else}<img src="/skin/i/backend/down_off.gif" width="5" height="11" alt="" />{/if}
		{/if}
	</th>
	<th>Profile name
		{if $arrPg.recall>1}
			{if $arrFilter.order!='profile_name--up'}<a href="{url name='site1_accounts' action='history' wg='order=profile_name--up'}"><img src="/skin/i/backend/up.gif" width="5" height="11" alt="" /></a>{else}<img src="/skin/i/backend/up_off.gif" width="5" height="11" alt="" />{/if}{if $arrFilter.order!='profile_name--dn'}<a href="{url name='site1_accounts' action='history' wg='order=profile_name--dn'}"><img src="/skin/i/backend/down.gif" width="5" height="11" alt="" /></a>{else}<img src="/skin/i/backend/down_off.gif" width="5" height="11" alt="" />{/if}
		{/if}
	</th>
	<th>Last modified
		{if $arrPg.recall>1}
			{if $arrFilter.order!='edited--up'}<a href="{url name='site1_accounts' action='history' wg='order=edited--up'}"><img src="/skin/i/backend/up.gif" width="5" height="11" alt="" /></a>{else}<img src="/skin/i/backend/up_off.gif" width="5" height="11" alt="" />{/if}{if $arrFilter.order!='edited--dn'}<a href="{url name='site1_accounts' action='history' wg='order=edited--dn'}"><img src="/skin/i/backend/down.gif" width="5" height="11" alt="" /></a>{else}<img src="/skin/i/backend/down_off.gif" width="5" height="11" alt="" />{/if}
		{/if}
	</th>
</tr>
</thead>
<tbody>
{foreach from=$arrList key='k' item='v'}
<tr{if $k%2=='0'} class="matros"{/if}>
	<td>{if $v.site_type==Project_Sites::CNB}CNB{elseif $v.site_type==Project_Sites::NCSB}NCSB{elseif $v.site_type==Project_Sites::BF}Blog Fusion{elseif $v.site_type==Project_Sites::NVSB}NVSB{elseif $v.site_type==Project_Sites::PSB}PSB{/if}</td>
	<td>{if $v.category_id}{$v.category}{else}no selected{/if}</td>
	<td><a href="{$v.url}">{$v.url}</a></td>
	<td><a href="{if $v.site_type==Project_Sites::CNB}{url name='site1_cnb' action='edit'}?id={$v.id}{elseif $v.site_type==Project_Sites::NCSB}{url name='site1_ncsb' action='edit'}?id={$v.id}{elseif $v.site_type==Project_Sites::BF}{url name='site1_blogfusion' action='general'}?id={$v.id}{elseif $v.site_type==Project_Sites::NVSB}{url name='site1_nvsb' action='edit'}?id={$v.id}{elseif $v.site_type==Project_Sites::PSB}{url name='site1_psb' action='edit'}?id={$v.id}{/if}">{$v.main_keyword}</a></td>
	<td>{if $v.template_name}<a href="{if $v.site_type==Project_Sites::CNB}{url name='site1_cnb' action='edit'}?id={$v.id}&template=change{elseif $v.site_type==Project_Sites::NCSB}{url name='site1_ncsb' action='edit'}?id={$v.id}&template=change{elseif $v.site_type==Project_Sites::BF}{url name='site1_blogfusion' action='changetheme'}?id={$v.id}{elseif $v.site_type==Project_Sites::NVSB}{url name='site1_nvsb' action='edit'}?id={$v.id}&template=change{elseif $v.site_type==Project_Sites::PSB}{url name='site1_psb' action='edit'}?id={$v.id}&template=change{/if}">{$v.template_name}</a>{else}-{/if}</td>
	<td>{if $v.profile_name}<a href="{url name='site1_profile' action='edit'}?id={$v.profile_id}">{$v.profile_name}</a>{else}-{/if}</td>
	<td>{$v.edited|date_format:$config->date_time->dt_full_format}</td>
</tr>
{/foreach}
</tbody>
<tfoot>
	<tr><td colspan="7">
	{include file="../../pgg_frontend.tpl"}
	</td></tr>
</tfoot>
</table>
{/if}
</div>