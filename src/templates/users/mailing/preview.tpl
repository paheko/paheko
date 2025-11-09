<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8" />
	<style type="text/css">
	{literal}
	body {
		background: #fff;
		color: #000;
		font-family: sans-serif;
	}
	main {
		max-width: 650px;
		margin: 0 auto;
	}
	pre {
		white-space: pre-wrap;
		font-family: monospace;
	}
	.help {
		border: 1px solid #ccc;
		border-radius: .5em;
		padding: .5em;
		background: #eee;
	}
	{/literal}
	</style>
</head>
<body>

<main>
{if $view === 'text'}
	<p class="help">Ce message sera vu sur les logiciels de mail ne gérant pas le HTML, où dont l'usager⋅usagère préfère ne pas voir le formattage riche.</p>
{/if}

	<pre>{$code|raw}</pre>
</main>

</body>
</html>