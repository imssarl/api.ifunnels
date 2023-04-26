{if $tree}
	{foreach from=$tree item='v'}
	<option value="{if $value}{$v[$value]}{else}{$v.id}{/if}"{if $value}{if $v[$value]==$selected} selected{/if}{elseif $v.id==$selected} selected{/if}>{$v.title|indent:$v.level:"-&nbsp;"}</option>
	{if $v.node}
		{include file="category_inc_treeopt.tpl" tree=$v.node selected=$selected value=$value}
	{/if}
	{/foreach}
{/if}