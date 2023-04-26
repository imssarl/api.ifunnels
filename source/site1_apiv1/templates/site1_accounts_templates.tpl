<form method="post" action="" class="wh" id="create_ncsb" style="width:50%">
	<fieldset>
		<legend>Site type selector</legend>
		<ol>
			<li><label>Manage Template:</label>
				<select id="links">
				<option value="">Please Select One Option
				<option value="{url name='site1_psb' action='fronttemplates'}">Site Profit Bot
				<option value="{url name='site1_ncsb' action='templates'}">Niche Content Site Builder
				<option value="{url name='site1_nvsb' action='templates'}">Niche Video Site Builder
				<option value="{url name='site1_cnb' action='fronttemplates'}">Creative Niche Builder
				</select>
			</li>
		</ol>
	</fieldset>
</form>
{literal}
<script>
	window.addEvent('domready',function(){
		$('links').addEvent('change', function(){
			if( $chk($('links').value) ){
				location.href=$('links').value;
			}
		});
	});
</script>
{/literal}