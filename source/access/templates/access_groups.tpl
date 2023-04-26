<div style="padding-bottom:10px;">
<div>Tips:</div>
<div>- for add new group input "title" and "system name" fields in first row</div>
</div>
<p><input type="submit" value="Submit" class="subbut" /></p>
<form action="" id="current-form" method="post">
<table class="info glow" style="width:98%">
<thead>
	<tr>
		<th style="padding-right:0;"><input type="checkbox" id="del" class="tooltip no-click" title="mass delete" rel="check to select all" /></th>
		<th id='moveto' width="0"></th>
		<th>Title</th>
		<th>System name</th>
		<th>Description</th>
	</tr>
</thead>
<tbody>
	<tr>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td valign="top"><input type="text" class="elogin" name="arrGroups[0][title]" value="" /></td>
		<td valign="top"><input type="text" class="elogin" name="arrGroups[0][sys_name]" value="" /></td>
		<td><textarea name="arrGroups[0][description]" class="elogin"></textarea></td>
	</tr>
	<tr>
		<td colspan="5">{include file="../../pgg_backend.tpl"}</td>
	</tr>
	{foreach $arrList as $v}
	<tr{if ($v@iteration-1) is div by 2} class="matros"{/if}>
		<input type="hidden" name="arrGroups[{$v.id}][id]" value="{$v.id}"/>
		<td style="padding-right:0;" valign="top"><input type="checkbox" name="arrGroups[{$v.id}][del]" class="check-me-del" id="check-{$v.id}" value="{$v.id}" /></td>
		<td valign="top"><select name="arrGroups[{$v.id}][moveto]" class="moveto-select" style="display: none; width:150px;" ><option value="0">delete all users{foreach $arrList as $item}<option value="{$item.id}">move to {$item.title}{/foreach}</select></td>
		<td valign="top"><input type="text" class="elogin" name="arrGroups[{$v.id}][title]" value="{$v.title}" /></td>
		<td valign="top"><input type="text" class="elogin" name="arrGroups[{$v.id}][sys_name]" value="{$v.sys_name}" /></td>
		<td><textarea name="arrGroups[{$v.id}][description]" class="elogin">{$v.description}</textarea></td>
	</tr>
	{/foreach}
	<tr>
		<td colspan="5">{include file="../../pgg_backend.tpl"}</td>
	</tr>
</tbody>
</table>
</form>
<div><input type="submit" value="Submit" class="subbut" /></div>

<script type="text/javascript">
var json='{$arrList|json}';
{literal}
$$('.check-me-del').each(function(el){
	el.addEvent('click',function(e){
		if(!el.checked){ // добавить
			el.getParent('td').getNext('td').getChildren('select')[0].setStyle('display','none');
			if( !$$('.check-me-del').some(function(item){ if(item.checked) return true; }) ){
				$('moveto').set('html','').setStyle('width','0px');
			}
			var group={};
			var groups=JSON.decode(json);
			groups.each(function( item ){
				if( item.id==el.value ){
					group=item;
					return;
				}
			});

			$$('.moveto-select').each(function(select){
				var option=new Element('option',{value:group.id,html:'move to '+group.title});
				option.inject( select );
				sortSelect(select);
			});
		} else {
			el.getParent('td').getNext('td').getChildren('select')[0].setStyle('display','block');
			$('moveto').set('html','User reassign').setStyle('width','160px');
			$$('.moveto-select').each(function(item){
				Object.each(item.options,function(option){
					if(el.value==option.value){
						option.destroy();
					}
				});
			});
		}

	});
});
var sortSelect=function (select) {
		var nodes = select.options;
		var len = nodes.length;
		var sorted = new Array();
		while (nodes[0]) {
			sorted.push(new String(nodes[0].get('html')));
			sorted[sorted.length-1].element = nodes[0];
			nodes[0].destroy();
		}
		sorted = sorted.sort();
		for (var i = 0; i < len; i++) {
			sorted[i].element.inject(select);
		}
		Object.each(select.options, function(option){
			if( option.value==0 ){
				option.selected=true;
			}
		});
};
$$('.moveto-select').each(function(select){
	sortSelect(select);
});
$$('.subbut').addEvent('click',function(e){
	e && e.stop();
	if ($$('.check-me-del').some(function(item){
		return item.checked==true;
	})) {
		if(!confirm('Your sure to delete selected items?')) return;
	}
	$('current-form').submit();
});
checkboxFullToggle($('del'));
{/literal}
</script>