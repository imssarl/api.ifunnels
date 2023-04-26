Hello, {$arrUser.nickname}<br/><br/>
Your "{$arrPrj.title}" content syndication project content has been reviewed and approved by our team. Below you can see the statuses of your content:<br/><br/>
<table>
	<tr>
		<td>title</td>
		<td>status</td>
		<td>comment</td>
	</tr>
{foreach $arrPrj.content as $kt}
	<tr>
		<td>{$kt.title}</td>
		<td>{$arrStat[$kt.flg_status]}</td>
		<td>{$kt.comment}</td>
	</tr>
{/foreach}
</table>
<br /><br />
Regards,<br/>
Creative Niche Manager Support Team