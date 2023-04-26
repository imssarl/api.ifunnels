{if $arrPrm.action}{*this module own action*}
{*тут отступы лучше не делать а то попадут в буфер*}{include file="b_`$arrPrm.action`.tpl"}
{elseif $arrNest.name=='site1_ecom_funnels'&&$arrNest.action=='create_template'}
{module name=$arrNest.name action=$arrNest.action}
{elseif $arrNest.name&&$arrNest.flg_tpl}{*pop-up actions*}
{*тут отступы лучше не делать а то попадут в буфер*}{module name=$arrNest.name action=$arrNest.action}
{else}
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>{if $arrCurReverse}{foreach $arrCurReverse as $node}{$node.title} / {/foreach}{/if}{Core_Module_Router::$domain}</title>
	<meta content="text/html; charset=utf-8" http-equiv="content-type" />
	{*if $arrHtmlRedirect}<meta http-equiv="Refresh" content="{$arrHtmlRedirect.sec}; url={$arrHtmlRedirect.url}">{/if*}
	<link rel="stylesheet" type="text/css" href="/skin/_css/backend.css" />
	<link rel="stylesheet" type="text/css" href="/skin/_css/tips.css" />
	<script type="text/javascript" src="/skin/_js/mootools.js"></script>
	<script type="text/javascript" src="/skin/_js/xlib.js"></script>
	<script type="text/javascript" src="/skin/_js/form_checker.js"></script>
	{*r.alert*}
	<link rel="stylesheet" type="text/css" href="/skin/_js/roar/roar.css" />
	<script type="text/javascript" src="/skin/_js/roar/roar.js"></script>
	{*calendar*}
	<link rel="stylesheet" type="text/css" href="/skin/_js/jscalendar/css/jscal2.css" />
	<link rel="stylesheet" type="text/css" href="/skin/_js/jscalendar/css/border-radius.css" />
	<!--<link rel="stylesheet" type="text/css" href="/skin/_js/jscalendar/css/steel/steel.css" />-->
	<script type="text/javascript" src="/skin/_js/jscalendar/js/jscal2.js"></script>
	<script type="text/javascript" src="/skin/_js/jscalendar/js/unicode-letter.js"></script>
	<script type="text/javascript" src="/skin/_js/jscalendar/js/lang/en.js"></script>
	{*cerabox*}
	<link rel="stylesheet" href="/skin/_js/cerabox/style/cerabox.css" media="screen" />
	<script type="text/javascript" src="/skin/_js/cerabox/cerabox.js"></script>
	{*i18n*}
	{if Zend_Registry::isRegistered( 'locale' )}
	<link rel="stylesheet" type="text/css" href="/skin/_js/lang/style.css" />
	<script type="text/javascript" src="/skin/_js/lang/lang.js"></script>
	{literal}
	<script type="text/javascript">
	var i18n;
	window.addEvent('domready',function(){
		i18n=new Lang({
			elements:'.lang',
			language:'{Core_i18n_Dynamic::$flags|json}'
		});
	});
	</script>
	{/literal}
	{/if}
	{literal}
	<script type="text/javascript" src="/skin/_js/validator/validator.js"></script>
	<link rel="stylesheet" type="text/css" href="/skin/_js/validator/style.css">
	<script type="text/javascript">
		img_preload([
			'/skin/i/backend/paging/sr_first.gif',
			'/skin/i/backend/paging/sr_first_gray.gif',
			'/skin/i/backend/paging/sr_prev.gif',
			'/skin/i/backend/paging/sr_prev_gray.gif',
			'/skin/i/backend/paging/sr_next.gif',
			'/skin/i/backend/paging/sr_next_gray.gif',
			'/skin/i/backend/paging/sr_last.gif',
			'/skin/i/backend/paging/sr_last_gray.gif',
			'/skin/i/backend/down.gif',
			'/skin/i/backend/down_off.gif',
			'/skin/i/backend/up.gif',
			'/skin/i/backend/up_off.gif'
		]);
		var r,tips;
		window.addEvent('domready',function(){
			if (window.ie) new ie_hover_tabletr_fix();
			tips=new Tips('.tooltip');
			r=new Roar();
			validator=new WhValidator({className:'validate'});
		});
	</script>
	{/literal}
</head>
<body>
	<div id="container">
		<div class="top">
			{if $arrUser.id}
			<ul>
				<li><a href="{url name='members' action='logout'}">exit</a></li>
				<li>signed in as {if in_array('Super Admin',$arrUser.groups)}<a href="{url name='members' action='set'}?id={$arrUser.id}">{/if}<b>{$arrUser.nickname}</b></a></li>
				{if $arrUser.right_parsed.backend}
					<li>current managed frontend
					{foreach from=$arrF item='v'}
					<a href="{Core_Module_Router::$uriFull}?new_frontend={$v.sys_name}" title="">{if $admin_current_frontend==$v.sys_name}<b>{$v.sys_name}</b>{else}{$v.sys_name}{/if}</a>
					{/foreach}
					</li>
				{/if}
				{if $config->engine->i18n}
					<li>current language
						{foreach Core_i18n::$lang as $k=>$v}
						<a href="/{$k}{Core_Module_Router::$uriWithoutLng}"{if Zend_Registry::get( 'locale' )->getLanguage()==$k} style="border-style:dotted;"{/if}><img src="/skin/i/frontends/flags/{$k}.png" width="16" height="11" alt="{$v}" /></a>
						{/foreach}
					</li>
				{/if}
			</ul>
			{/if}
		</div>
		<div id="leftmenu">{include file="inc_amenu.tpl"}</div>
		<div id="content">
		{if $arrUser.id}
			{if $arrNest.action&&$arrNest.action==$arrPrm.action} {*cur mod actions*}
				<h1>{$arrPrm.title}</h1>
				{include file="b_`$arrPrm.action`.tpl"}
			{elseif $arrNest.name} {*nested mod actions*}
				{module name=$arrNest.name action=$arrNest.action mcur=1}
			{else}
				<h1>{if LANG=='ru'}Выберите один из разделов меню{else}Select an item from left menu{/if}</h1>
			{/if}
		{else}
			{module name='members' action='login'}
		{/if}
		</div>
	</div>
	<div id="footer_container">{Core_Module_Router::$domain} &copy; all rights reserved.</div>
</body>
</html>
{/if}