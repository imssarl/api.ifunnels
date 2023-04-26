{if $arrPrm.action}
	<h1>{$arrPrm.title}{if $arrG&&$arrPrm.action=='adm_set_user'} as {foreach from=$arrG key='k' item='grp' name='loop'}{$grp}{if !$smarty.foreach.loop.last},{/if}{/foreach}{/if}
</h1>
	{include file="a_`$arrPrm.action`.tpl"}
{else}
	wrong action!
{/if}