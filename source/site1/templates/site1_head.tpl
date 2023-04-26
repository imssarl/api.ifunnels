	<title>{if $arrCurReverse}{foreach from=$arrCurReverse item='node'}{$node.title} / {/foreach}{/if}{$smarty.const.PROJECT_DOMAIN}</title>
	<meta content="text/html; charset=utf-8" http-equiv="content-type" />
	<meta name="Robots" content="{if !$arrCurReverse.0.meta_robots}NO{/if}INDEX, FOLLOW" />
	{if $arrCurReverse.0.meta_keywords}<meta name="keywords" content="{$arrCurReverse.0.meta_keywords}" />{/if}
	{if $arrCurReverse.0.meta_description}<meta name="description" content="{$arrCurReverse.0.meta_description}" />{/if}