{if $arrUser.id}
<ul>
	<li{if !$arrNest.action} class="active"{/if}><a href="{Core_Module_Router::$offset}" title="Home">Home</a></li>
	<li{if $arrNest.action=='settings'} class="active"{/if}><a href="{url name='site1_accounts' action='settings'}" title="Display Options">Display Options</a></li>
	<li{if $arrNest.action=='tutorials'} class="active"{/if}><a href="{url name='site1_accounts' action='tutorials'}" title="Tutorials and How-To Videos">Tutorials and How-To Videos</a></li>
	<li><a href="http://creativenichemanager.zendesk.com/" title="Support" target="_blank">Support</a></li>
	<li><a href="{if Project_Users::haveAccess( array( 'Site Profit Bot Pro', 'Site Profit Bot Hosted' ) )}http://siteprofitbot.com/blog{else}http://creativenichemanager.feedbackhq.com{/if}" title="Forums" target="_blank">Forums, Suggestions & Feedbacks</a></li>
	<li><a href="{url name='site1_accounts' action='logoff'}" title="Logout">Logout</a></li>
</ul>
{/if}