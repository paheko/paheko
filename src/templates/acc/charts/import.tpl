{include file="admin/_head.tpl" title="Importer un nouveau plan comptable" current="acc/charts"}

<nav class="tabs">
	<ul>
		<li><a href="{$admin_url}acc/charts/">Plans comptables</a></li>
		<li class="current"><a href="{$admin_url}acc/charts/import.php">Importer un plan comptable</a></li>
	</ul>
</nav>

{form_errors}

<form method="post" action="{$self_url}" enctype="multipart/form-data" data-focus="1">
	<fieldset>
		<legend>Importer un plan comptable</legend>
		<dl>
			{input type="text" name="label" label="Libellé" required=1}
			{input type="select" name="country" label="Pays" required=1 options=$country_list default=$config.pays}
			{input type="file" name="file" label="Fichier CSV" accept=".csv,text/csv" required=1}
			<dd class="help">
				Règles à suivre pour créer le fichier CSV&nbsp;:
				<ul>
					<li>Il est recommandé d'utiliser LibreOffice pour créer le fichier CSV</li>
					<li>Le fichier doit être en UTF-8</li>
					<li>Le séparateur doit être le point-virgule ou la virgule</li>
					<li>Cocher l'option <em>"Mettre en guillemets toutes les cellules du texte"</em></li>
					<li>Le fichier doit comporter les colonnes suivantes : <em>{$columns}</em></li>
					<li>Pour obtenir un exemple du format attendu, faire un export d'un plan comptable existant</li>
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