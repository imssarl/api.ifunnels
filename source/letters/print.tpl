<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Mail message print</title>
	<meta content="text/html; charset=utf-8" http-equiv="content-type" />
{literal}
<style type="text/css">
<!--
p.printLittle {
	font-size: 75%;
	font-family: Verdana, Arial, Helvetica, sans-serif;
	line-height:18px;
}
-->
</style>
{/literal}
</head>
<div><p class="printLittle">TO:</p> {$to}</div>
<div><p class="printLittle">FROM:</p> {$from}</div>
<div><p class="printLittle">SUBJECT:</p> {$subject}</div>
<div><p class="printLittle">RAW:</p><pre>{$raw}</pre></div>
</body>
</html>