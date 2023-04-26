Hello, {$arrUser.nickname}<br/><br/>
Your "{$arrPrj.title}" content syndication project content has been reviewed by our team, and some of the project content pieces have been rejected. Please check the statuses below:<br/><br/>
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
Please go to the Content Syndication module, and edit or delete the rejected content pieces. Then, re-submit the project again.<br/><br/>
Regards,<br/>
Creative Niche Manager Support Team