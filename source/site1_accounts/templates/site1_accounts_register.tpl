<form method="post" action="" class="wh" id="create_ncsb" style="width:50%">
	<fieldset>
		<legend>Site type selector</legend>
		<ol>
			<li><label>Register site</label><select id="sitetype">
				<option value=""> - select - </option>
				<option value="{url name='site1_psb' action='import'}">PSB Sites</option>
				<option value="{url name='site1_ncsb' action='import'}">NCSB Sites</option>
				<option value="{url name='site1_nvsb' action='import'}">NVSB Sites</option>
				<option value="{url name='site1_cnb' action='import'}">CNB Sites</option>
				<option value="{url name='site1_cnb' action='portal'}">CNB Portal Sites</option>
				<option value="{url name='site1_blogfusion' action='import'}">Blogs</option>
			</select></li>
		</ol>
	</fieldset>
</form>
{literal}
<script type="text/javascript">
window.addEvent('domready', function(){
	$('sitetype').addEvent('change',function(e){
		if ($chk(this.value)) {
			this.value.toURI().go();
		}
	});
});
</script>
{/literal}