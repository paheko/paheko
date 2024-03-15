{include file="_head.tpl" title=$file.name current=null layout="raw"}

<style type="text/css">
{literal}

body {
	position: relative;
	padding: 0;
}
main {
	position: fixed;
	left: 0;
	right: 0;
	top: 0;
	bottom: 0;
	overflow: hidden;
}
body, main, .download {
	display: flex;
	flex-direction: column;
	align-items: stretch;
	justify-content: stretch;
	height: 100%;
}
#document {
	display: flex;
	flex-direction: column;
	justify-content: center;
	align-items: center;
	height: calc(100% - 3em);
}
#document * {
	max-width: 95%;
	max-height: 95%;
}
header.public {
	display: flex;
	width: 100%;
	align-items: center;
	margin: 0;
}
header.public div {
	display: flex;
	flex-direction: column;
	margin: .5rem 1rem;
}
header.public p {
	margin-left: auto;
	margin-right: 1rem;
}
header.public div h1 a {
	font-size: .9rem;
	font-weight: normal;
	margin: 0;
	padding: 0;
	color: var(--gBorderColor);
}
header.public div h3 {
	font-size: 1rem;
}
body.public main {
	margin: 0;
	max-width: unset;
	height: 100%;
}
.download {
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 1rem;
	font-size: 1.3em;
}
#document iframe {
	border: none;
	width: 100%;
	height: 100%;
	max-width: unset;
	max-height: unset;
}
{/literal}
</style>

<header class="public">
	<div>
		<h1 class="org"><a href="{$site_url}" target="_blank">{if $config.files.logo}<img src="{$config->fileURL('logo', '150px')}" alt="" />{else}{$config.org_name}{/if}</a></h1>
		<h3 class="title">{$file.name}</h3>
	</div>
	<p>
		{linkbutton shape="download" label="Télécharger" href=$download_url}
	</p>
</header>

<div id="document">
	{if $object}
		{$object|raw}
	{else}
		<div class="download">
			<h2>{$file.name}</h2>
			<p>({$file.size|size_in_bytes})</p>
			{linkbutton shape="download" label="Télécharger" href=$download_url class="main"}
		</div>
	{/if}
</div>

{include file="_foot.tpl"}