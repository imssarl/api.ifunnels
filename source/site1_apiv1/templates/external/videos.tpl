	<ol>
		<li>
			<label>Category: <em>*</em></label>
			<select class="required" name="arrCnt[{$i.flg_source}][settings][category]">
				<option value=''> - select - </option>
				{html_options options=$video selected={$arrCnt.{$i.flg_source}.settings.category}}
			</select>
		</li>
		<li>
			<label>Search by tags: <em>*</em></label>
			<input size="40" class="required" type="text" name="arrCnt[{$i.flg_source}][settings][tags]" value="{if !empty($arrCnt.{$i.flg_source}.settings.tags)}{$arrCnt.{$i.flg_source}.settings.tags}{/if}"/>
		</li>
	</ol>