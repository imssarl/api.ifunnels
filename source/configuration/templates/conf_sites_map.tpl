<div style="width:100%;">
	<form method="post" action="" id="sitemap">
	<input type="hidden" name="arrTree[mode]" value="" id="arrTree_mode" />
	<input type="hidden" name="arrTree[id]" value="" id="arrTree_id" />
	</form>
	<div class="string">
		{if $arrUser.id}
		<ul>
			<li>select site tree:<li>
			{foreach from=$arrSites item='v'}
			<li><a href="{url name='configuration' action='sites_map'}?root_id={$v.root_id}" title="select the site">({if $v.flg_type==1}b{else}f{/if}) {if $smarty.get.root_id==$v.root_id}<b>{$v.title}</b>{else}{$v.title}{/if}</a></li>
			{/foreach}
			{if $smarty.get.root_id&&$arrCurrentSite.flg_type==0}
			<li>or <a href="{url name='configuration' action='set_page'}?root_id={$smarty.get.root_id}">create new page</a><li>
			{/if}
		</ul>
		{/if}
	</div>
{if $arrTree}
	</div style="width:100%;">
		<div style="float:left;" id="site_tree">
{function name=recursion}
	{if $tree}
		<ul class="list">
		{foreach name='loop' from=$tree item='v'}
		<li><a href="{url name='configuration' action='set_page'}?root_id={$smarty.get.root_id}&id={$v.id}">{$v.title}</a><span class="list">
			действия: [ 
			<a href="#" id="page_site-{$v.id}"{if !$v.flg_onmap} class="red"{/if}>{if $v.flg_onmap}скрыть{else}показать{/if}</a> |
			{if !$smarty.foreach.loop.first}
			<a href="#" id="page_up-{$v.id}">вверх</a> | 
			{/if}
			{if !$smarty.foreach.loop.last}
			<a href="#" id="page_dn-{$v.id}">вниз</a> | 
			{/if}
			<a href="{url name='configuration' action='set_page'}?root_id={$smarty.get.root_id}&pid={$v.id}">добавить подкатегорию</a> | 
			<a href="http://{Core_Module_Router::$domain}{$v.sys_name}" target="_blank">просмотр</a> | 
			<a href="#" id="page_del-{$v.id}" class="red">удалить</a> ]</span>
		</li>
		{if $v.node}
			{recursion tree=$v.node}
		{/if}
		{/foreach}
		</ul>
	{else}
	{/if}
{/function}
{recursion tree=$arrTree}
		</div>
		<div style="clear:both;"></div>
	</div>
<script type="text/javascript">
$$('#site_tree a').each(function(el){
	el.addEvent('click',function(e){ 
		var array=this.get('id').split('-');
		$('arrTree_mode').value=array[0];
		$('arrTree_id').value=array[1];
		$('sitemap').submit();
		return false;
	 });
});
</script>
{/if}
</div>