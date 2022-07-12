{include file="admin/_head.tpl" title="Importer des écritures" current="acc/years"}

<nav class="acc-year">
	<h4>Exercice sélectionné&nbsp;:</h4>
	<h3>{$year.label} — {$year.start_date|date_short} au {$year.end_date|date_short}</h3>
</nav>

<nav class="tabs">
	<ul>
		<li class="current"><a href="{$admin_url}acc/years/import.php?year={$year.id}">Import</a></li>
		<li><a href="{$admin_url}acc/years/export.php?year={$year.id}">Export</a></li>
	</ul>
</nav>

{form_errors}


{if $type_name && $csv->loaded()}
<form method="post" action="{$self_url}">
		{include file="common/_csv_match_columns.tpl"}

		<p class="submit">
			{csrf_field key=$csrf_key}
			{button type="submit" name="cancel" value="1" label="Annuler" shape="left"}
			{button type="submit" name="assign" label="Continuer" class="main" shape="right"}
		</p>
</form>
{elseif $type_name}
<form method="post" action="{$self_url}" enctype="multipart/form-data">

	<fieldset>
		<legend>Import d'écritures</legend>
		<dl>
			<dt>
				Type d'import
			</dt>
			<dd>
				{$type_name}
			</dd>
			{input type="file" name="file" label="Fichier à importer" accept="csv" required=true}
			{include file="common/_csv_help.tpl" csv=$csv}
			{input type="checkbox" name="ignore_ids" value="1" label="Ne pas tenir compte des numéros d'écritures" help="Si coché, les écritures importées seront créées, même si un numéro d'écriture est fourni et qu'il existe déjà. Cela peut mener à avoir des écritures en doublon."}
		</dl>
		<p class="help block">
			- Les lignes comportant un numéro d'écriture existant mettront à jour les écritures correspondant à ces numéros.<br />
			- Les lignes comportant un numéro inexistant renverront une erreur.<br />
			- Les lignes dont le numéro est vide créeront de nouvelles écritures.<br />
			- Si le fichier comporte des écritures dont la date est en dehors de l'exercice courant, elles seront ignorées.
		</p>

	</fieldset>

	<p class="submit">
		{csrf_field key=$csrf_key}
		{linkbutton href="?year=%d"|args:$year.id label="Annuler" shape="left"}
		{button type="submit" name="load" label="Importer" shape="upload" class="main"}
	</p>

</form>

{else}

<form method="get" action="{$self_url_no_qs}">
	<fieldset>
		<legend>Import d'écritures</legend>
		<dl>
			<dt><label for="f_type_garradin">Type de fichier à importer</label></dt>
			{input type="radio-btn" name="type" value="simple" label="Simplifié (comptabilité de trésorerie)" default="simple" help="Chaque ligne représente une écriture, comme dans un cahier. Les écritures avancées ne peuvent pas être importées dans ce format."}
			<dd class="help example">
				Exemple :
				<table class="list auto">
					{foreach from=$examples.simple item="row"}
					<tr>
						{foreach from=$row item="v"}
						<td>{$v}</td>
						{/foreach}
					</tr>
					{/foreach}
				</table>
			</dd>
			{input type="radio-btn" name="type" value="grouped" label="Complet groupé (comptabilité d'engagement)" help="Permet d'avoir des écritures avancées. Les 7 premières colonnes de chaque ligne sont vides pour indiquer les lignes suivantes de l'écriture."}
			<dd class="help example">
				Exemple :
				<table class="list auto">
					{foreach from=$examples.grouped item="row"}
					<tr>
						{foreach from=$row item="v"}
						<td>{$v}</td>
						{/foreach}
					</tr>
					{/foreach}
				</table>
			</dd>
		</dl>
	</fieldset>

	<p class="submit">
		<input type="hidden" name="year" value="{$year.id}" />
		{button type="submit" label="Continuer" shape="right" class="main"}
	</p>
</form>

{/if}


{include file="admin/_foot.tpl"}