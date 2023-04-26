{if $arrPrm.action=='just_install_me'}{elseif $arrPrm.action}
	<h1>{$arrPrm.title}</h1>
	{include file="conf_`$arrPrm.action`.tpl"}
{else}
	wrong action!
{/if}
