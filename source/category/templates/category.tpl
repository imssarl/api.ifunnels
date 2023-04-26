{if $arrPrm.action}
	{if !in_array( $arrPrm.action, array( 'catsOuter' ) )}
	<h1>{$arrPrm.title}</h1>
	{/if}
	{if in_array( $arrPrm.action, array( 'cats', 'catsOuter' ) )}
		{include file="category_cats.tpl"}
	{else}
		{include file="category_`$arrPrm.action`.tpl"}
	{/if}
{else}
	wrong action!
{/if}