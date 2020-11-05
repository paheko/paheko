{include file="admin/_head.tpl" title="Importer des écritures" current="acc/years"}

{include file="acc/_year_select.tpl"}

<nav class="tabs">
	<ul>
		<li class="current"><a href="{$admin_url}acc/years/import.php?id={$year.id}">Import</a></li>
		<li><a href="{$admin_url}acc/years/import.php?id={$year.id}&amp;export=csv">Export journal général - CSV</a></li>
		<li><a href="{$admin_url}acc/years/import.php?id={$year.id}&amp;export=ods">Export journal général - tableur</a></li>
	</ul>
</nav>

{form_errors}

<form method="post" action="{$self_url}" enctype="multipart/form-data">

	{if $csv_file}

	<fieldset>
		<legend>Importer depuis un fichier CSV générique</legend>
		<p class="help">{$csv_file|count} lignes trouvées dans le fichier</p>
		<dl>
			<dt><label><input type="checkbox" name="skip_first_line" value="1" checked="checked" /> Ne pas importer la première ligne</label></dt>
			<dd class="help">Décocher cette case si la première ligne ne contient pas l'intitulé des colonnes, mais des données.</dd>
			<dt><label>Correspondance des colonnes</label></dt>
			<dd class="help">Indiquer la correspondance entre colonnes du CSV et données comptables.</dd>
			<dd>
				<table class="list auto">
					<tbody>
					{foreach from=$csv_first_line key="index" item="csv_field"}
						<tr>
							<th>{$csv_field}</th>
							<td>
								<select name="translate[{$index}]">
									<option value="">-- Ne pas importer cette colonne</option>
									{foreach from=$possible_columns item="label" key="key"}
										<option value="{$key}" {if isset($_POST['translate'][$index]) && $key == $_POST['translate'][$index]}selected="selected"{/if}>{$label}</option>
									{/foreach}
								</select>
							</td>
						</tr>
					{/foreach}
					</tbody>
				</table>
			</dd>
		</dl>
	</fieldset>

	<input type="hidden" name="csv_encoded" value="{$csv_file|escape:'json'|escape}" />

	{else}

	<fieldset>
		<legend>Import d'écritures</legend>
		<dl>
			<dt><label for="f_type_garradin">Format de fichier</label></dt>
			{input type="radio" name="type" value="garradin" label="Journal général au format CSV Garradin"}
			{input type="radio" name="type" value="csv" label="Journal au format CSV libre"}
			<dd class="help">
				Règles à suivre pour créer le fichier CSV&nbsp;:
				<ul>
					<li>Il est recommandé d'utiliser LibreOffice pour créer le fichier CSV</li>
					<li>Le fichier doit être en UTF-8</li>
					<li>Le séparateur doit être le point-virgule ou la virgule</li>
					<li>Cocher l'option <em>"Mettre en guillemets toutes les cellules du texte"</em></li>
					<li>Le fichier doit comporter les colonnes suivantes : <em>{$columns}</em></li>
					<li>Le fichier peut également comporter les colonnes suivantes : <em>{$other_columns}</em></li>
				</ul>
			</dd>
			{input type="file" name="file" label="Fichier CSV" accept=".csv,text/csv" required=1}
			<dd class="help block">
				- Les lignes comportant un numéro d'écriture mettront à jour les écritures existantes correspondant à ces numéros (sauf si celles-ci ont été validées), alors que les lignes sans numéro créeront de nouvelles écritures.<br />
				- Si le fichier comporte des écritures dont la date est en dehors de l'exercice courant, elles seront ignorées.
			</dd>
		</dl>
	</fieldset>

	{/if}

	<p class="submit">
		{csrf_field key="acc_years_import_%d"|args:$year.id}
		{if $csv_file}
			<input type="submit" name="cancel" class="minor" value="Annuler l'import" />
		{/if}
		<input type="submit" name="import" value="Importer &rarr;" />
	</p>


</form>

{include file="admin/_foot.tpl"}