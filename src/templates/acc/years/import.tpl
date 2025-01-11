<?php
use Paheko\Accounting\Export;
?>
{include file="_head.tpl" title="Importer des écritures" current="acc/years"}

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

{if $type_name && $csv->ready()}
<form method="post" action="{$self_url}">
	<p class="alert block">
		Aucun problème n'a été détecté.<br />
		Voici un résumé des changements qui seront apportés par cet import&nbsp;:
	</p>

	{if $report.accounts_count}
	<details>
		<summary>
			<h2>{{%n compte sera créé}{%n comptes seront créées} n=$report.accounts_count}</h2>
		</summary>
		<table class="list auto">
		{foreach from=$report.accounts item="account"}
			<tr>
				<th>{$account.code}</th>
				<td>{$account.label}</td>
			</tr>
		{/foreach}
		</table>
	</details>
	{/if}

	{if $report.created_count}
	<details>
		<summary>
			<h2>{{%n écriture sera créée}{%n écritures seront créées} n=$report.created_count}</h2>
		</summary>
		<p class="help">Les écritures suivantes mentionnées dans le fichier seront ajoutées.</p>
		{include file="acc/reports/_journal.tpl" journal=$report.created with_linked_users=true}
	</details>
	{/if}

	{if $report.modified_count}
	<details>
		<summary>
			<h2>{{%n écriture sera modifiée}{%n écritures seront modifiées} n=$report.modified_count}</h2>
		</summary>
		<p class="help">Les écritures suivantes mentionnées dans le fichier seront modifiées.<br />En rouge ce qui sera supprimé, en vert ce qui sera ajouté.</p>
		{include file="acc/reports/_journal_diff.tpl" journal=$report.modified}
	</details>
	{/if}

	{if $report.unchanged_count}
	<details>
		<summary>
			<h3>{{%n écriture ne sera pas affectée}{%n écritures ne seront pas affectées} n=$report.unchanged_count}</h3>
		</summary>
		<p class="help">Les écritures suivantes mentionnées dans le fichier <strong>ne seront pas modifiées</strong>.</p>
		{include file="acc/reports/_journal.tpl" journal=$report.unchanged with_linked_users=true}
	</details>
	{/if}

	{if !$report.modified_count && !$report.created_count}
	<p class="error block">
		Aucune modification ne serait apportée par ce fichier à importer. Il n'est donc pas possible de terminer l'import.
	</p>
	{else}
	<p class="help">
		En validant ce formulaire, ces changements seront appliqués.
	</p>
	{/if}

	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="cancel" value="1" label="Annuler" shape="left"}
		{if $report.modified_count || $report.created_count}
		{button type="submit" name="import" label="Importer" class="main" shape="upload"}
		{/if}
	</p>
</form>
{elseif $type_name && $csv->loaded()}
<form method="post" action="{$self_url}">
	{include file="common/_csv_match_columns.tpl"}

	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="cancel" value="1" label="Annuler" shape="left"}
		{button type="submit" name="preview" label="Prévisualiser" class="main" shape="right"}
	</p>
</form>
{elseif $type_name}
<form method="post" action="{$self_url}" enctype="multipart/form-data">

	<fieldset>
		<legend>Importer un fichier</legend>
		<dl>
			<dt>
				Type d'import
			</dt>
			<dd>
				{$type_name}
			</dd>
			{input type="file" name="file" label="Fichier à importer" accept="csv" required=true}
			{include file="common/_csv_help.tpl" csv=$csv more_text="
				Si le fichier comporte des écritures dont la date est en dehors de l'exercice courant, elles seront ignorées."}
		</dl>

	</fieldset>

	<fieldset>
		<legend>Configuration de l'import</legend>
		<dl>
			<dt><label for="f_ignore_ids_1">Mode d'import</label> <b>(obligatoire)</b></dt>

			{input type="radio" name="ignore_ids" value="1" label="Créer toutes les écritures" required=true}
			<dd class="help">Toutes les écritures du fichier seront créées, sans tenir compte du numéro s'il est fourni.<br />Cela peut amener à avoir des écritures en doublon si on réalise plusieurs imports du même fichier.</dd>

			{input type="radio" name="ignore_ids" value="0" label="Mettre à jour en utilisant le numéro d'écriture" required=true}
			<dd class="help">
				Les écritures dans le fichier qui mentionnent un numéro d'écriture seront mises à jour en utilisant ce numéro.<br/>
				Si une ligne du fichier mentionne un numéro d'écriture qui n'existe pas, l'import échouera.<br />
				Les écritures qui ne mentionnent pas de numéro seront créées.
			</dd>

			{if $type == Export::FEC}
				<dd><p class="alert block">Avec le format FEC, cette option effacera certaines données des écritures mises à jour : référence du paiement et projet analytique.</p></dd>
			{/if}

			<dt><label for="f_auto_create_accounts_1">Création de comptes</label> <i>(facultatif)</i></dt>
			{input type="checkbox" name="auto_create_accounts" value="1" label="Créer les comptes qui n'existent pas dans le plan comptable" default=$auto_create_accounts}
			<dd class="help">Si cette case est cochée, si un numéro de compte dans une écriture importée n'existe pas, alors le compte sera automatiquement ajouté au plan comptable.</dd>
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key=$csrf_key}
		{linkbutton href="?year=%d"|args:$year.id label="Annuler" shape="left"}
		{button type="submit" name="load" label="Charger le fichier" shape="right" class="main"}
	</p>

</form>

{else}

<form method="get" action="{$self_url_no_qs}">
	<fieldset>
		<legend>Import d'écritures</legend>
		<dl>
			<dt><label for="f_type_grouped">Type de fichier à importer</label></dt>
			{foreach from=$types key="type" item="info"}
			{input type="radio-btn" name="type" value=$type label=$info.label help=$info.help default="simple"}
			<dd class="help example">
				Exemple :
				<table class="list auto">
					{foreach from=$examples[$type] item="row"}
					<tr>
						{foreach from=$row item="v"}
						<td>{$v}</td>
						{/foreach}
					</tr>
					{/foreach}
				</table>
			</dd>
			{/foreach}
		</dl>
	</fieldset>

	<p class="help">
		Il est conseillé de procéder à une <a href="{$admin_url}config/backup/">sauvegarde</a> avant de faire un import,
		cela vous permettra de revenir en arrière en cas d'erreur.
	</p>

	<p class="submit">
		<input type="hidden" name="year" value="{$year.id}" />
		{button type="submit" label="Continuer" shape="right" class="main"}
	</p>
</form>

{/if}


{include file="_foot.tpl"}