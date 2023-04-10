{include file="_head.tpl" title="Importer un module" current="config"}

{form_errors}

<form method="post" action="" enctype="multipart/form-data">
<fieldset>
	<legend>Importer un module d'extension</legend>
	<dl>
		{input type="file" required=true label="Fichier ZIP du module" name="zip" accept=".zip,application/zip"}
	</dl>
	<p class="alert block">
		<strong>Attention, faites-vous confiance à la personne qui vous a transmis ce module&nbsp;?</strong><br />
		Importer un module de source inconnue peut présenter des risques pour les données de votre association.<br />
		Un module écrit par une personne mal intentionnée pourrait voler les données de votre association, ou modifier ou supprimer des données.
	</p>
	<dl>
		{input type="checkbox" name="confirm" value=1 label="Je comprends les risques, importer ce module" required=true}
	</dl>
	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" shape="right" label="Importer ce module" name="import" class="main"}
	</p>
</fieldset>
</form>

{include file="_foot.tpl"}