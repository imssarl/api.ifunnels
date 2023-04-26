<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title>Error hunter say: "You Have {$arrHeader.errname}!"</title>
<meta content="text/html; charset=utf-8" http-equiv="content-type" />
{literal}
<style>.others{font: small Verdana, Geneva, Arial, Helvetica, sans-serif;}</style>
{/literal}
</head>

<body style="background-color: #E1E1CE;">
<table border="0" cellpadding="0" cellspacing="0" width="80%" align="center">
<tr><td>
<div class="others" align="center"><b><u>Error Hunter Panel {Core_Errors::$version}</u></b>{*<br>developed by Rodion Konnov*}</div><br>
<table border="0" cellpadding="0" cellspacing="0" width="100%">
<tr>
	<td class="others"><b>date is</b> {$datetime}</td>
	<td class="others"><b>error in</b> 
	{if $arrHeader.myType==constant("Core_Errors::DB")}MySQL query
	{elseif $arrHeader.myType==constant("Core_Errors::PHP")}PHP or 3rd code
	{elseif $arrHeader.myType==constant("Core_Errors::DEV")}Project code
	{elseif $arrHeader.myType==constant("Core_Errors::ENGINE")}WH Framework
	{elseif $arrHeader.myType==constant("Core_Errors::LOCAL")}File system
	{elseif $arrHeader.myType==constant("Core_Errors::REMOTE")}Remote services
	{else}Unknown{/if}.
	</td>
	<td class="others">{if $arrHeader.errname}<b>error type:</b> {$arrHeader.errname}{/if}</td>
</tr>
</table>
<div class="others">{if $arrHeader.msg>''}<br><b>message: </b>{$arrHeader.msg}<br><br>{/if}<b>located in:</b> {$arrHeader.file}</div>
{if $trace}
	<br /><div class="others"><b>trace error:</b></div>
	{foreach $trace as $v}
	<div>
		<table border="0" cellpadding="0" cellspacing="0" width="100%" >
			{foreach from=$v key='k' item='val'}
			<tr>
				<td class="others" width="10%">{$k}:&nbsp;</td>
				<td class="others">{$val}</td>
			</tr>
			{/foreach}
		</table>
	</div>
	{/foreach}
{/if}
<br /><div class="others"><strong>Project</strong> {$project} on <b>PHP version: </b><br>{$phpver}</div>
<br /><div class="others"><b>$_SESSION: </b><br><pre>{$session}</pre></div>
<br /><div class="others"><b>$_SERVER: </b><br><pre>{$server}</pre></div>
<br /><div class="others"><b>$_REQUEST: </b><br><pre>{$request}</pre></div>
</td></tr>
</table>
</body>

</html>