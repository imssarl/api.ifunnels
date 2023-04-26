<div>
<form method="post" class="wh" action="{Core_Module_Router::$uriFull}" name="r_set" id="r_set">
<input type="hidden" name="change_group" value="" id="change_group">
<div><b>Select Group</b>: <select name="arrR[group_id]" class="elogin" style="width:50%;" onchange="$('change_group').value=1;r_set.submit();">
	<option value="0"> - select - </option>
	{html_options options=$arrG selected=$smarty.request.group_id}
</select></div>
<fieldset>
	<legend>Select Rights</legend>
	<ol>
		<li>
			<label for="g_sel_all">select all</label><input type="checkbox" onClick="toggle_checkbox('r_set',this);" id="g_sel_all" />
		</li>
	</ol>

</fieldset>
<div>
	{foreach from=$templates item=i key=type}
	<fieldset>
		<legend>{$type}</legend>
		<ol>
			<li>
				<label>Selecct All</label><input type="checkbox" class="select-all" value="{$type}" />
			</li>
			{foreach from=$i item=template}
			<li>
				<input type="hidden" value="0" name="{$type}[{$template.id}]" />
				<label style="cursor: pointer;" for="id-{$template.id}">
					<a href="#" class="screenshot" rel="<img src='{$template.preview}'>" style="text-decoration:none">{$template.title}</a>&nbsp;</label><input type="checkbox" class="{$type}-checkbox" id="id-{$template.id}" value="{$template.id}" {if in_array($template.id,$templateIds[$type])} checked="1" {/if} name="{$type}[{$template.id}]" />
			</li>
			{/foreach}
		</ol>
	</fieldset>
	{/foreach}
</div>
<div style="width:90%;text-align:center;clear:both;padding-top: 20px;">
	<a href="#" onclick="r_set.submit();return false;">{if $smarty.request.group_id}update{else}attach{/if} rights</a></div>
</form>
</div>

{literal}
<script type="text/javascript">

window.addEvent('domready', function(){
	var optTips2 = new Tips('.tips');
	var optTips = new Tips('.screenshot');
	$$('.screenshot').each(function(el){ el.addEvent('click',function(e){ e.stop(); }); });
	$$('.select-all').each(function(input){
		input.addEvent('click',function(){
			$$('.'+input.get('value')+'-checkbox').each(function(el){
				el.set('checked',input.get('checked'));
			});
		});
	});
});
</script>
{/literal}