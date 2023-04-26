<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<link rel="stylesheet" type="text/css" href="/skin/_css/backend.css" />
	<link rel="stylesheet" type="text/css" href="/skin/_css/tips.css" />
	<script type="text/javascript" src="/skin/_js/mootools.js"></script>
</head>
<body style="padding:15px; background:none;">
<h2>Table: {$smarty.get.table}</h2>
{literal}<style>td div{font-size:12px !important; font-family:Arial !important;}</style>{/literal}
<div align="left" style="padding:20px;">
{include file="../../pgg_frontend.tpl"}
</div>
<table id="table" width="100%" class="info glow" style="border:1px solid #E0E6EB; border-collapse:collapse;">
	<tr>
		{foreach from=$arrColumns item=i}
		<th  align="center" style="background:#E0E6EB;border:1px solid #E0E6EB;">{$i}</th>
		{/foreach}
	</tr>
	{foreach from=$arrList item=i}
	<tr class="line">
		{foreach from=$arrColumns item=j}
		<td valign="top" style="border:1px solid #E0E6EB;"><div>{if strlen($i[$j])>200}{$i[$j]|truncate:200:'...&nbsp;<a href="#" class="show-all">show&nbsp;all</a>'}{else}{$i[$j]}{/if}</div><div style="display:none;">{$i[$j]}&nbsp;<a href="#" class="hide-all">hide</a></div></td>
		{/foreach}
	</tr>
	{/foreach}
</table>
{literal}
<script type="text/javascript">
window.addEvent('domready',function(){
	$$('.line').each(function(tr){
		tr.addEvent('click',function(e){
			tr.setStyle('background', (tr.style.background!='')? '':'#E0E6EB');
		});
	});
	$$('.show-all').each(function(el){
		el.addEvent('click',function(e){
			e.stop();
			el.getParent('div').hide();
			el.getParent('div').getNext('div').show('block');
		});
	});
	$$('.hide-all').each(function(el){ 
		el.addEvent('click',function(e){ 
			e.stop();
			el.getParent('div').hide();
			el.getParent('div').getPrevious('div').show('block');
		});
	});
});
</script>
{/literal}
<div align="left" style="padding:30px;">
{include file="../../pgg_frontend.tpl"}
</div>
</body>
</html>