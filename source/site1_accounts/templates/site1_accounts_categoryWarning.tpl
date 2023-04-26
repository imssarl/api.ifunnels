{if $boolShow}
{literal}
<script type="text/javascript">
window.addEvent('domready', function() {r.alert( 
	'Messages',
	{/literal}'Enhance your experience by selecting content categories for your:<br />{if $arrNum.wpress}<br />{$arrNum.wpress} blogs - <a href="{url name='site1_blogfusion' action='manage'}">select category now</a>{/if}{if $arrNum.ncsb}<br />{$arrNum.ncsb} NCSB sites - <a href="{url name='site1_ncsb' action='manage'}">select category now</a>{/if}{if $arrNum.psb}<br />{$arrNum.psb} PSB sites - <a href="{url name='site1_psb' action='manage'}">select category now</a>{/if}{if $arrNum.nvsb}<br />{$arrNum.nvsb} NVSB sites - <a href="{url name='site1_nvsb' action='manage'}">select category now</a>{/if}{if $arrNum.cnb}<br />{$arrNum.cnb} CNB sites - <a href="{url name='site1_cnb' action='manage'}">select category now</a>{/if}'{literal},
	'roar_warning',
	{duration:10000});});
</script>
{/literal}
{/if}