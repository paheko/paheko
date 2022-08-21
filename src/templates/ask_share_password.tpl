<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8" />
	<title>Accès document</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<link rel="stylesheet" type="text/css" href="{$admin_url}static/admin.css?{$version_hash}" media="all" />
	<style type="text/css">
	{literal}
	main {
		max-width: 650px;
		margin: 2em auto;
		text-align: center;
	}

	main input[type=password] {
		font-size: 1.5em;
	}

	main legend {
		font-size: 1.3em;
		padding: 0 2em;
	}

	main dl {
		padding: 1em 0;
	}

	main p.block.error, main p.block.alert {
		margin: 2em 0;
		font-size: 1.2em;
	}
	{/literal}
	</style>
</head>

<body class="transparent dialog">

<main>

{if $has_password}
<p class="block error">
	Le mot de passe fourni ne correspond pas.<br />Merci de vérifier la saisie.
</p>
{else}
	<p class="block alert">Un mot de passe est nécessaire pour accéder à ce document.</p>
{/if}

<form method="post" action="">
	<fieldset>
		<legend>Accès au document</legend>
		<dl>
			{input type="password" name="p" required=true label="Mot de passe"}
		</dl>
		<p class="submit">
			{button type="submit" label="Accéder au document" shape="right" class="main"}
		</p>
	</fieldset>
</form>

</main>

</body>
</html>