{include file="admin/_head.tpl" title="Recherche de membre" current="membres" js=1 custom_js=['query_builder.min.js']}

{include file="admin/membres/_nav.tpl" current="recherche"}

{form_errors}

<form method="post" action="{$admin_url}membres/recherche.php" id="queryBuilderForm">
	<fieldset>
		<legend>Rechercher un membre</legend>
		<div class="queryBuilder" id="queryBuilder"></div>
		<p class="actions">
			<label>Trier par 
				<select name="order">
					{foreach from=$colonnes key="colonne" item="config"}
					<option value="{$colonne}"{form_field name="order" selected=$colonne}>{$config.label}</option>
					{/foreach}
				</select>
			</label>
			<label><input type="checkbox" name="desc" value="1" {form_field name="desc" checked=1 default=$desc} /> Tri inversé</label>
			<label>Limiter à <input type="number" value="{$limit}" name="limit" size="5" /> résultats</label>
		</p>
		<p class="submit">
			<input type="submit" value="Chercher &rarr;" id="send" />
			<input type="hidden" name="q" id="jsonQuery" />
			<input type="hidden" name="id" value="{$id}" />
			<input type="submit" name="save" value="{if $id}Enregistrer : {$recherche.intitule|truncate:40:"…":true}{else}Enregistrer cette recherche{/if}" class="minor" />
		</p>
	</fieldset>
</form>

<script type="text/javascript">
var colonnes = {$colonnes|escape:'json'};

{literal}
var traductions = {
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
	"is null": "est nul",
	"is not null": "n'est pas nul",
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
	"Matches ANY of the following conditions:": "Correspond à UN SEUL des critères suivants :",
	"Add a new set of conditions below this one": "-- Ajouter un groupe de critères",
	"Remove this set of conditions": "-- Supprimer ce groupe de critères"
};

var q = new SQLQueryBuilder(colonnes);
q.__ = function (str) {
	return traductions[str];
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

	var i = document.createElement('input');
	i.type = type == 'integer' ? 'number' : type;
	i.value = label;

	if (type == 'button')
	{
		i.className = 'icn action';
	}

	return i;
};
q.init(document.getElementById('queryBuilder'));

$('#queryBuilderForm').onsubmit = function () {
	$('#jsonQuery').value = JSON.stringify(q.export());
};
{/literal}
q.import({$query|escape:'json'});
</script>


{if !empty($result)}
	{if $session->canAccess('membres', Membres::DROIT_ECRITURE)}
		<form method="post" action="{$admin_url}membres/action.php" class="memberList">
	{/if}

	<p class="help">{$result|count} membres trouvés pour cette recherche.</p>
	<table class="list search">
		<thead>
			<tr>
				{if $session->canAccess('membres', Membres::DROIT_ADMIN)}<td class="check"><input type="checkbox" value="Tout cocher / décocher" /></td>{/if}
				{foreach from=$result_header key="c" item="cfg"}
					<td>{$cfg.title}</td>
				{/foreach}
				<td></td>
			</tr>
		</thead>
		<tbody>
			{foreach from=$result item="row"}
				<tr>
					{if $session->canAccess('membres', Membres::DROIT_ADMIN)}<td class="check"><input type="checkbox" name="selected[]" value="{$row.id}" /></td>{/if}
					{foreach from=$row key="key" item="value"}
						<?php $link = false; ?>
						{if isset($result_header[$key])}
							<td>
								{if !$link}
									<a href="{$admin_url}membres/fiche.php?id={$row.id}">
								{/if}

								{$value|raw|display_champ_membre:$result_header[$key]}

								{if !$link}
									<?php $link = true; ?>
									</a>
								{/if}
							</td>
						{/if}
					{/foreach}
					<td class="actions">
						<a class="icn" href="{$admin_url}membres/fiche.php?id={$row.id}" title="Fiche membre">👤</a>
						{if $session->canAccess('membres', Membres::DROIT_ECRITURE)}
						<a class="icn" href="{$admin_url}membres/modifier.php?id={$row.id}" title="Modifier la fiche membre">✎</a>
						{/if}
					</td>
				</tr>
			{/foreach}
		</tbody>
	{if $session->canAccess('membres', Membres::DROIT_ADMIN)}
		{include file="admin/membres/_list_actions.tpl" colspan=count($result_header)+1}
	{/if}
	</table>

	{if $session->canAccess('membres', Membres::DROIT_ECRITURE)}
		</form>
	{/if}

{elseif $result !== null}

	<p class="alert">
		Aucun membre trouvé.
	</p>

	</form>
{/if}


{include file="admin/_foot.tpl"}