<div class="breadcrumb">
	<ul>
		<li class="first"><a href="{Core_Module_Router::$offset}">Home</a></li>
		{foreach from=$arrCurDirect item='node'}
			<li><a href="{$node.sys_name}">{$node.title}</a></li>
		{/foreach}
	</ul>
</div>