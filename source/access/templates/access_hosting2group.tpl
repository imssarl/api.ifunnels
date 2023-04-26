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
	<fieldset>
		<legend>Hosting</legend>
		<ol>
			<li>
				<input type="hidden" value="0" name="data[{Project_Acs_Hosting::REMOTE_ID}]" />
				<label style="cursor: pointer;" for="id-{Project_Acs_Hosting::REMOTE_ID}">Domains host externally&nbsp;</label><input type="checkbox" id="id-{Project_Acs_Hosting::REMOTE_ID}" value="{Project_Acs_Hosting::REMOTE_ID}" {if in_array(Project_Acs_Hosting::REMOTE_ID,$hostingIds)} checked="1" {/if} name="data[{Project_Acs_Hosting::REMOTE_ID}]" />
			</li>
			<li>
				<input type="hidden" value="0" name="data[{Project_Acs_Hosting::LOCAL_ID}]" />
				<label style="cursor: pointer;" for="id-{Project_Acs_Hosting::LOCAL_ID}">Domains hosted with us&nbsp;</label><input type="checkbox" id="id-{Project_Acs_Hosting::LOCAL_ID}" value="{Project_Acs_Hosting::LOCAL_ID}" {if in_array(Project_Acs_Hosting::LOCAL_ID,$hostingIds)} checked="1" {/if} name="data[{Project_Acs_Hosting::LOCAL_ID}]" />
			</li>
		</ol>
	</fieldset>
</div>
<div style="width:90%;text-align:center;clear:both;padding-top: 20px;">
	<a href="#" onclick="r_set.submit();return false;">{if $smarty.request.group_id}update{else}attach{/if} rights</a></div>
</form>
</div>