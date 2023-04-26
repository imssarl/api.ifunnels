<table class="info glow" id="modules_list">
<form method="post" action="{Core_Module_Router::$uriFull}" id="modules_manage">
<input type="hidden" name="arrM[mode]" value="{$arrM.order}" id='mode'>
<input type="hidden" name="arrM[name]" value="{$arrM.order}" id='name'>
<thead>
	<tr>
		<th>file</th>
		<th>title</th>
		<th>date</th>
		<th>actions</th>
	</tr>
</thead>
{foreach from=$arrMod key='k' item='v'}
<tr{if $k%2=='0'} class="matros"{/if}>
	<td>{$v.name}.class.php</td>
	<td>{$v.title|default:"-"}</td>
	<td>{$v.added|date_format:$config->date_time->dt_full_format|default:"not installed"}</td>
	<td>{if $v.added}
		<a href="#" id="update-{$v.name}">update</a>
		<a href="#" id="reinstall-{$v.name}">re-install</a>
		<a href="#" id="uninstall-{$v.name}">un-install</a>
	{else}
		<a href="#" id="install-{$v.name}">install</a>{/if}</td>
</tr>
{/foreach}
</form>
</table>
<script type="text/javascript">
$$('#modules_list a').each(function(el){
	el.addEvent('click',function(e){ 
		var array=this.get('id').split('-');
		$('mode').value=array[0];
		$('name').value=array[1];
		$('modules_manage').submit();
		return false;
	 });
});
</script>