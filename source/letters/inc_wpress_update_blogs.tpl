Hello, {$name}<br/><br/>
The Blog Fusion team wants to notify you that the following of your blogs have been updated to the latest version of WordPress: {$version}.<br/>
<ol>
{foreach from=$arrBlogs item='i'}
<li><a href="{$i.url}" target="_blank">{$i.title} (id:{$i.id})</a> Blog upgrade [{$i.version}]>>>[{$version}] version. {if !empty($i.error)}But script have exception: {$i.error}.{/if}</li>
{/foreach}
</ol>
Regards,<br/>
Blog Fusion<br/>
Creative Niche Manager Support Team