<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
<head>
	<meta charset="utf-8" />
	<title>{if empty($title)}Erreur{else}{$title}{/if}</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<style type="text/css">
	{literal}
	* { margin: 0; padding: 0; }

	html {
		width: 100%;
		height: 100%;
		display: flex;
		align-items: center;
		justify-content: center;
		text-align: center;
	}
	body {
		font-size: 100%;
		color: #000;
		font-family: "Trebuchet MS", Helvetica, Sans-serif;
		background: #fff;
		padding: 1em;
		font-size: 1.5em;
	}

	h1 { color: darkred; margin-bottom: 1rem; }

	p.error {
		background: #fcc;
		padding: 0.9em;
		margin-bottom: 1em;
		border-radius: .5em;
		max-width: 40rem;
	}

	p.back a {
		display: inline-flex;
		box-shadow: 0px 0px 5px 1px #666;
		background: #eee;
		border-radius: .5em;
		padding: .5em;
		color: #009;
		text-decoration: none;
	}

	p.back a:hover {
		background: #eef;
		box-shadow: 0px 0px 5px 1px darkblue;
	}

	p.back a:hover span {
		color: blue;
		text-decoration: none;
	}

	p.back b {
		font-size: 2em;
		line-height: .5em;
		opacity: 0.5;
		margin-right: 1rem;
	}

	p.back span {
		text-decoration: underline;
		display: block;
		text-align: center;
	}
	{/literal}
	</style>
</head>

<body>

<h1>{if empty($title)}Erreur{else}{$title}{/if}</h1>

<p class="block error">
	{if $html_error}
		{$html_error|raw}
	{else}
		{$error|escape|nl2br}
	{/if}
</p>

<p class="back">
	<a href="{$admin_url}" onclick="if (typeof window.parent.g !== 'undefined') {ldelim} window.parent.g.closeDialog(); return false; {rdelim}"><b>«</b><span>Retour</span></a>
</p>

</body>
</html>