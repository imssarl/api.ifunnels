	<ol>
		<li>
			<label>PLR Bank ID: <em>*</em></label>
			<input size="40" class="required" type="text" name="arrCnt[{$i.flg_source}][settings][id]" value="{if $arrCnt.{$i.flg_source}.settings.id!=''}{$arrCnt.{$i.flg_source}.settings.id}{else}myid00001{/if}"/>
			<a style="text-decoration:none" class="Tips" title="This option is not required but you will only earn affiliate commission if you enter your Clickbank affiliate ID."><b> ?</b></a>
		</li>
	</ol>