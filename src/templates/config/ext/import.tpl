{include file="_head.tpl" title="Importer un module" current="config"}

{form_errors}

{if !$_GET.ok}
	<div class="alert block">
		<h3>Attention, faites-vous confiance à la personne qui vous a transmis ce module&nbsp;?</h3>
		<p>
			<strong>Importer un module de source inconnue peut présenter des risques pour les données de votre association.</strong><br />
			Un module écrit par une personne mal intentionnée pourrait voler les données de votre association, ou modifier ou supprimer des données.
		</p>
	</div>
	<p>
		{linkbutton shape="right" href="?ok=1" label="Je comprends les risques, continuer"}
	</p>
{else}
	<form method="post" action="" enctype="multipart/form-data">
	<fieldset>
		<legend>Importer un module d'extension</legend>
		<dl>
			{input type="file" required=true label="Fichier ZIP du module" name="zip" accept=".zip,application/zip"}
			{if $exists}
				{input type="checkbox" name="overwrite" value=1 label="Écraser mes modifications existantes" required=true}
			{/if}
		</dl>
		<p class="submit">
			{csrf_field key=$csrf_key}
			{button type="submit" shape="right" label="Importer ce module" name="import" class="main"}
		</p>
	</fieldset>
	</form>
{/if}

{include file="_foot.tpl"}