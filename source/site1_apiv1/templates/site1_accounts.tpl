{if $arrPrm.action}
	{include file="site1_accounts_`$arrPrm.action`.tpl"}
{else}
	wrong action!
{/if}