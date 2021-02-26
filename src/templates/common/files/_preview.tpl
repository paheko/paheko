{include file="admin/_head.tpl" title=$file.name custom_css=['/content.css']}

{if $file->customType()}
	{$content|raw}
{else}
	<pre>{$content}</pre>
{/if}

{include file="admin/_foot.tpl"}
