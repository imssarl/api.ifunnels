{if $arrPrm.action}{*this module own action*}
{*тут отступы лучше не делать а то попадут в буфер*}{include file="site1_`$arrPrm.action`.tpl"}
{elseif $arrNest.name&&$arrNest.flg_tpl}{*pop-up actions*}
{*тут отступы лучше не делать а то попадут в буфер*}{module name=$arrNest.name action=$arrNest.action}
{else}
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	{module name='site1' action='head'}
	<link rel="stylesheet" type="text/css" href="/skin/_css/site1.css" />
	<link rel="stylesheet" type="text/css" href="/skin/_css/style1.css" />
	<link rel="stylesheet" type="text/css" href="/skin/_css/tips.css" />
	<script type="text/javascript" src="/skin/_js/mootools.js"></script>
	<script type="text/javascript" src="/skin/_js/xlib.js"></script>
	{*r.alert*}
	<link rel="stylesheet" type="text/css" href="/skin/_js/roar/roar.css" />
	<script type="text/javascript" src="/skin/_js/roar/roar.js"></script>
	{*additional js*}
	<script type="text/javascript" src="/skin/_js/site1.js"></script>
	<script type="text/javascript">
	{literal}
		img_preload(['/skin/_js/roar/roar.png']);
		var r,tips;
		window.addEvent('domready',function(){
			tips=new Tips('.tooltip');
			r=new Roar();
		});
	{/literal}
	</script>
</head>
<body>
	Hi, this is the tracking server!
	{*
	<div id="header-bg">
        <div id="header">
        <div class="col-full">
            <div id="logo" class="fl">
                <a href="/" title="Niche Marketing Platform"><img class="title" src="/skin/i/frontends/design/logo3.png" alt="Creative Niche Manager" /></a>                
            </div>
            {if $arrUser.id}
            <div id="info-block">
			<div style="width:270px; float:left; padding:0 0 0 0;">
			You're logged in as {$arrUser.nickname} - {foreach from=$arrCurrentGroups item='v' name='g'}{$v.title}{if !$smarty.foreach.g.last} / {/if}{/foreach} group{if count($arrCurrentGroups)>1}'s{/if}
			</div>
            </div>
            {/if}
            <div style="clear:both;"></div>
        </div>
        </div>
		<div id="navigation">
			{include file='inc_menu.tpl'}
		</div>        
    </div>

		{if $temporaryUnavailable}
			<div style="margin:80px 40px 70px 200px;">
				The Creative Niche Manager system is under maintenance right now. It will be back shortly. We apologize for any inconvenience.
			</div>
		{else}
			{if $arrNest.name}
			<div style="margin:0 40px 0 80px;">
				{module name='site1' action='breadcrumb'}
				{module name=$arrNest.name action=$arrNest.action}
			</div>
			{else}
				{if $arrUser.id}
					{module name='site1_accounts' action='main'}
				{else}
					{module name='site1_accounts' action='login'}
				{/if}
			{/if}
		{/if}

<div style="clear:both;"></div>	
<div id="footer">
    <div class="col-full">
        <!-- Footer Widget Area Starts -->
        <div id="footer-widgets">
            <div class="block"></div>
            <div class="block"></div>
            <div class="block"></div>
            <div class="block last"></div>
	        <div class="fix"></div>       
        </div>
        <!-- Footer Widget Area Ends -->
        <div id="footer-credits">
		<div id="copyright" class="col-left">
			<p>&copy; 2010 JP Schoeffel. All Rights Reserved.</p>
		</div>
		{if $config->database->master->host!='localhost'}
		<div class="col-left" id="mcafeesecure">
			<center>
				<a target="_blank" href="https://www.mcafeesecure.com/RatingVerify?ref=members.creativenichemanager.info">
					<img width="94" height="54" border="0" src="//images.scanalert.com/meter/members.creativenichemanager.info/13.gif" alt="McAfee Secure sites help keep you safe from identity theft, credit card fraud, spyware, spam, viruses and online scams" oncontextmenu="alert('Copying Prohibited by Law - McAfee Secure is a Trademark of McAfee, Inc.'); return false;">
				</a>
			</center>
		</div>
		{/if}
		<div id="credit" class="col-right">
			<p>Operated and Maintained by <a href="http://web2innovation.com/" title="Web2 Innovation: Custom Software Development">Web2 Innovation</a></p>
		</div>
		</div><!-- /#footer-widgets -->
	</div><!-- /.col-full -->
</div><!-- /#footer -->	
*}
</body>
</html>
{/if}