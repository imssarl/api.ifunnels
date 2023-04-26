{if !empty($strError)}<div class="red">Error: {$strError}</div>{/if}
<form action="" method="POST" enctype="multipart/form-data" class="wh" >
<fieldset>
	<legend></legend>
	<ol>
		<li>
			<label>Groups: </label><select name="arr[groups_ids][]" multiple="1">{html_options options=$arrG selected=$smarty.get.arrFilter.group_id}</select>
		</li>
		<li>
			<label></label>
			<input type="submit" name="import" value="Export" />
		</li>
	</ol>
</fieldset>
</form>