{if $arrPg.num}
<div style="width:auto;float:right;">
	{if $arrPg.recall}
	<span>Total {$arrPg.recall} record{if $arrPg.recall>1}s{/if}</span>
	{/if}
	{if $arrPg.recfrom!=1}
		<a href="{$arrPg.urlmin}" class="pg_handler"><img src="/skin/i/backend/paging/sr_first.gif" alt="First" title="First" alt="" /></a>
	{else}
		<img src="/skin/i/backend/paging/sr_first_gray.gif" alt="First" title="First" border="0" />
	{/if}
	{if $arrPg.urlminus}
		<a href="{$arrPg.urlminus}" class="pg_handler"><img src="/skin/i/backend/paging/sr_prev.gif" alt="" /></a>
	{else}
		<img src="/skin/i/backend/paging/sr_prev_gray.gif" alt="Previous" title="Previous" border="0" />
	{/if}
	{foreach from=$arrPg.num item='v'}
		{if $v.sel}
			<span><b>{$v.number}</b></span>	
		{else}
			<a href="{$v.url}" class="pg_handler">{$v.number}</a>
		{/if}
	{/foreach}
	{if $arrPg.urlplus}
		<a href="{$arrPg.urlplus}" class="pg_handler"><img src="/skin/i/backend/paging/sr_next.gif" alt="" /></a>
	{else}
		<img src="/skin/i/backend/paging/sr_next_gray.gif" alt="Next" title="Next" border="0" />
	{/if}
	{if $arrPg.recall!=$arrPg.recto}
		<a href="{$arrPg.urlmax}" class="pg_handler"><img src="/skin/i/backend/paging/sr_last.gif" alt="" /></a>
	{else}
		<img src="/skin/i/backend/paging/sr_last_gray.gif" alt="Last" title="Last" border="0" />
	{/if}
</div>
{/if}