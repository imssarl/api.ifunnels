	<ol>
		<li>
			<label>Category: <em>*</em></label>
			<select class="required edit_{$i.flg_source}" name="arrCnt[{$i.flg_source}][settings][category_id]">
				<option value=''> - select - </option>
				{html_options options=$articles selected={$arrCnt.{$i.flg_source}.settings.category}}
			</select>
		</li>
		<li>
			<label>Search by tags: <em>*</em></label>
			<input size="40" class="required edit_{$i.flg_source}" type="text" name="arrCnt[{$i.flg_source}][settings][tags]" value="{if !empty($arrCnt.{$i.flg_source}.settings.tags)}{$arrCnt.{$i.flg_source}.settings.tags}{/if}"/>
		</li>
	</ol>
	{literal}<script type="text/javascript">
window.addEvent('domready', function() {

// changer для выбора типа контента
$$('#edit_{/literal}{$i.flg_source}{literal}').addEvent('change',function(event){
	if ( $chk($$('#edit_{/literal}{$i.flg_source}{literal}').get('value') ))
		$$('.no_source_selected').setStyle('display','none');
	else
		$$('.no_source_selected').setStyle('display','');
});

});
</script>{/literal}