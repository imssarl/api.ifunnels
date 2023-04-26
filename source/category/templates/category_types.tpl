<p>
	<input type="submit" value="Submit" id="submit" />
</p>
<form action="" id="current-form" method="post">
<table class="info glow" style="width:98%">
<thead>
	<tr>
		<th style="padding-right:0;"><input type="checkbox" id="del" class="tooltip no-click" title="mass delete" rel="check to select all types" /></th>
		<th width="10%">type</th>
		<th width="10%">sort by</th>
		<th width="10%">link</th>
		<th width="1px">users</th>
		<th width="1px">i18n</th>
		<th width="10%">def</th>
		<th width="1px">&nbsp;</th>
		<th width="20%">sys name</th>
		<th width="15%">storage</th>
		<th width="25%">description</th>
		<th width="10%">&nbsp;</th>
	</tr>
</thead>
	<tr>
		<td>&nbsp;</td>
		<td style="padding-right:0px;" valign="top">
			<select name="arrTypes[{$v.id}][type]" class="elogin">
				{html_options options=$arrShema}
			</select>
		</td>
		<td style="padding-right:0px;" valign="top">
			<select name="arrTypes[0][flg_sort]" class="elogin">
				{html_options options=$arrSort}
			</select>
		</td>
		<td style="padding-right:0px;" valign="top">
			<select name="arrTypes[0][flg_typelink]" class="elogin">
				{html_options options=$arrLink}
			</select>
		</td>
		<td style="padding-right:0px;" valign="top"><input type="checkbox" name="arrTypes[0][flg_user]" /></td>
		<td style="padding-right:0px;" valign="top"><input type="checkbox" name="arrTypes[0][flg_multilng]" /></td>
		<td style="padding-right:0px;" valign="top">
			<select name="arrTypes[0][flg_deflng]" class="elogin">
				<option value=''>- select -</option>
				{html_options options=Core_Language::$lang}
			</select>
		</td>
		<td>{if $arrErr.0.title}<span class="red">*</span>{/if}</td>
		<td valign="top"><input type="text" class="elogin" name="arrTypes[0][title]" value="" /></td>
		<td valign="top"><input type="text" class="elogin" name="arrTypes[0][storage]" value="" /></td>
		<td><textarea name="arrTypes[0][description]" class="elogin"></textarea></td>
		<td>&nbsp;</td>
	</tr>
	{foreach from=$arrTypes key='k' item='v'}
	<tr{if $k%2=='0'} class="matros"{/if}>
		<input type="hidden" name="arrTypes[{$v.id}][id]" value="{$v.id}"/>
		<td style="padding-right:0;" valign="top"><input type="checkbox" name="arrTypes[{$v.id}][del]" class="check-me-del" id="check-{$v.id}" /></td>
		<td style="padding-right:0px;" valign="top">
			<select name="arrTypes[{$v.id}][type]" class="elogin">
				{html_options options=$arrShema selected=$v.type}
			</select>
		</td>
		<td style="padding-right:0px;" valign="top">
			<select name="arrTypes[{$v.id}][flg_sort]" class="elogin">
				{html_options options=$arrSort selected=$v.flg_sort}
			</select>
		</td>
		<td style="padding-right:0px;" valign="top">
			<select name="arrTypes[{$v.id}][flg_typelink]" class="elogin">
				{html_options options=$arrLink selected=$v.flg_typelink}
			</select>
		</td>
		<td style="padding-right:0px;" valign="top"><input type="checkbox" name="arrTypes[{$v.id}][flg_user]"{if $v.flg_user} checked=""{/if} /></td>
		<td style="padding-right:0px;" valign="top"><input type="checkbox" name="arrTypes[{$v.id}][flg_multilng]"{if $v.flg_multilng} checked=""{/if} /></td>
		<td style="padding-right:0px;" valign="top">
			<select name="arrTypes[{$v.id}][flg_deflng]" class="elogin">
				<option value=''>- select -</option>
				{html_options options=Core_Language::$lang selected=$v.flg_deflng}
			</select>
		</td>
		<td>{if $arrErr.$k.title}<span class="red">*</span>{/if}</td>
		<td valign="top"><input type="text" class="elogin" name="arrTypes[{$v.id}][title]" value="{$v.title}" /></td>
		<td valign="top"><input type="text" class="elogin" name="arrTypes[{$v.id}][storage]" value="{$v.storage}" /></td>
		<td><textarea name="arrTypes[{$v.id}][description]" class="elogin">{$v.description}</textarea></td>
		<td class="option">
			<a href="{url name='category' action='cats'}?type_id={$v.id}" title="edit category list of <{$v.title}> type">category</a>
			{if $v.type=='flagged'} | <a href="{url name='category' action='flags'}?type_id={$v.id}" title="edit flag list of <{$v.title}> type">flags</a>{/if}
		</td>
	</tr>
	{/foreach}
</table>
</form>
{literal}
<script type="text/javascript">
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
</script>
{/literal}