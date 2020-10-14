{include file="admin/_head.tpl" title="Importer un plan comptable" current="acc/charts"}

{form_errors}

<form method="post" action="{$self_url}" enctype="multipart/form-data">
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
				</ul>
			</dd>
		</dl>
		<p class="submit">
			{csrf_field key="acc_charts_import"}
			<input type="submit" name="import" value="Importer &rarr;" />
		</p>
	</fieldset>
</form>

{include file="admin/_foot.tpl"}