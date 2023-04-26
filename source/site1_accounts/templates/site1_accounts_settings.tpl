<form method="post" action="" class="wh" id="create_ncsb" style="width:50%">
	<p>Please complete the form below. Mandatory fields marked <em>*</em></p>
	<fieldset>
		<legend>Administrative settings</legend>
		<ol>
			<li><label for="page_links"><span{if $arrErr.page_links} class="red"{/if}>Page links in pagination <em>*</em></span></label> 
				<input name="page_links" value="{$arrSettings.page_links|default:'5'}" size="15" maxlength="15" type="text" id="page_links" /></li>
			<li><label for="rows_per_page"><span{if $arrErr.rows_per_page} class="red"{/if}>Total rows per page <em>*</em></span></label> 
				<input name="rows_per_page" value="{$arrSettings.rows_per_page|default:'15'}" size="15" maxlength="15" type="text" id="rows_per_page" /></li>
		</ol>
	</fieldset>
	<p>Note: Values need to by more than zero.</p></li>
	<p><input value="Update" type="submit"></p>
</form>