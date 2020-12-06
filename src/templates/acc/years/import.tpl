{include file="admin/_head.tpl" title="Importer des écritures" current="acc/years"}

<nav class="acc-year">
	<h4>Exercice sélectionné&nbsp;:</h4>
	<h3>{$year.label} — {$year.start_date|date_short} au {$year.end_date|date_short}</h3>
</nav>

<nav class="tabs">
	<ul>
		<li class="current"><a href="{$admin_url}acc/years/import.php?id={$year.id}">Import</a></li>
		<li><a href="{$admin_url}acc/years/import.php?id={$year.id}&amp;export=csv">Export journal général - CSV</a></li>
		<li><a href="{$admin_url}acc/years/import.php?id={$year.id}&amp;export=ods">Export journal général - tableur</a></li>
	</ul>
</nav>

{form_errors}

<form method="post" action="{$self_url}" enctype="multipart/form-data">

{if $csv->loaded()}

		{include file="common/_csv_match_columns.tpl"}

		<p class="submit">
			{csrf_field key=$csrf_key}
			{button type="submit" name="cancel" value="1" label="Annuler" shape="left"}
			{button type="submit" name="assign" label="Continuer" class="main" shape="right"}
		</p>

{else}

	<fieldset>
		<legend>Import d'écritures</legend>
		<dl>
			<dt><label for="f_type_garradin">Format de fichier</label></dt>
			{input type="radio" name="type" value="garradin" label="Journal général au format CSV Garradin" default="garradin"}
			{input type="radio" name="type" value="csv" label="Journal au format CSV libre"}
			{include file="common/_csv_help.tpl"}
			{input type="file" name="file" label="Fichier CSV" accept=".csv,text/csv" required=1}
			<dd class="help block">
				- Les lignes comportant un numéro d'écriture mettront à jour les écritures existantes correspondant à ces numéros (sauf si celles-ci ont été validées), alors que les lignes sans numéro créeront de nouvelles écritures.<br />
				- Si le fichier comporte des écritures dont la date est en dehors de l'exercice courant, elles seront ignorées.
			</dd>
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="load" label="Importer" shape="upload" class="main"}
	</p>

{/if}

</form>

{include file="admin/_foot.tpl"}