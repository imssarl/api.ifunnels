{module name='site1_accounts' action='categoryWarning'}

{if Project_Users::haveAccess( array( 'Blog Fusion' ) )}

<center>
<table width="100%" border="0">
<tr>
	<td width="22%"></td>
	<td width="280" valign="top">
		<div style="border:1px solid #999; padding:10px; margin:6px;">
		<b>Check these How-To User Guides:</b><br/><br/>
		<a target="_blank" href="http://members.creativenichemanager.info/usersdata/cnm_help/blogfusion.pdf">Step 1: Create your blog</a><br/><br/>
		<a target="_blank"  href="http://members.creativenichemanager.info/usersdata/cnm_help/contentpublishing.pdf">Step 2: Post content on your blogs</a><br/><br/>
		<a target="_blank"  href="http://members.creativenichemanager.info/usersdata/cnm_help/contentsyndication.pdf">Step 3: Generate traffic</a><br/><br/>
		Tools:<br/><br/>
		<a target="_blank"  href="http://members.creativenichemanager.info/usersdata/cnm_help/sitemanagement.pdf">Manage your FTP details</a><br/><br/>
		<a target="_blank"  href="http://members.creativenichemanager.info/usersdata/cnm_help/contentwizard.pdf">Article Manager</a><br/><br/>
		<a target="_blank"  href="http://members.creativenichemanager.info/usersdata/cnm_help/videomanager.pdf">Video Manager</a><br/><br/>
		</div>
	</td>
	<td width="30"></td>
	<td>
<table>
	<tr>
		<td><b>Step 1:</b> <a class="a1" href="{url name='site1_blogfusion' action='create'}">Create your blog</a></td>
	</tr>
	<tr>
		<td>
			<a class="a1" href="{url name='site1_blogfusion' action='create'}">
				<img  width="50" height="50" src="/skin/i/frontends/design/icons_on/blog_fusion.png" alt="Blog Fusion"/>
			</a>
		</td>
	</tr>	
	<tr>
		<td><b>Step 2:</b> <a class="a1" href="{url name='site1_content' action='blog'}">Post content on your blogs</a></td>
	</tr>
	<tr>
		<td>
			<a class="a1" href="{url name='site1_content' action='blog'}">
				<img src="/skin/i/frontends/design/icons_on/manage_category.png" alt="Content Publishing" />
			</a>
		</td>
	</tr>	
	<tr>
		<td><b>Step 3:</b> <a class="a1" href="{url name='site1_syndication' action='manage'}">Generate traffic</a></td>
	</tr>
	<tr>
		<td>
			<a class="a1" href="{url name='site1_syndication' action='manage'}">
				<img src="/skin/i/frontends/design/icons_on/upload_articles.png" alt="Forums"  />
			</a>
		</td>
	</tr>	
	<tr>
		<td><b>Tools:</b></td>
	</tr>
	<tr>
		<td>
		<a class="a1" href="{url name='ftp_tools' action='manage'}">Manage your FTP details</a><br/>
		<a class="a1" target="_blank" href="{Core_Module_Router::$offset}wpgen/index.php">Create WP Themes</a><br/>
		<a class="a1" href="{url name='site1_articles' action='articles'}">Article Manager</a><br/>
		<a class="a1" href="{url name='site1_video_manager' action='video'}">Video Manager</a><br/>
		<a class="a1" href="{url name='site1_quick_indexer' action='main'}">Quick Indexer</a><br/>
		<a class="a1" href="{url name='site1_blogfusion' action='manage'}">Manage Your Blogs</a>
		</td>
	</tr>
</table>
</td>
</tr>
</table>
</center>

{else}

<div id="wrap">
{if Project_Users::haveAccess( array( 'Advertiser', 'Campaign Optimizer' ) )}
<center>
	<table>
		<tr>
			<td style="text-align:center;">
{if Project_Users::haveAccess( array( 'Site Profit Bot Pro', 'Advertiser' ) )}
		<a class="a1" href="{Core_Module_Router::$offset}dams/index.php">
			<img src="/skin/i/frontends/design/icons_on/dams.png" alt="High Impact Ad Manager"  /><br />High Impact Ad Manager
		</a>
{/if}
			</td>
			<td style="text-align:center;">
		<a class="a1" href="{Core_Module_Router::$offset}snippets.php">
			<img src="/skin/i/frontends/design/icons_on/content_ad.png" alt="Campaign Optimizer" /><br />Campaign Optimizer
		</a>
			</td>
		</tr>
		<tr>
			<td style="text-align:left;" colspan="2"><br />
{if Project_Users::haveAccess( array( 'Campaign Optimizer' ) )}
Upgrade to Pro and get access to the High Impact Ad Manager (click <a href="http://siteprofitbot.com/advancedtopro" target="_blank">here</a>)<br />
{/if}
Upgrade and get unlimited access to all modules of the Creative Niche Manager (click <a href="http://siteprofitbot.com/protounlimited" target="_blank">here</a>)<br />
Here are all the modules you can access when you're a full Creative Niche Manager member:<br /> 
			</td>
		</tr>
	</table>
</center>
{/if}
{if Project_Users::haveAccess( array('Site Profit Bot Pro', 'Site Profit Bot Hosted' ) )}
<center>
Upgrade and get unlimited accesss to all modules of the Creative Niche Manager (click <a href="http://siteprofitbot.com/protounlimited/" target="_blank">here</a>)
<br/>
Before using the Site Profit Bot modules, make sure to read the user's guide: <a href="{if Project_Users::haveAccess( array('Site Profit Bot Hosted' ) )}/usersdata/cnm_help/spbhosted.pdf{elseif Project_Users::haveAccess( array('Site Profit Bot Pro') )}/usersdata/cnm_help/spbpro.pdf{/if}">Download It Here</a>
<br/><br/>
</center>
{/if}

{if Project_Users::haveAccess( array('NVSB Hosted Pro', 'NVSB Hosted' ) )}
<center>
Upgrade and get unlimited accesss to all modules of the Creative Niche Manager (click <a href="http://sales.ethiccash.com/2/48239bu/order" target="_blank">here</a>)
<br/>
Before using the Niche Video Site Builder, make sure to read the user's guide: <a href="{if Project_Users::haveAccess( array('NVSB Hosted' ) )}/usersdata/cnm_help/nvsbhosted.pdf{elseif Project_Users::haveAccess( array('NVSB Hosted Pro') )}/usersdata/cnm_help/nvsbpro.pdf{/if}">Download It Here</a>
<br/><br/>
</center>
{/if}

<div id="wrap_left">
	<div class="left">
		<h2>Site Management</h2>
		<div class="inside">
			<ul>
				<li>
				{if Project_Users::haveAccess( array( 'Unlimited' ) )}
					<a class="a1" href="{url name='site1_cnb' action='manage'}">
						<img src="/skin/i/frontends/design/icons_on/manage_sites.png" alt="Manage Existing Sites" /><br />Manage existing site
					</a>
				{else}
						<img src="/skin/i/frontends/design/icons_off/manage_sites.png" alt="Manage Existing Sites" /><br />Manage existing site
				{/if}
				</li>
				<li>
				{if Project_Users::haveAccess( array( 'Unlimited' ) )}
					<a class="a1" href="{url name='site1_accounts' action='history'}{*Core_Module_Router::$offset}cnbhistory.php*}">
						<img src="/skin/i/frontends/design/icons_on/history.png" alt="History" /><br />History
					</a>
				{else}
						<img src="/skin/i/frontends/design/icons_off/history.png" alt="History" /><br />History
				{/if}
				</li>
				<li>
				{if Project_Users::haveAccess( array( 'Unlimited' ) )}
					<a class="a1" href="{url name='site1_accounts' action='register'}">
						<img src="/skin/i/frontends/design/icons_on/new_site.png" alt="Register Site" /><br />Register Site
					</a>
				{else}
						<img src="/skin/i/frontends/design/icons_off/new_site.png" alt="Register Site" /><br />Register Site
				{/if}
				</li>
				<li>
				{if Project_Users::haveAccess( array( 'Unlimited', 'Site Profit Bot Pro', 'Site Profit Bot Hosted', 'NVSB Hosted', 'NVSB Hosted Pro' ) )}
					<a class="a1" href="{url name='ftp_tools' action='manage'}">
						<img src="/skin/i/frontends/design/icons_on/ftp.png" alt="Manage FTP details" /><br />Manage FTP details
					</a>
				{else}
						<img src="/skin/i/frontends/design/icons_off/ftp.png" alt="Manage FTP details" /><br />Manage FTP details
				{/if}
				</li>
				<li>
				{if Project_Users::haveAccess( array( 'Unlimited', 'Site Profit Bot Pro', 'Site Profit Bot Hosted' ) )}
					<a class="a1" href="{url name='site1_profile' action='manage'}">
						<img src="/skin/i/frontends/design/icons_on/profile_manager.png" alt="Profile Manager" /><br />Profile Manager
					</a>
				{else}
						<img src="/skin/i/frontends/design/icons_off/profile_manager.png" alt="Profile Manager" /><br />Profile Manager
				{/if}
				</li>
			</ul>
		</div>
	</div>

	<div class="left">
		<h2>Template Manager</h2>
		<div class="inside">
			<ul>
				<li>
				{if Project_Users::haveAccess( array( 'Unlimited' ) )}
					<a class="a1" target="_blank" href="{Core_Module_Router::$offset}wpgen/index.php">
						<img src="/skin/i/frontends/design/icons_on/create_wp.png" alt="Create WP Theme"  /><br />Create WP Theme
					</a>
				{else}
						<img src="/skin/i/frontends/design/icons_off/create_wp.png" alt="Create WP Theme"  /><br />Create WP Theme
				{/if}
				</li>
				<li>
				{if Project_Users::haveAccess( array( 'Unlimited' ) )}
					<a class="a1" href="{url name='site1_accounts' action='templates'}">
						<img src="/skin/i/frontends/design/icons_on/manage_template.png" alt="Manage Template" /><br />Manage Template
					</a>
				{else}
						<img src="/skin/i/frontends/design/icons_off/manage_template.png" alt="Manage Template" /><br />Manage Template
				{/if}
				</li>
				<li>
				{if Project_Users::haveAccess( array( 'Unlimited' ) )}
					<a class="a1" href="{url name='site1_blogfusion' action='themes'}">
						<img src="/skin/i/frontends/design/icons_on/manage_wp.png" alt="Manage WP Theme"  /><br />Manage WP Theme
					</a>
				{else}
						<img src="/skin/i/frontends/design/icons_off/manage_wp.png" alt="Manage WP Theme"  /><br />Manage WP Theme
				{/if}
				</li>
			</ul>
		</div>
	</div>

	<div class="left">
		<h2>Market Research and Competitive intelligence</h2>
		<div class="inside">
			<ul>
			{*<li>
				{if Project_Users::haveAccess( array( 'Unlimited' ) )}
					<a class="a1" href="{Core_Module_Router::$offset}url_analyzer/url.php">
						<img src="/skin/i/frontends/design/icons_on/url_ana.png" alt="URL Analyzer" /><br />URL <br /> Analyzer
					</a>
				{else}
						<img src="/skin/i/frontends/design/icons_off/url_ana.png" alt="URL Analyzer" /><br />URL <br /> Analyzer
				{/if}
				</li>*}
				<li>
				{if Project_Users::haveAccess( array( 'Unlimited' ) )}
					<a class="a1" href="{Core_Module_Router::$offset}nicheresearch.php">
						<img src="/skin/i/frontends/design/icons_on/niche_research.png" alt="Niche Research"  /><br />Niche <br /> Research
					</a>
				{else}
						<img src="/skin/i/frontends/design/icons_off/niche_research.png" alt="Niche Research"  /><br />Niche <br /> Research
				{/if}
				</li>
				<li>
				{if Project_Users::haveAccess( array( 'Unlimited' ) )}
					<a class="a1" href="{Core_Module_Router::$offset}kwdresearch/index.php">
						<img src="/skin/i/frontends/design/icons_on/keyword_research.png" alt="keyword Research"  /><br />Keyword <br /> Research
					</a>
				{else}
						<img src="/skin/i/frontends/design/icons_off/keyword_research.png" alt="keyword Research"  /><br />Keyword <br /> Research
				{/if}
				</li>
				<li>
				{if Project_Users::haveAccess( array( 'Unlimited' ) )}
					<a class="a1" href="{Core_Module_Router::$offset}keywordgenerator/">
						<img src="/skin/i/frontends/design/icons_on/keyword_generation.png" alt="Keyword Generation"  /><br />Keyword <br /> Generation
					</a>
				{else}
						<img src="/skin/i/frontends/design/icons_off/keyword_generation.png" alt="Keyword Generation"  /><br />Keyword <br /> Generation
				{/if}
				</li>
				<li>
				{if Project_Users::haveAccess( array( 'Unlimited' ) )}
					<a class="a1" href="{Core_Module_Router::$offset}market-trends/">
						<img src="/skin/i/frontends/design/icons_on/site1_market_trands.png" alt="Market Trends"  /><br />Market <br /> Trends
					</a>
				{else}
						<img src="/skin/i/frontends/design/icons_off/site1_market_trands.png" alt="Market Trends"  /><br />Market <br /> Trends
				{/if}
				</li>
			</ul>
		</div>
	</div>

	<div class="left">
		<h2>Domain Registration & Hosting</h2>
		<div class="inside" style="text-align:center; padding-top:8px;">
			{if Project_Users::haveAccess( array( 'Unlimited' ) )}
				<a class="a1" href="http://www.namecheap.com?aff=1158">
					<img src="/skin/i/frontends/design/icons_on/browser.png" alt="Sellineo.com" /><br />Namecheap.com - Cheap domain name registration, renewal and transfers - Free SSL Certificates - Web Hosting
				</a>
			{else}
					<img src="/skin/i/frontends/design/icons_off/browser.png" alt="Sellineo.com" /><br />Namecheap.com - Cheap domain name registration, renewal and transfers - Free SSL Certificates - Web Hosting
			{/if}
		</div>
	</div>

	<div class="left">
		<h2>Project Manager</h2>
		<div class="inside">
			<ul>
				<li>
				{if Project_Users::haveAccess( array( 'Unlimited' ) )}
					<a class="a1" href="{url name='site1_content' action='cnb'}">
						<img src="/skin/i/frontends/design/icons_on/existing_projects.png" alt="CNB Content Projects" /><br />CNB Content Projects
					</a>
				{else}
						<img src="/skin/i/frontends/design/icons_off/existing_projects.png" alt="CNB Content Projects" /><br />CNB Content Projects
				{/if}
				</li>
				<li style="width:120px;">
				{if Project_Users::haveAccess( array( 'Unlimited' ) )}
					<a class="a1" href="{url name='site1_content' action='blog'}">
						<img src="/skin/i/frontends/design/icons_on/create_keyword.png" alt="Blog Fusion Content Projects" /><br />Blog Fusion Content Projects
					</a>
				{else}
						<img src="/skin/i/frontends/design/icons_off/create_keyword.png" alt="Blog Fusion Content Projects" /><br />Blog Fusion Content Projects
				{/if}
				</li>
				<li>
				{if Project_Users::haveAccess( array( 'Unlimited' ) )}
					<a class="a1" href="{url name='site1_content' action='ncsb'}">
						<img src="/skin/i/frontends/design/icons_on/article_project.png" alt="NCSB Content Projects" /><br />NCSB Content Projects
					</a>
				{else}
						<img src="/skin/i/frontends/design/icons_off/article_project.png" alt="NCSB Content Projects" /><br />NCSB Content Projects
				{/if}
				</li>
				<li>
				{if Project_Users::haveAccess( array( 'Unlimited' ) )}
					<a class="a1" href="{url name='site1_organizer' action='manage'}">
						<img src="/skin/i/frontends/design/icons_on/organizer.png" alt="Organizer" /><br />Organizer
					</a>
				{else}
						<img src="/skin/i/frontends/design/icons_off/organizer.png" alt="Organizer" /><br />Organizer
				{/if}
				</li>
			</ul>
		</div>
	</div>
</div>

<div id="wrap_right">
	<div class="right">
		<h2>Site Builder</h2>
		<div class="inside">
			<ul>
				<li>
				{if Project_Users::haveAccess( array( 'Unlimited' ) )}
					<a class="a1" href="{url name='site1_cnb' action='create'}{*Core_Module_Router::$offset}cnbsites.php?process=create_cnmsite*}">
						<img src="/skin/i/frontends/design/icons_on/creative_builder.png" alt="Creative Niche Builder"  /><br />Creative Niche Builder
					</a>
				{else}
						<img src="/skin/i/frontends/design/icons_off/creative_builder.png" alt="Creative Niche Builder"  /><br />Creative Niche Builder
				{/if}
				</li>
				<li>
				{if Project_Users::haveAccess( array( 'Unlimited', 'NVSB Hosted', 'NVSB Hosted Pro' ) )}
					<a class="a1" href="{url name='site1_nvsb' action='create'}">
						<img src="/skin/i/frontends/design/icons_on/video_builder.png" alt="Niche Video Site Builder"  /><br />Niche Video Site Builder
					</a>
				{else}
						<img src="/skin/i/frontends/design/icons_off/video_builder.png" alt="Niche Video Site Builder"  /><br />Niche Video Site Builder
				{/if}
				</li>
				<li>
				{if Project_Users::haveAccess( array( 'Unlimited' ) )}
					<a class="a1" href="{url name='site1_ncsb' action='create'}">
						<img src="/skin/i/frontends/design/icons_on/content_builder.png" alt="Niche Content Site Builder"  /><br />Niche Content Site Builder
					</a>
				{else}
						<img src="/skin/i/frontends/design/icons_off/content_builder.png" alt="Niche Content Site Builder"  /><br />Niche Content Site Builder
				{/if}
				</li>
				<li>
				{if Project_Users::haveAccess( array( 'Unlimited' ) )}
					<a class="a1" href="{url name='site1_blogfusion' action='create'}">
						<img  width="50" height="50" src="/skin/i/frontends/design/icons_on/blog_fusion.png" alt="Blog Fusion"/><br />Blog Fusion
					</a>
				{else}
						<img  width="50" height="50" src="/skin/i/frontends/design/icons_off/blog_fusion.png" alt="Blog Fusion"/><br />Blog Fusion
				{/if}
				</li>
				<li>
				{if Project_Users::haveAccess( array( 'Unlimited', 'Site Profit Bot Pro', 'Site Profit Bot Hosted' ) )}
					<a class="a1" href="{url name='site1_psb' action='create'}">
						<img  width="50" height="50" src="/skin/i/frontends/design/icons_on/psb_icon.png" alt="Site Profit Bot"/><br />Site Profit Bot</a>&nbsp;
					
				{else}
						<img  width="50" height="50" src="/skin/i/frontends/design/icons_off/psb_icon.png" alt="Site Profit Bot"/><br />Site Profit Bot&nbsp;
				{/if}
				</li>
			</ul>
		</div>
	</div>

	<div class="right">
		<h2>Content Wizard</h2>
		<div class="inside">
			<ul>
				<li>
				{if Project_Users::haveAccess( array( 'Unlimited' ) )}
					<a class="a1" href="{url name='site1_articles' action='articles'}">
						<img src="/skin/i/frontends/design/icons_on/manage_article.png" alt="Manage Article" /><br />Article Manager
					</a>
				{else}
						<img src="/skin/i/frontends/design/icons_off/manage_article.png" alt="Manage Article" /><br />Article Manager
				{/if}
				</li>
				<li>
				{if Project_Users::haveAccess( array( 'Unlimited' ) )}
					<a class="a1" href="{url name='site1_content' action='blog'}">
						<img src="/skin/i/frontends/design/icons_on/manage_category.png" alt="Content Publishing" /><br />Content <br /> Publishing
					</a>
				{else}
						<img src="/skin/i/frontends/design/icons_off/manage_category.png" alt="Content Publishing" /><br />Content <br /> Publishing
				{/if}
				</li>
				<li>
				{if Project_Users::haveAccess( array( 'Unlimited' ) )}
					<a class="a1" href="{url name='site1_syndication' action='manage'}">
						<img src="/skin/i/frontends/design/icons_on/upload_articles.png" alt="Forums"  /><br />Content <br /> Syndication
					</a>
				{else}
						<img src="/skin/i/frontends/design/icons_off/upload_articles.png" alt="Forums"  /><br />Content <br /> Syndication
				{/if}
				</li>
				<li>
				{if Project_Users::haveAccess( array( 'Unlimited' ) )}
					<a class="a1" href="{url name='site1_articles' action='rewriter'}">
						<img src="/skin/i/frontends/design/icons_on/article_rewriter.png" alt="Article Rewriter" width="50" height="50"/><br/>Article Rewriter
					</a>
				{else}
						<img src="/skin/i/frontends/design/icons_off/article_rewriter.png" alt="Article Rewriter" width="50" height="50"/><br/>Article Rewriter
				{/if}
				</li>
				<li>
				{if Project_Users::haveAccess( array( 'Unlimited' ) )}
					<a class="a1" href="{url name='site1_video_manager' action='video'}">
						<img src="/skin/i/frontends/design/icons_on/site1_video_manager.png" alt="Video Manager"  /><br />Video Manager
					</a>
				{else}
						<img src="/skin/i/frontends/design/icons_off/site1_video_manager.png" alt="Video Manager"  /><br />Video Manager
				{/if}
				</li>
			</ul>
		</div>	

	</div>

	<div class="right">
		<h2>Advertising, Tracking and Monitoring</h2>
		<div class="inside">
			<ul>
				<li>
				{if Project_Users::haveAccess( array( 'Unlimited' ) )}
					<a class="a1" href="{Core_Module_Router::$offset}ccp/campaign.php">
						<img src="/skin/i/frontends/design/icons_on/covert_conversion.png" alt="Covert Conversion Pro"  /><br />Covert Con- <br/> version Pro
					</a>
				{else}
						<img src="/skin/i/frontends/design/icons_off/covert_conversion.png" alt="Covert Conversion Pro"  /><br />Covert Con- <br/> version Pro
				{/if}
				</li>
				<li>
				{if Project_Users::haveAccess( array( 'Unlimited', 'Site Profit Bot Pro', 'NVSB Hosted Pro' ) )}
					<a class="a1" href="{Core_Module_Router::$offset}dams/index.php">
						<img src="/skin/i/frontends/design/icons_on/dams.png" alt="High Impact Ad Manager"  /><br />High Impact Ad Manager
					</a>
				{else}
						<img src="/skin/i/frontends/design/icons_off/dams.png" alt="High Impact Ad Manager"  /><br />High Impact Ad Manager
				{/if}
				</li>
				<li>
				{if Project_Users::haveAccess( array( 'Unlimited', 'Site Profit Bot Pro', 'Campaign Optimizer', 'NVSB Hosted Pro' ) )}
					<a class="a1" href="{Core_Module_Router::$offset}snippets.php">
						<img src="/skin/i/frontends/design/icons_on/campaign_optimizer.png" alt="Campaign Optimizer" /><br />Campaign Optimizer
					</a>
				{else}
						<img src="/skin/i/frontends/design/icons_off/campaign_optimizer.png" alt="Campaign Optimizer" /><br />Campaign Optimizer
				{/if}
				</li>
				<li>
				{if Project_Users::haveAccess( array( 'Unlimited' ) )}
					<a class="a1" href="{url name='site1_accounts' action='copyprophet'}">
						<img src="/skin/i/frontends/design/icons_on/copyprophet.png" alt="Copy Prophet"  /><br />Copy Prophet
					</a>
				{else}
						<img src="/skin/i/frontends/design/icons_off/copyprophet.png" alt="Copy Prophet"  /><br />Copy Prophet
				{/if}
				</li>
				<li>
				{if Project_Users::haveAccess( array( 'Unlimited' ) )}
					<a class="a1" href="{Core_Module_Router::$offset}affiliate-module/create/">
						<img src="/skin/i/frontends/design/icons_on/site1_affiliate.png" alt="Affiliate Profit Booster"  /><br />Affiliate Profit Booster
					</a>
				{else}
						<img src="/skin/i/frontends/design/icons_off/site1_affiliate.png" alt="Affiliate Profit Booster"  /><br />Affiliate Profit Booster
				{/if}
				</li>
			</ul>
		</div>
	</div>

	<div class="right">
		<h2>Traffic Generation</h2>
		<div class="inside">
			<ul>
				<li>
				{if Project_Users::haveAccess( array( 'Unlimited' ) )}
					<a class="a1" href="{Core_Module_Router::$offset}traffic.php">
						<img src="/skin/i/frontends/design/icons_on/traffic_locator.png" alt="Traffic Locator" /><br />Traffic <br />Locator
					</a>
				{else}
						<img src="/skin/i/frontends/design/icons_off/traffic_locator.png" alt="Traffic Locator" /><br />Traffic <br />Locator
				{/if}
				</li>
				<li>
				{if Project_Users::haveAccess( array( 'Unlimited' ) )}
					<a href="{Core_Module_Router::$offset}mytool/show_ethiclinks.php" rel="popup[1000,490,r]">
						<img src="/skin/i/frontends/design/icons_on/link_building.png" alt="Link Building" border="0" /><br />Link <br />Building
					</a>
				{else}
						<img src="/skin/i/frontends/design/icons_off/link_building.png" alt="Link Building" border="0" /><br />Link <br />Building
				{/if}
				</li>
				<li>
				{if Project_Users::haveAccess( array( 'Unlimited' ) )}
					<a class="a1" href="{url name='site1_sbookmarking' action='gadget'}">
						<img src="/skin/i/frontends/design/icons_on/bookmarking.png" alt="Social Bookmarking"  /><br />Social Bookmarking
					</a>
				{else}
						<img src="/skin/i/frontends/design/icons_off/bookmarking.png" alt="Social Bookmarking"  /><br />Social Bookmarking
				{/if}
				</li>
				<li>
				{if Project_Users::haveAccess( array( 'Unlimited' ) )}
					<a class="a1" href="{Core_Module_Router::$offset}as/index.php">
						<img src="/skin/i/frontends/design/icons_on/article_module.png" alt="Article Submission Module"  /><br /> Article <br /> Submission
					</a>
				{else}
						<img src="/skin/i/frontends/design/icons_off/article_module.png" alt="Article Submission Module"  /><br /> Article <br /> Submission
				{/if}
				</li>
				<li>
				{if Project_Users::haveAccess( array( 'Unlimited', 'Site Profit Bot Pro', 'Site Profit Bot Hosted', 'NVSB Hosted Pro' ) )}
					<a class="a1" href="{url name='site1_quick_indexer' action='main'}">
						<img src="/skin/i/frontends/design/icons_on/statistics.png" alt="Quick Indexer"  /><br />Quick Indexer <br /> &nbsp;
					</a>
				{else}
						<img src="/skin/i/frontends/design/icons_off/statistics.png" alt="Quick Indexer"  /><br />Quick Indexer <br /> &nbsp;
				{/if}
				</li>
			</ul>
		</div>
	</div>

	<div class="right">
		<h2>Web tools</h2>
		<div class="inside">
			<ul>
				<li style="width:120px;">
				{if Project_Users::haveAccess( array( 'Unlimited' ) )}
					<a class="a1 mb" href="{url name='cpanel_tools' action='database'}" title="Cpanel Database Creator" rel="width:600,height:500">
						<img src="/skin/i/frontends/design/icons_on/cpanel_db.png" alt="Cpanel Database Creator" /><br />Cpanel Database Creator
					</a>
				{else}
						<img src="/skin/i/frontends/design/icons_off/cpanel_db.png" alt="Cpanel Database Creator" /><br />Cpanel Database Creator
				{/if}
				</li>
				<li style="width:117px;">
				{if Project_Users::haveAccess( array( 'Unlimited' ) )}
					<a class="a1 mb" href="{url name='cpanel_tools' action='subdomain'}" title="Cpanel Mass Subdomain Creator" rel="width:600,height:500">
						<img src="/skin/i/frontends/design/icons_on/cpanel_subdomain.png" alt="Cpanel Mass Subdomain Creator" /><br /> Cpanel Mass Subdomain Creator
					</a>
				{else}
						<img src="/skin/i/frontends/design/icons_off/cpanel_subdomain.png" alt="Cpanel Mass Subdomain Creator" /><br /> Cpanel Mass Subdomain Creator
				{/if}
				</li>
				<li style="width:100px;">
				{if Project_Users::haveAccess( array( 'Unlimited' ) )}
					<a class="a1 mb" href="{url name='cpanel_tools' action='addondomain'}" title="Cpanel Addon Domains Creator" rel="width:600,height:500">
						<img src="/skin/i/frontends/design/icons_on/cpanel_addondomains.png" alt="Cpanel Addon Domains Creator" /><br />Cpanel Addon Domains Creator
					</a>
				{else}
						<img src="/skin/i/frontends/design/icons_off/cpanel_addondomains.png" alt="Cpanel Addon Domains Creator" /><br />Cpanel Addon Domains Creator
				{/if}
				</li>
				<li style="width:110px;">
				{if Project_Users::haveAccess( array( 'Unlimited' ) )}
					<a class="a1" href="{Core_Module_Router::$offset}edit-file/" >
						<img src="/skin/i/frontends/design/icons_on/site1_file_editor.png" alt="Remote File Editor" /><br />Remote File Editor
					</a>
				{else}
						<img src="/skin/i/frontends/design/icons_off/site1_file_editor.png" alt="Remote File Editor" /><br />Remote File Editor
				{/if}
				</li>
			</ul>
		</div>
	</div>
</div>
</div>
{if Project_Users::haveAccess( array( 'Unlimited' ) )}
<link href="/skin/_js/multibox/multibox.css" rel="stylesheet" type="text/css" />
<!--[if lte IE 6]><link rel="stylesheet" href="/skin/_js/multibox/multiboxie6.css" type="text/css" media="all" /><![endif]-->
<script language="javascript" src="/skin/_js/multibox/overlay.js"></script>
<script language="javascript" src="/skin/_js/multibox/multibox.js"></script>
<script type="text/javascript">
{literal}
var multibox={};
window.addEvent('domready', function() {
	multibox = new multiBox({
		mbClass: '.mb',
		container: $(document.body),
		useOverlay: true,
		nobuttons: true,
	});
});
{/literal}
</script>
{/if}
{/if}