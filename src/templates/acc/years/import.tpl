{include file="admin/_head.tpl" title="Importer des écritures" current="acc/years"}

{include file="acc/_year_select.tpl"}

<nav class="tabs">
	<ul>
		<li class="current"><a href="{$admin_url}admin/acc/years/import.php">Import</a></li>
		<li><a href="{$admin_url}admin/acc/years/import.php?export=csv">Export journal général CSV</a></li>
		<li><a href="{$admin_url}admin/acc/years/import.php?export=ods">Export journal général tableur</a></li>
	</ul>
</nav>

{form_errors}

<form method="post" action="{$self_url}" enctype="multipart/form-data">

	<fieldset>
		<legend>Import d'écritures</legend>
		<dl>
			<dt><label for="f_type_garradin">Format de fichier</label></dt>
			{input type="radio" name="type" value="garradin" label="Journal général, format Garradin"}
			{input type="radio" name="type" value="garradin" label="Journal format libre"}
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
			{input type="file" name="file" label="Fichier CSV" accept=".csv,text/csv" required=1}
			<dd class="alert">
				Les lignes comportant un numéro d'écriture mettront à jour les écritures existantes correspondant à ces numéros (sauf si celles-ci ont été validées), alors que les lignes sans numéro créeront de nouvelles écritures.
			</dd>
			<dd class="help">
				Si le fichier comporte des opérations dont la date est en dehors de l'exercice courant,	elles seront ignorées.
			</dd>
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key="acc_years_import_%d"|args:$year.id}
		<input type="submit" name="import" value="{if $csv_file}Importer{else}Choisir les colonnes{/if} &rarr;" />
	</p>

</form>

{include file="admin/_foot.tpl"}