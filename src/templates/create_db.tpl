{include file="_head.tpl" title="Créer une base de données" current=""}

{form_errors}

<form method="post" action="">

	<fieldset>
		<legend>Créer une nouvelle base de données</legend>
		<dl>
			{input type="text" name="name" required="true" label="Nom de la base de données" help="Le fichier aura automatiquement l'extension '.sqlite'"}
		</dl>
		<p class="submit">
			{csrf_field key=$csrf_key}
			{button type="submit" name="create" label="Créer" shape="right" class="main"}
		</p>
	</fieldset>

{include file="_foot.tpl"}