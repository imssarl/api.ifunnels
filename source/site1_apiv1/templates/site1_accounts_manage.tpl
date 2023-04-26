 | Manage <select id="sitetype" style="width:100px;">
	<option value="{url name='site1_cnb' action='manage'}"{if $arrPrm.strCurrent=='site1_cnb'} selected=""{/if}>CNB Sites</option>
	<option value="{url name='site1_nvsb' action='manage'}"{if $arrPrm.strCurrent=='site1_nvsb'} selected=""{/if}>NVSB Sites</option>
	<option value="{url name='site1_ncsb' action='manage'}"{if $arrPrm.strCurrent=='site1_ncsb'} selected=""{/if}>NCSB Sites</option>
	<option value="{url name='site1_blogfusion' action='manage'}"{if $arrPrm.strCurrent=='site1_blogfusion'} selected=""{/if}>Blogs</option>
	<option value="{url name='site1_psb' action='manage'}"{if $arrPrm.strCurrent=='site1_psb'} selected=""{/if}>PSB Sites</option>
</select>
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