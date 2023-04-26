{if !$arrType}
	Category type not set or not exists.
{elseif $arrType.type=='nested'}
<form>
	Select parent node: <select name="arrCats[pid]" id="category-type" class="elogin" style="width:200px;">
		<option value=''>tree root node</option>
		{include file='category_inc_treeopt.tpl' tree=$arrTree selected=$smarty.get.pid}
	</select>
</form>
<script type="text/javascript">
{literal}
$('category-type').addEvent('change',function(e){
	e.stop();
	var myURI=new URI();
	if ( this.value=='' ) {
		myURI.setData(new Hash(myURI.getData()).filter(function(value, key){return key!='pid';}));
	} else {
		myURI.setData({pid:this.value}, true);
	}
	myURI.go();
});
{/literal}
</script>
<p>
	<input type="submit" value="Submit" id="submit" />
	<input type="submit" value="Show Root Node" id="root" />
</p>
<form action="" id="current-form" method="post" >
<table class="info glow">
<thead>
	<tr>
		<th style="padding-right:0;"><input type="checkbox" id="del" class="tooltip" title="mass delete" rel="check to select all categories" /></th>
		<th width="1px">&nbsp;</th>
		<th width="70%">title</th>
		<th width="20%">priority (from 0 to 999)</th>
		<th width="10%">&nbsp;</th>
	</tr>
</thead>
	<tr>
		<td>add:</td>
		<td>{if $arrError.0}<span class="red">*</span>{/if}</td>
		<td valign="top"><input type="text" class="elogin" name="arrCats[0][title]" value="" /></td>
		<td><input type="text" class="elogin" name="arrCats[0][priority]" value="" /></td>
		<td>&nbsp;</td>
	</tr>
	{foreach from=$arrCats key='k' item='v'}
	<tr{if $k%2=='0'} class="matros"{/if}>
		<input type="hidden" name="arrCats[{$v.id}][id]" value="{$v.id}" />
		<td style="padding-right:0;" valign="top"><input type="checkbox" name="arrCats[{$v.id}][del]" class="check-me-del" id="check-{$v.id}" /></td>
		<td>{if $arrError.$k}<span class="red">*</span>{/if}</td>
		<td valign="top">
		{foreach from=Core_Language::$flags item=lang key=lang_id}
		{assign var=flg_lang value=1}
			<input type="text" {if empty($lang.def)} style="display:none" {/if} class="elogin wh-lang-input {$lang.ico|replace:'.gif':''}" name="arrCats[{$v.id}][title_lng][{$lang_id}]" value="{$v.title_lng[$lang_id]}">
		{/foreach}		
			<input type="text" class="elogin {if !empty($flg_lang)}language{/if}" name="arrCats[{$v.id}][title]" value="{$v.title}" />
		</td>
		<td valign="top"><input type="text" class="elogin" name="arrCats[{$v.id}][priority]" value="{$v.priority}" /></td>
		<td valign="top" class="option">
			<a href="#" class="click-me-del" id="{$v.id}">delete</a> | 
			<a href="{url f=Core_Module_Router::$uriVar wg="pid={$v.id}"}">subdirs</a>
		</td>
	</tr>
	{/foreach}
</table>
</form>
<link href="/skin/_js/wh-language/language.css" rel="stylesheet" type="text/css" />
<script language="javascript" type="text/javascript" src="/skin/_js/wh-language/language.js"></script>
{literal}
<script type="text/javascript">
window.addEvent('domready',function(){
	$('submit').addEvent('click',function(e){
		e && e.stop();
		if ($$('.check-me-del').some(function(item){
			return item.checked==true;
		})) {
			if(!confirm('Your sure to delete selected items?')) {
				return;
			}
		}
		$('current-form').submit();
	});
	new Wh_Languages({
	'jsonLang':'{/literal}{Core_Language::$flags|json}{literal}',
	'elementsClass':'language'
	});


	$('root').addEvent('click',function(e){
		e && e.stop();
		var myUri= new URI(location.href);
		var id=myUri.getData('type_id');
		myUri.clearData();
		myUri.setData('type_id',id);
		myUri.go();
	});
	checkboxToggle($('del'));
	$$('.click-me-del').addEvent('click',function(e){
		e && e.stop();
		if ( !$('check-'+this.get('id')).get('checked') ) {
			$('check-'+this.get('id')).set('checked',true);
			if ($('check-'+this.get('id')).get('checked')) {
				$('submit').fireEvent('click');
			}
			$('check-'+this.get('id')).set('checked',false);
		}
	});
});
</script>
{/literal}

{else}
<div style="width:89%;padding-right:1%">
<table class="info glow" style="width:100%;">
<form method="post" action="">
<thead>
	<tr>
		<th width="1px">del</th>
		{foreach from=$arrFlags item='v'}
		<th width="1px">{$v}</th>
		{/foreach}
		<th width="1px">&nbsp;</th>
		<th>cat name</th>
		{if $arrType.flg_sort}
		<th width="1px">priority</th>
		{/if}
	</tr>
</thead>
	{foreach from=$arrCats key='k' item='v'}
	<tr{if $k%2=='0'} class="matros"{/if}>
		<input type="hidden" name="arrCats[{$v.id}][id]" value="{$v.id}">
		<td style="padding-right:0;"><input type="checkbox" name="arrCats[{$v.id}][del]" /></td>
		{foreach from=$arrFlags key='fk' item='f'}
		{assign var="name" value="flag`$fk`"}
		<td style="padding-right:0;"><input type="checkbox" name="arrCats[{$v.id}][flag{$fk}]"{if $v.$name} checked{/if} /></td>
		{/foreach}
		<td>{if $arrErr.$k}<span class="red">*</span>{/if}</td>
		<td valign="top"><input type="text" class="elogin" name="arrCats[{$v.id}][title]" value="{$v.title}" /></td>
		{if $arrType.flg_sort}
		<td valign="top"><input type="text" class="elogin" name="arrCats[{$v.id}][priority]" value="{$v.priority}" /></td>
		{/if}
	</tr>
	{/foreach}
	<tr>
		<input type="hidden" name="arrCats[0][id]" value="0">
		<td>&nbsp;</td>
		{foreach from=$arrFlags key='fk' item='f'}
		<td style="padding-right:0;"><input type="checkbox" name="arrCats[0][flag{$fk}]" /></td>
		{/foreach}
		<td>{if $arrErr.0}<span class="red">*</span>{/if}</td>
		<td valign="top"><input type="text" class="elogin" name="arrCats[0][title]" value="" /></td>
		{if $arrType.flg_sort}
		<td valign="top"><input type="text" class="elogin" name="arrCats[0][priority]" value="" /></td>
		{/if}
	</tr>
</table>
<div><input type="image" src="/skin/i/backend/bt_update.gif" style="margin-top:20px;" /></div>
</form>
</div>
{/if}