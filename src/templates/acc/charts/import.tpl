{include file="admin/_head.tpl" title="Importer un nouveau plan comptable" current="acc/charts"}

{include file="./_nav.tpl" current="import"}

{form_errors}

<form method="post" action="{$self_url}" enctype="multipart/form-data" data-focus="1">
	<fieldset>
		<legend>Importer un plan comptable</legend>
		<dl>
			{input type="text" name="label" label="Libellé" required=1}
			{input type="select" name="country" label="Pays" required=1 options=$country_list default=$config.pays}
			{input type="file" name="file" label="Fichier à importer" accept="csv" required=1}
			<dd class="help"> {* FIXME utiliser _csv_help.tpl ici ! *}
				Règles à suivre pour créer le fichier&nbsp;:
				<ul>
					<li>Le fichier doit comporter les colonnes suivantes : <em>{$columns}</em></li>
					<li>Suggestion : pour obtenir un exemple du format attendu, faire un export d'un plan comptable existant</li>
				</ul>
			</dd>
		</dl>
	</fieldset>
	<p class="submit">
		{csrf_field key="acc_charts_import"}
		{button type="submit" name="import" label="Importer" shape="upload" class="main"}
	</p>
</form>

{include file="admin/_foot.tpl"}