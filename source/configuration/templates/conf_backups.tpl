<div><a href="{Core_Module_Router::$uriFull}?backup_sys=1">archiving of system data</a></div>
<div style="width:90%;">
<table>
	<tr>
		<td>
	<form method="post" action="" name="g_set" id="g_set">
	<table width="100%">
	<tr>
		<td colspan="3" class="for_checkbox">
			<b>Select table</b> 
			<span>(</span><label for="g_sel_all">select all</label>
			<input type="checkbox" onClick="toggle_checkbox('g_set',this);" id="g_sel_all" /><span>):</span>
		</td>
	</tr>
	{foreach from=$arrTables key='k' item='v'}
		{if $k%3==0}
	</tr>
	<tr>
		{/if}
		<td class="for_checkbox">
			<input type="checkbox" name="arrSet[tables][{$k}]" value="{$v}" id="g_{$k}">
			<label for="g_{$k}" style="width:130px;"><a href="{url name='configuration' action='view_table'}?table={$v}" title="View table: {$v}" rel="width:{$arrUser.arrSettings.popup_width},height:{$arrUser.arrSettings.popup_height}" class="mb">{$v|truncate:19}</a></label>
		</td>
	{/foreach}
	</table>
<div style="width:90%;text-align:center;clear:both;padding-top: 20px;"><a href="#" onclick="g_set.submit();return false;">archive</a></div>
	</form>
		</td>
	{if $arrDumps}
		<td valign="top">
	<div style="float:right;">
	<table>
	<tr>
		<td colspan="5">
			<b>Backup</b> 
		</td>
	</tr>
	{foreach from=$arrDumps key='k' item='v'}
	<tr>
		<td><a href="{$config->path->html->db_backup}{$v.name}" title="download file ({$v.size} byte)" target="_blank">{$v.name}</a></td>
		<td>{$v.frendly_size}</td>
		<td><a href="{Core_Module_Router::$uriFull}?restore={$v.name}" title="restore dump">restore</a></td>
		<td>{$v.date|date_local:$config->date_time->dt_full_format}</td>
		<td><a href="{Core_Module_Router::$uriFull}?delete={$v.name}" title="delete file">X</a></td>
	</tr>
	{/foreach}
	</table>
	</div>
		</td>
	{/if}
	</tr>
</table>
</div>

{literal}
<script type="text/javascript">
window.addEvent('domready',function(){
	$$('.mb').cerabox({
		group: false,
		width:'80%',
		height:'80%',
		displayTitle: true,
		titleFormat: '{title}'
	})
});
</script>
{/literal}