<?php
assert(isset($columns));
assert(isset($action_url));
assert(isset($s));
assert(isset($is_admin));
$sql_disabled = !$is_admin || (!$session->canAccess($session::SECTION_CONFIG, $session::ACCESS_ADMIN) && $is_unprotected);
?>

{form_errors}

<form method="post" action="{$action_url}" id="queryBuilderForm">
	<fieldset>
	{if $s->type != $s::TYPE_JSON}
		{if $sql_disabled}
			<legend>Recherche enregistrée</legend>
			<h3>{$s.label}</h3>
		{else}
			<legend>Recherche SQL</legend>
			<dl>
				{input type="textarea" name="sql_query" cols="100" rows="10" required=1 label="Requête SQL" help="Si aucune limite n'est précisée, une limite de 100 résultats sera appliquée." default=$s.content}
				{if $session->canAccess($session::SECTION_CONFIG, $session::ACCESS_ADMIN)}
					{input type="checkbox" name="unprotected" value=1 label="Autoriser l'accès à toutes les tables de la base de données" default=$is_unprotected}
					<dd class="help">Attention : en cochant cette case vous autorisez la requête à lire toutes les données de toutes les tables de la base de données&nbsp;!</dd>
				{/if}

				<dd class="help">
					<details>
						<summary class="block help">Schéma SQL des tables</summary>
						<pre class="block help">{foreach from=$schema item="table"}{$table}<br />{/foreach}</pre>
					</details>
				</dd>
			</dl>
			<p class="submit">
				{button type="submit" name="run" label="Exécuter" shape="search" class="main"}
				<input type="hidden" name="id" value="{$s.id}" />
				{if $s->exists()}
					{button name="save" value=1 type="submit" label="Enregistrer : %s"|args:$s.label|truncate:40:"…":true shape="upload"}
					{button name="save_new" value=1 type="submit" label="Enregistrer nouvelle recherche" shape="plus"}
				{else}
					{button name="save" value=1 type="submit" label="Enregistrer cette recherche" shape="upload"}
				{/if}
				{if $session->canAccess($session::SECTION_CONFIG, $session::ACCESS_ADMIN)}
					{linkbutton href="!config/advanced/sql.php" target="_blank" shape="menu" label="Voir le schéma SQL complet"}
				{/if}
			</p>
		{/if}
	{else}
		<legend>Rechercher</legend>
		<div class="queryBuilder" id="queryBuilder"></div>
		<p class="submit">
			{button name="search" value=1 type="submit" label="Chercher" shape="search" id="send" class="main"}
			<input type="hidden" name="q" id="jsonQuery" />
			<input type="hidden" name="id" value="{$s.id}" />
			{if $s.id}
				{button name="save" value=1 type="submit" label="Enregistrer : %s"|args:$s.label|truncate:40:"…":true shape="upload"}
				{button name="save_new" value=1 type="submit" label="Enregistrer nouvelle recherche" shape="plus"}
			{else}
				{button name="save" value=1 type="submit" label="Enregistrer cette recherche" shape="upload"}
			{/if}
			{if $is_admin}
				{button name="to_sql" value=1 type="submit" label="Recherche SQL" shape="edit"}
			{/if}
		</p>
	{/if}
	</fieldset>
</form>

<script type="text/javascript">
var columns = {$columns|escape:'json'};

{literal}
var translations = {
	"after": "après",
	"before": "avant",
	"is equal to": "est égal à",
	"is equal to one of": "est égal à une des ces options",
	"is not equal to one of": "n'est pas égal à une des ces options",
	"is not equal to": "n'est pas égal à",
	"is greater than": "est supérieur à",
	"is greater than or equal to": "est supérieur ou égal à",
	"is less than": "est inférieur à",
	"is less than or equal to": "est inférieur ou égal à",
	"is between": "est situé entre",
	"is not between": "n'est pas situé entre",
	"is null": "est renseigné",
	"is not null": "n'est pas renseigné",
	"begins with": "commence par",
	"doesn't begin with": "ne commence pas par",
	"ends with": "se termine par",
	"doesn't end with": "ne se termine pas par",
	"contains": "contient",
	"doesn't contain": "ne contient pas",
	"matches one of": "correspond à",
	"is true": "oui",
	"is false": "non",
	"Matches ALL of the following conditions:": "Correspond à TOUS les critères suivants :",
	"Matches ANY of the following conditions:": "Correspond à UN des critères suivants :",
	"Add a new set of conditions below this one": "-- Ajouter un groupe de critères",
	"Remove this set of conditions": "-- Supprimer ce groupe de critères"
};

var q = new SQLQueryBuilder(columns);
q.__ = function (str) {
	return translations[str];
};
q.loadDefaultOperators();
q.buildInput = function (type, label, column) {
	if (label == '+')
	{
		label = '➕';
	}
	else if (label == '-')
	{
		label = '➖';
	}

	if (type == 'button')
	{
		var i = document.createElement('button');
		i.className = 'icn-btn';
		i.type = 'button';
		i.setAttribute('data-icon', label);
	}
	else {
		var i = document.createElement('input');
		i.type = type == 'integer' ? 'number' : type;
		i.value = label;
	}

	return i;
};
q.init(document.getElementById('queryBuilder'));

$('#queryBuilderForm').onsubmit = function () {
	$('#jsonQuery').value = JSON.stringify(q.export());
};
{/literal}
q.import({$s.content|raw});
</script>
